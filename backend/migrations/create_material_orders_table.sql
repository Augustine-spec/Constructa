-- Create material_orders table for storing Razorpay payment orders

CREATE TABLE IF NOT EXISTS `material_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `items` text NOT NULL COMMENT 'JSON encoded cart items',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_id` varchar(255) NOT NULL COMMENT 'Razorpay payment ID',
  `razorpay_order_id` varchar(255) NOT NULL COMMENT 'Razorpay order ID',
  `status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payment_id` (`payment_id`),
  KEY `razorpay_order_id` (`razorpay_order_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
