<?php
if (!function_exists('app_url')) {
    require_once dirname(__DIR__) . '/app/url.php';
}
require_once dirname(__DIR__) . '/app/staff.php';

$staffName = htmlspecialchars((string) ($_SESSION['user']['name'] ?? 'Staff'), ENT_QUOTES, 'UTF-8');
$logoUrl = app_brand_logo_url();
$isPlatform = kk_staff_is_platform();
$shopId = kk_staff_shop_id();
$active = basename($_SERVER['SCRIPT_NAME'] ?? '');

function kk_staff_nav_active(string $file, string $active): string
{
    return $file === $active ? ' is-active' : '';
}
?>
<header class="staff-topbar">
    <div class="staff-topbar__inner">
        <a href="<?= htmlspecialchars(app_url('admin/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="staff-topbar__brand">
            <span class="staff-topbar__logo-wrap" aria-hidden="true">
                <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="32" height="32" decoding="async">
            </span>
            <span class="staff-topbar__brand-text">
                <span class="staff-topbar__brand-label">Staff portal</span>
                <span class="staff-topbar__brand-name">Crispy Crave</span>
            </span>
        </a>

        <nav class="staff-topbar__nav" aria-label="Staff">
            <a href="<?= htmlspecialchars(app_url('admin/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
               class="staff-topbar__link<?= kk_staff_nav_active('dashboard.php', $active) ?>">
                <i class="bi bi-grid" aria-hidden="true"></i><span>Dashboard</span>
            </a>
            <a href="<?= htmlspecialchars(app_url('admin/orders.php'), ENT_QUOTES, 'UTF-8') ?>"
               class="staff-topbar__link<?= kk_staff_nav_active('orders.php', $active) ?>">
                <i class="bi bi-receipt" aria-hidden="true"></i><span>Orders</span>
            </a>
            <?php if ($isPlatform): ?>
                <a href="<?= htmlspecialchars(app_url('admin/shop.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="staff-topbar__link<?= kk_staff_nav_active('shop.php', $active) ?> <?= in_array($active, ['add-shop.php', 'edit-shop.php'], true) ? ' is-active' : '' ?>">
                    <i class="bi bi-shop" aria-hidden="true"></i><span>Shops</span>
                </a>
                <a href="<?= htmlspecialchars(app_url('admin/admin_stats.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="staff-topbar__link<?= kk_staff_nav_active('admin_stats.php', $active) ?>">
                    <i class="bi bi-bar-chart" aria-hidden="true"></i><span>Stats</span>
                </a>
            <?php else: ?>
                <?php $kitchenQs = '?shop_id=' . (int) $shopId; ?>
                <a href="<?= htmlspecialchars(app_url('admin/kds.php' . $kitchenQs), ENT_QUOTES, 'UTF-8') ?>"
                   class="staff-topbar__link<?= kk_staff_nav_active('kds.php', $active) ?>">
                    <i class="bi bi-display" aria-hidden="true"></i><span>KDS</span>
                </a>
                <a href="<?= htmlspecialchars(app_url('admin/pos.php' . $kitchenQs), ENT_QUOTES, 'UTF-8') ?>"
                   class="staff-topbar__link<?= kk_staff_nav_active('pos.php', $active) ?>">
                    <i class="bi bi-cash-register" aria-hidden="true"></i><span>POS</span>
                </a>
                <a href="<?= htmlspecialchars(app_url('admin/menus.php' . $kitchenQs), ENT_QUOTES, 'UTF-8') ?>"
                   class="staff-topbar__link<?= kk_staff_nav_active('menus.php', $active) ?> <?= in_array($active, ['add-menu.php', 'edit-menu.php'], true) ? ' is-active' : '' ?>">
                    <i class="bi bi-journal-text" aria-hidden="true"></i><span>Menu</span>
                </a>
                <a href="<?= htmlspecialchars(app_url('admin/inventory.php' . $kitchenQs), ENT_QUOTES, 'UTF-8') ?>"
                   class="staff-topbar__link<?= in_array($active, ['inventory.php', 'purchase-orders.php', 'waste.php'], true) ? ' is-active' : '' ?>">
                    <i class="bi bi-boxes" aria-hidden="true"></i><span>Stock</span>
                </a>
                <a href="<?= htmlspecialchars(app_url('admin/recipes.php' . $kitchenQs), ENT_QUOTES, 'UTF-8') ?>"
                   class="staff-topbar__link<?= in_array($active, ['recipes.php', 'recipe-edit.php'], true) ? ' is-active' : '' ?>">
                    <i class="bi bi-book" aria-hidden="true"></i><span>Recipes</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="staff-topbar__actions">
            <span class="staff-topbar__role-badge"><?= $isPlatform ? 'Admin' : 'Kitchen' ?></span>
            <span class="staff-topbar__user" title="<?= $staffName ?>">
                <i class="bi bi-person-circle" aria-hidden="true"></i>
                <span class="staff-topbar__user-name"><?= $staffName ?></span>
            </span>
            <a href="<?= htmlspecialchars(app_url('admin/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="staff-topbar__logout">Log out</a>
        </div>
    </div>
</header>
