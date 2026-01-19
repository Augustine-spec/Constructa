<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = getDatabaseConnection();
$action = $_POST['action'] ?? '';

if ($action === 'create' || $action === 'update') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $area_sqft = $_POST['area_sqft'];
    $floors = $_POST['floors'];
    $style = $_POST['style'];
    $budget_min = $_POST['budget_min'];
    $budget_max = $_POST['budget_max'];
    $specifications = $_POST['specifications'];
    
    // Handle Image Upload
    $image_url = $_POST['current_image'] ?? ''; // Default to existing if update
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $image_url = 'uploads/' . $fileName;
        }
    } else if ($action === 'create' && empty($image_url)) {
         // Use a placeholder if no image uploaded on create
         $image_url = 'https://via.placeholder.com/400x300?text=No+Image'; 
    }

    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO house_templates (title, description, area_sqft, floors, style, budget_min, budget_max, specifications, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiisddss", $title, $description, $area_sqft, $floors, $style, $budget_min, $budget_max, $specifications, $image_url);
    } else {
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE house_templates SET title=?, description=?, area_sqft=?, floors=?, style=?, budget_min=?, budget_max=?, specifications=?, image_url=? WHERE id=?");
        $stmt->bind_param("ssiisddssi", $title, $description, $area_sqft, $floors, $style, $budget_min, $budget_max, $specifications, $image_url, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Template saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }

} elseif ($action === 'delete') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM house_templates WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Template deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting template']);
    }
} elseif ($action === 'fetch_one') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("SELECT * FROM house_templates WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
