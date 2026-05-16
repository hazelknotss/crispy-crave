<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';

$shopId = kk_staff_shop_id();
$isShop = $shopId !== null;

$sql = "
    SELECT
        o.id AS order_id,
        u.name AS user_name,
        r.name AS shop_name,
        o.shop_id,
        o.total AS total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.delivery_status,
        o.rider_id,
        o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN restaurants r ON o.shop_id = r.id
";
$params = [];
if ($isShop) {
    $sql .= ' WHERE o.shop_id = ?';
    $params[] = $shopId;
}
$sql .= ' ORDER BY o.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$riders = [];
if ($isShop) {
    $rStmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'rider' AND restaurant_id = ?");
    $rStmt->execute([$shopId]);
    $riders = $rStmt->fetchAll();
}

include '../views/header.php';
?>

<main class="staff-main">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title"><?= $isShop ? 'Shop orders' : 'All orders' ?></h1>
        <p class="staff-page-head__sub">Update status, assign riders, and view details</p>
    </header>

    <section class="staff-panel">
        <div class="staff-panel__body staff-table-wrap">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <?php if (!$isShop): ?><th>Shop</th><?php endif; ?>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <?php if ($isShop): ?><th>Delivery</th><?php endif; ?>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="<?= $isShop ? 7 : 7 ?>" class="text-center text-muted py-4">No orders found</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= (int) $order['order_id'] ?></td>
                <td><?= htmlspecialchars($order['user_name']) ?></td>
                <?php if (!$isShop): ?>
                    <td><?= htmlspecialchars($order['shop_name']) ?></td>
                <?php endif; ?>
                <td>₱<?= number_format((float) $order['total_amount'], 2) ?></td>
                <td>
                    <?= strtoupper($order['payment_method']) ?><br>
                    <span class="badge <?= $order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= strtoupper($order['payment_status']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge <?= match ($order['order_status']) {
                        'pending' => 'bg-warning text-dark',
                        'preparing' => 'bg-info text-dark',
                        'delivering' => 'bg-primary',
                        'completed' => 'bg-success',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary',
                    } ?>"><?= ucfirst($order['order_status']) ?></span>
                </td>
                <?php if ($isShop): ?>
                    <td>
                        <?php
                        $ds = $order['delivery_status'] ?? 'assigned';
                        $dc = match ($ds) {
                            'picked_up' => 'primary',
                            'on_the_way' => 'warning',
                            'delivered' => 'success',
                            default => 'secondary',
                        };
                        ?>
                        <span class="badge bg-<?= $dc ?>"><?= ucfirst(str_replace('_', ' ', $ds)) ?></span>
                    </td>
                <?php endif; ?>
                <td>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <a href="order-details.php?id=<?= (int) $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                        <?php if ($isShop): ?>
                            <form action="assign_rider.php" method="POST" class="d-inline">
                                <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">
                                <select name="rider_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                    <option value="">Rider</option>
                                    <?php foreach ($riders as $r): ?>
                                        <option value="<?= (int) $r['id'] ?>" <?= ((int) $order['rider_id'] === (int) $r['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php endif; ?>
                        <form action="update_order_status.php" method="POST" class="d-inline">
                            <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">
                            <select name="order_status" class="form-select form-select-sm"
                                <?= in_array($order['order_status'], ['completed', 'cancelled'], true) ? 'disabled' : '' ?>
                                onchange="this.form.submit()">
                                <?php foreach (['pending', 'preparing', 'delivering', 'completed', 'cancelled'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $order['order_status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
        </div>
    </section>
</main>

<?php include '../views/footer.php'; ?>
