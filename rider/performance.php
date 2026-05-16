<?php
require '../auth/auth.php';
require '../db/database.php';
require_once __DIR__ . '/../app/rider_portal.php';

requireRider();
$riderId = (int) $_SESSION['user']['id'];
$stats = kk_rider_performance_stats($pdo, $riderId);
$shifts = kk_rider_shifts($pdo, $riderId);
$kkRiderNavActive = 'performance';
$riderPageTitle = 'Performance';
require '../views/rider-layout-head.php';
$completionRate = $stats['total'] > 0 ? round(100 * $stats['delivered'] / $stats['total']) : 0;
?>

<main class="rider-dash-page">
    <div class="container-fluid rider-dash-page__inner">
        <header class="rider-dash-hero">
            <div class="rider-dash-hero__copy">
                <p class="rider-dash-header__kicker">Analytics</p>
                <h1 class="rider-dash-header__title">Performance</h1>
                <p class="rider-dash-header__lede">KPIs: deliveries completed, active load, and completion rate.</p>
            </div>
        </header>

        <div class="rider-dash-stats mb-4">
            <div class="rider-dash-stat"><span class="rider-dash-stat__value"><?= (int) $stats['total'] ?></span><span class="rider-dash-stat__label">Assigned</span></div>
            <div class="rider-dash-stat rider-dash-stat--active"><span class="rider-dash-stat__value"><?= (int) $stats['active'] ?></span><span class="rider-dash-stat__label">Active</span></div>
            <div class="rider-dash-stat rider-dash-stat--done"><span class="rider-dash-stat__value"><?= (int) $stats['delivered'] ?></span><span class="rider-dash-stat__label">Delivered</span></div>
            <div class="rider-dash-stat"><span class="rider-dash-stat__value"><?= $completionRate ?>%</span><span class="rider-dash-stat__label">Completion</span></div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="rider-dash-surface p-3 p-md-4">
                    <h2 class="h6 fw-bold">Efficiency</h2>
                    <p class="small text-muted mb-2">Average rider fee per completed trip</p>
                    <p class="fs-4 fw-bold mb-0 tabular-nums">₱<?= $stats['delivered'] > 0 ? number_format($stats['earnings'] / $stats['delivered'], 2) : '0.00' ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="rider-dash-surface p-3 p-md-4">
                    <h2 class="h6 fw-bold">Upcoming shifts</h2>
                    <?php if ($shifts === []): ?>
                        <p class="small text-muted mb-0">No shifts scheduled. Contact your kitchen manager.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($shifts as $s): ?>
                                <li class="list-group-item px-0 small">
                                    <strong><?= htmlspecialchars((string) $s['shift_date'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?= htmlspecialchars(substr((string) $s['start_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?>–<?= htmlspecialchars(substr((string) $s['end_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars((string) $s['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require '../views/rider-layout-foot.php'; ?>
