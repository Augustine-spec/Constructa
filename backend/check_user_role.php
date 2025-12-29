<?php
// Debug User Role
require_once 'config.php';

$conn = getDatabaseConnection();
$email = 'augustinejoyaljose2028@mca.ajce.in';

echo "Checking user: $email\n\n";

$roles = ['homeowner', 'engineer', 'admin'];

foreach ($roles as $role) {
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "[FOUND] Role: " . $user['role'] . " (ID: " . $user['id'] . ")\n";
    } else {
        echo "[NOT FOUND] Role: $role\n";
    }
}

echo "\nIf the role you are testing with is [NOT FOUND], no OTP email will be sent.\n";
?>
