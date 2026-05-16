<?php
require '../auth/auth.php';
require '../db/database.php';
require_once __DIR__ . '/../app/rider_assign.php';
require_once __DIR__ . '/../app/delivery_proof.php';

requireRider();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$order_id = (int) ($_POST['order_id'] ?? 0);
$status = (string) ($_POST['delivery_status'] ?? '');
$rider_id = (int) $_SESSION['user']['id'];

$allowed = ['assigned', 'picked_up', 'on_the_way', 'delivered'];
if ($order_id < 1 || !in_array($status, $allowed, true)) {
    header('Location: dashboard.php');
    exit;
}

$restaurantId = isset($_SESSION['user']['restaurant_id']) ? (int) $_SESSION['user']['restaurant_id'] : null;
if ($restaurantId !== null && $restaurantId < 1) {
    $restaurantId = null;
}

if (!kk_rider_get_order($pdo, $order_id, $rider_id, $restaurantId)) {
    header('Location: dashboard.php');
    exit;
}

$redirect = (string) ($_POST['redirect'] ?? 'dashboard.php');
if (!preg_match('#^(?:dashboard\.php|order-details\.php\?id=\d+)$#', $redirect)) {
    $redirect = 'dashboard.php';
}

if ($status === 'delivered') {
    $proofFile = $_FILES['delivery_proof'] ?? [];
    $note = isset($_POST['delivery_proof_note']) ? (string) $_POST['delivery_proof_note'] : null;
    $proof = kk_delivery_proof_attach($pdo, $order_id, $rider_id, $proofFile, $note);
    if (!$proof['ok']) {
        $msg = urlencode((string) ($proof['error'] ?? 'Proof of delivery is required.'));
        header('Location: complete-delivery.php?id=' . $order_id . '&error=' . $msg);
        exit;
    }

    $stmt = $pdo->prepare('
        UPDATE orders
        SET delivery_status = ?,
            order_status = \'completed\',
            payment_status = \'paid\'
        WHERE id = ? AND rider_id = ?
    ');
} else {
    $stmt = $pdo->prepare('
        UPDATE orders
        SET delivery_status = ?
        WHERE id = ? AND rider_id = ?
    ');
}

$stmt->execute([$status, $order_id, $rider_id]);

header('Location: ' . $redirect);
exit;
