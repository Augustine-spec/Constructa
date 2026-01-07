<?php
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$db = 'constructa';

echo "Testing connection to $host:$port...\n";
$conn = @new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    echo "Connection error: " . $conn->connect_error . "\n";
    exit;
}

echo "Connected successfully to $db\n";
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total users: " . $row['count'] . "\n";
} else {
    echo "Query failed: " . $conn->error . "\n";
}

$email = 'homeowner1@gmail.com';
$stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user_data = $res->fetch_assoc();

if ($user_data) {
    echo "User found:\n";
    print_r($user_data);
} else {
    echo "User $email not found.\n";
}

$conn->close();
?>
