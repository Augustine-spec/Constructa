<?php
require_once 'config.php';

header('Content-Type: text/plain');

try {
    $conn = getDatabaseConnection();

    echo "--- DIAGNOSTIC REPPORT ---\n\n";

    // 1. Check user roles and IDs
    $sql = "SELECT id, name, role, email FROM users WHERE name LIKE '%engineer%' OR role = 'engineer'";
    echo "1. Users with 'engineer' in name or role:\n";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Role: " . $row['role'] . "\n";
    }
    echo "\n";

    // 2. Check Post Counts per user
    $sql = "SELECT user_id, COUNT(*) as count FROM engineer_posts GROUP BY user_id";
    echo "2. Post counts by User ID:\n";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        echo "User ID: " . $row['user_id'] . " | Posts: " . $row['count'] . "\n";
    }
    echo "\n";

    // 3. Run the trending query Logic explicitly (without the ID exclusion)
    echo "3. Trending Query Simulation (All Engineers):\n";
    
    $trending_query = "
        SELECT 
            u.id,
            u.name,
            u.role,
            COUNT(DISTINCT ep.id) as post_count,
            COUNT(DISTINCT pl.id) as likes_received,
            COUNT(DISTINCT pc.id) as comments_received,
            COUNT(DISTINCT ef.follower_id) as follower_count,
            (COUNT(DISTINCT ep.id) * 3 + 
             COUNT(DISTINCT pl.id) * 2 + 
             COUNT(DISTINCT pc.id) * 2 + 
             COUNT(DISTINCT ef.follower_id) * 5) as activity_score
        FROM users u
        LEFT JOIN engineer_posts ep ON u.id = ep.user_id
        LEFT JOIN post_likes pl ON ep.id = pl.post_id
        LEFT JOIN post_comments pc ON ep.id = pc.post_id
        LEFT JOIN engineer_followers ef ON u.id = ef.following_id
        WHERE u.role = 'engineer'
        GROUP BY u.id, u.name, u.role
        ORDER BY activity_score DESC
    ";
    
    $result = $conn->query($trending_query);
    while ($row = $result->fetch_assoc()) {
        echo "User: " . $row['name'] . " (ID: " . $row['id'] . ")\n";
        echo "   - Role: " . $row['role'] . "\n";
        echo "   - Posts: " . $row['post_count'] . "\n";
        echo "   - Score: " . $row['activity_score'] . "\n";
        echo "--------------------------\n";
    }

    $conn->close();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
