<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/url.php';

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'chicken_ordering';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '';
$dbPortEnv = getenv('DB_PORT');
$dbPortPart = '';
if ($dbPortEnv !== false && $dbPortEnv !== '') {
    $p = (int) $dbPortEnv;
    if ($p > 0 && $p <= 65535) {
        $dbPortPart = ';port=' . $p;
    }
}

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

/** True when using `php -S` (serve.ps1 / php-dev-server.sh). */
function kk_db_is_php_dev_server(): bool
{
    $sw = $_SERVER['SERVER_SOFTWARE'] ?? '';
    return $sw !== '' && stripos($sw, 'Development Server') !== false;
}

$hostsToTry = [$host];
if ($host === 'localhost' || $host === '127.0.0.1') {
    $hostsToTry = array_values(array_unique(['127.0.0.1', 'localhost']));
}

/**
 * Connection attempts as [host, portSuffix] where portSuffix is '' or ';port=3307'.
 * On PHP built-in server with default host and no DB_PORT, try this repo's Docker map (3307) first.
 */
$attempts = [];
$prependDockerPort = kk_db_is_php_dev_server()
    && ($dbPortEnv === false || $dbPortEnv === '')
    && $dbPortPart === ''
    && ($host === 'localhost' || $host === '127.0.0.1');
if ($prependDockerPort) {
    $attempts[] = ['127.0.0.1', ';port=3307'];
}
foreach ($hostsToTry as $tryHost) {
    $attempts[] = [$tryHost, $dbPortPart];
}

$seen = [];
$uniqueAttempts = [];
foreach ($attempts as $pair) {
    $key = $pair[0] . "\0" . $pair[1];
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    $uniqueAttempts[] = $pair;
}

$lastException = null;
$pdo = null;

// Docker in this repo: MySQL on host 3307, user chicken (see docker-compose.yml).
if (
    $prependDockerPort
    && $user === 'root'
    && $pass === ''
    && getenv('DB_USER') === false
    && getenv('DB_PASS') === false
) {
    try {
        $pdo = new PDO(
            "mysql:host=127.0.0.1;port=3307;dbname=$db;charset=UTF8",
            'chicken',
            'chicken_secret',
            $pdoOptions
        );
        $lastException = null;
    } catch (PDOException $e) {
        $lastException = $e;
        $pdo = null;
    }
}

if ($pdo === null) {
    foreach ($uniqueAttempts as $pair) {
        $tryHost = $pair[0];
        $portPart = $pair[1];
        try {
            $pdo = new PDO(
                "mysql:host=$tryHost$portPart;dbname=$db;charset=UTF8",
                $user,
                $pass,
                $pdoOptions
            );
            $lastException = null;
            break;
        } catch (PDOException $e) {
            $lastException = $e;
            $pdo = null;
        }
    }
}

if ($pdo === null) {
    $msg = 'Database connection failed.';
    if (kk_db_is_php_dev_server() || getenv('DB_DEBUG') === '1') {
        $detail = $lastException ? $lastException->getMessage() : 'Unknown error';
        $msg .= ' ' . $detail;
        $refused = stripos($detail, '2002') !== false
            || stripos($detail, 'actively refused') !== false
            || stripos($detail, 'Connection refused') !== false;
        if ($refused) {
            $msg .= ' [TCP refused: nothing accepted MySQL on the tried host/port(s). ';
            $msg .= 'If you use Docker from this repo: run `docker compose up -d` and wait until MySQL is healthy; ';
            $msg .= 'the compose file maps host port 3307 to MySQL. ';
            $msg .= 'This app tries 127.0.0.1:3307 automatically when you use serve.ps1 without DB_PORT. ';
            $msg .= 'If your map is different, set DB_PORT (and DB_USER/DB_PASS if not root). ';
            $msg .= 'CLI: php db/ping-mysql.php ]';
        } else {
            $msg .= ' — Start MySQL (XAMPP Control Panel: Start MySQL).';
            $msg .= ' Create database `' . $db . '` if missing, then import `db/chicken_ordering.sql` in phpMyAdmin.';
            $msg .= ' If your MySQL root user has a password, set environment variable DB_PASS before starting PHP.';
            $msg .= ' If MySQL uses a non-default port (e.g. Docker), set DB_PORT.';
            if (stripos($detail, 'Access denied') !== false || stripos($detail, '1045') !== false) {
                $msg .= ' This repo Docker uses user `chicken` / password `chicken_secret` on port 3307; run serve-docker-db.bat or set DB_USER and DB_PASS.';
            }
        }
    }
    die($msg);
}
