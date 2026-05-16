-- Point Krazy Crunch "Chicken" (₱50, no rice) at images/menus/crazy_krunch/fried_chicken.jpg
-- (DB stores path relative to images/menus/.)

UPDATE `menus`
SET `image` = 'crazy_krunch/fried_chicken.jpg'
WHERE `restaurant_id` = 3
  AND `name` = 'Chicken'
  AND `price` = 50.00;
