<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/views/icons.php';
require __DIR__ . '/../includes/views/admin.php';

if (is_admin()) redirect('admin/');

$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $key = 'login:' . client_ip();
        // Max 5 attempts per 15 minutes per IP, then a 15-minute lockout.
        [$allowed, $retry, $remaining] = rate_limit($key, 5, 900, 900);
        $password = $_POST['password'] ?? '';
        if (!form_post_is_valid()) {
            $error = 'Сессия устарела. Обновите страницу и попробуйте снова.';
        } elseif (!$allowed) {
            $error = 'Слишком много попыток. Повторите через ' . max(1, (int) ceil($retry / 60)) . ' мин.';
        } elseif (!is_string($password) || $password === '' || strlen($password) > 128 || !password_verify($password, (string) cfg('admin_password_hash'))) {
            $error = $remaining > 0 ? "Неверный пароль. Осталось попыток: {$remaining}" : 'Неверный пароль. Вход временно заблокирован.';
        } else {
            reset_rate_limit($key);
            admin_login();
            redirect('admin/');
        }
    } catch (Throwable $e) {
        log_error('admin/login', $e);
        $error = 'Внутренняя ошибка сервера. Попробуйте позже.';
    }
}

admin_page_start('Вход');
?>
<form method="post" class="card p-6 sm:p-8" autocomplete="on">
  <h1 class="text-2xl font-extrabold tracking-tight">Вход в админ-панель</h1>
  <p class="mt-1 text-sm text-mute">Только для персонала клуба</p>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label for="password" class="label mt-6">Пароль</label>
  <input id="password" name="password" type="password" class="field" autocomplete="current-password" maxlength="128" required autofocus
         <?= $error ? 'aria-invalid="true" aria-describedby="login-err"' : '' ?>>
  <?php if ($error): ?><p id="login-err" role="alert" class="mt-3 text-sm text-red-400"><?= e($error) ?></p><?php endif; ?>
  <button type="submit" class="<?= btn_class('primary', 'md', 'mt-6 w-full') ?>">Войти</button>
</form>
<?php admin_page_end();
