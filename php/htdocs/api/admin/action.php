<?php
/** POST {id, action: confirm|cancel|complete|extend, minutes?} */
require __DIR__ . '/../../includes/bootstrap.php';

api(function () {
    require_method('POST');
    require_admin_api();
    $raw = read_json(1024);
    $action = $raw['action'] ?? null;
    if (!in_array($action, ['confirm', 'cancel', 'complete', 'extend'], true)) json_error('Неизвестное действие', 422);
    $keys = $action === 'extend' ? ['id', 'action', 'minutes'] : ['id', 'action'];
    $v = (new Validator($raw, $keys))->int('id', 1, PHP_INT_MAX, 'Некорректный номер брони');
    if ($action === 'extend') $v->int('minutes', 30, 300, 'Шаг 30 минут', 30);
    $in = $v->result();
    $b = $action === 'extend' ? extend_booking($in['id'], $in['minutes']) : set_booking_status($in['id'], $action);
    json_response(['ok' => true, 'booking' => booking_public_admin($b)]);
});
