<?php
/**
 * Club logo: an uploaded logo (admin → /admin/logo.php) replaces the default one in assets/.
 * Uploads are re-encoded with GD (strips metadata and anything hidden in the file) into fixed
 * file names — the client's file name is never used.
 */
defined('APP') or exit;

const LOGO_MAX_BYTES = 2 * 1024 * 1024;   // 2 MB
const LOGO_MIN_SIDE = 64;
const LOGO_MAX_SIDE = 4000;                // guards against decompression bombs
const LOGO_VARIANTS = [                    // name => [size, format, background]
    'logo-512'   => [512, 'auto', null],   // hero
    'logo-96'    => [96, 'auto', null],    // header
    'favicon-32' => [32, 'png', null],     // browser tab
    'apple-icon' => [180, 'png', [10, 10, 10]], // iOS home screen (no transparency)
    'icon-192'   => [192, 'png', null],    // Android home screen
];

function logo_dir(): string
{
    return __DIR__ . '/../uploads';
}

/** URL of a logo variant: the uploaded one if present, otherwise the default from assets/. */
function logo_url(string $variant = 'logo-96'): string
{
    foreach (['webp', 'png'] as $ext) {
        $file = logo_dir() . "/{$variant}.{$ext}";
        if (is_file($file)) return base_path() . "uploads/{$variant}.{$ext}?v=" . filemtime($file);
    }
    $defaults = ['logo-512' => 'logo-512.webp', 'logo-96' => 'logo-96.webp', 'favicon-32' => 'favicon-32.png', 'apple-icon' => 'apple-icon.png', 'icon-192' => 'icon-192.png'];
    return asset($defaults[$variant] ?? 'logo-96.webp');
}

function has_custom_logo(): bool
{
    return is_file(logo_dir() . '/logo-96.webp') || is_file(logo_dir() . '/logo-96.png');
}

/**
 * Validate an uploaded file from $_FILES and generate all logo variants.
 * @throws BookingError with a user-friendly message on any problem
 */
function save_logo(array $file): void
{
    if (!extension_loaded('gd')) throw new BookingError('На сервере не включено расширение GD — загрузка картинок недоступна', 500);

    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) throw new BookingError('Файл слишком большой (максимум 2 МБ)', 413);
    if ($err === UPLOAD_ERR_NO_FILE) throw new BookingError('Выберите файл с логотипом', 422);
    if ($err !== UPLOAD_ERR_OK) throw new BookingError('Не удалось загрузить файл. Попробуйте ещё раз.', 400);

    $tmp = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) throw new BookingError('Некорректная загрузка', 400);
    if (filesize($tmp) > LOGO_MAX_BYTES) throw new BookingError('Файл слишком большой (максимум 2 МБ)', 413);

    // Check the real content type (not the extension or the browser's claim).
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $allowed = ['image/png', 'image/jpeg', 'image/webp'];
    if (!in_array($mime, $allowed, true)) throw new BookingError('Поддерживаются только PNG, JPG и WebP', 415);

    $info = @getimagesize($tmp);
    if (!$info) throw new BookingError('Файл не похож на изображение', 415);
    [$w, $h] = $info;
    if ($w < LOGO_MIN_SIDE || $h < LOGO_MIN_SIDE) throw new BookingError('Картинка слишком маленькая (минимум 64×64)', 422);
    if ($w > LOGO_MAX_SIDE || $h > LOGO_MAX_SIDE) throw new BookingError('Картинка слишком большая (максимум 4000×4000)', 422);

    $src = match ($mime) {
        'image/png' => @imagecreatefrompng($tmp),
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
        default => false,
    };
    if (!$src) throw new BookingError('Не удалось прочитать картинку', 415);

    // Centered square crop — the site shows the logo in a circle.
    $side = min($w, $h);
    $sx = intdiv($w - $side, 2);
    $sy = intdiv($h - $side, 2);

    $dir = logo_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new BookingError('Нет доступа к папке uploads', 500);
    if (!is_writable($dir)) throw new BookingError('Папка uploads недоступна для записи', 500);

    $webp = function_exists('imagewebp');
    $written = [];
    try {
        foreach (LOGO_VARIANTS as $name => [$size, $format, $bg]) {
            $dst = imagecreatetruecolor($size, $size);
            if ($bg) {
                imagefill($dst, 0, 0, imagecolorallocate($dst, ...$bg));
            } else {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
                imagealphablending($dst, true);
            }
            imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $size, $size, $side, $side);
            imagesavealpha($dst, true);

            $ext = ($format === 'auto' && $webp) ? 'webp' : 'png';
            $tmpOut = "{$dir}/.{$name}." . bin2hex(random_bytes(4)) . ".{$ext}";
            $ok = $ext === 'webp' ? imagewebp($dst, $tmpOut, 88) : imagepng($dst, $tmpOut, 9);
            imagedestroy($dst);
            if (!$ok) throw new BookingError('Не удалось сохранить картинку', 500);
            $written[$name] = [$tmpOut, "{$dir}/{$name}.{$ext}"];
        }
        // All variants generated → swap them in (removing the other format, if any).
        foreach ($written as $name => [$tmpOut, $final]) {
            foreach (['webp', 'png'] as $ext) {
                $old = "{$dir}/{$name}.{$ext}";
                if ($old !== $final && is_file($old)) unlink($old);
            }
            rename($tmpOut, $final);
            chmod($final, 0644);
        }
    } finally {
        imagedestroy($src);
        foreach ($written as [$tmpOut]) if (is_file($tmpOut)) unlink($tmpOut);
    }
}

/** Remove the uploaded logo → the default one is used again. */
function reset_logo(): void
{
    foreach (array_keys(LOGO_VARIANTS) as $name) {
        foreach (['webp', 'png'] as $ext) {
            $f = logo_dir() . "/{$name}.{$ext}";
            if (is_file($f)) unlink($f);
        }
    }
}
