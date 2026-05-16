<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';
require_once __DIR__ . '/../app/menu_ops.php';

kk_menu_ensure_schema($pdo);

$shop_id = $_GET['shop_id'] ?? $_POST['shop_id'] ?? null;
if ($shop_id === null && kk_staff_shop_id() !== null) {
    header('Location: add-menu.php?shop_id=' . kk_staff_shop_id());
    exit;
}

if (!$shop_id) {
    header('Location: dashboard.php');
    exit;
}

$shop_id = (int) $shop_id;
kk_staff_assert_shop($shop_id);

$shopStmt = $pdo->prepare('SELECT name FROM restaurants WHERE id = ?');
$shopStmt->execute([$shop_id]);
$shopName = $shopStmt->fetchColumn() ?: 'Shop';

$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $desc = trim((string) ($_POST['description'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $category = trim((string) ($_POST['category'] ?? 'General'));
    if ($category === '') {
        $category = 'General';
    }

    if ($name === '' || $price <= 0) {
        $formError = 'Enter a name and a valid price.';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $formError = 'Please upload a menu image.';
    } else {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $formError = 'Image must be JPG, PNG, or WebP.';
        } else {
            $imageName = uniqid('menu_') . '.' . $ext;
            $target = app_project_root() . '/images/menus/' . $imageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $formError = 'Failed to save image. Try again.';
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO menus (restaurant_id, name, description, price, image, category)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$shop_id, $name, $desc, $price, $imageName, $category]);
                header('Location: menus.php?shop_id=' . $shop_id);
                exit;
            }
        }
    }
}

include '../views/header.php';
?>

<main class="staff-main staff-form-page">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title">Add menu item</h1>
        <p class="staff-page-head__sub"><?= htmlspecialchars($shopName) ?> · appears on POS and customer menu</p>
    </header>

    <?php if ($formError !== ''): ?>
        <p class="pos-alert" role="alert"><?= htmlspecialchars($formError) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="staff-form-card">
        <input type="hidden" name="shop_id" value="<?= (int) $shop_id ?>">

        <section class="staff-form-section">
            <h2 class="staff-form-section__title">Item details</h2>

            <label class="staff-field">
                <span class="staff-field__label">Name</span>
                <input type="text" name="name" class="staff-field__input" required
                       value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="staff-field">
                <span class="staff-field__label">Description</span>
                <textarea name="description" class="staff-field__textarea" rows="3"
                          placeholder="Optional — ingredients or serving size"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>

            <div class="staff-form-row">
                <label class="staff-field staff-form-row__col">
                    <span class="staff-field__label">Price (₱)</span>
                    <input type="number" step="0.01" min="0.01" name="price" class="staff-field__input" required
                           value="<?= htmlspecialchars((string) ($_POST['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </label>

                <label class="staff-field staff-form-row__col">
                    <span class="staff-field__label">Category</span>
                    <select name="category" class="staff-field__select">
                        <?php
                        $selCat = $_POST['category'] ?? 'General';
                        foreach (kk_menu_category_options() as $opt):
                            ?>
                            <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"<?= $selCat === $opt ? ' selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="staff-field__hint">Groups items on POS.</span>
                </label>
            </div>
        </section>

        <section class="staff-form-section">
            <h2 class="staff-form-section__title">Photo</h2>

            <label class="staff-field">
                <span class="staff-field__label">Menu image</span>
                <input type="file" name="image" class="staff-field__file" accept="image/jpeg,image/png,image/webp" required>
                <span class="staff-field__hint">Shown to customers and on POS tiles.</span>
            </label>
        </section>

        <footer class="staff-form-actions">
            <a href="menus.php?shop_id=<?= (int) $shop_id ?>" class="staff-btn staff-btn--secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back
            </a>
            <button type="submit" class="staff-btn staff-btn--primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Save item
            </button>
        </footer>
    </form>
</main>

<?php include '../views/footer.php'; ?>
