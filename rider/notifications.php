<?php
require '../auth/auth.php';
require '../db/database.php';
require_once __DIR__ . '/../app/rider_portal.php';

requireRider();
kk_rider_ensure_schema($pdo);
$riderId = (int) $_SESSION['user']['id'];

if (isset($_GET['read']) && $_GET['read'] === 'all') {
    $mark = $pdo->prepare('UPDATE rider_notifications SET is_read = 1 WHERE user_id = ?');
    $mark->execute([$riderId]);
    header('Location: notifications.php');
    exit;
}

$items = kk_rider_notifications($pdo, $riderId);
$kkRiderNavActive = 'notifications';
$riderPageTitle = 'Notifications';
require '../views/rider-layout-head.php';
?>

<main class="rider-dash-page">
    <div class="container-fluid rider-dash-page__inner">
        <header class="rider-dash-hero">
            <div class="rider-dash-hero__copy">
                <p class="rider-dash-header__kicker">Communication</p>
                <h1 class="rider-dash-header__title">Notifications</h1>
                <p class="rider-dash-header__lede">Alerts for new orders, schedule changes, and account updates.</p>
            </div>
            <?php if ($items !== []): ?>
                <a href="?read=all" class="btn btn-sm btn-outline-dark">Mark all read</a>
            <?php endif; ?>
        </header>

        <div class="rider-dash-surface">
            <ul class="list-group list-group-flush">
                <?php if ($items === []): ?>
                    <li class="list-group-item text-muted py-4 text-center">No notifications yet.</li>
                <?php else: ?>
                    <?php foreach ($items as $n): ?>
                        <li class="list-group-item<?= empty($n['is_read']) ? ' bg-warning-subtle' : '' ?>">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <p class="fw-semibold mb-1"><?= htmlspecialchars((string) $n['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="small mb-1"><?= htmlspecialchars((string) $n['message'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="small text-muted mb-0"><?= date('M j · g:i A', strtotime((string) $n['created_at'])) ?></p>
                                </div>
                                <?php if (!empty($n['link_url'])): ?>
                                    <a href="<?= htmlspecialchars(app_url((string) $n['link_url']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-dark align-self-start">Open</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</main>

<?php require '../views/rider-layout-foot.php'; ?>
