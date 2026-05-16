<?php

function kk_order_messages_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            sender_user_id INT NOT NULL,
            sender_role ENUM('user','rider') NOT NULL,
            body TEXT NOT NULL,
            read_at_customer DATETIME NULL,
            read_at_rider DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY (order_id),
            KEY (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/**
 * @return array{role:string,order:array<string,mixed>,other_name:string,other_label:string}|null
 */
function kk_order_chat_access(PDO $pdo, int $orderId, ?array $sessionUser): ?array
{
    if (!$sessionUser || $orderId < 1) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT o.*, cu.name AS customer_name, ru.name AS rider_name
        FROM orders o
        JOIN users cu ON cu.id = o.user_id
        LEFT JOIN users ru ON ru.id = o.rider_id
        WHERE o.id = ?
    ');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return null;
    }

    $role = (string) ($sessionUser['role'] ?? '');
    $userId = (int) ($sessionUser['id'] ?? 0);

    if ($role === 'user' && (int) $order['user_id'] === $userId) {
        if (empty($order['rider_id'])) {
            return null;
        }

        return [
            'role'         => 'user',
            'order'        => $order,
            'other_name'   => (string) ($order['rider_name'] ?? 'Your rider'),
            'other_label'  => 'Rider',
        ];
    }

    if ($role === 'rider' && (int) $order['rider_id'] === $userId) {
        return [
            'role'         => 'rider',
            'order'        => $order,
            'other_name'   => (string) ($order['customer_name'] ?? 'Customer'),
            'other_label'  => 'Customer',
        ];
    }

    return null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function kk_order_chat_fetch(PDO $pdo, int $orderId): array
{
    kk_order_messages_ensure_schema($pdo);
    $stmt = $pdo->prepare('
        SELECT m.*, u.name AS sender_name
        FROM order_messages m
        JOIN users u ON u.id = m.sender_user_id
        WHERE m.order_id = ?
        ORDER BY m.created_at ASC
    ');
    $stmt->execute([$orderId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function kk_order_chat_mark_read(PDO $pdo, int $orderId, string $viewerRole): void
{
    kk_order_messages_ensure_schema($pdo);
    if ($viewerRole === 'user') {
        $pdo->prepare('
            UPDATE order_messages SET read_at_customer = NOW()
            WHERE order_id = ? AND sender_role = \'rider\' AND read_at_customer IS NULL
        ')->execute([$orderId]);
    } elseif ($viewerRole === 'rider') {
        $pdo->prepare('
            UPDATE order_messages SET read_at_rider = NOW()
            WHERE order_id = ? AND sender_role = \'user\' AND read_at_rider IS NULL
        ')->execute([$orderId]);
    }
}

function kk_order_chat_send(PDO $pdo, int $orderId, int $senderUserId, string $senderRole, string $body): bool
{
    $body = trim($body);
    if ($body === '' || strlen($body) > 2000) {
        return false;
    }

    kk_order_messages_ensure_schema($pdo);
    $stmt = $pdo->prepare('
        INSERT INTO order_messages (order_id, sender_user_id, sender_role, body, read_at_customer, read_at_rider)
        VALUES (?, ?, ?, ?, ?, ?)
    ');

    if ($senderRole === 'rider') {
        $readCustomer = null;
        $readRider = date('Y-m-d H:i:s');
    } else {
        $readCustomer = date('Y-m-d H:i:s');
        $readRider = null;
    }

    $stmt->execute([$orderId, $senderUserId, $senderRole, $body, $readCustomer, $readRider]);

    if ($senderRole === 'rider' && function_exists('kk_rider_notify')) {
        // notify customer via nothing yet - rider has notifications
    }

    if ($senderRole === 'user' && function_exists('kk_rider_notify')) {
        require_once __DIR__ . '/url.php';
        $order = $pdo->prepare('SELECT rider_id FROM orders WHERE id = ?');
        $order->execute([$orderId]);
        $riderId = (int) $order->fetchColumn();
        if ($riderId > 0) {
            require_once __DIR__ . '/rider_portal.php';
            kk_rider_notify(
                $pdo,
                $riderId,
                'New message',
                'Customer sent a message on order #' . $orderId,
                app_url('order-chat.php?order_id=' . $orderId)
            );
        }
    }

    return true;
}

function kk_order_chat_unread_count(PDO $pdo, int $orderId, string $viewerRole): int
{
    kk_order_messages_ensure_schema($pdo);
    if ($viewerRole === 'user') {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM order_messages
            WHERE order_id = ? AND sender_role = \'rider\' AND read_at_customer IS NULL
        ');
    } else {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM order_messages
            WHERE order_id = ? AND sender_role = \'user\' AND read_at_rider IS NULL
        ');
    }
    $stmt->execute([$orderId]);

    return (int) $stmt->fetchColumn();
}

function kk_order_chat_unread_total_customer(PDO $pdo, int $customerUserId): int
{
    kk_order_messages_ensure_schema($pdo);
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM order_messages m
        JOIN orders o ON o.id = m.order_id
        WHERE o.user_id = ? AND m.sender_role = \'rider\' AND m.read_at_customer IS NULL
    ');
    $stmt->execute([$customerUserId]);

    return (int) $stmt->fetchColumn();
}

function kk_order_chat_unread_total_rider(PDO $pdo, int $riderUserId): int
{
    kk_order_messages_ensure_schema($pdo);
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM order_messages m
        JOIN orders o ON o.id = m.order_id
        WHERE o.rider_id = ? AND m.sender_role = \'user\' AND m.read_at_rider IS NULL
    ');
    $stmt->execute([$riderUserId]);

    return (int) $stmt->fetchColumn();
}
