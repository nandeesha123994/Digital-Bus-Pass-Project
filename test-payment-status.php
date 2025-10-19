<?php
session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Payment Status Test</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .warning{color:#ffc107;} .info{color:#007bff;}";
echo "</style></head><body>";

echo "<h2>🔍 Payment Status Debugging</h2>";

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    echo "<p class='error'>❌ Please log in first to test payment status</p>";
    echo "<p><a href='login.php'>Login</a></p>";
    echo "</body></html>";
    exit();
}

$userId = $_SESSION['uid'];
echo "<p class='info'>ℹ️ Testing for User ID: $userId</p>";

// Check bus_pass_applications table
echo "<h3>📋 Bus Pass Applications</h3>";
$appsQuery = "SELECT id, application_id, applicant_name, status, payment_status, amount, application_date, pass_number FROM bus_pass_applications WHERE user_id = ? ORDER BY application_date DESC";
$appsStmt = $con->prepare($appsQuery);
$appsStmt->bind_param("i", $userId);
$appsStmt->execute();
$appsResult = $appsStmt->get_result();

if ($appsResult->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>App ID</th><th>Name</th><th>Status</th><th>Payment Status</th><th>Amount</th><th>Pass Number</th><th>Date</th></tr>";
    while ($app = $appsResult->fetch_assoc()) {
        $statusClass = '';
        switch ($app['payment_status']) {
            case 'Paid': $statusClass = 'success'; break;
            case 'Pending': $statusClass = 'warning'; break;
            case 'Failed': $statusClass = 'error'; break;
            default: $statusClass = 'info';
        }
        
        echo "<tr>";
        echo "<td>" . $app['id'] . "</td>";
        echo "<td>" . htmlspecialchars($app['application_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($app['applicant_name']) . "</td>";
        echo "<td>" . htmlspecialchars($app['status']) . "</td>";
        echo "<td class='$statusClass'><strong>" . htmlspecialchars($app['payment_status']) . "</strong></td>";
        echo "<td>₹" . number_format($app['amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($app['pass_number'] ?? 'Not assigned') . "</td>";
        echo "<td>" . date('M d, Y', strtotime($app['application_date'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No applications found for this user</p>";
}

// Check payments table
echo "<h3>💳 Payment Records</h3>";
$paymentsQuery = "SELECT p.*, ba.application_id, ba.applicant_name FROM payments p LEFT JOIN bus_pass_applications ba ON p.application_id = ba.id WHERE p.user_id = ? ORDER BY p.payment_date DESC";
$paymentsStmt = $con->prepare($paymentsQuery);
$paymentsStmt->bind_param("i", $userId);
$paymentsStmt->execute();
$paymentsResult = $paymentsStmt->get_result();

if ($paymentsResult->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Payment ID</th><th>App ID</th><th>Name</th><th>Amount</th><th>Method</th><th>Status</th><th>Transaction ID</th><th>Date</th></tr>";
    while ($payment = $paymentsResult->fetch_assoc()) {
        $statusClass = '';
        switch ($payment['status']) {
            case 'completed': $statusClass = 'success'; break;
            case 'pending': $statusClass = 'warning'; break;
            case 'failed': $statusClass = 'error'; break;
            default: $statusClass = 'info';
        }
        
        echo "<tr>";
        echo "<td>" . $payment['id'] . "</td>";
        echo "<td>" . htmlspecialchars($payment['application_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($payment['applicant_name'] ?? 'N/A') . "</td>";
        echo "<td>₹" . number_format($payment['amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($payment['payment_method']) . "</td>";
        echo "<td class='$statusClass'><strong>" . htmlspecialchars($payment['status']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($payment['transaction_id']) . "</td>";
        echo "<td>" . date('M d, Y H:i', strtotime($payment['payment_date'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No payment records found for this user</p>";
}

// Check for mismatched statuses
echo "<h3>🔍 Status Analysis</h3>";
$mismatchQuery = "SELECT ba.id, ba.application_id, ba.applicant_name, ba.payment_status as app_payment_status, p.status as payment_record_status, p.transaction_id 
                  FROM bus_pass_applications ba 
                  LEFT JOIN payments p ON ba.id = p.application_id 
                  WHERE ba.user_id = ?";
$mismatchStmt = $con->prepare($mismatchQuery);
$mismatchStmt->bind_param("i", $userId);
$mismatchStmt->execute();
$mismatchResult = $mismatchStmt->get_result();

$issues = [];
while ($row = $mismatchResult->fetch_assoc()) {
    if ($row['app_payment_status'] === 'Paid' && empty($row['payment_record_status'])) {
        $issues[] = "Application {$row['application_id']}: Marked as Paid but no payment record found";
    } elseif ($row['app_payment_status'] === 'Pending' && $row['payment_record_status'] === 'completed') {
        $issues[] = "Application {$row['application_id']}: Payment completed but application still shows Pending";
    } elseif ($row['app_payment_status'] === 'Paid' && $row['payment_record_status'] !== 'completed') {
        $issues[] = "Application {$row['application_id']}: Application shows Paid but payment status is {$row['payment_record_status']}";
    }
}

if (empty($issues)) {
    echo "<p class='success'>✅ No payment status mismatches found</p>";
} else {
    echo "<p class='error'>❌ Found " . count($issues) . " payment status issues:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li class='error'>$issue</li>";
    }
    echo "</ul>";
}

// Quick fix option
if (!empty($issues)) {
    echo "<h3>🔧 Quick Fix</h3>";
    echo "<p>Would you like to fix payment status mismatches?</p>";
    echo "<form method='post'>";
    echo "<button type='submit' name='fix_payments' style='background:#28a745;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;'>Fix Payment Statuses</button>";
    echo "</form>";
    
    if (isset($_POST['fix_payments'])) {
        echo "<h4>🔄 Fixing Payment Statuses...</h4>";
        
        // Fix applications that have completed payments but wrong status
        $fixQuery = "UPDATE bus_pass_applications ba 
                     JOIN payments p ON ba.id = p.application_id 
                     SET ba.payment_status = 'Paid' 
                     WHERE ba.user_id = ? AND p.status = 'completed' AND ba.payment_status != 'Paid'";
        $fixStmt = $con->prepare($fixQuery);
        $fixStmt->bind_param("i", $userId);
        
        if ($fixStmt->execute()) {
            $affected = $fixStmt->affected_rows;
            echo "<p class='success'>✅ Fixed $affected application(s)</p>";
        } else {
            echo "<p class='error'>❌ Error fixing statuses: " . $con->error . "</p>";
        }
        
        echo "<p><a href='test-payment-status.php'>🔄 Refresh to see changes</a></p>";
    }
}

echo "<h3>🔗 Navigation</h3>";
echo "<p>";
echo "<a href='user-dashboard.php' style='margin-right:15px;'>← Back to Dashboard</a>";
echo "<a href='payment.php?application_id=1' style='margin-right:15px;'>Test Payment</a>";
echo "<a href='admin-dashboard.php'>Admin Dashboard</a>";
echo "</p>";

echo "</body></html>";
?>
