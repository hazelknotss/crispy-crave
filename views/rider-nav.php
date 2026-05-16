<?php
if (!function_exists('app_url')) {
    require_once dirname(__DIR__) . '/app/url.php';
}
$kkRiderNavActive = $kkRiderNavActive ?? 'deliveries';
$kkRiderUnread = isset($kkRiderUnread) ? (int) $kkRiderUnread : 0;

$kkNavItems = [
    'deliveries'    => ['label' => 'Deliveries', 'href' => app_url('rider/dashboard.php'), 'icon' => 'bi-truck'],
    'earnings'      => ['label' => 'Earnings', 'href' => app_url('rider/earnings.php'), 'icon' => 'bi-wallet2'],
    'performance'   => ['label' => 'Stats', 'href' => app_url('rider/performance.php'), 'icon' => 'bi-bar-chart'],
    'tracking'      => ['label' => 'GPS', 'href' => app_url('rider/tracking.php'), 'icon' => 'bi-geo-alt'],
    'notifications' => ['label' => 'Alerts', 'href' => app_url('rider/notifications.php'), 'icon' => 'bi-bell'],
    'profile'       => ['label' => 'Profile', 'href' => app_url('rider/profile.php'), 'icon' => 'bi-person'],
];
?>
<nav class="rider-portal-nav" aria-label="Rider portal">
    <div class="rider-portal-nav__dock">
        <?php foreach ($kkNavItems as $key => $item): ?>
            <?php $isActive = $kkRiderNavActive === $key; ?>
            <a
                href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                class="rider-portal-nav__link<?= $isActive ? ' rider-portal-nav__link--active' : '' ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>
                title="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="rider-portal-nav__icon-wrap">
                    <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                    <?php if ($key === 'notifications' && $kkRiderUnread > 0): ?>
                        <span class="rider-portal-nav__badge"><?= $kkRiderUnread > 9 ? '9+' : (int) $kkRiderUnread ?></span>
                    <?php endif; ?>
                </span>
                <span class="rider-portal-nav__label"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
