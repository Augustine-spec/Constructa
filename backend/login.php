<?php
/**
 * Login Handler
 * Handles email/password login with validation
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
    $selectedRole = isset($data['role']) ? trim($data['role']) : null;
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate role if provided
    if ($selectedRole && !in_array($selectedRole, ['homeowner', 'engineer', 'admin'])) {
        throw new Exception('Invalid role selected');
    }
    
    // Connect to database
    $conn = getDatabaseConnection();
    
    // Check if user exists with this email
    $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
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
    
    // Validate role matches if role was provided
    if ($selectedRole) {
        // Normalize both roles to lowercase for comparison
        $userRoleLower = strtolower(trim($user['role']));
        $selectedRoleLower = strtolower(trim($selectedRole));
        
        // Debug logging (remove in production)
        error_log("Role Validation - User Role: '{$userRoleLower}', Selected Role: '{$selectedRoleLower}'");
        
        if ($userRoleLower !== $selectedRoleLower) {
            // Role mismatch - provide helpful error message
            $actualRole = ucfirst($user['role']);
            $selectedRoleDisplay = ucfirst($selectedRole);
            throw new Exception("Role mismatch: This account is registered as a {$actualRole}, not a {$selectedRoleDisplay}. Please select the correct role.");
        }
    }

    // Check Status for Engineers
    if ($user['role'] === 'engineer' && isset($user['status'])) {
        if ($user['status'] === 'pending') {
            echo json_encode([
                'success' => false,
                'status_check' => true,
                'status' => 'pending',
                'email' => $user['email'],
                'message' => 'Your account is pending approval.'
            ]);
            exit();
        } else if ($user['status'] === 'rejected') {
            echo json_encode([
                'success' => false,
                'status_check' => true,
                'status' => 'rejected',
                'email' => $user['email'],
                'message' => 'Your application was not approved.'
            ]);
            exit();
        }
    }
    
    // Login successful - Create session
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['name'];  // Changed from user_name to full_name
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];  // Changed from user_role to role
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => isset($user['status']) ? $user['status'] : 'approved'
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
