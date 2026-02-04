<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Razorpay API credentials
$keyId = 'rzp_test_S60Mda5xiv9lpa';
$keySecret = 'twRisxEufZms0w4zsqcioiZP';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Log the input for debugging
file_put_contents('razorpay_debug.log', date('Y-m-d H:i:s') . " - Input: " . json_encode($input) . "\n", FILE_APPEND);

if (!isset($input['amount']) || $input['amount'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit();
}

$amount = $input['amount']; // Amount in paise
$cart = $input['cart'] ?? [];

// Check if cURL is available
if (!function_exists('curl_init')) {
    echo json_encode(['success' => false, 'message' => 'cURL is not enabled on this server. Please enable cURL extension in php.ini']);
    exit();
}

// Create Razorpay order
$url = 'https://api.razorpay.com/v1/orders';

$orderData = [
    'amount' => $amount,
    'currency' => 'INR',
    'receipt' => 'order_' . time(),
    'notes' => [
        'order_type' => 'material_purchase',
        'item_count' => count($cart)
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For localhost testing

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Log the response for debugging
file_put_contents('razorpay_debug.log', date('Y-m-d H:i:s') . " - HTTP Code: $httpCode\n", FILE_APPEND);
file_put_contents('razorpay_debug.log', date('Y-m-d H:i:s') . " - Response: $response\n", FILE_APPEND);
if ($curlError) {
    file_put_contents('razorpay_debug.log', date('Y-m-d H:i:s') . " - cURL Error: $curlError\n", FILE_APPEND);
}

if ($curlError) {
    echo json_encode([
        'success' => false,
        'message' => 'cURL Error: ' . $curlError
    ]);
    exit();
}

if ($httpCode === 200) {
    $order = json_decode($response, true);
    
    echo json_encode([
        'success' => true,
        'order_id' => $order['id'],
        'amount' => $order['amount'],
        'currency' => $order['currency']
    ]);
} else {
    $error = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $error['error']['description'] ?? 'Failed to create order',
        'http_code' => $httpCode,
        'raw_response' => $response
    ]);
}
?>
