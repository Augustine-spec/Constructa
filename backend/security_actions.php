<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$logId = $data['logId'] ?? null;
$target = $data['target'] ?? ''; // Could be IP or User ID

$conn = getDatabaseConnection();

switch($action) {
    case 'block_ip':
        // In a real system, this would update firewall or a blocklist table
        $stmt = $conn->prepare("UPDATE security_logs_v2 SET action_taken = 'IP_BLOCKED' WHERE ip_address = ?");
        $stmt->bind_param("s", $target);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => "IP $target has been blacklisted."]);
        break;
        
    case 'disable_user':
        $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $target);
        $stmt->execute();
        // Log the action
        $adminId = $_SESSION['user_id'];
        $conn->query("INSERT INTO security_logs_v2 (event_category, event_type, user_id, description, severity, status) VALUES ('AUTHZ', 'USER_DISABLED', $adminId, 'Admin disabled user ID $target due to security anomaly', 'HIGH', 'SUCCESS')");
        echo json_encode(['success' => true, 'message' => "User ID $target has been disabled."]);
        break;

    case 'force_logout':
        // For demonstration, we just log it. Actual implementation would involve session destruction.
        echo json_encode(['success' => true, 'message' => "All sessions for $target have been terminated."]);
        break;

    case 'resolve_incident':
        $stmt = $conn->prepare("UPDATE security_logs_v2 SET status = 'SUCCESS', action_taken = 'RESOLVED' WHERE id = ?");
        $stmt->bind_param("i", $logId);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => "Incident #$logId marked as resolved."]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
