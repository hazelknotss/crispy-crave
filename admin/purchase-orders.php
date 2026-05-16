<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/kitchen_ops.php';

kk_kitchen_ensure_schema($pdo);
$shopId = kk_kitchen_require_shop_id();
$userId = (int) $_SESSION['user']['id'];

if (isset($_GET['generate']) && $_GET['generate'] === '1') {
    $poId = kk_kitchen_generate_po_from_low_stock($pdo, $shopId, $userId);
    header('Location: purchase-orders.php?shop_id=' . $shopId . ($poId ? '&created=' . $poId : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'receive') {
    $poId = (int) ($_POST['po_id'] ?? 0);
    $lines = $pdo->prepare('SELECT * FROM kitchen_purchase_order_items WHERE po_id = ?');
    $lines->execute([$poId]);
    foreach ($lines as $line) {
        $qty = (float) $line['qty_ordered'];
        kk_kitchen_adjust_stock($pdo, (int) $line['inventory_id'], $shopId, $qty, 'receive', null, 'PO #' . $poId);
        $pdo->prepare('UPDATE kitchen_purchase_order_items SET qty_received = qty_ordered WHERE id = ?')->execute([(int) $line['id']]);
    }
    $pdo->prepare("UPDATE kitchen_purchase_orders SET status = 'received' WHERE id = ? AND shop_id = ?")->execute([$poId, $shopId]);
    header('Location: purchase-orders.php?shop_id=' . $shopId);
    exit;
}

$pos = $pdo->prepare('SELECT * FROM kitchen_purchase_orders WHERE shop_id = ? ORDER BY id DESC LIMIT 20');
$pos->execute([$shopId]);
$pos = $pos->fetchAll(PDO::FETCH_ASSOC);

include '../views/header.php';
?>

<main class="staff-main">
    <header class="staff-page-head d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="staff-page-head__title">Purchase orders</h1>
            <p class="staff-page-head__sub">Replenishment based on usage and low-stock alerts</p>
        </div>
        <a href="?shop_id=<?= $shopId ?>&generate=1" class="staff-btn staff-btn--primary">Generate from low stock</a>
    </header>

    <?php if (!empty($_GET['created'])): ?>
        <div class="alert alert-success">Purchase order #<?= (int) $_GET['created'] ?> created.</div>
    <?php endif; ?>

    <section class="staff-panel">
        <div class="staff-panel__head">Recent POs</div>
        <div class="staff-panel__body staff-table-wrap">
            <table class="table align-middle mb-0">
                <thead><tr><th>PO #</th><th>Supplier</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($pos as $po): ?>
                    <tr>
                        <td><?= htmlspecialchars($po['po_number']) ?></td>
                        <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                        <td><span class="badge bg-secondary"><?= ucfirst($po['status']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($po['created_at'])) ?></td>
                        <td>
                            <?php if ($po['status'] === 'draft' || $po['status'] === 'sent'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="receive">
                                    <input type="hidden" name="po_id" value="<?= (int) $po['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Mark received</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pos)): ?>
                    <tr><td colspan="5" class="staff-empty">No purchase orders yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include '../views/footer.php'; ?>
