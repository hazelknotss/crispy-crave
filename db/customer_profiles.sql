-- Customer profile & saved payment methods (auto-created by app/customer_profile.php)
CREATE TABLE IF NOT EXISTS `customer_profiles` (
  `user_id` int(11) NOT NULL,
  `phone` varchar(24) DEFAULT NULL,
  `gcash_number` varchar(20) DEFAULT NULL,
  `gcash_account_name` varchar(100) DEFAULT NULL,
  `bank_name` varchar(80) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(40) DEFAULT NULL,
  `card_holder_name` varchar(100) DEFAULT NULL,
  `card_last4` char(4) DEFAULT NULL,
  `card_brand` varchar(24) DEFAULT NULL,
  `card_exp_month` tinyint(3) UNSIGNED DEFAULT NULL,
  `card_exp_year` smallint(5) UNSIGNED DEFAULT NULL,
  `preferred_payment` enum('cod','gcash','bank','card') DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
