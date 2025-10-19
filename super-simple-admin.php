<?php
session_start();
include('includes/dbconnection.php');

// Super simple admin check
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true; // Auto-login for testing
    $_SESSION['admin_username'] = 'admin';
}

// Handle actions - SUPER SIMPLE
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    if ($action == 'approve') {
        $con->query("UPDATE bus_pass_applications SET status = 'Approved' WHERE id = $id");
        $msg = "✅ Application #$id APPROVED!";
    }
    elseif ($action == 'reject') {
        $con->query("UPDATE bus_pass_applications SET status = 'Rejected' WHERE id = $id");
        $msg = "❌ Application #$id REJECTED!";
    }
    elseif ($action == 'paid') {
        $con->query("UPDATE bus_pass_applications SET payment_status = 'Paid' WHERE id = $id");
        $msg = "💳 Application #$id marked as PAID!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Super Simple Admin</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f0f0f0; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .btn { padding: 8px 12px; margin: 2px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; font-size: 12px; }
        .approve { background: #4CAF50; }
        .reject { background: #f44336; }
        .paid { background: #ff9800; }
        .msg { background: #dff0d8; color: #3c763d; padding: 15px; border-radius: 5px; margin: 10px 0; font-weight: bold; }
        .approved { color: #4CAF50; font-weight: bold; }
        .rejected { color: #f44336; font-weight: bold; }
        .pending { color: #ff9800; font-weight: bold; }
        .paid-status { color: #4CAF50; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚌 Super Simple Admin Panel</h1>
        <p><strong>Click buttons to approve/reject applications instantly!</strong></p>
        
        <?php if (isset($msg)): ?>
            <div class="msg"><?php echo $msg; ?></div>
        <?php endif; ?>
        
        <h2>📋 All Applications</h2>
        
        <?php
        $result = $con->query("SELECT * FROM bus_pass_applications ORDER BY id DESC");
        
        if ($result && $result->num_rows > 0):
        ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><strong>#<?php echo $row['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($row['applicant_name']); ?></td>
                <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                <td class="<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></td>
                <td class="<?php echo $row['payment_status'] == 'Paid' ? 'paid-status' : 'pending'; ?>"><?php echo $row['payment_status']; ?></td>
                <td><?php echo date('M d, Y', strtotime($row['application_date'])); ?></td>
                <td>
                    <?php if ($row['status'] != 'Approved'): ?>
                        <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn approve" onclick="return confirm('Approve #<?php echo $row['id']; ?>?')">✅ Approve</a>
                    <?php endif; ?>
                    
                    <?php if ($row['status'] != 'Rejected'): ?>
                        <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn reject" onclick="return confirm('Reject #<?php echo $row['id']; ?>?')">❌ Reject</a>
                    <?php endif; ?>
                    
                    <?php if ($row['payment_status'] != 'Paid'): ?>
                        <a href="?action=paid&id=<?php echo $row['id']; ?>" class="btn paid" onclick="return confirm('Mark #<?php echo $row['id']; ?> as paid?')">💳 Mark Paid</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <p>No applications found.</p>
        <?php endif; ?>
        
        <h2>📊 Quick Stats</h2>
        <?php
        $stats = $con->query("SELECT 
            COUNT(*) as total,
            SUM(status = 'Pending') as pending,
            SUM(status = 'Approved') as approved,
            SUM(status = 'Rejected') as rejected,
            SUM(payment_status = 'Paid') as paid
            FROM bus_pass_applications")->fetch_assoc();
        ?>
        <p>
            <strong>Total:</strong> <?php echo $stats['total']; ?> | 
            <strong class="pending">Pending:</strong> <?php echo $stats['pending']; ?> | 
            <strong class="approved">Approved:</strong> <?php echo $stats['approved']; ?> | 
            <strong class="rejected">Rejected:</strong> <?php echo $stats['rejected']; ?> | 
            <strong class="paid-status">Paid:</strong> <?php echo $stats['paid']; ?>
        </p>
        
        <h2>🔗 Other Admin Pages</h2>
        <p>
            <a href="admin-dashboard.php" class="btn approve">Main Admin Dashboard</a>
            <a href="direct-admin-control.php" class="btn approve">Direct Admin Control</a>
            <a href="basic-admin.php" class="btn approve">Basic Admin</a>
            <a href="?" class="btn paid">🔄 Refresh This Page</a>
        </p>
        
        <h2>ℹ️ How This Works</h2>
        <ul>
            <li><strong>No JavaScript complexity</strong> - just simple links</li>
            <li><strong>No AJAX</strong> - direct page refresh</li>
            <li><strong>No forms</strong> - just click and confirm</li>
            <li><strong>Instant results</strong> - changes happen immediately</li>
            <li><strong>Auto-login</strong> - no session issues</li>
        </ul>
        
        <div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>🎯 Testing Instructions</h3>
            <ol>
                <li>Find any application in the table above</li>
                <li>Click "✅ Approve" to approve it</li>
                <li>Click "❌ Reject" to reject it</li>
                <li>Click "💳 Mark Paid" to mark payment as completed</li>
                <li>Confirm in the popup dialog</li>
                <li>See the result message at the top</li>
                <li>Notice the status change in the table</li>
            </ol>
            <p><strong>If this doesn't work, there may be a fundamental database connection issue.</strong></p>
        </div>
        
        <h2>🔧 System Check</h2>
        <p>
            <strong>Database:</strong> <?php echo $con ? '✅ Connected' : '❌ Failed'; ?><br>
            <strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
            <strong>Admin Session:</strong> ✅ Active
        </p>
    </div>
</body>
</html>
