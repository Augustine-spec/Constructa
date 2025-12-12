<?php
/**
 * Backend Test Script
 * This will help us diagnose the issue
 */

echo "<h1>Constructa Backend Diagnostics</h1>";

// Test 1: PHP Version
echo "<h2>✓ PHP Version</h2>";
echo "PHP " . phpversion() . "<br><br>";

// Test 2: Database Connection
echo "<h2>Database Connection Test</h2>";
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'constructa';

$conn = @new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    echo "❌ <span style='color: red;'>MySQL Connection Failed: " . $conn->connect_error . "</span><br>";
    echo "⚠️ <strong>Make sure MySQL is running in XAMPP!</strong><br><br>";
} else {
    echo "✓ <span style='color: green;'>MySQL Connection Successful</span><br>";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$dbname'");
    if ($result->num_rows > 0) {
        echo "✓ <span style='color: green;'>Database '$dbname' exists</span><br>";
        
        // Select database and check tables
        $conn->select_db($dbname);
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows > 0) {
            echo "✓ <span style='color: green;'>Table 'users' exists</span><br>";
            
            // Check table structure
            $result = $conn->query("DESCRIBE users");
            echo "<br><strong>Users Table Structure:</strong><br>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
            }
            echo "</table><br>";
        } else {
            echo "❌ <span style='color: red;'>Table 'users' does not exist</span><br>";
            echo "⚠️ Run the database initialization script<br><br>";
        }
    } else {
        echo "❌ <span style='color: red;'>Database '$dbname' does not exist</span><br>";
        echo "⚠️ Database will be created automatically on first backend call<br><br>";
    }
    $conn->close();
}

// Test 3: Backend File Paths
echo "<h2>Backend Files Check</h2>";
$files = [
    'backend/config.php',
    'backend/google_signup.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ <span style='color: green;'>$file exists</span><br>";
    } else {
        echo "❌ <span style='color: red;'>$file NOT FOUND</span><br>";
    }
}

// Test 4: Config Values
echo "<h2>Configuration Check</h2>";
if (file_exists('backend/config.php')) {
    require_once 'backend/config.php';
    echo "✓ Google Client ID: " . substr($GOOGLE_CLIENT_ID, 0, 20) . "...<br>";
    echo "✓ Database Host: " . DB_HOST . "<br>";
    echo "✓ Database Name: " . DB_NAME . "<br>";
    echo "✓ Database User: " . DB_USER . "<br><br>";
}

// Test 5: Session Support
echo "<h2>Session Support</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "✓ <span style='color: green;'>Sessions are working</span><br>";
} else {
    echo "✓ <span style='color: green;'>Session already started</span><br>";
}

echo "<br><hr><br>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If MySQL connection failed: <strong>Start MySQL in XAMPP Control Panel</strong></li>";
echo "<li>If database doesn't exist: <strong>It will be created automatically on first Google sign-in</strong></li>";
echo "<li>If all checks pass: <strong>Open Browser Console (F12) and try Google Sign-In again</strong></li>";
echo "<li>Check the <strong>Network</strong> tab in browser console for the request to google_signup.php</li>";
echo "</ol>";

?>
