<?php
// backend/setup_tracking_schema.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<h2>Starting Schema Update...</h2>";

try {
    $conn = getDatabaseConnection();
    echo "<p>Connected to database successfully.</p>";

    // 1. Add project_name if not exists
    $result = $conn->query("SHOW COLUMNS FROM material_orders LIKE 'project_name'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE material_orders ADD COLUMN project_name VARCHAR(255) DEFAULT 'Dream Villa Project'";
        if ($conn->query($sql)) {
            echo "<p style='color:green'>Added column 'project_name'.</p>";
        } else {
            echo "<p style='color:red'>Error adding 'project_name': " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Column 'project_name' already exists.</p>";
    }

    // 2. Add delivery_stage
    $result = $conn->query("SHOW COLUMNS FROM material_orders LIKE 'delivery_stage'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE material_orders ADD COLUMN delivery_stage ENUM('Requested', 'Engineer Approved', 'Vendor Packed', 'In Transit', 'At Site', 'Verified') DEFAULT 'Requested'";
        if ($conn->query($sql)) {
            echo "<p style='color:green'>Added column 'delivery_stage'.</p>";
        } else {
            echo "<p style='color:red'>Error adding 'delivery_stage': " . $conn->error . "</p>";
        }
    } else {
         // Modify it if it exists to ensure all values are there
         // $sql = "ALTER TABLE material_orders MODIFY COLUMN delivery_stage ENUM('Requested', 'Engineer Approved', 'Vendor Packed', 'In Transit', 'At Site', 'Verified') DEFAULT 'Requested'";
         // $conn->query($sql);
        echo "<p>Column 'delivery_stage' already exists.</p>";
    }

    // 3. Add tracking_history
    $result = $conn->query("SHOW COLUMNS FROM material_orders LIKE 'tracking_history'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE material_orders ADD COLUMN tracking_history TEXT DEFAULT NULL";
        if ($conn->query($sql)) {
            echo "<p style='color:green'>Added column 'tracking_history'.</p>";
        } else {
            echo "<p style='color:red'>Error adding 'tracking_history': " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Column 'tracking_history' already exists.</p>";
    }

    echo "<h3>Schema update completed successfully!</h3>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>
