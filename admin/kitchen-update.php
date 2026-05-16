<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/kitchen_ops.php';

kk_kitchen_ensure_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kds.php');
    exit;
}

$shopId = kk_kitchen_require_shop_id();
$orderId = (int) ($_POST['order_id'] ?? 0);
$status = (string) ($_POST['kitchen_status'] ?? '');
$priority = isset($_POST['kitchen_priority']) ? (int) $_POST['kitchen_priority'] : null;

$allowed = array_keys(kk_kitchen_statuses());
if ($orderId <= 0 || !in_array($status, $allowed, true)) {
    header('Location: kds.php?shop_id=' . $shopId);
    exit;
}

$check = $pdo->prepare('SELECT id FROM orders WHERE id = ? AND shop_id = ?');
$check->execute([$orderId, $shopId]);
if (!$check->fetch()) {
    header('Location: kds.php?shop_id=' . $shopId);
    exit;
}

if ($priority !== null) {
    $pdo->prepare('UPDATE orders SET kitchen_priority = ? WHERE id = ?')->execute([max(0, min(2, $priority)), $orderId]);
}

kk_kitchen_sync_order_status($pdo, $orderId, $status);

$redirect = $_POST['redirect'] ?? 'kds.php';
if (!str_contains($redirect, 'kds.php')) {
    $redirect = 'kds.php';
}
header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'shop_id=' . $shopId);
exit;
