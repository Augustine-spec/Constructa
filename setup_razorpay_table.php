<?php
// setup_razorpay_table.php - Run this file once to create the material_orders table

require_once 'backend/config.php';

try {
    $conn = getDatabaseConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `material_orders` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ SUCCESS! Table 'material_orders' created successfully.<br><br>";
        echo "You can now:<br>";
        echo "1. Close this page<br>";
        echo "2. Go back to Material Market<br>";
        echo "3. Try checkout again<br><br>";
        echo "<a href='material_market.php' style='padding: 10px 20px; background: #294033; color: white; text-decoration: none; border-radius: 5px;'>Go to Material Market</a>";
    } else {
        echo "❌ Error creating table: " . $conn->error;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Razorpay Setup</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f6f7f2;
        }
        h1 {
            color: #294033;
        }
    </style>
</head>
<body>
    <h1>🚀 Razorpay Database Setup</h1>
</body>
</html>
