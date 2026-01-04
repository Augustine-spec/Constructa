<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
require_once 'config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$role = isset($input['role']) ? trim($input['role']) : 'homeowner';

// Validate inputs
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

if (empty($password) || strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters long.'
    ]);
    exit;
}

try {
    $conn = getDatabaseConnection();
    
    // Verify that OTP was verified for this email - lookup role from DB
    $verifyStmt = $conn->prepare("SELECT id, role FROM password_reset_otp WHERE email = ? AND verified = TRUE ORDER BY created_at DESC LIMIT 1");
    $verifyStmt->bind_param("s", $email);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please verify your OTP first.'
        ]);
        exit;
    }
    
    // Get the correct role from the verified OTP session
    $otpRow = $verifyResult->fetch_assoc();
    $role = $otpRow['role'];
    
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user password
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = ?");
    $updateStmt->bind_param("sss", $hashedPassword, $email, $role);
    
    if ($updateStmt->execute()) {
        // Delete used OTP
        $deleteStmt = $conn->prepare("DELETE FROM password_reset_otp WHERE email = ? AND role = ?");
        $deleteStmt->bind_param("ss", $email, $role);
        $deleteStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reset password. Please try again.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}

$conn->close();
?>
