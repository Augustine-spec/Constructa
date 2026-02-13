<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'] ?? '';

try {
    $conn = getDatabaseConnection();
    $new_url = '';

    if ($type === 'upload') {
        // Handle File Upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            throw new Exception('Invalid file type');
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            throw new Exception('File too large (max 5MB)');
        }

        $upload_dir = '../uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = 'profile_' . $user_id . '_' . uniqid() . '.' . $ext;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $new_url = 'uploads/profiles/' . $filename;
        } else {
            throw new Exception('Failed to save file');
        }

    } elseif ($type === 'avatar') {
        // Handle Avatar Selection (DiceBear URL)
        $avatar_url = $_POST['avatar_url'] ?? '';
        if (empty($avatar_url)) {
            throw new Exception('No avatar selected');
        }
        $new_url = $avatar_url;

    } else {
        throw new Exception('Invalid update type');
    }

    // Update Database
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $new_url, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'new_url' => $new_url]);
    } else {
        throw new Exception('Database update failed');
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
