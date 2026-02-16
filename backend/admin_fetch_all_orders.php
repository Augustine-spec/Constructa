<?php
// backend/admin_fetch_all_orders.php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Check if admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$conn = getDatabaseConnection();

try {
    // Fetch all orders with user details
    $sql = "SELECT m.id, m.user_id, m.project_name, m.items, m.total_amount, m.delivery_stage, m.created_at, m.tracking_history, u.name AS full_name, u.email 
            FROM material_orders m 
            LEFT JOIN users u ON m.user_id = u.id 
            ORDER BY m.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];

    while ($row = $result->fetch_assoc()) {
        // Decode items
        $row['items'] = json_decode($row['items'], true);
        $row['tracking_history'] = json_decode($row['tracking_history'], true);
        $orders[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $orders]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
