<?php
require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    echo "Checking users table schema...\n";
    
    // Add password column if missing
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
    if ($result->num_rows === 0) {
        echo "Adding 'password' column...\n";
        $conn->query("ALTER TABLE users ADD COLUMN password VARCHAR(255) AFTER email");
    } else {
        echo "'password' column already exists.\n";
    }
    
    // Add profile_picture column if missing
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    if ($result->num_rows === 0) {
        echo "Adding 'profile_picture' column...\n";
        $conn->query("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(500) AFTER google_id");
    } else {
        echo "'profile_picture' column already exists.\n";
    }
    
    echo "Database schema update completed successfully.\n";
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
