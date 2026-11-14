<?php
defined('APP') or exit;

function wa_link(string $number, ?string $text = null): string
{
    $n = preg_replace('/\D/', '', $number);
    return 'https://wa.me/' . $n . ($text !== null ? '?text=' . rawurlencode($text) : '');
}

function booking_message(array $b, string $tableName): string
{
    $start = from_db($b['start_at']);
    $end = from_db($b['end_at']);
    return implode("\n", [
        'Новая бронь 🎱',
        'Имя: ' . $b['name'],
        'Телефон: ' . $b['phone'],
        'Стол: ' . $tableName,
        'Дата: ' . fmt_date($start),
        'Время: ' . fmt_time($start) . ' – ' . fmt_time($end),
        'Гостей: ' . $b['guests'],
        'Комментарий: ' . (trim((string) ($b['comment'] ?? '')) !== '' ? $b['comment'] : '—'),
        '№ брони: ' . $b['id'],
    ]);
}
