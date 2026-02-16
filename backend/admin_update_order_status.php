<?php
// backend/admin_update_order_status.php
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
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate inputs
    if (!isset($data['order_id']) || !isset($data['new_status'])) {
        throw new Exception('Invalid input data');
    }

    $orderId = (int)$data['order_id'];
    $newStatus = trim($data['new_status']);
    
    // Validate status
    $validStatuses = ['Requested', 'Engineer Approved', 'Vendor Packed', 'In Transit', 'At Site', 'Verified', 'Cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        throw new Exception('Invalid status value');
    }

    // Begin transaction
    $conn->begin_transaction();

    // 1. Fetch current tracking history
    $stmt = $conn->prepare("SELECT tracking_history FROM material_orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        throw new Exception('Order not found');
    }

    $history = json_decode($row['tracking_history'], true);
    if (!is_array($history)) {
        $history = [];
    }

    // 2. Add new history entry
    $history[] = [
        'status' => $newStatus,
        'timestamp' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id'], // Admin ID
        'note' => isset($data['note']) ? $data['note'] : 'Status updated by admin'
    ];

    $newHistoryJson = json_encode($history);

    // 3. Update Status and History
    $sql = "UPDATE material_orders SET delivery_stage = ?, tracking_history = ? WHERE id = ?";
    $updateStmt = $conn->prepare($sql);
    $updateStmt->bind_param("ssi", $newStatus, $newHistoryJson, $orderId);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Database update failed');
    }

    $conn->commit();
    
    echo json_encode(['status' => 'success', 'message' => 'Order status updated successfully']);

} catch (Exception $e) {
    if ($conn && $conn->connect_errno == 0 && isset($conn->in_transaction) && $conn->in_transaction) {
        $conn->rollback();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
