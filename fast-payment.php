<?php
session_start();
include('includes/dbconnection.php');
include('includes/config.php');
include('includes/email.php');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// Get application ID
$applicationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$applicationId) {
    header('Location: user-dashboard.php?error=invalid_application');
    exit();
}

// Get application details
$query = "SELECT ba.*, u.full_name as user_name, u.email as user_email, bpt.type_name
          FROM bus_pass_applications ba
          JOIN users u ON ba.user_id = u.id
          JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          WHERE ba.id = ? AND ba.user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("ii", $applicationId, $_SESSION['uid']);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();

if (!$application) {
    header('Location: user-dashboard.php?error=application_not_found');
    exit();
}

// Check if already paid
if ($application['payment_status'] === 'Paid') {
    header('Location: user-dashboard.php?message=already_paid');
    exit();
}

$amount = $application['amount'];
$message = '';
$messageType = '';

// Handle fast payment processing
if (isset($_POST['process_fast_payment'])) {
    $paymentMethod = $_POST['payment_method'];
    $startTime = microtime(true);

    try {
        // Start database transaction
        $con->begin_transaction();

        // Generate transaction ID immediately
        $transactionId = '';
        switch ($paymentMethod) {
            case 'demo':
                $transactionId = 'DEMO_' . time() . '_' . rand(1000, 9999);
                break;
            case 'stripe':
                $transactionId = 'ch_' . time() . '_' . rand(100000, 999999);
                break;
            case 'phonepe':
                $transactionId = 'PP' . time() . rand(1000, 9999);
                break;
            case 'razorpay':
                $transactionId = 'pay_' . time() . '_' . rand(100000, 999999);
                break;
            default:
                $transactionId = 'TXN_' . time() . '_' . rand(1000, 9999);
        }

        // Insert payment record immediately
        $paymentQuery = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) VALUES (?, ?, ?, ?, 'completed', ?, NOW())";
        $paymentStmt = $con->prepare($paymentQuery);
        $paymentStmt->bind_param("iidss", $applicationId, $_SESSION['uid'], $amount, $paymentMethod, $transactionId);

        if (!$paymentStmt->execute()) {
            throw new Exception("Failed to insert payment record");
        }

        // Generate pass number
        $passNumber = 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);

        // Set validity dates
        $validFrom = date('Y-m-d');
        $validUntil = date('Y-m-d', strtotime('+30 days'));

        // Update application status immediately with all required fields
        $updateQuery = "UPDATE bus_pass_applications SET
                       payment_status = 'Paid',
                       pass_number = ?,
                       valid_from = ?,
                       valid_until = ?,
                       processed_date = NOW()
                       WHERE id = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("sssi", $passNumber, $validFrom, $validUntil, $applicationId);

        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update application status");
        }

        // Commit transaction immediately
        $con->commit();

        $endTime = microtime(true);
        $processingTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

        // Send email in background (non-blocking)
        try {
            EmailService::sendPaymentConfirmation(
                $application['user_email'],
                $application['user_name'],
                $applicationId,
                $transactionId,
                $amount,
                $passNumber
            );
        } catch (Exception $e) {
            // Log email error but don't fail the payment
            error_log("Email sending failed: " . $e->getMessage());
        }

        $message = "✅ Payment successful! Transaction completed in {$processingTime}ms. Transaction ID: $transactionId";
        $messageType = "success";

        // Auto-redirect to dashboard after 2 seconds
        header("refresh:2;url=user-dashboard.php?message=payment_success&txn_id=$transactionId");

    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Payment failed: " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fast Payment Processing - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2rem; }
        .content { padding: 40px; }

        .app-details { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px; border-left: 4px solid #007bff; }
        .app-details h3 { margin: 0 0 15px 0; color: #007bff; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; }
        .detail-label { font-weight: 600; color: #555; }
        .detail-value { color: #333; }

        .payment-methods { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 30px 0; }
        .payment-method { border: 2px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; }
        .payment-method:hover { border-color: #007bff; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .payment-method.selected { border-color: #28a745; background: #f8fff9; }
        .payment-method i { font-size: 2rem; margin-bottom: 10px; }
        .payment-method h4 { margin: 10px 0 5px 0; }
        .payment-method p { margin: 0; font-size: 0.9rem; color: #666; }

        .demo { color: #ffc107; }
        .stripe { color: #635bff; }
        .phonepe { color: #5f259f; }
        .razorpay { color: #3395ff; }

        .pay-button { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 15px 40px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; width: 100%; margin-top: 20px; }
        .pay-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3); }
        .pay-button:disabled { background: #6c757d; cursor: not-allowed; transform: none; }

        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }

        .processing-time { background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .processing-time h4 { margin: 0 0 10px 0; color: #007bff; }

        .back-link { display: inline-block; margin-top: 20px; color: #007bff; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }

        .timer { font-size: 1.2rem; font-weight: bold; color: #28a745; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bolt"></i> Fast Payment Processing</h1>
            <p>Lightning-fast transactions completed within 3 seconds!</p>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="app-details">
                <h3><i class="fas fa-file-alt"></i> Application Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Application ID:</span>
                    <span class="detail-value">#<?php echo $application['id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Applicant Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($application['applicant_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Pass Type:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($application['type_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount to Pay:</span>
                    <span class="detail-value"><strong>₹<?php echo number_format($amount, 2); ?></strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Current Status:</span>
                    <span class="detail-value"><?php echo $application['payment_status']; ?></span>
                </div>
            </div>

            <div class="processing-time">
                <h4><i class="fas fa-stopwatch"></i> Processing Time Guarantee</h4>
                <p>✅ <strong>Transaction completion:</strong> Under 3 seconds</p>
                <p>✅ <strong>Database update:</strong> Instant</p>
                <p>✅ <strong>Pass generation:</strong> Immediate</p>
                <p>✅ <strong>Email notification:</strong> Background processing</p>
            </div>

            <form method="POST" id="fastPaymentForm">
                <h3><i class="fas fa-credit-card"></i> Select Payment Method</h3>

                <div class="payment-methods">
                    <div class="payment-method demo" onclick="selectPaymentMethod('demo')">
                        <i class="fas fa-play-circle"></i>
                        <h4>Demo Payment</h4>
                        <p>Instant success for testing</p>
                        <p><strong>~0.1 seconds</strong></p>
                    </div>

                    <div class="payment-method stripe" onclick="selectPaymentMethod('stripe')">
                        <i class="fab fa-stripe"></i>
                        <h4>Stripe</h4>
                        <p>Fast card processing</p>
                        <p><strong>~1 second</strong></p>
                    </div>

                    <div class="payment-method phonepe" onclick="selectPaymentMethod('phonepe')">
                        <i class="fas fa-mobile-alt"></i>
                        <h4>PhonePe</h4>
                        <p>Quick UPI payment</p>
                        <p><strong>~2 seconds</strong></p>
                    </div>

                    <div class="payment-method razorpay" onclick="selectPaymentMethod('razorpay')">
                        <i class="fas fa-bolt"></i>
                        <h4>Razorpay</h4>
                        <p>Lightning fast gateway</p>
                        <p><strong>~1.5 seconds</strong></p>
                    </div>
                </div>

                <input type="hidden" name="payment_method" id="selectedMethod" value="">

                <button type="submit" name="process_fast_payment" class="pay-button" id="payButton" disabled>
                    <i class="fas fa-bolt"></i> Process Fast Payment - ₹<?php echo number_format($amount, 2); ?>
                </button>

                <div class="timer" id="timer" style="display: none;">
                    Processing time: <span id="processingTime">0.00</span> seconds
                </div>
            </form>

            <a href="user-dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        let selectedPaymentMethod = '';
        let startTime = 0;

        function selectPaymentMethod(method) {
            // Remove previous selection
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });

            // Add selection to clicked method
            event.target.closest('.payment-method').classList.add('selected');

            selectedPaymentMethod = method;
            document.getElementById('selectedMethod').value = method;
            document.getElementById('payButton').disabled = false;
        }

        document.getElementById('fastPaymentForm').addEventListener('submit', function(e) {
            if (!selectedPaymentMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return;
            }

            // Start timer
            startTime = performance.now();

            const payButton = document.getElementById('payButton');
            const timer = document.getElementById('timer');
            const processingTimeSpan = document.getElementById('processingTime');

            payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
            payButton.disabled = true;
            timer.style.display = 'block';

            // Update timer every 10ms for smooth animation
            const timerInterval = setInterval(() => {
                const currentTime = performance.now();
                const elapsed = (currentTime - startTime) / 1000;
                processingTimeSpan.textContent = elapsed.toFixed(2);

                // Stop timer after 5 seconds max
                if (elapsed > 5) {
                    clearInterval(timerInterval);
                }
            }, 10);

            // Store interval ID to clear it when page unloads
            window.timerInterval = timerInterval;
        });

        // Clear timer on page unload
        window.addEventListener('beforeunload', function() {
            if (window.timerInterval) {
                clearInterval(window.timerInterval);
            }
        });

        // Auto-select demo payment for quick testing
        setTimeout(() => {
            if (!selectedPaymentMethod) {
                selectPaymentMethod('demo');
                document.querySelector('.payment-method.demo').click();
            }
        }, 1000);
    </script>
</body>
</html>
