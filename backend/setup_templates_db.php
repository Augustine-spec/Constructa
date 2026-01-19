<?php
require_once 'config.php';

function setupTemplatesTable() {
    $conn = getDatabaseConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS house_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(255),
        area_sqft INT,
        floors INT DEFAULT 1,
        style VARCHAR(100),
        budget_min DECIMAL(15,2),
        budget_max DECIMAL(15,2),
        specifications TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'house_templates' created or already exists.<br>";
        
        // Check if table is empty, if so, seed it with sample data
        $check = $conn->query("SELECT COUNT(*) as count FROM house_templates");
        $row = $check->fetch_assoc();
        
        if ($row['count'] == 0) {
            $stmt = $conn->prepare("INSERT INTO house_templates (title, description, image_url, area_sqft, floors, style, budget_min, budget_max, specifications, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            
            $samples = [
                [
                    "Modern Villa 1", 
                    "A spacious modern villa with open floor plan.", 
                    "uploads/template_1.jpg", 
                    2500, 
                    2, 
                    "Modern", 
                    4500000, 
                    6000000, 
                    "4 Bedrooms, 1 Home Office, Open Kitchen"
                ],
                [
                    "Compact Starter", 
                    "Perfect for small plots.", 
                    "uploads/template_2.jpg", 
                    1200, 
                    1, 
                    "Minimalist", 
                    1500000, 
                    2200000, 
                    "2 Bedrooms, 1 Bath"
                ]
            ];
            
            foreach ($samples as $sample) {
                // Use dummy image path if not exists
                $stmt->bind_param("sssiisdds", $sample[0], $sample[1], $sample[2], $sample[3], $sample[4], $sample[5], $sample[6], $sample[7], $sample[8]);
                $stmt->execute();
            }
            echo "Sample templates inserted.<br>";
        }
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
    
    $conn->close();
}

setupTemplatesTable();
?>
