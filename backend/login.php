<?php
/**
 * Login Handler
 * Handles email/password login with validation
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Include configuration
require_once 'config.php';

try {
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate required fields
    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception('Email and password are required');
    }
    
    $email = trim($data['email']);
    $password = $data['password'];
    $role = isset($data['role']) ? $data['role'] : 'homeowner';
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Connect to database
    $conn = getDatabaseConnection();
    
    // Check if user exists with this email
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User doesn't exist
        throw new Exception('No account found with this email address. Please sign up first.');
    }
    
    $user = $result->fetch_assoc();
    
    // Check if user has a password (not a Google-only account)
    if (empty($user['password'])) {
        throw new Exception('This account was created with Google Sign-In. Please use "Sign in with Google" instead.');
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Incorrect password. Please try again.');
    }
    
    // Check if role matches
    if ($user['role'] !== $role) {
        throw new Exception('This account is registered as a ' . $user['role'] . '. Please use the correct login portal.');
    }
    
    // Login successful - Create session
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
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
