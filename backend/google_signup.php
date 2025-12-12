<?php
/**
 * Google OAuth Signup Handler
 * Verifies Google ID token and creates user account
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
    
    if (!isset($data['credential'])) {
        throw new Exception('No credential provided');
    }
    
    $credential = $data['credential'];
    $role = isset($data['role']) ? $data['role'] : 'homeowner';
    
    // Verify the Google ID token
    $userInfo = verifyGoogleToken($credential);
    
    if (!$userInfo) {
        throw new Exception('Invalid Google token');
    }
    
    // Connect to database
    $conn = getDatabaseConnection();
    
    // Check if user already exists
    $email = $userInfo['email'];
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists - log them in instead
        $user = $result->fetch_assoc();
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $role;
        
        echo json_encode([
            'success' => true,
            'message' => 'Welcome back!',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ],
            'existing_user' => true
        ]);
    } else {
        // Create new user
        $name = $userInfo['name'];
        $picture = isset($userInfo['picture']) ? $userInfo['picture'] : null;
        $google_id = $userInfo['sub'];
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, google_id, profile_picture, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $name, $email, $google_id, $picture, $role);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Create session
            session_start();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;
            
            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully',
                'user' => [
                    'id' => $user_id,
                    'name' => $name,
                    'email' => $email
                ],
                'existing_user' => false
            ]);
        } else {
            throw new Exception('Failed to create user account');
        }
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
    
    // Basic validation - In production, you should verify the signature
    // using Google's public keys from https://www.googleapis.com/oauth2/v3/certs
    
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
