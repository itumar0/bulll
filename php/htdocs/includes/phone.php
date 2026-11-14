<?php
/** Kyrgyzstan phones: +996 followed by 9 digits. */
defined('APP') or exit;

function phone_digits(string $input): string
{
    $s = $input;
    $idx = strpos($s, '+996');
    if ($idx !== false) $s = substr($s, 0, $idx) . substr($s, $idx + 4);
    $digits = preg_replace('/\D/', '', $s) ?? '';
    if ($idx === false && strlen($digits) > 9) {
        if (str_starts_with($digits, '996')) $digits = substr($digits, 3);
        elseif (str_starts_with($digits, '0')) $digits = substr($digits, 1);
    }
    return substr($digits, 0, 9);
}

function normalize_phone(string $input): ?string
{
    $d = phone_digits($input);
    return strlen($d) === 9 ? '+996' . $d : null;
}

function pretty_phone(string $normalized): string
{
    $d = phone_digits($normalized);
    return sprintf('+996 (%s) %s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3));
}
