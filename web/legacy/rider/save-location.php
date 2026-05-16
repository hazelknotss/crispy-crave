<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'rider') {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

require_once __DIR__ . '/../db/database.php';
require_once __DIR__ . '/../app/rider_portal.php';

$lat = isset($_POST['lat']) ? (float) $_POST['lat'] : 0;
$lng = isset($_POST['lng']) ? (float) $_POST['lng'] : 0;
$acc = isset($_POST['accuracy']) ? (float) $_POST['accuracy'] : null;

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

kk_rider_ensure_schema($pdo);
$userId = (int) $_SESSION['user']['id'];

$stmt = $pdo->prepare('
    INSERT INTO rider_locations (user_id, latitude, longitude, accuracy_m)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), accuracy_m = VALUES(accuracy_m)
');
$stmt->execute([$userId, $lat, $lng, $acc]);

echo json_encode(['ok' => true]);
