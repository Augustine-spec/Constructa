<?php
/**
 * Get Engineer Requests for Admin Dashboard
 * Returns all engineer applications with statistics
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

require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    // Get all engineer requests
    $stmt = $conn->prepare("SELECT id, name, email, phone, role, status, specialization, experience, license_number, portfolio_url, bio, created_at FROM users WHERE role = 'engineer' ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    // Get statistics
    $stats = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
    
    foreach ($requests as $request) {
        if (isset($stats[$request['status']])) {
            $stats[$request['status']]++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'stats' => $stats
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
