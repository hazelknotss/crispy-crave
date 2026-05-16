<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/kitchen_ops.php';

kk_kitchen_ensure_schema($pdo);
$shopId = kk_kitchen_require_shop_id();

$shop = $pdo->prepare('SELECT name FROM restaurants WHERE id = ?');
$shop->execute([$shopId]);
$shopName = $shop->fetchColumn() ?: 'Kitchen';

$channels = kk_kitchen_channels();
$statuses = kk_kitchen_statuses();
$activeStatuses = ['new', 'in_preparation', 'ready_pickup', 'dispatched'];

$stmt = $pdo->prepare("
    SELECT o.*, u.name AS customer_name,
        (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', m.name) SEPARATOR ', ')
         FROM order_items oi JOIN menus m ON m.id = oi.menu_id WHERE oi.order_id = o.id) AS items_summary
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.shop_id = ?
      AND o.kitchen_status NOT IN ('served','cancelled')
      AND o.order_status != 'cancelled'
    ORDER BY o.kitchen_priority DESC, o.created_at ASC
");
$stmt->execute([$shopId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$byStatus = [];
foreach ($activeStatuses as $s) {
    $byStatus[$s] = [];
}
foreach ($orders as $o) {
    $ks = $o['kitchen_status'] ?? 'new';
    if (!isset($byStatus[$ks])) {
        $byStatus[$ks] = [];
    }
    $byStatus[$ks][] = $o;
}

$kkBodyClass = 'staff-portal kds-page';
include '../views/header.php';
?>

<main class="staff-main staff-main--wide">
    <header class="staff-page-head d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="staff-page-head__title"><i class="bi bi-display"></i> Kitchen display</h1>
            <p class="staff-page-head__sub"><?= htmlspecialchars($shopName) ?> — digital tickets from all channels</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="pos.php?shop_id=<?= $shopId ?>" class="staff-btn staff-btn--primary"><i class="bi bi-cash-register"></i> POS</a>
            <a href="dashboard.php" class="staff-btn staff-btn--secondary">Dashboard</a>
            <button type="button" class="staff-btn staff-btn--secondary" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        </div>
    </header>

    <p class="kds-hint text-muted small mb-3">
        <i class="bi bi-info-circle"></i> Orders from website, POS, delivery apps, and phone appear here. Auto-refreshes every 30s.
    </p>

    <div class="kds-board">
        <?php foreach ($activeStatuses as $statusKey): ?>
            <?php $meta = $statuses[$statusKey]; ?>
            <section class="kds-column kds-column--<?= htmlspecialchars($statusKey) ?>">
                <header class="kds-column__head">
                    <span><?= htmlspecialchars($meta['label']) ?></span>
                    <span class="badge bg-<?= $meta['color'] ?>"><?= count($byStatus[$statusKey] ?? []) ?></span>
                </header>
                <div class="kds-column__body">
                    <?php if (empty($byStatus[$statusKey])): ?>
                        <p class="kds-empty">No tickets</p>
                    <?php endif; ?>
                    <?php foreach ($byStatus[$statusKey] ?? [] as $o): ?>
                        <?php
                        $ch = $channels[$o['order_channel'] ?? 'website'] ?? $channels['website'];
                        $prio = (int) ($o['kitchen_priority'] ?? 0);
                        ?>
                        <article class="kds-ticket <?= $prio > 0 ? 'kds-ticket--priority' : '' ?>">
                            <div class="kds-ticket__top">
                                <strong>#<?= (int) $o['id'] ?></strong>
                                <?php if ($o['pos_ticket_no']): ?>
                                    <span class="kds-ticket__pos"><?= htmlspecialchars($o['pos_ticket_no']) ?></span>
                                <?php endif; ?>
                                <span class="kds-channel <?= htmlspecialchars($ch['class']) ?>">
                                    <i class="bi bi-<?= htmlspecialchars($ch['icon']) ?>"></i>
                                    <?= htmlspecialchars($ch['label']) ?>
                                </span>
                            </div>
                            <p class="kds-ticket__customer"><?= htmlspecialchars($o['customer_name']) ?></p>
                            <p class="kds-ticket__items"><?= htmlspecialchars($o['items_summary'] ?? '—') ?></p>
                            <p class="kds-ticket__time">
                                <i class="bi bi-clock"></i>
                                <?= date('g:i A', strtotime($o['created_at'])) ?>
                                · ₱<?= number_format((float) $o['total'], 0) ?>
                            </p>
                            <?php if (stripos($o['delivery_address'], 'pickup') !== false): ?>
                                <span class="badge bg-info text-dark">Pickup</span>
                            <?php endif; ?>
                            <form method="post" action="kitchen-update.php" class="kds-ticket__actions">
                                <input type="hidden" name="shop_id" value="<?= $shopId ?>">
                                <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                <input type="hidden" name="redirect" value="kds.php?shop_id=<?= $shopId ?>">
                                <?php
                                $next = match ($statusKey) {
                                    'new' => 'in_preparation',
                                    'in_preparation' => 'ready_pickup',
                                    'ready_pickup' => 'dispatched',
                                    'dispatched' => 'served',
                                    default => 'served',
                                };
                                ?>
                                <button type="submit" name="kitchen_status" value="<?= $next ?>" class="btn btn-sm btn-dark w-100">
                                    → <?= htmlspecialchars($statuses[$next]['label'] ?? 'Next') ?>
                                </button>
                            </form>
                            <div class="kds-ticket__quick mt-1">
                                <form method="post" action="kitchen-update.php" class="d-inline">
                                    <input type="hidden" name="shop_id" value="<?= $shopId ?>">
                                    <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                                    <input type="hidden" name="kitchen_status" value="cancelled">
                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0">Cancel</button>
                                </form>
                                <a href="order-details.php?id=<?= (int) $o['id'] ?>" class="btn btn-link btn-sm p-0">Details</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>

<script>setTimeout(function(){ location.reload(); }, 30000);</script>
<?php include '../views/footer.php'; ?>
