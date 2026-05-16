<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/kitchen_ops.php';

kk_kitchen_ensure_schema($pdo);
$shopId = kk_kitchen_require_shop_id();
$userId = (int) $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invId = (int) ($_POST['inventory_id'] ?? 0);
    $qty = (float) ($_POST['qty'] ?? 0);
    $reason = trim((string) ($_POST['reason'] ?? 'Spoilage'));
    if ($invId > 0 && $qty > 0) {
        $cost = $pdo->prepare('SELECT cost_per_unit FROM kitchen_inventory WHERE id = ? AND shop_id = ?');
        $cost->execute([$invId, $shopId]);
        $cpu = (float) $cost->fetchColumn();
        $impact = $qty * $cpu;
        kk_kitchen_adjust_stock($pdo, $invId, $shopId, -$qty, 'waste', null, $reason);
        $pdo->prepare('INSERT INTO kitchen_waste_logs (shop_id, inventory_id, qty, reason, cost_impact, logged_by) VALUES (?,?,?,?,?,?)')
            ->execute([$shopId, $invId, $qty, $reason, $impact, $userId]);
    }
    header('Location: waste.php?shop_id=' . $shopId);
    exit;
}

$inventory = $pdo->prepare('SELECT id, name, unit FROM kitchen_inventory WHERE shop_id = ? ORDER BY name');
$inventory->execute([$shopId]);
$inventory = $inventory->fetchAll(PDO::FETCH_ASSOC);

$logs = $pdo->prepare('
    SELECT w.*, i.name AS item_name
    FROM kitchen_waste_logs w
    JOIN kitchen_inventory i ON i.id = w.inventory_id
    WHERE w.shop_id = ?
    ORDER BY w.created_at DESC LIMIT 30
');
$logs->execute([$shopId]);
$logs = $logs->fetchAll(PDO::FETCH_ASSOC);

$totalWaste = array_sum(array_column($logs, 'cost_impact'));

include '../views/header.php';
?>

<main class="staff-main">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title">Yield & waste</h1>
        <p class="staff-page-head__sub">Track spoiled or discarded inventory and cost impact</p>
    </header>

    <div class="staff-stat-grid mb-4">
        <div class="staff-stat staff-stat--amber">
            <p class="staff-stat__label">Recent waste cost (30 logs)</p>
            <p class="staff-stat__value">₱<?= number_format($totalWaste, 2) ?></p>
        </div>
    </div>

    <section class="staff-panel mb-4">
        <div class="staff-panel__head">Log waste</div>
        <div class="staff-panel__body--padded">
            <form method="post" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <select name="inventory_id" class="form-select" required>
                        <option value="">Select item</option>
                        <?php foreach ($inventory as $inv): ?>
                            <option value="<?= (int) $inv['id'] ?>"><?= htmlspecialchars($inv['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="number" step="0.01" name="qty" class="form-control" placeholder="Qty" required></div>
                <div class="col-md-3"><input type="text" name="reason" class="form-control" placeholder="Reason" value="Spoilage"></div>
                <div class="col-md-2"><button type="submit" class="staff-btn staff-btn--warning w-100">Log</button></div>
            </form>
        </div>
    </section>

    <section class="staff-panel">
        <div class="staff-panel__head">Recent waste logs</div>
        <div class="staff-panel__body staff-table-wrap">
            <table class="table mb-0">
                <thead><tr><th>Item</th><th>Qty</th><th>Reason</th><th>Cost</th><th>When</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['item_name']) ?></td>
                        <td><?= number_format((float) $log['qty'], 2) ?></td>
                        <td><?= htmlspecialchars($log['reason']) ?></td>
                        <td>₱<?= number_format((float) $log['cost_impact'], 2) ?></td>
                        <td><?= date('M d g:i A', strtotime($log['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="staff-empty">No waste logged yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include '../views/footer.php'; ?>
