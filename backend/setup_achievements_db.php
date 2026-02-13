<?php
require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    // Create engineer_achievements table
    $sql = "CREATE TABLE IF NOT EXISTS engineer_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        badge_icon VARCHAR(50) NOT NULL,
        badge_title VARCHAR(100) NOT NULL,
        badge_description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'engineer_achievements' created successfully or already exists.";
    } else {
        echo "Error creating table: " . $conn->error;
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
