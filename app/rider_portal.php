<?php

function kk_rider_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rider_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            doc_type ENUM('license','registration','id_photo','other') NOT NULL DEFAULT 'license',
            file_name VARCHAR(255) NOT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rider_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(120) NOT NULL,
            message TEXT NOT NULL,
            link_url VARCHAR(255) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY (user_id),
            KEY (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rider_locations (
            user_id INT PRIMARY KEY,
            latitude DECIMAL(10,7) NOT NULL,
            longitude DECIMAL(10,7) NOT NULL,
            accuracy_m DECIMAL(8,2) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rider_shifts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            shift_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            notes VARCHAR(255) NULL,
            status ENUM('scheduled','active','completed','cancelled') NOT NULL DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY (user_id),
            KEY (shift_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    foreach (['vehicle_plate VARCHAR(20) NULL', 'emergency_contact VARCHAR(80) NULL', 'onboarding_notes TEXT NULL'] as $col) {
        try {
            $pdo->exec('ALTER TABLE riders ADD COLUMN ' . $col);
        } catch (PDOException $e) {
            // column exists
        }
    }
}

function kk_rider_ensure_profile_row(PDO $pdo, int $userId): void
{
    $check = $pdo->prepare('SELECT id FROM riders WHERE user_id = ? LIMIT 1');
    $check->execute([$userId]);
    if ($check->fetch()) {
        return;
    }
    $ins = $pdo->prepare("INSERT INTO riders (user_id, status) VALUES (?, 'available')");
    $ins->execute([$userId]);
}

/**
 * @return array<string, mixed>|null
 */
function kk_rider_profile(PDO $pdo, int $userId): ?array
{
    kk_rider_ensure_profile_row($pdo, $userId);
    $stmt = $pdo->prepare('
        SELECT u.id, u.name, u.email, u.approval_status, u.restaurant_id,
               r.phone, r.vehicle_type, r.vehicle_plate, r.emergency_contact,
               r.status AS fleet_status, r.onboarding_notes
        FROM users u
        LEFT JOIN riders r ON r.user_id = u.id
        WHERE u.id = ? AND u.role = \'rider\'
    ');
    $stmt->execute([$userId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function kk_rider_notify(PDO $pdo, int $userId, string $title, string $message, ?string $linkUrl = null): void
{
    kk_rider_ensure_schema($pdo);
    $stmt = $pdo->prepare('
        INSERT INTO rider_notifications (user_id, title, message, link_url)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$userId, $title, $message, $linkUrl]);
}

function kk_rider_unread_count(PDO $pdo, int $userId): int
{
    kk_rider_ensure_schema($pdo);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rider_notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}

/**
 * @return array<int, array<string, mixed>>
 */
function kk_rider_notifications(PDO $pdo, int $userId, int $limit = 30): array
{
    kk_rider_ensure_schema($pdo);
    $stmt = $pdo->prepare('
        SELECT * FROM rider_notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ' . (int) $limit
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * @return array{total:int,active:int,delivered:int,earnings:float,avg_minutes:float|null}
 */
function kk_rider_performance_stats(PDO $pdo, int $riderId): array
{
    $total = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE rider_id = ?');
    $total->execute([$riderId]);
    $totalCount = (int) $total->fetchColumn();

    $delivered = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = ? AND delivery_status = 'delivered'");
    $delivered->execute([$riderId]);
    $deliveredCount = (int) $delivered->fetchColumn();

    $earnings = $pdo->prepare("SELECT COALESCE(SUM(rider_fee), 0) FROM orders WHERE rider_id = ? AND delivery_status = 'delivered'");
    $earnings->execute([$riderId]);
    $earningsSum = (float) $earnings->fetchColumn();

    return [
        'total'       => $totalCount,
        'active'      => max(0, $totalCount - $deliveredCount),
        'delivered'   => $deliveredCount,
        'earnings'    => $earningsSum,
        'avg_minutes' => null,
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function kk_rider_earnings_rows(PDO $pdo, int $riderId, int $limit = 50): array
{
    $stmt = $pdo->prepare("
        SELECT id, total, rider_fee, barangay, created_at, delivery_status, payment_method
        FROM orders
        WHERE rider_id = ? AND delivery_status = 'delivered'
        ORDER BY created_at DESC
        LIMIT " . (int) $limit
    );
    $stmt->execute([$riderId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * @return array<int, array<string, mixed>>
 */
function kk_rider_documents(PDO $pdo, int $userId): array
{
    kk_rider_ensure_schema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM rider_documents WHERE user_id = ? ORDER BY uploaded_at DESC');
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function kk_rider_save_document(PDO $pdo, int $userId, string $docType, string $fileName): void
{
    kk_rider_ensure_schema($pdo);
    $stmt = $pdo->prepare('
        INSERT INTO rider_documents (user_id, doc_type, file_name, status)
        VALUES (?, ?, ?, \'pending\')
    ');
    $stmt->execute([$userId, $docType, $fileName]);
}

function kk_rider_upload_dir(): string
{
    $dir = app_project_root() . '/uploads/riders';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

/**
 * @return array{lat:float,lng:float,updated_at:string}|null
 */
function kk_rider_last_location(PDO $pdo, int $userId): ?array
{
    kk_rider_ensure_schema($pdo);
    $stmt = $pdo->prepare('SELECT latitude, longitude, updated_at FROM rider_locations WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'lat'        => (float) $row['latitude'],
        'lng'        => (float) $row['longitude'],
        'updated_at' => (string) $row['updated_at'],
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function kk_rider_shifts(PDO $pdo, int $userId): array
{
    kk_rider_ensure_schema($pdo);
    $stmt = $pdo->prepare('
        SELECT * FROM rider_shifts
        WHERE user_id = ? AND shift_date >= CURDATE()
        ORDER BY shift_date ASC, start_time ASC
        LIMIT 14
    ');
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function kk_rider_onboarding_complete(array $profile, array $documents): bool
{
    if (($profile['approval_status'] ?? '') !== 'approved') {
        return false;
    }
    $types = [];
    foreach ($documents as $doc) {
        if (($doc['status'] ?? '') !== 'rejected') {
            $types[$doc['doc_type'] ?? ''] = true;
        }
    }

    return isset($types['license']) && isset($types['id_photo']);
}
