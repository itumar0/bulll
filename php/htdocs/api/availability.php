<?php
require __DIR__ . '/../includes/bootstrap.php';

api(function () {
    require_method('GET');
    $date = (string) ($_GET['date'] ?? '');
    if (!is_valid_date($date)) json_error('Некорректная дата', 400);
    json_response(['ok' => true, 'date' => $date, 'now' => iso(now_utc()), 'busy' => busy_intervals($date)]);
});
