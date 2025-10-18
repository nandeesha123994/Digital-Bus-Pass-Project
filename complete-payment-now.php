<?php
session_start();
include('includes/dbconnection.php');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle instant payment completion
if (isset($_POST['complete_payment_now'])) {
    $applicationId = intval($_POST['application_id']);
    
    try {
        $con->begin_transaction();
        
        // Get application details
        $appQuery = "SELECT * FROM bus_pass_applications WHERE id = ? AND user_id = ?";
        $appStmt = $con->prepare($appQuery);
        $appStmt->bind_param("ii", $applicationId, $_SESSION['uid']);
        $appStmt->execute();
        $app = $appStmt->get_result()->fetch_assoc();
        
        if (!$app) {
            throw new Exception("Application not found");
        }
        
        // Generate transaction ID
        $transactionId = 'INSTANT_' . time() . '_' . rand(1000, 9999);
        
        // Insert payment record
        $paymentQuery = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) VALUES (?, ?, ?, 'instant', 'completed', ?, NOW())";
        $paymentStmt = $con->prepare($paymentQuery);
        $paymentStmt->bind_param("iids", $applicationId, $_SESSION['uid'], $app['amount'], $transactionId);
        
        if (!$paymentStmt->execute()) {
            throw new Exception("Failed to create payment record");
        }
        
        // Generate pass number
        $passNumber = 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);
        
        // Set validity dates
        $validFrom = date('Y-m-d');
        $validUntil = date('Y-m-d', strtotime('+30 days'));
        
        // Update application with all required fields for printing
        $updateQuery = "UPDATE bus_pass_applications SET 
                       status = 'Approved',
                       payment_status = 'Paid', 
                       pass_number = ?, 
                       valid_from = ?, 
                       valid_until = ?, 
                       processed_date = NOW(),
                       admin_remarks = 'Payment completed and auto-approved for printing'
                       WHERE id = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("sssi", $passNumber, $validFrom, $validUntil, $applicationId);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update application");
        }
        
        $con->commit();
        
        $message = "✅ Payment completed successfully! Your bus pass is now ready for printing. Transaction ID: $transactionId, Pass Number: $passNumber";
        $messageType = "success";
        
        // Auto-redirect to dashboard
        header("refresh:3;url=user-dashboard.php?message=payment_completed");
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get user's applications that need payment
$appsQuery = "SELECT ba.*, bpt.type_name 
              FROM bus_pass_applications ba
              JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
              WHERE ba.user_id = ? AND (ba.payment_status != 'Paid' OR ba.status != 'Approved' OR ba.pass_number IS NULL)
              ORDER BY ba.id DESC";
$appsStmt = $con->prepare($appsQuery);
$appsStmt->bind_param("i", $_SESSION['uid']);
$appsStmt->execute();
$applications = $appsStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Complete Payment Now - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.5rem; }
        .content { padding: 40px; }
        
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        
        .app-card { background: #f8f9fa; border: 2px solid #ddd; border-radius: 10px; padding: 25px; margin: 20px 0; transition: all 0.3s ease; }
        .app-card:hover { border-color: #28a745; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        
        .app-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .app-id { font-size: 1.2rem; font-weight: bold; color: #007bff; }
        .app-amount { font-size: 1.5rem; font-weight: bold; color: #28a745; }
        
        .app-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .detail-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-weight: 600; color: #555; }
        .detail-value { color: #333; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .payment-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .payment-paid { background: #d4edda; color: #155724; }
        .payment-pending { background: #fff3cd; color: #856404; }
        .payment-required { background: #f8d7da; color: #721c24; }
        
        .complete-button { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; width: 100%; margin-top: 20px; }
        .complete-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3); }
        
        .benefits { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .benefits h4 { margin: 0 0 15px 0; color: #007bff; }
        .benefits ul { margin: 0; padding-left: 20px; }
        .benefits li { margin: 8px 0; }
        
        .quick-links { display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap; }
        .quick-link { background: #007bff; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .quick-link:hover { background: #0056b3; transform: translateY(-2px); text-decoration: none; color: white; }
        
        .no-apps { text-align: center; padding: 40px; color: #666; }
        .no-apps i { font-size: 4rem; margin-bottom: 20px; color: #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bolt"></i> Complete Payment Now</h1>
            <p>Instant payment completion and bus pass activation</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="benefits">
                <h4><i class="fas fa-star"></i> What happens when you complete payment:</h4>
                <ul>
                    <li>✅ <strong>Payment status</strong> updated to "Paid"</li>
                    <li>✅ <strong>Application status</strong> changed to "Approved"</li>
                    <li>✅ <strong>Pass number</strong> generated automatically</li>
                    <li>✅ <strong>Validity dates</strong> set (30 days from today)</li>
                    <li>✅ <strong>Print buttons</strong> enabled in dashboard</li>
                    <li>✅ <strong>Bus pass ready</strong> for immediate use</li>
                </ul>
            </div>
            
            <?php if ($applications && $applications->num_rows > 0): ?>
                <h3><i class="fas fa-credit-card"></i> Applications Ready for Payment</h3>
                
                <?php while ($app = $applications->fetch_assoc()): ?>
                    <div class="app-card">
                        <div class="app-header">
                            <div class="app-id">
                                <i class="fas fa-ticket-alt"></i>
                                Application #<?php echo $app['id']; ?>
                            </div>
                            <div class="app-amount">
                                ₹<?php echo number_format($app['amount'], 2); ?>
                            </div>
                        </div>
                        
                        <div class="app-details">
                            <div class="detail-item">
                                <span class="detail-label">Applicant Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($app['applicant_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Pass Type:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($app['type_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Current Status:</span>
                                <span class="detail-value">
                                    <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                        <?php echo $app['status']; ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Payment Status:</span>
                                <span class="detail-value">
                                    <span class="payment-badge payment-<?php echo strtolower(str_replace('_', '', $app['payment_status'])); ?>">
                                        <?php echo $app['payment_status']; ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Pass Number:</span>
                                <span class="detail-value"><?php echo $app['pass_number'] ? htmlspecialchars($app['pass_number']) : '<em>Will be generated</em>'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Applied Date:</span>
                                <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($app['application_date'])); ?></span>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <button type="submit" name="complete_payment_now" class="complete-button"
                                    onclick="return confirm('Complete payment for Application #<?php echo $app['id']; ?>?\n\nThis will:\n- Mark payment as completed\n- Approve the application\n- Generate pass number\n- Enable printing\n\nAmount: ₹<?php echo number_format($app['amount'], 2); ?>\n\nContinue?')">
                                <i class="fas fa-bolt"></i> Complete Payment & Enable Printing - ₹<?php echo number_format($app['amount'], 2); ?>
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
                
            <?php else: ?>
                <div class="no-apps">
                    <i class="fas fa-check-circle"></i>
                    <h3>All Applications Completed!</h3>
                    <p>All your applications have been paid and are ready for printing.</p>
                    <a href="user-dashboard.php" class="quick-link">
                        <i class="fas fa-dashboard"></i> Go to Dashboard
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="quick-links">
                <a href="user-dashboard.php" class="quick-link">
                    <i class="fas fa-dashboard"></i> User Dashboard
                </a>
                <a href="fix-payment-and-print.php" class="quick-link">
                    <i class="fas fa-tools"></i> Fix Payment Issues
                </a>
                <a href="apply-pass.php" class="quick-link">
                    <i class="fas fa-plus"></i> Apply New Pass
                </a>
                <a href="fast-payment.php?id=1" class="quick-link">
                    <i class="fas fa-rocket"></i> Fast Payment
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Add loading states to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
                    button.disabled = true;
                }
            });
        });
        
        // Auto-redirect after success
        <?php if ($messageType === 'success'): ?>
        let countdown = 3;
        const updateCountdown = () => {
            const message = document.querySelector('.message.success');
            if (message) {
                message.innerHTML = message.innerHTML.split('Redirecting')[0] + ` Redirecting to dashboard in ${countdown} seconds...`;
            }
            countdown--;
            if (countdown > 0) {
                setTimeout(updateCountdown, 1000);
            }
        };
        setTimeout(updateCountdown, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
