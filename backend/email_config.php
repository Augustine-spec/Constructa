<?php
/**
 * Email Configuration for Constructa
 * 
 * SETUP INSTRUCTIONS:
 * 1. Install PHPMailer: composer require phpmailer/phpmailer
 * 2. Configure your email settings below
 * 3. For Gmail: Enable 2FA and create an App Password
 */

// Email Service Provider: 'phpmailer', 'sendgrid', or 'basic'
define('EMAIL_PROVIDER', 'phpmailer');

// PHPMailer SMTP Configuration (for Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'augustinejoyaljose2028@mca.ajce.in');  // UPDATE THIS
define('SMTP_PASSWORD', 'gtibtqrpegfssjep');      // UPDATE THIS - Use App Password, not regular password
define('SMTP_FROM_EMAIL', 'augustinejoyaljose2028@mca.ajce.in');
define('SMTP_FROM_NAME', 'Constructa');

// SendGrid Configuration (Alternative)
define('SENDGRID_API_KEY', 'your-sendgrid-api-key');

/**
 * Send email using configured provider
 */
function sendEmail($to, $subject, $htmlMessage, $textMessage = '') {
    // Check if PHPMailer is available
    // Check if PHPMailer is available (Composer or Manual)
    if (EMAIL_PROVIDER === 'phpmailer') {
        $mail = null;
        
        // Option 1: Composer Autoload
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        } 
        // Option 2: Manual Loading
        else if (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
            require_once __DIR__ . '/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        } 
        // Option 3: Not Found
        else {
            error_log('PHPMailer not installed via Composer or manually.');
            return fallbackEmailSend($to, $subject, $htmlMessage);
        }
        
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            // $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Standard TLS
            $mail->SMTPSecure = 'tls'; // Use explicit string for better compatibility
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlMessage;
            $mail->AltBody = $textMessage ?: strip_tags($htmlMessage);
            
            $mail->send();
            return true;
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return false;
        } catch (Exception $e) {
             error_log("General Error: " . $e->getMessage());
             return false;
        }
        
    } else if (EMAIL_PROVIDER === 'sendgrid') {
        // TODO: Implement SendGrid
        return fallbackEmailSend($to, $subject, $htmlMessage);
        
    } else {
        return fallbackEmailSend($to, $subject, $htmlMessage);
    }
}

/**
 * Fallback to PHP mail() function
 * NOTE: This requires SMTP configuration in php.ini
 */
function fallbackEmailSend($to, $subject, $htmlMessage) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    
    $result = @mail($to, $subject, $htmlMessage, $headers);
    
    if (!$result) {
        error_log("CRITICAL: Email sending failed. PHP mail() is not configured properly.");
        error_log("Please configure SMTP in php.ini OR install PHPMailer: composer require phpmailer/phpmailer");
    }
    
    return $result;
}

/**
 * Check if email is properly configured
 */
function isEmailConfigured() {
    if (EMAIL_PROVIDER === 'phpmailer') {
        // Check for Composer or Manual Autoload
        $hasLibrary = file_exists(__DIR__ . '/../vendor/autoload.php') || 
                      file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php');

        $isConfigured = (
            SMTP_USERNAME !== 'your-email@gmail.com' && 
            SMTP_PASSWORD !== 'your-app-password' &&
            $hasLibrary
        );
        
        if (!$isConfigured) {
            error_log('⚠️ Email not configured! Update backend/email_config.php with your Gmail credentials.');
            if (!$hasLibrary) {
                 error_log('⚠️ PHPMailer library missing. Install via Composer or manually.');
            }
        }
        
        return $isConfigured;
    }
    
    return false;
}
?>
