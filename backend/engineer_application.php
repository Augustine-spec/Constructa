<?php
/**
 * Engineer Application Handler
 * Handles engineer registration with pending status
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once 'config.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate required fields
    $requiredFields = ['name', 'email', 'password', 'phone', 'specialization', 'experience', 'bio'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $name = trim($data['name']);
    $email = trim($data['email']);
    $password = $data['password'];
    $phone = trim($data['phone']);
    $specialization = trim($data['specialization']);
    $experience = intval($data['experience']);
    $license = isset($data['license']) ? trim($data['license']) : null;
    $portfolio = isset($data['portfolio']) ? trim($data['portfolio']) : null;
    $bio = trim($data['bio']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }
    
    // Validate bio length
    if (strlen($bio) < 50) {
        throw new Exception('Professional bio must be at least 50 characters long');
    }
    
    $conn = getDatabaseConnection();
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id, email, role, status FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existingUser = $result->fetch_assoc();
        if ($existingUser['role'] === 'engineer') {
            if ($existingUser['status'] === 'pending') {
                throw new Exception('An application with this email is already pending review.');
            } else if ($existingUser['status'] === 'approved') {
                throw new Exception('An account with this email already exists. Please log in instead.');
            } else if ($existingUser['status'] === 'rejected') {
                throw new Exception('A previous application with this email was not approved. Please contact support.');
            }
        } else {
            throw new Exception('An account with this email already exists with a different role.');
        }
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Create new engineer application with pending status
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, status, specialization, experience, license_number, portfolio_url, bio, created_at) VALUES (?, ?, ?, ?, 'engineer', 'pending', ?, ?, ?, ?, ?, NOW())");
    
    if ($stmt === false) {
        throw new Exception("SQL Prepare Error: " . $conn->error);
    }
    
    $stmt->bind_param("sssssisss", $name, $email, $hashedPassword, $phone, $specialization, $experience, $license, $portfolio, $bio);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Note: We don't create a session for pending engineers
        // They need admin approval first
        
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully',
            'application' => [
                'id' => $user_id,
                'email' => $email,
                'status' => 'pending'
            ]
        ]);
    } else {
        throw new Exception('Failed to submit application');
    }
    
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
