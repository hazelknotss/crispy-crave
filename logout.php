<?php
session_start();
require_once __DIR__ . '/app/url.php';

/* Unset all session variables */
$_SESSION = [];

session_unset();
/* Destroy session */
session_destroy();

/* Delete session cookie (important) */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* Return to home hero with sign-in modal */
header('Location: ' . app_url('index.php?welcome=1#hero'));
exit;
