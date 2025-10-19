<?php
session_start();
include('includes/dbconnection.php');

// Auto-login for testing
if (!isset($_SESSION['uid'])) {
    $_SESSION['uid'] = 1;
    $_SESSION['username'] = 'testuser';
}

$message = '';
$messageType = '';

// Handle payment fix actions
if (isset($_POST['fix_action'])) {
    $action = $_POST['fix_action'];
    $applicationId = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
    
    try {
        $con->begin_transaction();
        
        switch ($action) {
            case 'complete_payment':
                // Generate transaction ID
                $transactionId = 'FIX_' . time() . '_' . rand(1000, 9999);
                
                // Insert payment record
                $paymentQuery = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) VALUES (?, ?, ?, 'demo', 'completed', ?, NOW())";
                $paymentStmt = $con->prepare($paymentQuery);
                $amount = 100.00; // Default amount
                $paymentStmt->bind_param("iids", $applicationId, $_SESSION['uid'], $amount, $transactionId);
                $paymentStmt->execute();
                
                // Generate pass number
                $passNumber = 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);
                
                // Update application
                $updateQuery = "UPDATE bus_pass_applications SET payment_status = 'Paid', pass_number = ?, processed_date = NOW() WHERE id = ?";
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("si", $passNumber, $applicationId);
                $updateStmt->execute();
                
                $con->commit();
                $message = "✅ Payment completed and pass number generated! Transaction ID: $transactionId, Pass Number: $passNumber";
                $messageType = "success";
                break;
                
            case 'approve_and_enable_print':
                // Get application details
                $appQuery = "SELECT * FROM bus_pass_applications WHERE id = ?";
                $appStmt = $con->prepare($appQuery);
                $appStmt->bind_param("i", $applicationId);
                $appStmt->execute();
                $app = $appStmt->get_result()->fetch_assoc();
                
                if (!$app) {
                    throw new Exception("Application not found");
                }
                
                // Generate pass number if missing
                $passNumber = $app['pass_number'];
                if (empty($passNumber)) {
                    $passNumber = 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);
                }
                
                // Set valid dates
                $validFrom = date('Y-m-d');
                $validUntil = date('Y-m-d', strtotime('+30 days'));
                
                // Update application to approved with all required fields
                $updateQuery = "UPDATE bus_pass_applications SET 
                               status = 'Approved', 
                               payment_status = 'Paid', 
                               pass_number = ?, 
                               valid_from = ?, 
                               valid_until = ?, 
                               processed_date = NOW(),
                               admin_remarks = 'Auto-approved for testing - payment completed'
                               WHERE id = ?";
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("sssi", $passNumber, $validFrom, $validUntil, $applicationId);
                $updateStmt->execute();
                
                $con->commit();
                $message = "✅ Application approved and print enabled! Pass Number: $passNumber, Valid: $validFrom to $validUntil";
                $messageType = "success";
                break;
                
            case 'fix_all_pending':
                // Fix all pending applications for current user
                $fixQuery = "UPDATE bus_pass_applications SET 
                            status = 'Approved',
                            payment_status = 'Paid',
                            pass_number = CONCAT('BP', YEAR(NOW()), LPAD(id, 6, '0')),
                            valid_from = CURDATE(),
                            valid_until = DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                            processed_date = NOW(),
                            admin_remarks = 'Auto-fixed for testing'
                            WHERE user_id = ? AND (status != 'Approved' OR payment_status != 'Paid' OR pass_number IS NULL)";
                $fixStmt = $con->prepare($fixQuery);
                $fixStmt->bind_param("i", $_SESSION['uid']);
                $fixStmt->execute();
                $affected = $fixStmt->affected_rows;
                
                $con->commit();
                $message = "✅ Fixed $affected application(s) - all now approved with print capability!";
                $messageType = "success";
                break;
        }
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get user's applications
$appsQuery = "SELECT ba.*, p.transaction_id, p.payment_method, p.payment_date 
              FROM bus_pass_applications ba
              LEFT JOIN payments p ON ba.id = p.application_id AND p.status = 'completed'
              WHERE ba.user_id = ?
              ORDER BY ba.id DESC";
$appsStmt = $con->prepare($appsQuery);
$appsStmt->bind_param("i", $_SESSION['uid']);
$appsStmt->execute();
$applications = $appsStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Payment & Print Issues - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.5rem; }
        .content { padding: 40px; }
        
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        
        .fix-section { background: #f8f9fa; padding: 30px; border-radius: 10px; margin: 30px 0; border-left: 4px solid #dc3545; }
        .fix-section h3 { margin: 0 0 20px 0; color: #dc3545; }
        
        .app-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .app-table th, .app-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .app-table th { background: #f8f9fa; font-weight: 600; }
        .app-table tr:hover { background: #f8f9fa; }
        
        .status-approved { color: #28a745; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }
        
        .payment-paid { color: #28a745; font-weight: bold; }
        .payment-pending { color: #ffc107; font-weight: bold; }
        .payment-required { color: #dc3545; font-weight: bold; }
        
        .fix-button { background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; margin: 2px; }
        .fix-button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3); }
        
        .global-fix { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer; font-size: 1.1rem; font-weight: 600; margin: 20px 0; width: 100%; }
        .global-fix:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3); }
        
        .print-status { padding: 8px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .print-enabled { background: #d4edda; color: #155724; }
        .print-disabled { background: #f8d7da; color: #721c24; }
        
        .quick-links { display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap; }
        .quick-link { background: #007bff; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .quick-link:hover { background: #0056b3; transform: translateY(-2px); text-decoration: none; color: white; }
        
        .issue-explanation { background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .issue-explanation h4 { margin: 0 0 10px 0; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tools"></i> Fix Payment & Print Issues</h1>
            <p>Resolve payment status and enable bus pass printing</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="issue-explanation">
                <h4><i class="fas fa-info-circle"></i> Common Issues & Solutions</h4>
                <ul>
                    <li><strong>Payment shows "Pending"</strong> → Payment record not created or status not updated</li>
                    <li><strong>Cannot print pass</strong> → Application not approved OR pass number missing</li>
                    <li><strong>Print button not visible</strong> → Requires: status='Approved' AND pass_number exists</li>
                    <li><strong>Payment completed but status wrong</strong> → Database sync issue between payments and applications</li>
                </ul>
            </div>
            
            <div class="fix-section">
                <h3><i class="fas fa-magic"></i> Global Fix - Solve All Issues</h3>
                <p>This will fix all your applications at once: complete payments, approve applications, generate pass numbers, and enable printing.</p>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="fix_action" value="fix_all_pending">
                    <button type="submit" class="global-fix" onclick="return confirm('Fix all your applications? This will:\n- Complete any pending payments\n- Approve all applications\n- Generate pass numbers\n- Enable printing\n\nContinue?')">
                        <i class="fas fa-magic"></i> Fix All My Applications
                    </button>
                </form>
            </div>
            
            <div class="fix-section">
                <h3><i class="fas fa-list"></i> Your Applications Status</h3>
                
                <?php if ($applications && $applications->num_rows > 0): ?>
                    <table class="app-table">
                        <tr>
                            <th>ID</th>
                            <th>Applicant Name</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Pass Number</th>
                            <th>Print Available</th>
                            <th>Individual Fixes</th>
                        </tr>
                        <?php while ($app = $applications->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $app['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                            <td class="status-<?php echo strtolower($app['status']); ?>"><?php echo $app['status']; ?></td>
                            <td class="payment-<?php echo strtolower(str_replace('_', '', $app['payment_status'])); ?>"><?php echo $app['payment_status']; ?></td>
                            <td><?php echo $app['pass_number'] ? htmlspecialchars($app['pass_number']) : '<em>Not generated</em>'; ?></td>
                            <td>
                                <?php if ($app['status'] === 'Approved' && $app['pass_number']): ?>
                                    <span class="print-status print-enabled">✅ Print Enabled</span>
                                <?php else: ?>
                                    <span class="print-status print-disabled">❌ Print Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($app['payment_status'] !== 'Paid'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="fix_action" value="complete_payment">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    <button type="submit" class="fix-button" title="Complete payment">
                                        <i class="fas fa-credit-card"></i> Fix Payment
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($app['status'] !== 'Approved' || !$app['pass_number']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="fix_action" value="approve_and_enable_print">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    <button type="submit" class="fix-button" title="Approve and enable printing">
                                        <i class="fas fa-check"></i> Enable Print
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($app['status'] === 'Approved' && $app['pass_number']): ?>
                                <a href="user-dashboard.php" class="fix-button" style="background: #28a745; text-decoration: none;" title="Go to dashboard to print">
                                    <i class="fas fa-print"></i> Print Now
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No applications found. <a href="apply-pass.php">Apply for a bus pass first</a>.</p>
                <?php endif; ?>
            </div>
            
            <div class="quick-links">
                <a href="user-dashboard.php" class="quick-link">
                    <i class="fas fa-dashboard"></i> User Dashboard
                </a>
                <a href="fast-payment.php?id=1" class="quick-link">
                    <i class="fas fa-bolt"></i> Fast Payment
                </a>
                <a href="payment-speed-test.php" class="quick-link">
                    <i class="fas fa-stopwatch"></i> Payment Speed Test
                </a>
                <a href="apply-pass.php" class="quick-link">
                    <i class="fas fa-plus"></i> Apply New Pass
                </a>
            </div>
            
            <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff;">
                <h4><i class="fas fa-lightbulb"></i> How Print Functionality Works</h4>
                <p><strong>Requirements for printing bus pass:</strong></p>
                <ol>
                    <li><strong>Application Status</strong> must be "Approved"</li>
                    <li><strong>Payment Status</strong> must be "Paid"</li>
                    <li><strong>Pass Number</strong> must be generated</li>
                    <li><strong>Valid dates</strong> must be set</li>
                </ol>
                <p><strong>If any of these are missing, the print buttons won't appear in the user dashboard.</strong></p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh page after successful fixes
        <?php if ($messageType === 'success'): ?>
        setTimeout(() => {
            window.location.reload();
        }, 3000);
        <?php endif; ?>
        
        // Add loading states to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    button.disabled = true;
                }
            });
        });
    </script>
</body>
</html>
