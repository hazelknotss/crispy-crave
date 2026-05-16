<?php
require_once __DIR__ . '/db/database.php';
include __DIR__ . '/views/header.php';
?>

<main class="legal-page">
    <div class="container legal-page__inner">
        <header class="legal-page__intro">
            <p class="legal-page__kicker">Legal</p>
            <h1 class="legal-page__title">Privacy policy</h1>
            <p class="legal-page__updated text-muted small mb-0">Last updated: <?= date('F j, Y') ?></p>
        </header>

        <div class="legal-page__surface">
            <p>Crispy Crave (“we”, “us”) runs this ordering site for local partner kitchens. This policy explains what we collect and how we use it.</p>

            <h2 class="h6 fw-semibold mt-4">Information we collect</h2>
            <ul>
                <li><strong>Account details</strong> — name, email, and password (stored securely) when you register.</li>
                <li><strong>Order details</strong> — items, shop, delivery or pickup preferences, address or notes, and payment method you choose at checkout.</li>
                <li><strong>Technical data</strong> — basic logs such as browser type and pages visited, used to keep the service reliable.</li>
            </ul>

            <h2 class="h6 fw-semibold mt-4">How we use it</h2>
            <ul>
                <li>To process and fulfill your orders with the kitchen you selected.</li>
                <li>To show your order history in “My orders”.</li>
                <li>To improve the site and respond to support requests.</li>
            </ul>

            <h2 class="h6 fw-semibold mt-4">Sharing</h2>
            <p>We share order information only with the restaurant fulfilling your order and as needed to operate payments or delivery. We do not sell your personal data.</p>

            <h2 class="h6 fw-semibold mt-4">Your choices</h2>
            <p>You can update account details by contacting us. You may request deletion of your account using the phone number on our site.</p>

            <h2 class="h6 fw-semibold mt-4">Contact</h2>
            <p class="mb-0">Questions about privacy? Call <a href="tel:+639389762763">09389762763</a> during business hours (10AM – 10PM).</p>
        </div>

        <p class="legal-page__back mt-4 mb-0">
            <a href="<?= htmlspecialchars(app_url('index.php')) ?>" class="fw-semibold text-decoration-none">← Back to home</a>
        </p>
    </div>
</main>

<?php include __DIR__ . '/views/footer.php'; ?>
