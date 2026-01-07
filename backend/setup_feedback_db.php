<?php
require_once 'config.php';

try {
    $conn = getDatabaseConnection();

    // 1. Feedback Questions Table
    $sqlQuestions = "CREATE TABLE IF NOT EXISTS feedback_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_text TEXT NOT NULL,
        question_type ENUM('text', 'rating', 'select', 'textarea') NOT NULL,
        options_json JSON DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sqlQuestions) === TRUE) {
        echo "Table 'feedback_questions' checks out.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }

    // 2. Feedback Responses Table
    $sqlResponses = "CREATE TABLE IF NOT EXISTS feedback_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        question_id INT NOT NULL,
        response_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (question_id) REFERENCES feedback_questions(id) ON DELETE CASCADE
    )";
    if ($conn->query($sqlResponses) === TRUE) {
        echo "Table 'feedback_responses' checks out.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }

    // 3. Seed Default Data
    $result = $conn->query("SELECT COUNT(*) as count FROM feedback_questions");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $defaults = [
            ['How would you rate your overall experience?', 'rating', null],
            ['What feature did you find most useful?', 'select', json_encode(['3D Design', 'Cost Estimator', 'Engineer Chat', 'Material Market'])],
            ['Did you encounter any issues?', 'textarea', null],
            ['How likely are you to recommend Constructa?', 'rating', null]
        ];

        $stmt = $conn->prepare("INSERT INTO feedback_questions (question_text, question_type, options_json) VALUES (?, ?, ?)");
        foreach ($defaults as $q) {
            $stmt->bind_param("sss", $q[0], $q[1], $q[2]);
            $stmt->execute();
        }
        echo "Seeded default feedback questions.\n";
    }

    $conn->close();

} catch (Exception $e) {
    die("DB Setup Error: " . $e->getMessage());
}
?>
