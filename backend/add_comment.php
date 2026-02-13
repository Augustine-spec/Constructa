<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($post_id <= 0 || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

try {
    $conn = getDatabaseConnection();
    
    // Insert comment
    $stmt = $conn->prepare("INSERT INTO post_comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
    
    if ($stmt->execute()) {
        $comment_id = $stmt->insert_id;
        
        // Fetch user details for immediate display response if needed (frontend already handles basic display)
        $userStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $userStmt->bind_param("i", $user_id);
        $userStmt->execute();
        $userRes = $userStmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'comment_id' => $comment_id,
            'user_name' => $userRes['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
