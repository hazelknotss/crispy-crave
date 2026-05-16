<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';

$orderId = (int) ($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: dashboard.php');
    exit;
}

$sql = '
    SELECT o.*, u.name AS customer_name, r.name AS shop_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN restaurants r ON o.shop_id = r.id
    WHERE o.id = ?
';
$params = [$orderId];

$shopId = kk_staff_shop_id();
if ($shopId !== null) {
    $sql .= ' AND o.shop_id = ?';
    $params[] = $shopId;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: dashboard.php');
    exit;
}

$items = $pdo->prepare('
    SELECT oi.*, m.name
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
');
$items->execute([$orderId]);
$items = $items->fetchAll();

include '../views/header.php';
?>

<main class="staff-main">
    <a href="<?= $shopId ? 'dashboard.php' : 'orders.php' ?>" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <h3 class="d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-box-seam"></i><span>Order #<?= (int) $order['id'] ?></span>
    </h3>

    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <?php if (!$shopId): ?>
                <p><strong>Shop:</strong> <?= htmlspecialchars($order['shop_name']) ?></p>
            <?php endif; ?>
            <p><strong>Payment:</strong> <?= strtoupper($order['payment_method']) ?> (<?= $order['payment_status'] ?>)</p>
            <p><strong>Status:</strong> <?= ucfirst($order['order_status']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
            <p><strong>Barangay:</strong> <?= htmlspecialchars($order['barangay']) ?></p>
            <p><strong>Total:</strong> ₱<?= number_format((float) $order['total'], 2) ?></p>
        </div>
    </div>

    <h5 class="mb-3">Ordered items</h5>
    <table class="table table-bordered">
        <thead><tr><th>Menu</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td>₱<?= number_format((float) $item['price'], 2) ?></td>
                <td>₱<?= number_format((float) $item['price'] * (int) $item['quantity'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php include '../views/footer.php'; ?>
