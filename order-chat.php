<?php
session_start();
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/app/order_messages.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php?redirect=' . urlencode('order-chat.php?order_id=' . (int) ($_GET['order_id'] ?? 0)));
    exit;
}

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$access = kk_order_chat_access($pdo, $orderId, $_SESSION['user']);

if (!$access) {
    if (($_SESSION['user']['role'] ?? '') === 'rider') {
        header('Location: ' . app_url('rider/dashboard.php'));
    } else {
        header('Location: ' . app_url('my-orders.php'));
    }
    exit;
}

$role = $access['role'];
$userId = (int) $_SESSION['user']['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = (string) ($_POST['body'] ?? '');
    if (!kk_order_chat_send($pdo, $orderId, $userId, $role, $body)) {
        $error = 'Message could not be sent. Keep it under 2000 characters.';
    } else {
        header('Location: order-chat.php?order_id=' . $orderId);
        exit;
    }
}

kk_order_chat_mark_read($pdo, $orderId, $role);
$messages = kk_order_chat_fetch($pdo, $orderId);

$backUrl = $role === 'rider'
    ? app_url('rider/order-details.php?id=' . $orderId)
    : app_url('my-orders.php');
$isRiderUi = $role === 'rider';

if ($isRiderUi) {
    require_once __DIR__ . '/app/rider_portal.php';
    kk_rider_ensure_schema($pdo);
    $kkRiderNavActive = 'deliveries';
    $riderPageTitle = 'Order #' . $orderId . ' chat';
    $riderBodyClass = 'order-chat-page order-chat-page--rider';
    $riderExtraCss = [app_url('css/order-chat.css')];
    require __DIR__ . '/views/rider-layout-head.php';
} else {
    $kkBodyClass = 'order-chat-page order-chat-page--customer';
    $kkExtraCss = [app_url('css/order-chat.css')];
    include __DIR__ . '/views/header.php';
}
?>

<div class="order-chat-shell">
<main class="order-chat" id="orderChat">
    <header class="order-chat__head">
        <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="order-chat__back">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
        <div class="order-chat__head-text">
            <p class="order-chat__kicker">Order #<?= (int) $orderId ?></p>
            <h1 class="order-chat__title"><?= htmlspecialchars($access['other_name'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="order-chat__sub"><?= htmlspecialchars($access['other_label'], ENT_QUOTES, 'UTF-8') ?> · delivery updates only</p>
        </div>
    </header>

    <div class="order-chat__thread" id="chatThread">
        <?php if ($messages === []): ?>
            <p class="order-chat__empty">No messages yet. Say hi or share delivery instructions.</p>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php $mine = (int) $msg['sender_user_id'] === $userId; ?>
                <div class="order-chat__bubble-wrap<?= $mine ? ' order-chat__bubble-wrap--mine' : '' ?>">
                    <div class="order-chat__bubble<?= $mine ? ' order-chat__bubble--mine' : '' ?>">
                        <?php if (!$mine): ?>
                            <span class="order-chat__sender"><?= htmlspecialchars((string) $msg['sender_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <p class="order-chat__body"><?= nl2br(htmlspecialchars((string) $msg['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <time class="order-chat__time" datetime="<?= htmlspecialchars((string) $msg['created_at'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= date('g:i A', strtotime((string) $msg['created_at'])) ?>
                        </time>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer class="order-chat__composer">
        <?php if ($error !== ''): ?>
            <p class="order-chat__error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form method="post" class="order-chat__form">
            <label class="visually-hidden" for="chatBody">Message</label>
            <textarea id="chatBody" name="body" class="order-chat__input" rows="1" placeholder="Type a message…" required maxlength="2000"></textarea>
            <button type="submit" class="order-chat__send" aria-label="Send message">
                <i class="bi bi-send-fill" aria-hidden="true"></i>
            </button>
        </form>
    </footer>
</main>
</div>

<script>
(function () {
    var thread = document.getElementById('chatThread');
    if (thread) thread.scrollTop = thread.scrollHeight;
    var ta = document.getElementById('chatBody');
    if (ta) {
        ta.addEventListener('input', function () {
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
        });
    }
})();
</script>

<?php
if ($isRiderUi) {
    require __DIR__ . '/views/rider-layout-foot.php';
} else {
    include __DIR__ . '/views/footer.php';
}
