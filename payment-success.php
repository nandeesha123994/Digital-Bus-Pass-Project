<?php
/**
 * Payment Success Handler
 * Processes successful payments from PhonePe prototype and updates application status
 */

session_start();
include('includes/dbconnection.php');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// Get parameters from PhonePe success callback
$applicationId = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;
$transactionId = isset($_GET['txn_id']) ? htmlspecialchars($_GET['txn_id']) : '';
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

if (!$applicationId || !$transactionId || !$amount) {
    header('Location: user-dashboard.php?error=invalid_payment_data');
    exit();
}

try {
    // Verify application belongs to current user
    $verifyQuery = "SELECT * FROM bus_pass_applications WHERE id = ? AND user_id = ?";
    $verifyStmt = $con->prepare($verifyQuery);
    $verifyStmt->bind_param("ii", $applicationId, $_SESSION['uid']);
    $verifyStmt->execute();
    $application = $verifyStmt->get_result()->fetch_assoc();
    
    if (!$application) {
        header('Location: user-dashboard.php?error=application_not_found');
        exit();
    }
    
    // Check if already paid
    if ($application['payment_status'] == 'Paid') {
        header('Location: user-dashboard.php?message=already_paid');
        exit();
    }
    
    // Start database transaction
    $con->begin_transaction();
    
    try {
        // Insert payment record
        $paymentQuery = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) 
                        VALUES (?, ?, ?, 'phonepe', 'completed', ?, NOW())";
        $paymentStmt = $con->prepare($paymentQuery);
        $paymentStmt->bind_param("iids", $applicationId, $_SESSION['uid'], $amount, $transactionId);
        
        if (!$paymentStmt->execute()) {
            throw new Exception("Failed to insert payment record");
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
                       admin_remarks = 'Payment completed via PhonePe - Auto-approved'
                       WHERE id = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("sssi", $passNumber, $validFrom, $validUntil, $applicationId);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update application status");
        }
        
        // Commit transaction
        $con->commit();
        
        // Redirect to user dashboard with success message
        header('Location: user-dashboard.php?payment_success=1&txn_id=' . urlencode($transactionId) . '&pass_number=' . urlencode($passNumber));
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $con->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Payment Success Error: ' . $e->getMessage());
    header('Location: user-dashboard.php?error=payment_processing_failed');
    exit();
}

$con->close();
?>
