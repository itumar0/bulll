<?php
require __DIR__ . '/../../includes/bootstrap.php';

api(function () {
    require_method('POST');
    require_same_origin();
    $key = 'login:' . client_ip();
    // Max 5 attempts per 15 minutes per IP, then a 15-minute lockout.
    [$allowed, $retry, $remaining] = rate_limit($key, 5, 900, 900);
    if (!$allowed) {
        json_error('Слишком много попыток. Повторите через ' . max(1, (int) ceil($retry / 60)) . ' мин.', 429, ['retryAfter' => $retry]);
    }
    $in = read_json(1024);
    $password = $in['password'] ?? null;
    if (!is_string($password) || $password === '' || strlen($password) > 128 || count($in) !== 1) {
        json_error('Введите пароль', 422);
    }
    if (!password_verify($password, (string) cfg('admin_password_hash'))) {
        json_error($remaining > 0 ? "Неверный пароль. Осталось попыток: {$remaining}" : 'Неверный пароль. Вход временно заблокирован.', 401);
    }
    reset_rate_limit($key);
    admin_login();
    // The CSRF token must be sent back in the X-CSRF-Token header on every admin POST.
    json_response(['ok' => true, 'csrf' => csrf_token()]);
});
