<?php
/**
 * Simple Login Test Script
 * Test role validation directly
 */

// Test data
$testCases = [
    [
        'userRole' => 'engineer',
        'selectedRole' => 'engineer',
        'shouldMatch' => true
    ],
    [
        'userRole' => 'Engineer',
        'selectedRole' => 'engineer',
        'shouldMatch' => true
    ],
    [
        'userRole' => 'engineer ',
        'selectedRole' => 'engineer',
        'shouldMatch' => true
    ],
    [
        'userRole' => 'homeowner',
        'selectedRole' => 'engineer',
        'shouldMatch' => false
    ]
];

echo "<h2>Role Validation Test</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>User Role</th><th>Selected Role</th><th>Expected</th><th>Result (Old Logic)</th><th>Result (New Logic)</th></tr>";

foreach ($testCases as $test) {
    $userRole = $test['userRole'];
    $selectedRole = $test['selectedRole'];
    $expected = $test['shouldMatch'] ? 'MATCH' : 'MISMATCH';
    
    // Old logic (strict comparison)
    $oldResult = ($userRole === $selectedRole) ? 'MATCH' : 'MISMATCH';
    
    // New logic (normalized comparison)
    $userRoleLower = strtolower(trim($userRole));
    $selectedRoleLower = strtolower(trim($selectedRole));
    $newResult = ($userRoleLower === $selectedRoleLower) ? 'MATCH' : 'MISMATCH';
    
    $oldColor = ($oldResult === $expected) ? 'green' : 'red';
    $newColor = ($newResult === $expected) ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>'{$userRole}' (len: " . strlen($userRole) . ")</td>";
    echo "<td>'{$selectedRole}' (len: " . strlen($selectedRole) . ")</td>";
    echo "<td><strong>{$expected}</strong></td>";
    echo "<td style='color: {$oldColor};'><strong>{$oldResult}</strong></td>";
    echo "<td style='color: {$newColor};'><strong>{$newResult}</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Conclusion:</h3>";
echo "<p>The <strong>New Logic</strong> (with trim and lowercase) handles edge cases better.</p>";
echo "<p>All tests should show <span style='color: green;'>green</span> in the 'New Logic' column.</p>";
?>
