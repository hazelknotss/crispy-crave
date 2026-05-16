<?php
require '../auth/auth.php';
require '../db/database.php';
require_once __DIR__ . '/../app/rider_assign.php';
require_once __DIR__ . '/../app/order_messages.php';
require __DIR__ . '/_status.php';

requireRider();

$rider_id = (int) $_SESSION['user']['id'];
$riderName = (string) ($_SESSION['user']['name'] ?? 'Rider');
$restaurantId = isset($_SESSION['user']['restaurant_id']) ? (int) $_SESSION['user']['restaurant_id'] : null;
if ($restaurantId !== null && $restaurantId < 1) {
    $restaurantId = null;
}

/* Assign older delivery orders that were never linked to a rider */
kk_backfill_unassigned_delivery_orders($pdo);

$orders = kk_fetch_rider_orders($pdo, $rider_id, $restaurantId);

$totalCount = count($orders);
$activeCount = 0;
$doneCount = 0;
foreach ($orders as $o) {
    if (($o['delivery_status'] ?? '') === 'delivered') {
        $doneCount++;
    } else {
        $activeCount++;
    }
}

$kkRiderNavActive = 'deliveries';
$riderPageTitle = 'My deliveries';
require '../views/rider-layout-head.php';
?>

<main class="rider-dash-page">
    <div class="container-fluid rider-dash-page__inner">
        <header class="rider-dash-hero">
            <div class="rider-dash-hero__copy">
                <p class="rider-dash-header__kicker">Delivery partner</p>
                <h1 class="rider-dash-header__title">Hi, <?= htmlspecialchars($riderName, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="rider-dash-header__lede">Orders assigned to you by the kitchen. Update status as you go.</p>
            </div>
            <div class="rider-dash-stats" aria-label="Delivery summary">
                <div class="rider-dash-stat">
                    <span class="rider-dash-stat__value tabular-nums"><?= $totalCount ?></span>
                    <span class="rider-dash-stat__label">Total</span>
                </div>
                <div class="rider-dash-stat rider-dash-stat--active">
                    <span class="rider-dash-stat__value tabular-nums"><?= $activeCount ?></span>
                    <span class="rider-dash-stat__label">Active</span>
                </div>
                <div class="rider-dash-stat rider-dash-stat--done">
                    <span class="rider-dash-stat__value tabular-nums"><?= $doneCount ?></span>
                    <span class="rider-dash-stat__label">Delivered</span>
                </div>
            </div>
        </header>

        <section class="rider-dash-section" aria-labelledby="rider-deliveries-heading">
            <h2 id="rider-deliveries-heading" class="rider-dash-section__title">
                <i class="bi bi-truck" aria-hidden="true"></i>
                <span>My deliveries</span>
            </h2>

            <?php if ($orders === []): ?>
                <div class="rider-dash-empty-card" role="status">
                    <div class="rider-dash-empty-card__icon" aria-hidden="true">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h3 class="rider-dash-empty-card__title">No deliveries yet</h3>
                    <p class="rider-dash-empty-card__text">The restaurant will assign orders to you when they are ready for pickup.</p>
                </div>
            <?php else: ?>
                <ul class="rider-delivery-list">
                    <?php foreach ($orders as $o): ?>
                        <?php
                            $deliveryStatus = (string) ($o['delivery_status'] ?? 'assigned');
                            $delMeta = rider_delivery_status_meta($deliveryStatus);
                            $ordMeta = rider_order_status_meta((string) ($o['order_status'] ?? 'pending'));
                            $addr = (string) $o['delivery_address'];
                            $barangay = (string) ($o['barangay'] ?? '');
                            $mapsDest = kk_maps_destination($addr, $barangay);
                            $placed = !empty($o['created_at']) ? date('M j · g:i A', strtotime((string) $o['created_at'])) : '';
                            $isPool = empty($o['rider_id']) || (int) $o['rider_id'] === 0;
                            $chatUnread = $isPool ? 0 : kk_order_chat_unread_count($pdo, (int) $o['id'], 'rider');
                            $chatUrl = app_url('order-chat.php?order_id=' . (int) $o['id']);
                            ?>
                            <li class="rider-delivery-card<?= $isPool ? ' rider-delivery-card--pool' : '' ?>">
                            <div class="rider-delivery-card__top">
                                <div class="rider-delivery-card__id">
                                    <span class="rider-delivery-card__hash">#</span><?= (int) $o['id'] ?>
                                </div>
                                <span class="rider-delivery-card__total tabular-nums">₱<?= number_format((float) $o['total'], 2) ?></span>
                            </div>
                            <?php if ($placed !== ''): ?>
                                <p class="rider-delivery-card__time"><?= htmlspecialchars($placed, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <div class="rider-delivery-card__badges">
                                <?php if ($isPool): ?>
                                    <span class="rider-pill rider-pill--new">New order</span>
                                <?php endif; ?>
                                <span class="rider-pill <?= htmlspecialchars($ordMeta['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($ordMeta['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="rider-pill <?= htmlspecialchars($delMeta['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($delMeta['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php if ($barangay !== ''): ?>
                                <p class="rider-delivery-card__location">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($barangay, ENT_QUOTES, 'UTF-8') ?></span>
                                </p>
                            <?php endif; ?>
                            <div class="rider-delivery-card__actions">
                                <a href="order-details.php?id=<?= (int) $o['id'] ?>" class="btn btn-sm btn-dark rider-btn-pill">Details</a>
                                <?php if (!$isPool): ?>
                                    <a href="<?= htmlspecialchars($chatUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary rider-btn-pill" aria-label="Message customer">
                                        <i class="bi bi-chat-dots" aria-hidden="true"></i>
                                        <?php if ($chatUnread > 0): ?>
                                            <span class="badge rounded-pill bg-danger ms-1"><?= $chatUnread > 9 ? '9+' : $chatUnread ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                                <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= urlencode($mapsDest) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary rider-btn-pill" aria-label="Open map">
                                    <i class="bi bi-map" aria-hidden="true"></i>
                                </a>
                                <a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= urlencode($mapsDest) ?>&amp;travelmode=two_wheeler" target="_blank" rel="noopener noreferrer" class="btn btn-sm rider-btn-pill rider-btn-nav" aria-label="Navigate">
                                    <i class="bi bi-sign-turn-right" aria-hidden="true"></i>
                                    <span class="d-none d-sm-inline">Navigate</span>
                                </a>
                                <?php
                                $orderId = (int) $o['id'];
                                $compact = true;
                                $redirectBack = 'dashboard.php';
                                require __DIR__ . '/_delivery-actions.php';
                                ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require '../views/rider-layout-foot.php'; ?>
