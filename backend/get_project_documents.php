<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$project_id = (int)$_GET['project_id'];
$stage_idx = (int)$_GET['stage_idx'];

$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT id, file_name, file_path, file_size, uploaded_at FROM project_documents WHERE project_id = ? AND stage_idx = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("ii", $project_id, $stage_idx);
$stmt->execute();
$result = $stmt->get_result();

$documents = [];
while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}

echo json_encode(['success' => true, 'documents' => $documents]);
$conn->close();
?>
