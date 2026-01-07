<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['responses'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }

    $conn = getDatabaseConnection();
    $userId = $_SESSION['user_id'];
    $engineerId = isset($data['engineer_id']) ? $data['engineer_id'] : null;
    $responses = $data['responses'];

    // Calculate quality index (average score)
    $totalPoints = 0;
    $count = 0;
    foreach ($responses as $r) {
        if (isset($r['score'])) {
            $totalPoints += $r['score'];
            $count++;
        }
    }
    $avgScore = $count > 0 ? ($totalPoints / $count) : 0;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO feedback_sessions (user_id, engineer_id, total_score) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $userId, $engineerId, $avgScore);
        $stmt->execute();
        $sessionId = $conn->insert_id;

        $stmtRecord = $conn->prepare("INSERT INTO feedback_records (session_id, question_id, score, comment) VALUES (?, ?, ?, ?)");
        foreach ($responses as $r) {
            $qId = $r['question_id'];
            $score = isset($r['score']) ? $r['score'] : 0;
            $comment = isset($r['comment']) ? $r['comment'] : null;
            $stmtRecord->bind_param("iiis", $sessionId, $qId, $score, $comment);
            $stmtRecord->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    $conn->close();
}
?>
