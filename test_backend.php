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
require_once 'backend/config.php';

try {
    $conn = getDatabaseConnection();
    echo "✓ <span style='color: green;'>MySQL Connection Successful (using config.php)</span><br>";
    
    // Check if database exists (implied by connection success as getDatabaseConnection selects it)
    echo "✓ <span style='color: green;'>Database '" . DB_NAME . "' selected</span><br>";
    
    // Check tables
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    
    if ($result === false) {
        echo "❌ <span style='color: red;'>Query Failed: " . $conn->error . "</span><br>";
    } elseif ($result->num_rows > 0) {
        echo "✓ <span style='color: green;'>Table 'users' exists</span><br>";
        
        // Check table structure
        $result = $conn->query("DESCRIBE users");
        
        if ($result === false) {
             echo "❌ <span style='color: red;'>DESCRIBE Failed: " . $conn->error . "</span><br>";
        } else {
            echo "<br><strong>Users Table Structure:</strong><br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $bg = '';
            if ($row['Field'] === 'status') $bg = 'style="background-color: #d4edda;"';
            echo "<tr $bg><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
        }
        echo "</table><br>";
        
        // Verify status column specifically
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
        if ($result->num_rows > 0) {
            echo "✓ <span style='color: green;'>Column 'status' exists</span><br>";
        } else {
            echo "❌ <span style='color: red;'>Column 'status' MISSING</span><br>";
            // Try to force update
            require_once 'backend/config.php';
            // updateSchema() is called on include, but maybe we can call it explicitly if needed, but it's not exported if we are in function scope? 
            // It is a function.
            if (function_exists('updateSchema')) {
                updateSchema();
                echo "ℹ️ Triggered schema update... refreshing check...<br>";
                $result = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
                if ($result->num_rows > 0) echo "✓ <span style='color: green;'>Column 'status' created</span><br>";
                 else echo "❌ <span style='color: red;'>Column 'status' still MISSING. Check error log.</span><br>";
            }
        }
        }
    } else {
        echo "❌ <span style='color: red;'>Table 'users' does not exist</span><br>";
        echo "⚠️ Run the database initialization script<br><br>";
    }
    
    $conn->close();

} catch (Exception $e) {
    echo "❌ <span style='color: red;'>MySQL Connection Failed: " . $e->getMessage() . "</span><br>";
    echo "⚠️ <strong>Make sure MySQL is running in XAMPP!</strong><br><br>";
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
    // require_once 'backend/config.php'; // already included
    echo "✓ Google Client ID: " . substr($GOOGLE_CLIENT_ID, 0, 20) . "...<br>";
    echo "✓ Database Host: " . DB_HOST . "<br>";
    echo "✓ Database Name: " . DB_NAME . "<br>";
    echo "✓ Database User: " . DB_USER . "<br>";
    echo "✓ Database Port: " . DB_PORT . "<br><br>";
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
