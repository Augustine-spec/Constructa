<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$post_id = $_POST['post_id'] ?? 0;

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

try {
    $conn = getDatabaseConnection();

    // Begin transaction to delete related data (if foreign keys don't cascade)
    // Assuming Foreign Keys are set to CASCADE, but for safety lets act like they might not be or we want to be explicit.
    // Actually, simple delete is often enough if DB is set up right. I will assume standard delete from posts table is sufficient 
    // but I'll add a check.
    
    // First check if post exists
    $check = $conn->prepare("SELECT id FROM engineer_posts WHERE id = ?");
    $check->bind_param("i", $post_id);
    $check->execute();
    if($check->get_result()->num_rows === 0) {
        throw new Exception("Post not found");
    }

    $stmt = $conn->prepare("DELETE FROM engineer_posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }

    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
