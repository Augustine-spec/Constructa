<?php
/**
 * Database Schema Update for New User Management System
 * Adds status field and admin role support
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    echo "<h2>Updating Database Schema...</h2>";
    
    // 1. Update role ENUM to include 'admin'
    echo "<p>Updating role column to include admin...</p>";
    $conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('homeowner', 'engineer', 'admin') DEFAULT 'homeowner'");
    echo "<p style='color: green;'>✓ Role column updated</p>";
    
    // 2. Add status column for engineer approval workflow
    echo "<p>Adding status column...</p>";
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER role");
        echo "<p style='color: green;'>✓ Status column added</p>";
    } else {
        echo "<p style='color: blue;'>Status column already exists</p>";
    }
    
    // 3. Add engineer-specific fields
    echo "<p>Adding engineer-specific fields...</p>";
    
    $engineerFields = [
        'phone' => "VARCHAR(20)",
        'specialization' => "VARCHAR(100)",
        'experience' => "INT",
        'license_number' => "VARCHAR(100)",
        'portfolio_url' => "VARCHAR(500)",
        'bio' => "TEXT",
        'id_proof_path' => "VARCHAR(500)"
    ];
    
    foreach ($engineerFields as $field => $type) {
        $result = $conn->query("SHOW COLUMNS FROM users LIKE '$field'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN $field $type AFTER status");
            echo "<p style='color: green;'>✓ Added $field column</p>";
        } else {
            echo "<p style='color: blue;'>$field column already exists</p>";
        }
    }
    
    // 4. Create admin user if doesn't exist
    echo "<p>Creating admin user...</p>";
    $adminEmail = 'admin@gmail.com';
    $adminPassword = password_hash('admin', PASSWORD_DEFAULT);
    $adminName = 'System Administrator';
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $adminEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'approved', NOW())");
        $stmt->bind_param("sss", $adminName, $adminEmail, $adminPassword);
        $stmt->execute();
        echo "<p style='color: green;'>✓ Admin user created (email: admin@gmail.com, password: admin)</p>";
    } else {
        echo "<p style='color: blue;'>Admin user already exists</p>";
    }
    
    // 5. Update existing users
    echo "<p>Updating existing users...</p>";
    $conn->query("UPDATE users SET status = 'approved' WHERE role = 'homeowner' AND status IS NULL");
    $conn->query("UPDATE users SET status = 'approved' WHERE role = 'admin' AND status IS NULL");
    echo "<p style='color: green;'>✓ Existing homeowner and admin users set to approved</p>";
    
    echo "<h3 style='color: green;'>✓ Database schema update completed successfully!</h3>";
    
    // Display current schema
    echo "<h3>Current Users Table Schema:</h3>";
    $result = $conn->query("DESCRIBE users");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
