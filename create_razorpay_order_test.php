<?php
/**
 * Razorpay Order Creation Test Endpoint (No Session Required)
 * For debugging Razorpay integration issues
 */

include('includes/config.php');

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log all requests for debugging
$logMessage = date('Y-m-d H:i:s') . " - Test Razorpay order request received\n";
file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $logMessage = date('Y-m-d H:i:s') . " - Error: Invalid request method: " . $_SERVER['REQUEST_METHOD'] . "\n";
    file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'debug' => 'Only POST method allowed']);
    exit();
}

// Get JSON input
$rawInput = file_get_contents('php://input');
$logMessage = date('Y-m-d H:i:s') . " - Raw input: " . $rawInput . "\n";
file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);

$input = json_decode($rawInput, true);

if (!$input || !isset($input['application_id']) || !isset($input['amount'])) {
    $logMessage = date('Y-m-d H:i:s') . " - Error: Missing parameters. Input: " . print_r($input, true) . "\n";
    file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required parameters', 
        'debug' => 'application_id and amount required', 
        'received' => $input
    ]);
    exit();
}

$applicationId = (int)$input['application_id'];
$amount = (float)$input['amount'];

$logMessage = date('Y-m-d H:i:s') . " - Processing order for application $applicationId, amount $amount\n";
file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);

try {
    // Create Razorpay order
    $orderData = createTestRazorpayOrder($amount, $applicationId);
    
    if ($orderData['success']) {
        $response = [
            'success' => true,
            'order_id' => $orderData['order_id'],
            'amount' => $amount * 100, // Convert to paise
            'currency' => 'INR',
            'key' => RAZORPAY_KEY_ID,
            'name' => SITE_NAME,
            'description' => 'Test Bus Pass Payment - Application #' . $applicationId,
            'prefill' => [
                'name' => 'Test User',
                'email' => 'test@example.com'
            ]
        ];
        
        $logMessage = date('Y-m-d H:i:s') . " - Success: " . json_encode($response) . "\n";
        file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);
        
        echo json_encode($response);
    } else {
        $logMessage = date('Y-m-d H:i:s') . " - Order creation failed: " . $orderData['error'] . "\n";
        file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);
        
        http_response_code(500);
        echo json_encode(['error' => $orderData['error'], 'debug' => 'Order creation failed']);
    }
    
} catch (Exception $e) {
    $logMessage = date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n";
    file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create payment order', 'debug' => $e->getMessage()]);
}

function createTestRazorpayOrder($amount, $applicationId) {
    $logMessage = date('Y-m-d H:i:s') . " - Creating order with RAZORPAY_KEY_ID: " . RAZORPAY_KEY_ID . "\n";
    file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);
    
    // Always use demo mode for testing
    $orderId = 'order_test_' . time() . '_' . rand(1000, 9999);
    
    $logMessage = date('Y-m-d H:i:s') . " - Demo order created: $orderId for application $applicationId\n";
    file_put_contents('razorpay_test_debug.log', $logMessage, FILE_APPEND);
    
    return [
        'success' => true,
        'order_id' => $orderId
    ];
}
?>
