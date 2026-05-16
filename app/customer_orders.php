<?php

function kk_customer_order_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $cols = $pdo->query("SHOW COLUMNS FROM orders LIKE 'cancel_reason'")->fetch();
    if (!$cols) {
        $pdo->exec("
            ALTER TABLE orders
            ADD COLUMN cancel_reason VARCHAR(500) NULL DEFAULT NULL,
            ADD COLUMN cancelled_at DATETIME NULL DEFAULT NULL
        ");
    }
}

/**
 * @return list<string>
 */
function kk_customer_cancel_reasons(): array
{
    return [
        'changed_mind'   => 'Changed my mind',
        'wrong_order'    => 'Ordered by mistake',
        'too_long'       => 'Taking too long',
        'wrong_address'  => 'Wrong address or items',
        'found_elsewhere'=> 'Found another option',
        'other'          => 'Other',
    ];
}

function kk_customer_is_pickup(array $order): bool
{
    $barangay = (string) ($order['barangay'] ?? '');
    $addr = (string) ($order['delivery_address'] ?? '');

    return stripos($barangay, 'pickup') !== false || stripos($addr, 'pickup') !== false;
}

function kk_customer_can_cancel(array $order): bool
{
    $status = strtolower((string) ($order['order_status'] ?? 'pending'));
    $ds = strtolower((string) ($order['delivery_status'] ?? ''));

    if (in_array($status, ['completed', 'cancelled'], true)) {
        return false;
    }

    // No cancel once rider has picked up or order is marked delivered
    if (in_array($ds, ['picked_up', 'on_the_way', 'delivered'], true)) {
        return false;
    }

    if (in_array($status, ['pending', 'preparing'], true)) {
        return true;
    }

    if ($status === 'delivering') {
        return in_array($ds, ['assigned', ''], true);
    }

    return false;
}

/**
 * @return array<int, array{key:string,label:string,desc:string,state:string}>
 */
function kk_customer_tracking_steps(array $order): array
{
    $orderStatus = strtolower((string) ($order['order_status'] ?? 'pending'));
    $deliveryStatus = strtolower((string) ($order['delivery_status'] ?? 'assigned'));
    $hasRider = !empty($order['rider_id']);
    $pickup = kk_customer_is_pickup($order);

    if ($orderStatus === 'cancelled') {
        return [
            [
                'key'   => 'cancelled',
                'label' => 'Order cancelled',
                'desc'  => !empty($order['cancel_reason'])
                    ? (string) $order['cancel_reason']
                    : 'This order was cancelled.',
                'state' => 'cancelled',
            ],
        ];
    }

    $defs = [
        ['key' => 'placed', 'label' => 'Order placed', 'desc' => 'We received your order'],
        ['key' => 'preparing', 'label' => 'Preparing', 'desc' => 'The kitchen is preparing your food'],
    ];

    if ($pickup) {
        $defs[] = ['key' => 'ready', 'label' => 'Ready for pickup', 'desc' => 'Head to the store when ready'];
    } else {
        $defs[] = ['key' => 'delivering', 'label' => 'Out for delivery', 'desc' => 'Your order is being prepared for delivery'];
        $defs[] = ['key' => 'rider_assigned', 'label' => 'Rider assigned', 'desc' => 'A rider will pick up your order'];
        $defs[] = ['key' => 'picked_up', 'label' => 'Picked up', 'desc' => 'Rider collected your order from the kitchen'];
        $defs[] = ['key' => 'on_the_way', 'label' => 'On the way', 'desc' => 'Rider is heading to your address'];
        $defs[] = ['key' => 'delivered', 'label' => 'Delivered', 'desc' => 'Order handed to you'];
    }

    $defs[] = ['key' => 'completed', 'label' => 'Completed', 'desc' => $pickup ? 'Enjoy your meal!' : 'Thank you for ordering'];

    $currentKey = 'placed';
    if ($orderStatus === 'completed' || $deliveryStatus === 'delivered') {
        $currentKey = 'completed';
    } elseif ($deliveryStatus === 'on_the_way') {
        $currentKey = 'on_the_way';
    } elseif ($deliveryStatus === 'picked_up') {
        $currentKey = 'picked_up';
    } elseif ($orderStatus === 'delivering' && $hasRider && $deliveryStatus === 'assigned') {
        $currentKey = 'rider_assigned';
    } elseif ($orderStatus === 'delivering') {
        $currentKey = $pickup ? 'ready' : 'delivering';
    } elseif ($orderStatus === 'preparing') {
        $currentKey = 'preparing';
    } elseif ($orderStatus === 'pending') {
        $currentKey = 'placed';
    }

    $keys = array_column($defs, 'key');
    $currentIdx = array_search($currentKey, $keys, true);
    if ($currentIdx === false) {
        $currentIdx = 0;
    }

    $steps = [];
    foreach ($defs as $i => $def) {
        if ($i < $currentIdx) {
            $state = 'done';
        } elseif ($i === $currentIdx) {
            $state = $orderStatus === 'completed' || $currentKey === 'completed' ? 'done' : 'current';
        } else {
            $state = 'upcoming';
        }
        if ($orderStatus === 'completed') {
            $state = 'done';
        }
        $steps[] = array_merge($def, ['state' => $state]);
    }

    return $steps;
}

function kk_customer_delivery_status_label(array $order): string
{
    $orderStatus = strtolower((string) ($order['order_status'] ?? 'pending'));
    $deliveryStatus = strtolower((string) ($order['delivery_status'] ?? 'assigned'));

    if ($orderStatus === 'cancelled') {
        return 'Cancelled';
    }
    if ($orderStatus === 'completed') {
        return 'Completed';
    }
    if ($orderStatus === 'pending') {
        return 'Order received';
    }
    if ($orderStatus === 'preparing') {
        return 'Preparing your order';
    }
    if (kk_customer_is_pickup($order)) {
        return 'Ready for pickup soon';
    }

    return match ($deliveryStatus) {
        'picked_up'  => 'Rider picked up your order',
        'on_the_way' => 'Rider is on the way',
        'delivered'  => 'Delivered',
        default      => !empty($order['rider_id']) ? 'Rider assigned' : 'Waiting for rider',
    };
}
