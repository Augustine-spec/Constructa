<?php
session_start();
require_once 'config.php';
$conn = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch unread count
// We check global broadcasts, role-specific broadcasts, and individual broadcasts
// that haven't been entered into user_notifications or are entered as unread

$sql = "SELECT COUNT(*) as unread FROM notification_broadcasts b
        LEFT JOIN user_notifications u ON b.id = u.broadcast_id AND u.user_id = ?
        WHERE (u.is_read IS NULL OR u.is_read = 0)
        AND (
            b.target_type = 'GLOBAL' 
            OR (b.target_type = 'ROLE' AND b.target_value = ?)
            OR (b.target_type = 'INDIVIDUAL' AND b.target_value = ?)
        )
        AND b.status = 'SENT'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $role, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['unread' => $row['unread']]);
?>
