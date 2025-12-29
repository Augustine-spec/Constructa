<?php
require_once 'config.php';
try {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("SELECT id FROM users LIMIT 1");
    if (method_exists($stmt, 'get_result')) {
        echo "get_result is available!";
    } else {
        echo "get_result is NOT available!";
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
