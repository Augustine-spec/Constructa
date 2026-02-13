<?php
require_once 'config.php';

try {
    $conn = getDatabaseConnection();
    
    // Fix role for engineer4 if it exists
    $sql = "UPDATE users SET role = 'engineer', specialization = 'Civil Engineer' WHERE name LIKE '%engineer4%' OR email LIKE '%engineer4%'";
    if ($conn->query($sql) === TRUE) {
        echo "Updated role for engineer4 users. Rows affected: " . $conn->affected_rows;
    } else {
        echo "Error updating record: " . $conn->error;
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
