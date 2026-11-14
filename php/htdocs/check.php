<?php
/**
 * Server self-check for troubleshooting. Available ONLY when 'debug' => true in includes/config.php
 * (otherwise it answers 404). Shows no passwords or personal data.
 */
declare(strict_types=1);

$results = [];
$add = function (string $name, bool $ok, string $info = '') use (&$results) { $results[] = [$name, $ok, $info]; };

// Load the app step by step so we can tell exactly where it breaks.
try {
    require __DIR__ . '/includes/bootstrap.php';
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Не удалось загрузить includes/bootstrap.php:\n" . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine();
    exit;
}
if (!cfg('debug')) { http_response_code(404); exit; }

$add('PHP версии 8.0 или новее', PHP_VERSION_ID >= 80000, PHP_VERSION);
foreach (['pdo_mysql', 'mbstring', 'json', 'session', 'gd', 'fileinfo'] as $ext) {
    $add("Расширение {$ext}", extension_loaded($ext), in_array($ext, ['gd', 'fileinfo'], true) ? 'нужно для загрузки логотипа' : '');
}
$add('Функция imagewebp (WebP)', function_exists('imagewebp'), 'если нет — логотип сохранится в PNG');
$disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
$needed = ['ini_set', 'session_start', 'session_regenerate_id', 'password_verify', 'random_bytes', 'random_int', 'header', 'setcookie'];
$blocked = array_values(array_intersect($needed, $disabled));
$add('Нужные функции не отключены хостингом', !$blocked, $blocked ? 'отключены: ' . implode(', ', $blocked) : '');

// Sessions (the admin panel needs them).
$savePath = (string) (ini_get('session.save_path') ?: sys_get_temp_dir());
$savePath = preg_replace('/^\d+;/', '', $savePath);
$add('Папка сессий доступна для записи', is_dir($savePath) && is_writable($savePath), $savePath);
try {
    start_admin_session();
    $_SESSION['check'] = ($_SESSION['check'] ?? 0) + 1;
    $add('Сессии PHP запускаются', session_status() === PHP_SESSION_ACTIVE, 'обновите страницу: счётчик должен расти → ' . $_SESSION['check']);
} catch (Throwable $e) {
    $add('Сессии PHP запускаются', false, $e->getMessage());
}

// Database.
try {
    $pdo = db();
    $add('Подключение к MySQL', true, (string) $pdo->query('SELECT VERSION()')->fetchColumn());
    foreach (['pool_tables', 'bookings', 'rate_limits'] as $t) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $st->execute([$t]);
        $exists = (int) $st->fetchColumn() === 1;
        $add("Таблица {$t}", $exists, $exists ? '' : 'не найдена — импортируйте schema.sql через phpMyAdmin');
    }
    $add('Столы в pool_tables', (int) $pdo->query('SELECT COUNT(*) FROM pool_tables')->fetchColumn() === 8, $pdo->query('SELECT COUNT(*) FROM pool_tables')->fetchColumn() . ' из 8');
    rate_limit('selfcheck', 1000, 60, 60);
    $add('Запись в базу (rate_limits)', true);
} catch (Throwable $e) {
    $add('Подключение к MySQL / таблицы', false, $e->getMessage() . ' — проверьте db_host, db_name, db_user, db_pass в config.php и импорт schema.sql');
}

// Config.
$hash = (string) cfg('admin_password_hash');
$add('Хэш пароля админки в config.php', str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2b$') || str_starts_with($hash, '$2a$'), 'должен начинаться с $2y$');
$add('Пароль админки сменён с 1111', !password_verify('1111', $hash), 'рекомендуется сменить');
$uploads = __DIR__ . '/uploads';
$add('Папка uploads доступна для записи', is_dir($uploads) && is_writable($uploads), 'нужно для загрузки логотипа');
$add('Файлы оформления на месте', is_file(__DIR__ . '/assets/app.css') && is_file(__DIR__ . '/assets/site.js'), 'assets/app.css, assets/site.js');
$add('HTTPS', is_https(), is_https() ? '' : 'включите SSL и force_https после настройки');

send_security_headers();
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
?><!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Проверка сервера</title><link rel="stylesheet" href="<?= e(asset('app.css')) ?>"></head>
<body class="min-h-dvh bg-ink-950 p-5 text-white sm:p-10">
<main class="mx-auto max-w-2xl">
<h1 class="text-2xl font-extrabold">Проверка сервера</h1>
<p class="mt-1 mb-6 text-sm text-red-300">Режим debug включён. После проверки поставьте 'debug' => false в includes/config.php.</p>
<ul class="grid gap-2">
<?php foreach ($results as [$name, $ok, $info]): ?>
  <li class="card flex items-start gap-3 p-4 text-sm">
    <span class="font-bold <?= $ok ? 'text-accent-400' : 'text-red-400' ?>"><?= $ok ? '✓' : '✗' ?></span>
    <span><span class="font-semibold"><?= e($name) ?></span><?php if ($info !== ''): ?><span class="block text-mute"><?= e($info) ?></span><?php endif; ?></span>
  </li>
<?php endforeach; ?>
</ul>
</main></body></html>
