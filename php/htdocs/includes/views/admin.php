<?php
/** Server-rendered admin layout and widgets (work without JavaScript; admin.js only adds live refresh). */
defined('APP') or exit;

const ADMIN_PAGES = [
    'index'    => ['admin/', 'Столы'],
    'bookings' => ['admin/bookings.php', 'Брони'],
    'timeline' => ['admin/timeline.php', 'Таймлайн'],
    'new'      => ['admin/new.php', '+ Бронь'],
    'logo'     => ['admin/logo.php', 'Логотип'],
];

const STATUS_LABEL = ['PENDING' => 'Ожидает', 'CONFIRMED' => 'Подтверждена', 'COMPLETED' => 'Завершена', 'CANCELLED' => 'Отменена'];
const STATUS_PILL = [
    'PENDING' => 'bg-amber-400/15 text-amber-200',
    'CONFIRMED' => 'bg-accent-500/15 text-accent-300',
    'COMPLETED' => 'bg-white/10 text-mute',
    'CANCELLED' => 'bg-red-500/10 text-red-300 line-through',
];

function admin_page_start(string $title, string $active = '', bool $wide = false): void
{
    send_security_headers();
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    $pageTitle = $title . ' | ' . CLUB['name'];
    $noindex = true;
    require __DIR__ . '/head.php';
    $max = $wide ? 'max-w-7xl' : 'max-w-xl';
    echo '<body class="min-h-dvh bg-ink-950 text-white" data-base="' . e(base_path()) . '">';
    if (is_admin()) {
        echo '<header class="sticky top-0 z-40 border-b border-white/[0.06] bg-ink-950/85 backdrop-blur-xl">'
            . '<div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">' . logo_html()
            . '<div class="flex items-center gap-3 text-sm"><span class="hidden text-mute tabular-nums sm:inline">' . e(fmt_date(now_utc()) . ' · ' . fmt_time(now_utc())) . '</span>'
            . '<form method="post" action="' . e(base_path()) . 'admin/logout.php">' . csrf_field()
            . '<button class="rounded-lg px-3 py-2 text-mute hover:bg-white/5 hover:text-white" type="submit">Выйти</button></form></div></div>'
            . '<nav aria-label="Разделы" class="mx-auto max-w-7xl overflow-x-auto px-4 pb-3 sm:px-6"><ul class="flex gap-1">';
        foreach (ADMIN_PAGES as $key => [$href, $label]) {
            $on = $key === $active;
            echo '<li><a href="' . e(base_path() . $href) . '"' . ($on ? ' aria-current="page"' : '')
                . ' class="block rounded-lg px-4 py-2 text-sm font-semibold whitespace-nowrap transition '
                . ($on ? 'bg-accent-600 text-white shadow-glow-sm' : 'text-mute hover:bg-white/5 hover:text-white') . '">' . e($label) . '</a></li>';
        }
        echo '</ul></nav></header>';
    }
    echo '<main class="mx-auto w-full ' . $max . ' px-4 py-6 sm:px-6 sm:py-8">';
    if (!is_admin()) echo '<div class="mb-8">' . logo_html() . '</div>';
}

function admin_page_end(): void
{
    echo '</main><script src="' . e(asset('admin.js')) . '" defer></script></body></html>';
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
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

/** Only allow redirects back to our own admin pages (no open redirect). */
function safe_return(string $r): string
{
    $path = (string) parse_url($r, PHP_URL_PATH);
    $query = (string) parse_url($r, PHP_URL_QUERY);
    $allowed = ['admin/', 'admin/index.php', 'admin/bookings.php', 'admin/timeline.php'];
    if (!in_array($path, $allowed, true)) return 'admin/';
    return $path . ($query !== '' ? '?' . preg_replace('/[^A-Za-z0-9_=&%.\-+]/', '', $query) : '');
}

function current_return(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $base = base_path();
    $rel = str_starts_with($uri, $base) ? substr($uri, strlen($base)) : ltrim($uri, '/');
    return safe_return($rel);
}

function fmt_remaining(int $seconds): string
{
    $m = max(0, (int) round($seconds / 60));
    $h = intdiv($m, 60);
    $m %= 60;
    if ($h && $m) return "{$h} ч {$m} мин";
    return $h ? "{$h} ч" : "{$m} мин";
}

/** Action buttons for a booking row (each is a tiny POST form → admin/action.php). */
function booking_actions(array $b, bool $compact = false): string
{
    $id = (int) $b['id'];
    $active = in_array($b['status'], ACTIVE_STATUSES, true);
    $now = now_utc();
    $live = $active && from_db($b['start_at']) <= $now && from_db($b['end_at']) > $now;
    $return = e(current_return());
    $btn = 'inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition';
    $form = function (string $action, string $label, string $cls, string $confirm = '', int $minutes = 0) use ($id, $return, $btn) {
        return '<form method="post" action="' . e(base_path()) . 'admin/action.php"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
            . csrf_field() . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="action" value="' . $action . '">'
            . ($minutes ? '<input type="hidden" name="minutes" value="' . $minutes . '">' : '')
            . '<input type="hidden" name="return" value="' . $return . '">'
            . '<button type="submit" class="' . $btn . ' ' . $cls . '">' . e($label) . '</button></form>';
    };
    $out = '<div class="flex flex-wrap gap-1.5">';
    if ($b['status'] === 'PENDING') $out .= $form('confirm', 'Подтвердить', 'border-accent-500/40 text-accent-300 hover:bg-accent-500/10');
    if ($active) {
        $out .= $form('extend', '+30 мин', 'border-white/15 text-soft hover:bg-white/5', '', 30);
        if (!$compact) $out .= $form('extend', '+1 ч', 'border-white/15 text-soft hover:bg-white/5', '', 60);
        if ($live) $out .= $form('complete', 'Завершить', 'border-amber-400/40 text-amber-200 hover:bg-amber-400/10', "Завершить бронь №{$id} досрочно? Стол освободится сейчас.");
        $out .= $form('cancel', 'Отменить', 'border-red-500/40 text-red-300 hover:bg-red-500/10', "Отменить бронь №{$id} ({$b['name']})?");
    }
    $out .= '<a href="' . e(wa_link($b['phone'], client_message($b))) . '" target="_blank" rel="noopener noreferrer" class="' . $btn
        . ' border-[#25D366]/40 text-[#25D366] hover:bg-[#25D366]/10" aria-label="Написать клиенту ' . e($b['name']) . ' в WhatsApp">'
        . icon('whatsapp', 'h-3.5 w-3.5') . ' WhatsApp</a>';
    return $out . '</div>';
}

/** Fetch a booking DB row by id (for widgets that get API-shaped arrays). */
function booking_row(int $id): ?array
{
    return get_booking($id);
}
