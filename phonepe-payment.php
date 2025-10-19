<?php
/**
 * PhonePe Style Fake Payment Interface
 * Realistic prototype for bus pass payment system
 */

session_start();

// Get payment details from URL parameters or session
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 1500.00;
$merchant = isset($_GET['merchant']) ? htmlspecialchars($_GET['merchant']) : 'Bus Pass System';
$order_id = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : 'BP' . date('YmdHis');
$redirect_url = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'user-dashboard.php';
$application_id = isset($_GET['application_id']) ? htmlspecialchars($_GET['application_id']) : '';

// Handle payment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'pay_now') {
            // Simulate payment processing delay
            sleep(2);
            
            // Generate fake transaction ID
            $transaction_id = 'TXN' . date('YmdHis') . rand(1000, 9999);

            // Redirect to success page with application_id if available
            $successUrl = "phonepe-success.php?txn_id=$transaction_id&amount=$amount&merchant=" . urlencode($merchant) . "&redirect=" . urlencode($redirect_url);
            if (!empty($application_id)) {
                $successUrl .= "&application_id=" . urlencode($application_id);
            }

            header("Location: $successUrl");
            exit();
        } elseif ($_POST['action'] === 'cancel') {
            // Redirect to cancel page
            header("Location: phonepe-cancel.php?redirect=" . urlencode($redirect_url));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhonePe - Pay ₹<?php echo number_format($amount, 2); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #5f2c82 0%, #49a09d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .phonepe-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .phonepe-header {
            background: linear-gradient(135deg, #5f2c82 0%, #49a09d 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .phonepe-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 15px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #5f2c82;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .phonepe-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .phonepe-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        .payment-details {
            padding: 30px 20px;
        }

        .merchant-info {
            text-align: center;
            margin-bottom: 30px;
        }

        .merchant-name {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .merchant-subtitle {
            font-size: 14px;
            color: #666;
        }

        .amount-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            border: 2px solid #e9ecef;
        }

        .amount-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .amount-value {
            font-size: 36px;
            font-weight: 700;
            color: #5f2c82;
            margin-bottom: 5px;
        }

        .order-id {
            font-size: 12px;
            color: #999;
        }

        .upi-section {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .upi-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .upi-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #5f2c82 0%, #49a09d 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            margin-right: 12px;
        }

        .upi-info h4 {
            font-size: 16px;
            color: #333;
            margin-bottom: 2px;
        }

        .upi-info p {
            font-size: 14px;
            color: #666;
        }

        .upi-id {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            color: #333;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .qr-section {
            text-align: center;
            margin: 20px 0;
        }

        .qr-code {
            width: 120px;
            height: 120px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #666;
            position: relative;
            overflow: hidden;
        }

        .qr-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 20%, #333 2px, transparent 2px),
                radial-gradient(circle at 80% 20%, #333 2px, transparent 2px),
                radial-gradient(circle at 20% 80%, #333 2px, transparent 2px),
                radial-gradient(circle at 80% 80%, #333 2px, transparent 2px),
                radial-gradient(circle at 50% 50%, #333 1px, transparent 1px);
            background-size: 20px 20px, 20px 20px, 20px 20px, 20px 20px, 10px 10px;
            opacity: 0.7;
        }

        .action-buttons {
            padding: 0 20px 30px;
            display: flex;
            gap: 15px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-pay {
            background: linear-gradient(135deg, #5f2c82 0%, #49a09d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(95, 44, 130, 0.3);
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(95, 44, 130, 0.4);
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e9ecef;
        }

        .btn-cancel:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        .loading {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            align-items: center;
            justify-content: center;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #5f2c82;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .security-info {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            border-radius: 8px;
            padding: 12px;
            margin: 20px 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .security-info i {
            color: #28a745;
            font-size: 16px;
        }

        .security-info span {
            font-size: 12px;
            color: #155724;
        }

        @media (max-width: 480px) {
            .phonepe-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .amount-value {
                font-size: 32px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="phonepe-container">
        <!-- Header -->
        <div class="phonepe-header">
            <div class="phonepe-logo">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <div class="phonepe-title">PhonePe</div>
            <div class="phonepe-subtitle">Secure Payment Gateway</div>
        </div>

        <!-- Payment Details -->
        <div class="payment-details">
            <!-- Merchant Info -->
            <div class="merchant-info">
                <div class="merchant-name"><?php echo $merchant; ?></div>
                <div class="merchant-subtitle">Digital Bus Pass Portal</div>
            </div>

            <!-- Amount Section -->
            <div class="amount-section">
                <div class="amount-label">Amount to Pay</div>
                <div class="amount-value">₹<?php echo number_format($amount, 2); ?></div>
                <div class="order-id">Order ID: <?php echo $order_id; ?></div>
            </div>

            <!-- UPI Section -->
            <div class="upi-section">
                <div class="upi-header">
                    <div class="upi-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="upi-info">
                        <h4>UPI Payment</h4>
                        <p>Pay using PhonePe UPI</p>
                    </div>
                </div>
                <div class="upi-id">pay@phonepe</div>
            </div>

            <!-- QR Code Section -->
            <div class="qr-section">
                <div class="qr-code">
                    <div class="qr-pattern"></div>
                    <div style="position: relative; z-index: 1; font-weight: 600;">
                        <i class="fas fa-qrcode" style="font-size: 24px; margin-bottom: 5px; display: block;"></i>
                        Scan to Pay
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Info -->
        <div class="security-info">
            <i class="fas fa-shield-alt"></i>
            <span>Your payment is secured with 256-bit SSL encryption</span>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <form method="POST" style="display: flex; gap: 15px; width: 100%;">
                <button type="submit" name="action" value="cancel" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" name="action" value="pay_now" class="btn btn-pay" id="payBtn">
                    <i class="fas fa-credit-card"></i> Pay Now
                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('payBtn').addEventListener('click', function(e) {
            // Show loading state
            const loading = document.getElementById('loading');
            loading.style.display = 'flex';
            
            // Disable button
            this.disabled = true;
            this.style.opacity = '0.7';
            
            // Add some realistic delay
            setTimeout(() => {
                // Form will submit naturally
            }, 1000);
        });

        // Add realistic touch feedback
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });
            
            btn.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
