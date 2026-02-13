<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once 'config.php';
$conn = getDatabaseConnection();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    die(json_encode(['success' => false, 'message' => 'Invalid data']));
}

$sender_id = $_SESSION['user_id'];
$category = $data['category'];
$priority = $data['priority'];
$title = $data['title'];
$content = $data['content'];
$target_type = $data['target_type'];
$target_value = $data['target_value'] ?? null;

$stmt = $conn->prepare("INSERT INTO notification_broadcasts (sender_id, category, priority, title, content, target_type, target_value, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'SENT')");
$stmt->bind_param("issssss", $sender_id, $category, $priority, $title, $content, $target_type, $target_value);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Broadcasting communication...']);
} else {
    echo json_encode(['success' => false, 'message' => 'Broadcast failure.']);
}
?>
