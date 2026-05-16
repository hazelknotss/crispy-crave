<?php

/**
 * Pick an approved rider for a shop (least active deliveries first).
 */
function kk_find_rider_for_shop(PDO $pdo, int $shopId): ?int
{
    $stmt = $pdo->prepare("
        SELECT u.id
        FROM users u
        LEFT JOIN orders o ON o.rider_id = u.id
            AND COALESCE(o.delivery_status, 'assigned') <> 'delivered'
        WHERE u.role = 'rider'
          AND u.approval_status = 'approved'
          AND (u.restaurant_id = :shop_id OR u.restaurant_id IS NULL)
        GROUP BY u.id
        ORDER BY COUNT(o.id) ASC, u.id ASC
        LIMIT 1
    ");
    $stmt->execute(['shop_id' => $shopId]);
    $riderId = $stmt->fetchColumn();

    if ($riderId !== false) {
        return (int) $riderId;
    }

    $fallback = $pdo->query("
        SELECT id FROM users
        WHERE role = 'rider' AND approval_status = 'approved'
        ORDER BY id ASC
        LIMIT 1
    ");
    $id = $fallback ? $fallback->fetchColumn() : false;

    return $id !== false ? (int) $id : null;
}

function kk_order_needs_rider(string $barangay, string $deliveryAddress): bool
{
    if (stripos($barangay, 'pickup') !== false) {
        return false;
    }
    if (stripos($deliveryAddress, 'pickup') !== false) {
        return false;
    }

    return true;
}

/**
 * Assign rider_id on a delivery order. Returns assigned rider user id or null.
 */
function kk_auto_assign_rider(PDO $pdo, int $orderId, int $shopId, string $barangay, string $deliveryAddress): ?int
{
    if (!kk_order_needs_rider($barangay, $deliveryAddress)) {
        return null;
    }

    $riderId = kk_find_rider_for_shop($pdo, $shopId);
    if ($riderId === null) {
        return null;
    }

    $stmt = $pdo->prepare("
        UPDATE orders
        SET rider_id = ?,
            delivery_status = 'assigned'
        WHERE id = ?
          AND (rider_id IS NULL OR rider_id = 0)
    ");
    $stmt->execute([$riderId, $orderId]);

    if ($stmt->rowCount() > 0) {
        require_once __DIR__ . '/rider_portal.php';
        if (!function_exists('app_url')) {
            require_once __DIR__ . '/url.php';
        }
        kk_rider_notify(
            $pdo,
            $riderId,
            'New delivery assigned',
            'Order #' . $orderId . ' is ready for pickup. Open your dashboard for details.',
            app_url('rider/order-details.php?id=' . $orderId)
        );

        return $riderId;
    }

    return null;
}

/**
 * Assign any delivery orders that still have no rider (e.g. placed before auto-assign existed).
 */
function kk_backfill_unassigned_delivery_orders(PDO $pdo): int
{
    $rows = $pdo->query("
        SELECT id, shop_id, barangay, delivery_address
        FROM orders
        WHERE (rider_id IS NULL OR rider_id = 0)
        ORDER BY created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($rows as $row) {
        if (!kk_order_needs_rider((string) $row['barangay'], (string) $row['delivery_address'])) {
            continue;
        }
        if (kk_auto_assign_rider(
            $pdo,
            (int) $row['id'],
            (int) $row['shop_id'],
            (string) $row['barangay'],
            (string) $row['delivery_address']
        ) !== null) {
            $count++;
        }
    }

    return $count;
}

/**
 * Orders visible to a logged-in rider (assigned to them + open pool for their shop).
 *
 * @return array<int, array<string, mixed>>
 */
function kk_fetch_rider_orders(PDO $pdo, int $riderId, ?int $restaurantId): array
{
    if ($restaurantId !== null && $restaurantId > 0) {
        $stmt = $pdo->prepare("
            SELECT o.*
            FROM orders o
            WHERE o.rider_id = :rider_id
               OR (
                    (o.rider_id IS NULL OR o.rider_id = 0)
                    AND o.shop_id = :shop_id
                    AND o.barangay NOT LIKE '%pickup%'
                    AND o.delivery_address NOT LIKE '%pickup%'
               )
            ORDER BY
                CASE WHEN o.rider_id = :rider_id2 THEN 0 ELSE 1 END,
                o.created_at DESC
        ");
        $stmt->execute([
            'rider_id'  => $riderId,
            'shop_id'   => $restaurantId,
            'rider_id2' => $riderId,
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT o.*
            FROM orders o
            WHERE o.rider_id = :rider_id
               OR (
                    (o.rider_id IS NULL OR o.rider_id = 0)
                    AND o.barangay <> 'Store pickup'
                    AND o.delivery_address NOT LIKE '%pickup%'
               )
            ORDER BY
                CASE WHEN o.rider_id = :rider_id2 THEN 0 ELSE 1 END,
                o.created_at DESC
        ");
        $stmt->execute([
            'rider_id'  => $riderId,
            'rider_id2' => $riderId,
        ]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Load one order for a rider; claims unassigned pool orders for their shop.
 *
 * @return array<string, mixed>|null
 */
function kk_rider_get_order(PDO $pdo, int $orderId, int $riderId, ?int $restaurantId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        return null;
    }

    $assigned = (int) ($order['rider_id'] ?? 0);

    if ($assigned === $riderId) {
        return $order;
    }

    if ($assigned > 0) {
        return null;
    }

    if (!kk_order_needs_rider((string) $order['barangay'], (string) $order['delivery_address'])) {
        return null;
    }

    $shopId = (int) $order['shop_id'];
    if ($restaurantId !== null && $restaurantId > 0 && $shopId !== $restaurantId) {
        return null;
    }

    $claim = $pdo->prepare('
        UPDATE orders
        SET rider_id = ?, delivery_status = \'assigned\'
        WHERE id = ? AND (rider_id IS NULL OR rider_id = 0)
    ');
    $claim->execute([$riderId, $orderId]);

    if ($claim->rowCount() < 1) {
        return null;
    }

    $stmt->execute([$orderId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Build a clean address string for Google Maps (street + barangay, no order notes).
 */
function kk_maps_destination(string $deliveryAddress, string $barangay): string
{
    $block = trim($deliveryAddress);

    if (preg_match('/^(.+?)(?:\r\n|\n)\s*(?:\r\n|\n)/s', $block, $m)) {
        $block = trim($m[1]);
    }

    $lines = preg_split('/\r\n|\r|\n/', $block);
    $street = trim($lines[0] ?? $block);

    $street = preg_replace(
        '/\s*(Scheduled delivery|Delivery option|Preferred payment|Customer notes|Fulfillment:).*$/iu',
        '',
        $street
    );
    $street = trim($street, " \t,;");

    if ($street === '') {
        $street = trim($block);
    }

    $parts = [];
    if ($street !== '') {
        $parts[] = $street;
    }

    $b = trim($barangay);
    if ($b !== '' && stripos($b, 'pickup') === false) {
        $lowerStreet = strtolower($street);
        if ($b !== '' && stripos($lowerStreet, strtolower($b)) === false) {
            $parts[] = $b;
        }
    }

    $parts[] = 'Pototan';
    $parts[] = 'Iloilo';
    $parts[] = 'Philippines';

    return implode(', ', array_unique(array_filter($parts)));
}
