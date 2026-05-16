<?php
if (!function_exists('app_url')) {
    require_once dirname(__DIR__) . '/app/url.php';
}
$riderName = htmlspecialchars((string) ($_SESSION['user']['name'] ?? 'Rider'), ENT_QUOTES, 'UTF-8');
$logoUrl = app_brand_logo_url();
?>
<header class="rider-topbar">
    <div class="container-fluid rider-topbar__inner">
        <a href="<?= htmlspecialchars(app_url('rider/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="rider-topbar__brand">
            <span class="rider-topbar__logo-wrap" aria-hidden="true">
                <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="28" height="28" decoding="async">
            </span>
            <span class="rider-topbar__brand-text">
                <span class="rider-topbar__brand-label">Rider portal</span>
                <span class="rider-topbar__brand-name">Crispy Crave</span>
            </span>
        </a>
        <div class="rider-topbar__actions">
            <?php if (!empty($kkRiderUnread)): ?>
                <a href="<?= htmlspecialchars(app_url('rider/notifications.php'), ENT_QUOTES, 'UTF-8') ?>" class="rider-topbar__bell" aria-label="<?= (int) $kkRiderUnread ?> unread notifications">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <span class="rider-topbar__bell-count"><?= (int) $kkRiderUnread > 9 ? '9+' : (int) $kkRiderUnread ?></span>
                </a>
            <?php endif; ?>
            <span class="rider-topbar__user">
                <i class="bi bi-person-circle" aria-hidden="true"></i>
                <span><?= $riderName ?></span>
            </span>
            <a href="<?= htmlspecialchars(app_url('rider/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="rider-topbar__logout">Log out</a>
        </div>
    </div>
</header>
