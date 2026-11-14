<?php
/** Booking rules: overlap checks, create/extend/status changes, live table statuses. */
defined('APP') or exit;

class BookingError extends RuntimeException
{
    public function __construct(string $message, public int $status = 400, public ?string $field = null)
    {
        parent::__construct($message);
    }
}

/** Run $fn inside a transaction that holds a row lock on the table → bookings per table are serialized. */
function with_table_lock(string $tableId, callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT id FROM pool_tables WHERE id = ? FOR UPDATE');
        $st->execute([$tableId]);
        if (!$st->fetch()) throw new BookingError('Стол не найден', 404);
        $result = $fn($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/** Active booking overlapping [start, end) — half-open, so back-to-back is allowed. */
function find_conflict(PDO $pdo, string $tableId, DateTimeImmutable $start, DateTimeImmutable $end, ?int $excludeId = null): ?array
{
    $sql = 'SELECT id FROM bookings WHERE table_id = ? AND status IN (' . placeholders(ACTIVE_STATUSES) . ')
            AND start_at < ? AND end_at > ?' . ($excludeId ? ' AND id <> ?' : '') . ' LIMIT 1';
    $params = array_merge([$tableId], ACTIVE_STATUSES, [db_dt($end), db_dt($start)]);
    if ($excludeId) $params[] = $excludeId;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetch() ?: null;
}

function get_booking(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM bookings WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function create_booking(array $a): array
{
    $meta = TABLES[$a['tableId']];
    if ($a['guests'] > $meta['capacity']) {
        throw new BookingError("{$meta['name']}: максимум {$meta['capacity']} гостей", 400, 'guests');
    }
    if ($a['endAt'] <= $a['startAt']) throw new BookingError('Некорректный интервал');

    $id = with_table_lock($a['tableId'], function (PDO $pdo) use ($a) {
        if (find_conflict($pdo, $a['tableId'], $a['startAt'], $a['endAt'])) {
            throw new BookingError('Это время уже занято. Выберите другой слот или стол.', 409, 'startMinutes');
        }
        $pdo->prepare('INSERT INTO bookings (table_id, name, phone, guests, comment, start_at, end_at, status, source)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                $a['tableId'], $a['name'], $a['phone'], $a['guests'], $a['comment'] ?: null,
                db_dt($a['startAt']), db_dt($a['endAt']), $a['status'], $a['source'],
            ]);
        return (int) $pdo->lastInsertId();
    });
    return get_booking($id);
}

/** Website booking interval: local date + start minutes. Club is open 24/7. */
function website_interval(string $date, int $startMinutes, int $hours): array
{
    $start = local_to_utc($date, $startMinutes);
    $end = $start->modify('+' . ($hours * 60) . ' minutes');
    $now = now_utc();
    if ($start < $now->modify('-25 minutes')) throw new BookingError('Нельзя забронировать прошедшее время', 400, 'startMinutes');
    if ($start > $now->modify('+60 days')) throw new BookingError('Бронирование доступно максимум на 60 дней вперёд', 400, 'date');
    return [$start, $end];
}

function extend_booking(int $id, int $minutes): array
{
    $b = get_booking($id);
    if (!$b) throw new BookingError('Бронь не найдена', 404);
    if (!in_array($b['status'], ACTIVE_STATUSES, true)) throw new BookingError('Можно продлить только активную бронь');
    $oldEnd = from_db($b['end_at']);
    $newEnd = $oldEnd->modify("+{$minutes} minutes");
    with_table_lock($b['table_id'], function (PDO $pdo) use ($b, $oldEnd, $newEnd, $id) {
        if (find_conflict($pdo, $b['table_id'], $oldEnd, $newEnd, $id)) {
            throw new BookingError('Продление пересекается со следующей бронью', 409);
        }
        $pdo->prepare('UPDATE bookings SET end_at = ? WHERE id = ?')->execute([db_dt($newEnd), $id]);
    });
    return get_booking($id);
}

function set_booking_status(int $id, string $action): array
{
    $b = get_booking($id);
    if (!$b) throw new BookingError('Бронь не найдена', 404);
    $active = in_array($b['status'], ACTIVE_STATUSES, true);
    $pdo = db();
    switch ($action) {
        case 'confirm':
            if ($b['status'] !== 'PENDING') throw new BookingError('Подтвердить можно только ожидающую бронь');
            $pdo->prepare("UPDATE bookings SET status = 'CONFIRMED' WHERE id = ?")->execute([$id]);
            break;
        case 'cancel':
            if (!$active) throw new BookingError('Бронь уже неактивна');
            $pdo->prepare("UPDATE bookings SET status = 'CANCELLED' WHERE id = ?")->execute([$id]);
            break;
        case 'complete':
            if (!$active) throw new BookingError('Бронь уже неактивна');
            // Early finish: cut the interval at "now" so the table frees immediately.
            $now = now_utc();
            $end = (from_db($b['start_at']) < $now && from_db($b['end_at']) > $now) ? $now : from_db($b['end_at']);
            $pdo->prepare("UPDATE bookings SET status = 'COMPLETED', end_at = ? WHERE id = ?")->execute([db_dt($end), $id]);
            break;
        default:
            throw new BookingError('Неизвестное действие');
    }
    return get_booking($id);
}

/**
 * Live statuses, derived purely from the clock: busy while start <= now < end,
 * so a table frees itself automatically when the time is over.
 * @return array<int,array<string,mixed>>
 */
function table_statuses(bool $withPersonalData): array
{
    $now = now_utc();
    $horizon = $now->modify('+24 hours');
    $tables = db()->query('SELECT id, name, type, capacity FROM pool_tables ORDER BY sort_order')->fetchAll();
    $st = db()->prepare('SELECT * FROM bookings WHERE status IN (' . placeholders(ACTIVE_STATUSES) . ')
                         AND end_at > ? AND start_at < ? ORDER BY start_at');
    $st->execute(array_merge(ACTIVE_STATUSES, [db_dt($now), db_dt($horizon)]));
    $byTable = [];
    foreach ($st->fetchAll() as $b) $byTable[$b['table_id']][] = $b;

    $out = [];
    foreach ($tables as $t) {
        $own = $byTable[$t['id']] ?? [];
        $current = null;
        foreach ($own as $b) {
            if (from_db($b['start_at']) <= $now && from_db($b['end_at']) > $now) { $current = $b; break; }
        }
        $busyUntil = null;
        if ($current) {
            $busyUntil = from_db($current['end_at']);
            foreach ($own as $b) { // merge back-to-back bookings
                if (from_db($b['start_at']) <= $busyUntil && from_db($b['end_at']) > $busyUntil) $busyUntil = from_db($b['end_at']);
            }
        }
        $next = null;
        foreach ($own as $b) {
            if (from_db($b['start_at']) > ($busyUntil ?? $now)) { $next = $b; break; }
        }
        $state = $current ? 'busy' : (($next && from_db($next['start_at'])->getTimestamp() - $now->getTimestamp() <= 3600) ? 'soon' : 'free');

        $row = [
            'id' => $t['id'],
            'name' => $t['name'],
            'type' => $t['type'],
            'capacity' => (int) $t['capacity'],
            'state' => $state,
            'busyUntil' => $busyUntil ? iso($busyUntil) : null,
            'nextStart' => $next ? iso(from_db($next['start_at'])) : null,
        ];
        if ($withPersonalData) {
            $row['current'] = $current ? booking_public_admin($current) : null;
            $row['next'] = $next ? booking_public_admin($next) : null;
        }
        $out[] = $row;
    }
    return $out;
}

/** Busy intervals (no personal data) for the booking form of a local date. */
function busy_intervals(string $date): array
{
    $from = local_to_utc($date, 0);
    $to = local_to_utc($date, MAX_END_MIN);
    $st = db()->prepare('SELECT table_id, start_at, end_at FROM bookings WHERE status IN (' . placeholders(ACTIVE_STATUSES) . ')
                         AND start_at < ? AND end_at > ? ORDER BY start_at');
    $st->execute(array_merge(ACTIVE_STATUSES, [db_dt($to), db_dt($from)]));
    return array_map(fn ($r) => [
        'tableId' => $r['table_id'],
        'start' => iso(from_db($r['start_at'])),
        'end' => iso(from_db($r['end_at'])),
    ], $st->fetchAll());
}

/** Booking as JSON for the admin panel. */
function booking_public_admin(array $b): array
{
    return [
        'id' => (int) $b['id'],
        'tableId' => $b['table_id'],
        'tableName' => TABLES[$b['table_id']]['name'] ?? $b['table_id'],
        'name' => $b['name'],
        'phone' => $b['phone'],
        'guests' => (int) $b['guests'],
        'comment' => $b['comment'],
        'startAt' => iso(from_db($b['start_at'])),
        'endAt' => iso(from_db($b['end_at'])),
        'status' => $b['status'],
        'source' => $b['source'],
        'createdAt' => iso(from_db($b['created_at'])),
    ];
}

/**
 * Admin list of bookings. Filters: date (local day, overlap), tableId, status, q (name or phone digits).
 * @return array<int,array<string,mixed>> rows from the DB
 */
function list_bookings(array $f): array
{
    $where = [];
    $params = [];
    $date = (string) ($f['date'] ?? '');
    if ($date !== '') {
        if (!is_valid_date($date)) throw new BookingError('Некорректная дата');
        // Bookings overlapping the local calendar day (incl. ones that started before midnight).
        $where[] = 'start_at < ? AND end_at > ?';
        $params[] = db_dt(local_to_utc($date, 24 * 60));
        $params[] = db_dt(local_to_utc($date, 0));
    }
    $tableId = (string) ($f['tableId'] ?? '');
    if ($tableId !== '') {
        if (!isset(TABLES[$tableId])) throw new BookingError('Некорректный стол');
        $where[] = 'table_id = ?';
        $params[] = $tableId;
    }
    $status = (string) ($f['status'] ?? '');
    if ($status !== '') {
        if (!in_array($status, STATUSES, true)) throw new BookingError('Некорректный статус');
        $where[] = 'status = ?';
        $params[] = $status;
    }
    $q = sanitize_text(mb_substr((string) ($f['q'] ?? ''), 0, 60));
    if ($q !== '') {
        $digits = preg_replace('/\D/', '', $q);
        $cond = 'name LIKE ?';
        $params[] = '%' . addcslashes($q, '%_\\') . '%';
        if (strlen($digits) >= 3) {
            $cond .= ' OR phone LIKE ?';
            $params[] = '%' . $digits . '%';
        }
        $where[] = '(' . $cond . ')';
    }
    $sql = 'SELECT * FROM bookings' . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY start_at ' . ($date !== '' ? 'ASC' : 'DESC') . ' LIMIT 500';
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Validate input for a manual (walk-in) booking. Throws ValidationError. */
function validate_manual_booking(array $raw): array
{
    return (new Validator($raw, ['name', 'phone', 'tableId', 'date', 'startMinutes', 'startNow', 'durationHours', 'guests', 'comment']))
        ->name()->phone()->tableId()->date('date', true)
        ->int('startMinutes', 0, LAST_START_MIN, 'Некорректное время', SLOT_MIN, true)
        ->bool('startNow')
        ->number('durationHours', 0.5, MAX_DURATION_H, 0.5, 'От 30 минут до 5 часов')
        ->int('guests', 1, 10, 'От 1 до 10 гостей')
        ->text('comment', 500)
        ->result();
}

/** Create a confirmed booking made by the admin (starts now or at a chosen time). */
function create_manual_booking(array $in): array
{
    if (!empty($in['startNow']) || !isset($in['startMinutes'])) {
        $now = now_utc();
        $start = $now->setTime((int) $now->format('H'), (int) $now->format('i'));
    } else {
        $start = local_to_utc($in['date'] ?? today_local(), $in['startMinutes']);
    }
    $end = $start->modify('+' . (int) round($in['durationHours'] * 60) . ' minutes');
    return create_booking([
        'tableId' => $in['tableId'], 'name' => $in['name'], 'phone' => $in['phone'], 'guests' => $in['guests'],
        'comment' => $in['comment'], 'startAt' => $start, 'endAt' => $end, 'status' => 'CONFIRMED', 'source' => 'ADMIN',
    ]);
}

/** Message the admin sends to a client from the panel. */
function client_message(array $b): string
{
    $s = from_db($b['start_at']);
    $e = from_db($b['end_at']);
    return 'Здравствуйте, ' . $b['name'] . '! Это «' . CLUB['name'] . '». По вашей брони №' . $b['id'] . ': '
        . (TABLES[$b['table_id']]['name'] ?? $b['table_id']) . ', ' . fmt_date($s) . ', ' . fmt_time($s) . ' – ' . fmt_time($e) . '.';
}
