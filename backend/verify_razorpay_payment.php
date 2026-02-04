<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Razorpay API credentials
$keyId = 'rzp_test_S60Mda5xiv9lpa';
$keySecret = 'twRisxEufZms0w4zsqcioiZP';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$razorpayPaymentId = $input['razorpay_payment_id'] ?? '';
$razorpayOrderId = $input['razorpay_order_id'] ?? '';
$razorpaySignature = $input['razorpay_signature'] ?? '';
$orderData = $input['order_data'] ?? [];

if (empty($razorpayPaymentId) || empty($razorpayOrderId) || empty($razorpaySignature)) {
    echo json_encode(['success' => false, 'message' => 'Missing payment details']);
    exit();
}

// Verify signature
$generatedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $keySecret);

if ($generatedSignature !== $razorpaySignature) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment signature']);
    exit();
}

// Payment verified successfully
// Now save the order to database
try {
    $conn = getDatabaseConnection();
    
    $userId = $_SESSION['user_id'] ?? null;
    $items = json_encode($orderData['items']);
    $total = $orderData['total'];
    $paymentId = $razorpayPaymentId;
    $orderId = $razorpayOrderId;
    $status = 'paid';
    
    // Insert order into database
    $stmt = $conn->prepare("INSERT INTO material_orders (user_id, items, total_amount, payment_id, razorpay_order_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isdsss", $userId, $items, $total, $paymentId, $orderId, $status);
    
    if ($stmt->execute()) {
        $insertedOrderId = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment verified and order placed successfully',
            'order_id' => 'ORD' . str_pad($insertedOrderId, 6, '0', STR_PAD_LEFT),
            'payment_id' => $paymentId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Payment verified but failed to save order: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
