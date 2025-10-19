<?php
/**
 * PhonePe Style Success Page
 * Fake payment success confirmation
 */

// Get transaction details from URL
$transaction_id = isset($_GET['txn_id']) ? htmlspecialchars($_GET['txn_id']) : 'TXN' . date('YmdHis');
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 1500.00;
$merchant = isset($_GET['merchant']) ? htmlspecialchars($_GET['merchant']) : 'Bus Pass System';
$redirect_url = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'user-dashboard.php';

// Generate timestamp
$payment_time = date('d M Y, h:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - PhonePe</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
            overflow: hidden;
            text-align: center;
            position: relative;
        }

        .success-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px 20px;
            position: relative;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #28a745;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: successPulse 2s ease-in-out infinite;
        }

        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .success-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .success-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .payment-summary {
            padding: 30px 20px;
        }

        .amount-paid {
            font-size: 42px;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 10px;
        }

        .merchant-name {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .transaction-details {
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
            font-family: monospace;
        }

        .transaction-id {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .transaction-id .label {
            font-size: 12px;
            color: #155724;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .transaction-id .value {
            font-size: 16px;
            color: #155724;
            font-weight: 700;
            font-family: monospace;
            letter-spacing: 1px;
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

        .confetti {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .confetti-piece {
            position: absolute;
            width: 8px;
            height: 8px;
            background: #ffd700;
            animation: confettiFall 3s linear infinite;
        }

        .confetti-piece:nth-child(2n) { background: #ff6b6b; animation-delay: 0.5s; }
        .confetti-piece:nth-child(3n) { background: #4ecdc4; animation-delay: 1s; }
        .confetti-piece:nth-child(4n) { background: #45b7d1; animation-delay: 1.5s; }

        @keyframes confettiFall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        @media (max-width: 480px) {
            .success-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .amount-paid {
                font-size: 36px;
            }
            
            .success-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <!-- Confetti Animation -->
        <div class="confetti">
            <div class="confetti-piece" style="left: 10%; animation-delay: 0s;"></div>
            <div class="confetti-piece" style="left: 20%; animation-delay: 0.2s;"></div>
            <div class="confetti-piece" style="left: 30%; animation-delay: 0.4s;"></div>
            <div class="confetti-piece" style="left: 40%; animation-delay: 0.6s;"></div>
            <div class="confetti-piece" style="left: 50%; animation-delay: 0.8s;"></div>
            <div class="confetti-piece" style="left: 60%; animation-delay: 1s;"></div>
            <div class="confetti-piece" style="left: 70%; animation-delay: 1.2s;"></div>
            <div class="confetti-piece" style="left: 80%; animation-delay: 1.4s;"></div>
            <div class="confetti-piece" style="left: 90%; animation-delay: 1.6s;"></div>
        </div>

        <!-- Success Header -->
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <div class="success-title">Payment Successful!</div>
            <div class="success-subtitle">Your transaction has been completed</div>
        </div>

        <!-- Payment Summary -->
        <div class="payment-summary">
            <div class="amount-paid">₹<?php echo number_format($amount, 2); ?></div>
            <div class="merchant-name">Paid to <?php echo $merchant; ?></div>

            <!-- Transaction ID -->
            <div class="transaction-id">
                <div class="label">TRANSACTION ID</div>
                <div class="value"><?php echo $transaction_id; ?></div>
            </div>

            <!-- Transaction Details -->
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="detail-label">Payment Method</span>
                    <span class="detail-value">PhonePe UPI</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value"><?php echo $payment_time; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color: #28a745;">SUCCESS</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">UPI ID</span>
                    <span class="detail-value">pay@phonepe</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <?php
            // Check if this is from bus pass application
            $applicationId = isset($_GET['application_id']) ? $_GET['application_id'] : '';
            if ($applicationId) {
                $finalRedirectUrl = "payment-success.php?application_id=" . urlencode($applicationId) .
                                   "&txn_id=" . urlencode($transaction_id) .
                                   "&amount=" . urlencode($amount);
            } else {
                $finalRedirectUrl = $redirect_url;
            }
            ?>
            <a href="<?php echo $finalRedirectUrl; ?>" class="btn btn-primary">
                <i class="fas fa-home"></i> Continue to Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-download"></i> Download Receipt
            </button>
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
        // Auto-redirect after 10 seconds
        setTimeout(() => {
            const continueBtn = document.querySelector('.btn-primary');
            if (continueBtn) {
                continueBtn.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                continueBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting...';
                
                setTimeout(() => {
                    window.location.href = '<?php echo $redirect_url; ?>';
                }, 2000);
            }
        }, 8000);

        // Add success sound effect (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Create a simple success beep
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        });
    </script>
</body>
</html>
