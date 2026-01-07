<?php
$host = 'localhost';
$port = 3306;
$user = 'root';
$pass = '';
$db = 'constructa';

echo "Connecting to $host:$port...\n";
mysqli_report(MYSQLI_REPORT_ALL);
try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    echo "Connected successfully!\n";
    $conn->close();
} catch (mysqli_sql_exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}

?>
