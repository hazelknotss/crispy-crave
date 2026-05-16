<?php
/**
 * Rider delivery status actions.
 *
 * Expects: $orderId (int), $deliveryStatus (string), optional $compact (bool), $redirectBack (string)
 */
$orderId = (int) ($orderId ?? 0);
$deliveryStatus = (string) ($deliveryStatus ?? 'assigned');
$compact = !empty($compact);
$redirectBack = (string) ($redirectBack ?? 'dashboard.php');
$isDelivered = $deliveryStatus === 'delivered';

if ($orderId < 1) {
    return;
}

if (!isset($pdo) && isset($GLOBALS['pdo'])) {
    $pdo = $GLOBALS['pdo'];
}
$deliveryProof = null;
if ($isDelivered && isset($pdo)) {
    require_once dirname(__DIR__) . '/app/delivery_proof.php';
    $deliveryProof = kk_delivery_proof_for_order($pdo, $orderId);
}

$nextStatus = match ($deliveryStatus) {
    'assigned'   => 'picked_up',
    'picked_up'  => 'on_the_way',
    'on_the_way' => 'delivered',
    default      => null,
};

$nextLabel = match ($deliveryStatus) {
    'assigned'   => $compact ? 'Picked up' : 'Picked up from restaurant',
    'picked_up'  => $compact ? 'On the way' : 'On the way to customer',
    'on_the_way' => 'Mark complete',
    default      => null,
};

$nextIcon = match ($deliveryStatus) {
    'assigned'   => 'bi-bag-check',
    'picked_up'  => 'bi-bicycle',
    'on_the_way' => 'bi-check-circle-fill',
    default      => null,
};

$completeUrl = 'complete-delivery.php?id=' . $orderId;
?>
<div class="rider-delivery-actions<?= $compact ? ' rider-delivery-actions--compact' : '' ?><?= $isDelivered ? ' rider-delivery-actions--done' : '' ?>">
    <?php if ($isDelivered): ?>
        <p class="rider-delivery-actions__done" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span>Order received by customer — delivery complete.</span>
        </p>
        <?php if ($deliveryProof !== null && !empty($deliveryProof['url'])): ?>
            <div class="rider-proof-display">
                <p class="rider-proof-display__label small fw-semibold text-muted mb-2">Proof of delivery</p>
                <a href="<?= htmlspecialchars($deliveryProof['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="rider-proof-display__link">
                    <img
                        src="<?= htmlspecialchars($deliveryProof['url'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="Proof of delivery for order #<?= $orderId ?>"
                        class="rider-proof-display__img"
                        loading="lazy"
                        decoding="async">
                </a>
                <?php if (!empty($deliveryProof['note'])): ?>
                    <p class="rider-proof-display__note small mb-0 mt-2"><?= htmlspecialchars($deliveryProof['note'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if (!empty($deliveryProof['at'])): ?>
                    <p class="rider-proof-display__time small text-muted mb-0 mt-1">
                        <?= date('M j, Y · g:i A', strtotime($deliveryProof['at'])) ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (!$compact): ?>
            <p class="rider-delivery-actions__hint">Update status as you go. When the customer receives the order, tap <strong>Mark complete</strong> and upload a delivery photo.</p>
            <ol class="rider-delivery-steps" aria-label="Delivery progress">
                <?php
                $steps = [
                    'assigned'   => 'Assigned',
                    'picked_up'  => 'Picked up',
                    'on_the_way' => 'On the way',
                    'delivered'  => 'Complete',
                ];
                $stepKeys = array_keys($steps);
                $currentIdx = array_search($deliveryStatus, $stepKeys, true);
                if ($currentIdx === false) {
                    $currentIdx = 0;
                }
                foreach ($steps as $key => $label):
                    $idx = array_search($key, $stepKeys, true);
                    $state = $idx < $currentIdx ? 'done' : ($idx === $currentIdx ? 'current' : 'upcoming');
                    ?>
                    <li class="rider-delivery-steps__item rider-delivery-steps__item--<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="rider-delivery-steps__dot" aria-hidden="true"></span>
                        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php if ($nextStatus !== null && $nextLabel !== null): ?>
            <?php if ($nextStatus === 'delivered'): ?>
                <a
                    href="<?= htmlspecialchars($completeUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="btn rider-delivery-actions__btn rider-delivery-actions__btn--complete">
                    <?php if ($nextIcon !== null): ?>
                        <i class="bi <?= htmlspecialchars($nextIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($nextLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php else: ?>
                <form method="post" action="update_delivery.php" class="rider-delivery-actions__form">
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                    <input type="hidden" name="delivery_status" value="<?= htmlspecialchars($nextStatus, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectBack, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn rider-delivery-actions__btn btn-dark">
                        <?php if ($nextIcon !== null): ?>
                            <i class="bi <?= htmlspecialchars($nextIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($nextLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$compact && $deliveryStatus !== 'on_the_way'): ?>
            <p class="rider-delivery-actions__skip mb-0 mt-2 text-center">
                <a href="<?= htmlspecialchars($completeUrl, ENT_QUOTES, 'UTF-8') ?>" class="rider-delivery-actions__skip-btn">
                    Mark complete with photo
                </a>
            </p>
        <?php endif; ?>

        <details class="rider-delivery-actions__more">
            <summary class="rider-delivery-actions__more-summary">Other status</summary>
            <form method="post" action="update_delivery.php" class="rider-delivery-actions__select-form" id="status-form-<?= $orderId ?>">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectBack, ENT_QUOTES, 'UTF-8') ?>">
                <label class="visually-hidden" for="status-select-<?= $orderId ?>">Set delivery status</label>
                <select
                    id="status-select-<?= $orderId ?>"
                    name="delivery_status"
                    class="form-select form-select-sm rider-status-select"
                    data-complete-url="<?= htmlspecialchars($completeUrl, ENT_QUOTES, 'UTF-8') ?>"
                    onchange="kkRiderStatusChange(this)">
                    <option value="assigned" <?= $deliveryStatus === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                    <option value="picked_up" <?= $deliveryStatus === 'picked_up' ? 'selected' : '' ?>>Picked up</option>
                    <option value="on_the_way" <?= $deliveryStatus === 'on_the_way' ? 'selected' : '' ?>>On the way</option>
                    <option value="delivered" <?= $deliveryStatus === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
            </form>
        </details>
    <?php endif; ?>
</div>
