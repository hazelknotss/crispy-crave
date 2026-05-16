<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';
require_once __DIR__ . '/../app/kitchen_ops.php';

kk_kitchen_ensure_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$status = (string) ($_POST['order_status'] ?? '');

$allowedStatuses = ['pending', 'preparing', 'delivering', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses, true)) {
    header('Location: orders.php');
    exit;
}

$sql = 'SELECT order_status, shop_id FROM orders WHERE id = ?';
$params = [$orderId];
$shopId = kk_staff_shop_id();
if ($shopId !== null) {
    $sql .= ' AND shop_id = ?';
    $params[] = $shopId;
}

$check = $pdo->prepare($sql);
$check->execute($params);
$row = $check->fetch(PDO::FETCH_ASSOC);

if (!$row || in_array($row['order_status'], ['completed', 'cancelled'], true)) {
    header('Location: orders.php');
    exit;
}

$kitchenMap = [
    'pending' => 'new',
    'preparing' => 'in_preparation',
    'delivering' => 'dispatched',
    'completed' => 'served',
    'cancelled' => 'cancelled',
];
$kitchenStatus = $kitchenMap[$status] ?? 'new';
kk_kitchen_sync_order_status($pdo, $orderId, $kitchenStatus);

header('Location: orders.php');
exit;
