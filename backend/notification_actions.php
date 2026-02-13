<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false]));
}

require_once 'config.php';
$conn = getDatabaseConnection();

$action = $_POST['action'] ?? '';
$notif_id = $_POST['id'] ?? 0;
$user_id = $_SESSION['user_id'];

if ($action === 'read') {
    $conn->query("INSERT INTO user_notifications (broadcast_id, user_id, is_read, read_at) 
                  VALUES ($notif_id, $user_id, 1, NOW())
                  ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()");
} elseif ($action === 'acknowledge') {
    $conn->query("INSERT INTO user_notifications (broadcast_id, user_id, is_read, read_at, is_acknowledged, acknowledged_at) 
                  VALUES ($notif_id, $user_id, 1, NOW(), 1, NOW())
                  ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW(), is_acknowledged = 1, acknowledged_at = NOW()");
}

echo json_encode(['success' => true]);
?>
