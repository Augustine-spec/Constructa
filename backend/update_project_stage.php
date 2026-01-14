<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$project_id = isset($data['project_id']) ? (int)$data['project_id'] : 0;
$new_stage = isset($data['new_stage']) ? (int)$data['new_stage'] : 0;

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit();
}

try {
    $conn = getDatabaseConnection();
    
    // Verify engineer owns the project
    $stmt = $conn->prepare("SELECT id FROM project_requests WHERE id = ? AND engineer_id = ?");
    $engineer_id = $_SESSION['user_id'];
    $stmt->bind_param("ii", $project_id, $engineer_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Project not found or unauthorized']);
        exit();
    }

    // Update stage and potentially status
    if ($new_stage >= 7) {
        $stmt = $conn->prepare("UPDATE project_requests SET current_stage = ?, status = 'completed' WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE project_requests SET current_stage = ? WHERE id = ?");
    }
    $stmt->bind_param("ii", $new_stage, $project_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Project stage updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update stage']);
    }

    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
