<?php

/**
 * URL path prefix for this app under the web server document root.
 * Set APP_BASE_PATH (e.g. /chicken_ordering) if auto-detection fails.
 */
function app_base_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $env = getenv('APP_BASE_PATH');
    if ($env !== false && $env !== '') {
        $env = trim(str_replace('\\', '/', $env), '/');
        return $cached = ($env === '') ? '' : '/' . $env;
    }

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($docRoot === '') {
        return $cached = '';
    }

    $docResolved = realpath($docRoot);
    $appDir = dirname(__DIR__);
    $appResolved = realpath($appDir);

    if ($docResolved === false || $appResolved === false) {
        return $cached = '';
    }

    $docNorm = strtolower(str_replace('\\', '/', $docResolved));
    $appNorm = strtolower(str_replace('\\', '/', $appResolved));

    if (strpos($appNorm, $docNorm) !== 0) {
        return $cached = '';
    }

    $rel = substr($appResolved, strlen($docResolved));
    $rel = trim(str_replace('\\', '/', $rel), '/');
    if ($rel === '') {
        return $cached = '';
    }

    return $cached = '/' . $rel;
}

/** Absolute URL path: /css/style.css or /chicken_ordering/css/style.css */
function app_url(string $path): string
{
    $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
    $base = app_base_path();
    if ($base === '') {
        return $path;
    }
    return rtrim($base, '/') . $path;
}

/** Project root on disk (for uploads, deletes). */
function app_project_root(): string
{
    static $root = null;
    if ($root !== null) {
        return $root;
    }
    $dir = dirname(__DIR__);
    $resolved = realpath($dir);
    return $root = ($resolved !== false ? $resolved : $dir);
}

/**
 * Navbar / auth brand mark (`images/official_logo.png`). Query version changes when the file is replaced.
 */
function app_brand_logo_url(): string
{
    $url = app_url('images/official_logo.png');
    $path = app_project_root() . '/images/official_logo.png';
    if (is_file($path)) {
        $url .= '?v=' . (string) filemtime($path);
    }

    return $url;
}
