-- Removes Andoks (restaurant id 5), its menus, related orders, and linked users.
-- Run on an existing DB that still has shop id 5 from older seed data.
-- Safe to run even if id 5 is already gone (no rows updated).

SET FOREIGN_KEY_CHECKS = 0;

DELETE oi FROM order_items oi
INNER JOIN orders o ON o.id = oi.order_id
WHERE o.shop_id = 5;

DELETE FROM order_items WHERE menu_id = 10;

DELETE FROM orders WHERE shop_id = 5;

DELETE FROM menus WHERE restaurant_id = 5 OR id = 10;

DELETE FROM `users` WHERE `restaurant_id` = 5;

DELETE FROM restaurants WHERE id = 5;

SET FOREIGN_KEY_CHECKS = 1;

-- Optional: align display names with Crispy King / Krazy Crunch
UPDATE `restaurants` SET `name` = 'Crispy King', `description` = REPLACE(`description`, 'Krispy King', 'Crispy King') WHERE `id` IN (1, 2);
UPDATE `restaurants` SET `name` = 'Krazy Crunch', `description` = 'A popular Filipino fast-food chain known for its affordable, crunchy fried chicken, often served with rice and drinks with numerous branches expanding across the Visayas (especially in Bacolod and Iloilo) and offering franchising opportunities with no royalty fees.' WHERE `id` = 3;
