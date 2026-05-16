<?php
session_start();
$_SESSION = [];
session_unset();
session_destroy();

require_once __DIR__ . '/../app/url.php';
header('Location: ' . app_url('admin/login.php'));
exit;
