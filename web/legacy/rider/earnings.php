<?php
require '../auth/auth.php';
require '../db/database.php';
require_once __DIR__ . '/../app/rider_portal.php';

requireRider();
$riderId = (int) $_SESSION['user']['id'];
$stats = kk_rider_performance_stats($pdo, $riderId);
$rows = kk_rider_earnings_rows($pdo, $riderId);
$kkRiderNavActive = 'earnings';
$riderPageTitle = 'Earnings';
require '../views/rider-layout-head.php';
?>

<main class="rider-dash-page">
    <div class="container-fluid rider-dash-page__inner">
        <header class="rider-dash-hero">
            <div class="rider-dash-hero__copy">
                <p class="rider-dash-header__kicker">Financials</p>
                <h1 class="rider-dash-header__title">Earnings</h1>
                <p class="rider-dash-header__lede">Completed trips, rider fees, and delivery incentives.</p>
            </div>
            <div class="rider-dash-stats">
                <div class="rider-dash-stat rider-dash-stat--done">
                    <span class="rider-dash-stat__value tabular-nums">₱<?= number_format($stats['earnings'], 2) ?></span>
                    <span class="rider-dash-stat__label">Total earned</span>
                </div>
                <div class="rider-dash-stat">
                    <span class="rider-dash-stat__value tabular-nums"><?= (int) $stats['delivered'] ?></span>
                    <span class="rider-dash-stat__label">Trips</span>
                </div>
            </div>
        </header>

        <div class="rider-dash-surface">
            <div class="table-responsive">
                <table class="table rider-dash-table mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Barangay</th>
                            <th class="text-end">Rider fee</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($rows === []): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No completed deliveries yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><a href="order-details.php?id=<?= (int) $row['id'] ?>">#<?= (int) $row['id'] ?></a></td>
                                <td class="small text-muted"><?= date('M j, Y', strtotime((string) $row['created_at'])) ?></td>
                                <td><?= htmlspecialchars((string) $row['barangay'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end tabular-nums fw-semibold">₱<?= number_format((float) $row['rider_fee'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require '../views/rider-layout-foot.php'; ?>
