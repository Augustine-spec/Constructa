<?php
/**
 * Update Engineer Application Status
 * Allows admin to approve or reject engineer applications
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once 'config.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['user_id']) || !isset($data['status'])) {
        throw new Exception('User ID and status are required');
    }
    
    $userId = intval($data['user_id']);
    $newStatus = trim($data['status']);
    
    // Validate status
    if (!in_array($newStatus, ['pending', 'approved', 'rejected'])) {
        throw new Exception('Invalid status value');
    }
    
    $conn = getDatabaseConnection();
    
    // Verify the user is an engineer
    $stmt = $conn->prepare("SELECT id, email, role FROM users WHERE id = ? AND role = 'engineer' LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Engineer not found');
    }
    
    $engineer = $result->fetch_assoc();
    
    // Update status
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $userId);
    
    if ($stmt->execute()) {
        // TODO: Send email notification to engineer about status change
        // For now, we'll just return success
        
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'user_id' => $userId,
            'new_status' => $newStatus
        ]);
    } else {
        throw new Exception('Failed to update status');
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
