<?php
/**
 * Get All Users for Admin User Management
 * Returns both engineers and homeowners with statistics
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
    
    // Get all users (except admins)
    $stmt = $conn->prepare("SELECT id, name, email, phone, role, status, specialization, experience, license_number, portfolio_url, bio, created_at FROM users WHERE role != 'admin' ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    // Get statistics
    $stats = [
        'total' => count($users),
        'homeowners' => 0,
        'engineers' => 0,
        'pending_engineers' => 0,
        'approved_engineers' => 0
    ];
    
    foreach ($users as $user) {
        if ($user['role'] === 'homeowner') {
            $stats['homeowners']++;
        } else if ($user['role'] === 'engineer') {
            $stats['engineers']++;
            if ($user['status'] === 'pending') {
                $stats['pending_engineers']++;
            } else if ($user['status'] === 'approved') {
                $stats['approved_engineers']++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users,
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
