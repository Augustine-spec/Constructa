<?php
require_once 'config.php';

$conn = getDatabaseConnection();

// Add attachment_url column if it doesn't exist
$sql1 = "SHOW COLUMNS FROM messages LIKE 'attachment_url'";
$result1 = $conn->query($sql1);
if ($result1->num_rows == 0) {
    $alter1 = "ALTER TABLE messages ADD COLUMN attachment_url VARCHAR(255) DEFAULT NULL";
    if ($conn->query($alter1) === TRUE) {
        echo "Added attachment_url column.<br>";
    } else {
        echo "Error adding attachment_url: " . $conn->error . "<br>";
    }
}

// Add attachment_type column if it doesn't exist
$sql2 = "SHOW COLUMNS FROM messages LIKE 'attachment_type'";
$result2 = $conn->query($sql2);
if ($result2->num_rows == 0) {
    $alter2 = "ALTER TABLE messages ADD COLUMN attachment_type VARCHAR(50) DEFAULT NULL";
    if ($conn->query($alter2) === TRUE) {
        echo "Added attachment_type column.<br>";
    } else {
        echo "Error adding attachment_type: " . $conn->error . "<br>";
    }
}

$conn->close();
?>
