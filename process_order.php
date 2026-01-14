<?php
// process_order.php - Backend script to handle material orders

session_start();
header('Content-Type: application/json');

// Database connection
require_once 'db_connect.php';

// Get JSON input
$input = file_get_contents('php://input');
$orderData = json_decode($input, true);

// Validate input
if (!$orderData || !isset($orderData['items']) || !isset($orderData['total'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to place an order']);
    exit;
}

$userId = $_SESSION['user_id'];
$items = $orderData['items'];
$total = $orderData['total'];
$timestamp = $orderData['timestamp'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Insert order
    $stmt = $conn->prepare("INSERT INTO material_orders (user_id, total_amount, order_date, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("ids", $userId, $total, $timestamp);
    $stmt->execute();
    
    $orderId = $conn->insert_id;
    
    // Insert order items
    $stmt = $conn->prepare("INSERT INTO material_order_items (order_id, product_name, product_price, product_unit, quantity, item_total) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($items as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $stmt->bind_param("isdsid", 
            $orderId, 
            $item['name'], 
            $item['price'], 
            $item['unit'], 
            $item['quantity'], 
            $itemTotal
        );
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Send success response
    echo json_encode([
        'success' => true,
        'orderId' => $orderId,
        'message' => 'Order placed successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
