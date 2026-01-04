<?php
/**
 * OTP System Diagnostic Tool
 * Run this to check the status of your OTP password reset system
 */

header('Content-Type: text/html; charset=utf-8');

require_once 'backend/config.php';

echo "<html><head><title>OTP System Diagnostics</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #16a34a; font-weight: bold; }
    .error { color: #dc2626; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    h1 { color: #294033; }
    h2 { color: #3d5a49; border-bottom: 2px solid #294033; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #294033; color: white; }
    .code { background: #f1f5f9; padding: 10px; border-radius: 4px; font-family: monospace; }
</style></head><body>";

echo "<h1>🔍 OTP System Diagnostics</h1>";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    $conn = getDatabaseConnection();
    echo "<div class='section'><p class='success'>✅ Database connection successful!</p></div>";
    
    // Check if users table exists
    echo "<div class='section'><h2>👥 Users Table</h2>";
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p class='success'>✅ Users table exists</p>";
        
        $userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc();
        echo "<p>Total users: <strong>" . $userCount['count'] . "</strong></p>";
        
        if ($userCount['count'] > 0) {
            echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Has Password</th></tr>";
            $users = $conn->query("SELECT id, name, email, role, password FROM users LIMIT 10");
            while ($user = $users->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['name']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['role']}</td>";
                echo "<td>" . (!empty($user['password']) ? '✅ Yes' : '❌ No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️  No users found. Create a test user:</p>";
            echo "<div class='code'>INSERT INTO users (name, email, password, role) VALUES ('Test User', 'test@example.com', '\$2y\$10\$abc...', 'homeowner');</div>";
        }
    } else {
        echo "<p class='error'>❌ Users table does NOT exist!</p>";
    }
    echo "</div>";
    
    // Check if password_reset_otp table exists
    echo "<div class='section'><h2>🔐 OTP Table</h2>";
    $result = $conn->query("SHOW TABLES LIKE 'password_reset_otp'");
    if ($result->num_rows > 0) {
        echo "<p class='success'>✅ password_reset_otp table exists</p>";
        
        // Check table structure
        echo "<h3>Table Structure:</h3>";
        echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        $columns = $conn->query("DESCRIBE password_reset_otp");
        $hasVerified = false;
        while ($col = $columns->fetch_assoc()) {
            if ($col['Field'] === 'verified') $hasVerified = true;
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($hasVerified) {
            echo "<p class='success'>✅ 'verified' column exists</p>";
        } else {
            echo "<p class='error'>❌ 'verified' column is MISSING! Run: backend/migrate_otp_table.php</p>";
        }
        
        // Show current OTPs
        $otpCount = $conn->query("SELECT COUNT(*) as count FROM password_reset_otp")->fetch_assoc();
        echo "<p>Active OTPs: <strong>" . $otpCount['count'] . "</strong></p>";
        
        if ($otpCount['count'] > 0) {
            echo "<table><tr><th>Email</th><th>OTP</th><th>Verified</th><th>Expiry</th><th>Status</th></tr>";
            $otps = $conn->query("SELECT email, otp, verified, expiry FROM password_reset_otp ORDER BY created_at DESC LIMIT 10");
            while ($otp = $otps->fetch_assoc()) {
                $expired = strtotime($otp['expiry']) < time();
                $status = $expired ? '🔴 Expired' : '🟢 Valid';
                echo "<tr>";
                echo "<td>{$otp['email']}</td>";
                echo "<td><strong>{$otp['otp']}</strong></td>";
                echo "<td>" . ($otp['verified'] ? '✅ Yes' : '❌ No') . "</td>";
                echo "<td>{$otp['expiry']}</td>";
                echo "<td>{$status}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>❌ password_reset_otp table does NOT exist!</p>";
        echo "<p>It will be created automatically when you first use the OTP system.</p>";
    }
    echo "</div>";
    
    // Check backend files
    echo "<div class='section'><h2>📁 Backend Files</h2>";
    $files = [
        'backend/config.php' => 'Database configuration',
        'backend/email_config.php' => 'Email configuration',
        'backend/send_otp.php' => 'Send OTP endpoint',
        'backend/verify_otp.php' => 'Verify OTP endpoint',
        'backend/reset_password.php' => 'Reset password endpoint',
    ];
    
    echo "<table><tr><th>File</th><th>Status</th><th>Description</th></tr>";
    foreach ($files as $file => $desc) {
        $exists = file_exists($file);
        $status = $exists ? "<span class='success'>✅ Exists</span>" : "<span class='error'>❌ Missing</span>";
        echo "<tr><td>{$file}</td><td>{$status}</td><td>{$desc}</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // System recommendations
    echo "<div class='section'><h2>💡 Recommendations</h2>";
    echo "<ul>";
    
    if (!file_exists('vendor/autoload.php')) {
        echo "<li class='warning'>⚠️ PHPMailer not installed. System running in development mode (OTP shows on page). To enable emails: <code>composer require phpmailer/phpmailer</code></li>";
    } else {
        echo "<li class='success'>✅ PHPMailer is installed. Check <code>backend/email_config.php</code> to configure Gmail credentials.</li>";
    }
    
    $userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc();
    if ($userCount['count'] == 0) {
        echo "<li class='warning'>⚠️ No users in database. Create test users to test the system.</li>";
    } else {
        echo "<li class='success'>✅ You have {$userCount['count']} user(s) in the database.</li>";
    }
    
    echo "</ul>";
    echo "</div>";
    
    // Test URLs
    echo "<div class='section'><h2>🧪 Test URLs</h2>";
    echo "<ul>";
    echo "<li><a href='forgot_password.html' target='_blank'><strong>Forgot Password Page</strong></a></li>";
    echo "<li><a href='login.html' target='_blank'><strong>Login Page</strong></a></li>";
    echo "</ul>";
    echo "</div>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='section'><p class='error'>❌ Error: " . $e->getMessage() . "</p></div>";
}

echo "</body></html>";
?>
