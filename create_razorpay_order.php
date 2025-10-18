<?php
/**
 * Razorpay Order Creation Endpoint
 * Creates a Razorpay order for payment processing
 */

session_start();
include('includes/dbconnection.php');
include('includes/config.php');

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all requests for debugging
$logMessage = date('Y-m-d H:i:s') . " - Razorpay order request received\n";
file_put_contents('razorpay_debug.log', $logMessage, FILE_APPEND);

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    $logMessage = date('Y-m-d H:i:s') . " - Error: User not logged in\n";
    file_put_contents('razorpay_debug.log', $logMessage, FILE_APPEND);
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access', 'debug' => 'User not logged in']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $logMessage = date('Y-m-d H:i:s') . " - Error: Invalid request method: " . $_SERVER['REQUEST_METHOD'] . "\n";
    file_put_contents('razorpay_debug.log', $logMessage, FILE_APPEND);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'debug' => 'Only POST method allowed']);
    exit();
}

// Get JSON input
$rawInput = file_get_contents('php://input');
$logMessage = date('Y-m-d H:i:s') . " - Raw input: " . $rawInput . "\n";
file_put_contents('razorpay_debug.log', $logMessage, FILE_APPEND);

$input = json_decode($rawInput, true);

if (!$input || !isset($input['application_id']) || !isset($input['amount'])) {
    $logMessage = date('Y-m-d H:i:s') . " - Error: Missing parameters. Input: " . print_r($input, true) . "\n";
    file_put_contents('razorpay_debug.log', $logMessage, FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters', 'debug' => 'application_id and amount required', 'received' => $input]);
    exit();
}

$applicationId = (int)$input['application_id'];
$amount = (float)$input['amount'];

// Verify application belongs to user
$query = "SELECT ba.*, u.full_name, u.email
          FROM bus_pass_applications ba
          JOIN users u ON ba.user_id = u.id
          WHERE ba.id = ? AND ba.user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("ii", $applicationId, $_SESSION['uid']);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();

if (!$application) {
    http_response_code(404);
    echo json_encode(['error' => 'Application not found']);
    exit();
}

// Check if payment is already completed
if ($application['payment_status'] === 'Paid') {
    http_response_code(400);
    echo json_encode(['error' => 'Payment already completed']);
    exit();
}

try {
    // Create Razorpay order
    $orderData = createRazorpayOrder($amount, $applicationId, $application);

    if ($orderData['success']) {
        echo json_encode([
            'success' => true,
            'order_id' => $orderData['order_id'],
            'amount' => $amount * 100, // Convert to paise
            'currency' => 'INR',
            'key' => RAZORPAY_KEY_ID,
            'name' => SITE_NAME,
            'description' => 'Bus Pass Payment - Application #' . $applicationId,
            'prefill' => [
                'name' => $application['full_name'],
                'email' => $application['email']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $orderData['error']]);
    }

} catch (Exception $e) {
    logError("Razorpay order creation failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create payment order']);
}

function createRazorpayOrder($amount, $applicationId, $application) {
    // For demo purposes, we'll simulate order creation
    // In production, use Razorpay PHP SDK

    if (RAZORPAY_KEY_ID === 'rzp_test_1234567890' || RAZORPAY_KEY_SECRET === 'demo_secret_key_12345') {
        // Demo mode - generate mock order ID
        $orderId = 'order_demo_' . time() . '_' . rand(1000, 9999);

        logError("Demo Razorpay order created: $orderId for application $applicationId");

        return [
            'success' => true,
            'order_id' => $orderId
        ];
    }

    // Real Razorpay integration
    $url = 'https://api.razorpay.com/v1/orders';
    $data = [
        'amount' => $amount * 100, // Convert to paise
        'currency' => 'INR',
        'receipt' => 'receipt_' . $applicationId . '_' . time(),
        'notes' => [
            'application_id' => $applicationId,
            'user_name' => $application['full_name'],
            'user_email' => $application['email']
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $orderData = json_decode($response, true);
        if (isset($orderData['id'])) {
            logError("Razorpay order created successfully: " . $orderData['id']);
            return [
                'success' => true,
                'order_id' => $orderData['id']
            ];
        }
    }

    $errorData = json_decode($response, true);
    $errorMessage = isset($errorData['error']['description']) ?
                   $errorData['error']['description'] :
                   'Failed to create Razorpay order';

    logError("Razorpay order creation failed: $errorMessage");

    return [
        'success' => false,
        'error' => $errorMessage
    ];
}
?>
