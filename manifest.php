<?php

declare(strict_types=1);

require_once __DIR__ . '/app/url.php';

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$scope = app_url('/');
if (substr($scope, -1) !== '/') {
    $scope .= '/';
}

$root = app_project_root();
$iconPath = $root . '/images/official_logo.png';
if (!is_file($iconPath)) {
    $iconPath = $root . '/images/logo.png';
}
$iconSrc = strpos($iconPath, 'official_logo') !== false
    ? app_url('images/official_logo.png')
    : app_url('images/logo.png');

$manifest = [
    'name' => 'Crispy Crave',
    'short_name' => 'Crispy Crave',
    'description' => 'Order meals, snacks, and drinks from Pototan restaurants — delivery or pickup.',
    'start_url' => app_url('index.php'),
    'scope' => $scope,
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#111111',
    'lang' => 'en',
    'dir' => 'ltr',
    'icons' => [
        [
            'src' => $iconSrc,
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => $iconSrc,
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
    ],
    'categories' => ['food', 'shopping'],
];

$json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo '{}';
    exit;
}

echo $json;
