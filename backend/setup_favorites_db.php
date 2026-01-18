<?php
require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    // Create saved_favorites table
    $sql = "CREATE TABLE IF NOT EXISTS saved_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        item_id VARCHAR(50) NOT NULL,
        item_type ENUM('design', 'material', 'professional') NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(255),
        meta_info JSON, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_fav (user_id, item_id, item_type),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'saved_favorites' created or already exists successfully.";
    } else {
        echo "Error creating table: " . $conn->error;
    }
    
    $conn->close();
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
