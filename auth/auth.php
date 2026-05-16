<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isset($_SESSION['user'])) {
        header("Location: ../login.php");
        exit;
    }
}

/**
 * Require staff (platform admin or shop / kitchen manager).
 */
function requireStaff() {
    if (!function_exists('app_url')) {
        require_once dirname(__DIR__) . '/app/url.php';
    }
    require_once dirname(__DIR__) . '/app/staff.php';
    if (!kk_is_staff()) {
        header('Location: ' . app_url('admin/login.php'));
        exit;
    }
}

/**
 * Require platform admin only (all shops, settings).
 */
function requirePlatformAdmin() {
    requireStaff();
    if (!kk_staff_is_platform()) {
        header('Location: ' . app_url('admin/dashboard.php'));
        exit;
    }
}

/**
 * @deprecated Use requireStaff() or requirePlatformAdmin()
 */
function requireAdmin() {
    requireStaff();
}

/**
 * Require rider role (use from rider/*.php after auth.php is loaded).
 */
function requireRider() {
    if (!function_exists('app_url')) {
        require_once dirname(__DIR__) . '/app/url.php';
    }
    if (!isset($_SESSION['user'])) {
        header('Location: ' . app_url('rider/login.php'));
        exit;
    }
    if (($_SESSION['user']['role'] ?? '') !== 'rider') {
        header('Location: ' . app_url('rider/login.php'));
        exit;
    }
}
