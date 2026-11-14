<?php
/**
 * Revenue reports. Revenue = booked hours × price per hour.
 *  - counted: CONFIRMED + COMPLETED bookings (completed early → the actual, shortened time)
 *  - pending: PENDING bookings are shown separately ("ожидают подтверждения")
 *  - a booking belongs to the local day on which it starts
 *  - price: the price saved with the booking; older bookings use the current price
 */
defined('APP') or exit;

const REPORT_TYPES = ['day' => 'День', 'week' => 'Неделя', 'month' => 'Месяц'];
const MONTHS_NOM = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
const MONTHS_GEN = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
const WEEKDAYS_SHORT = ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'];

function local_date(string $ymd): DateTimeImmutable
{
    return DateTimeImmutable::createFromFormat('!Y-m-d', $ymd, tz_local());
}

/** [from (incl), to (excl), title, prevDate, nextDate, days[]] in local dates. */
function report_period(string $type, string $date): array
{
    $d = local_date(is_valid_date($date) ? $date : today_local());
    switch ($type) {
        case 'week':
            $from = $d->modify('-' . ((int) $d->format('N') - 1) . ' days');
            $to = $from->modify('+7 days');
            $last = $to->modify('-1 day');
            $title = (int) $from->format('j') . ' ' . MONTHS_GEN[(int) $from->format('n') - 1]
                . ' – ' . (int) $last->format('j') . ' ' . MONTHS_GEN[(int) $last->format('n') - 1] . ' ' . $last->format('Y');
            $prev = $from->modify('-7 days');
            $next = $to;
            break;
        case 'month':
            $from = $d->modify('first day of this month');
            $to = $from->modify('first day of next month');
            $title = mb_convert_case(MONTHS_NOM[(int) $from->format('n') - 1], MB_CASE_TITLE) . ' ' . $from->format('Y');
            $prev = $from->modify('first day of previous month');
            $next = $to;
            break;
        default:
            $type = 'day';
            $from = $d;
            $to = $d->modify('+1 day');
            $title = (int) $d->format('j') . ' ' . MONTHS_GEN[(int) $d->format('n') - 1] . ' ' . $d->format('Y')
                . ', ' . WEEKDAYS_SHORT[(int) $d->format('N') - 1];
            $prev = $d->modify('-1 day');
            $next = $to;
    }
    $days = [];
    for ($x = $from; $x < $to; $x = $x->modify('+1 day')) $days[] = $x->format('Y-m-d');
    return [
        'type' => $type, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'title' => $title,
        'prev' => $prev->format('Y-m-d'), 'next' => $next->format('Y-m-d'), 'days' => $days,
        'isCurrent' => $from->format('Y-m-d') <= today_local() && today_local() < $to->format('Y-m-d'),
    ];
}

/** Bookings that start within [fromDate, toDate) local, with computed hours and amount. */
function report_rows(string $fromDate, string $toDate): array
{
    $hasPrice = true;
    try {
        ensure_price_column();
    } catch (Throwable $e) {
        $hasPrice = false; // no ALTER privilege → fall back to current prices
    }
    $st = db()->prepare('SELECT id, table_id, name, phone, guests, comment, start_at, end_at, status, source, created_at'
        . ($hasPrice ? ', price_per_hour' : '') . ' FROM bookings WHERE start_at >= ? AND start_at < ? ORDER BY start_at');
    $st->execute([db_dt(local_to_utc($fromDate, 0)), db_dt(local_to_utc($toDate, 0))]);
    $rows = [];
    foreach ($st->fetchAll() as $b) {
        $s = from_db($b['start_at']);
        $hours = max(0, from_db($b['end_at'])->getTimestamp() - $s->getTimestamp()) / 3600;
        $price = (int) ($b['price_per_hour'] ?? 0) ?: table_price($b['table_id']);
        $b['hours'] = $hours;
        $b['price'] = $price;
        $b['amount'] = (int) round($hours * $price);
        $b['localDate'] = $s->setTimezone(tz_local())->format('Y-m-d');
        $rows[] = $b;
    }
    return $rows;
}

function report_summary(array $period): array
{
    $rows = report_rows($period['from'], $period['to']);
    $paid = ['CONFIRMED', 'COMPLETED'];
    $sum = ['revenue' => 0, 'hours' => 0.0, 'count' => 0, 'guests' => 0, 'pendingAmount' => 0, 'pendingCount' => 0,
            'cancelled' => 0, 'walkins' => 0, 'online' => 0, 'vipRevenue' => 0, 'regularRevenue' => 0];
    $byDay = array_fill_keys($period['days'], ['revenue' => 0, 'count' => 0, 'hours' => 0.0]);
    $byTable = [];
    foreach (TABLES as $id => $t) $byTable[$id] = ['name' => $t['name'], 'type' => $t['type'], 'revenue' => 0, 'count' => 0, 'hours' => 0.0];

    foreach ($rows as $b) {
        if ($b['status'] === 'CANCELLED') { $sum['cancelled']++; continue; }
        if ($b['status'] === 'PENDING') { $sum['pendingAmount'] += $b['amount']; $sum['pendingCount']++; continue; }
        if (!in_array($b['status'], $paid, true)) continue;
        $sum['revenue'] += $b['amount'];
        $sum['hours'] += $b['hours'];
        $sum['count']++;
        $sum['guests'] += (int) $b['guests'];
        $sum[$b['source'] === 'ADMIN' ? 'walkins' : 'online']++;
        $sum[(TABLES[$b['table_id']]['type'] ?? '') === 'VIP' ? 'vipRevenue' : 'regularRevenue'] += $b['amount'];
        if (isset($byDay[$b['localDate']])) {
            $byDay[$b['localDate']]['revenue'] += $b['amount'];
            $byDay[$b['localDate']]['count']++;
            $byDay[$b['localDate']]['hours'] += $b['hours'];
        }
        if (isset($byTable[$b['table_id']])) {
            $byTable[$b['table_id']]['revenue'] += $b['amount'];
            $byTable[$b['table_id']]['count']++;
            $byTable[$b['table_id']]['hours'] += $b['hours'];
        }
    }
    $capacityHours = count(TABLES) * 24 * count($period['days']);
    $sum['avgCheck'] = $sum['count'] ? (int) round($sum['revenue'] / $sum['count']) : 0;
    $sum['occupancy'] = $capacityHours ? $sum['hours'] / $capacityHours * 100 : 0;
    return ['sum' => $sum, 'byDay' => $byDay, 'byTable' => $byTable, 'rows' => $rows];
}

/** Previous period's revenue for the "vs last period" comparison. */
function report_previous_revenue(array $period): int
{
    $prev = report_period($period['type'], $period['prev']);
    $total = 0;
    foreach (report_rows($prev['from'], $prev['to']) as $b) {
        if (in_array($b['status'], ['CONFIRMED', 'COMPLETED'], true)) $total += $b['amount'];
    }
    return $total;
}

function money(int|float $v): string
{
    return number_format((float) $v, 0, ',', ' ') . ' сом';
}

function hours_label(float $h): string
{
    $s = rtrim(rtrim(number_format($h, 1, ',', ' '), '0'), ',');
    return $s . ' ч';
}

/** CSV-safe cell: neutralize spreadsheet formulas. */
function csv_cell($v): string
{
    $v = (string) $v;
    if ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) $v = "'" . $v;
    return $v;
}
