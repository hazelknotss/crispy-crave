<?php
require '../auth/auth.php';
require '../db/database.php';
require_once __DIR__ . '/../app/rider_assign.php';
require_once __DIR__ . '/../app/delivery_proof.php';
require __DIR__ . '/_status.php';

requireRider();

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$rider_id = (int) $_SESSION['user']['id'];
$restaurantId = isset($_SESSION['user']['restaurant_id']) ? (int) $_SESSION['user']['restaurant_id'] : null;
if ($restaurantId !== null && $restaurantId < 1) {
    $restaurantId = null;
}

if ($order_id < 1) {
    header('Location: dashboard.php');
    exit;
}

$order = kk_rider_get_order($pdo, $order_id, $rider_id, $restaurantId);
if (!$order) {
    header('Location: dashboard.php');
    exit;
}

$deliveryStatus = (string) ($order['delivery_status'] ?? 'assigned');
if ($deliveryStatus === 'delivered') {
    header('Location: order-details.php?id=' . $order_id);
    exit;
}

$customerStmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
$customerStmt->execute([(int) $order['user_id']]);
$customerName = $customerStmt->fetchColumn();
$customerName = $customerName !== false ? (string) $customerName : 'Customer';

$error = isset($_GET['error']) ? urldecode((string) $_GET['error']) : '';

$kkRiderNavActive = 'deliveries';
$riderPageTitle = 'Complete delivery';
require '../views/rider-layout-head.php';
?>

<main class="rider-dash-page">
    <div class="container-fluid rider-dash-page__inner rider-complete-delivery">
        <header class="rider-dash-header mb-3">
            <a href="order-details.php?id=<?= $order_id ?>" class="rider-login-panel__back d-inline-flex">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>Back to order</span>
            </a>
            <p class="rider-dash-header__kicker mt-3">Proof of delivery</p>
            <h1 class="rider-dash-header__title">Order #<?= $order_id ?></h1>
            <p class="rider-dash-header__lede">Confirm the customer received the order and upload a photo.</p>
        </header>

        <div class="rider-dash-surface p-3 p-md-4 mb-3">
            <dl class="rider-dash-detail-grid mb-0">
                <dt>Customer</dt>
                <dd class="fw-medium"><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Address</dt>
                <dd class="small"><?= htmlspecialchars((string) $order['delivery_address'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Total</dt>
                <dd class="tabular-nums fw-bold">₱<?= number_format((float) $order['total'], 2) ?></dd>
            </dl>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form
            method="post"
            action="update_delivery.php"
            enctype="multipart/form-data"
            class="rider-dash-surface p-3 p-md-4 rider-proof-form"
            id="proofForm">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">
            <input type="hidden" name="delivery_status" value="delivered">
            <input type="hidden" name="redirect" value="order-details.php?id=<?= $order_id ?>">

            <div class="rider-proof-photo">
                <label class="rider-proof-photo__label" for="delivery_proof">
                    <span class="rider-proof-photo__icon" aria-hidden="true"><i class="bi bi-camera"></i></span>
                    <span class="rider-proof-photo__text">
                        <strong>Delivery photo</strong>
                        <span class="d-block small text-muted">Package at door, handoff, or receipt — required</span>
                    </span>
                </label>
                <input
                    type="file"
                    id="delivery_proof"
                    name="delivery_proof"
                    class="rider-proof-photo__input"
                    accept="image/jpeg,image/png,image/webp"
                    capture="environment"
                    required>
                <img src="" alt="" class="rider-proof-photo__preview" id="proofPreview" hidden>
            </div>

            <div class="mb-3">
                <label for="delivery_proof_note" class="form-label small fw-semibold">Note (optional)</label>
                <textarea
                    id="delivery_proof_note"
                    name="delivery_proof_note"
                    class="form-control"
                    rows="2"
                    maxlength="500"
                    placeholder="e.g. Left with guard, handed to customer"></textarea>
            </div>

            <button type="submit" class="btn rider-delivery-actions__btn rider-delivery-actions__btn--complete w-100">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>Submit &amp; mark complete</span>
            </button>
        </form>
    </div>
</main>

<script>
(function () {
    var input = document.getElementById('delivery_proof');
    var preview = document.getElementById('proofPreview');
    var form = document.getElementById('proofForm');
    if (!input || !preview) return;
    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) {
            preview.hidden = true;
            preview.removeAttribute('src');
            return;
        }
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
    });
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!input.files || !input.files.length) {
                e.preventDefault();
                alert('Please add a delivery photo.');
                return;
            }
            if (!confirm('Confirm the customer received this order?')) {
                e.preventDefault();
            }
        });
    }
})();
</script>

<?php require '../views/rider-layout-foot.php'; ?>
