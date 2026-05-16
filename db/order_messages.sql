-- Per-order chat between customer and assigned rider
CREATE TABLE IF NOT EXISTS `order_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `sender_user_id` int NOT NULL,
  `sender_role` enum('user','rider') NOT NULL,
  `body` text NOT NULL,
  `read_at_customer` datetime DEFAULT NULL,
  `read_at_rider` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
