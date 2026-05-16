-- Kitchen operations: KDS, inventory, recipes, POS channels
-- Applied automatically via app/kitchen_ops.php on first use

ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `order_channel` ENUM('website','pos','doordash','ubereats','grabfood','phone') NOT NULL DEFAULT 'website',
  ADD COLUMN IF NOT EXISTS `kitchen_status` ENUM('new','in_preparation','ready_pickup','dispatched','served','cancelled') NOT NULL DEFAULT 'new',
  ADD COLUMN IF NOT EXISTS `kitchen_priority` TINYINT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `pos_ticket_no` VARCHAR(24) NULL;

CREATE TABLE IF NOT EXISTS `kitchen_inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shop_id` INT NOT NULL,
  `sku` VARCHAR(40) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
  `qty_on_hand` DECIMAL(12,3) NOT NULL DEFAULT 0,
  `reorder_level` DECIMAL(12,3) NOT NULL DEFAULT 0,
  `cost_per_unit` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `supplier_name` VARCHAR(120) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `shop_sku` (`shop_id`, `sku`),
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kitchen_inventory_moves` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `inventory_id` INT NOT NULL,
  `shop_id` INT NOT NULL,
  `delta_qty` DECIMAL(12,3) NOT NULL,
  `reason` ENUM('sale','adjustment','waste','receive','recipe_deduct') NOT NULL,
  `order_id` INT NULL,
  `user_id` INT NULL,
  `note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `inventory_id` (`inventory_id`),
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kitchen_recipes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shop_id` INT NOT NULL,
  `menu_id` INT NOT NULL,
  `yield_servings` INT NOT NULL DEFAULT 1,
  `prep_minutes` INT NOT NULL DEFAULT 15,
  `steps` TEXT NULL,
  `calories` INT NULL,
  `allergens` VARCHAR(255) NULL,
  `protein_g` DECIMAL(6,1) NULL,
  `carbs_g` DECIMAL(6,1) NULL,
  `fat_g` DECIMAL(6,1) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `menu_recipe` (`menu_id`),
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kitchen_recipe_ingredients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `recipe_id` INT NOT NULL,
  `inventory_id` INT NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL,
  KEY `recipe_id` (`recipe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kitchen_waste_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shop_id` INT NOT NULL,
  `inventory_id` INT NOT NULL,
  `qty` DECIMAL(12,3) NOT NULL,
  `reason` VARCHAR(120) NOT NULL,
  `cost_impact` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `logged_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kitchen_purchase_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shop_id` INT NOT NULL,
  `po_number` VARCHAR(32) NOT NULL,
  `supplier_name` VARCHAR(120) NOT NULL,
  `status` ENUM('draft','sent','received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `po_number` (`po_number`),
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kitchen_purchase_order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `po_id` INT NOT NULL,
  `inventory_id` INT NOT NULL,
  `qty_ordered` DECIMAL(12,3) NOT NULL,
  `qty_received` DECIMAL(12,3) NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(10,2) NOT NULL DEFAULT 0,
  KEY `po_id` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
