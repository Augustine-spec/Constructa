<?php
require_once 'config.php';
$email = 'homeowner1@gmail.com';
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
print_r($user);
?>
