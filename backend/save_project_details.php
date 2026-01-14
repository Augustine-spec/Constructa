<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$project_id = (int)$data['project_id'];
$details = json_encode($data['details']);

$conn = getDatabaseConnection();
$stmt = $conn->prepare("UPDATE project_requests SET project_details = ? WHERE id = ?");
$stmt->bind_param("si", $details, $project_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>
