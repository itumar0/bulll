<?php
/** Rate limiting, origin checks, admin sessions and CSRF. */
defined('APP') or exit;

const ADMIN_SESSION_TTL = 8 * 3600;
const ADMIN_SESSION_NAME = 'bs_admin';

function client_ip(): string
{
    if (cfg('trust_proxy') && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Fixed-window limiter with lockout, stored in MySQL (works on shared hosting).
 * Returns [allowed, retryAfterSeconds, remaining]. Keys are hashed — no raw IPs stored.
 */
function rate_limit(string $key, int $limit, int $windowSec, int $blockSec): array
{
    $k = hash('sha256', $key);
    $now = time();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT hits, window_start, blocked_until FROM rate_limits WHERE k = ? FOR UPDATE');
        $st->execute([$k]);
        $row = $st->fetch();
        if ($row && (int) $row['blocked_until'] > $now) {
            $pdo->commit();
            return [false, (int) $row['blocked_until'] - $now, 0];
        }
        if (!$row || $now - (int) $row['window_start'] >= $windowSec) {
            $hits = 1;
            $windowStart = $now;
        } else {
            $hits = (int) $row['hits'] + 1;
            $windowStart = (int) $row['window_start'];
        }
        $blocked = $hits > $limit ? $now + $blockSec : 0;
        $pdo->prepare('INSERT INTO rate_limits (k, hits, window_start, blocked_until) VALUES (?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE hits = VALUES(hits), window_start = VALUES(window_start), blocked_until = VALUES(blocked_until)')
            ->execute([$k, $hits, $windowStart, $blocked]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    // Occasional cleanup of stale rows.
    if (random_int(1, 50) === 1) {
        db()->prepare('DELETE FROM rate_limits WHERE blocked_until < ? AND window_start < ?')->execute([$now, $now - 86400]);
    }
    return $blocked ? [false, $blockSec, 0] : [true, 0, $limit - $hits];
}

function reset_rate_limit(string $key): void
{
    db()->prepare('DELETE FROM rate_limits WHERE k = ?')->execute([hash('sha256', $key)]);
}

/** CSRF defense in depth: state-changing requests must come from this site. */
function require_same_origin(): void
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $source = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    $srcHost = strtolower((string) parse_url($source, PHP_URL_HOST));
    $srcPort = parse_url($source, PHP_URL_PORT);
    if ($srcPort) $srcHost .= ':' . $srcPort;
    if ($source === '' || $srcHost !== $host) json_error('Forbidden', 403);
}

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) ADMIN_SESSION_TTL);
    session_name(ADMIN_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => ADMIN_SESSION_TTL,
        'path' => base_path(),
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function is_admin(): bool
{
    start_admin_session();
    $ok = !empty($_SESSION['admin']) && (time() - (int) ($_SESSION['login_at'] ?? 0)) < ADMIN_SESSION_TTL;
    if (!$ok && !empty($_SESSION['admin'])) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    return $ok;
}

function admin_login(): void
{
    start_admin_session();
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    $_SESSION['login_at'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function admin_logout(): void
{
    start_admin_session();
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict',
    ]);
    session_destroy();
}

function csrf_token(): string
{
    start_admin_session();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

/**
 * For classic HTML form posts in /admin: same origin + CSRF token from a hidden field.
 * Returns false instead of exiting, so the page can show an error.
 */
function form_post_is_valid(): bool
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $source = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    $srcHost = strtolower((string) parse_url($source, PHP_URL_HOST));
    $srcPort = parse_url($source, PHP_URL_PORT);
    if ($srcPort) $srcHost .= ':' . $srcPort;
    if ($source === '' || $srcHost !== $host) return false;
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && hash_equals(csrf_token(), $sent);
}

/** For admin API endpoints: valid session + (for POST) same origin and CSRF header. */
function require_admin_api(): void
{
    if (!is_admin()) json_error('Unauthorized', 401);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        require_same_origin();
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) json_error('Forbidden', 403);
    }
    session_write_close(); // release the session lock for parallel polling
}
