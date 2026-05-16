<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';

$shopId = kk_staff_require_shop();

$totalRevenue = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0) FROM orders
    WHERE shop_id = ? AND order_status = 'completed' AND payment_status = 'paid'
");
$totalRevenue->execute([$shopId]);
$totalRevenue = $totalRevenue->fetchColumn();

$todaySales = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0) FROM orders
    WHERE shop_id = ? AND DATE(created_at) = CURDATE()
    AND order_status = 'completed' AND payment_status = 'paid'
");
$todaySales->execute([$shopId]);
$todaySales = $todaySales->fetchColumn();

$monthlySales = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0) FROM orders
    WHERE shop_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    AND order_status = 'completed' AND payment_status = 'paid'
");
$monthlySales->execute([$shopId]);
$monthlySales = $monthlySales->fetchColumn();

$totalOrders = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE shop_id = ?');
$totalOrders->execute([$shopId]);
$totalOrders = $totalOrders->fetchColumn();

$stmt = $pdo->prepare('
    SELECT id, total, order_status, created_at FROM orders
    WHERE shop_id = ? ORDER BY created_at DESC LIMIT 5
');
$stmt->execute([$shopId]);
$recentOrders = $stmt->fetchAll();

include '../views/header.php';
?>

<main class="staff-main">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title">Earnings</h1>
        <p class="staff-page-head__sub">Revenue and sales for your shop</p>
    </header>

    <div class="staff-stat-grid">
        <div class="staff-stat staff-stat--green">
            <p class="staff-stat__label">Total revenue</p>
            <p class="staff-stat__value">₱<?= number_format((float) $totalRevenue, 2) ?></p>
        </div>
        <div class="staff-stat">
            <p class="staff-stat__label">Today's sales</p>
            <p class="staff-stat__value">₱<?= number_format((float) $todaySales, 2) ?></p>
        </div>
        <div class="staff-stat">
            <p class="staff-stat__label">Monthly sales</p>
            <p class="staff-stat__value">₱<?= number_format((float) $monthlySales, 2) ?></p>
        </div>
        <div class="staff-stat">
            <p class="staff-stat__label">Total orders</p>
            <p class="staff-stat__value"><?= (int) $totalOrders ?></p>
        </div>
    </div>

    <section class="staff-panel">
        <div class="staff-panel__head">Recent orders</div>
        <div class="staff-panel__body staff-table-wrap">
            <table class="table align-middle mb-0">
                <thead><tr><th>ID</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td>#<?= (int) $o['id'] ?></td>
                        <td>₱<?= number_format((float) $o['total'], 2) ?></td>
                        <td><?= ucfirst($o['order_status']) ?></td>
                        <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="4" class="staff-empty">No orders yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include '../views/footer.php'; ?>
