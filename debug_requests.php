<?php
require_once 'backend/config.php';
$conn = getDatabaseConnection();

echo "<h1>Debug Project Requests</h1>";

// 1. Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'project_requests'");
if ($result->num_rows == 0) {
    echo "Table 'project_requests' does NOT exist.<br>";
    exit;
} else {
    echo "Table 'project_requests' exists.<br>";
}

// 2. Dump all requests
$sql = "SELECT * FROM project_requests";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'><tr>";
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        echo "<th>{$field->name}</th>";
    }
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>{$value}</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No requests found in table.<br>";
}

// 3. User Info
session_start();
echo "<h2>Session</h2>";
var_dump($_SESSION);
?>
