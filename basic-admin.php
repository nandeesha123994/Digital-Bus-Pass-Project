<?php
session_start();
include('includes/dbconnection.php');

// Simple admin check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "<h1>Please login as admin first</h1>";
    echo "<a href='admin-login.php'>Login Here</a>";
    exit();
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Basic Admin Control</title>";
echo "<style>";
echo "body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:8px;}";
echo "table{width:100%;border-collapse:collapse;margin:20px 0;}";
echo "th,td{border:1px solid #ddd;padding:12px;text-align:left;}";
echo "th{background:#007bff;color:white;}";
echo "tr:nth-child(even){background:#f9f9f9;}";
echo ".btn{padding:8px 15px;margin:2px;border:none;border-radius:4px;cursor:pointer;text-decoration:none;display:inline-block;color:white;font-size:12px;}";
echo ".approve{background:#28a745;} .reject{background:#dc3545;} .paid{background:#ffc107;color:black;} .view{background:#17a2b8;}";
echo ".btn:hover{opacity:0.8;}";
echo ".message{padding:15px;margin:10px 0;border-radius:5px;font-weight:bold;}";
echo ".success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}";
echo ".error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}";
echo ".status-approved{color:#28a745;font-weight:bold;}";
echo ".status-rejected{color:#dc3545;font-weight:bold;}";
echo ".status-pending{color:#ffc107;font-weight:bold;}";
echo ".payment-paid{color:#28a745;font-weight:bold;}";
echo ".payment-pending{color:#ffc107;font-weight:bold;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Basic Admin Control Panel</h1>";
echo "<p><strong>Simple approve/reject system - Direct database updates</strong></p>";

// Handle actions
$message = '';
if (isset($_GET['do']) && isset($_GET['id'])) {
    $action = $_GET['do'];
    $id = intval($_GET['id']);
    
    echo "<h3>Processing Action: $action for Application ID: $id</h3>";
    
    $success = false;
    $sql = '';
    
    switch ($action) {
        case 'approve':
            $sql = "UPDATE bus_pass_applications SET status = 'Approved', admin_remarks = 'Approved by admin', processed_date = NOW() WHERE id = $id";
            break;
        case 'reject':
            $sql = "UPDATE bus_pass_applications SET status = 'Rejected', admin_remarks = 'Rejected by admin', processed_date = NOW() WHERE id = $id";
            break;
        case 'paid':
            $sql = "UPDATE bus_pass_applications SET payment_status = 'Paid', admin_remarks = 'Marked as paid by admin', processed_date = NOW() WHERE id = $id";
            break;
        case 'pending':
            $sql = "UPDATE bus_pass_applications SET status = 'Pending', admin_remarks = 'Reset to pending by admin', processed_date = NOW() WHERE id = $id";
            break;
    }
    
    if ($sql) {
        echo "<p><strong>SQL Query:</strong> $sql</p>";
        
        if ($con->query($sql)) {
            echo "<div class='message success'>✅ SUCCESS! Action '$action' completed for Application #$id</div>";
            
            // Verify the change
            $verifyQuery = "SELECT status, payment_status, admin_remarks FROM bus_pass_applications WHERE id = $id";
            $verifyResult = $con->query($verifyQuery);
            if ($verifyResult && $verifyResult->num_rows > 0) {
                $verified = $verifyResult->fetch_assoc();
                echo "<p><strong>Verified Result:</strong></p>";
                echo "<ul>";
                echo "<li>Status: <strong>{$verified['status']}</strong></li>";
                echo "<li>Payment Status: <strong>{$verified['payment_status']}</strong></li>";
                echo "<li>Admin Remarks: <strong>{$verified['admin_remarks']}</strong></li>";
                echo "</ul>";
            }
        } else {
            echo "<div class='message error'>❌ ERROR: " . $con->error . "</div>";
        }
    } else {
        echo "<div class='message error'>❌ Invalid action: $action</div>";
    }
    
    echo "<hr>";
}

// Show all applications
echo "<h2>📋 All Applications</h2>";

$query = "SELECT ba.id, ba.applicant_name, ba.status, ba.payment_status, ba.amount, ba.application_date, ba.admin_remarks,
                 u.email as user_email, bpt.type_name
          FROM bus_pass_applications ba
          LEFT JOIN users u ON ba.user_id = u.id
          LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          ORDER BY ba.id DESC";

$result = $con->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "<th>Email</th>";
    echo "<th>Pass Type</th>";
    echo "<th>Amount</th>";
    echo "<th>Status</th>";
    echo "<th>Payment</th>";
    echo "<th>Date</th>";
    echo "<th>Actions</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>#{$row['id']}</strong></td>";
        echo "<td>" . htmlspecialchars($row['applicant_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user_email'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['type_name'] ?? 'N/A') . "</td>";
        echo "<td>₹" . number_format($row['amount'], 2) . "</td>";
        echo "<td class='status-" . strtolower($row['status']) . "'>" . $row['status'] . "</td>";
        echo "<td class='payment-" . strtolower($row['payment_status']) . "'>" . $row['payment_status'] . "</td>";
        echo "<td>" . date('M d, Y', strtotime($row['application_date'])) . "</td>";
        echo "<td>";
        
        // Action buttons
        if ($row['status'] !== 'Approved') {
            echo "<a href='?do=approve&id={$row['id']}' class='btn approve' onclick=\"return confirm('Approve application #{$row['id']}?')\">✅ Approve</a>";
        }
        
        if ($row['status'] !== 'Rejected') {
            echo "<a href='?do=reject&id={$row['id']}' class='btn reject' onclick=\"return confirm('Reject application #{$row['id']}?')\">❌ Reject</a>";
        }
        
        if ($row['payment_status'] !== 'Paid') {
            echo "<a href='?do=paid&id={$row['id']}' class='btn paid' onclick=\"return confirm('Mark application #{$row['id']} as paid?')\">💳 Mark Paid</a>";
        }
        
        echo "<a href='?do=pending&id={$row['id']}' class='btn view' onclick=\"return confirm('Reset application #{$row['id']} to pending?')\">🔄 Reset</a>";
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No applications found.</p>";
}

// Statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid
    FROM bus_pass_applications";
$statsResult = $con->query($statsQuery);
$stats = $statsResult->fetch_assoc();

echo "<h2>📊 Statistics</h2>";
echo "<table style='width:auto;'>";
echo "<tr><th>Total Applications</th><td>{$stats['total']}</td></tr>";
echo "<tr><th>Pending</th><td class='status-pending'>{$stats['pending']}</td></tr>";
echo "<tr><th>Approved</th><td class='status-approved'>{$stats['approved']}</td></tr>";
echo "<tr><th>Rejected</th><td class='status-rejected'>{$stats['rejected']}</td></tr>";
echo "<tr><th>Paid</th><td class='payment-paid'>{$stats['paid']}</td></tr>";
echo "</table>";

// Quick links
echo "<h2>🔗 Quick Links</h2>";
echo "<p>";
echo "<a href='admin-dashboard.php' class='btn view'>Main Admin Dashboard</a>";
echo "<a href='simple-admin-view.php' class='btn view'>Simple Admin View</a>";
echo "<a href='direct-admin-control.php' class='btn view'>Direct Admin Control</a>";
echo "<a href='?refresh=1' class='btn approve'>🔄 Refresh Page</a>";
echo "</p>";

// Database connection test
echo "<h2>🔧 System Status</h2>";
echo "<table style='width:auto;'>";
echo "<tr><th>Database Connection</th><td class='status-approved'>✅ Connected</td></tr>";
echo "<tr><th>Admin Session</th><td class='status-approved'>✅ Active</td></tr>";
echo "<tr><th>Admin User</th><td>" . ($_SESSION['admin_username'] ?? 'admin') . "</td></tr>";
echo "<tr><th>Current Time</th><td>" . date('Y-m-d H:i:s') . "</td></tr>";
echo "</table>";

echo "</div>";
echo "</body></html>";
?>
