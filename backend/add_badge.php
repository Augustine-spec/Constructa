<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$badge_title = $_POST['title'] ?? '';
$badge_desc = $_POST['description'] ?? '';
$badge_icon = $_POST['icon'] ?? 'fas fa-trophy';

if (empty($badge_title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit();
}

try {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("INSERT INTO engineer_achievements (user_id, badge_title, badge_description, badge_icon) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $badge_title, $badge_desc, $badge_icon);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Badge added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add badge']);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
