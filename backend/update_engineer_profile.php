<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once 'config.php';
$conn = getDatabaseConnection();

$user_id = $_SESSION['user_id'];
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$specialization = $_POST['specialization'] ?? '';
$experience = $_POST['experience'] ?? 0;
$license_number = $_POST['license_number'] ?? '';
$bio = $_POST['bio'] ?? '';

try {
    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, specialization = ?, experience = ?, license_number = ?, bio = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssissi", $name, $phone, $specialization, $experience, $license_number, $bio, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $name; // Update session name
        header('Location: ../engineer_profile.php?updated=true');
    } else {
        header('Location: ../engineer_profile.php?error=update_failed');
    }
} catch (Exception $e) {
    header('Location: ../engineer_profile.php?error=' . urlencode($e->getMessage()));
}

$conn->close();
?>
