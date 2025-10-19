<?php
session_start();
include('includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle direct URL actions (GET method for simplicity)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $applicationId = intval($_GET['id']);
    
    // Verify application exists
    $checkQuery = "SELECT * FROM bus_pass_applications WHERE id = ?";
    $checkStmt = $con->prepare($checkQuery);
    $checkStmt->bind_param("i", $applicationId);
    $checkStmt->execute();
    $app = $checkStmt->get_result()->fetch_assoc();
    
    if (!$app) {
        $message = "❌ Application #$applicationId not found!";
        $messageType = "error";
    } else {
        $success = false;
        $errorMsg = '';
        
        try {
            switch ($action) {
                case 'approve':
                    $sql = "UPDATE bus_pass_applications SET status = 'Approved', admin_remarks = 'Approved by admin', processed_date = NOW() WHERE id = $applicationId";
                    $success = $con->query($sql);
                    if ($success) {
                        $message = "✅ Application #$applicationId ({$app['applicant_name']}) has been APPROVED successfully!";
                        $messageType = "success";
                    } else {
                        $errorMsg = $con->error;
                    }
                    break;
                    
                case 'reject':
                    $sql = "UPDATE bus_pass_applications SET status = 'Rejected', admin_remarks = 'Rejected by admin', processed_date = NOW() WHERE id = $applicationId";
                    $success = $con->query($sql);
                    if ($success) {
                        $message = "✅ Application #$applicationId ({$app['applicant_name']}) has been REJECTED successfully!";
                        $messageType = "success";
                    } else {
                        $errorMsg = $con->error;
                    }
                    break;
                    
                case 'paid':
                    $sql = "UPDATE bus_pass_applications SET payment_status = 'Paid', admin_remarks = 'Payment marked as paid by admin', processed_date = NOW() WHERE id = $applicationId";
                    $success = $con->query($sql);
                    if ($success) {
                        $message = "✅ Application #$applicationId ({$app['applicant_name']}) has been marked as PAID successfully!";
                        $messageType = "success";
                    } else {
                        $errorMsg = $con->error;
                    }
                    break;
                    
                case 'pending':
                    $sql = "UPDATE bus_pass_applications SET status = 'Pending', admin_remarks = 'Reset to pending by admin', processed_date = NOW() WHERE id = $applicationId";
                    $success = $con->query($sql);
                    if ($success) {
                        $message = "✅ Application #$applicationId ({$app['applicant_name']}) has been reset to PENDING successfully!";
                        $messageType = "success";
                    } else {
                        $errorMsg = $con->error;
                    }
                    break;
                    
                default:
                    $message = "❌ Invalid action: $action";
                    $messageType = "error";
            }
            
            if (!$success && !empty($errorMsg)) {
                $message = "❌ Database error: $errorMsg";
                $messageType = "error";
            }
            
        } catch (Exception $e) {
            $message = "❌ Exception: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Get all applications
$query = "SELECT ba.*, u.full_name as user_name, u.email as user_email, bpt.type_name 
          FROM bus_pass_applications ba
          LEFT JOIN users u ON ba.user_id = u.id
          LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          ORDER BY ba.application_date DESC";
$applications = $con->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Direct Admin Control - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.5rem; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-size: 1.1rem; font-weight: bold; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2.5rem; font-weight: bold; margin-bottom: 10px; }
        .stat-label { color: #666; font-size: 1.1rem; }
        .stat-pending { color: #ffc107; }
        .stat-approved { color: #28a745; }
        .stat-rejected { color: #dc3545; }
        .stat-paid { color: #17a2b8; }
        
        .table-container { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        
        .action-links { display: flex; gap: 8px; flex-wrap: wrap; }
        .action-link { padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.3s; }
        .action-link:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        
        .approve-link { background: #28a745; color: white; }
        .reject-link { background: #dc3545; color: white; }
        .paid-link { background: #ffc107; color: black; }
        .pending-link { background: #6c757d; color: white; }
        .view-link { background: #17a2b8; color: white; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .payment-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .payment-paid { background: #d1ecf1; color: #0c5460; }
        .payment-pending { background: #fff3cd; color: #856404; }
        .payment-payment_required { background: #f8d7da; color: #721c24; }
        
        .instructions { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .instructions h3 { margin-top: 0; color: #007bff; }
        
        .refresh-btn { background: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; margin: 20px 0; }
        .refresh-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Direct Admin Control</h1>
            <p>Simple, reliable approve/reject system - No JavaScript, No AJAX, Just Works!</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> How to Use This System</h3>
            <p><strong>This is a direct control system that bypasses all complex JavaScript and AJAX.</strong></p>
            <ul>
                <li><strong>Click "Approve"</strong> to instantly approve an application</li>
                <li><strong>Click "Reject"</strong> to instantly reject an application</li>
                <li><strong>Click "Mark Paid"</strong> to update payment status</li>
                <li><strong>Click "Reset Pending"</strong> to reset status back to pending</li>
                <li><strong>All actions work immediately</strong> - no forms, no modals, no JavaScript</li>
            </ul>
        </div>

        <!-- Statistics -->
        <?php
        $statsQuery = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid
            FROM bus_pass_applications";
        $statsResult = $con->query($statsQuery);
        $stats = $statsResult->fetch_assoc();
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-pending"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-approved"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-rejected"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-paid"><?php echo $stats['paid']; ?></div>
                <div class="stat-label">Paid</div>
            </div>
        </div>

        <button onclick="window.location.reload()" class="refresh-btn">
            <i class="fas fa-sync-alt"></i> Refresh Page
        </button>

        <!-- Applications Table -->
        <div class="table-container">
            <?php if ($applications && $applications->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Applicant Name</th>
                            <th>Pass Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Applied Date</th>
                            <th>Direct Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($app = $applications->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $app['id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($app['applicant_name']); ?></strong>
                                <?php if ($app['user_email']): ?>
                                    <br><small><?php echo htmlspecialchars($app['user_email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($app['type_name'] ?? 'N/A'); ?></td>
                            <td><strong>₹<?php echo number_format($app['amount'], 2); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                    <?php echo $app['status']; ?>
                                </span>
                                <?php if ($app['admin_remarks']): ?>
                                    <br><small><?php echo htmlspecialchars(substr($app['admin_remarks'], 0, 30)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="payment-badge payment-<?php echo strtolower($app['payment_status']); ?>">
                                    <?php echo $app['payment_status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                            <td>
                                <div class="action-links">
                                    <?php if ($app['status'] !== 'Approved'): ?>
                                    <a href="?action=approve&id=<?php echo $app['id']; ?>" 
                                       class="action-link approve-link"
                                       onclick="return confirm('✅ APPROVE Application #<?php echo $app['id']; ?> for <?php echo htmlspecialchars($app['applicant_name']); ?>?')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['status'] !== 'Rejected'): ?>
                                    <a href="?action=reject&id=<?php echo $app['id']; ?>" 
                                       class="action-link reject-link"
                                       onclick="return confirm('❌ REJECT Application #<?php echo $app['id']; ?> for <?php echo htmlspecialchars($app['applicant_name']); ?>?')">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['payment_status'] !== 'Paid'): ?>
                                    <a href="?action=paid&id=<?php echo $app['id']; ?>" 
                                       class="action-link paid-link"
                                       onclick="return confirm('💳 Mark Application #<?php echo $app['id']; ?> as PAID?')">
                                        <i class="fas fa-credit-card"></i> Mark Paid
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="?action=pending&id=<?php echo $app['id']; ?>" 
                                       class="action-link pending-link"
                                       onclick="return confirm('🔄 Reset Application #<?php echo $app['id']; ?> to PENDING?')">
                                        <i class="fas fa-undo"></i> Reset
                                    </a>
                                    
                                    <a href="simple-admin-view.php?view=<?php echo $app['id']; ?>" 
                                       class="action-link view-link">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 20px;"></i>
                    <h3>No applications found</h3>
                    <p>There are currently no bus pass applications in the system.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <a href="admin-dashboard.php" class="action-link view-link" style="padding: 15px 30px; font-size: 1.1rem;">
                <i class="fas fa-arrow-left"></i> Back to Main Admin Dashboard
            </a>
        </div>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const successMsg = document.querySelector('.message.success');
            if (successMsg) {
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.style.display = 'none', 500);
            }
        }, 5000);
        
        // Add loading state to action links
        document.querySelectorAll('.action-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.href.includes('action=')) {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    this.style.pointerEvents = 'none';
                }
            });
        });
    </script>
</body>
</html>
