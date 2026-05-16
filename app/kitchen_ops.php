<?php

require_once __DIR__ . '/staff.php';

function kk_kitchen_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    foreach ([
        "order_channel ENUM('website','pos','doordash','ubereats','grabfood','phone') NOT NULL DEFAULT 'website'",
        "kitchen_status ENUM('new','in_preparation','ready_pickup','dispatched','served','cancelled') NOT NULL DEFAULT 'new'",
        'kitchen_priority TINYINT NOT NULL DEFAULT 0',
        'pos_ticket_no VARCHAR(24) NULL',
    ] as $col) {
        try {
            $pdo->exec('ALTER TABLE orders ADD COLUMN ' . $col);
        } catch (PDOException $e) {
            // exists
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kitchen_inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            sku VARCHAR(40) NOT NULL,
            name VARCHAR(120) NOT NULL,
            unit VARCHAR(20) NOT NULL DEFAULT 'pcs',
            qty_on_hand DECIMAL(12,3) NOT NULL DEFAULT 0,
            reorder_level DECIMAL(12,3) NOT NULL DEFAULT 0,
            cost_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0,
            supplier_name VARCHAR(120) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY shop_sku (shop_id, sku),
            KEY shop_id (shop_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kitchen_inventory_moves (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inventory_id INT NOT NULL,
            shop_id INT NOT NULL,
            delta_qty DECIMAL(12,3) NOT NULL,
            reason ENUM('sale','adjustment','waste','receive','recipe_deduct') NOT NULL,
            order_id INT NULL,
            user_id INT NULL,
            note VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY inventory_id (inventory_id),
            KEY shop_id (shop_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kitchen_recipes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            menu_id INT NOT NULL,
            yield_servings INT NOT NULL DEFAULT 1,
            prep_minutes INT NOT NULL DEFAULT 15,
            steps TEXT NULL,
            calories INT NULL,
            allergens VARCHAR(255) NULL,
            protein_g DECIMAL(6,1) NULL,
            carbs_g DECIMAL(6,1) NULL,
            fat_g DECIMAL(6,1) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY menu_recipe (menu_id),
            KEY shop_id (shop_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kitchen_recipe_ingredients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipe_id INT NOT NULL,
            inventory_id INT NOT NULL,
            quantity DECIMAL(12,3) NOT NULL,
            KEY recipe_id (recipe_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kitchen_waste_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            inventory_id INT NOT NULL,
            qty DECIMAL(12,3) NOT NULL,
            reason VARCHAR(120) NOT NULL,
            cost_impact DECIMAL(10,2) NOT NULL DEFAULT 0,
            logged_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY shop_id (shop_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kitchen_purchase_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            po_number VARCHAR(32) NOT NULL,
            supplier_name VARCHAR(120) NOT NULL,
            status ENUM('draft','sent','received','cancelled') NOT NULL DEFAULT 'draft',
            notes TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY po_number (po_number),
            KEY shop_id (shop_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kitchen_purchase_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            po_id INT NOT NULL,
            inventory_id INT NOT NULL,
            qty_ordered DECIMAL(12,3) NOT NULL,
            qty_received DECIMAL(12,3) NOT NULL DEFAULT 0,
            unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            KEY po_id (po_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/** Shop id for kitchen pages (bound manager or platform ?shop_id=). */
function kk_kitchen_resolve_shop_id(): ?int
{
    $bound = kk_staff_shop_id();
    if ($bound !== null) {
        return $bound;
    }
    if (!kk_staff_is_platform()) {
        return null;
    }
    $q = (int) ($_GET['shop_id'] ?? $_POST['shop_id'] ?? 0);
    return $q > 0 ? $q : null;
}

function kk_kitchen_require_shop_id(): int
{
    $id = kk_kitchen_resolve_shop_id();
    if ($id === null) {
        header('Location: ' . app_url('admin/dashboard.php'));
        exit;
    }
    return $id;
}

function kk_kitchen_channels(): array
{
    return [
        'website'   => ['label' => 'Website', 'icon' => 'globe', 'class' => 'channel-web'],
        'pos'       => ['label' => 'POS', 'icon' => 'cash-register', 'class' => 'channel-pos'],
        'doordash'  => ['label' => 'DoorDash', 'icon' => 'bag', 'class' => 'channel-dd'],
        'ubereats'  => ['label' => 'UberEats', 'icon' => 'bicycle', 'class' => 'channel-ue'],
        'grabfood'  => ['label' => 'GrabFood', 'icon' => 'phone', 'class' => 'channel-grab'],
        'phone'     => ['label' => 'Phone', 'icon' => 'telephone', 'class' => 'channel-phone'],
    ];
}

function kk_kitchen_statuses(): array
{
    return [
        'new'             => ['label' => 'New ticket', 'color' => 'secondary'],
        'in_preparation'  => ['label' => 'In preparation', 'color' => 'warning'],
        'ready_pickup'    => ['label' => 'Ready', 'color' => 'success'],
        'dispatched'      => ['label' => 'Dispatched', 'color' => 'primary'],
        'served'          => ['label' => 'Served / Done', 'color' => 'dark'],
        'cancelled'       => ['label' => 'Cancelled', 'color' => 'danger'],
    ];
}

function kk_kitchen_sync_order_status(PDO $pdo, int $orderId, string $kitchenStatus): void
{
    $map = [
        'new'            => 'pending',
        'in_preparation' => 'preparing',
        'ready_pickup'   => 'preparing',
        'dispatched'     => 'delivering',
        'served'         => 'completed',
        'cancelled'      => 'cancelled',
    ];
    if (!isset($map[$kitchenStatus])) {
        return;
    }
    $orderStatus = $map[$kitchenStatus];
    if ($orderStatus === 'completed') {
        $pdo->prepare("UPDATE orders SET order_status = ?, kitchen_status = ?, payment_status = 'paid' WHERE id = ?")
            ->execute([$orderStatus, $kitchenStatus, $orderId]);
        kk_kitchen_deduct_inventory_for_order($pdo, $orderId);
    } else {
        $pdo->prepare('UPDATE orders SET order_status = ?, kitchen_status = ? WHERE id = ?')
            ->execute([$orderStatus, $kitchenStatus, $orderId]);
    }
}

function kk_kitchen_deduct_inventory_for_order(PDO $pdo, int $orderId): void
{
    $order = $pdo->prepare('SELECT shop_id FROM orders WHERE id = ?');
    $order->execute([$orderId]);
    $shopId = (int) $order->fetchColumn();
    if ($shopId <= 0) {
        return;
    }

    $items = $pdo->prepare('
        SELECT oi.menu_id, oi.quantity, r.id AS recipe_id
        FROM order_items oi
        LEFT JOIN kitchen_recipes r ON r.menu_id = oi.menu_id
        WHERE oi.order_id = ?
    ');
    $items->execute([$orderId]);

    foreach ($items as $row) {
        if (empty($row['recipe_id'])) {
            continue;
        }
        $ings = $pdo->prepare('
            SELECT ri.inventory_id, ri.quantity, i.cost_per_unit
            FROM kitchen_recipe_ingredients ri
            JOIN kitchen_inventory i ON i.id = ri.inventory_id
            WHERE ri.recipe_id = ?
        ');
        $ings->execute([(int) $row['recipe_id']]);
        $qtySold = (int) $row['quantity'];
        foreach ($ings as $ing) {
            $deduct = (float) $ing['quantity'] * $qtySold;
            kk_kitchen_adjust_stock($pdo, (int) $ing['inventory_id'], $shopId, -$deduct, 'recipe_deduct', $orderId, 'Order #' . $orderId);
        }
    }
}

function kk_kitchen_adjust_stock(
    PDO $pdo,
    int $inventoryId,
    int $shopId,
    float $delta,
    string $reason,
    ?int $orderId = null,
    ?string $note = null
): void {
    $pdo->prepare('UPDATE kitchen_inventory SET qty_on_hand = qty_on_hand + ? WHERE id = ? AND shop_id = ?')
        ->execute([$delta, $inventoryId, $shopId]);

    $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    $pdo->prepare('
        INSERT INTO kitchen_inventory_moves (inventory_id, shop_id, delta_qty, reason, order_id, user_id, note)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ')->execute([$inventoryId, $shopId, $delta, $reason, $orderId, $userId, $note]);
}

function kk_kitchen_low_stock(PDO $pdo, int $shopId): array
{
    $stmt = $pdo->prepare('
        SELECT * FROM kitchen_inventory
        WHERE shop_id = ? AND reorder_level > 0 AND qty_on_hand <= reorder_level
        ORDER BY qty_on_hand ASC
    ');
    $stmt->execute([$shopId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function kk_kitchen_seed_inventory_if_empty(PDO $pdo, int $shopId): void
{
    $c = $pdo->prepare('SELECT COUNT(*) FROM kitchen_inventory WHERE shop_id = ?');
    $c->execute([$shopId]);
    if ((int) $c->fetchColumn() > 0) {
        return;
    }
    $defaults = [
        ['CHK-RAW', 'Chicken (raw)', 'kg', 50, 10, 180],
        ['OIL-FRY', 'Frying oil', 'L', 20, 5, 85],
        ['RICE', 'Rice', 'kg', 30, 8, 45],
        ['WRAP', 'Wrapper / box', 'pcs', 200, 50, 2],
        ['Sauce', 'Dipping sauce', 'L', 10, 3, 120],
    ];
    $ins = $pdo->prepare('
        INSERT INTO kitchen_inventory (shop_id, sku, name, unit, qty_on_hand, reorder_level, cost_per_unit, supplier_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    foreach ($defaults as $d) {
        $ins->execute([$shopId, $d[0], $d[1], $d[2], $d[3], $d[4], $d[5], 'Local supplier']);
    }
}

function kk_kitchen_next_po_number(PDO $pdo, int $shopId): string
{
    $n = $pdo->prepare('SELECT COUNT(*) FROM kitchen_purchase_orders WHERE shop_id = ?');
    $n->execute([$shopId]);
    return 'PO-' . $shopId . '-' . str_pad((string) ((int) $n->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
}

function kk_kitchen_generate_po_from_low_stock(PDO $pdo, int $shopId, int $userId): ?int
{
    $low = kk_kitchen_low_stock($pdo, $shopId);
    if (empty($low)) {
        return null;
    }
    $poNum = kk_kitchen_next_po_number($pdo, $shopId);
    $supplier = $low[0]['supplier_name'] ?? 'Supplier';
    $pdo->prepare('
        INSERT INTO kitchen_purchase_orders (shop_id, po_number, supplier_name, status, created_by, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ')->execute([$shopId, $poNum, $supplier, 'draft', $userId, 'Auto-generated from low-stock alerts']);

    $poId = (int) $pdo->lastInsertId();
    $line = $pdo->prepare('
        INSERT INTO kitchen_purchase_order_items (po_id, inventory_id, qty_ordered, unit_cost)
        VALUES (?, ?, ?, ?)
    ');
    foreach ($low as $item) {
        $need = max(0, (float) $item['reorder_level'] * 2 - (float) $item['qty_on_hand']);
        $line->execute([$poId, (int) $item['id'], $need, (float) $item['cost_per_unit']]);
    }
    return $poId;
}
