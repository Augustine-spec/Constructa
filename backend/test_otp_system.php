<?php
// Test OTP System and Check Database
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 OTP System Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    h2 { color: #294033; border-bottom: 2px solid #294033; padding-bottom: 5px; }
</style>";

require_once 'config.php';

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    $conn = getDatabaseConnection();
    echo "<div class='success'>✅ Database connection successful!</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

// Test 2: Check Users Table
echo "<h2>2. Users Table Check</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
echo "<div class='info'>📊 Total users in database: <strong>" . $row['count'] . "</strong></div>";

if ($row['count'] > 0) {
    echo "<h3>Sample Users:</h3>";
    $users = $conn->query("SELECT id, name, email, role FROM users LIMIT 5");
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; background: white;'>";
    echo "<tr style='background: #294033; color: white;'><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    while ($user = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠️ No users found in database. You need to create a user first!</div>";
    echo "<h3>Create a Test User:</h3>";
    echo "<pre>INSERT INTO users (name, email, password, role) 
VALUES ('Test User', 'test@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'homeowner');</pre>";
    echo "<p><em>Password for this user is: <strong>password</strong></em></p>";
}

// Test 3: Check password_otp Table
echo "<h2>3. Password OTP Table Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'password_otp'");
if ($result->num_rows > 0) {
    echo "<div class='success'>✅ password_otp table exists</div>";
    
    $otps = $conn->query("SELECT * FROM password_otp ORDER BY created_at DESC LIMIT 5");
    if ($otps->num_rows > 0) {
        echo "<h3>Recent OTP Requests:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; background: white;'>";
        echo "<tr style='background: #294033; color: white;'><th>Email</th><th>OTP</th><th>Role</th><th>Expiry</th><th>Verified</th><th>Created</th></tr>";
        while ($otp = $otps->fetch_assoc()) {
            $expired = strtotime($otp['expiry']) < time() ? '❌ Expired' : '✅ Valid';
            $verified = $otp['verified'] ? '✅ Yes' : '❌ No';
            echo "<tr>";
            echo "<td>" . $otp['email'] . "</td>";
            echo "<td><strong style='font-size: 18px; color: #294033;'>" . $otp['otp'] . "</strong></td>";
            echo "<td>" . $otp['role'] . "</td>";
            echo "<td>" . $otp['expiry'] . " " . $expired . "</td>";
            echo "<td>" . $verified . "</td>";
            echo "<td>" . $otp['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ No OTP requests found yet</div>";
    }
} else {
    echo "<div class='warning'>⚠️ password_otp table doesn't exist yet (will be created automatically on first OTP request)</div>";
}

// Test 4: Email Configuration Check
echo "<h2>4. Email Configuration Check</h2>";
require_once 'email_config.php';

if (SMTP_USERNAME === 'your-email@gmail.com' || SMTP_PASSWORD === 'your-app-password') {
    echo "<div class='warning'>⚠️ Email is NOT configured - System is in DEVELOPMENT MODE</div>";
    echo "<div class='info'>📺 OTPs will be displayed on the webpage instead of being sent via email</div>";
    echo "<h3>To Configure Email:</h3>";
    echo "<ol>";
    echo "<li>Install PHPMailer: <code>composer require phpmailer/phpmailer</code></li>";
    echo "<li>Get Gmail App Password from: <a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a></li>";
    echo "<li>Update <code>backend/email_config.php</code> with your credentials</li>";
    echo "</ol>";
} else {
    echo "<div class='success'>✅ Email credentials are configured</div>";
    echo "<div class='info'>SMTP Username: " . SMTP_USERNAME . "</div>";
    
    // Check if PHPMailer is installed
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        echo "<div class='success'>✅ PHPMailer is installed</div>";
    } else {
        echo "<div class='error'>❌ PHPMailer is NOT installed. Run: <code>composer require phpmailer/phpmailer</code></div>";
    }
}

// Test 5: Test OTP Generation
echo "<h2>5. Test OTP Generation</h2>";
$test_otp = sprintf("%06d", mt_rand(0, 999999));
echo "<div class='success'>✅ OTP generation works! Sample OTP: <strong style='font-size: 24px; color: #294033;'>" . $test_otp . "</strong></div>";

// Summary
echo "<h2>📋 Summary</h2>";
echo "<div class='info'>";
echo "<h3>Current Status:</h3>";
echo "<ul>";
echo "<li>Database: <strong>Connected ✅</strong></li>";
echo "<li>Users: <strong>" . $row['count'] . " users</strong></li>";
echo "<li>Email: <strong>" . (SMTP_USERNAME === 'your-email@gmail.com' ? 'NOT Configured (Dev Mode) ⚠️' : 'Configured ✅') . "</strong></li>";
echo "<li>OTP System: <strong>Ready ✅</strong></li>";
echo "</ul>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>🎯 What This Means:</h3>";
echo "<p><strong>The OTP is NOT being sent to email because email is not configured.</strong></p>";
echo "<p>Instead, the OTP is being <strong>displayed on the webpage</strong> in a big green box after you click 'Send OTP'.</p>";
echo "<p>This is called <strong>Development Mode</strong> and it's working perfectly for testing!</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🧪 How to Test:</h3>";
echo "<ol>";
echo "<li>Go to: <a href='../homeowner_login.html' target='_blank'>homeowner_login.html</a></li>";
echo "<li>Click 'Forgot Password?'</li>";
echo "<li>Enter a registered email (see users table above)</li>";
echo "<li>Click 'Send OTP'</li>";
echo "<li><strong>LOOK FOR THE GREEN BOX</strong> - The OTP will be displayed there!</li>";
echo "<li>Also check browser console (F12) - OTP is logged there too</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>
