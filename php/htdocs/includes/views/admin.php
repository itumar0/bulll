<?php
/** Minimal server-rendered admin layout (works without JavaScript). */
defined('APP') or exit;

function admin_page_start(string $title): void
{
    send_security_headers();
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    $pageTitle = $title . ' | ' . CLUB['name'];
    $noindex = true;
    require __DIR__ . '/head.php';
    echo '<body class="min-h-dvh bg-ink-950 text-white"><main class="mx-auto w-full max-w-xl px-5 py-10 sm:py-16">';
    echo '<div class="mb-8 flex items-center justify-between gap-4">' . logo_html();
    if (is_admin()) {
        echo '<nav class="flex items-center gap-2 text-sm"><a class="rounded-lg px-3 py-2 text-mute hover:text-white" href="' . e(base_path()) . 'admin/">Меню</a>'
            . '<form method="post" action="' . e(base_path()) . 'admin/logout.php"><input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
            . '<button class="rounded-lg px-3 py-2 text-mute hover:text-white" type="submit">Выйти</button></form></nav>';
    }
    echo '</div>';
}

function admin_page_end(): void
{
    echo '</main></body></html>';
}

/** One-time message shown after a redirect (Post/Redirect/Get). */
function flash(?string $message = null, bool $error = false): ?array
{
    start_admin_session();
    if ($message !== null) {
        $_SESSION['flash'] = ['text' => $message, 'error' => $error];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function flash_html(): string
{
    $f = flash();
    if (!$f) return '';
    $cls = $f['error'] ? 'border-red-500/30 bg-red-500/10 text-red-200' : 'border-accent-500/30 bg-accent-500/10 text-accent-200';
    return '<p role="' . ($f['error'] ? 'alert' : 'status') . '" class="mb-6 rounded-xl border px-4 py-3 text-sm ' . $cls . '">' . e($f['text']) . '</p>';
}

function redirect(string $path): void
{
    header('Location: ' . base_path() . ltrim($path, '/'), true, 303);
    exit;
}

function require_admin_page(): void
{
    if (!is_admin()) redirect('admin/login.php');
}
