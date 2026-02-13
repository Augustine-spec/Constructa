<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'config.php';
$conn = getDatabaseConnection();

$follower_id = $_SESSION['user_id'];
$following_id = isset($_POST['engineer_id']) ? intval($_POST['engineer_id']) : 0;

if ($following_id <= 0 || $follower_id == $following_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    // Check if already following
    $check_stmt = $conn->prepare("SELECT id FROM engineer_followers WHERE follower_id = ? AND following_id = ?");
    $check_stmt->bind_param("ii", $follower_id, $following_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unfollow
        $delete_stmt = $conn->prepare("DELETE FROM engineer_followers WHERE follower_id = ? AND following_id = ?");
        $delete_stmt->bind_param("ii", $follower_id, $following_id);
        $delete_stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'action' => 'unfollowed',
            'message' => 'Unfollowed successfully'
        ]);
    } else {
        // Follow
        $insert_stmt = $conn->prepare("INSERT INTO engineer_followers (follower_id, following_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $follower_id, $following_id);
        $insert_stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'action' => 'followed',
            'message' => 'Following successfully'
        ]);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
