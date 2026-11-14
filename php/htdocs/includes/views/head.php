<?php
/** <head> shared by all pages. Expects $pageTitle, $pageDescription, $noindex. */
defined('APP') or exit;
$pageTitle ??= CLUB['name'] . ' — бильярдный клуб в Караколе | Онлайн-бронирование';
$pageDescription ??= CLUB['description'];
$noindex ??= false;
?><!doctype html>
<html lang="ru" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<meta name="theme-color" content="#0A0A0A">
<meta name="color-scheme" content="dark">
<meta name="format-detection" content="telephone=no">
<?php if ($noindex): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<link rel="canonical" href="<?= e(site_url()) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="<?= e(CLUB['name']) ?>">
<meta property="og:title" content="<?= e(CLUB['name']) ?> — бильярдный клуб в Караколе">
<meta property="og:description" content="<?= e(CLUB['description']) ?>">
<meta property="og:url" content="<?= e(site_url()) ?>">
<meta property="og:image" content="<?= e(site_url('assets/og.png')) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<link rel="icon" href="<?= e(base_path()) ?>assets/favicon.ico" sizes="any">
<link rel="icon" href="<?= e(base_path()) ?>assets/favicon-32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="<?= e(base_path()) ?>assets/apple-icon.png">
<link rel="preload" href="<?= e(base_path()) ?>assets/fonts/manrope-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<script src="<?= e(asset('boot.js')) ?>"></script>
</head>
