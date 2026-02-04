<?php
// backend/fetch_material_tracking.php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDatabaseConnection();

try {
    // Fetch all orders for the user
    // In a real app, we might join with a projects table, but for now we use the project_name string
    $sql = "SELECT id, project_name, items, total_amount, delivery_stage, created_at, tracking_history 
            FROM material_orders 
            WHERE user_id = ? 
            ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $projects = [];

    while ($row = $result->fetch_assoc()) {
        $projectName = $row['project_name'] ?: 'Dream Villa Project';
        
        if (!isset($projects[$projectName])) {
            $projects[$projectName] = [
                'name' => $projectName,
                'total_orders' => 0,
                'total_spend' => 0,
                'materials' => [],
                'stages_summary' => [
                    'Requested' => 0,
                    'Engineer Approved' => 0,
                    'Vendor Packed' => 0,
                    'In Transit' => 0,
                    'At Site' => 0,
                    'Verified' => 0
                ]
            ];
        }

        $projects[$projectName]['total_orders']++;
        $projects[$projectName]['total_spend'] += $row['total_amount'];
        $projects[$projectName]['stages_summary'][$row['delivery_stage']]++;

        // Process items
        $items = json_decode($row['items'], true);
        if (is_array($items)) {
            foreach ($items as $item) {
                // Add order-level context to each item for the dashboard
                $item['order_id'] = $row['id'];
                $item['delivery_stage'] = $row['delivery_stage'];
                $item['order_date'] = $row['created_at'];
                $item['tracking_history'] = json_decode($row['tracking_history'], true);
                
                $projects[$projectName]['materials'][] = $item;
            }
        }
    }

    echo json_encode(['status' => 'success', 'data' => array_values($projects)]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
