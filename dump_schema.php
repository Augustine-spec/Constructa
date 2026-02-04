<?php
require_once 'backend/config.php';

try {
    $conn = getDatabaseConnection();
    
    $tablesResult = $conn->query("SHOW TABLES");
    $output = "";
    
    while ($tableRow = $tablesResult->fetch_row()) {
        $tableName = $tableRow[0];
        $output .= "### Table: $tableName\n";
        $columnsResult = $conn->query("SHOW COLUMNS FROM $tableName");
        $output .= "| Field | Type | Null | Key | Default | Extra |\n";
        $output .= "|-------|------|------|-----|---------|-------|\n";
        while ($colRow = $columnsResult->fetch_assoc()) {
            $output .= "| " . implode(" | ", array_values($colRow)) . " |\n";
        }
        $output .= "\n";
    }
    
    echo $output;
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
