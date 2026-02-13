<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

try {
    $conn = getDatabaseConnection();
    
    // Improved query to get counts and user-specific like status
    $sql = "
        SELECT 
            p.*, 
            u.name, 
            u.profile_picture,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
            (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as is_liked
        FROM engineer_posts p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC, p.id DESC 
        LIMIT 20
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $posts = [];
    
    while ($row = $result->fetch_assoc()) {
        $row['avatar_letter'] = strtoupper(substr($row['name'], 0, 1));
        $row['time_ago'] = time_elapsed_string($row['created_at']);
        $row['is_liked'] = (bool)$row['is_liked'];
        
        // Fetch recent comments (latest 2) for preview
        $comment_sql = "
            SELECT c.*, u.name as commenter_name 
            FROM post_comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at ASC 
            LIMIT 5
        ";
        $c_stmt = $conn->prepare($comment_sql);
        $c_stmt->bind_param("i", $row['id']);
        $c_stmt->execute();
        $comments_result = $c_stmt->get_result();
        $row['comments'] = [];
        while($comment = $comments_result->fetch_assoc()) {
            $row['comments'][] = $comment;
        }

        $posts[] = $row;
    }
    
    echo json_encode(['success' => true, 'posts' => $posts]);
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
