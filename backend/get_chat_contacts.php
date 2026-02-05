<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDatabaseConnection();

// Find users who have exchanged messages with the current user
// We want distinct other users from both sent and received messages
$sql = "
    SELECT DISTINCT u.id, u.name, u.role, u.profile_picture 
    FROM users u
    JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = ?) 
                    OR (m.receiver_id = u.id AND m.sender_id = ?)
    WHERE u.id != ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$contacts = [];
while ($row = $result->fetch_assoc()) {
    // Get last message for preview
    $lastMsgSql = "SELECT message_text, sent_at FROM messages 
                   WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
                   ORDER BY sent_at DESC LIMIT 1";
    $lmStmt = $conn->prepare($lastMsgSql);
    $lmStmt->bind_param("iiii", $user_id, $row['id'], $row['id'], $user_id);
    $lmStmt->execute();
    $lmResult = $lmStmt->get_result();
    $lastMsg = $lmResult->fetch_assoc();
    
    $contacts[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'role' => $row['role'],
        'avatar' => strtoupper(substr($row['name'], 0, 2)), // simplified avatar fallback
        'last_message' => $lastMsg ? $lastMsg['message_text'] : '',
        'time' => $lastMsg ? date('h:i A', strtotime($lastMsg['sent_at'])) : ''
    ];
    $lmStmt->close();
}

echo json_encode(['status' => 'success', 'contacts' => $contacts]);

$stmt->close();
$conn->close();
?>
