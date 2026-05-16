<?php
if (!function_exists('app_url')) {
    require_once dirname(__DIR__) . '/app/url.php';
}
$riderPageTitle = $riderPageTitle ?? 'Rider portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($riderPageTitle, ENT_QUOTES, 'UTF-8') ?> — Crispy Crave</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?php require __DIR__ . '/pwa-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('css/rider-portal.css')) ?>">
    <?php if (!empty($riderExtraCss) && is_array($riderExtraCss)): ?>
        <?php foreach ($riderExtraCss as $riderCssHref): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars((string) $riderCssHref, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="rider-dash-body<?= !empty($riderBodyClass) ? ' ' . htmlspecialchars((string) $riderBodyClass, ENT_QUOTES, 'UTF-8') : '' ?>">
<?php
require_once dirname(__DIR__) . '/app/rider_portal.php';
if (isset($pdo, $_SESSION['user']['id']) && ($_SESSION['user']['role'] ?? '') === 'rider') {
    kk_rider_ensure_schema($pdo);
    $kkRiderUnread = kk_rider_unread_count($pdo, (int) $_SESSION['user']['id']);
} else {
    $kkRiderUnread = 0;
}
require __DIR__ . '/rider-topbar.php';
?>
