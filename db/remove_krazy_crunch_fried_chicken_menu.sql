-- Remove Krazy Crunch "Fried Chicken" at ₱59 (legacy menu id 9 and any duplicate rows).
-- Run this on your MySQL database, then refresh the Krazy Crunch shop page.

SET FOREIGN_KEY_CHECKS = 0;

-- Line items that pointed at the old menu
DELETE oi FROM `order_items` oi WHERE oi.`menu_id` = 9;

DELETE oi FROM `order_items` oi
INNER JOIN `menus` m ON m.`id` = oi.`menu_id`
WHERE m.`restaurant_id` = 3
  AND m.`name` = 'Fried Chicken'
  AND m.`price` = 59.00;

-- The menu card itself (shop id 3 = Krazy Crunch)
DELETE FROM `menus`
WHERE `id` = 9
   OR (`restaurant_id` = 3 AND `name` = 'Fried Chicken' AND `price` = 59.00);

SET FOREIGN_KEY_CHECKS = 1;
