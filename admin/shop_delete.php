<?php
require '../auth/auth.php';
requirePlatformAdmin();
require '../db/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
$stmt->execute([$id]);

header("Location: dashboard.php");
exit;
