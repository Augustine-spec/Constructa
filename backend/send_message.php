<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}


$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message_text = isset($_POST['message_text']) ? trim($_POST['message_text']) : '';

// File Upload Handling
$attachment_url = null;
$attachment_type = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/chat_attachments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmpPath = $_FILES['attachment']['tmp_name'];
    $fileName = $_FILES['attachment']['name'];
    $fileSize = $_FILES['attachment']['size'];
    $fileType = $_FILES['attachment']['type'];
    
    // Generate unique name
    $newFileName = uniqid('chat_', true) . '_' . preg_replace('/[^a-zA-Z0-9.]+/', '_', $fileName);
    $destPath = $uploadDir . $newFileName;

    if(move_uploaded_file($fileTmpPath, $destPath)) {
        $attachment_url = 'uploads/chat_attachments/' . $newFileName;
        
        // Determine simple type
        if (strpos($fileType, 'image') !== false) {
            $attachment_type = 'image';
        } elseif (strpos($fileType, 'video') !== false) {
            $attachment_type = 'video';
        } elseif (strpos($fileType, 'audio') !== false) {
            $attachment_type = 'audio';
        } else {
            $attachment_type = 'document'; // Default
        }
    }
}

// Validation: Must have text OR attachment
if ($receiver_id === 0 || (empty($message_text) && empty($attachment_url))) {
    echo json_encode(['status' => 'error', 'message' => 'Message or attachment required']);
    exit();
}

$conn = getDatabaseConnection();
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, attachment_url, attachment_type) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $sender_id, $receiver_id, $message_text, $attachment_url, $attachment_type);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Message sent', 'attach' => $attachment_url]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
