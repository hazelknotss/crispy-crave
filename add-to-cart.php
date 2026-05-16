<?php
session_start();
require 'db/database.php';


/* 🔒 REQUIRE LOGIN */
if (!isset($_SESSION['user'])) {
    header("Location: login.php?redirect=cart");
    exit;
}

$menu_id = $_POST['menu_id'] ?? null;

if (!$menu_id) {
    header('Location: ' . app_url('index.php'));
    exit;
}

/* -------------------------------------------------
   FETCH MENU FROM DATABASE (SOURCE OF TRUTH)
------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT m.*, m.restaurant_id AS shop_id
    FROM menus m
    WHERE m.id = ?
");
$stmt->execute([$menu_id]);
$menu = $stmt->fetch();

if (!$menu) {
    header('Location: ' . app_url('index.php'));
    exit;
}

/* -------------------------------------------------
   ENFORCE ONE SHOP ONLY
------------------------------------------------- */
if (
    isset($_SESSION['cart_shop_id']) &&
    $_SESSION['cart_shop_id'] != $menu['shop_id']
) {
    header('Location: ' . app_url('restaurant.php?id=' . (int) $menu['shop_id'] . '&error=different_shop'));
    exit;
}

/* -------------------------------------------------
   INITIALIZE CART
------------------------------------------------- */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* Set shop ID only ONCE */
if (!isset($_SESSION['cart_shop_id'])) {
    $_SESSION['cart_shop_id'] = $menu['shop_id'];
}

/* -------------------------------------------------
   ADD OR UPDATE ITEM
------------------------------------------------- */
if (isset($_SESSION['cart'][$menu_id])) {
    $_SESSION['cart'][$menu_id]['qty'] += 1;
} else {
    $_SESSION['cart'][$menu_id] = [
        'menu_id' => $menu['id'],
        'name'    => $menu['name'],
        'price'   => $menu['price'],
        'image'   => $menu['image'],
        'qty'     => 1
    ];
}

$prefillFlow = (string) ($_POST['prefill_flow'] ?? '');
if ($prefillFlow === 'order_now') {
    $ful = (string) ($_POST['prefill_fulfillment'] ?? 'delivery');
    if ($ful !== 'pickup') {
        $ful = 'delivery';
    }
    $pay = (string) ($_POST['prefill_payment'] ?? 'cod');
    if (!in_array($pay, ['cod', 'gcash', 'bank', 'card'], true)) {
        $pay = 'cod';
    }
    $dOpt = (string) ($_POST['prefill_delivery_option'] ?? 'standard');
    if (!in_array($dOpt, ['standard', 'priority', 'scheduled'], true)) {
        $dOpt = 'standard';
    }
    $_SESSION['checkout_prefill'] = [
        'fulfillment'       => $ful,
        'barangay'          => trim((string) ($_POST['prefill_barangay'] ?? '')),
        'delivery_option'   => $dOpt,
        'distance_km'       => trim((string) ($_POST['prefill_distance_km'] ?? '')),
        'rider_fee'         => trim((string) ($_POST['prefill_rider_fee'] ?? '')),
        'address'           => trim((string) ($_POST['prefill_address'] ?? '')),
        'time'              => trim((string) ($_POST['prefill_time'] ?? '')),
        'payment'           => $pay,
        'notes'             => trim((string) ($_POST['prefill_notes'] ?? '')),
        'schedule_date'     => trim((string) ($_POST['prefill_schedule_date'] ?? '')),
        'schedule_time'     => trim((string) ($_POST['prefill_schedule_time'] ?? '')),
    ];
}

header('Location: ' . app_url('cart.php'));
exit;
