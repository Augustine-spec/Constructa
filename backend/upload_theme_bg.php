<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

if (!isset($_FILES['bg_image']) || $_FILES['bg_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
    exit();
}

$uploadDir = '../uploads/chat_backgrounds/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory']);
        exit();
    }
}

$fileTmpPath = $_FILES['bg_image']['tmp_name'];
$fileName = $_FILES['bg_image']['name'];
$fileType = $_FILES['bg_image']['type'];

// Allow only images
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP allowed']);
    exit();
}

// Generate unique name
$newFileName = 'bg_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
$destPath = $uploadDir . $newFileName;

if(move_uploaded_file($fileTmpPath, $destPath)) {
    // Return the relative URL to be stored in frontend
    $webPath = 'uploads/chat_backgrounds/' . $newFileName;
    echo json_encode(['status' => 'success', 'url' => $webPath]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
}
?>
