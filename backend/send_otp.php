<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection and email configuration
require_once 'config.php';
require_once 'email_config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$email = isset($input['email']) ? trim($input['email']) : '';
$role = isset($input['role']) ? trim($input['role']) : 'homeowner';

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

try {
    $conn = getDatabaseConnection();
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Truly doesn't exist
        echo json_encode([
            'success' => false, // Changing to false to be helpful during debugging, normally true for security
            'message' => 'No account found with this email address.'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $role = $user['role']; // Override input role with actual role
    
    // Generate a 4-digit OTP
    $otp = sprintf("%04d", mt_rand(0, 9999));
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Create the OTP table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS password_otp (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        role ENUM('homeowner', 'engineer') NOT NULL,
        otp VARCHAR(6) NOT NULL,
        expiry DATETIME NOT NULL,
        verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(email),
        INDEX(otp)
    )";
    $conn->query($createTable);
    
    // Delete any existing OTPs for this email
    $deleteStmt = $conn->prepare("DELETE FROM password_otp WHERE email = ? AND role = ?");
    $deleteStmt->bind_param("ss", $email, $role);
    $deleteStmt->execute();
    
    // Insert new OTP
    $insertStmt = $conn->prepare("INSERT INTO password_otp (user_id, email, role, otp, expiry) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("issss", $user['id'], $email, $role, $otp, $expiry);
    $insertStmt->execute();
    
    // Send email with OTP
    $to = $email;
    $subject = "🔐 Your 4-Digit OTP for Constructa Password Reset";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #294033; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .otp-box { 
                background-color: #294033; 
                color: white; 
                font-size: 42px; 
                font-weight: bold; 
                padding: 24px; 
                text-align: center; 
                letter-spacing: 12px;
                border-radius: 8px;
                margin: 24px 0;
            }
            .warning { 
                background-color: #fff3cd; 
                border-left: 4px solid #ffc107; 
                padding: 12px; 
                margin: 15px 0;
                border-radius: 4px;
            }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Password Reset Request</h1>
            </div>
            <div class='content'>
                <p>Hello {$user['name']},</p>
                <p>Use the secret code below to reset your Constructa password:</p>
                
                <div class='otp-box'>
                    {$otp}
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Valid for 10 minutes</strong>
                </div>
                
                <p>If you didn't ask for this code, you can safely ignore this email.</p>
            </div>
            <div class='footer'>
                <p>© 2025 Constructa</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Constructa <noreply@constructa.com>" . "\r\n";
    $headers .= "Reply-To: support@constructa.com" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Check if email is configured
    $emailConfigured = isEmailConfigured();
    
    // Send the email using centralized email configuration
    $mailSent = sendEmail($to, $subject, $message);
    
    // Also log the OTP for development/debugging purposes
    error_log("Password reset OTP for {$email}: {$otp} - Mail sent status: " . ($mailSent ? 'SUCCESS' : 'FAILED'));
    
    // Build response
    $response = [
        'success' => true,
        'message' => 'OTP sent successfully to your email.',
        // For development only - remove in production:
        'dev_otp' => $otp
    ];
    
    // Add helpful debugging info
    if (!$emailConfigured) {
        $response['debug_info'] = 'Email service not fully configured. Check EMAIL_SETUP_GUIDE.md';
        $response['dev_mode'] = true;
        error_log("⚠️ EMAIL NOT CONFIGURED - OTP for {$email}: {$otp}");
        error_log("📧 To enable email sending: See EMAIL_SETUP_GUIDE.md");
    }
    
    if (!$mailSent) {
        $response['success'] = false;
        $response['message'] = 'Failed to send OTP email. Please try again later.';
        $response['debug_info'] = 'Email sending failed. Check server logs.';
        error_log("CRITICAL: Failed to send OTP email to {$email}. OTP was: {$otp}");
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Send OTP error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}

$conn->close();
?>
