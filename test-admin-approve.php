<?php
session_start();
include('includes/dbconnection.php');

// Auto-login as admin for testing
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
}

$message = '';
$messageType = '';

// Handle test approval
if (isset($_POST['test_approve'])) {
    $applicationId = intval($_POST['application_id']);
    
    try {
        $con->begin_transaction();
        
        // Generate pass number and set validity dates for immediate printing
        $passNumber = 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);
        $validFrom = date('Y-m-d');
        $validUntil = date('Y-m-d', strtotime('+30 days'));
        $remarks = 'Application approved by admin - Pass ready for printing';
        
        // Update with all fields needed for printing (bypass payment requirement)
        $updateQuery = "UPDATE bus_pass_applications SET 
                       status = 'Approved', 
                       payment_status = 'Paid', 
                       pass_number = ?, 
                       valid_from = ?, 
                       valid_until = ?, 
                       admin_remarks = ?, 
                       processed_date = NOW() 
                       WHERE id = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("ssssi", $passNumber, $validFrom, $validUntil, $remarks, $applicationId);
        
        if ($updateStmt->execute()) {
            $con->commit();
            $message = "✅ Application #$applicationId approved successfully! Pass number $passNumber generated and ready for printing.";
            $messageType = "success";
        } else {
            throw new Exception("Database update failed: " . $con->error);
        }
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Create sample application if none exist
if (isset($_POST['create_sample'])) {
    try {
        $con->begin_transaction();
        
        // Create sample user if needed
        $checkUser = $con->query("SELECT id FROM users LIMIT 1");
        if ($checkUser->num_rows == 0) {
            $userQuery = "INSERT INTO users (full_name, email, phone, password, created_at) VALUES ('Test User', 'test@example.com', '1234567890', '" . password_hash('password', PASSWORD_DEFAULT) . "', NOW())";
            $con->query($userQuery);
            $userId = $con->insert_id;
        } else {
            $userId = $checkUser->fetch_assoc()['id'];
        }
        
        // Create sample pass type if needed
        $checkPassType = $con->query("SELECT id FROM bus_pass_types LIMIT 1");
        if ($checkPassType->num_rows == 0) {
            $passTypeQuery = "INSERT INTO bus_pass_types (type_name, duration_days, created_at) VALUES ('Monthly Pass', 30, NOW())";
            $con->query($passTypeQuery);
            $passTypeId = $con->insert_id;
        } else {
            $passTypeId = $checkPassType->fetch_assoc()['id'];
        }
        
        // Create sample application
        $appQuery = "INSERT INTO bus_pass_applications (
            user_id, pass_type_id, applicant_name, phone, address, 
            source, destination, amount, status, payment_status, 
            application_date
        ) VALUES (
            ?, ?, 'Test Applicant', '1234567890', 'Test Address', 
            'City A', 'City B', 100.00, 'Pending', 'Pending', 
            NOW()
        )";
        $appStmt = $con->prepare($appQuery);
        $appStmt->bind_param("ii", $userId, $passTypeId);
        $appStmt->execute();
        
        $con->commit();
        $message = "✅ Sample application created successfully!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error creating sample: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get applications for testing
$appsQuery = "SELECT ba.*, u.full_name as user_name, bpt.type_name 
              FROM bus_pass_applications ba
              LEFT JOIN users u ON ba.user_id = u.id
              LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
              ORDER BY ba.id DESC LIMIT 10";
$applications = $con->query($appsQuery);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Admin Approve - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.5rem; }
        .content { padding: 40px; }
        
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        
        .test-section { background: #f8f9fa; padding: 30px; border-radius: 10px; margin: 30px 0; border-left: 4px solid #28a745; }
        .test-section h3 { margin: 0 0 20px 0; color: #28a745; }
        
        .app-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .app-table th, .app-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .app-table th { background: #f8f9fa; font-weight: 600; }
        .app-table tr:hover { background: #f8f9fa; }
        
        .status-approved { color: #28a745; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }
        
        .payment-paid { color: #28a745; font-weight: bold; }
        .payment-pending { color: #ffc107; font-weight: bold; }
        
        .approve-button { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; margin: 2px; }
        .approve-button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); }
        
        .create-button { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer; font-size: 1.1rem; font-weight: 600; margin: 20px 0; }
        .create-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3); }
        
        .print-status { padding: 8px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .print-enabled { background: #d4edda; color: #155724; }
        .print-disabled { background: #f8d7da; color: #721c24; }
        
        .quick-links { display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap; }
        .quick-link { background: #007bff; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .quick-link:hover { background: #0056b3; transform: translateY(-2px); text-decoration: none; color: white; }
        
        .workflow-info { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .workflow-info h4 { margin: 0 0 10px 0; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-check-circle"></i> Test Admin Approve</h1>
            <p>Test admin approval functionality and verify printing is enabled</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="workflow-info">
                <h4><i class="fas fa-info-circle"></i> How Admin Approval Now Works</h4>
                <p><strong>When admin clicks "Approve":</strong></p>
                <ol>
                    <li>✅ <strong>Application Status</strong> → Changed to "Approved"</li>
                    <li>✅ <strong>Payment Status</strong> → Automatically set to "Paid" (bypasses payment)</li>
                    <li>✅ <strong>Pass Number</strong> → Generated automatically (e.g., BP2024000001)</li>
                    <li>✅ <strong>Validity Dates</strong> → Set (Today to +30 days)</li>
                    <li>✅ <strong>Print Buttons</strong> → Immediately available in user dashboard</li>
                    <li>✅ <strong>User Can Print</strong> → Pass ready for immediate use</li>
                </ol>
                <p><strong>No payment required - admin approval enables everything!</strong></p>
            </div>
            
            <div class="test-section">
                <h3><i class="fas fa-plus"></i> Create Sample Application</h3>
                <p>Create a sample application to test the approval process.</p>
                
                <form method="POST">
                    <button type="submit" name="create_sample" class="create-button">
                        <i class="fas fa-plus"></i> Create Sample Application for Testing
                    </button>
                </form>
            </div>
            
            <div class="test-section">
                <h3><i class="fas fa-list"></i> Applications Available for Testing</h3>
                
                <?php if ($applications && $applications->num_rows > 0): ?>
                    <table class="app-table">
                        <tr>
                            <th>ID</th>
                            <th>Applicant Name</th>
                            <th>Pass Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Pass Number</th>
                            <th>Print Available</th>
                            <th>Test Approve</th>
                        </tr>
                        <?php while ($app = $applications->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $app['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                            <td><?php echo htmlspecialchars($app['type_name'] ?? 'N/A'); ?></td>
                            <td>₹<?php echo number_format($app['amount'], 2); ?></td>
                            <td class="status-<?php echo strtolower($app['status']); ?>"><?php echo $app['status']; ?></td>
                            <td class="payment-<?php echo strtolower($app['payment_status']); ?>"><?php echo $app['payment_status']; ?></td>
                            <td><?php echo $app['pass_number'] ? htmlspecialchars($app['pass_number']) : '<em>Not generated</em>'; ?></td>
                            <td>
                                <?php if ($app['status'] === 'Approved' && $app['pass_number']): ?>
                                    <span class="print-status print-enabled">✅ Print Enabled</span>
                                <?php else: ?>
                                    <span class="print-status print-disabled">❌ Print Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($app['status'] !== 'Approved'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    <button type="submit" name="test_approve" class="approve-button" 
                                            onclick="return confirm('Approve Application #<?php echo $app['id']; ?>?\n\nThis will:\n- Set status to Approved\n- Set payment to Paid\n- Generate pass number\n- Enable printing\n\nContinue?')">
                                        <i class="fas fa-check"></i> Test Approve
                                    </button>
                                </form>
                                <?php else: ?>
                                <span style="color: #28a745; font-weight: bold;">✅ Already Approved</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No applications found. Create a sample application first.</p>
                <?php endif; ?>
            </div>
            
            <div class="quick-links">
                <a href="admin-dashboard.php" class="quick-link">
                    <i class="fas fa-dashboard"></i> Admin Dashboard
                </a>
                <a href="admin-actions.php" class="quick-link">
                    <i class="fas fa-bolt"></i> Admin Actions
                </a>
                <a href="simple-admin-view.php" class="quick-link">
                    <i class="fas fa-eye"></i> Simple Admin View
                </a>
                <a href="user-dashboard.php" class="quick-link">
                    <i class="fas fa-user"></i> User Dashboard
                </a>
            </div>
            
            <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
                <h4><i class="fas fa-lightbulb"></i> Testing Instructions</h4>
                <ol>
                    <li><strong>Create Sample Application</strong> - Click the button above to create test data</li>
                    <li><strong>Test Approve</strong> - Click "Test Approve" for any pending application</li>
                    <li><strong>Verify Results</strong> - Check that status changes and print becomes enabled</li>
                    <li><strong>Check User Dashboard</strong> - Go to user dashboard to verify print buttons appear</li>
                    <li><strong>Test Printing</strong> - Try to print the pass to confirm functionality</li>
                </ol>
                <p><strong>Expected Result: After approval, users can immediately print their passes without payment!</strong></p>
            </div>
        </div>
    </div>
    
    <script>
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
        
        // Auto-refresh after successful approval
        <?php if ($messageType === 'success' && strpos($message, 'approved') !== false): ?>
        setTimeout(() => {
            window.location.reload();
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
