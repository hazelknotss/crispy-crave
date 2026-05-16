<?php
session_start();
require 'db/database.php';
require_once __DIR__ . '/app/order_messages.php';
require_once __DIR__ . '/app/customer_orders.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
kk_customer_order_ensure_schema($pdo);

$stmt = $pdo->prepare("
    SELECT 
        o.id AS order_id,
        r.name AS shop_name,
        o.total AS total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.delivery_status,
        o.barangay,
        o.delivery_address,
        o.rider_id,
        o.cancel_reason,
        ru.name AS rider_name,
        o.created_at
    FROM orders o
    JOIN restaurants r ON o.shop_id = r.id
    LEFT JOIN users ru ON ru.id = o.rider_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

kk_order_messages_ensure_schema($pdo);
foreach ($orders as &$orderRow) {
    $orderRow['chat_unread'] = !empty($orderRow['rider_id'])
        ? kk_order_chat_unread_count($pdo, (int) $orderRow['order_id'], 'user')
        : 0;
    $orderRow['track_steps'] = kk_customer_tracking_steps($orderRow);
    $orderRow['status_label'] = kk_customer_delivery_status_label($orderRow);
    $orderRow['can_cancel'] = kk_customer_can_cancel($orderRow);
}
unset($orderRow);

$cancelled = isset($_GET['cancelled']);
$cancelError = isset($_GET['cancel_error']) ? (string) $_GET['cancel_error'] : '';

$kkBodyClass = 'my-orders-layout';
include 'views/header.php';
?>

<main class="my-orders-page">
    <div class="my-orders-page__inner">
        <header class="my-orders-page__intro">
            <p class="my-orders-page__kicker">Your account</p>
            <h1 class="my-orders-page__title">My orders</h1>
            <p class="my-orders-page__lede">Track delivery status or cancel while your order is still being prepared.</p>
        </header>

        <?php if ($cancelled): ?>
            <div class="alert alert-success mb-3" role="status">Your order has been cancelled.</div>
        <?php endif; ?>
        <?php if ($cancelError === 'not_allowed'): ?>
            <div class="alert alert-warning mb-3" role="alert">That order can no longer be cancelled.</div>
        <?php elseif ($cancelError !== ''): ?>
            <div class="alert alert-warning mb-3" role="alert">Could not cancel. Please try again.</div>
        <?php endif; ?>

        <?php if ($orders === []): ?>
            <div class="my-orders-empty-card" role="status">
                <i class="bi bi-inbox my-orders-empty__icon" aria-hidden="true"></i>
                <p class="my-orders-empty__text">You have no orders yet.</p>
                <a class="btn btn-sm btn-dark my-orders-empty__cta" href="<?= htmlspecialchars(app_url('index.php')) ?>#shops">Browse shops</a>
            </div>
        <?php else: ?>
            <ul class="my-orders-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $orderId = (int) $order['order_id'];
                    $statusKey = strtolower((string) $order['order_status']);
                    $isCancelled = $statusKey === 'cancelled';
                    $isComplete = $statusKey === 'completed';
                    ?>
                    <li class="my-orders-card<?= $isCancelled ? ' my-orders-card--cancelled' : '' ?>">
                        <div class="my-orders-card__top">
                            <div>
                                <p class="my-orders-card__id mb-0">Order #<?= $orderId ?></p>
                                <p class="my-orders-card__shop mb-0"><?= htmlspecialchars((string) $order['shop_name'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <p class="my-orders-card__total tabular-nums mb-0">₱<?= number_format((float) $order['total_amount'], 2) ?></p>
                        </div>

                        <p class="my-orders-card__status"><?= htmlspecialchars((string) $order['status_label'], ENT_QUOTES, 'UTF-8') ?></p>

                        <?php if (!$isCancelled): ?>
                            <ol class="my-orders-mini-track" aria-label="Order progress for order <?= $orderId ?>">
                                <?php foreach ($order['track_steps'] as $step): ?>
                                    <?php if ($step['key'] === 'cancelled') {
                                        continue;
                                    } ?>
                                    <li
                                        class="my-orders-mini-track__step my-orders-mini-track__step--<?= htmlspecialchars($step['state'], ENT_QUOTES, 'UTF-8') ?>"
                                        title="<?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="visually-hidden"><?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <p class="small text-muted mb-2"><?= htmlspecialchars((string) ($order['cancel_reason'] ?? 'Order cancelled'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>

                        <p class="my-orders-card__meta small text-muted mb-0">
                            <?= date('M j, Y · g:i A', strtotime((string) $order['created_at'])) ?>
                            · <?= htmlspecialchars(strtoupper((string) $order['payment_method']), ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($order['payment_status'] === 'paid'): ?>
                                · <span class="text-success">Paid</span>
                            <?php endif; ?>
                        </p>

                        <div class="my-orders-card__actions">
                            <a href="<?= htmlspecialchars(app_url('order-track.php?id=' . $orderId), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-dark">
                                <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>Track order
                            </a>
                            <?php if (!empty($order['rider_id']) && !$isCancelled): ?>
                                <?php $unread = (int) ($order['chat_unread'] ?? 0); ?>
                                <a href="<?= htmlspecialchars(app_url('order-chat.php?order_id=' . $orderId), ENT_QUOTES, 'UTF-8') ?>"
                                   class="btn btn-sm btn-outline-dark my-orders-msg-btn">
                                    <i class="bi bi-chat-dots" aria-hidden="true"></i>
                                    <?php if ($unread > 0): ?>
                                        <span class="my-orders-msg-badge"><?= $unread > 9 ? '9+' : $unread ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($order['can_cancel']): ?>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-kk-cancel-order="<?= $orderId ?>"
                                    data-kk-cancel-redirect="my-orders.php">
                                    Cancel
                                </button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

<?php
include __DIR__ . '/views/cancel-order-modal.php';
include 'views/footer.php';
