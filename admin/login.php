<?php
session_start();
require_once __DIR__ . '/../db/database.php';
require_once __DIR__ . '/../app/staff.php';

$error = '';
$emailPrefill = '';

if (kk_is_staff()) {
    header('Location: dashboard.php');
    exit;
}

if (isset($_SESSION['user']) && !kk_is_staff()) {
    $_SESSION = [];
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $error = 'Signed out of your other account. Use your staff credentials below.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailPrefill = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'restaurant')");
    $stmt->execute([$emailPrefill]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = 'No staff account found for this email.';
    } elseif (!password_verify($password, $user['password'])) {
        $error = 'Incorrect password.';
    } elseif (($user['role'] ?? '') === 'restaurant' && empty($user['restaurant_id'])) {
        $error = 'This kitchen account is not linked to a shop yet. Contact platform admin.';
    } else {
        $_SESSION['user'] = [
            'id'            => (int) $user['id'],
            'name'          => $user['name'],
            'role'          => $user['role'],
            'restaurant_id' => isset($user['restaurant_id']) ? (int) $user['restaurant_id'] : null,
        ];
        header('Location: dashboard.php');
        exit;
    }
}

$bgPath = app_project_root() . '/images/kitchen.jpg';
$bgImage = app_url('images/kitchen.jpg');
if (is_file($bgPath)) {
    $bgImage .= '?v=' . (string) filemtime($bgPath);
}
$logoUrl = app_brand_logo_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff sign in — Crispy Crave</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('css/rider-portal.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('css/admin-portal.css')) ?>">
</head>
<body class="rider-login-page admin-login-page">
    <div class="rider-login-shell">
        <aside class="rider-login-visual admin-login-visual" style="--rider-login-bg: url('<?= htmlspecialchars($bgImage, ENT_QUOTES, 'UTF-8') ?>')">
            <div class="rider-login-visual__inner">
                <div class="rider-login-visual__logo-wrap" aria-hidden="true">
                    <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="rider-login-visual__logo" width="52" height="52" decoding="async">
                </div>
                <p class="rider-login-visual__brand">Crispy Crave</p>
                <h1 class="rider-login-visual__title">Staff portal</h1>
                <p class="rider-login-visual__text">Platform admins and kitchen managers — orders, menus, riders, and shops.</p>
            </div>
        </aside>

        <main class="rider-login-panel">
            <div class="rider-login-panel__card">
                <div class="rider-login-panel__inner">
                    <a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="rider-login-panel__back">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        <span>Back to store</span>
                    </a>

                    <div class="rider-login-panel__head">
                        <span class="rider-login-panel__badge admin-login-badge"><i class="bi bi-shield-lock" aria-hidden="true"></i> Staff only</span>
                        <h2 class="rider-login-panel__title">Sign in</h2>
                        <p class="rider-login-panel__lede">Admins and kitchen managers sign in here. Customers and riders use their own portals.</p>
                    </div>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger rider-login-alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form method="post" class="rider-login-form" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="staff-email">Work email</label>
                            <input
                                type="email"
                                class="form-control rider-login-input"
                                id="staff-email"
                                name="email"
                                value="<?= htmlspecialchars($emailPrefill, ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="email"
                                required
                                placeholder="you@crispycrave.com">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="staff-password">Password</label>
                            <div class="input-group rider-login-password">
                                <input
                                    type="password"
                                    class="form-control rider-login-input"
                                    id="staff-password"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                    placeholder="••••••••">
                                <button type="button" class="btn btn-outline-secondary rider-login-toggle-pw" aria-controls="staff-password" aria-label="Show password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 fw-semibold rider-login-submit admin-login-submit">
                            <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>
                            Sign in
                        </button>
                    </form>

                    <p class="rider-login-panel__note">
                        <a href="<?= htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8') ?>">Customer login</a>
                        ·
                        <a href="<?= htmlspecialchars(app_url('rider/login.php'), ENT_QUOTES, 'UTF-8') ?>">Rider portal</a>
                    </p>
                </div>
            </div>
        </main>
    </div>
    <script>
    (function () {
        var btn = document.querySelector('.rider-login-toggle-pw');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var inp = document.getElementById(btn.getAttribute('aria-controls'));
            if (!inp) return;
            var show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            var ic = btn.querySelector('i');
            if (ic) ic.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    })();
    </script>
</body>
</html>
