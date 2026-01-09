<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as engineer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once 'config.php';

try {
    // Validate input
    if (!isset($_POST['request_id']) || !isset($_POST['status'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    $request_id = intval($_POST['request_id']);
    $status = trim($_POST['status']);
    $engineer_id = $_SESSION['user_id'];

    // Validate status
    if (!in_array($status, ['accepted', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    $conn = getDatabaseConnection();

    // Verify the request belongs to this engineer
    $stmt = $conn->prepare("SELECT id FROM project_requests WHERE id = ? AND engineer_id = ?");
    $stmt->bind_param("ii", $request_id, $engineer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Request not found or access denied']);
        exit();
    }

    // Update the request status
    $stmt = $conn->prepare("UPDATE project_requests SET status = ?, updated_at = NOW() WHERE id = ? AND engineer_id = ?");
    $stmt->bind_param("sii", $status, $request_id, $engineer_id);

    if ($stmt->execute()) {
        $message = $status === 'accepted' 
            ? 'Project request accepted successfully! You can now contact the client.' 
            : 'Project request rejected.';
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update request status']);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
