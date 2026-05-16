<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';

include '../views/header.php';

$shopId = kk_staff_shop_id();
$isShop = $shopId !== null;
?>

<main class="staff-main">
    <?php if ($isShop): ?>
        <?php
        $shopStmt = $pdo->prepare('SELECT name FROM restaurants WHERE id = ?');
        $shopStmt->execute([$shopId]);
        $shopName = $shopStmt->fetchColumn() ?: 'Your shop';

        $stmt = $pdo->prepare("
            SELECT
                o.id,
                u.name AS customer_name,
                o.total,
                o.payment_method,
                o.payment_status,
                o.order_status,
                o.delivery_status,
                o.created_at,
                o.rider_id
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.shop_id = ?
            ORDER BY o.id DESC
        ");
        $stmt->execute([$shopId]);
        $orders = $stmt->fetchAll();

        $totalOrders = count($orders);
        $completed = 0;
        $pending = 0;
        foreach ($orders as $o) {
            if ($o['order_status'] === 'completed') {
                $completed++;
            }
            if ($o['order_status'] === 'pending') {
                $pending++;
            }
        }
        ?>

        <header class="staff-page-head">
            <h1 class="staff-page-head__title"><?= htmlspecialchars($shopName) ?></h1>
            <p class="staff-page-head__sub">Kitchen dashboard — manage orders, menu, and riders</p>
        </header>

        <div class="staff-actions">
            <a href="kds.php?shop_id=<?= (int) $shopId ?>" class="staff-btn staff-btn--primary">
                <i class="bi bi-display"></i> KDS
            </a>
            <a href="pos.php?shop_id=<?= (int) $shopId ?>" class="staff-btn staff-btn--primary">
                <i class="bi bi-cash-register"></i> POS
            </a>
            <a href="menus.php?shop_id=<?= (int) $shopId ?>" class="staff-btn staff-btn--success">
                <i class="bi bi-journal-text"></i> Menu
            </a>
            <a href="inventory.php?shop_id=<?= (int) $shopId ?>" class="staff-btn staff-btn--secondary">
                <i class="bi bi-boxes"></i> Stock
            </a>
            <a href="recipes.php?shop_id=<?= (int) $shopId ?>" class="staff-btn staff-btn--secondary">
                <i class="bi bi-book"></i> Recipes
            </a>
            <a href="orders.php" class="staff-btn staff-btn--secondary">
                <i class="bi bi-receipt"></i> Orders
            </a>
        </div>

        <div class="staff-stat-grid">
            <div class="staff-stat">
                <p class="staff-stat__label">Total orders</p>
                <p class="staff-stat__value"><?= $totalOrders ?></p>
            </div>
            <div class="staff-stat staff-stat--amber">
                <p class="staff-stat__label">Pending</p>
                <p class="staff-stat__value"><?= $pending ?></p>
            </div>
            <div class="staff-stat staff-stat--green">
                <p class="staff-stat__label">Completed</p>
                <p class="staff-stat__value"><?= $completed ?></p>
            </div>
        </div>

        <section class="staff-panel">
            <div class="staff-panel__head">
                <i class="bi bi-box-seam"></i><span>Recent orders</span>
            </div>
            <div class="staff-panel__body staff-table-wrap">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="staff-empty">No orders yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?= (int) $order['id'] ?></strong></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td>₱<?= number_format((float) $order['total'], 2) ?></td>
                            <td>
                                <span class="text-muted small d-block"><?= strtoupper($order['payment_method']) ?></span>
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
                            <td>
                                <?php
                                $status = $order['delivery_status'] ?? 'assigned';
                                $color = match ($status) {
                                    'picked_up' => 'primary',
                                    'on_the_way' => 'warning',
                                    'delivered' => 'success',
                                    default => 'secondary',
                                };
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <a href="order-details.php?id=<?= (int) $order['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <form action="assign_rider.php" method="POST" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                        <select name="rider_id" class="form-select form-select-sm" style="min-width:7rem;" onchange="this.form.submit()">
                                            <option value="">Rider…</option>
                                            <?php
                                            $riders = $pdo->prepare("SELECT id, name FROM users WHERE role = 'rider' AND restaurant_id = ?");
                                            $riders->execute([$shopId]);
                                            foreach ($riders as $r):
                                            ?>
                                                <option value="<?= (int) $r['id'] ?>" <?= ((int) $order['rider_id'] === (int) $r['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($r['name']) ?>
                                                </option>
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

    <?php else: ?>
        <header class="staff-page-head">
            <h1 class="staff-page-head__title">Staff dashboard</h1>
            <p class="staff-page-head__sub">Manage shops, menus, and platform orders</p>
        </header>

        <div class="staff-actions">
            <a href="add-shop.php" class="staff-btn staff-btn--primary">
                <i class="bi bi-plus-lg"></i> Add shop
            </a>
            <a href="shop.php" class="staff-btn staff-btn--secondary">
                <i class="bi bi-shop"></i> Manage shops
            </a>
            <a href="orders.php" class="staff-btn staff-btn--secondary">
                <i class="bi bi-receipt"></i> All orders
            </a>
            <a href="admin_stats.php" class="staff-btn staff-btn--primary">
                <i class="bi bi-bar-chart-line"></i> Statistics
            </a>
        </div>

        <div class="staff-shop-grid">
            <?php
            $shops = $pdo->query('SELECT * FROM restaurants ORDER BY id DESC')->fetchAll();
            foreach ($shops as $shop):
            ?>
                <article class="staff-shop-card">
                    <div class="staff-shop-card__img-wrap staff-shop-card__img-wrap--contain">
                        <img src="<?= htmlspecialchars(app_url('images/logos/' . $shop['logo'])) ?>"
                            onerror="this.src='<?= htmlspecialchars(app_url('images/no-image.png'), ENT_QUOTES) ?>'"
                            class="staff-shop-card__img" alt="">
                    </div>
                    <div class="staff-shop-card__body">
                        <h2 class="staff-shop-card__title"><?= htmlspecialchars($shop['name']) ?></h2>
                        <div class="staff-shop-card__actions">
                            <a href="kds.php?shop_id=<?= (int) $shop['id'] ?>" class="staff-chip staff-chip--menus">KDS</a>
                            <a href="pos.php?shop_id=<?= (int) $shop['id'] ?>" class="staff-chip staff-chip--edit">POS</a>
                            <a href="menus.php?shop_id=<?= (int) $shop['id'] ?>" class="staff-chip staff-chip--menus">Menus</a>
                            <a href="edit-shop.php?id=<?= (int) $shop['id'] ?>" class="staff-chip staff-chip--edit">Edit</a>
                            <a href="shop_delete.php?id=<?= (int) $shop['id'] ?>" class="staff-chip staff-chip--delete"
                               onclick="return confirm('Delete this shop?')">Delete</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../views/footer.php'; ?>
