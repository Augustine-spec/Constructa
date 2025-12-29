<?php
require_once 'config.php';
try {
    $conn = getDatabaseConnection();
    echo "Connection successful!";
    $conn->close();
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
