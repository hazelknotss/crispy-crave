<?php

declare(strict_types=1);

if (!function_exists('app_url')) {
    require_once dirname(__DIR__) . '/app/url.php';
}

$manifestHref = app_url('manifest.php');
$touchIcon = app_url('images/official_logo.png');
$root = app_project_root();
if (!is_file($root . '/images/official_logo.png')) {
    $touchIcon = app_url('images/logo.png');
}

?>
<link rel="manifest" href="<?= htmlspecialchars($manifestHref, ENT_QUOTES, 'UTF-8') ?>">
<meta name="theme-color" content="#111111">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Crispy Crave">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="<?= htmlspecialchars($touchIcon, ENT_QUOTES, 'UTF-8') ?>">
