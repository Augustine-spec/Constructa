<?php
// debug_db_check.php
require_once 'backend/config.php';

$conn = getDatabaseConnection();

echo "<h3>Table: material_orders</h3>";
$result = $conn->query("DESCRIBE material_orders");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
} else {
    echo "Error describing material_orders: " . $conn->error;
}

echo "<h3>Table: users</h3>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
} else {
    echo "Error describing users: " . $conn->error;
}

echo "<h3>Test Query Data</h3>";
$sql = "SELECT m.id, m.user_id, m.project_name, m.items, m.total_amount, m.delivery_stage, m.created_at, m.tracking_history, u.full_name, u.email 
FROM material_orders m 
LEFT JOIN users u ON m.user_id = u.id 
ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
} else {
    $stmt->execute();
    $res = $stmt->get_result();
    echo "Rows found: " . $res->num_rows . "<br>";
    while ($row = $res->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
}
?>
