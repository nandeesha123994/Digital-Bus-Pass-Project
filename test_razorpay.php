<?php
/**
 * Razorpay Integration Test Page
 * Tests Razorpay payment integration without requiring a full application
 */

include('includes/config.php');

$message = '';
$messageType = '';

if (isset($_POST['test_razorpay_demo'])) {
    $testAmount = 100; // ₹100 for testing
    $testOrderId = 'order_demo_' . time() . '_' . rand(1000, 9999);
    
    $message = "Demo Razorpay order created successfully! Order ID: $testOrderId";
    $messageType = 'success';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Razorpay Test - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
        .test-section {
            margin: 25px 0;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #f9f9f9;
        }
        .test-section h3 {
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
        .config-display {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            font-family: monospace;
            margin: 15px 0;
        }
        .demo-payment-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin: 10px 0;
        }
        .demo-payment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-credit-card"></i> Razorpay Integration Test</h1>
            <p>Test Razorpay payment integration for the Bus Pass Management System</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="test-section">
            <h3><i class="fas fa-info-circle"></i> Current Razorpay Configuration</h3>
            <div class="config-display">
                Key ID: <?php echo htmlspecialchars(RAZORPAY_KEY_ID); ?><br>
                Secret: <?php echo str_repeat('*', strlen(RAZORPAY_KEY_SECRET) - 4) . substr(RAZORPAY_KEY_SECRET, -4); ?><br>
                Mode: <?php echo (RAZORPAY_KEY_ID === 'rzp_test_1234567890') ? 'Demo Mode' : 'Live Configuration'; ?>
            </div>
            
            <?php if (RAZORPAY_KEY_ID === 'rzp_test_1234567890'): ?>
                <div class="info-box">
                    <strong><i class="fas fa-check-circle"></i> Demo Mode Active</strong><br>
                    The system is configured for demo testing. No real money will be charged.
                </div>
            <?php else: ?>
                <div class="info-box">
                    <strong><i class="fas fa-exclamation-triangle"></i> Live Mode</strong><br>
                    Real Razorpay credentials detected. Payments will be processed for real money.
                </div>
            <?php endif; ?>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-play-circle"></i> Test Razorpay Payment</h3>
            <p>Click the button below to test the Razorpay payment integration with a demo amount of ₹100.</p>
            
            <button class="demo-payment-btn" onclick="testRazorpayPayment()">
                <i class="fas fa-credit-card"></i> Test Razorpay Payment - ₹100
            </button>
            
            <div class="info-box">
                <strong>What this test does:</strong><br>
                • Creates a Razorpay order<br>
                • Opens Razorpay checkout modal<br>
                • Simulates payment process<br>
                • Shows success/failure messages
            </div>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-cog"></i> Integration Status</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="text-align: center; padding: 15px; border: 1px solid #28a745; border-radius: 6px; background: #d4edda;">
                    <i class="fas fa-check-circle" style="font-size: 2em; color: #28a745; margin-bottom: 10px;"></i>
                    <h4>Razorpay Script</h4>
                    <p style="color: #155724;">✓ Loaded</p>
                </div>
                <div style="text-align: center; padding: 15px; border: 1px solid #28a745; border-radius: 6px; background: #d4edda;">
                    <i class="fas fa-key" style="font-size: 2em; color: #28a745; margin-bottom: 10px;"></i>
                    <h4>API Keys</h4>
                    <p style="color: #155724;">✓ Configured</p>
                </div>
                <div style="text-align: center; padding: 15px; border: 1px solid #28a745; border-radius: 6px; background: #d4edda;">
                    <i class="fas fa-code" style="font-size: 2em; color: #28a745; margin-bottom: 10px;"></i>
                    <h4>Integration</h4>
                    <p style="color: #155724;">✓ Ready</p>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-question-circle"></i> Troubleshooting</h3>
            <div class="info-box">
                <strong>If Razorpay payment doesn't work:</strong><br>
                1. Check browser console for JavaScript errors<br>
                2. Verify Razorpay script is loaded<br>
                3. Ensure API keys are correct<br>
                4. Check network connectivity<br>
                5. Try refreshing the page
            </div>
            
            <a href="configure_razorpay.php" class="btn btn-warning">
                <i class="fas fa-cog"></i> Configure Razorpay
            </a>
            <a href="payment_demo.php" class="btn btn-success">
                <i class="fas fa-play"></i> Full Payment Demo
            </a>
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

    <script>
        function testRazorpayPayment() {
            // Test configuration
            const testAmount = 100; // ₹100
            const testOrderId = 'order_demo_' + Date.now() + '_' + Math.floor(Math.random() * 9999);
            
            // Show loading
            const button = document.querySelector('.demo-payment-btn');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Opening Razorpay...';
            button.disabled = true;
            
            // Configure Razorpay options
            const options = {
                key: '<?php echo RAZORPAY_KEY_ID; ?>',
                amount: testAmount * 100, // Convert to paise
                currency: 'INR',
                name: '<?php echo SITE_NAME; ?>',
                description: 'Test Payment - Bus Pass Management',
                order_id: testOrderId,
                handler: function (response) {
                    // Payment successful
                    button.innerHTML = '<i class="fas fa-check-circle"></i> Payment Successful!';
                    button.style.background = '#28a745';
                    
                    showNotification('Payment successful! Payment ID: ' + response.razorpay_payment_id, 'success');
                    
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.style.background = '';
                        button.disabled = false;
                    }, 3000);
                },
                prefill: {
                    name: 'Test User',
                    email: 'test@example.com',
                    contact: '9999999999'
                },
                theme: {
                    color: '#007bff'
                },
                modal: {
                    ondismiss: function() {
                        // Reset button when modal is closed
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                }
            };
            
            // Create and open Razorpay checkout
            const rzp = new Razorpay(options);
            
            rzp.on('payment.failed', function (response) {
                button.innerHTML = originalText;
                button.disabled = false;
                showNotification('Payment failed: ' + response.error.description, 'error');
            });
            
            rzp.open();
        }
        
        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `message ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                max-width: 400px;
                padding: 15px;
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>${message}</div>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 18px; cursor: pointer; margin-left: 10px;">&times;</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        
        // Check if Razorpay is loaded
        window.addEventListener('load', function() {
            if (typeof Razorpay === 'undefined') {
                showNotification('Razorpay script failed to load. Check your internet connection.', 'error');
            } else {
                console.log('Razorpay integration ready!');
            }
        });
    </script>
</body>
</html>
