-- POS: menu categories (auto-applied by app/menu_ops.php on first load)
ALTER TABLE `menus` ADD COLUMN `category` VARCHAR(80) NULL DEFAULT NULL;
