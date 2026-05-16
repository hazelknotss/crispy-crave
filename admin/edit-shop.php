<?php
require '../auth/auth.php';
requirePlatformAdmin();
require '../db/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: shop.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE id = ?');
$stmt->execute([$id]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    header('Location: shop.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $delivery = trim((string) ($_POST['delivery_time'] ?? ''));
    $is_active = (int) ($_POST['is_active'] ?? 1);

    $logo = $shop['logo'];

    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $logo = uniqid('shop_', true) . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], app_project_root() . '/images/logos/' . $logo);
        }
    }

    $stmt = $pdo->prepare('
        UPDATE restaurants
        SET name = ?, description = ?, delivery_time = ?, logo = ?, is_active = ?
        WHERE id = ?
    ');
    $stmt->execute([$name, $description, $delivery, $logo, $is_active, $id]);

    header('Location: shop.php');
    exit;
}

include '../views/header.php';
$logoUrl = app_url('images/logos/' . $shop['logo']);
?>

<main class="staff-main staff-form-page">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title">Edit shop</h1>
        <p class="staff-page-head__sub"><?= htmlspecialchars($shop['name']) ?></p>
    </header>

    <form method="post" enctype="multipart/form-data" class="staff-form-card">
        <section class="staff-form-section">
            <h2 class="staff-form-section__title">Shop details</h2>

            <label class="staff-field">
                <span class="staff-field__label">Shop name</span>
                <input type="text" name="name" class="staff-field__input" required
                       value="<?= htmlspecialchars($shop['name'], ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Description</span>
                <textarea name="description" class="staff-field__textarea" rows="4"><?= htmlspecialchars((string) $shop['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Delivery time</span>
                <input type="text" name="delivery_time" class="staff-field__input"
                       value="<?= htmlspecialchars((string) $shop['delivery_time'], ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Shop logo</span>
                <div class="staff-form-preview">
                    <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="staff-form-preview__img">
                    <span class="staff-form-preview__caption">Current logo — upload to replace</span>
                </div>
                <input type="file" name="logo" class="staff-field__file" accept="image/jpeg,image/png">
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Visibility</span>
                <select name="is_active" class="staff-field__select">
                    <option value="1"<?= $shop['is_active'] ? ' selected' : '' ?>>Active — visible to customers</option>
                    <option value="0"<?= !$shop['is_active'] ? ' selected' : '' ?>>Hidden</option>
                </select>
            </label>
        </section>

        <footer class="staff-form-actions">
            <a href="shop.php" class="staff-btn staff-btn--secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back
            </a>
            <button type="submit" class="staff-btn staff-btn--primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Update shop
            </button>
        </footer>
    </form>
</main>

<?php include '../views/footer.php'; ?>
