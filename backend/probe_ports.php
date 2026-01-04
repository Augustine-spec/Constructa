<?php
$ports = [3306, 3307];
foreach ($ports as $port) {
    echo "Testing port $port: ";
    try {
        $conn = @new mysqli("127.0.0.1", "root", "", "", $port);
        if ($conn->connect_error) {
            echo "Failed (" . $conn->connect_error . ")\n";
        } else {
            echo "Success!\n";
            $conn->close();
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}
?>
