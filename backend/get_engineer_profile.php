<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

// Validate authentication
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false, 
        'message' => 'Authentication required'
   ]));
}

// Get parameters
$engineer_id = $_GET['engineer_id'] ?? null;
$viewer_role = $_SESSION['role'] ?? 'guest';
$viewer_id = $_SESSION['user_id'] ?? null;

// Validate engineer_id
if (!$engineer_id || !is_numeric($engineer_id)) {
    die(json_encode([
        'success' => false, 
        'message' => 'Invalid engineer ID'
    ]));
}

try {
    $conn = getDatabaseConnection();
    
    // Fetch engineer profile
    $stmt = $conn->prepare("
        SELECT 
            id,
            name,
            email,
            phone,
            profile_picture,
            role,
            status,
            specialization,
            experience,
            license_number,
            portfolio_url,
            bio,
            created_at,
            updated_at
        FROM users 
        WHERE id = ? AND role = 'engineer'
    ");
    
    $stmt->bind_param("i", $engineer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die(json_encode([
            'success' => false, 
            'message' => 'Engineer not found'
        ]));
    }
    
    $engineer = $result->fetch_assoc();
    
    // Fetch project statistics
    $stmt_projects = $conn->prepare("
        SELECT 
            COUNT(*) as total_projects,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_projects,
            COUNT(CASE WHEN status = 'accepted' OR status = 'in_progress' THEN 1 END) as active_projects
        FROM project_requests 
        WHERE engineer_id = ?
    ");
    $stmt_projects->bind_param("i", $engineer_id);
    $stmt_projects->execute();
    $project_stats = $stmt_projects->get_result()->fetch_assoc();
    
    // Determine view mode
    $is_self_view = ($viewer_role === 'engineer' && $viewer_id == $engineer_id);
    $is_admin = ($viewer_role === 'admin');
    $is_homeowner = ($viewer_role === 'homeowner');
    $is_other_engineer = ($viewer_role === 'engineer' && $viewer_id != $engineer_id);
    
    // Build response based on role
    $response = [
        'success' => true,
        'view_mode' => $is_self_view ? 'self' : ($is_admin ? 'admin' : 'public'),
        'engineer' => []
    ];
    
    // === PUBLIC DATA (Available to all roles) ===
   $response['engineer'] = [
        'id' => (int)$engineer['id'],
        'name' => $engineer['name'],
        'specialization' => $engineer['specialization'] ?? 'Structural Engineer',
        'experience' => (int)($engineer['experience'] ?? 0),
        'bio' => $engineer['bio'] ?? '',
        'profile_picture' => $engineer['profile_picture'],
        'status' => $engineer['status'],
        'is_verified' => ($engineer['status'] === 'approved'),
        'member_since' => date('F Y', strtotime($engineer['created_at'])),
        'stats' => [
            'total_projects' => (int)$project_stats['total_projects'],
            'completed_projects' => (int)$project_stats['completed_projects'],
            'active_projects' => (int)$project_stats['active_projects']
        ]
    ];
    
    // === EXTENDED DATA (Self-view or Admin only) ===
    if ($is_self_view || $is_admin) {
        $response['engineer']['email'] = $engineer['email'];
        $response['engineer']['phone'] = $engineer['phone'];
        $response['engineer']['license_number'] = $engineer['license_number'];
        $response['engineer']['portfolio_url'] = $engineer['portfolio_url'];
        $response['engineer']['updated_at'] = $engineer['updated_at'];
    }
    
    // === ADMIN-ONLY DATA ===
    if ($is_admin) {
        $response['engineer']['created_at'] = $engineer['created_at'];
        $response['engineer']['raw_status'] = $engineer['status'];
        
        // Fetch recent activity (last 10 project interactions)
        $stmt_activity = $conn->prepare("
            SELECT 
                pr.id,
                pr.project_title,
                pr.status,
                pr.created_at,
                u.name as homeowner_name
            FROM project_requests pr
            LEFT JOIN users u ON pr.homeowner_id = u.id
            WHERE pr.engineer_id = ?
            ORDER BY pr.created_at DESC
            LIMIT 10
        ");
        $stmt_activity->bind_param("i", $engineer_id);
        $stmt_activity->execute();
        $activity_result = $stmt_activity->get_result();
        
        $response['admin_data'] = [
            'recent_activity' => $activity_result->fetch_all(MYSQLI_ASSOC),
            'can_verify' => ($engineer['status'] !== 'approved'),
            'can_suspend' => ($engineer['status'] === 'approved')
        ];
    }
    
    // === PERMISSIONS ===
    $response['permissions'] = [
        'can_edit' => $is_self_view || $is_admin,
        'can_view_contact' => $is_self_view || $is_admin,
        'can_request_service' => $is_homeowner && $engineer['status'] === 'approved',
        'can_admin_actions' => $is_admin,
        'show_public_view' => !$is_self_view && !$is_admin
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
