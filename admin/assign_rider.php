<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$shopId = kk_staff_shop_id();
if ($shopId === null) {
    header('Location: orders.php');
    exit;
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$riderId = $_POST['rider_id'] ?? '';
$riderId = $riderId === '' ? null : (int) $riderId;

$check = $pdo->prepare('SELECT id FROM orders WHERE id = ? AND shop_id = ?');
$check->execute([$orderId, $shopId]);
if (!$check->fetch()) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare('UPDATE orders SET rider_id = ? WHERE id = ?');
$stmt->execute([$riderId, $orderId]);

header('Location: dashboard.php');
exit;
