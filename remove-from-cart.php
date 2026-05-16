<?php
session_start();

$menu_id = $_GET['id'] ?? null;

if (!$menu_id || !isset($_SESSION['cart'][$menu_id])) {
    header("Location: cart.php");
    exit;
}

/* Remove item */
unset($_SESSION['cart'][$menu_id]);

/* If cart is empty, reset shop lock */
if (empty($_SESSION['cart'])) {
    unset($_SESSION['cart_shop_id']);
}

header("Location: cart.php");
exit;
