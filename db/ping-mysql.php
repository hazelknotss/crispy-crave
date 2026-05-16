<?php

/**
 * CLI helper: test MySQL TCP from the same PHP binary as serve.ps1.
 *
 *   C:\xampp\php\php.exe db\ping-mysql.php
 *
 * Optional env (PowerShell): $env:DB_HOST='127.0.0.1'; $env:DB_PORT='3307'; $env:DB_PASS='...'
 */

declare(strict_types=1);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db = getenv('DB_NAME') ?: 'chicken_ordering';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '';
$port = getenv('DB_PORT');
$portPart = '';
if ($port !== false && $port !== '') {
    $p = (int) $port;
    if ($p > 0 && $p <= 65535) {
        $portPart = ';port=' . $p;
    }
}

$hosts = [$host];
if ($host === 'localhost' || $host === '127.0.0.1') {
    $hosts = ['127.0.0.1', 'localhost'];
}

echo "Testing MySQL (database={$db}, user={$user})...\n";

foreach ($hosts as $h) {
    $dsn = "mysql:host={$h}{$portPart};dbname={$db};charset=UTF8";
    $shown = 'mysql:host=' . $h . ($portPart !== '' ? $portPart : '') . ';dbname=' . $db;
    echo '  Try: ' . $shown . "\n";
    try {
        new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "OK: connected.\n";
        exit(0);
    } catch (PDOException $e) {
        echo 'FAIL: ' . $e->getMessage() . "\n";
    }
}

echo "\nNothing accepted the connection. Typical fixes:\n";
echo "  - XAMPP: open Control Panel and Start MySQL (listens on 127.0.0.1:3306).\n";
echo "  - Docker MySQL: run  docker ps  and map host port, e.g. 0.0.0.0:3306->3306/tcp\n";
echo "    then set DB_PORT to the LEFT number if it is not 3306.\n";
echo "  - Windows:  netstat -ano | findstr 3306  (should show LISTENING if MySQL is up)\n";
exit(1);
