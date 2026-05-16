-- Krazy Crunch (restaurant id 3): add street menu + image paths under images/menus/crazy_krunch/
-- Filenames with spaces (match disk exactly):
--   beef_pares with rice.jpg  fried_chicken with rice.jpg  lumpia 3pcs.jpg  siomai 3 pcs.jpg
-- Plus: beef_pares.jpg  fried_chicken.jpg  kikiam.jpg  cheese_sticks.jpg  tempura.jpg  fries.jpg  nuts.jpg

INSERT INTO `menus` (`id`, `restaurant_id`, `name`, `description`, `price`, `image`, `created_at`, `is_active`) VALUES
(21, 3, 'Beef pares', 'Slow-cooked beef in savory broth — no rice.', 55.00, 'crazy_krunch/beef_pares.jpg', NOW(), 1),
(22, 3, 'Beef pares with rice', 'Beef pares with steamed rice.', 65.00, 'crazy_krunch/beef_pares with rice.jpg', NOW(), 1),
(23, 3, 'Chicken', 'Crispy chicken serving — no rice.', 50.00, 'crazy_krunch/fried_chicken.jpg', NOW(), 1),
(24, 3, 'Chicken with rice', 'Crispy chicken with steamed rice.', 60.00, 'crazy_krunch/fried_chicken with rice.jpg', NOW(), 1),
(25, 3, 'Lumpia (3 pcs)', 'Three pieces of crispy lumpia.', 25.00, 'crazy_krunch/lumpia 3pcs.jpg', NOW(), 1),
(26, 3, 'Siomai (3 pcs)', 'Three pieces of steamed siomai.', 25.00, 'crazy_krunch/siomai 3 pcs.jpg', NOW(), 1),
(27, 3, 'Kikiam', 'Street-style kikiam, fried to order.', 20.00, 'crazy_krunch/kikiam.jpg', NOW(), 1),
(28, 3, 'Cheese sticks', 'Melty cheese sticks, golden fried.', 20.00, 'crazy_krunch/cheese_sticks.jpg', NOW(), 1),
(29, 3, 'Tempura', 'Light, crispy tempura.', 20.00, 'crazy_krunch/tempura.jpg', NOW(), 1),
(30, 3, 'Fries', 'Hot, seasoned fries.', 20.00, 'crazy_krunch/fries.jpg', NOW(), 1),
(31, 3, 'Nuts', 'Crunchy snack nuts.', 10.00, 'crazy_krunch/nuts.jpg', NOW(), 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `price` = VALUES(`price`),
    `image` = VALUES(`image`),
    `is_active` = VALUES(`is_active`);

ALTER TABLE `menus` AUTO_INCREMENT = 32;
