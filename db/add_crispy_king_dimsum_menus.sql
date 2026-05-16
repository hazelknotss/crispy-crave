-- Single Crispy King branch (restaurant id 1): dim sum under images/menus/crispy_king/
-- plus Fried Chicken / Fried Chicken with rice (images/menus/crispy_king/…).
-- Does not create a second Crispy King (old duplicate id 2 was removed from seeds).

INSERT INTO `restaurants` (`id`, `name`, `description`, `logo`, `delivery_time`, `is_active`) VALUES
(1, 'Crispy King', 'Crispy King is a fast-food business, specializing in fried chicken. The company’s first Crispy King store opened in Lopez Jaena St., Ormoc City with just 20 sqm.', 'shop_6942e2cc0b6322.91203712.png', '10-15', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `logo` = VALUES(`logo`),
    `delivery_time` = VALUES(`delivery_time`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `menus` (`id`, `restaurant_id`, `name`, `description`, `price`, `image`, `created_at`, `is_active`) VALUES
(11, 1, 'Bola-bola', 'Skewered savory meatballs — great with rice.', 10.00, 'crispy_king/bola_bola.jpg', NOW(), 1),
(12, 1, 'Ngohiong', 'Crispy Cebu-style roll with spiced filling.', 12.00, 'crispy_king/ngohiong.jpg', NOW(), 1),
(13, 1, 'Siomai', 'Steamed dumplings, hot and juicy.', 30.00, 'crispy_king/siomai.jpg', NOW(), 1),
(14, 1, 'Lumpia', 'Golden fried spring rolls.', 8.00, 'crispy_king/lumpia.jpg', NOW(), 1),
(15, 1, 'Siopao', 'Fluffy steamed bun with savory filling.', 30.00, 'crispy_king/siopao.jpg', NOW(), 1),
(16, 1, 'Fried Chicken', 'Chicken that is crunchy on the outside and juicy on the inside.', 50.00, 'crispy_king/chicken.jpg', NOW(), 1),
(17, 1, 'Fried Chicken with rice', 'Crispy fried chicken with steamed rice.', 60.00, 'crispy_king/fried_chicken with rice.jpg', NOW(), 1)
ON DUPLICATE KEY UPDATE
    `restaurant_id` = VALUES(`restaurant_id`),
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `price` = VALUES(`price`),
    `image` = VALUES(`image`),
    `is_active` = VALUES(`is_active`);

ALTER TABLE `menus` AUTO_INCREMENT = 32;
