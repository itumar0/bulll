<?php
require __DIR__ . '/../../includes/bootstrap.php';

api(function () {
    require_method('GET');
    require_admin_api();
    json_response(['ok' => true, 'csrf' => csrf_token(), 'now' => iso(now_utc()), 'tables' => table_statuses(true)]);
});
