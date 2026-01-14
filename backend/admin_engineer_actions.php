<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

// Validate admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin access required.'
    ]));
}

// Get parameters
$engineer_id = $_POST['engineer_id'] ?? null;
$action = $_POST['action'] ?? null; // 'verify', 'suspend', 'activate'

// Validate inputs
if (!$engineer_id || !is_numeric($engineer_id)) {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid engineer ID'
    ]));
}

if (!in_array($action, ['verify', 'suspend', 'activate'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]));
}

try {
    $conn = getDatabaseConnection();
    
    // Verify engineer exists
    $stmt_check = $conn->prepare("SELECT id, name, status FROM users WHERE id = ? AND role = 'engineer'");
    $stmt_check->bind_param("i", $engineer_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        die(json_encode([
            'success' => false,
            'message' => 'Engineer not found'
        ]));
    }
    
    $engineer = $result->fetch_assoc();
    
    // Perform action
    $new_status = '';
    $message = '';
    
    switch ($action) {
        case 'verify':
            if ($engineer['status'] === 'approved') {
                die(json_encode([
                    'success' => false,
                    'message' => 'Engineer is already verified'
                ]));
            }
            $new_status = 'approved';
            $message = 'Engineer verified successfully';
            break;
            
        case 'suspend':
            if ($engineer['status'] === 'rejected') {
                die(json_encode([
                    'success' => false,
                    'message' => 'Engineer is already suspended'
                ]));
            }
            $new_status = 'rejected';
            $message = 'Engineer account suspended';
            break;
            
        case 'activate':
            $new_status = 'approved';
            $message = 'Engineer account activated';
            break;
    }
    
    // Update status
    $stmt_update = $conn->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt_update->bind_param("si", $new_status, $engineer_id);
    
    if ($stmt_update->execute()) {
        // Log admin action (optional - can be added to an admin_logs table)
        // For now, we'll just return success
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'new_status' => $new_status,
            'engineer_name' => $engineer['name']
        ]);
    } else {
        throw new Exception('Failed to update engineer status');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
