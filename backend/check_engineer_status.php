<?php
/**
 * Check Engineer Application Status
 * Returns the current status of an engineer application
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    if (!isset($_GET['email']) || trim($_GET['email']) === '') {
        throw new Exception('Email parameter is required');
    }
    
    $email = trim($_GET['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    $conn = getDatabaseConnection();
    
    // Get application details
    $stmt = $conn->prepare("SELECT id, name, email, role, status, created_at, specialization, experience FROM users WHERE email = ? AND role = 'engineer' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('No engineer application found with this email address');
    }
    
    $application = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'application' => [
            'id' => $application['id'],
            'name' => $application['name'],
            'email' => $application['email'],
            'status' => $application['status'],
            'created_at' => $application['created_at'],
            'specialization' => $application['specialization'],
            'experience' => $application['experience']
        ]
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
