<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';
$conn = getDatabaseConnection();

try {
    switch ($action) {
        case 'get_all':
            $category = $_GET['category'] ?? 'all';
            
            if ($category === 'all') {
                $sql = "SELECT * FROM gallery_images ORDER BY created_at DESC";
                $result = $conn->query($sql);
            } else {
                $stmt = $conn->prepare("SELECT * FROM gallery_images WHERE category = ? ORDER BY created_at DESC");
                $stmt->bind_param("s", $category);
                $stmt->execute();
                $result = $stmt->get_result();
            }
            
            $images = [];
            while ($row = $result->fetch_assoc()) {
                $images[] = $row;
            }
            
            echo json_encode(['success' => true, 'images' => $images]);
            break;
            
        case 'add':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $imageUrl = $data['image_url'] ?? '';
            $category = $data['category'] ?? 'exterior';
            $subcategory = $data['subcategory'] ?? '';
            $title = $data['title'] ?? 'New Image';
            $description = $data['description'] ?? '';
            
            if (empty($imageUrl)) {
                throw new Exception('Image URL is required');
            }
            
            $stmt = $conn->prepare("INSERT INTO gallery_images (image_url, category, subcategory, title, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $imageUrl, $category, $subcategory, $title, $description);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Image added successfully', 'id' => $conn->insert_id]);
            } else {
                throw new Exception('Failed to add image');
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? $_GET['id'] ?? 0;
            
            if ($id <= 0) {
                throw new Exception('Invalid image ID');
            }
            
            $stmt = $conn->prepare("DELETE FROM gallery_images WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
            } else {
                throw new Exception('Failed to delete image');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
