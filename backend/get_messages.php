<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$other_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($other_user_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing user ID']);
    exit();
}

$conn = getDatabaseConnection();

// Mark messages as read if I am the receiver
$updateStmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$updateStmt->bind_param("ii", $other_user_id, $user_id);
$updateStmt->execute();
$updateStmt->close();

// Fetch messages
$sql = "SELECT m.*, 
        CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as type
        FROM messages m 
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?) 
        ORDER BY m.sent_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $user_id, $user_id, $other_user_id, $other_user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['id'],
        'message' => $row['message_text'],
        'time' => date('h:i A', strtotime($row['sent_at'])),
        'type' => $row['type'],
        'attachment_url' => $row['attachment_url'],
        'attachment_type' => $row['attachment_type']
    ];
}

echo json_encode(['status' => 'success', 'messages' => $messages]);

$stmt->close();
$conn->close();
?>
