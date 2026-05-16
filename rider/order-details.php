<?php
require '../auth/auth.php';
require '../db/database.php';
require_once __DIR__ . '/../app/rider_assign.php';
require_once __DIR__ . '/../app/order_messages.php';
require __DIR__ . '/_status.php';

requireRider();

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$rider_id = (int) $_SESSION['user']['id'];
$restaurantId = isset($_SESSION['user']['restaurant_id']) ? (int) $_SESSION['user']['restaurant_id'] : null;
if ($restaurantId !== null && $restaurantId < 1) {
    $restaurantId = null;
}

if ($order_id < 1) {
    header('Location: dashboard.php');
    exit;
}

$order = kk_rider_get_order($pdo, $order_id, $rider_id, $restaurantId);

if (!$order) {
    header('Location: dashboard.php');
    exit;
}

$customerStmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
$customerStmt->execute([(int) $order['user_id']]);
$customerName = $customerStmt->fetchColumn();
$order['customer_name'] = $customerName !== false ? (string) $customerName : 'Customer';

$stmt = $pdo->prepare('
    SELECT oi.*, m.name
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
');
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$deliveryStatus = (string) ($order['delivery_status'] ?? 'assigned');
$delMeta = rider_delivery_status_meta($deliveryStatus);
$ordMeta = rider_order_status_meta((string) ($order['order_status'] ?? 'pending'));
$addr = (string) $order['delivery_address'];
$mapsDest = kk_maps_destination($addr, (string) ($order['barangay'] ?? ''));
$chatUnread = kk_order_chat_unread_count($pdo, $order_id, 'rider');
$chatUrl = app_url('order-chat.php?order_id=' . $order_id);

$kkRiderNavActive = 'deliveries';
$riderPageTitle = 'Order #' . $order_id;
require '../views/rider-layout-head.php';
?>

<main class="rider-dash-page">
    <div class="container-fluid rider-dash-page__inner">
        <header class="rider-dash-header mb-3">
            <a href="dashboard.php" class="rider-login-panel__back d-inline-flex">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>All deliveries</span>
            </a>
            <p class="rider-dash-header__kicker mt-3">Order details</p>
            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                <h1 class="rider-dash-header__title mb-0">Order #<?= (int) $order['id'] ?></h1>
                <span class="rider-pill <?= htmlspecialchars($delMeta['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($delMeta['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

        <div class="rider-dash-surface p-3 p-md-4 mb-3">
            <dl class="rider-dash-detail-grid">
                <dt>Customer</dt>
                <dd class="fw-medium"><?= htmlspecialchars((string) $order['customer_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Barangay</dt>
                <dd><?= htmlspecialchars((string) $order['barangay'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Address</dt>
                <dd class="small"><?= nl2br(htmlspecialchars($addr, ENT_QUOTES, 'UTF-8')) ?></dd>
                <dt>Payment</dt>
                <dd><?= htmlspecialchars(strtoupper((string) $order['payment_method']), ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Kitchen status</dt>
                <dd><span class="rider-pill <?= htmlspecialchars($ordMeta['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($ordMeta['label'], ENT_QUOTES, 'UTF-8') ?></span></dd>
                <dt>Total</dt>
                <dd class="tabular-nums fw-bold fs-5">₱<?= number_format((float) $order['total'], 2) ?></dd>
            </dl>
            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= urlencode($mapsDest) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary rider-btn-pill">
                    <i class="bi bi-map me-1" aria-hidden="true"></i>Map
                </a>
                <a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= urlencode($mapsDest) ?>&amp;travelmode=two_wheeler" target="_blank" rel="noopener noreferrer" class="btn btn-sm rider-btn-pill rider-btn-nav">
                    <i class="bi bi-sign-turn-right me-1" aria-hidden="true"></i>Navigate
                </a>
                <a href="<?= htmlspecialchars($chatUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary rider-btn-pill">
                    <i class="bi bi-chat-dots me-1" aria-hidden="true"></i>Message
                    <?php if ($chatUnread > 0): ?>
                        <span class="badge rounded-pill bg-danger ms-1"><?= $chatUnread > 9 ? '9+' : $chatUnread ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <?php
        $orderId = $order_id;
        $compact = false;
        $redirectBack = 'order-details.php?id=' . $order_id;
        ?>
        <div class="rider-dash-surface p-3 p-md-4 mb-3">
            <h2 class="h6 fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-truck text-warning" aria-hidden="true"></i>
                <span>Delivery status</span>
            </h2>
            <?php require __DIR__ . '/_delivery-actions.php'; ?>
        </div>

        <div class="rider-dash-surface">
            <div class="px-3 py-3 border-bottom bg-light-subtle">
                <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-bag-check text-warning" aria-hidden="true"></i>
                    <span>Items</span>
                </h2>
            </div>
            <div class="table-responsive">
                <table class="table rider-dash-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col" class="text-end">Qty</th>
                            <th scope="col" class="text-end">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i): ?>
                            <tr>
                                <td class="fw-medium"><?= htmlspecialchars((string) $i['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end tabular-nums"><?= (int) $i['quantity'] ?></td>
                                <td class="text-end tabular-nums">₱<?= number_format((float) $i['price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require '../views/rider-layout-foot.php'; ?>
