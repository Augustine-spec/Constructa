<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $project_id = (int)$_POST['project_id'];
    $stage_idx = (int)$_POST['stage_idx'];
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'];

    $target_dir = "../uploads/projects/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = basename($file['name']);
    $unique_name = time() . '_' . $file_name;
    $target_file = $target_dir . $unique_name;
    $file_size = $file['size'];

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("INSERT INTO project_documents (project_id, stage_idx, file_name, file_path, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $file_path = "uploads/projects/" . $unique_name;
        $stmt->bind_param("iissii", $project_id, $stage_idx, $file_name, $file_path, $file_size, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'document' => [
                    'id' => $stmt->insert_id,
                    'file_name' => $file_name,
                    'file_path' => $file_path,
                    'file_size' => $file_size,
                    'uploaded_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
