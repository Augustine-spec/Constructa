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

$contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($contact_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid contact ID']);
    exit();
}

$conn = getDatabaseConnection();

// 1. Find attachments to delete files
$query = "SELECT attachment_url FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $user_id, $contact_id, $contact_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (!empty($row['attachment_url'])) {
        $filePath = '../' . $row['attachment_url'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
$stmt->close();

// 2. Delete the messages from DB
$delQuery = "DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)";
$delStmt = $conn->prepare($delQuery);
$delStmt->bind_param("iiii", $user_id, $contact_id, $contact_id, $user_id);

if ($delStmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Chat history cleared']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to clear chat']);
}

$delStmt->close();
$conn->close();
?>
