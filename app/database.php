<?php

$host = 'localhost';
$db   = 'chicken_ordering';
$user = 'root';
$pass = ''; // change if needed

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=UTF8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed");
}
