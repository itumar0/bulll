<?php
/** Common bootstrap: errors, timezone, security headers, helpers. */
declare(strict_types=1);
define('APP', true);

ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('UTC'); // all storage in UTC; local time = Asia/Bishkek (see time.php)
mb_internal_encoding('UTF-8');

// config.local.php (if present, not committed) overrides config.php — handy for local testing.
$CONFIG = require (is_file(__DIR__ . '/config.local.php') ? __DIR__ . '/config.local.php' : __DIR__ . '/config.php');

if (!empty($CONFIG['debug'])) {
    ini_set('display_errors', '1');
    // Show uncaught errors (incl. fatal ones) with file and line instead of a blank "500".
    set_exception_handler(function (Throwable $e) {
        if (!headers_sent()) { http_response_code(500); header('Content-Type: text/plain; charset=utf-8'); }
        echo 'DEBUG: ' . get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();
    });
}

require __DIR__ . '/club.php';
require __DIR__ . '/time.php';
require __DIR__ . '/phone.php';
require __DIR__ . '/db.php';
require __DIR__ . '/security.php';
require __DIR__ . '/validation.php';
require __DIR__ . '/booking.php';
require __DIR__ . '/whatsapp.php';
require __DIR__ . '/logo.php';

function cfg(string $key)
{
    global $CONFIG;
    return $CONFIG[$key] ?? null;
}

function is_https(): bool
{
    if (cfg('force_https')) return true;
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/** Absolute site URL (scheme + host + base path), e.g. https://start.example.com */
function site_url(string $path = ''): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/[^A-Za-z0-9.:\-]/', '', $host);
    return (is_https() ? 'https://' : 'http://') . $host . base_path() . ltrim($path, '/');
}

/** Base path of the site ("/" when installed in htdocs root). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
        $appRoot = realpath(__DIR__ . '/..') ?: '';
        $rel = ($docRoot && str_starts_with($appRoot, $docRoot)) ? substr($appRoot, strlen($docRoot)) : '';
        $base = rtrim(str_replace('\\', '/', $rel), '/') . '/';
    }
    return $base;
}

function asset(string $file): string
{
    $path = __DIR__ . '/../assets/' . $file;
    $v = is_file($path) ? substr(md5((string) filemtime($path)), 0, 8) : '1';
    return base_path() . 'assets/' . $file . '?v=' . $v;
}

/** HTML escape. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function send_security_headers(): void
{
    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self'",
        "style-src 'self'",
        "img-src 'self' data:",
        "font-src 'self'",
        "connect-src 'self'",
        "frame-ancestors 'none'",
        "frame-src 'none'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
    ]) . (is_https() ? '; upgrade-insecure-requests' : '');
    header('Content-Security-Policy: ' . $csp);
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    if (is_https()) header('Strict-Transport-Security: max-age=63072000; includeSubDomains');
    header_remove('X-Powered-By');
}

/* ------------------------------- JSON API ------------------------------- */

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $status = 400, array $extra = []): void
{
    json_response(['ok' => false, 'error' => $message] + $extra, $status);
}

/** Parse a JSON request body (max 8 KB). */
function read_json(int $maxBytes = 8192): array
{
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($raw === false || strlen($raw) > $maxBytes) json_error('Некорректный запрос', 400);
    $data = json_decode($raw, true);
    if (!is_array($data)) json_error('Некорректный запрос', 400);
    return $data;
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        header('Allow: ' . $method);
        json_error('Method not allowed', 405);
    }
}

/** Log without personal data. */
function log_error(string $where, Throwable $e): void
{
    error_log(sprintf('[%s] %s: %s', $where, get_class($e), $e->getMessage()));
}

/** Run an API handler with uniform error handling. */
function api(callable $handler): void
{
    send_security_headers();
    try {
        $handler();
    } catch (BookingError $e) {
        json_error($e->getMessage(), $e->status, $e->field ? ['fields' => [$e->field => $e->getMessage()]] : []);
    } catch (ValidationError $e) {
        json_error('Проверьте правильность заполнения формы', 422, ['fields' => $e->fields]);
    } catch (Throwable $e) {
        log_error('api', $e);
        json_error('Внутренняя ошибка сервера. Попробуйте позже.', 500, cfg('debug') ? ['debug' => get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine()] : []);
    }
}
