<?php
require_once 'config.php';
$conn = getDatabaseConnection();

// Create feedback table
$sql = "CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    feedback_text TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ Feedback table created successfully!\n\n";
    
    // Insert sample feedback data for demonstration
    $sampleData = "INSERT INTO feedback (user_id, rating, feedback_text, category) VALUES
        (1, 5, 'Excellent platform! Very helpful for my construction project.', 'general'),
        (1, 4, 'Great templates, but would love more customization options.', 'templates'),
        (2, 5, 'Amazing gallery of designs. Found exactly what I needed.', 'gallery'),
        (2, 4, 'Good service overall.', 'general'),
        (3, 5, 'Professional engineers, quick response time.', 'engineers'),
        (3, 3, 'Interface could be more intuitive.', 'ui'),
        (1, 4, 'Love the 3D visualization feature!', 'features'),
        (2, 5, 'Best construction platform I have used.', 'general')";
    
    if ($conn->query($sampleData) === TRUE) {
        echo "✅ Sample feedback data inserted!\n\n";
        
        // Show average rating
        $result = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM feedback");
        $row = $result->fetch_assoc();
        echo "📊 Current Statistics:\n";
        echo "   Average Rating: " . round($row['avg_rating'], 1) . "/5.0\n";
        echo "   Total Feedback: " . $row['total'] . "\n";
    }
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
