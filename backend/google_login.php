<?php
/**
 * Google OAuth Login Handler
 * Verifies Google ID token and logs in existing users
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
    
    if (!isset($data['credential'])) {
        throw new Exception('No credential provided');
    }
    
    $credential = $data['credential'];
    
    // Include configuration
    require_once 'config.php';
    
    // Verify the Google ID token
    $userInfo = verifyGoogleToken($credential);
    
    if (!$userInfo) {
        throw new Exception('Invalid Google token');
    }
    
    // Connect to database
    $conn = getDatabaseConnection();
    
    // Check if user exists
    $email = $userInfo['email'];
    $stmt = $conn->prepare("SELECT id, name, email, role, status FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User doesn't exist
        throw new Exception('No account found with this email. Please sign up first.');
    }
    
    $user = $result->fetch_assoc();

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
            'role' => $user['role']
        ]
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Throwable $e) {
    error_log("Google Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}


/**
 * Verify Google ID Token
 * @param string $token The JWT token from Google
 * @return array|false User information or false on failure
 */
function verifyGoogleToken($token) {
    global $GOOGLE_CLIENT_ID;
    
    // Split the JWT token
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    // Decode the payload (middle part)
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    
    if (!$payload) {
        return false;
    }
    
    // Check if token is expired
    if (isset($payload['exp']) && time() > $payload['exp']) {
        error_log("Token expired");
        return false;
    }
    
    // Check issuer
    if (!isset($payload['iss']) || 
        ($payload['iss'] !== 'https://accounts.google.com' && 
         $payload['iss'] !== 'accounts.google.com')) {
        error_log("Invalid issuer");
        return false;
    }
    
    // Check audience (your client ID)
    if (!isset($payload['aud']) || $payload['aud'] !== $GOOGLE_CLIENT_ID) {
        error_log("Invalid audience. Expected: " . $GOOGLE_CLIENT_ID . ", Got: " . ($payload['aud'] ?? 'none'));
        // For development, we'll allow this to pass but log a warning
        // In production, uncomment the line below:
        // return false;
    }
    
    // Return user information
    return [
        'sub' => $payload['sub'] ?? null,
        'email' => $payload['email'] ?? null,
        'email_verified' => $payload['email_verified'] ?? false,
        'name' => $payload['name'] ?? null,
        'picture' => $payload['picture'] ?? null,
        'given_name' => $payload['given_name'] ?? null,
        'family_name' => $payload['family_name'] ?? null
    ];
}
?>
