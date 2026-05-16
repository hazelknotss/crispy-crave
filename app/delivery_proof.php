<?php

function kk_delivery_proof_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $cols = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivery_proof_file'")->fetch();
    if (!$cols) {
        $pdo->exec("
            ALTER TABLE orders
            ADD COLUMN delivery_proof_file VARCHAR(255) NULL DEFAULT NULL,
            ADD COLUMN delivery_proof_note VARCHAR(500) NULL DEFAULT NULL,
            ADD COLUMN delivery_proof_at DATETIME NULL DEFAULT NULL
        ");
    }
}

function kk_delivery_proof_upload_dir(): string
{
    $dir = app_project_root() . '/uploads/delivery-proofs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

/**
 * @return array{ok:bool,filename:?string,error:?string}
 */
function kk_delivery_proof_process_upload(array $file, int $orderId): array
{
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'filename' => null, 'error' => 'Please add a photo as proof of delivery.'];
    }
    if ($err !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'error' => 'Photo upload failed. Try again.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'filename' => null, 'error' => 'Invalid upload.'];
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['ok' => false, 'filename' => null, 'error' => 'Photo must be 5 MB or smaller.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'filename' => null, 'error' => 'Use a JPG, PNG, or WebP photo.'];
    }

    $ext = $allowed[$mime];
    $safe = 'order_' . $orderId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = kk_delivery_proof_upload_dir() . '/' . $safe;

    if (!move_uploaded_file($tmp, $target)) {
        return ['ok' => false, 'filename' => null, 'error' => 'Could not save the photo.'];
    }

    return ['ok' => true, 'filename' => $safe, 'error' => null];
}

function kk_delivery_proof_url(?string $filename): ?string
{
    if ($filename === null || $filename === '') {
        return null;
    }
    if (!function_exists('app_url')) {
        require_once __DIR__ . '/url.php';
    }

    return app_url('uploads/delivery-proofs/' . rawurlencode($filename));
}

/**
 * @return array{ok:bool,error:?string}
 */
function kk_delivery_proof_attach(PDO $pdo, int $orderId, int $riderId, array $file, ?string $note): array
{
    kk_delivery_proof_ensure_schema($pdo);

    $check = $pdo->prepare('SELECT delivery_proof_file FROM orders WHERE id = ? AND rider_id = ?');
    $check->execute([$orderId, $riderId]);
    $existing = $check->fetchColumn();

    if ($existing !== false && $existing !== null && $existing !== '') {
        $upload = ['ok' => true, 'filename' => (string) $existing, 'error' => null];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = kk_delivery_proof_process_upload($file, $orderId);
            if (!$upload['ok']) {
                return ['ok' => false, 'error' => $upload['error']];
            }
            $oldPath = kk_delivery_proof_upload_dir() . '/' . basename((string) $existing);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
    } else {
        $upload = kk_delivery_proof_process_upload($file, $orderId);
        if (!$upload['ok']) {
            return ['ok' => false, 'error' => $upload['error']];
        }
    }

    $note = $note !== null ? trim($note) : '';
    if (strlen($note) > 500) {
        $note = substr($note, 0, 500);
    }

    $stmt = $pdo->prepare('
        UPDATE orders
        SET delivery_proof_file = ?,
            delivery_proof_note = ?,
            delivery_proof_at = NOW()
        WHERE id = ? AND rider_id = ?
    ');
    $stmt->execute([
        $upload['filename'],
        $note !== '' ? $note : null,
        $orderId,
        $riderId,
    ]);

    return ['ok' => true, 'error' => null];
}

/**
 * @return array{file:?string,note:?string,at:?string,url:?string}|null
 */
function kk_delivery_proof_for_order(PDO $pdo, int $orderId): ?array
{
    kk_delivery_proof_ensure_schema($pdo);
    $stmt = $pdo->prepare('
        SELECT delivery_proof_file, delivery_proof_note, delivery_proof_at
        FROM orders WHERE id = ?
    ');
    $stmt->execute([$orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['delivery_proof_file'])) {
        return null;
    }

    return [
        'file' => (string) $row['delivery_proof_file'],
        'note' => $row['delivery_proof_note'] !== null ? (string) $row['delivery_proof_note'] : null,
        'at'   => $row['delivery_proof_at'] !== null ? (string) $row['delivery_proof_at'] : null,
        'url'  => kk_delivery_proof_url((string) $row['delivery_proof_file']),
    ];
}
