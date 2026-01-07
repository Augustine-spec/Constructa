<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDatabaseConnection();
    $userId = $_SESSION['user_id'];
    $responses = $_POST['responses']; // Array [question_id => answer_value]

    if (is_array($responses)) {
        $stmt = $conn->prepare("INSERT INTO feedback_responses (user_id, question_id, response_text) VALUES (?, ?, ?)");
        
        foreach ($responses as $qId => $answer) {
            // Basic validation
            if (!empty($answer)) {
                $stmt->bind_param("iis", $userId, $qId, $answer);
                $stmt->execute();
            }
        }
    }

    echo "<script>alert('Thank you for your feedback!'); window.location.href = '../homeowner.php';</script>";
}
?>
