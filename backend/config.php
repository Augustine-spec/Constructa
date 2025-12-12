<?php
/**
 * Database and Google OAuth Configuration
 * IMPORTANT: Update these values with your actual credentials
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');              // Default XAMPP MySQL username
define('DB_PASS', '');                  // Default XAMPP MySQL password (empty)
define('DB_NAME', 'constructa');        // Database name

// Google OAuth Configuration
$GOOGLE_CLIENT_ID = '665743141019-gq39034aahsgi72o9imvc46gr1dkfpq3.apps.googleusercontent.com';

/**
 * Get Database Connection
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDatabaseConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Check if database and tables exist, create if needed
 */
function initializeDatabase() {
    try {
        // Connect without selecting a database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Create database if it doesn't exist
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
        if (!$conn->query($sql)) {
            throw new Exception("Error creating database: " . $conn->error);
        }
        
        // Select the database
        $conn->select_db(DB_NAME);
        
        // Create users table
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255),
            google_id VARCHAR(255) UNIQUE,
            profile_picture VARCHAR(500),
            role ENUM('homeowner', 'engineer') DEFAULT 'homeowner',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_google_id (google_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($sql)) {
            throw new Exception("Error creating users table: " . $conn->error);
        }
        
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

// Initialize database on first load
initializeDatabase();
?>
