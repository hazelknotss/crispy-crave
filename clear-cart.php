<?php
session_start();
unset($_SESSION['cart'], $_SESSION['cart_shop_id'], $_SESSION['checkout_prefill']);
header("Location: cart.php");
exit;
