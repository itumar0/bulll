<?php
/** Time helpers. Storage: UTC "Y-m-d H:i:s". Club time: Asia/Bishkek (UTC+6, no DST). */
defined('APP') or exit;

function tz_local(): DateTimeZone
{
    static $tz = null;
    return $tz ??= new DateTimeZone('Asia/Bishkek');
}

function tz_utc(): DateTimeZone
{
    static $tz = null;
    return $tz ??= new DateTimeZone('UTC');
}

function now_utc(): DateTimeImmutable
{
    return new DateTimeImmutable('now', tz_utc());
}

/** Local date (Y-m-d) + minutes after local midnight → UTC instant. */
function local_to_utc(string $date, int $minutes): DateTimeImmutable
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date, tz_local());
    return $d->modify(($minutes >= 0 ? '+' : '') . $minutes . ' minutes')->setTimezone(tz_utc());
}

function today_local(): string
{
    return now_utc()->setTimezone(tz_local())->format('Y-m-d');
}

function db_dt(DateTimeImmutable $d): string
{
    return $d->setTimezone(tz_utc())->format('Y-m-d H:i:s');
}

function from_db(string $s): DateTimeImmutable
{
    return new DateTimeImmutable($s, tz_utc());
}

function iso(DateTimeImmutable $d): string
{
    return $d->setTimezone(tz_utc())->format('Y-m-d\TH:i:s\Z');
}

function fmt_time(DateTimeImmutable $d): string
{
    return $d->setTimezone(tz_local())->format('H:i');
}

function fmt_date(DateTimeImmutable $d): string
{
    return $d->setTimezone(tz_local())->format('d.m.Y');
}

function is_valid_date(string $s): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return false;
    [$y, $m, $d] = array_map('intval', explode('-', $s));
    return checkdate($m, $d, $y);
}
