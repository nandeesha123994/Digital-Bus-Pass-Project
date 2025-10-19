<?php
ob_start(); // Start output buffering
session_start();
include('includes/dbconnection.php');
include('includes/config.php');
include('includes/email.php');

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$messageType = '';
$applicationId = isset($_GET['application_id']) ? (int)$_GET['application_id'] : 0;

if (!$applicationId) {
    header("Location: user-dashboard.php");
    exit();
}

// Get application details
$query = "SELECT ba.*, bpt.type_name, bpt.duration_days, u.full_name as user_name, u.email as user_email
          FROM bus_pass_applications ba
          JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          JOIN users u ON ba.user_id = u.id
          WHERE ba.id = ? AND ba.user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("ii", $applicationId, $_SESSION['uid']);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();

if (!$application) {
    header("Location: user-dashboard.php");
    exit();
}

// Check if already paid
if ($application['payment_status'] == 'Paid') {
    header("Location: user-dashboard.php?message=already_paid");
    exit();
}

$amount = $application['amount'];
$tax = calculateTax($amount - calculateTax($amount)); // Remove tax to get base amount
$baseAmount = $amount - $tax;

// Handle payment processing
if (isset($_POST['process_payment'])) {
    $paymentMethod = $_POST['payment_method'];

    if (in_array($paymentMethod, ['stripe', 'phonepe', 'demo'])) {
        $success = false;
        $transactionId = '';
        $errorMessage = '';

        switch ($paymentMethod) {
            case 'demo':
                // Demo payment - always successful
                $transactionId = 'DEMO_' . time() . '_' . rand(1000, 9999);
                $success = true;
                break;

            case 'stripe':
                // Stripe payment processing
                if (isset($_POST['stripe_token'])) {
                    // Get card details for logging (never store sensitive data)
                    $cardLast4 = isset($_POST['card_number']) ? substr(str_replace(' ', '', $_POST['card_number']), -4) : '****';
                    $result = processStripePayment($_POST['stripe_token'], $amount, $applicationId, $cardLast4);
                    $success = $result['success'];
                    $transactionId = $result['transaction_id'] ?? '';
                    $errorMessage = $result['error'] ?? '';
                } else {
                    $errorMessage = "Stripe token missing";
                }
                break;

            case 'phonepe':
                // Redirect to PhonePe prototype interface
                $orderId = 'BP' . date('YmdHis') . $applicationId;
                $merchant = 'Bus Pass System';
                $redirectUrl = 'payment-success.php?application_id=' . $applicationId;

                $phonePeUrl = "phonepe-payment.php?amount=" . urlencode($amount) .
                             "&merchant=" . urlencode($merchant) .
                             "&order_id=" . urlencode($orderId) .
                             "&redirect=" . urlencode($redirectUrl) .
                             "&application_id=" . urlencode($applicationId);

                header("Location: $phonePeUrl");
                exit();
                break;
        }

        if ($success && !empty($transactionId)) {
            // Start transaction for data consistency
            $con->begin_transaction();

            try {
                // Debug log
                error_log("Starting payment processing for application ID: " . $applicationId);

                // Insert payment record
                $paymentQuery = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) VALUES (?, ?, ?, ?, 'completed', ?, NOW())";
                $paymentStmt = $con->prepare($paymentQuery);
                $paymentStmt->bind_param("iidss", $applicationId, $_SESSION['uid'], $amount, $paymentMethod, $transactionId);

                if (!$paymentStmt->execute()) {
                    throw new Exception("Failed to insert payment record");
                }

                error_log("Payment record inserted successfully");

                // Get pass type for reward points
                $passTypeQuery = "SELECT bpt.type_name 
                                 FROM bus_pass_applications ba 
                                 JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id 
                                 WHERE ba.id = ?";
                $passTypeStmt = $con->prepare($passTypeQuery);
                $passTypeStmt->bind_param("i", $applicationId);
                $passTypeStmt->execute();
                $passTypeResult = $passTypeStmt->get_result();
                $passType = $passTypeResult->fetch_assoc();

                error_log("Pass type retrieved: " . ($passType ? $passType['type_name'] : 'Not found'));

                if ($passType) {
                    // Get reward points for this pass type
                    $pointsQuery = "SELECT points_awarded FROM rewards_rules WHERE pass_type = ?";
                    $pointsStmt = $con->prepare($pointsQuery);
                    $pointsStmt->bind_param("s", $passType['type_name']);
                    $pointsStmt->execute();
                    $pointsResult = $pointsStmt->get_result();
                    $pointsRule = $pointsResult->fetch_assoc();

                    error_log("Reward points rule found: " . ($pointsRule ? $pointsRule['points_awarded'] : 'Not found'));

                    if ($pointsRule) {
                        $pointsEarned = $pointsRule['points_awarded'];

                        // Update user's reward points
                        $updateUserQuery = "UPDATE users SET reward_points = reward_points + ? WHERE id = ?";
                        $updateUserStmt = $con->prepare($updateUserQuery);
                        $updateUserStmt->bind_param("ii", $pointsEarned, $_SESSION['uid']);
                        
                        if (!$updateUserStmt->execute()) {
                            throw new Exception("Failed to update reward points");
                        }

                        // Record the reward transaction
                        $transactionQuery = "INSERT INTO rewards_transactions 
                                           (user_id, pass_type, points_earned, application_id, description) 
                                           VALUES (?, ?, ?, ?, ?)";
                        $transactionStmt = $con->prepare($transactionQuery);
                        $description = "Points earned for purchasing " . $passType['type_name'];
                        $transactionStmt->bind_param("isiss", $_SESSION['uid'], $passType['type_name'], $pointsEarned, $applicationId, $description);
                        
                        if (!$transactionStmt->execute()) {
                            throw new Exception("Failed to record reward transaction");
                        }
                    }
                }

                // Generate pass number
                $passNumber = 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);

                // Set validity dates
                $validFrom = date('Y-m-d');
                $validUntil = date('Y-m-d', strtotime('+30 days'));

                // Update application with payment status, pass number, and validity dates
                $updateQuery = "UPDATE bus_pass_applications SET
                               payment_status = 'Paid',
                               status = 'Approved',
                               pass_number = ?,
                               valid_from = ?,
                               valid_until = ?,
                               processed_date = NOW(),
                               admin_remarks = 'Payment completed - Auto-approved'
                               WHERE id = ?";
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("sssi", $passNumber, $validFrom, $validUntil, $applicationId);

                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update application status");
                }

                // Commit transaction
                $con->commit();

                // Send payment confirmation email
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

                // Store success message in session
                $_SESSION['payment_success'] = true;
                $_SESSION['payment_message'] = "Payment successful! Your bus pass has been approved.";
                $_SESSION['pass_number'] = $passNumber;
                $_SESSION['transaction_id'] = $transactionId;

                // Clear any output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Ensure no output before redirect
                if (!headers_sent()) {
                    // Redirect to user dashboard with success parameters
                    header("Location: user-dashboard.php?status=success&pass=" . urlencode($passNumber) . "&txn=" . urlencode($transactionId));
                    exit();
                } else {
                    // Fallback if headers were already sent
                    echo "<script>window.location.href='user-dashboard.php?status=success&pass=" . urlencode($passNumber) . "&txn=" . urlencode($transactionId) . "';</script>";
                    echo "<noscript><meta http-equiv='refresh' content='0;url=user-dashboard.php?status=success&pass=" . urlencode($passNumber) . "&txn=" . urlencode($transactionId) . "'></noscript>";
                    exit();
                }

            } catch (Exception $e) {
                // Rollback transaction
                $con->rollback();
                
                // Store error message in session
                $_SESSION['payment_error'] = true;
                $_SESSION['payment_message'] = "Payment processing failed: " . $e->getMessage();
                
                // Clear any output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Ensure no output before redirect
                if (!headers_sent()) {
                    // Redirect to user dashboard with error
                    header("Location: user-dashboard.php?status=error");
                    exit();
                } else {
                    // Fallback if headers were already sent
                    echo "<script>window.location.href='user-dashboard.php?status=error';</script>";
                    echo "<noscript><meta http-equiv='refresh' content='0;url=user-dashboard.php?status=error'></noscript>";
                    exit();
                }
            }
        } else {
            // Store error message in session
            $_SESSION['payment_error'] = true;
            $_SESSION['payment_message'] = "Payment failed: " . ($errorMessage ?: "Unknown error occurred");
            
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Ensure no output before redirect
            if (!headers_sent()) {
                // Redirect to user dashboard with error
                header("Location: user-dashboard.php?status=error");
                exit();
            } else {
                // Fallback if headers were already sent
                echo "<script>window.location.href='user-dashboard.php?status=error';</script>";
                echo "<noscript><meta http-equiv='refresh' content='0;url=user-dashboard.php?status=error'></noscript>";
                exit();
            }
        }
    } else {
        $message = "Invalid payment method selected.";
        $messageType = "error";
    }
}

// Payment processing functions
function processStripePayment($token, $amount, $applicationId, $cardLast4 = '****') {
    // This is a simplified Stripe integration for demo purposes
    // In production, use Stripe PHP SDK and proper error handling

    // For demo purposes, we'll simulate different outcomes based on token
    if (strpos($token, 'tok_') === 0) {
        // Simulate successful payment
        $transactionId = 'ch_' . time() . '_' . rand(100000, 999999);

        // Log the payment attempt (in production, use proper logging)
        error_log("Stripe Payment: Application #$applicationId, Amount: $amount, Card: ****$cardLast4, Token: $token");

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'card_last4' => $cardLast4
        ];
    }

    // For real Stripe integration, uncomment and configure:
    /*
    $stripeSecretKey = STRIPE_SECRET_KEY;

    $postData = [
        'amount' => $amount * 100, // Stripe expects amount in cents
        'currency' => 'usd',
        'source' => $token,
        'description' => "Bus Pass Application #$applicationId",
        'metadata' => [
            'application_id' => $applicationId,
            'card_last4' => $cardLast4
        ]
    ];

    $ch = curl_init('https://api.stripe.com/v1/charges');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $stripeSecretKey,
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['paid']) && $data['paid']) {
            return [
                'success' => true,
                'transaction_id' => $data['id'],
                'card_last4' => $data['source']['last4'] ?? $cardLast4
            ];
        }
    }

    $errorData = json_decode($response, true);
    return [
        'success' => false,
        'error' => $errorData['error']['message'] ?? 'Stripe payment failed'
    ];
    */

    return [
        'success' => false,
        'error' => 'Invalid payment token'
    ];
}

function processPhonePePayment($transactionId, $amount, $applicationId) {
    // PhonePe payment verification
    // For demo purposes, we'll simulate PhonePe payment verification

    // Demo mode - simulate successful payment
    if (!empty($transactionId)) {
        // Validate transaction ID format (PhonePe typically uses alphanumeric IDs)
        if (preg_match('/^[A-Za-z0-9_-]+$/', $transactionId)) {
            logError("Demo PhonePe payment verified: $transactionId for application $applicationId");
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'method' => 'phonepe'
            ];
        } else {
            logError("Demo PhonePe payment failed: invalid transaction ID format for application $applicationId");
            return [
                'success' => false,
                'error' => 'Invalid transaction ID format'
            ];
        }
    } else {
        logError("Demo PhonePe payment failed: empty transaction ID for application $applicationId");
        return [
            'success' => false,
            'error' => 'Transaction ID is required'
        ];
    }

    /*
    // For real PhonePe integration, you would implement:
    // 1. PhonePe API verification
    // 2. Merchant ID and Salt Key validation
    // 3. Checksum verification
    // 4. Transaction status checking

    // Example PhonePe verification (commented out for demo):
    $merchantId = 'YOUR_MERCHANT_ID';
    $saltKey = 'YOUR_SALT_KEY';
    $saltIndex = 1;

    // Create checksum for verification
    $checksum = hash('sha256', '/pg/v1/status/' . $merchantId . '/' . $transactionId . $saltKey) . '###' . $saltIndex;

    // Make API call to PhonePe
    $url = 'https://api.phonepe.com/apis/hermes/pg/v1/status/' . $merchantId . '/' . $transactionId;
    $headers = [
        'Content-Type: application/json',
        'X-VERIFY: ' . $checksum,
        'X-MERCHANT-ID: ' . $merchantId
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data['success'] && $data['data']['state'] === 'COMPLETED') {
            return [
                'success' => true,
                'transaction_id' => $data['data']['transactionId'],
                'amount_paid' => $data['data']['amount'] / 100,
                'method' => 'phonepe'
            ];
        }
    }

    return [
        'success' => false,
        'error' => 'PhonePe payment verification failed'
    ];
    */
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Bus Pass Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(to right, #6A11CB, #2575FC);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Stylish Header */
        .header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.1)"/></svg>') center/cover;
            opacity: 0.1;
        }

        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .bus-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            gap: 1rem;
        }

        .payment-method {
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .payment-method:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .payment-method.active {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .payment-method i {
            font-size: 1.5rem;
            color: #3b82f6;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 117, 252, 0.2);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #1e293b;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Order Summary */
        .order-summary {
            background: white;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
        }

        .order-summary h2 {
            margin: 0 0 1rem;
            font-size: 1.5rem;
            color: #1e293b;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: #64748b;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-weight: 600;
            color: #1e293b;
        }

        /* Change Payment Method */
        .change-method {
            display: none;
            margin-top: 0.5rem;
            color: #3b82f6;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .payment-method.active + .change-method {
            display: block;
        }

        /* Success Animation */
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .success-checkmark {
            color: #10b981;
            font-size: 3rem;
            animation: checkmark 0.5s ease-in-out;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 1.5rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .container {
                margin: 1rem auto;
            }

            .card {
                padding: 1rem;
            }
        }

        /* PhonePe UPI Modal Styles */
        .phonepe-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .phonepe-modal.show {
            display: flex;
        }

        .phonepe-container {
            width: 400px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .phonepe-header {
            background: #5f259f;
            padding: 20px;
            text-align: center;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .phonepe-header img {
            width: 32px;
            height: 32px;
        }

        .phonepe-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .phonepe-content {
            padding: 25px;
        }

        .upi-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .upi-group {
            position: relative;
        }

        .upi-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
            font-weight: 500;
        }

        .upi-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
            pointer-events: none;
        }

        .upi-input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
            box-sizing: border-box;
        }

        .upi-input:focus {
            border-color: #5f259f;
            box-shadow: 0 0 0 2px rgba(95, 37, 159, 0.1);
            outline: none;
        }

        .upi-input.error {
            border-color: #dc3545;
        }

        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .upi-example {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .upi-verify-btn {
            background: #5f259f;
            color: white;
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .upi-verify-btn:hover {
            background: #4a1d7a;
        }

        .upi-verify-btn:active {
            transform: translateY(1px);
        }

        .upi-alternatives {
            margin-top: 20px;
            text-align: center;
        }

        .upi-alternatives p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .upi-apps {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .upi-app-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .upi-app-icon:hover {
            transform: scale(1.1);
        }

        /* Loading and Success States */
        .upi-loading {
            display: none;
            text-align: center;
            padding: 30px;
        }

        .upi-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #5f259f;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        .upi-loading p {
            font-size: 16px;
            margin-top: 15px;
            color: #666;
        }

        .upi-success {
            display: none;
            text-align: center;
            padding: 30px;
        }

        .upi-success-icon {
            color: #28a745;
            font-size: 64px;
            margin: 20px 0;
            animation: scaleIn 0.5s ease-out;
        }

        .upi-success h3 {
            font-size: 24px;
            margin: 15px 0;
            color: #333;
        }

        .upi-success p {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            .phonepe-container {
                width: 90%;
                margin: 15px auto;
            }

            .phonepe-content {
                padding: 20px;
            }
        }

        /* Modern Debit Card Payment Styles */
        .debit-card-container {
            width: 400px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            margin: 20px auto;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow: hidden;
            position: relative;
        }

        .debit-card-header {
            background: #1a73e8;
            padding: 20px;
            text-align: center;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .debit-card-header img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .debit-card-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .debit-card-content {
            padding: 25px;
        }

        .card-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            position: relative;
            margin-bottom: 5px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
            pointer-events: none;
            z-index: 1;
        }

        .card-input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
            box-sizing: border-box;
        }

        .card-input:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.1);
            outline: none;
        }

        .card-row {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .card-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .save-card {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }

        .save-card input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1a73e8;
            margin: 0;
        }

        .save-card label {
            margin: 0;
            font-weight: normal;
        }

        .card-pay-btn {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s ease;
        }

        .card-pay-btn:hover {
            background: #1557b0;
        }

        .card-pay-btn:active {
            transform: translateY(1px);
        }

        /* Loading and Success States */
        .card-loading {
            display: none;
            text-align: center;
            padding: 30px;
        }

        .card-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1a73e8;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        .card-loading p {
            font-size: 16px;
            margin-top: 15px;
            color: #666;
        }

        .card-success {
            display: none;
            text-align: center;
            padding: 30px;
        }

        .card-success-icon {
            color: #28a745;
            font-size: 64px;
            margin: 20px 0;
            animation: scaleIn 0.5s ease-out;
        }

        .card-success h3 {
            font-size: 24px;
            margin: 15px 0;
            color: #333;
        }

        .card-success p {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }

        .card-download-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 20px auto;
            transition: all 0.3s ease;
        }

        .card-download-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .card-download-btn i {
            font-size: 18px;
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            .debit-card-container {
                width: 90%;
                margin: 15px auto;
            }

            .debit-card-content {
                padding: 20px;
            }

            .card-row {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* Animations */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        /* Modal Styles */
        .debit-card-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .debit-card-modal.show {
            display: flex !important;
        }

        .payment-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
        }

        .back-button {
            position: absolute;
            left: 0;
            display: flex;
            align-items: center;
            padding: 8px 16px;
            background: #f8f9fa;
            border-radius: 5px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: #e9ecef;
            transform: translateX(-2px);
        }

        .back-button i {
            margin-right: 8px;
        }

        .payment-header h2 {
            width: 100%;
            text-align: center;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="bus-icon">
            <i class="fas fa-bus"></i>
        </div>
        <h1>Payment Dashboard</h1>
        <p>Complete your bus pass payment securely</p>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="card">
                <h2>Select Payment Method</h2>
                <div class="payment-methods">
                    <div class="payment-method" data-method="demo">
                        <i class="fas fa-credit-card"></i>
                        <div>
                            <h3>Demo Payment</h3>
                            <p>Test payment (no real charges)</p>
                        </div>
                    </div>
                    <div class="change-method">Change Method</div>

                    <div class="payment-method" data-method="stripe">
                        <i class="fab fa-stripe"></i>
                        <div>
                            <h3>Credit/Debit Card</h3>
                            <p>Pay with Stripe</p>
                        </div>
                    </div>
                    <div class="change-method">Change Method</div>

                    <div class="payment-method" data-method="phonepe">
                        <i class="fas fa-mobile-alt"></i>
                        <div>
                            <h3>PhonePe</h3>
                            <p>UPI & Wallet payments</p>
                        </div>
                    </div>
                    <div class="change-method">Change Method</div>
                </div>
            </div>

            <div class="card">
                <h2>Secure Payment</h2>
                <p>Your payment information is encrypted and secure. We never store your card details.</p>
                <div class="security-badges">
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-shield-alt"></i>
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>

            <div class="card">
                <h2>Need Help?</h2>
                <p>Contact our support team for assistance with your payment.</p>
                <button class="btn btn-secondary" onclick="window.location.href='contact-support.php'">
                    <i class="fas fa-headset"></i>
                    Contact Support
                </button>
            </div>
        </div>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="summary-item">
                <span>Base Amount</span>
                <span>₹<?php echo number_format($baseAmount, 2); ?></span>
            </div>
            <div class="summary-item">
                <span>GST (<?php echo TAX_RATE * 100; ?>%)</span>
                <span>₹<?php echo number_format($tax, 2); ?></span>
            </div>
            <div class="summary-total">
                <span>Total Amount</span>
                <span>₹<?php echo number_format($amount, 2); ?></span>
            </div>
            <button class="btn btn-primary" id="payButton">
                <i class="fas fa-lock"></i>
                Pay Now
            </button>
        </div>
    </div>

    <!-- PhonePe UPI Modal -->
    <div class="phonepe-modal" id="phonepeModal">
        <div class="phonepe-container">
            <div class="phonepe-header">
                <img src="assets/images/phonepe-logo.png" alt="PhonePe Logo">
                <h2>PhonePe UPI Payment</h2>
            </div>
            <div class="phonepe-content">
                <form class="upi-form" id="upiForm">
                    <div class="upi-group">
                        <label for="upiId">Enter your UPI ID</label>
                        <i class="fas fa-user"></i>
                        <input type="text" id="upiId" class="upi-input" 
                               placeholder="9008723711@ybl" 
                               pattern="^[\w.\-_]{2,50}@[a-zA-Z]{2,15}$"
                               required>
                        <div class="error-message">Please enter a valid UPI ID</div>
                        <div class="upi-example">Example: 9008723711@ybl, username@okhdfcbank</div>
                    </div>
                    <button type="submit" class="upi-verify-btn">Verify & Proceed</button>
                </form>
                <div class="upi-alternatives">
                    <p>Or pay with another UPI App</p>
                    <div class="upi-apps">
                        <img src="assets/images/gpay-logo.png" alt="Google Pay" class="upi-app-icon">
                        <img src="assets/images/paytm-logo.png" alt="Paytm" class="upi-app-icon">
                    </div>
                </div>
                <div class="upi-loading">
                    <div class="upi-spinner"></div>
                    <p>Verifying UPI ID...</p>
                </div>
                <div class="upi-success">
                    <i class="fas fa-check-circle upi-success-icon"></i>
                    <h3>UPI ID Verified!</h3>
                    <p>Please complete the payment in your UPI app</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Debit Card Payment Modal -->
    <div class="debit-card-modal" id="debitCardModal">
        <div class="debit-card-container">
            <div class="debit-card-header">
                <img src="assets/images/debit-card-icon.png" alt="Debit Card" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiPjxwYXRoIGQ9Ik0yMCA0SDRDMy40NDcgNCAzIDQuNDQ3IDMgNVYxOUMzIDE5LjU1MyAzLjQ0NyAyMCA0IDIwSDIwQzIwLjU1MyAyMCAyMSAxOS41NTMgMjEgMTlWNUMyMSA0LjQ0NyAyMC41NTMgNCAyMCA0WiIvPjxwYXRoIGQ9Ik0zIDEwSDIxIi8+PC9zdmc+'" />
                <h2>Debit Card Payment</h2>
            </div>
            <div class="debit-card-content">
                <form class="card-form" id="debitCardForm">
                    <div class="form-group">
                        <label for="cardNumber">Card Number</label>
                        <i class="fas fa-credit-card"></i>
                        <input type="text" id="cardNumber" class="card-input" placeholder="XXXX XXXX XXXX" maxlength="14" required>
                    </div>
                    <div class="card-row">
                        <div class="form-group">
                            <label for="expiryDate">Expiry Date</label>
                            <i class="fas fa-calendar"></i>
                            <input type="text" id="expiryDate" class="card-input" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="cvv" class="card-input" placeholder="***" maxlength="3" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cardName">Cardholder Name</label>
                        <i class="fas fa-user"></i>
                        <input type="text" id="cardName" class="card-input" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="save-card">
                        <input type="checkbox" id="saveCard">
                        <label for="saveCard">Save card for future use (securely encrypted)</label>
                    </div>
                    <button type="submit" class="card-pay-btn">Pay ₹<?php echo number_format($amount, 2); ?></button>
                </form>
                <div class="card-loading">
                    <div class="card-spinner"></div>
                    <p>Processing payment...</p>
                </div>
                <div class="card-success">
                    <i class="fas fa-check-circle card-success-icon"></i>
                    <h3>Payment Successful!</h3>
                    <p>Your payment has been processed successfully.</p>
                    <button class="card-download-btn" onclick="downloadReceipt()">
                        <i class="fas fa-file-invoice"></i> Download Payment Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="payment-header">
        <a href="user-dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h2>Payment Details</h2>
    </div>

    <script>
        // Payment Method Selection
        const paymentMethods = document.querySelectorAll('.payment-method');
        let selectedMethod = null;

        paymentMethods.forEach(method => {
            method.addEventListener('click', () => {
                // Remove active class from all methods
                paymentMethods.forEach(m => m.classList.remove('active'));
                // Add active class to selected method
                method.classList.add('active');
                selectedMethod = method.dataset.method;
            });
        });

        // Change Method Button
        document.querySelectorAll('.change-method').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const method = button.previousElementSibling;
                method.classList.remove('active');
                selectedMethod = null;
            });
        });

        // Pay Button Click
        document.getElementById('payButton').addEventListener('click', () => {
            if (!selectedMethod) {
                alert('Please select a payment method');
                return;
            }

            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="process_payment" value="1">
                <input type="hidden" name="payment_method" value="${selectedMethod}">
            `;
            document.body.appendChild(form);
            form.submit();
        });

        // Show PhonePe Modal
        function showPhonePeModal() {
            const modal = document.getElementById('phonepeModal');
            modal.classList.add('show');
        }

        // Close modal when clicking outside
        document.getElementById('phonepeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });

        // Add click event listener to the PhonePe payment method
        document.addEventListener('DOMContentLoaded', function() {
            const phonepeButton = document.querySelector('.payment-method[data-method="phonepe"]');
            if (phonepeButton) {
                phonepeButton.addEventListener('click', function() {
                    showPhonePeModal();
                });
            }
        });

        // UPI ID validation
        const upiInput = document.getElementById('upiId');
        const upiForm = document.getElementById('upiForm');
        const errorMessage = document.querySelector('.error-message');

        upiInput.addEventListener('input', function() {
            const upiRegex = /^[\w.\-_]{2,50}@[a-zA-Z]{2,15}$/;
            const isValid = upiRegex.test(this.value);
            
            this.classList.toggle('error', !isValid);
            errorMessage.style.display = isValid ? 'none' : 'block';
        });

        // Handle form submission
        upiForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const upiId = upiInput.value;
            const upiRegex = /^[\w.\-_]{2,50}@[a-zA-Z]{2,15}$/;
            
            if (upiRegex.test(upiId)) {
                // Show loading state
                document.querySelector('.upi-form').style.display = 'none';
                document.querySelector('.upi-alternatives').style.display = 'none';
                document.querySelector('.upi-loading').style.display = 'block';
                
                // Simulate UPI verification
                setTimeout(() => {
                    document.querySelector('.upi-loading').style.display = 'none';
                    document.querySelector('.upi-success').style.display = 'block';
                }, 2000);
            } else {
                upiInput.classList.add('error');
                errorMessage.style.display = 'block';
            }
        });

        // Download Receipt
        function downloadReceipt() {
            // Get payment details
            const transactionId = '<?php echo $transactionId ?? ''; ?>';
            const amount = '<?php echo $amount ?? ''; ?>';
            const date = new Date().toLocaleDateString();
            const time = new Date().toLocaleTimeString();
            
            // Create receipt content
            const receiptContent = `
                ====================================
                BUS PASS PAYMENT RECEIPT
                ====================================
                Transaction ID: ${transactionId}
                Amount Paid: ₹${amount}
                Date: ${date}
                Time: ${time}
                Payment Method: Debit Card
                Status: Success
                ====================================
                Thank you for your payment!
                ====================================
            `;
            
            // Create blob and download
            const blob = new Blob([receiptContent], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `payment_receipt_${transactionId}.txt`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        // Show Debit Card Modal
        function showDebitCardModal() {
            console.log('Showing debit card modal');
            const modal = document.getElementById('debitCardModal');
            if (modal) {
                modal.classList.add('show');
                modal.style.display = 'flex';
            }
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('debitCardModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.remove('show');
                        this.style.display = 'none';
                    }
                });
            }

            // Add click event listener to the debit card payment method
            const debitCardButton = document.querySelector('.payment-method[data-method="stripe"]');
            if (debitCardButton) {
                console.log('Debit card button found');
                debitCardButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Debit card button clicked');
                    showDebitCardModal();
                });
            } else {
                console.log('Debit card button not found');
            }
        });

        // Format card number with spaces
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = '';
            for(let i = 0; i < value.length; i++) {
                if(i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            if (formattedValue.length > 14) formattedValue = formattedValue.substr(0, 14);
            e.target.value = formattedValue;
        });

        // Format expiry date
        document.getElementById('expiryDate').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substr(0, 2) + '/' + value.substr(2);
            }
            if (value.length > 5) value = value.substr(0, 5);
            e.target.value = value;
        });

        // Format CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 3) value = value.substr(0, 3);
            e.target.value = value;
        });

        // Validate card details
        function validateCardDetails(cardNumber, expiryDate, cvv, cardName) {
            let isValid = true;
            let errors = [];

            if (!cardNumber || cardNumber.replace(/\s/g, '').length !== 12) {
                errors.push('Please enter a valid 12-digit card number');
                isValid = false;
            }

            if (!expiryDate || !/^\d{2}\/\d{2}$/.test(expiryDate)) {
                errors.push('Please enter a valid expiry date (MM/YY)');
                isValid = false;
            }

            if (!cvv || cvv.length !== 3) {
                errors.push('Please enter a valid 3-digit CVV');
                isValid = false;
            }

            if (!cardName || cardName.length < 2) {
                errors.push('Please enter the cardholder name');
                isValid = false;
            }

            if (!isValid) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
            }

            return isValid;
        }

        // Handle form submission
        document.getElementById('debitCardForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const cardNumber = document.getElementById('cardNumber').value;
            const expiryDate = document.getElementById('expiryDate').value;
            const cvv = document.getElementById('cvv').value;
            const cardName = document.getElementById('cardName').value;
            
            if (validateCardDetails(cardNumber, expiryDate, cvv, cardName)) {
                // Show loading state
                document.querySelector('.card-form').style.display = 'none';
                document.querySelector('.card-loading').style.display = 'block';
                
                // Simulate payment processing
                setTimeout(() => {
                    document.querySelector('.card-loading').style.display = 'none';
                    document.querySelector('.card-success').style.display = 'block';
                }, 2000);
            }
        });
    </script>
</body>
</html>
