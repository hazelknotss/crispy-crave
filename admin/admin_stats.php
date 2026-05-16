<?php
require '../auth/auth.php';
requirePlatformAdmin();
require '../db/database.php';

$totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

$totalRevenue = (float) $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM orders
    WHERE order_status = 'completed'
      AND payment_status = 'paid'
")->fetchColumn();

$pending = (int) $pdo->query("
    SELECT COUNT(*) FROM orders WHERE order_status = 'pending'
")->fetchColumn();

$preparing = (int) $pdo->query("
    SELECT COUNT(*) FROM orders WHERE order_status = 'preparing'
")->fetchColumn();

$delivered = (int) $pdo->query("
    SELECT COUNT(*) FROM orders WHERE order_status = 'completed'
")->fetchColumn();

$cancelled = (int) $pdo->query("
    SELECT COUNT(*) FROM orders WHERE order_status = 'cancelled'
")->fetchColumn();

$totalCustomers = (int) $pdo->query("
    SELECT COUNT(*) FROM users WHERE role = 'user'
")->fetchColumn();

$todaySales = (float) $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM orders
    WHERE DATE(created_at) = CURDATE()
      AND order_status = 'completed'
      AND payment_status = 'paid'
")->fetchColumn();

$metrics = [
    [
        'label' => 'Total orders',
        'value' => (string) $totalOrders,
        'icon' => 'bi-bag-check',
        'tone' => 'neutral',
    ],
    [
        'label' => 'Pending',
        'value' => (string) $pending,
        'icon' => 'bi-hourglass-split',
        'tone' => 'amber',
    ],
    [
        'label' => 'Preparing',
        'value' => (string) $preparing,
        'icon' => 'bi-fire',
        'tone' => 'sky',
    ],
    [
        'label' => 'Delivered',
        'value' => (string) $delivered,
        'icon' => 'bi-check2-circle',
        'tone' => 'blue',
    ],
    [
        'label' => 'Cancelled',
        'value' => (string) $cancelled,
        'icon' => 'bi-x-circle',
        'tone' => 'rose',
    ],
    [
        'label' => 'Customers',
        'value' => (string) $totalCustomers,
        'icon' => 'bi-people',
        'tone' => 'neutral',
    ],
];
?>

<?php include '../views/header.php'; ?>

<main class="staff-main staff-stats-page">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title">Statistics</h1>
        <p class="staff-page-head__sub">Platform overview at a glance</p>
    </header>

    <section class="staff-stats-hero" aria-label="Key metrics">
        <article class="staff-stats-hero__card">
            <div class="staff-stats-hero__meta">
                <span class="staff-stats-hero__icon" aria-hidden="true"><i class="bi bi-currency-exchange"></i></span>
                <p class="staff-stats-hero__label">Total revenue</p>
            </div>
            <p class="staff-stats-hero__value">₱<?= number_format($totalRevenue, 2) ?></p>
            <p class="staff-stats-hero__hint">Completed &amp; paid orders</p>
        </article>
        <article class="staff-stats-hero__card">
            <div class="staff-stats-hero__meta">
                <span class="staff-stats-hero__icon" aria-hidden="true"><i class="bi bi-calendar-day"></i></span>
                <p class="staff-stats-hero__label">Today&rsquo;s sales</p>
            </div>
            <p class="staff-stats-hero__value">₱<?= number_format($todaySales, 2) ?></p>
            <p class="staff-stats-hero__hint"><?= htmlspecialchars(date('l, M j')) ?></p>
        </article>
    </section>

    <section class="staff-stats-section" aria-label="Order and customer metrics">
        <h2 class="staff-stats-section__title">Orders &amp; customers</h2>
        <div class="staff-metrics-grid">
            <?php foreach ($metrics as $m): ?>
                <article class="staff-metric staff-metric--<?= htmlspecialchars($m['tone'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="staff-metric__icon" aria-hidden="true"><i class="bi <?= htmlspecialchars($m['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
                    <p class="staff-metric__label"><?= htmlspecialchars($m['label']) ?></p>
                    <p class="staff-metric__value"><?= htmlspecialchars($m['value']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php include '../views/footer.php'; ?>
