<?php
require_once 'config.php';
echo "Testing connection to " . DB_HOST . ":" . DB_PORT . "...\n";
try {
    $conn = getDatabaseConnection();
    echo "Connected successfully to " . DB_NAME . "\n";
    $conn->close();
} catch (Exception $e) {
    echo "Connection error: " . $e->getMessage() . "\n";
}
?>
