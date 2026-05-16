<?php
require_once __DIR__ . '/../app/url.php';

/** @var string $kkStaffRedirectTarget admin path e.g. admin/dashboard.php */
if (!isset($kkStaffRedirectTarget)) {
    $kkStaffRedirectTarget = 'admin/dashboard.php';
}

$qs = $_SERVER['QUERY_STRING'] ?? '';
$url = app_url($kkStaffRedirectTarget);
if ($qs !== '') {
    $url .= (str_contains($url, '?') ? '&' : '?') . $qs;
}
header('Location: ' . $url, true, 302);
exit;
