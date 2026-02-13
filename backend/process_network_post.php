<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$category = $_POST['category'] ?? 'General';
$media_url = null;
$media_type = 'text';

// Validate content (must have text OR file)
if (empty($content) && empty($_FILES['media']['name']) && empty($_FILES['blueprint']['name'])) {
    echo json_encode(['success' => false, 'message' => 'Post cannot be empty.']);
    exit();
}

try {
    $conn = getDatabaseConnection();
    
    // File Upload Handling
    $upload_dir = '../uploads/posts/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($_FILES['media']['name'])) {
        $file = $_FILES['media'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $filename = uniqid('post_img_') . '.' . $ext;
            $destination = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $media_url = 'uploads/posts/' . $filename;
                $media_type = 'image';
            }
        }
    } elseif (!empty($_FILES['blueprint']['name'])) {
        $file = $_FILES['blueprint'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($ext === 'pdf') {
            $filename = uniqid('post_doc_') . '.' . $ext;
            $destination = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $media_url = 'uploads/posts/' . $filename;
                $media_type = 'document'; // or 'blueprint'
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO engineer_posts (user_id, content, media_url, media_type, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $content, $media_url, $media_type, $category);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }

    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
