<?php
session_start();
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/app/customer_orders.php';
require_once __DIR__ . '/app/order_messages.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$userId = (int) $_SESSION['user']['id'];

if ($orderId < 1) {
    header('Location: my-orders.php');
    exit;
}

kk_customer_order_ensure_schema($pdo);

$stmt = $pdo->prepare("
    SELECT o.*, r.name AS shop_name, ru.name AS rider_name
    FROM orders o
    JOIN restaurants r ON r.id = o.shop_id
    LEFT JOIN users ru ON ru.id = o.rider_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: my-orders.php');
    exit;
}

$steps = kk_customer_tracking_steps($order);
$statusHeadline = kk_customer_delivery_status_label($order);
$canCancel = kk_customer_can_cancel($order);
$isPickup = kk_customer_is_pickup($order);
$chatUnread = !empty($order['rider_id']) ? kk_order_chat_unread_count($pdo, $orderId, 'user') : 0;

$cancelled = isset($_GET['cancelled']);
$cancelError = isset($_GET['cancel_error']) ? (string) $_GET['cancel_error'] : '';

$kkBodyClass = 'order-track-layout';
include __DIR__ . '/views/header.php';
?>

<main class="order-track-page">
    <div class="order-track-page__inner">
        <header class="order-track-page__intro">
            <a href="<?= htmlspecialchars(app_url('my-orders.php'), ENT_QUOTES, 'UTF-8') ?>" class="order-track-page__back">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>Back to my orders</span>
            </a>
            <p class="order-track-page__kicker">Order tracking</p>
            <h1 class="order-track-page__title">Order #<?= $orderId ?></h1>
            <p class="order-track-page__shop"><?= htmlspecialchars((string) $order['shop_name'], ENT_QUOTES, 'UTF-8') ?></p>
        </header>

        <?php if ($cancelled): ?>
            <div class="alert alert-success" role="status">Your order has been cancelled.</div>
        <?php endif; ?>
        <?php if ($cancelError === 'not_allowed'): ?>
            <div class="alert alert-warning" role="alert">This order can no longer be cancelled.</div>
        <?php elseif ($cancelError !== ''): ?>
            <div class="alert alert-warning" role="alert">Could not cancel. Please try again.</div>
        <?php endif; ?>

        <div class="order-track-page__grid">
        <section class="order-track-status-card" aria-live="polite">
            <p class="order-track-status-card__label">Current status</p>
            <p class="order-track-status-card__headline"><?= htmlspecialchars($statusHeadline, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="order-track-status-card__meta text-muted small mb-0">
                Placed <?= date('M j, Y · g:i A', strtotime((string) $order['created_at'])) ?>
                · ₱<?= number_format((float) $order['total'], 2) ?>
            </p>
        </section>

        <section class="order-track-timeline" aria-label="Order progress">
            <ol class="order-track-timeline__list">
                <?php foreach ($steps as $step): ?>
                    <li class="order-track-timeline__item order-track-timeline__item--<?= htmlspecialchars($step['state'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="order-track-timeline__dot" aria-hidden="true"></span>
                        <div class="order-track-timeline__content">
                            <p class="order-track-timeline__title mb-0"><?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="order-track-timeline__desc small text-muted mb-0"><?= htmlspecialchars($step['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

        </div>

        <section class="order-track-details">
            <?php if (!$isPickup && !empty($order['rider_name'])): ?>
                <p class="small mb-2"><i class="bi bi-person-badge me-1" aria-hidden="true"></i>Rider: <strong><?= htmlspecialchars((string) $order['rider_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
            <?php endif; ?>
            <p class="small mb-0">
                <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>
                <?= htmlspecialchars((string) $order['barangay'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        </section>

        <div class="order-track-actions">
            <?php if (!empty($order['rider_id']) && strtolower((string) $order['order_status']) !== 'cancelled'): ?>
                <a href="<?= htmlspecialchars(app_url('order-chat.php?order_id=' . $orderId), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-dark">
                    <i class="bi bi-chat-dots me-1" aria-hidden="true"></i>Message rider
                    <?php if ($chatUnread > 0): ?>
                        <span class="badge rounded-pill bg-danger ms-1"><?= $chatUnread ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
            <?php if ($canCancel): ?>
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    data-kk-cancel-order="<?= $orderId ?>"
                    data-kk-cancel-redirect="order-track.php?id=<?= $orderId ?>">
                    Cancel order
                </button>
            <?php endif; ?>
            <a href="<?= htmlspecialchars(app_url('my-orders.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-dark">All orders</a>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/views/cancel-order-modal.php';
include __DIR__ . '/views/footer.php';
