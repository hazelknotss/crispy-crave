<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/kitchen_ops.php';

kk_kitchen_ensure_schema($pdo);
$shopId = kk_kitchen_require_shop_id();

$cart = $_SESSION['pos_cart'][$shopId] ?? [];
if (empty($cart)) {
    header('Location: pos.php?shop_id=' . $shopId);
    exit;
}

$payment = in_array($_POST['payment_method'] ?? '', ['cod', 'gcash'], true) ? $_POST['payment_method'] : 'cod';
$fulfillment = ($_POST['fulfillment'] ?? '') === 'delivery' ? 'delivery' : 'pickup';
$staffId = (int) $_SESSION['user']['id'];
$cashTendered = isset($_POST['cash_tendered']) && $_POST['cash_tendered'] !== ''
    ? (float) $_POST['cash_tendered']
    : null;

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
}

if ($payment === 'cod' && ($cashTendered === null || $cashTendered < $subtotal - 0.001)) {
    header('Location: pos.php?shop_id=' . $shopId . '&cash_error=1');
    exit;
}

$change = $payment === 'cod' && $cashTendered !== null
    ? max(0, round($cashTendered - $subtotal, 2))
    : 0;

$ticketNo = 'POS-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
$address = $fulfillment === 'pickup'
    ? "POS Walk-in — Store pickup\nFulfillment: Store pickup"
    : 'POS Walk-in — Delivery (counter order)';

if ($payment === 'cod' && $cashTendered !== null) {
    $address .= "\nCash tendered: ₱" . number_format($cashTendered, 2)
        . "\nChange: ₱" . number_format($change, 2);
}

$pdo->beginTransaction();
try {
    $pdo->prepare("
        INSERT INTO orders (
            user_id, shop_id, total, payment_method, payment_status,
            order_status, kitchen_status, order_channel, pos_ticket_no,
            delivery_address, barangay, distance_km, rider_fee, pickup_time
        ) VALUES (?, ?, ?, ?, 'paid', 'preparing', 'in_preparation', 'pos', ?, ?, 'Store', 0, 0, CURTIME())
    ")->execute([$staffId, $shopId, $subtotal, $payment, $ticketNo, $address]);

    $orderId = (int) $pdo->lastInsertId();

    $line = $pdo->prepare('INSERT INTO order_items (order_id, menu_id, price, quantity) VALUES (?, ?, ?, ?)');
    foreach ($cart as $menuId => $item) {
        $line->execute([$orderId, (int) $menuId, $item['price'], (int) $item['qty']]);
    }

    $pdo->commit();
    $_SESSION['pos_cart'][$shopId] = [];
} catch (Throwable $e) {
    $pdo->rollBack();
    header('Location: pos.php?shop_id=' . $shopId . '&error=1');
    exit;
}

header('Location: kds.php?shop_id=' . $shopId . '&pos_ok=' . $orderId);
exit;
