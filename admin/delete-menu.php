<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';

$menu_id = (int) ($_GET['id'] ?? 0);
$shop_id = (int) ($_GET['shop_id'] ?? 0);

if ($menu_id <= 0 || $shop_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

kk_staff_assert_shop($shop_id);

$stmt = $pdo->prepare('SELECT image, restaurant_id FROM menus WHERE id = ?');
$stmt->execute([$menu_id]);
$menu = $stmt->fetch();

if (!$menu || (int) $menu['restaurant_id'] !== $shop_id) {
    header('Location: dashboard.php');
    exit;
}

if ($menu && !empty($menu['image'])) {
    $imagePath = app_project_root() . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'menus' . DIRECTORY_SEPARATOR . $menu['image'];

    if (file_exists($imagePath)) {
        unlink($imagePath); // delete image file
    }
}

// Delete menu from database
$stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
$stmt->execute([$menu_id]);

// Redirect back to menus page
header("Location: menus.php?shop_id=" . $shop_id);
exit;
