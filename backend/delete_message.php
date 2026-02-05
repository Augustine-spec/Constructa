<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($message_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid message ID']);
    exit();
}

$conn = getDatabaseConnection();

// Verify ownership and delete (soft delete or hard delete - going with hard delete for 'unsend' effect)
// Also delete attachment if exists? For now just delete the record.
$check = $conn->prepare("SELECT sender_id, attachment_url FROM messages WHERE id = ?");
$check->bind_param("i", $message_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Message not found']);
    exit();
}

$row = $result->fetch_assoc();
if ($row['sender_id'] !== $user_id) {
    echo json_encode(['status' => 'error', 'message' => 'You can only delete your own messages']);
    exit();
}

// Delete attachment file if exists
if (!empty($row['attachment_url'])) {
    $filePath = '../' . $row['attachment_url'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

$stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
$stmt->bind_param("i", $message_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Message unsent']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete message']);
}

$stmt->close();
$conn->close();
?>
