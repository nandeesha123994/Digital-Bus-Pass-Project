<?php
/**
 * Razorpay Debug Page
 * Comprehensive debugging for Razorpay integration issues
 */

session_start();
include('includes/dbconnection.php');
include('includes/config.php');

$debugInfo = [];
$testResults = [];

// Check configuration
$debugInfo['config'] = [
    'RAZORPAY_KEY_ID' => RAZORPAY_KEY_ID,
    'RAZORPAY_KEY_SECRET' => substr(RAZORPAY_KEY_SECRET, 0, 4) . str_repeat('*', strlen(RAZORPAY_KEY_SECRET) - 4),
    'SITE_NAME' => SITE_NAME,
    'demo_mode' => (RAZORPAY_KEY_ID === 'rzp_test_1234567890')
];

// Check session
$debugInfo['session'] = [
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
    'user_logged_in' => isset($_SESSION['uid']),
    'user_id' => $_SESSION['uid'] ?? 'Not set',
    'session_id' => session_id()
];

// Check database connection
try {
    $testQuery = $con->query("SELECT 1");
    $debugInfo['database'] = [
        'connected' => true,
        'test_query' => 'Success'
    ];
} catch (Exception $e) {
    $debugInfo['database'] = [
        'connected' => false,
        'error' => $e->getMessage()
    ];
}

// Check file permissions
$debugInfo['files'] = [
    'create_razorpay_order.php' => file_exists('create_razorpay_order.php') ? 'Exists' : 'Missing',
    'includes/config.php' => file_exists('includes/config.php') ? 'Exists' : 'Missing',
    'includes/dbconnection.php' => file_exists('includes/dbconnection.php') ? 'Exists' : 'Missing'
];

// Test order creation if requested
if (isset($_POST['test_order_creation'])) {
    // Simulate logged in user for testing
    if (!isset($_SESSION['uid'])) {
        $_SESSION['uid'] = 999; // Test user ID
    }
    
    $testData = [
        'application_id' => 1,
        'amount' => 100
    ];
    
    // Test the order creation endpoint
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/buspassmsfull/create_razorpay_order_test.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $testResults['order_creation'] = [
        'http_code' => $httpCode,
        'curl_error' => $curlError ?: 'None',
        'response' => $response,
        'parsed_response' => json_decode($response, true)
    ];
}

// Test payment verification if requested
if (isset($_POST['test_payment_verification'])) {
    $testPaymentId = 'pay_demo_' . time() . '_test';
    $testAmount = 100;
    $testApplicationId = 1;
    
    // Include the payment processing function
    include_once('payment.php');
    
    $result = processRazorpayPayment($testPaymentId, $testAmount, $testApplicationId);
    
    $testResults['payment_verification'] = [
        'input' => [
            'payment_id' => $testPaymentId,
            'amount' => $testAmount,
            'application_id' => $testApplicationId
        ],
        'result' => $result
    ];
}

// Check log files
$logFiles = ['razorpay_debug.log', 'razorpay_test_debug.log', 'error.log'];
$debugInfo['logs'] = [];
foreach ($logFiles as $logFile) {
    if (file_exists($logFile)) {
        $debugInfo['logs'][$logFile] = [
            'exists' => true,
            'size' => filesize($logFile),
            'last_modified' => date('Y-m-d H:i:s', filemtime($logFile)),
            'last_10_lines' => array_slice(file($logFile), -10)
        ];
    } else {
        $debugInfo['logs'][$logFile] = ['exists' => false];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Razorpay Debug - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 { color: #dc3545; margin: 0; }
        .debug-section {
            margin: 25px 0;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #f9f9f9;
        }
        .debug-section h3 {
            color: #007bff;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn { 
            background: #007bff; 
            color: white; 
            padding: 12px 30px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: 600;
            margin: 5px;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #212529; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #e9ecef;
            max-height: 300px;
            overflow-y: auto;
        }
        .status-good { color: #28a745; font-weight: bold; }
        .status-bad { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bug"></i> Razorpay Debug Center</h1>
            <p>Comprehensive debugging for Razorpay integration issues</p>
        </div>

        <div class="debug-section">
            <h3><i class="fas fa-cog"></i> Configuration Status</h3>
            <pre><?php echo json_encode($debugInfo['config'], JSON_PRETTY_PRINT); ?></pre>
            
            <div class="info-box">
                <strong>Status:</strong> 
                <?php if ($debugInfo['config']['demo_mode']): ?>
                    <span class="status-good">✅ Demo Mode Active</span>
                <?php else: ?>
                    <span class="status-warning">⚠️ Live Mode (Check credentials)</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="debug-section">
            <h3><i class="fas fa-user"></i> Session Status</h3>
            <pre><?php echo json_encode($debugInfo['session'], JSON_PRETTY_PRINT); ?></pre>
            
            <div class="info-box">
                <strong>Status:</strong> 
                <?php if ($debugInfo['session']['user_logged_in']): ?>
                    <span class="status-good">✅ User Logged In</span>
                <?php else: ?>
                    <span class="status-bad">❌ User Not Logged In</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="debug-section">
            <h3><i class="fas fa-database"></i> Database Status</h3>
            <pre><?php echo json_encode($debugInfo['database'], JSON_PRETTY_PRINT); ?></pre>
            
            <div class="info-box">
                <strong>Status:</strong> 
                <?php if ($debugInfo['database']['connected']): ?>
                    <span class="status-good">✅ Database Connected</span>
                <?php else: ?>
                    <span class="status-bad">❌ Database Connection Failed</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="debug-section">
            <h3><i class="fas fa-file"></i> File Status</h3>
            <pre><?php echo json_encode($debugInfo['files'], JSON_PRETTY_PRINT); ?></pre>
        </div>

        <div class="debug-section">
            <h3><i class="fas fa-vial"></i> Live Tests</h3>
            
            <form method="post" style="display: inline;">
                <button type="submit" name="test_order_creation" class="btn btn-warning">
                    <i class="fas fa-shopping-cart"></i> Test Order Creation
                </button>
            </form>
            
            <form method="post" style="display: inline;">
                <button type="submit" name="test_payment_verification" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Test Payment Verification
                </button>
            </form>
            
            <?php if (!empty($testResults)): ?>
                <h4>Test Results:</h4>
                <pre><?php echo json_encode($testResults, JSON_PRETTY_PRINT); ?></pre>
            <?php endif; ?>
        </div>

        <div class="debug-section">
            <h3><i class="fas fa-file-alt"></i> Log Files</h3>
            <?php foreach ($debugInfo['logs'] as $logFile => $logInfo): ?>
                <h4><?php echo $logFile; ?></h4>
                <?php if ($logInfo['exists']): ?>
                    <p>Size: <?php echo $logInfo['size']; ?> bytes | Last Modified: <?php echo $logInfo['last_modified']; ?></p>
                    <pre><?php echo implode('', $logInfo['last_10_lines']); ?></pre>
                <?php else: ?>
                    <p class="status-warning">⚠️ Log file does not exist</p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="debug-section">
            <h3><i class="fas fa-tools"></i> Quick Actions</h3>
            <a href="test_razorpay_simple.php" class="btn">
                <i class="fas fa-play"></i> Simple Razorpay Test
            </a>
            <a href="test_razorpay.php" class="btn">
                <i class="fas fa-credit-card"></i> Full Razorpay Test
            </a>
            <a href="payment_demo.php" class="btn btn-success">
                <i class="fas fa-demo"></i> Payment Demo
            </a>
            <a href="configure_razorpay.php" class="btn btn-warning">
                <i class="fas fa-cog"></i> Configure Razorpay
            </a>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="index.php" style="color: #007bff; text-decoration: none;">
                🏠 Back to Home
            </a>
        </div>
    </div>
</body>
</html>
