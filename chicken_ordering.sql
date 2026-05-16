-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 09:26 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chicken_ordering`
--

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `restaurant_id`, `name`, `description`, `price`, `image`, `created_at`, `is_active`) VALUES
(11, 1, 'Bola-bola', 'Skewered savory meatballs — great with rice.', 10.00, 'crispy_king/bola_bola.jpg', '2026-05-14 12:00:00', 1),
(12, 1, 'Ngohiong', 'Crispy Cebu-style roll with spiced filling.', 12.00, 'crispy_king/ngohiong.jpg', '2026-05-14 12:00:00', 1),
(13, 1, 'Siomai', 'Steamed dumplings, hot and juicy.', 30.00, 'crispy_king/siomai.jpg', '2026-05-14 12:00:00', 1),
(14, 1, 'Lumpia', 'Golden fried spring rolls.', 8.00, 'crispy_king/lumpia.jpg', '2026-05-14 12:00:00', 1),
(15, 1, 'Siopao', 'Fluffy steamed bun with savory filling.', 30.00, 'crispy_king/siopao.jpg', '2026-05-14 12:00:00', 1),
(16, 1, 'Fried Chicken', 'Chicken that is crunchy on the outside and juicy on the inside.', 50.00, 'crispy_king/chicken.jpg', '2026-05-14 12:00:00', 1),
(17, 1, 'Fried Chicken with rice', 'Crispy fried chicken with steamed rice.', 60.00, 'crispy_king/fried_chicken with rice.jpg', '2026-05-14 12:00:00', 1),
(21, 3, 'Beef pares', 'Slow-cooked beef in savory broth — no rice.', 55.00, 'crazy_krunch/beef_pares.jpg', '2026-05-14 14:00:00', 1),
(22, 3, 'Beef pares with rice', 'Beef pares with steamed rice.', 65.00, 'crazy_krunch/beef_pares with rice.jpg', '2026-05-14 14:00:00', 1),
(23, 3, 'Chicken', 'Crispy chicken serving — no rice.', 50.00, 'crazy_krunch/fried_chicken.jpg', '2026-05-14 14:00:00', 1),
(24, 3, 'Chicken with rice', 'Crispy chicken with steamed rice.', 60.00, 'crazy_krunch/fried_chicken with rice.jpg', '2026-05-14 14:00:00', 1),
(25, 3, 'Lumpia (3 pcs)', 'Three pieces of crispy lumpia.', 25.00, 'crazy_krunch/lumpia 3pcs.jpg', '2026-05-14 14:00:00', 1),
(26, 3, 'Siomai (3 pcs)', 'Three pieces of steamed siomai.', 25.00, 'crazy_krunch/siomai 3 pcs.jpg', '2026-05-14 14:00:00', 1),
(27, 3, 'Kikiam', 'Street-style kikiam, fried to order.', 20.00, 'crazy_krunch/kikiam.jpg', '2026-05-14 14:00:00', 1),
(28, 3, 'Cheese sticks', 'Melty cheese sticks, golden fried.', 20.00, 'crazy_krunch/cheese_sticks.jpg', '2026-05-14 14:00:00', 1),
(29, 3, 'Tempura', 'Light, crispy tempura.', 20.00, 'crazy_krunch/tempura.jpg', '2026-05-14 14:00:00', 1),
(30, 3, 'Fries', 'Hot, seasoned fries.', 20.00, 'crazy_krunch/fries.jpg', '2026-05-14 14:00:00', 1),
(31, 3, 'Nuts', 'Crunchy snack nuts.', 10.00, 'crazy_krunch/nuts.jpg', '2026-05-14 14:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `rider_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('cod','gcash') NOT NULL,
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `gcash_ref` varchar(50) DEFAULT NULL,
  `order_status` enum('pending','preparing','delivering','completed','cancelled') NOT NULL DEFAULT 'pending',
  `delivery_address` text NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `distance_km` decimal(5,2) NOT NULL,
  `rider_fee` decimal(10,2) NOT NULL,
  `pickup_time` time NOT NULL,
  `delivery_status` enum('assigned','picked_up','on_the_way','delivered') DEFAULT 'assigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

-- (no sample orders)

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

-- (no sample order lines)

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `delivery_time` varchar(50) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `name`, `description`, `logo`, `delivery_time`, `is_active`) VALUES
(1, 'Crispy King', 'Crispy King is a fast-food business, specializing in fried chicken. The company’s first Crispy King store opened in Lopez Jaena St., Ormoc City with just 20 sqm.', 'ck.png', '10-15', 1),
(3, 'Krazy Crunch', 'A popular Filipino fast-food chain known for its affordable, crunchy fried chicken, often served with rice and drinks with numerous branches expanding across the Visayas (especially in Bacolod and Iloilo) and offering franchising opportunities with no royalty fees.', 'shop_69462b57326096.76403290.jpg', '10-20', 1);

-- --------------------------------------------------------

--
-- Table structure for table `riders`
--

CREATE TABLE `riders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `status` enum('available','busy') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','user','restaurant','rider') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `restaurant_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `approval_status`, `restaurant_id`) VALUES
(3, 'andre penalver', 'andre@gmail.com', '$2y$10$ip4Fa3hyX5u2h95cmBwuVuHsgdIG.gwSlES3MYPOPlwbadFwgNEqW', 'user', '2025-12-17 15:49:56', 'pending', NULL),
(4, 'Admin', 'admin@crispycrave.com', '$2y$10$WBCZxhsP8.tegOiyi8Ar8OEnzi/Oi/iaFS6dCdzJ944iY6tS7HB/W', 'admin', '2025-12-17 16:17:51', 'pending', NULL),
(5, 'leonel', 'leonel@gmail.com', '$2y$10$RER.sEx.9kahSZgFp/cNc.Q8peym7MyrQy89Ev54OEEA.KtJ2DQdm', 'user', '2026-01-04 10:39:43', 'pending', NULL),
(6, 'Crispy Crave Main Branch', 'restaurant@test.com', '$2y$10$9MQhe9hqR4Wo8zS.DukIN.VJ7U2QvTP2Ed0.c.aJyAfHVAqfB6gtO', 'restaurant', '2026-02-25 15:18:24', 'approved', 1),
(7, 'Juan Rider', 'rider@test.com', '$2y$10$6LmJ1jB/ZswZlLxcWYqVaOV2WY9XbnXgKwLBHvJu822cOLnltvyKa', 'rider', '2026-02-25 15:28:22', 'approved', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `riders`
--
ALTER TABLE `riders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `riders`
--
ALTER TABLE `riders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `riders`
--
ALTER TABLE `riders`
  ADD CONSTRAINT `riders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
