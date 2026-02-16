<?php
/**
 * Database and Google OAuth Configuration
 * IMPORTANT: Update these values with your actual credentials
 */

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');              // Default XAMPP MySQL username
define('DB_PASS', '');                  // Default XAMPP MySQL password (empty)
define('DB_NAME', 'constructa');        // Database name
define('DB_PORT', 3306);                // MySQL port

// Google OAuth Configuration
$GOOGLE_CLIENT_ID = '665743141019-gq39034aahsgi72o9imvc46gr1dkfpq3.apps.googleusercontent.com';

/**
 * Get Database Connection
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDatabaseConnection() {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = mysqli_init();
    if (!$conn) {
        throw new Exception("mysqli_init failed");
    }
    
    // Set connection timeout to 5 seconds
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    
    try {
        @$conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
    } catch (Throwable $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
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
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, "", DB_PORT);
        
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
            role ENUM('homeowner', 'engineer', 'admin') DEFAULT 'homeowner',
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

/**
 * Update table schema if needed
 */
function updateSchema() {
    try {
        $conn = getDatabaseConnection();
        
        // Check for password column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN password VARCHAR(255) AFTER email");
            error_log("Added password column to users table");
        }
        
        // Check for profile_picture column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(500) AFTER google_id");
            error_log("Added profile_picture column to users table");
        }

        // Check for role column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN role ENUM('homeowner', 'engineer') DEFAULT 'homeowner' AFTER profile_picture");
            error_log("Added role column to users table");
        }
        
        // Check for status column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER role");
            error_log("Added status column to users table");
        }

        // Check for phone column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER password");
            error_log("Added phone column to users table");
        }

        // Check for specialization column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'specialization'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN specialization VARCHAR(255) AFTER status");
            error_log("Added specialization column to users table");
        }

        // Check for experience column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'experience'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN experience INT AFTER specialization");
            error_log("Added experience column to users table");
        }

        // Check for license_number column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'license_number'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN license_number VARCHAR(100) AFTER experience");
            error_log("Added license_number column to users table");
        }

        // Check for portfolio_url column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'portfolio_url'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN portfolio_url VARCHAR(500) AFTER license_number");
            error_log("Added portfolio_url column to users table");
        }

        // Check for bio column
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'bio'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN bio TEXT AFTER portfolio_url");
            error_log("Added bio column to users table");
        }
        
        // Check for feedback_sessions table
        $conn->query("CREATE TABLE IF NOT EXISTS feedback_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            engineer_id INT DEFAULT NULL,
            status ENUM('new', 'reviewed', 'archived') DEFAULT 'new',
            total_score DECIMAL(5,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");

        // Check for feedback_records table
        $conn->query("CREATE TABLE IF NOT EXISTS feedback_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            question_id INT NOT NULL,
            score INT NOT NULL,
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES feedback_sessions(id)
        )");

        $conn->close();
        return true;
    } catch (Exception $e) {
        error_log("Schema update error: " . $e->getMessage());
        return false;
    }
}


/**
 * Create default admin user if it doesn't exist
 */
function createDefaultAdmin() {
    try {
        $conn = getDatabaseConnection();
        
        // Check if admin already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $adminEmail = 'admin@gmail.com';
        $stmt->bind_param("s", $adminEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Admin doesn't exist, create it
            $adminName = 'Administrator';
            $adminPassword = password_hash('admin', PASSWORD_DEFAULT);
            $adminRole = 'admin';
            
            $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'approved')");
            $insertStmt->bind_param("ssss", $adminName, $adminEmail, $adminPassword, $adminRole);
            
            if ($insertStmt->execute()) {
                error_log("Default admin user created successfully");
                return true;
            } else {
                error_log("Failed to create default admin user: " . $insertStmt->error);
                return false;
            }
        }
        
        $stmt->close();
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Error creating default admin: " . $e->getMessage());
        return false;
    }
}

// Initialize database and schema only if not already initialized
// In a production environment, this should be done via a migration script
/*
if (php_sapi_name() !== 'cli') {
    // Only initialize once during web requests
    // Using a static variable or a flag in the database would be better, 
    // but for now let's just make it cleaner.
    initializeDatabase();
    updateSchema();
}
*/

