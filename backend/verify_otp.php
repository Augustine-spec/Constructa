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
$otp = isset($input['otp']) ? trim($input['otp']) : '';
$role = isset($input['role']) ? trim($input['role']) : 'homeowner';

// Validate inputs
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

if (empty($otp) || !preg_match('/^\d{4}$/', $otp)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid 4-digit OTP.'
    ]);
    exit;
}

try {
    $conn = getDatabaseConnection();
    
    // Check if OTP exists and is valid - ignore input role, match by email & OTP
    $stmt = $conn->prepare("SELECT id, user_id, verified, expiry, role FROM password_otp WHERE email = ? AND otp = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid OTP. Please check and try again.'
        ]);
        exit;
    }
    
    $otpData = $result->fetch_assoc();
    
    // Check if OTP has already been verified
    if ($otpData['verified']) {
        echo json_encode([
            'success' => false,
            'message' => 'This OTP has already been used. Please request a new one.'
        ]);
        exit;
    }
    
    // Check if OTP has expired
    if (strtotime($otpData['expiry']) < time()) {
        echo json_encode([
            'success' => false,
            'message' => 'OTP has expired. Please request a new one.'
        ]);
        exit;
    }
    
    // Mark OTP as verified
    $updateStmt = $conn->prepare("UPDATE password_otp SET verified = TRUE WHERE id = ?");
    $updateStmt->bind_param("i", $otpData['id']);
    $updateStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully.'
    ]);
    
} catch (Exception $e) {
    error_log("Verify OTP error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}

$conn->close();
?>
