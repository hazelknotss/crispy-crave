<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';
require_once __DIR__ . '/../app/menu_ops.php';

kk_menu_ensure_schema($pdo);

$menu_id = (int) ($_GET['id'] ?? 0);
if ($menu_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM menus WHERE id = ?');
$stmt->execute([$menu_id]);
$menu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    header('Location: dashboard.php');
    exit;
}

$shop_id = (int) $menu['restaurant_id'];
kk_staff_assert_shop($shop_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $desc = trim((string) ($_POST['description'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $category = trim((string) ($_POST['category'] ?? 'General'));
    if ($category === '') {
        $category = 'General';
    }

    $imageName = $menu['image'];

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $newImage = uniqid('menu_') . '.' . $ext;
            $target = app_project_root() . '/images/menus/' . $newImage;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $oldPath = app_project_root() . '/images/menus/' . $menu['image'];
                if (!empty($menu['image']) && is_file($oldPath)) {
                    unlink($oldPath);
                }
                $imageName = $newImage;
            }
        }
    }

    $update = $pdo->prepare('
        UPDATE menus
        SET name = ?, description = ?, price = ?, image = ?, category = ?
        WHERE id = ?
    ');
    $update->execute([$name, $desc, $price, $imageName, $category, $menu_id]);

    header('Location: menus.php?shop_id=' . $shop_id);
    exit;
}

$menuCategory = kk_menu_resolve_category($menu['name'], $menu['category'] ?? null);
$imageUrl = app_url('images/menus/' . $menu['image']);

include '../views/header.php';
?>

<main class="staff-main staff-form-page">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title">Edit menu item</h1>
        <p class="staff-page-head__sub"><?= htmlspecialchars($menu['name']) ?></p>
    </header>

    <form method="post" enctype="multipart/form-data" class="staff-form-card">
        <section class="staff-form-section">
            <h2 class="staff-form-section__title">Item details</h2>

            <label class="staff-field">
                <span class="staff-field__label">Name</span>
                <input type="text" name="name" class="staff-field__input" required
                       value="<?= htmlspecialchars($menu['name'], ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Description</span>
                <textarea name="description" class="staff-field__textarea" rows="3"><?= htmlspecialchars((string) $menu['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>

            <div class="staff-form-row">
                <label class="staff-field staff-form-row__col">
                    <span class="staff-field__label">Price (₱)</span>
                    <input type="number" step="0.01" min="0.01" name="price" class="staff-field__input" required
                           value="<?= htmlspecialchars((string) $menu['price'], ENT_QUOTES, 'UTF-8') ?>">
                </label>

                <label class="staff-field staff-form-row__col">
                    <span class="staff-field__label">Category</span>
                    <select name="category" class="staff-field__select">
                        <?php foreach (kk_menu_category_options() as $opt): ?>
                            <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"<?= $menuCategory === $opt ? ' selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>

        <section class="staff-form-section">
            <h2 class="staff-form-section__title">Photo</h2>

            <label class="staff-field">
                <span class="staff-field__label">Menu image</span>
                <div class="staff-form-preview">
                    <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="staff-form-preview__img">
                    <span class="staff-form-preview__caption">Current image — upload to replace</span>
                </div>
                <input type="file" name="image" class="staff-field__file" accept="image/jpeg,image/png,image/webp">
            </label>
        </section>

        <footer class="staff-form-actions">
            <a href="menus.php?shop_id=<?= (int) $shop_id ?>" class="staff-btn staff-btn--secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back
            </a>
            <button type="submit" class="staff-btn staff-btn--primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Update item
            </button>
        </footer>
    </form>
</main>

<?php include '../views/footer.php'; ?>
