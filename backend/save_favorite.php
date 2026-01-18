<?php
// Prevent any output before JSON
ob_start();
session_start();
error_reporting(E_ALL); 
ini_set('display_errors', 0); // Hide errors from output, log them instead

require_once 'config.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access. Please log in.');
    }

    $conn = getDatabaseConnection();
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // --- LAZY TABLE CREATION (Fix for 'Table doesn't exist') ---
    $tableSql = "CREATE TABLE IF NOT EXISTS saved_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        item_id VARCHAR(50) NOT NULL,
        item_type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(255),
        meta_info JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        UNIQUE KEY unique_fav (user_id, item_id, item_type)
    )";
    if (!$conn->query($tableSql)) {
        throw new Exception("Failed to ensure table exists: " . $conn->error);
    }
    // -----------------------------------------------------------

    $userId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- HANDLE POST: ADD/REMOVE FAVORITE ---
        $inputRaw = file_get_contents('php://input');
        $input = json_decode($inputRaw, true);
        
        if (!$input) {
            throw new Exception("Invalid JSON input");
        }
        
        if (!isset($input['item_id']) || !isset($input['item_type'])) {
            throw new Exception("Missing required fields (item_id, item_type)");
        }
        
        $itemId = $input['item_id'];
        $itemType = $input['item_type'];
        $title = $input['title'] ?? 'Unknown Item';
        
        // Remove 'remove_action' dummy title if present
        if($title === 'remove_action') $title = 'Removed Item';

        $description = $input['description'] ?? '';
        $imageUrl = $input['image_url'] ?? '';
        $metaInfo = isset($input['meta_info']) ? json_encode($input['meta_info']) : '{}';

        // Check if exists
        $checkSql = "SELECT id FROM saved_favorites WHERE user_id = ? AND item_id = ? AND item_type = ?";
        $stmt = $conn->prepare($checkSql);
        if(!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("iis", $userId, $itemId, $itemType);
        $stmt->execute();
        $checkResult = $stmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Remove
            $delSql = "DELETE FROM saved_favorites WHERE user_id = ? AND item_id = ? AND item_type = ?";
            $delStmt = $conn->prepare($delSql);
            $delStmt->bind_param("iss", $userId, $itemId, $itemType); // Updated to 's' for itemId
            if ($delStmt->execute()) {
                $response = ['status' => 'removed', 'message' => 'Removed from favorites'];
            } else {
                throw new Exception("Failed to delete: " . $conn->error);
            }
        } else {
            // Add
            $insSql = "INSERT INTO saved_favorites (user_id, item_id, item_type, title, description, image_url, meta_info) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insStmt = $conn->prepare($insSql);
            if(!$insStmt) throw new Exception("Prepare failed: " . $conn->error);
            
            $insStmt->bind_param("issssss", $userId, $itemId, $itemType, $title, $description, $imageUrl, $metaInfo); // Updated to 's' for itemId
            if ($insStmt->execute()) {
                 $response = ['status' => 'added', 'message' => 'Added to favorites'];
            } else {
                 throw new Exception("Failed to insert: " . $conn->error);
            }
        }

    } else {
        // --- HANDLE GET: FETCH FAVORITES ---
        $type = $_GET['type'] ?? 'all';
        
        $sql = "SELECT * FROM saved_favorites WHERE user_id = ?";
        $params = "i";
        $paramValues = [$userId];
        
        if ($type !== 'all') {
            $sql .= " AND item_type = ?";
            $params .= "s";
            $paramValues[] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if(!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param($params, ...$paramValues);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            // Decode meta_info for frontend
            $row['meta_info'] = json_decode($row['meta_info'] ?? '{}', true);
            $favorites[] = $row;
        }
        
        $response = ['status' => 'success', 'data' => $favorites];
    }

} catch (Exception $e) {
    error_log("SaveFavorite Error: " . $e->getMessage());
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

// Clear any accidental output (whitespace, warnings)
ob_clean();
echo json_encode($response);
exit();
?>
