<?php
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';

echo "Connecting to $host:$port...\n";
$conn = @new mysqli($host, $user, $pass, null, $port);

if ($conn->connect_error) {
    echo "Connection error: " . $conn->connect_error . "\n";
} else {
    echo "Success!\n";
    $conn->close();
}
?>
