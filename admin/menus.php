<?php

require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';

$shop_id = $_GET['shop_id'] ?? null;
if ($shop_id === null && kk_staff_shop_id() !== null) {
    header('Location: menus.php?shop_id=' . kk_staff_shop_id());
    exit;
}

if (!$shop_id) {
    header('Location: dashboard.php');
    exit;
}

$shop_id = (int) $shop_id;
kk_staff_assert_shop($shop_id);

$shop = $pdo->prepare('SELECT * FROM restaurants WHERE id = ?');
$shop->execute([$shop_id]);
$shop = $shop->fetch();

if (!$shop) {
    header('Location: dashboard.php');
    exit;
}

$menuStmt = $pdo->prepare('SELECT * FROM menus WHERE restaurant_id = ? ORDER BY name');
$menuStmt->execute([$shop_id]);
$menuList = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

include '../views/header.php';
?>

<main class="staff-main">
    <header class="staff-page-head staff-page-head--full d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="staff-page-head__title"><?= htmlspecialchars($shop['name']) ?> — Menus</h1>
            <p class="staff-page-head__sub">Add, edit, and enable menu items</p>
        </div>
        <a href="add-menu.php?shop_id=<?= $shop_id ?>" class="staff-btn staff-btn--primary flex-shrink-0">
            <i class="bi bi-plus-lg"></i> Add menu
        </a>
    </header>

    <?php if (empty($menuList)): ?>
        <p class="staff-empty">No menus added yet. <a href="add-menu.php?shop_id=<?= $shop_id ?>">Add your first item</a>.</p>
    <?php else: ?>
    <div class="staff-card-grid staff-card-grid--menus">
        <?php foreach ($menuList as $menu): ?>
            <?php
            $image = (!empty($menu['image']) && is_file(app_project_root() . '/images/menus/' . $menu['image']))
                ? $menu['image'] : 'default.png';
            ?>
            <article class="staff-menu-card <?= !$menu['is_active'] ? 'staff-menu-card--inactive' : '' ?>">
                <div class="staff-menu-card__img-wrap">
                    <img src="<?= htmlspecialchars(app_url('images/menus/' . $image)) ?>"
                        class="staff-menu-card__img" alt="<?= htmlspecialchars($menu['name']) ?>">
                </div>
                <div class="staff-menu-card__body">
                    <h2 class="staff-menu-card__title"><?= htmlspecialchars($menu['name']) ?></h2>
                    <p class="staff-menu-card__desc"><?= htmlspecialchars($menu['description'] ?: 'No description') ?></p>
                    <p class="staff-menu-card__price">₱<?= number_format((float) $menu['price'], 2) ?></p>
                    <div class="staff-menu-card__actions">
                        <a href="edit-menu.php?id=<?= (int) $menu['id'] ?>" class="staff-chip staff-chip--edit">Edit</a>
                        <a href="<?= htmlspecialchars(app_url('admin/delete-menu.php?id=' . (int) $menu['id'] . '&shop_id=' . $shop_id)) ?>"
                           class="staff-chip staff-chip--delete"
                           onclick="return confirm('Delete this menu permanently?')">Delete</a>
                        <?php if ($menu['is_active']): ?>
                            <a href="toggle-menu.php?id=<?= (int) $menu['id'] ?>&shop_id=<?= $shop_id ?>"
                               class="staff-chip staff-chip--delete"
                               onclick="return confirm('Disable this menu?')">Disable</a>
                        <?php else: ?>
                            <a href="toggle-menu.php?id=<?= (int) $menu['id'] ?>&shop_id=<?= $shop_id ?>"
                               class="staff-chip staff-chip--menus">Enable</a>
                            <span class="badge bg-secondary">Off</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<?php include '../views/footer.php'; ?>
