<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
require_once 'db_connection.php';

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
    // Check if user exists
    $table = $role === 'engineer' ? 'engineers' : 'homeowners';
    $stmt = $conn->prepare("SELECT id, name, email FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // For security reasons, always return success even if email doesn't exist
    // This prevents email enumeration attacks
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate a unique reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store the reset token in database
        // First, create the password_resets table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            role ENUM('homeowner', 'engineer') NOT NULL,
            token VARCHAR(255) NOT NULL,
            expiry DATETIME NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(token),
            INDEX(email)
        )";
        $conn->query($createTable);
        
        // Delete any existing reset tokens for this email
        $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND role = ?");
        $deleteStmt->bind_param("ss", $email, $role);
        $deleteStmt->execute();
        
        // Insert new reset token
        $insertStmt = $conn->prepare("INSERT INTO password_resets (user_id, email, role, token, expiry) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->bind_param("issss", $user['id'], $email, $role, $token, $expiry);
        $insertStmt->execute();
        
        // Create reset link
        $resetLink = "http://localhost/Constructa/reset_password.html?token=" . $token . "&role=" . $role;
        
        // Send email (you'll need to configure this with your email service)
        $to = $email;
        $subject = "Password Reset Request - Constructa";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #294033; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 30px; }
                .button { 
                    display: inline-block; 
                    background-color: #294033; 
                    color: white; 
                    padding: 12px 30px; 
                    text-decoration: none; 
                    border-radius: 8px;
                    margin: 20px 0;
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
                    <p>We received a request to reset your password. Click the button below to reset it:</p>
                    <p style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Reset Password</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background-color: #fff; padding: 10px; border-left: 4px solid #294033;'>
                        {$resetLink}
                    </p>
                    <p><strong>This link will expire in 1 hour.</strong></p>
                    <p>If you didn't request a password reset, please ignore this email and your password will remain unchanged.</p>
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
        // mail($to, $subject, $message, $headers);
        
        // For development, just log the reset link
        error_log("Password reset link for {$email}: {$resetLink}");
    }
    
    // Always return success to prevent email enumeration
    echo json_encode([
        'success' => true,
        'message' => 'If an account exists with this email, a password reset link has been sent.',
        // For development only - remove in production:
        'dev_reset_link' => isset($resetLink) ? $resetLink : null
    ]);
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}

$conn->close();
?>
