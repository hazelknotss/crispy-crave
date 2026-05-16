<?php
require_once __DIR__ . '/db/database.php';
include __DIR__ . '/views/header.php';
?>

<section class="kk-forgot-page py-5">
    <div class="container kk-forgot-page__inner">
        <h1 class="h4 mb-3">Forgot password</h1>
        <p class="text-muted mb-4">
            Password reset by email is not available on this demo yet. If you need help accessing your account,
            please contact Crispy Crave support using the phone number in the header, or create a new account with a different email.
        </p>
        <p class="mb-0">
            <a href="<?= htmlspecialchars(app_url('index.php')) ?>" class="fw-semibold text-decoration-none">← Back to home</a>
        </p>
    </div>
</section>

<?php include __DIR__ . '/views/footer.php'; ?>
