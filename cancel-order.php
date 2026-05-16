<?php
session_start();
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/app/customer_orders.php';
require_once __DIR__ . '/app/kitchen_ops.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'user') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my-orders.php');
    exit;
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$userId = (int) $_SESSION['user']['id'];
$reasonKey = (string) ($_POST['cancel_reason'] ?? '');
$note = trim((string) ($_POST['cancel_note'] ?? ''));

$reasons = kk_customer_cancel_reasons();
$redirect = (string) ($_POST['redirect'] ?? 'my-orders.php');
if (!preg_match('#^(?:my-orders\.php|order-track\.php\?id=\d+)$#', $redirect)) {
    $redirect = 'my-orders.php';
}

if ($orderId < 1 || !isset($reasons[$reasonKey])) {
    header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'cancel_error=invalid');
    exit;
}

if ($reasonKey === 'other' && $note === '') {
    header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'cancel_error=note');
    exit;
}

kk_customer_order_ensure_schema($pdo);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order || !kk_customer_can_cancel($order)) {
    header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'cancel_error=not_allowed');
    exit;
}

$reasonText = $reasons[$reasonKey];
if ($note !== '') {
    $reasonText .= ' — ' . $note;
}
if (strlen($reasonText) > 500) {
    $reasonText = substr($reasonText, 0, 497) . '...';
}

kk_kitchen_ensure_schema($pdo);

$upd = $pdo->prepare("
    UPDATE orders
    SET order_status = 'cancelled',
        cancel_reason = ?,
        cancelled_at = NOW(),
        kitchen_status = 'cancelled'
    WHERE id = ? AND user_id = ?
");
$upd->execute([$reasonText, $orderId, $userId]);

if ($upd->rowCount() < 1) {
    header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'cancel_error=not_allowed');
    exit;
}

header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'cancelled=1');
exit;
