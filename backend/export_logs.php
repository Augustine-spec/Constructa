<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

require_once 'config.php';
$conn = getDatabaseConnection();

$filename = "Sentinel_Forensics_Export_" . date('Y-m-d_H-i-s') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Header
fputcsv($output, ['Event ID', 'Category', 'Timestamp', 'Event Type', 'Actor', 'Role', 'Target Asset', 'Action Summary', 'IP Address', 'Risk Score', 'Confidence', 'Integrity Hash']);

// Data
$query = "SELECT * FROM security_forensics_events ORDER BY timestamp_ms DESC";
$res = $conn->query($query);

while ($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['event_id'],
        $row['category'],
        $row['timestamp_ms'],
        $row['event_type'],
        $row['actor_name'],
        $row['actor_role'],
        $row['target_asset'],
        $row['action_summary'],
        $row['ip_address'],
        $row['device_trust_score'], // Using this as risk-related metric
        $row['confidence_score'],
        $row['integrity_hash']
    ]);
}

fclose($output);
exit();
?>
