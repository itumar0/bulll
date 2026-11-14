<?php
require __DIR__ . '/../includes/bootstrap.php';

api(function () {
    require_method('GET');
    json_response(['ok' => true, 'now' => iso(now_utc()), 'tables' => table_statuses(false)]);
});
