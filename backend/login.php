<?php
/**
 * Login Handler
 * Handles email/password login with validation
 */

// Enable error reporting for logs but disable for output to prevent breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON response header
header('Content-Type: application/json');

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
    
    // Include configuration
    require_once 'config.php';
    
    // Connect to database
    $conn = getDatabaseConnection();
    
    // Check if user exists with this email
    $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
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
        // For security, you might want a generic message, but since this and 
        // signup are separate, it's often okay to be specific in dev.
        throw new Exception('Incorrect password. Please try again.');
    }
    
    // Validate role matches if role was provided
    if ($selectedRole && $selectedRole !== 'null' && $selectedRole !== '') {
        // Normalize both roles to lowercase for comparison
        $userRoleLower = strtolower(trim($user['role']));
        $selectedRoleLower = strtolower(trim($selectedRole));
        
        if ($userRoleLower !== $selectedRoleLower) {
            $actualRole = ucfirst($user['role']);
            $selectedRoleDisplay = ucfirst($selectedRole);
            throw new Exception("Role mismatch: This account is registered as a {$actualRole}, not a {$selectedRoleDisplay}.");
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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    
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
    
} catch (Throwable $e) {
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

?>
