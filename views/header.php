<?php
require_once dirname(__DIR__) . '/app/url.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------------- CART COUNT ---------------- */
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}

/* ---------------- USER ROLE ---------------- */
$role = $_SESSION['user']['role'] ?? null;
$kkIsStaffPortal = ($role === 'admin' || $role === 'restaurant');
if ($kkIsStaffPortal) {
    if (empty($kkExtraCss) || !is_array($kkExtraCss)) {
        $kkExtraCss = [];
    }
    $staffCss = app_url('css/admin-portal.css');
    if (!in_array($staffCss, $kkExtraCss, true)) {
        $kkExtraCss[] = $staffCss;
    }
    $kkBodyClass = trim(((string) ($kkBodyClass ?? '')) . ' staff-portal');
}

/* ---------------- LOGO LINK (ROLE-BASED) ---------------- */
if ($role === 'admin' || $role === 'restaurant') {
    $logoLink = app_url('admin/dashboard.php');
} else {
    $logoLink = app_url('index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Crispy Crave</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php require __DIR__ . '/pwa-head.php'; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('css/style.css')) ?>">
    <?php if (!empty($kkExtraCss) && is_array($kkExtraCss)): ?>
        <?php foreach ($kkExtraCss as $kkCssHref): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars((string) $kkCssHref, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<?php
$kkBodyClasses = trim(
    (isset($_SESSION['user']) ? 'has-session' : '')
    . (!empty($kkBodyClass) ? ' ' . (string) $kkBodyClass : '')
);
?>
<body<?= $kkBodyClasses !== '' ? ' class="' . htmlspecialchars($kkBodyClasses, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>

<?php if (!empty($kkIsStaffPortal)): ?>
    <?php require __DIR__ . '/staff-topbar.php'; ?>
<?php else: ?>
<header class="main-header">
    <div class="header-inner<?= isset($_SESSION['user']) ? ' header-inner--session' : '' ?>">
        <div class="header-left">
            <a href="<?= htmlspecialchars($logoLink) ?>" class="brand-link">
                <img src="<?= htmlspecialchars(app_brand_logo_url()) ?>" class="logo" alt="Crispy Crave">
                <span class="brand-name">Crispy Crave</span>
            </a>
        </div>

        <div class="header-cluster">
            <?php if (!$role || $role === 'user' || $role === 'rider'): ?>
            <nav class="header-nav" aria-label="Main">
                <a href="<?= htmlspecialchars(app_url('index.php')) ?>">Home</a>
                <a href="<?= htmlspecialchars(app_url('index.php')) ?>#shops">Order now</a>
            </nav>
            <?php endif; ?>

            <div class="header-meta">
                <span class="info d-inline-flex align-items-center gap-1"><i class="bi bi-clock" aria-hidden="true"></i><span>10AM – 10PM</span></span>
                <span class="info d-inline-flex align-items-center gap-1"><i class="bi bi-telephone" aria-hidden="true"></i><a href="tel:+639389762763" class="header-meta__tel">09389762763</a></span>
            </div>
        </div>

        <div class="header-actions">
            <?php if ($role === 'user'): ?>
                <a href="<?= htmlspecialchars(app_url('cart.php')) ?>" class="cart-btn position-relative" aria-label="Cart">
                    <span class="d-none d-sm-inline">Cart</span>
                    <i class="bi bi-cart3 d-sm-none fs-5" aria-hidden="true"></i>
                    <span class="visually-hidden d-sm-none">Cart</span>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= htmlspecialchars(app_url('my-orders.php')) ?>" class="user-btn">
                    <span class="d-none d-sm-inline">My orders</span>
                    <span class="d-sm-none">Orders</span>
                </a>
                <a href="<?= htmlspecialchars(app_url('profile.php')) ?>" class="user-btn d-none d-md-inline-flex">
                    Profile
                </a>
            <?php endif; ?>

            <?php if ($role === 'admin' || $role === 'restaurant'): ?>
                <a href="<?= htmlspecialchars(app_url('admin/dashboard.php')) ?>" class="admin-btn">Dashboard</a>
                <a href="<?= htmlspecialchars(app_url('admin/orders.php')) ?>" class="admin-btn">Orders</a>
                <?php if ($role === 'restaurant'): ?>
                    <a href="<?= htmlspecialchars(app_url('admin/menus.php')) ?>" class="admin-btn d-none d-md-inline">Menu</a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['user'])): ?>
                <?php if ($role === 'user'): ?>
                    <a href="<?= htmlspecialchars(app_url('profile.php')) ?>" class="user-name user-name--link" title="Your profile">
                        <?= htmlspecialchars($_SESSION['user']['name']) ?>
                    </a>
                <?php else: ?>
                    <span class="user-name" title="<?= htmlspecialchars($_SESSION['user']['name']) ?>">
                        <?= htmlspecialchars($_SESSION['user']['name']) ?>
                    </span>
                <?php endif; ?>
                <?php
                $logoutUrl = ($role === 'admin' || $role === 'restaurant')
                    ? app_url('admin/logout.php')
                    : app_url('logout.php');
                ?>
                <a href="<?= htmlspecialchars($logoutUrl) ?>" class="logout-btn">Log out</a>
            <?php else: ?>
                <button type="button" class="register-btn" data-bs-toggle="modal" data-bs-target="#kkAuthModal" data-auth-tab="register" aria-haspopup="dialog">Get started</button>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php endif; ?>
