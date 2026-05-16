<?php
session_start();
require 'db/database.php';

function kk_auth_json(bool $ok, array $payload = []): void
{
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE);
    exit;
}

$ajax = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && ((string) ($_POST['ajax'] ?? '') === '1'
        || (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (($user['role'] ?? '') === 'rider') {
            $error = 'This sign-in is for customers. Riders use the rider portal.';
        } elseif (($user['role'] ?? '') === 'admin' || ($user['role'] ?? '') === 'restaurant') {
            $error = 'Staff accounts must sign in at the staff portal.';
        } elseif (
            $user['role'] !== 'user' &&
            $user['role'] !== 'admin' &&
            $user['approval_status'] !== 'approved'
        ) {
            $error = 'Your account is waiting for admin approval.';
        } else {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
                'restaurant_id' => isset($user['restaurant_id']) ? (int) $user['restaurant_id'] : null,
            ];

            $redirect = app_url('index.php');

            if ($ajax) {
                kk_auth_json(true, ['redirect' => $redirect]);
            }
            header('Location: ' . $redirect);
            exit;
        }
    } else {
        $error = 'Invalid email or password.';
    }

    if ($ajax) {
        kk_auth_json(false, ['error' => $error]);
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login — Crispy Crave</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require __DIR__ . '/views/pwa-head.php'; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('css/register.css')) ?>">
</head>
<body>

<div class="auth-bg"></div>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= htmlspecialchars(app_url('index.php')) ?>">
            <img src="<?= htmlspecialchars(app_brand_logo_url()) ?>" alt="Crispy Crave" class="me-2">
            <strong>Crispy Crave</strong>
        </a>

        <div class="ms-auto">
            <a href="<?= htmlspecialchars(app_url('index.php')) ?>" class="btn btn-outline-light btn-sm">Home</a>
        </div>
    </div>
</nav>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow auth-card p-4">
        <div class="text-center mb-4">
            <img src="<?= htmlspecialchars(app_brand_logo_url()) ?>" class="auth-logo mb-3" alt="Crispy Crave">
            <h4 class="fw-bold">Welcome back</h4>
            <p class="text-muted small">
                Sign in to continue ordering.
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold" for="auth-login-email">Email address</label>
                <input type="email" id="auth-login-email" name="email" class="form-control" required autocomplete="email">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold" for="auth-login-password">Password</label>
                <div class="input-group auth-password-group">
                    <input type="password" id="auth-login-password" name="password" class="form-control" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary auth-toggle-pw" aria-controls="auth-login-password" aria-label="Show password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                        <span class="visually-hidden auth-toggle-pw__sr">Show password</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                Log in
            </button>
        </form>

        <div class="text-center mt-3">
            <small>
                New here?
                <a href="<?= htmlspecialchars(app_url('register.php')) ?>" class="text-decoration-none fw-semibold">Create an account</a>
            </small>
        </div>
        <p class="text-center mt-2 mb-0 small text-muted">
            Delivery rider?
            <a href="<?= htmlspecialchars(app_url('rider/login.php')) ?>" class="text-decoration-none fw-semibold">Rider portal</a>
            ·
            <a href="<?= htmlspecialchars(app_url('admin/login.php')) ?>" class="text-decoration-none fw-semibold">Staff</a>
        </p>
    </div>
</div>

<footer></footer>
<script>
(function () {
    document.querySelectorAll('.auth-toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cid = btn.getAttribute('aria-controls');
            var inp = cid ? document.getElementById(cid) : null;
            if (!inp) return;
            var show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            var ic = btn.querySelector('i');
            if (ic) ic.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            var sr = btn.querySelector('.auth-toggle-pw__sr');
            if (sr) sr.textContent = show ? 'Hide password' : 'Show password';
        });
    });
})();
</script>
<?php require __DIR__ . '/views/pwa-script.php'; ?>
</body>
</html>
