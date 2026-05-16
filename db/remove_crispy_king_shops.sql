-- Removes the duplicate Crispy King branch (restaurant id 2 only) and its menus.
-- Keeps one Crispy King (id 1) and Krazy Crunch (id 3).
-- Safe if id 2 is already absent.

SET FOREIGN_KEY_CHECKS = 0;

DELETE oi FROM order_items oi
INNER JOIN orders o ON o.id = oi.order_id
WHERE o.shop_id = 2;

DELETE FROM orders WHERE shop_id = 2;

DELETE oi FROM order_items oi
INNER JOIN menus m ON m.id = oi.menu_id
WHERE m.restaurant_id = 2;

DELETE FROM `users` WHERE `restaurant_id` = 2;

DELETE FROM menus WHERE restaurant_id = 2;

DELETE FROM restaurants WHERE id = 2;

SET FOREIGN_KEY_CHECKS = 1;
