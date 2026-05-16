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

$own = $pdo->prepare('SELECT id FROM menus WHERE id = ? AND restaurant_id = ?');
$own->execute([$menu_id, $shop_id]);
if (!$own->fetch()) {
    header('Location: dashboard.php');
    exit;
}

// Toggle status
$stmt = $pdo->prepare("
    UPDATE menus
    SET is_active = IF(is_active = 1, 0, 1)
    WHERE id = ?
");
$stmt->execute([$menu_id]);

header("Location: menus.php?shop_id=" . $shop_id);
exit;
