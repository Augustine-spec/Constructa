<?php
require_once 'config.php';
$conn = getDatabaseConnection();

$sql = "ALTER TABLE project_requests ADD COLUMN IF NOT EXISTS project_details TEXT AFTER description";
if ($conn->query($sql)) {
    echo "Added project_details column successfully.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
$conn->close();
?>
