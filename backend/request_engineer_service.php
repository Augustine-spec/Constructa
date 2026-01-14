<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

// Validate homeowner authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized. Homeowner access required.'
    ]));
}

// Get parameters
$engineer_id = $_POST['engineer_id'] ?? null;
$project_title = $_POST['project_title'] ?? '';
$project_description = $_POST['project_description'] ?? '';
$location = $_POST['location'] ?? '';
$budget_range = $_POST['budget_range'] ?? '';

// Validate inputs
if (!$engineer_id || !is_numeric($engineer_id)) {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid engineer ID'
    ]));
}

if (empty($project_title) || empty($project_description)) {
    die(json_encode([
        'success' => false,
        'message' => 'Project title and description are required'
    ]));
}

try {
    $conn = getDatabaseConnection();
    $homeowner_id = $_SESSION['user_id'];
    
    // Verify engineer exists and is approved
    $stmt_check = $conn->prepare("SELECT id, name, status FROM users WHERE id = ? AND role = 'engineer'");
    $stmt_check->bind_param("i", $engineer_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        die(json_encode([
            'success' => false,
            'message' => 'Engineer not found'
        ]));
    }
    
    $engineer = $result->fetch_assoc();
    
    if ($engineer['status'] !== 'approved') {
        die(json_encode([
            'success' => false,
            'message' => 'This engineer is not currently accepting projects'
        ]));
    }
    
    // Create project request
    $stmt_insert = $conn->prepare("
        INSERT INTO project_requests 
        (user_id, engineer_id, project_title, project_description, location, budget_range, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt_insert->bind_param(
        "iissss",
        $homeowner_id,
        $engineer_id,
        $project_title,
        $project_description,
        $location,
        $budget_range
    );
    
    if ($stmt_insert->execute()) {
        $request_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Project request sent successfully',
            'request_id' => $request_id,
            'engineer_name' => $engineer['name']
        ]);
    } else {
        throw new Exception('Failed to create project request');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
