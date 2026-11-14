<?php
require __DIR__ . '/../../includes/bootstrap.php';

api(function () {
    require_method('POST');
    require_admin_api();
    start_admin_session();
    admin_logout();
    json_response(['ok' => true]);
});
