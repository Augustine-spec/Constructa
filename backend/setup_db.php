<?php
/**
 * Setup Database script
 * Run this once to ensure all tables and columns are created
 */
require_once 'config.php';

echo "<h1>Constructa Database Setup</h1>";

try {
    echo "Connecting to database...<br>";
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, "", DB_PORT);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Creating database if not exists...<br>";
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    $conn->select_db(DB_NAME);
    echo "Checking schema...<br>";
    
    // Use the functions from config.php
    if (initializeDatabase()) {
        echo "✅ Database tables initialized.<br>";
    } else {
        echo "❌ Database table initialization failed.<br>";
    }
    
    if (updateSchema()) {
        echo "✅ Schema updates applied.<br>";
    } else {
        echo "❌ Schema update failed.<br>";
    }
    
    // Update role ENUM to include admin
    echo "Updating role ENUM to include admin...<br>";
    try {
        $conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('homeowner', 'engineer', 'admin') DEFAULT 'homeowner'");
        echo "✅ Role ENUM updated.<br>";
    } catch (Exception $e) {
        echo "⚠️ Role ENUM update: " . $e->getMessage() . "<br>";
    }
    
    // Create default admin user
    echo "Creating default admin user...<br>";
    if (createDefaultAdmin()) {
        echo "✅ Default admin user created (admin@gmail.com / admin).<br>";
    } else {
        echo "⚠️ Admin user already exists or creation skipped.<br>";
    }
    
    echo "<br><strong>Setup Complete!</strong> You can now try logging in.<br>";
    echo '<a href="../login.html">Go to Login Page</a>';
    
    $conn->close();
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
    echo "Please check if XAMPP MySQL is running on port " . DB_PORT;
}
?>
