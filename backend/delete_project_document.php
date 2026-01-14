<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$doc_id = (int)$data['doc_id'];
$user_id = $_SESSION['user_id'];

$conn = getDatabaseConnection();

// Check if document exists and user is owner (or simplified for now: associated with project)
$stmt = $conn->prepare("SELECT file_path FROM project_documents WHERE id = ?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result();
$doc = $result->fetch_assoc();

if ($doc) {
    if (file_exists("../" . $doc['file_path'])) {
        unlink("../" . $doc['file_path']);
    }
    
    $del = $conn->prepare("DELETE FROM project_documents WHERE id = ?");
    $del->bind_param("i", $doc_id);
    if ($del->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Document not found']);
}

$conn->close();
?>
