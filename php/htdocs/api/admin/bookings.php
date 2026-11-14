<?php
/** GET: list bookings (filters: date, tableId, status, q). POST: manual booking (walk-in). */
require __DIR__ . '/../../includes/bootstrap.php';

api(function () {
    require_admin_api();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $where = [];
        $params = [];
        $date = (string) ($_GET['date'] ?? '');
        if ($date !== '') {
            if (!is_valid_date($date)) json_error('Некорректная дата', 400);
            // Bookings overlapping the local calendar day (incl. ones that started before midnight).
            $where[] = 'start_at < ? AND end_at > ?';
            $params[] = db_dt(local_to_utc($date, 24 * 60));
            $params[] = db_dt(local_to_utc($date, 0));
        }
        $tableId = (string) ($_GET['tableId'] ?? '');
        if ($tableId !== '') {
            if (!isset(TABLES[$tableId])) json_error('Некорректный стол', 400);
            $where[] = 'table_id = ?';
            $params[] = $tableId;
        }
        $status = (string) ($_GET['status'] ?? '');
        if ($status !== '') {
            if (!in_array($status, STATUSES, true)) json_error('Некорректный статус', 400);
            $where[] = 'status = ?';
            $params[] = $status;
        }
        $q = sanitize_text(mb_substr((string) ($_GET['q'] ?? ''), 0, 60));
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
        json_response(['ok' => true, 'bookings' => array_map('booking_public_admin', $st->fetchAll())]);
    }

    if ($method === 'POST') {
        $in = (new Validator(read_json(), ['name', 'phone', 'tableId', 'date', 'startMinutes', 'startNow', 'durationHours', 'guests', 'comment']))
            ->name()->phone()->tableId()->date('date', true)
            ->int('startMinutes', 0, LAST_START_MIN, 'Некорректное время', SLOT_MIN, true)
            ->bool('startNow')
            ->number('durationHours', 0.5, MAX_DURATION_H, 0.5, 'От 30 минут до 5 часов')
            ->int('guests', 1, 10, 'От 1 до 10 гостей')
            ->text('comment', 500)
            ->result();
        if ($in['startNow'] || !isset($in['startMinutes'])) {
            $start = now_utc()->setTime((int) now_utc()->format('H'), (int) now_utc()->format('i'));
        } else {
            $start = local_to_utc($in['date'] ?? today_local(), $in['startMinutes']);
        }
        $end = $start->modify('+' . (int) round($in['durationHours'] * 60) . ' minutes');
        $b = create_booking([
            'tableId' => $in['tableId'], 'name' => $in['name'], 'phone' => $in['phone'], 'guests' => $in['guests'],
            'comment' => $in['comment'], 'startAt' => $start, 'endAt' => $end, 'status' => 'CONFIRMED', 'source' => 'ADMIN',
        ]);
        json_response(['ok' => true, 'id' => (int) $b['id']], 201);
    }

    header('Allow: GET, POST');
    json_error('Method not allowed', 405);
});
