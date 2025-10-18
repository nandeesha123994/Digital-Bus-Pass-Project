<?php
/**
 * Razorpay Configuration and Testing Page
 * Helps configure and test Razorpay payment integration
 */

$message = '';
$messageType = '';

if (isset($_POST['save_razorpay_config'])) {
    $result = saveRazorpayConfiguration($_POST);
    $message = $result['message'];
    $messageType = $result['type'];
}

if (isset($_POST['test_razorpay'])) {
    $result = testRazorpayConnection();
    $message = $result['message'];
    $messageType = $result['type'];
}

function saveRazorpayConfiguration($data) {
    $configFile = 'includes/config.php';
    
    if (!file_exists($configFile)) {
        return ['message' => 'Config file not found!', 'type' => 'error'];
    }
    
    $configContent = file_get_contents($configFile);
    
    // Update Razorpay settings
    $keyId = trim($data['razorpay_key_id']);
    $keySecret = trim($data['razorpay_key_secret']);
    
    $configContent = preg_replace(
        "/define\('RAZORPAY_KEY_ID', '.*?'\);/",
        "define('RAZORPAY_KEY_ID', '$keyId');",
        $configContent
    );
    
    $configContent = preg_replace(
        "/define\('RAZORPAY_KEY_SECRET', '.*?'\);/",
        "define('RAZORPAY_KEY_SECRET', '$keySecret');",
        $configContent
    );
    
    if (file_put_contents($configFile, $configContent)) {
        return ['message' => 'Razorpay configuration saved successfully!', 'type' => 'success'];
    } else {
        return ['message' => 'Failed to save configuration. Check file permissions.', 'type' => 'error'];
    }
}

function testRazorpayConnection() {
    include_once 'includes/config.php';
    
    if (RAZORPAY_KEY_ID === 'rzp_test_1234567890' || RAZORPAY_KEY_SECRET === 'your_razorpay_secret') {
        return [
            'message' => 'Demo mode active. Razorpay is configured for testing with demo credentials.',
            'type' => 'success'
        ];
    }
    
    // Test API connection
    $url = 'https://api.razorpay.com/v1/payments';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return [
            'message' => 'Connection error: ' . $curlError,
            'type' => 'error'
        ];
    }
    
    if ($httpCode === 200) {
        return [
            'message' => 'Razorpay API connection successful! Your credentials are working.',
            'type' => 'success'
        ];
    } elseif ($httpCode === 401) {
        return [
            'message' => 'Authentication failed. Please check your Razorpay Key ID and Secret.',
            'type' => 'error'
        ];
    } else {
        return [
            'message' => "API test failed with HTTP code: $httpCode",
            'type' => 'error'
        ];
    }
}

// Get current settings
include_once 'includes/config.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Razorpay Configuration - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 { color: #007bff; margin: 0; }
        .config-section {
            margin: 25px 0;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #f9f9f9;
        }
        .config-section h3 {
            color: #007bff;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #333;
        }
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            box-sizing: border-box;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
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
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .message { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 6px; 
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            color: #856404;
        }
        .current-config {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            font-family: monospace;
            margin: 15px 0;
        }
        .demo-section {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fab fa-cc-visa"></i> Razorpay Configuration</h1>
            <p>Configure and test Razorpay payment integration for Indian users</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="config-section">
            <h3><i class="fas fa-info-circle"></i> Current Configuration</h3>
            <div class="current-config">
                Key ID: <?php echo htmlspecialchars(RAZORPAY_KEY_ID); ?><br>
                Secret: <?php echo str_repeat('*', strlen(RAZORPAY_KEY_SECRET) - 4) . substr(RAZORPAY_KEY_SECRET, -4); ?><br>
                Status: <?php echo (RAZORPAY_KEY_ID === 'rzp_test_1234567890') ? 'Demo Mode' : 'Live Configuration'; ?>
            </div>
            
            <form method="post" style="display: inline;">
                <button type="submit" name="test_razorpay" class="btn btn-warning">
                    <i class="fas fa-plug"></i> Test Connection
                </button>
            </form>
        </div>

        <?php if (RAZORPAY_KEY_ID === 'rzp_test_1234567890'): ?>
        <div class="demo-section">
            <h3><i class="fas fa-play-circle"></i> Demo Mode Active</h3>
            <p>The system is currently configured with demo Razorpay credentials. This allows you to test the payment flow without real transactions.</p>
            
            <div class="info-box">
                <strong>Demo Features:</strong><br>
                • No real money transactions<br>
                • Simulated payment success/failure<br>
                • Full payment flow testing<br>
                • Order creation and verification
            </div>
            
            <a href="payment_demo.php" class="btn btn-success">
                <i class="fas fa-play"></i> Try Demo Payment
            </a>
        </div>
        <?php endif; ?>

        <div class="config-section">
            <h3><i class="fas fa-cog"></i> Update Razorpay Configuration</h3>
            
            <div class="warning-box">
                <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                Get your Razorpay credentials from the Razorpay Dashboard → Settings → API Keys
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="razorpay_key_id">Razorpay Key ID:</label>
                    <input type="text" id="razorpay_key_id" name="razorpay_key_id" 
                           value="<?php echo htmlspecialchars(RAZORPAY_KEY_ID); ?>"
                           placeholder="rzp_test_xxxxxxxxxxxxxxxx">
                </div>
                
                <div class="form-group">
                    <label for="razorpay_key_secret">Razorpay Key Secret:</label>
                    <input type="password" id="razorpay_key_secret" name="razorpay_key_secret" 
                           placeholder="Enter your Razorpay key secret">
                </div>
                
                <button type="submit" name="save_razorpay_config" class="btn">
                    <i class="fas fa-save"></i> Save Configuration
                </button>
            </form>
        </div>

        <div class="config-section">
            <h3><i class="fas fa-question-circle"></i> How to Get Razorpay Credentials</h3>
            
            <div class="info-box">
                <strong>Step-by-step guide:</strong><br>
                1. Sign up at <a href="https://razorpay.com" target="_blank">razorpay.com</a><br>
                2. Complete KYC verification<br>
                3. Go to Dashboard → Settings → API Keys<br>
                4. Generate Test/Live API keys<br>
                5. Copy Key ID and Key Secret<br>
                6. Paste them in the form above
            </div>
            
            <div class="warning-box">
                <strong>Test vs Live Mode:</strong><br>
                • <strong>Test Keys:</strong> Start with "rzp_test_" - for testing only<br>
                • <strong>Live Keys:</strong> Start with "rzp_live_" - for real transactions<br>
                • Always test thoroughly before switching to live mode
            </div>
        </div>

        <div class="config-section">
            <h3><i class="fas fa-credit-card"></i> Supported Payment Methods</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 6px;">
                    <i class="fas fa-credit-card" style="font-size: 2em; color: #007bff; margin-bottom: 10px;"></i>
                    <h4>Credit/Debit Cards</h4>
                    <p>Visa, Mastercard, Rupay</p>
                </div>
                <div style="text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 6px;">
                    <i class="fas fa-mobile-alt" style="font-size: 2em; color: #28a745; margin-bottom: 10px;"></i>
                    <h4>UPI</h4>
                    <p>Google Pay, PhonePe, Paytm</p>
                </div>
                <div style="text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 6px;">
                    <i class="fas fa-wallet" style="font-size: 2em; color: #ffc107; margin-bottom: 10px;"></i>
                    <h4>Wallets</h4>
                    <p>Paytm, Mobikwik, Freecharge</p>
                </div>
                <div style="text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 6px;">
                    <i class="fas fa-university" style="font-size: 2em; color: #6f42c1; margin-bottom: 10px;"></i>
                    <h4>Net Banking</h4>
                    <p>All major banks</p>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="payment_demo.php" style="color: #007bff; text-decoration: none; margin-right: 20px;">
                💳 Payment Demo
            </a>
            <a href="test_redirections.php" style="color: #007bff; text-decoration: none; margin-right: 20px;">
                🔄 Test System
            </a>
            <a href="index.php" style="color: #007bff; text-decoration: none;">
                🏠 Back to Home
            </a>
        </div>
    </div>
</body>
</html>
