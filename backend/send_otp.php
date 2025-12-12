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
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // For security, don't reveal if email doesn't exist
        echo json_encode([
            'success' => true,
            'message' => 'If an account exists with this email, an OTP has been sent.'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Generate a 6-digit OTP
    $otp = sprintf("%06d", mt_rand(0, 999999));
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
    $subject = "Password Reset OTP - Constructa";
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
                font-size: 32px; 
                font-weight: bold; 
                padding: 20px; 
                text-align: center; 
                letter-spacing: 8px;
                border-radius: 8px;
                margin: 20px 0;
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
                <h1>Constructa - Password Reset</h1>
            </div>
            <div class='content'>
                <p>Hello {$user['name']},</p>
                <p>We received a request to reset your password. Use the following OTP to complete the password reset process:</p>
                
                <div class='otp-box'>
                    {$otp}
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Important:</strong> This OTP will expire in <strong>10 minutes</strong>.
                </div>
                
                <p>If you didn't request a password reset, please ignore this email and your password will remain unchanged.</p>
                
                <p><strong>Security Tips:</strong></p>
                <ul>
                    <li>Never share your OTP with anyone</li>
                    <li>Constructa will never ask for your OTP via phone or email</li>
                    <li>If you suspect any suspicious activity, contact support immediately</li>
                </ul>
            </div>
            <div class='footer'>
                <p>© 2025 Constructa. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Constructa <noreply@constructa.com>" . "\r\n";
    
    // Note: For development, you might want to use a library like PHPMailer
    // For now, we'll use PHP's mail() function
    // In production, consider using services like SendGrid, AWS SES, or Mailgun
    
    // Uncomment this when you have mail configured:
    // $mailSent = mail($to, $subject, $message, $headers);
    
    // For development, just log the OTP
    error_log("Password reset OTP for {$email}: {$otp}");
    
    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully to your email.',
        // For development only - remove in production:
        'dev_otp' => $otp
    ]);
    
} catch (Exception $e) {
    error_log("Send OTP error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}

$conn->close();
?>
