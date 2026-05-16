<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/kitchen_ops.php';

kk_kitchen_ensure_schema($pdo);
$shopId = kk_kitchen_require_shop_id();
kk_kitchen_seed_inventory_if_empty($pdo, $shopId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'adjust') {
    $invId = (int) ($_POST['inventory_id'] ?? 0);
    $delta = (float) ($_POST['delta'] ?? 0);
    $note = trim((string) ($_POST['note'] ?? ''));
    if ($invId > 0 && $delta != 0) {
        kk_kitchen_adjust_stock($pdo, $invId, $shopId, $delta, 'adjustment', null, $note ?: 'Manual adjustment');
    }
    header('Location: inventory.php?shop_id=' . $shopId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_item') {
    $sku = trim((string) ($_POST['sku'] ?? ''));
    $name = trim((string) ($_POST['name'] ?? ''));
    $unit = trim((string) ($_POST['unit'] ?? 'pcs'));
    $reorder = (float) ($_POST['reorder_level'] ?? 0);
    $cost = (float) ($_POST['cost_per_unit'] ?? 0);
    $supplier = trim((string) ($_POST['supplier_name'] ?? ''));
    if ($sku !== '' && $name !== '') {
        $pdo->prepare('INSERT INTO kitchen_inventory (shop_id, sku, name, unit, qty_on_hand, reorder_level, cost_per_unit, supplier_name) VALUES (?,?,?,?,0,?,?,?)')
            ->execute([$shopId, $sku, $name, $unit, $reorder, $cost, $supplier]);
    }
    header('Location: inventory.php?shop_id=' . $shopId);
    exit;
}

$items = $pdo->prepare('SELECT * FROM kitchen_inventory WHERE shop_id = ? ORDER BY name');
$items->execute([$shopId]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
$lowStock = kk_kitchen_low_stock($pdo, $shopId);

include '../views/header.php';
?>

<main class="staff-main">
    <header class="staff-page-head d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="staff-page-head__title">Inventory</h1>
            <p class="staff-page-head__sub">Real-time stock · auto-deduct when orders complete (with recipes)</p>
        </div>
        <a href="purchase-orders.php?shop_id=<?= $shopId ?>" class="staff-btn staff-btn--secondary">Purchase orders</a>
    </header>

    <?php if (!empty($lowStock)): ?>
        <div class="alert alert-warning">
            <strong><?= count($lowStock) ?> low-stock item(s).</strong>
            <a href="purchase-orders.php?shop_id=<?= $shopId ?>&generate=1">Generate replenishment PO</a>
        </div>
    <?php endif; ?>

    <section class="staff-panel mb-4">
        <div class="staff-panel__head">Add ingredient</div>
        <div class="staff-panel__body--padded">
            <form method="post" class="row g-2 align-items-end">
                <input type="hidden" name="action" value="save_item">
                <div class="col-md-2"><input name="sku" class="form-control form-control-sm" placeholder="SKU" required></div>
                <div class="col-md-3"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
                <div class="col-md-1"><input name="unit" class="form-control form-control-sm" value="pcs"></div>
                <div class="col-md-2"><input name="reorder_level" type="number" step="0.01" class="form-control form-control-sm" placeholder="Reorder"></div>
                <div class="col-md-2"><input name="cost_per_unit" type="number" step="0.01" class="form-control form-control-sm" placeholder="Cost"></div>
                <div class="col-md-2"><button type="submit" class="staff-btn staff-btn--primary btn-sm w-100">Add</button></div>
            </form>
        </div>
    </section>

    <section class="staff-panel">
        <div class="staff-panel__head">Stock on hand</div>
        <div class="staff-panel__body staff-table-wrap">
            <table class="table align-middle mb-0">
                <thead><tr><th>SKU</th><th>Item</th><th>On hand</th><th>Reorder</th><th>Adjust</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php $low = (float) $it['qty_on_hand'] <= (float) $it['reorder_level'] && (float) $it['reorder_level'] > 0; ?>
                    <tr class="<?= $low ? 'table-warning' : '' ?>">
                        <td><?= htmlspecialchars($it['sku']) ?></td>
                        <td><?= htmlspecialchars($it['name']) ?> (<?= htmlspecialchars($it['unit']) ?>)</td>
                        <td><strong><?= number_format((float) $it['qty_on_hand'], 2) ?></strong></td>
                        <td><?= number_format((float) $it['reorder_level'], 2) ?></td>
                        <td>
                            <form method="post" class="d-flex gap-1">
                                <input type="hidden" name="action" value="adjust">
                                <input type="hidden" name="inventory_id" value="<?= (int) $it['id'] ?>">
                                <input type="number" step="0.01" name="delta" class="form-control form-control-sm" style="width:5rem" required>
                                <button type="submit" class="btn btn-sm btn-outline-dark">Apply</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include '../views/footer.php'; ?>
