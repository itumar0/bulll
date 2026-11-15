<?php
/** PDO connection. Only prepared statements are used — no SQL is built from user input. */
defined('APP') or exit;

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', cfg('db_host'), (int) cfg('db_port'), cfg('db_name'));
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];
    // Free hosting MySQL sometimes refuses a connection for a moment
    // ("Too many connections", "Connection refused") — retry a few times before failing.
    for ($attempt = 1; ; $attempt++) {
        try {
            $pdo = new PDO($dsn, (string) cfg('db_user'), (string) cfg('db_pass'), $opts);
            break;
        } catch (PDOException $e) {
            if ($attempt >= 3) throw $e;
            usleep(400000 * $attempt);
        }
    }
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}

/** Build "?, ?, ?" for IN (...) clauses. */
function placeholders(array $items): string
{
    return implode(', ', array_fill(0, count($items), '?'));
}
