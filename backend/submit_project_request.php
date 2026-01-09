<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as homeowner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    // Validate required fields
    $required_fields = ['engineer_id', 'homeowner_id', 'project_title', 'project_type', 'budget', 'location', 'timeline', 'description'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit();
        }
    }

    // Get form data
    $engineer_id = intval($_POST['engineer_id']);
    $homeowner_id = intval($_POST['homeowner_id']);
    $project_title = trim($_POST['project_title']);
    $project_type = trim($_POST['project_type']);
    $budget = trim($_POST['budget']);
    $location = trim($_POST['location']);
    $timeline = trim($_POST['timeline']);
    $project_size = isset($_POST['project_size']) ? trim($_POST['project_size']) : null;
    $description = trim($_POST['description']);
    $contact_phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : null;

    // Verify engineer exists and is approved
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'engineer' AND status = 'approved'");
    $stmt->bind_param("i", $engineer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid engineer selected']);
        exit();
    }

    // Verify homeowner matches session
    if ($homeowner_id !== $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Invalid homeowner ID']);
        exit();
    }

    // Create project_requests table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS project_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        engineer_id INT NOT NULL,
        homeowner_id INT NOT NULL,
        project_title VARCHAR(255) NOT NULL,
        project_type VARCHAR(100) NOT NULL,
        budget VARCHAR(100) NOT NULL,
        location VARCHAR(255) NOT NULL,
        timeline VARCHAR(100) NOT NULL,
        project_size VARCHAR(100),
        description TEXT NOT NULL,
        contact_phone VARCHAR(20),
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (engineer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_engineer_id (engineer_id),
        INDEX idx_homeowner_id (homeowner_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->query($create_table_sql);

    // Insert project request
    $stmt = $conn->prepare("INSERT INTO project_requests (engineer_id, homeowner_id, project_title, project_type, budget, location, timeline, project_size, description, contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("iissssssss", 
        $engineer_id, 
        $homeowner_id, 
        $project_title, 
        $project_type, 
        $budget, 
        $location, 
        $timeline, 
        $project_size, 
        $description, 
        $contact_phone
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Project request submitted successfully! The engineer will review it soon.',
            'request_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit request: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
