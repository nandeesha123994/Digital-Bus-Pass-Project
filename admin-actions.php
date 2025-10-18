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

// Handle quick actions
if (isset($_POST['action']) && isset($_POST['application_id'])) {
    $action = $_POST['action'];
    $applicationId = intval($_POST['application_id']);
    $remarks = $_POST['remarks'] ?? '';

    // Get application details
    $appQuery = "SELECT * FROM bus_pass_applications WHERE id = ?";
    $appStmt = $con->prepare($appQuery);
    $appStmt->bind_param("i", $applicationId);
    $appStmt->execute();
    $app = $appStmt->get_result()->fetch_assoc();

    if (!$app) {
        $message = "❌ Application not found!";
        $messageType = "error";
    } else {
        $success = false;

        switch ($action) {
            case 'approve':
                // Generate pass number and set validity dates for immediate printing
                $passNumber = 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);
                $validFrom = date('Y-m-d');
                $validUntil = date('Y-m-d', strtotime('+30 days'));
                $finalRemarks = $remarks ?: 'Application approved by admin - Pass ready for printing';

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
                $updateStmt->bind_param("ssssi", $passNumber, $validFrom, $validUntil, $finalRemarks, $applicationId);
                $success = $updateStmt->execute();

                if ($success) {
                    $message = "✅ Application #$applicationId approved successfully! Pass number $passNumber generated and ready for printing.";
                    $messageType = "success";
                } else {
                    $message = "❌ Error approving application: " . $con->error;
                    $messageType = "error";
                }
                break;

            case 'reject':
                $updateQuery = "UPDATE bus_pass_applications SET status = 'Rejected', admin_remarks = ?, processed_date = NOW() WHERE id = ?";
                $finalRemarks = $remarks ?: 'Application rejected by admin';
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("si", $finalRemarks, $applicationId);
                $success = $updateStmt->execute();

                if ($success) {
                    $message = "✅ Application #$applicationId rejected successfully!";
                    $messageType = "success";
                } else {
                    $message = "❌ Error rejecting application: " . $con->error;
                    $messageType = "error";
                }
                break;

            case 'mark_paid':
                $updateQuery = "UPDATE bus_pass_applications SET payment_status = 'Paid', admin_remarks = ?, processed_date = NOW() WHERE id = ?";
                $finalRemarks = $remarks ?: 'Payment marked as paid by admin';
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("si", $finalRemarks, $applicationId);
                $success = $updateStmt->execute();

                if ($success) {
                    $message = "✅ Application #$applicationId marked as paid successfully!";
                    $messageType = "success";
                } else {
                    $message = "❌ Error updating payment status: " . $con->error;
                    $messageType = "error";
                }
                break;

            case 'mark_pending':
                $updateQuery = "UPDATE bus_pass_applications SET payment_status = 'Pending', admin_remarks = ?, processed_date = NOW() WHERE id = ?";
                $finalRemarks = $remarks ?: 'Payment status updated to pending by admin';
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("si", $finalRemarks, $applicationId);
                $success = $updateStmt->execute();

                if ($success) {
                    $message = "✅ Application #$applicationId marked as pending payment successfully!";
                    $messageType = "success";
                } else {
                    $message = "❌ Error updating payment status: " . $con->error;
                    $messageType = "error";
                }
                break;

            default:
                $message = "❌ Invalid action specified!";
                $messageType = "error";
        }
    }
}

// Get all applications for display
$query = "SELECT ba.*, u.full_name as user_name, u.email as user_email,
                 bpt.type_name, bpt.duration_days
          FROM bus_pass_applications ba
          JOIN users u ON ba.user_id = u.id
          JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          ORDER BY ba.application_date DESC";
$applications = $con->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Actions - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #007bff; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .message { padding: 15px; border-radius: 5px; margin: 20px 0; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }

        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; font-size: 12px; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.8; }

        .status-pending { color: #ffc107; font-weight: bold; }
        .status-approved { color: #28a745; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }

        .payment-paid { color: #28a745; font-weight: bold; }
        .payment-pending { color: #ffc107; font-weight: bold; }
        .payment-payment_required { color: #dc3545; font-weight: bold; }

        .action-form { display: inline-block; margin: 2px; }
        .quick-actions { display: flex; gap: 5px; flex-wrap: wrap; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cogs"></i> Admin Actions Dashboard</h1>
            <p>Quick approve, reject, and manage bus pass applications</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

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

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #28a745;"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #dc3545;"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #28a745;"><?php echo $stats['paid']; ?></div>
                <div class="stat-label">Payments Completed</div>
            </div>
        </div>

        <!-- Applications Table -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2><i class="fas fa-list"></i> All Applications</h2>

            <?php if ($applications && $applications->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Applicant</th>
                            <th>Pass Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Applied Date</th>
                            <th>Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($app = $applications->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $app['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($app['applicant_name']); ?>
                                <br><small><?php echo htmlspecialchars($app['user_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($app['type_name']); ?></td>
                            <td>₹<?php echo number_format($app['amount'], 2); ?></td>
                            <td class="status-<?php echo strtolower($app['status']); ?>">
                                <?php echo $app['status']; ?>
                                <?php if ($app['admin_remarks']): ?>
                                    <br><small><?php echo htmlspecialchars(substr($app['admin_remarks'], 0, 50)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td class="payment-<?php echo strtolower($app['payment_status']); ?>">
                                <?php echo $app['payment_status']; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                            <td>
                                <div class="quick-actions">
                                    <?php if ($app['status'] !== 'Approved'): ?>
                                    <form method="POST" class="action-form" onsubmit="return confirm('Approve this application?')">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="remarks" value="Application approved by admin">
                                        <button type="submit" class="btn btn-success" title="Approve Application">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($app['status'] !== 'Rejected'): ?>
                                    <form method="POST" class="action-form" onsubmit="return confirm('Reject this application?')">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="remarks" value="Application rejected by admin">
                                        <button type="submit" class="btn btn-danger" title="Reject Application">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($app['payment_status'] !== 'Paid'): ?>
                                    <form method="POST" class="action-form" onsubmit="return confirm('Mark payment as completed?')">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="remarks" value="Payment marked as paid by admin">
                                        <button type="submit" class="btn btn-warning" title="Mark as Paid">
                                            <i class="fas fa-credit-card"></i> Mark Paid
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <a href="simple-admin-view.php?view=<?php echo $app['id']; ?>" class="btn btn-info" title="View Details">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #666;">No applications found.</p>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="admin-dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Full Admin Dashboard
            </a>
            <a href="simple-admin-view.php" class="btn btn-info">
                <i class="fas fa-list"></i> Simple Admin View
            </a>
        </div>
    </div>

    <script>
        // Auto-refresh page every 30 seconds to show latest updates
        setTimeout(function() {
            if (confirm('Refresh page to see latest updates?')) {
                window.location.reload();
            }
        }, 30000);

        // Show success message and auto-hide after 5 seconds
        <?php if ($messageType === 'success'): ?>
        setTimeout(function() {
            const messageDiv = document.querySelector('.message.success');
            if (messageDiv) {
                messageDiv.style.opacity = '0';
                setTimeout(function() {
                    messageDiv.style.display = 'none';
                }, 500);
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
