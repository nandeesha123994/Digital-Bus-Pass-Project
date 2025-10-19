<?php
/**
 * PhonePe Style Cancel Page
 * Payment cancellation confirmation
 */

// Get redirect URL
$redirect_url = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'user-dashboard.php';
$cancel_time = date('d M Y, h:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - PhonePe</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .cancel-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
            overflow: hidden;
            text-align: center;
            position: relative;
        }

        .cancel-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px 20px;
            position: relative;
        }

        .cancel-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #dc3545;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: cancelShake 1s ease-in-out;
        }

        @keyframes cancelShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .cancel-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .cancel-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .cancel-content {
            padding: 30px 20px;
        }

        .cancel-message {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .cancel-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .status-badge {
            background: #f8d7da;
            color: #721c24;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #f5c6cb;
            margin: 20px 0;
            display: inline-block;
        }

        .help-section {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .help-title {
            font-size: 16px;
            color: #1976d2;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .help-text {
            font-size: 14px;
            color: #1565c0;
            line-height: 1.5;
        }

        .action-buttons {
            padding: 0 20px 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        .btn-retry {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .phonepe-branding {
            background: #f8f9fa;
            padding: 15px;
            border-top: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 12px;
            color: #666;
        }

        .phonepe-logo-small {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #5f2c82 0%, #49a09d 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .cancel-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .cancel-title {
                font-size: 20px;
            }
            
            .cancel-message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <!-- Cancel Header -->
        <div class="cancel-header">
            <div class="cancel-icon">
                <i class="fas fa-times"></i>
            </div>
            <div class="cancel-title">Payment Cancelled</div>
            <div class="cancel-subtitle">Transaction was not completed</div>
        </div>

        <!-- Cancel Content -->
        <div class="cancel-content">
            <div class="cancel-message">
                Your payment has been cancelled and no amount has been deducted from your account.
            </div>

            <div class="status-badge">
                <i class="fas fa-ban"></i> CANCELLED
            </div>

            <!-- Cancel Details -->
            <div class="cancel-details">
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color: #dc3545;">CANCELLED</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value"><?php echo $cancel_time; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method</span>
                    <span class="detail-value">PhonePe UPI</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount</span>
                    <span class="detail-value">₹0.00 (Not Charged)</span>
                </div>
            </div>

            <!-- Help Section -->
            <div class="help-section">
                <div class="help-title">
                    <i class="fas fa-info-circle"></i>
                    Need Help?
                </div>
                <div class="help-text">
                    If you cancelled by mistake, you can retry the payment. No charges have been applied to your account.
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button onclick="history.back()" class="btn btn-retry">
                <i class="fas fa-redo"></i> Retry Payment
            </button>
            <a href="<?php echo $redirect_url; ?>" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
            <a href="apply-pass.php" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Apply New Pass
            </a>
        </div>

        <!-- PhonePe Branding -->
        <div class="phonepe-branding">
            <div class="phonepe-logo-small">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <span>Powered by PhonePe • Secure Payment Gateway</span>
        </div>
    </div>

    <script>
        // Auto-redirect after 15 seconds
        let countdown = 15;
        const countdownInterval = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = '<?php echo $redirect_url; ?>';
            }
        }, 1000);

        // Add click sound effect for buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Simple click feedback
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });
    </script>
</body>
</html>
