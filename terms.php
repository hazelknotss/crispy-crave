<?php
require_once __DIR__ . '/db/database.php';
include __DIR__ . '/views/header.php';
?>

<main class="legal-page">
    <div class="container legal-page__inner">
        <header class="legal-page__intro">
            <p class="legal-page__kicker">Legal</p>
            <h1 class="legal-page__title">Terms of service</h1>
            <p class="legal-page__updated text-muted small mb-0">Last updated: <?= date('F j, Y') ?></p>
        </header>

        <div class="legal-page__surface">
            <p>By using Crispy Crave to browse menus and place orders, you agree to these terms. If you do not agree, please do not use the service.</p>

            <h2 class="h6 fw-semibold mt-4">Ordering</h2>
            <ul>
                <li>Each cart is limited to <strong>one kitchen</strong> at a time.</li>
                <li>Prices, availability, and delivery times are set by each partner kitchen and may change.</li>
                <li>Placing an order is an offer to purchase; the kitchen confirms fulfillment when your order is accepted and prepared.</li>
            </ul>

            <h2 class="h6 fw-semibold mt-4">Accounts</h2>
            <p>You are responsible for keeping your login details secure and for activity under your account. Provide accurate contact and delivery information so we can reach you about your order.</p>

            <h2 class="h6 fw-semibold mt-4">Payments</h2>
            <p>Payment options shown at checkout (such as cash on delivery or bank transfer) must be completed as selected. Unpaid or fraudulent orders may be cancelled.</p>

            <h2 class="h6 fw-semibold mt-4">Cancellations &amp; issues</h2>
            <p>Contact us promptly if there is a problem with your order. Refunds or replacements are handled according to each kitchen’s policy and applicable law.</p>

            <h2 class="h6 fw-semibold mt-4">Limitation</h2>
            <p class="mb-0">Crispy Crave provides the ordering platform. Food preparation and delivery are the responsibility of the kitchen you order from. The service is provided “as is” to the extent permitted by law.</p>
        </div>

        <p class="legal-page__back mt-4 mb-0">
            <a href="<?= htmlspecialchars(app_url('index.php')) ?>" class="fw-semibold text-decoration-none">← Back to home</a>
        </p>
    </div>
</main>

<?php include __DIR__ . '/views/footer.php'; ?>
