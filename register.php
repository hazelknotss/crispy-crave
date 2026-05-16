<?php
session_start();
require 'db/database.php';

function kk_register_json(bool $ok, array $payload = []): void
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
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $error = 'Email already registered.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password, role, approval_status)
            VALUES (?, ?, ?, 'user', 'approved')"
        );
        $stmt->execute([$name, $email, $hashedPassword]);

        if ($ajax) {
            kk_register_json(true, [
                'registered' => true,
                'message' => 'Account created. Please sign in.',
            ]);
        }
        header('Location: ' . app_url('login.php'));
        exit;
    }

    if ($ajax) {
        kk_register_json(false, ['error' => $error]);
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register — Crispy Crave</title>
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
            <h4 class="fw-bold">Create an account</h4>
            <p class="text-muted small">
                Register to start ordering from local kitchens.
            </p>
        </div>
        
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger text-center">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold" for="auth-reg-name">Full name</label>
                <input type="text" id="auth-reg-name" name="name" class="form-control" required autocomplete="name">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" for="auth-reg-email">Email address</label>
                <input type="email" id="auth-reg-email" name="email" class="form-control" required autocomplete="email">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold" for="auth-reg-password">Password</label>
                <div class="input-group auth-password-group">
                    <input type="password" id="auth-reg-password" name="password" class="form-control" required minlength="6" autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary auth-toggle-pw" aria-controls="auth-reg-password" aria-label="Show password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                        <span class="visually-hidden auth-toggle-pw__sr">Show password</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                Register
            </button>
        </form>

        <div class="text-center mt-3">
            <small>
                Already have an account?
                <a href="<?= htmlspecialchars(app_url('login.php')) ?>" class="text-decoration-none fw-semibold">Log in</a>
            </small>
        </div>
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
