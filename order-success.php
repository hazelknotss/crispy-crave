<?php
session_start();
require 'db/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($order_id < 1) {
    header('Location: index.php');
    exit;
}

$user_id = (int) $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT o.*, r.name AS shop_name
    FROM orders o
    JOIN restaurants r ON r.id = o.shop_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}

$itemStmt = $pdo->prepare("
    SELECT oi.quantity, oi.price, m.name AS item_name
    FROM order_items oi
    JOIN menus m ON m.id = oi.menu_id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");
$itemStmt->execute([$order_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$referenceNo = 'KK-' . date('Ymd', strtotime((string) $order['created_at'])) . '-' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT);

$grandTotal = (float) $order['total'];
$riderFee = (float) $order['rider_fee'];
$foodTotal = max(0, $grandTotal - $riderFee);

$paymentMethod = strtolower((string) ($order['payment_method'] ?? 'cod'));
$paymentStatus = strtolower((string) ($order['payment_status'] ?? 'pending'));
$orderStatus = strtolower((string) ($order['order_status'] ?? 'pending'));
$gcashRef = trim((string) ($order['gcash_ref'] ?? ''));

$paymentLabels = [
    'cod'   => 'Cash on delivery',
    'gcash' => 'GCash',
];
$paymentLabel = $paymentLabels[$paymentMethod] ?? strtoupper($paymentMethod);

if ($paymentMethod === 'gcash' && $paymentStatus === 'paid') {
    $paymentBadgeClass = 'order-success-page__badge--paid';
    $paymentBadgeText = 'GCash · Paid';
    $paymentBadgeIcon = 'bi-check-circle-fill';
} elseif ($paymentMethod === 'gcash') {
    $paymentBadgeClass = 'order-success-page__badge--pending';
    $paymentBadgeText = 'GCash · Pending';
    $paymentBadgeIcon = 'bi-phone';
} else {
    $paymentBadgeClass = 'order-success-page__badge--cod';
    $paymentBadgeText = 'Pay on delivery';
    $paymentBadgeIcon = 'bi-cash-coin';
}

$statusLabels = [
    'pending'    => 'Pending',
    'preparing'  => 'Preparing',
    'delivering' => 'On the way',
    'completed'  => 'Completed',
    'cancelled'  => 'Cancelled',
];
$orderStatusLabel = $statusLabels[$orderStatus] ?? ucfirst($orderStatus);

$placedAt = date('M j, Y · g:i A', strtotime((string) $order['created_at']));
$isPickup = stripos((string) $order['barangay'], 'pickup') !== false
    || stripos((string) $order['delivery_address'], 'pickup') !== false;

include 'views/header.php';
?>

<main class="order-success-page">
    <div class="container order-success-page__inner">
        <header class="order-success-page__hero">
            <div class="order-success-page__check" aria-hidden="true">
                <i class="bi bi-check-lg"></i>
            </div>
            <p class="order-success-page__kicker">Thank you</p>
            <h1 class="order-success-page__title">Order confirmed</h1>
            <p class="order-success-page__lede">We received your order. Save your reference number below for support or payment follow-up.</p>
        </header>

        <div class="order-success-page__layout">
            <section class="order-success-page__ref" aria-labelledby="order-ref-heading">
                <h2 id="order-ref-heading" class="visually-hidden">Reference number</h2>
                <p class="order-success-page__ref-label">Reference number</p>
                <p class="order-success-page__ref-value tabular-nums" id="orderReference"><?= htmlspecialchars($referenceNo, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="order-success-page__ref-meta text-muted small mb-0">Order #<?= (int) $order['id'] ?> · <?= htmlspecialchars($placedAt, ENT_QUOTES, 'UTF-8') ?></p>
                <div class="order-success-page__ref-actions">
                    <button type="button" class="btn btn-sm btn-outline-dark order-success-page__copy" id="copyReferenceBtn" data-copy-target="orderReference">
                        <i class="bi bi-clipboard me-1" aria-hidden="true"></i>Copy reference
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary order-success-page__print" onclick="window.print()">
                        <i class="bi bi-printer me-1" aria-hidden="true"></i>Print receipt
                    </button>
                </div>
            </section>

            <article class="order-success-page__receipt" aria-labelledby="order-receipt-heading">
                <header class="order-success-page__receipt-head">
                    <div>
                        <h2 id="order-receipt-heading" class="order-success-page__receipt-title">
                            <i class="bi bi-receipt" aria-hidden="true"></i>
                            <span>Receipt</span>
                        </h2>
                        <p class="order-success-page__receipt-shop mb-0"><?= htmlspecialchars((string) $order['shop_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span class="order-success-page__badge order-success-page__badge--status"><?= htmlspecialchars($orderStatusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </header>

                <div class="table-responsive order-success-page__scroll">
                    <table class="table order-success-receipt-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col" class="text-end">Qty</th>
                                <th scope="col" class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $line): ?>
                                <?php
                                $qty = (int) $line['quantity'];
                                $lineTotal = (float) $line['price'] * $qty;
                                ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars((string) $line['item_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end tabular-nums"><?= $qty ?></td>
                                    <td class="text-end tabular-nums">₱<?= number_format($lineTotal, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="order-success-page__totals">
                    <div class="order-success-page__totals-row">
                        <span>Food subtotal</span>
                        <span class="tabular-nums">₱<?= number_format($foodTotal, 2) ?></span>
                    </div>
                    <div class="order-success-page__totals-row">
                        <span>Rider fee</span>
                        <span class="tabular-nums">₱<?= number_format($riderFee, 2) ?></span>
                    </div>
                    <div class="order-success-page__totals-row order-success-page__totals-row--grand">
                        <span>Grand total</span>
                        <span class="tabular-nums">₱<?= number_format($grandTotal, 2) ?></span>
                    </div>
                </div>

                <dl class="order-success-page__meta">
                    <div class="order-success-page__meta-item">
                        <dt>Payment</dt>
                        <dd>
                            <span><?= htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="order-success-page__badge <?= htmlspecialchars($paymentBadgeClass, ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi <?= htmlspecialchars($paymentBadgeIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                <?= htmlspecialchars($paymentBadgeText, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </dd>
                    </div>
                    <?php if ($gcashRef !== ''): ?>
                        <div class="order-success-page__meta-item">
                            <dt>GCash ref</dt>
                            <dd class="tabular-nums"><?= htmlspecialchars($gcashRef, ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                    <?php endif; ?>
                    <div class="order-success-page__meta-item">
                        <dt><?= $isPickup ? 'Fulfillment' : 'Delivery' ?></dt>
                        <dd>
                            <span class="d-block fw-medium"><?= htmlspecialchars((string) $order['barangay'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (trim((string) $order['delivery_address']) !== ''): ?>
                                <span class="order-success-page__address"><?= nl2br(htmlspecialchars((string) $order['delivery_address'], ENT_QUOTES, 'UTF-8')) ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <?php if (!$isPickup && (float) $order['distance_km'] > 0): ?>
                        <div class="order-success-page__meta-item">
                            <dt>Distance</dt>
                            <dd class="tabular-nums"><?= number_format((float) $order['distance_km'], 1) ?> km</dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </article>
        </div>

        <div class="order-success-page__actions">
            <a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-dark order-success-page__cta">
                <i class="bi bi-house-door me-2" aria-hidden="true"></i>Back to home
            </a>
            <a href="<?= htmlspecialchars(app_url('order-track.php?id=' . (int) $order['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-dark order-success-page__cta">
                <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Track order
            </a>
            <a href="<?= htmlspecialchars(app_url('my-orders.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary order-success-page__cta">
                <i class="bi bi-box-seam me-2" aria-hidden="true"></i>All orders
            </a>
        </div>

        <p class="order-success-page__footnote">Keep your reference number handy. For GCash orders, use it when contacting the kitchen about payment.</p>
    </div>
</main>

<script>
(function () {
    var btn = document.getElementById('copyReferenceBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var el = document.getElementById(btn.getAttribute('data-copy-target'));
        if (!el) return;
        var text = el.textContent.trim();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                btn.innerHTML = '<i class="bi bi-check2 me-1" aria-hidden="true"></i>Copied';
                setTimeout(function () {
                    btn.innerHTML = '<i class="bi bi-clipboard me-1" aria-hidden="true"></i>Copy reference';
                }, 2000);
            });
        } else {
            window.prompt('Copy reference number:', text);
        }
    });
})();
</script>

<?php include 'views/footer.php'; ?>
