<?php
require '../auth/auth.php';
requirePlatformAdmin();
require '../db/database.php';

$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $delivery_time = trim((string) ($_POST['delivery_time'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $formError = 'That email is already in use. Try another.';
    } elseif (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        $formError = 'Please upload a shop logo (JPG or PNG).';
    } else {
        $logoName = $_FILES['logo']['name'];
        $logoTmp = $_FILES['logo']['tmp_name'];
        $logoExt = strtolower(pathinfo($logoName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($logoExt, $allowed, true)) {
            $formError = 'Logo must be JPG or PNG.';
        } else {
            $newLogoName = uniqid('shop_', true) . '.' . $logoExt;
            $uploadDir = app_project_root() . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logos' . DIRECTORY_SEPARATOR;
            $uploadPath = $uploadDir . $newLogoName;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (!move_uploaded_file($logoTmp, $uploadPath)) {
                $formError = 'Failed to upload logo. Try again.';
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO restaurants (name, description, delivery_time, logo)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$name, $description, $delivery_time, $newLogoName]);
                $shop_id = (int) $pdo->lastInsertId();

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmtUser = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, restaurant_id, approval_status)
                    VALUES (?, ?, ?, 'restaurant', ?, 'approved')
                ");
                $stmtUser->execute([$name . ' Owner', $email, $hashedPassword, $shop_id]);

                header('Location: dashboard.php?success=shop_added');
                exit;
            }
        }
    }
}

include '../views/header.php';
?>

<main class="staff-main staff-form-page">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title">Add shop</h1>
        <p class="staff-page-head__sub">Create a new location and kitchen manager account</p>
    </header>

    <?php if ($formError !== ''): ?>
        <p class="pos-alert" role="alert"><?= htmlspecialchars($formError) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="staff-form-card">
        <section class="staff-form-section">
            <h2 class="staff-form-section__title">Shop details</h2>

            <label class="staff-field">
                <span class="staff-field__label">Shop name</span>
                <input type="text" name="name" class="staff-field__input" required
                       value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Description</span>
                <textarea name="description" class="staff-field__textarea" rows="4"
                          placeholder="Short description for customers"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Delivery time</span>
                <input type="text" name="delivery_time" class="staff-field__input"
                       placeholder="e.g. 25–35"
                       value="<?= htmlspecialchars($_POST['delivery_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <span class="staff-field__hint">Shown on the shop card (minutes).</span>
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Shop logo</span>
                <input type="file" name="logo" class="staff-field__file" accept="image/jpeg,image/png" required>
                <span class="staff-field__hint">JPG or PNG, square works best.</span>
            </label>
        </section>

        <section class="staff-form-section">
            <h2 class="staff-form-section__title">Kitchen manager login</h2>
            <p class="staff-field__hint" style="margin: -0.5rem 0 1rem;">This account manages menu, POS, and kitchen for this shop.</p>

            <label class="staff-field">
                <span class="staff-field__label">Email</span>
                <input type="email" name="email" class="staff-field__input" required autocomplete="off"
                       value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Password</span>
                <input type="password" name="password" class="staff-field__input" required minlength="6" autocomplete="new-password">
                <span class="staff-field__hint">At least 6 characters.</span>
            </label>
        </section>

        <footer class="staff-form-actions">
            <a href="shop.php" class="staff-btn staff-btn--secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Cancel
            </a>
            <button type="submit" class="staff-btn staff-btn--primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Save shop
            </button>
        </footer>
    </form>
</main>

<?php include '../views/footer.php'; ?>
