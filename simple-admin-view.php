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

// Handle status updates
if (isset($_POST['update_status'])) {
    $applicationId = $_POST['application_id'];
    $newStatus = $_POST['status'];
    $newPaymentStatus = $_POST['payment_status'] ?? null;
    $remarks = trim($_POST['remarks']);

    // Special handling for approval - enable printing immediately
    if ($newStatus === 'Approved') {
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

        if ($updateStmt->execute()) {
            $message = "✅ Application approved successfully! Pass number $passNumber generated and ready for printing.";
            $messageType = "success";
        } else {
            $message = "❌ Error approving application: " . $con->error;
            $messageType = "error";
        }
    } else {
        // Regular status update for non-approval actions
        $updateQuery = "UPDATE bus_pass_applications SET status = ?, admin_remarks = ?, processed_date = NOW()";
        $params = [$newStatus, $remarks];
        $types = "ss";

        if ($newPaymentStatus !== null && !empty($newPaymentStatus)) {
            $updateQuery .= ", payment_status = ?";
            $params[] = $newPaymentStatus;
            $types .= "s";
        }

        $updateQuery .= " WHERE id = ?";
        $params[] = $applicationId;
        $types .= "i";

        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param($types, ...$params);

        if ($updateStmt->execute()) {
            $message = "✅ Application updated successfully!";
            $messageType = "success";
        } else {
            $message = "❌ Error updating application: " . $con->error;
            $messageType = "error";
        }
    }
}

// Get application details if viewing
$viewingApp = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $appId = intval($_GET['view']);

    $viewQuery = "SELECT ba.*, u.full_name as user_name, u.email as user_email,
                         bpt.type_name, bpt.duration_days,
                         p.transaction_id, p.payment_method, p.payment_date
                  FROM bus_pass_applications ba
                  LEFT JOIN users u ON ba.user_id = u.id
                  LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                  LEFT JOIN payments p ON ba.id = p.application_id
                  WHERE ba.id = ?";
    $viewStmt = $con->prepare($viewQuery);
    $viewStmt->bind_param("i", $appId);
    $viewStmt->execute();
    $viewingApp = $viewStmt->get_result()->fetch_assoc();
}

// Get all applications
$query = "SELECT ba.*, u.full_name as user_name, u.email as user_email,
                 p.transaction_id, p.payment_method, p.payment_date,
                 bpt.type_name, bpt.duration_days
          FROM bus_pass_applications ba
          JOIN users u ON ba.user_id = u.id
          JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          LEFT JOIN payments p ON ba.id = p.application_id
          ORDER BY ba.application_date DESC";
$applications = $con->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Admin View - Bus Pass Management</title>
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

        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { opacity: 0.8; }

        .status-pending { color: #ffc107; font-weight: bold; }
        .status-approved { color: #28a745; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }

        .payment-paid { color: #28a745; font-weight: bold; }
        .payment-pending { color: #ffc107; font-weight: bold; }
        .payment-payment_required { color: #dc3545; font-weight: bold; }

        .details-section { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .detail-item { margin: 10px 0; }
        .detail-label { font-weight: bold; color: #666; }
        .detail-value { margin-left: 10px; }

        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { height: 80px; resize: vertical; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bus"></i> Simple Admin Dashboard</h1>
            <p>View and update bus pass applications</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($viewingApp): ?>
            <div class="details-section">
                <h2><i class="fas fa-eye"></i> Application Details - ID #<?php echo $viewingApp['id']; ?></h2>

                <div class="detail-grid">
                    <div>
                        <h3>Personal Information</h3>
                        <div class="detail-item">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($viewingApp['applicant_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date of Birth:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($viewingApp['date_of_birth']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Gender:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($viewingApp['gender']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Contact:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($viewingApp['contact_number']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Address:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($viewingApp['address']); ?></span>
                        </div>
                    </div>

                    <div>
                        <h3>Application Information</h3>
                        <div class="detail-item">
                            <span class="detail-label">Pass Type:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($viewingApp['type_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Route:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($viewingApp['source'] . ' → ' . $viewingApp['destination']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Amount:</span>
                            <span class="detail-value">₹<?php echo number_format($viewingApp['amount'], 2); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Applied Date:</span>
                            <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($viewingApp['application_date'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value status-<?php echo strtolower($viewingApp['status']); ?>"><?php echo $viewingApp['status']; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Status:</span>
                            <span class="detail-value payment-<?php echo strtolower($viewingApp['payment_status']); ?>"><?php echo $viewingApp['payment_status']; ?></span>
                        </div>
                        <?php if ($viewingApp['pass_number']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Pass Number:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($viewingApp['pass_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($viewingApp['transaction_id']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Transaction ID:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($viewingApp['transaction_id']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <h3><i class="fas fa-edit"></i> Update Application</h3>
                <form method="POST">
                    <input type="hidden" name="application_id" value="<?php echo $viewingApp['id']; ?>">
                    <input type="hidden" name="update_status" value="1">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="status">Application Status:</label>
                            <select name="status" id="status" required>
                                <option value="Pending" <?php echo $viewingApp['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo $viewingApp['status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo $viewingApp['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="payment_status">Payment Status:</label>
                            <select name="payment_status" id="payment_status">
                                <option value="">Keep Current</option>
                                <option value="Pending" <?php echo $viewingApp['payment_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Paid" <?php echo $viewingApp['payment_status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="Payment_Required" <?php echo $viewingApp['payment_status'] === 'Payment_Required' ? 'selected' : ''; ?>>Payment Required</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Admin Remarks:</label>
                        <textarea name="remarks" id="remarks" placeholder="Enter remarks for this status update..."><?php echo htmlspecialchars($viewingApp['admin_remarks']); ?></textarea>
                    </div>

                    <!-- Quick Action Buttons -->
                    <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin: 0 0 15px 0; color: #666;">Quick Actions:</h4>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" onclick="quickApprove()" class="btn btn-success">
                                <i class="fas fa-check"></i> Quick Approve
                            </button>
                            <button type="button" onclick="quickReject()" class="btn btn-danger">
                                <i class="fas fa-times"></i> Quick Reject
                            </button>
                            <button type="button" onclick="markPaid()" class="btn btn-warning">
                                <i class="fas fa-credit-card"></i> Mark as Paid
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Application
                    </button>
                    <a href="simple-admin-view.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>

                    <script>
                    function quickApprove() {
                        document.getElementById('status').value = 'Approved';
                        document.getElementById('remarks').value = 'Application approved by admin - Pass ready for printing';
                        document.getElementById('status').style.backgroundColor = '#d4edda';
                        document.getElementById('remarks').style.backgroundColor = '#d4edda';
                        alert('This will approve the application and automatically enable printing for the user!');
                    }

                    function quickReject() {
                        document.getElementById('status').value = 'Rejected';
                        document.getElementById('remarks').value = 'Application rejected by admin';
                        document.getElementById('status').style.backgroundColor = '#f8d7da';
                        document.getElementById('remarks').style.backgroundColor = '#f8d7da';
                    }

                    function markPaid() {
                        document.getElementById('payment_status').value = 'Paid';
                        document.getElementById('remarks').value = 'Payment status updated to Paid by admin';
                        document.getElementById('payment_status').style.backgroundColor = '#d4edda';
                        document.getElementById('remarks').style.backgroundColor = '#d4edda';
                    }
                    </script>
                </form>
            </div>
        <?php endif; ?>

        <div class="details-section">
            <h2><i class="fas fa-list"></i> All Applications</h2>

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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($app = $applications->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $app['id']; ?></td>
                            <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                            <td><?php echo htmlspecialchars($app['type_name']); ?></td>
                            <td>₹<?php echo number_format($app['amount'], 2); ?></td>
                            <td class="status-<?php echo strtolower($app['status']); ?>">
                                <?php echo $app['status']; ?>
                            </td>
                            <td class="payment-<?php echo strtolower($app['payment_status']); ?>">
                                <?php echo $app['payment_status']; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                            <td>
                                <a href="?view=<?php echo $app['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View & Update
                                </a>
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
            <a href="admin-dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Full Admin Dashboard
            </a>
        </div>
    </div>
</body>
</html>
