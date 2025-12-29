<?php
/**
 * Debug Script - Check User Roles in Database
 * This script helps identify role validation issues
 */

require_once 'backend/config.php';

try {
    $conn = getDatabaseConnection();
    
    // Get all users with their roles
    $stmt = $conn->prepare("SELECT id, name, email, role, LENGTH(role) as role_length FROM users ORDER BY id");
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<h2>User Roles in Database</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Role Length</th><th>Role (hex)</th></tr>";
    
    while ($user = $result->fetch_assoc()) {
        $roleHex = bin2hex($user['role']);
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>'{$user['role']}'</td>";
        echo "<td>{$user['role_length']}</td>";
        echo "<td>{$roleHex}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Expected Values:</h3>";
    echo "<ul>";
    echo "<li><strong>homeowner</strong> - Length: 9, Hex: " . bin2hex('homeowner') . "</li>";
    echo "<li><strong>engineer</strong> - Length: 8, Hex: " . bin2hex('engineer') . "</li>";
    echo "<li><strong>admin</strong> - Length: 5, Hex: " . bin2hex('admin') . "</li>";
    echo "</ul>";
    
    echo "<h3>Notes:</h3>";
    echo "<p>If the role length doesn't match expected values, there may be extra whitespace.</p>";
    echo "<p>If the hex values don't match, there may be special characters or encoding issues.</p>";
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
