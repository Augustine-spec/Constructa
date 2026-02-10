-- Create feedback table for user satisfaction ratings
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    feedback_text TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample feedback data
INSERT INTO feedback (user_id, rating, feedback_text, category) VALUES
(1, 5, 'Excellent platform! Very helpful for my construction project.', 'general'),
(1, 4, 'Great templates, but would love more customization options.', 'templates'),
(2, 5, 'Amazing gallery of designs. Found exactly what I needed.', 'gallery'),
(2, 4, 'Good service overall.', 'general'),
(3, 5, 'Professional engineers, quick response time.', 'engineers'),
(3, 3, 'Interface could be more intuitive.', 'ui'),
(1, 4, 'Love the 3D visualization feature!', 'features'),
(2, 5, 'Best construction platform I have used.', 'general'),
(3, 4, 'Very satisfied with the service.', 'general'),
(1, 5, 'Outstanding experience!', 'general');

-- Verify the data
SELECT 
    AVG(rating) as average_rating,
    COUNT(*) as total_feedback,
    COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
    COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
    COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
    COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
    COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
FROM feedback;
