<?php
session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Payment Debug</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .warning{color:#ffc107;} .info{color:#007bff;}";
echo "button{background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin:5px;}";
echo "</style></head><body>";

echo "<h2>🔍 Payment System Debug</h2>";

// Test payment processing
if (isset($_POST['test_payment'])) {
    $applicationId = intval($_POST['application_id']);
    
    echo "<h3>🧪 Testing Payment Processing for Application ID: $applicationId</h3>";
    
    // Check if application exists
    $checkQuery = "SELECT * FROM bus_pass_applications WHERE id = ?";
    $checkStmt = $con->prepare($checkQuery);
    $checkStmt->bind_param("i", $applicationId);
    $checkStmt->execute();
    $application = $checkStmt->get_result()->fetch_assoc();
    
    if (!$application) {
        echo "<p class='error'>❌ Application not found!</p>";
    } else {
        echo "<p class='info'>✅ Application found: {$application['applicant_name']}</p>";
        echo "<p>Current payment status: <strong>{$application['payment_status']}</strong></p>";
        
        // Simulate payment processing
        $con->begin_transaction();
        
        try {
            // Insert payment record
            $transactionId = 'TEST_' . time() . '_' . rand(1000, 9999);
            $amount = $application['amount'];
            $userId = $application['user_id'];
            
            $paymentQuery = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) VALUES (?, ?, ?, 'demo', 'completed', ?, NOW())";
            $paymentStmt = $con->prepare($paymentQuery);
            $paymentStmt->bind_param("iids", $applicationId, $userId, $amount, $transactionId);
            
            if (!$paymentStmt->execute()) {
                throw new Exception("Failed to insert payment record: " . $con->error);
            }
            
            echo "<p class='success'>✅ Payment record inserted with transaction ID: $transactionId</p>";
            
            // Generate pass number
            $passNumber = 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);
            
            // Update application
            $updateQuery = "UPDATE bus_pass_applications SET payment_status = 'Paid', pass_number = ?, processed_date = NOW() WHERE id = ?";
            $updateStmt = $con->prepare($updateQuery);
            $updateStmt->bind_param("si", $passNumber, $applicationId);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update application: " . $con->error);
            }
            
            echo "<p class='success'>✅ Application updated with pass number: $passNumber</p>";
            
            // Commit transaction
            $con->commit();
            echo "<p class='success'>✅ Transaction committed successfully!</p>";
            
            // Verify the update
            $verifyQuery = "SELECT payment_status, pass_number FROM bus_pass_applications WHERE id = ?";
            $verifyStmt = $con->prepare($verifyQuery);
            $verifyStmt->bind_param("i", $applicationId);
            $verifyStmt->execute();
            $updated = $verifyStmt->get_result()->fetch_assoc();
            
            echo "<p class='info'>📋 Verification - Payment Status: <strong>{$updated['payment_status']}</strong>, Pass Number: <strong>{$updated['pass_number']}</strong></p>";
            
        } catch (Exception $e) {
            $con->rollback();
            echo "<p class='error'>❌ Transaction failed: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
}

// Show database structure
echo "<h3>🗄️ Database Table Structure</h3>";

echo "<h4>bus_pass_applications table:</h4>";
$structureQuery = "DESCRIBE bus_pass_applications";
$result = $con->query($structureQuery);
echo "<table>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h4>payments table:</h4>";
$paymentsStructure = "DESCRIBE payments";
$paymentsResult = $con->query($paymentsStructure);
if ($paymentsResult) {
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $paymentsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ Payments table not found or error: " . $con->error . "</p>";
}

// Show current applications
echo "<h3>📋 Current Applications</h3>";
$appsQuery = "SELECT id, application_id, applicant_name, payment_status, pass_number, amount, user_id FROM bus_pass_applications ORDER BY id DESC LIMIT 10";
$appsResult = $con->query($appsQuery);

if ($appsResult && $appsResult->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>App ID</th><th>Name</th><th>Payment Status</th><th>Pass Number</th><th>Amount</th><th>User ID</th><th>Action</th></tr>";
    while ($app = $appsResult->fetch_assoc()) {
        $statusClass = '';
        switch ($app['payment_status']) {
            case 'Paid': $statusClass = 'success'; break;
            case 'Pending': $statusClass = 'warning'; break;
            case 'Payment_Required': $statusClass = 'info'; break;
            default: $statusClass = 'error';
        }
        
        echo "<tr>";
        echo "<td>{$app['id']}</td>";
        echo "<td>" . htmlspecialchars($app['application_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($app['applicant_name']) . "</td>";
        echo "<td class='$statusClass'><strong>{$app['payment_status']}</strong></td>";
        echo "<td>" . htmlspecialchars($app['pass_number'] ?? 'Not assigned') . "</td>";
        echo "<td>₹" . number_format($app['amount'], 2) . "</td>";
        echo "<td>{$app['user_id']}</td>";
        echo "<td>";
        if ($app['payment_status'] !== 'Paid') {
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='application_id' value='{$app['id']}'>";
            echo "<button type='submit' name='test_payment' style='background:#28a745;'>Test Payment</button>";
            echo "</form>";
        } else {
            echo "<span class='success'>✅ Paid</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No applications found</p>";
}

// Show payment records
echo "<h3>💳 Payment Records</h3>";
$paymentsQuery = "SELECT p.*, ba.applicant_name FROM payments p LEFT JOIN bus_pass_applications ba ON p.application_id = ba.id ORDER BY p.payment_date DESC LIMIT 10";
$paymentsResult = $con->query($paymentsQuery);

if ($paymentsResult && $paymentsResult->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>App ID</th><th>Name</th><th>Amount</th><th>Method</th><th>Status</th><th>Transaction ID</th><th>Date</th></tr>";
    while ($payment = $paymentsResult->fetch_assoc()) {
        $statusClass = $payment['status'] === 'completed' ? 'success' : 'warning';
        
        echo "<tr>";
        echo "<td>{$payment['id']}</td>";
        echo "<td>{$payment['application_id']}</td>";
        echo "<td>" . htmlspecialchars($payment['applicant_name'] ?? 'N/A') . "</td>";
        echo "<td>₹" . number_format($payment['amount'], 2) . "</td>";
        echo "<td>{$payment['payment_method']}</td>";
        echo "<td class='$statusClass'><strong>{$payment['status']}</strong></td>";
        echo "<td>" . htmlspecialchars($payment['transaction_id']) . "</td>";
        echo "<td>" . date('M d, Y H:i', strtotime($payment['payment_date'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No payment records found</p>";
}

// Quick fix buttons
echo "<h3>🔧 Quick Actions</h3>";
echo "<form method='post' style='display:inline;'>";
echo "<button type='submit' name='fix_all_payments' onclick='return confirm(\"Fix all payment statuses?\")' style='background:#dc3545;'>Fix All Payment Statuses</button>";
echo "</form>";

if (isset($_POST['fix_all_payments'])) {
    echo "<h4>🔄 Fixing All Payment Statuses...</h4>";
    
    // Fix applications with completed payments
    $fixQuery = "UPDATE bus_pass_applications ba 
                 JOIN payments p ON ba.id = p.application_id 
                 SET ba.payment_status = 'Paid' 
                 WHERE p.status = 'completed' AND ba.payment_status != 'Paid'";
    
    if ($con->query($fixQuery)) {
        $affected = $con->affected_rows;
        echo "<p class='success'>✅ Fixed $affected applications with completed payments</p>";
    } else {
        echo "<p class='error'>❌ Error: " . $con->error . "</p>";
    }
    
    // Generate missing pass numbers
    $passQuery = "SELECT id FROM bus_pass_applications WHERE payment_status = 'Paid' AND (pass_number IS NULL OR pass_number = '')";
    $passResult = $con->query($passQuery);
    $passCount = 0;
    
    while ($row = $passResult->fetch_assoc()) {
        $passNumber = 'BP' . date('Y') . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
        $updatePassQuery = "UPDATE bus_pass_applications SET pass_number = ? WHERE id = ?";
        $stmt = $con->prepare($updatePassQuery);
        $stmt->bind_param("si", $passNumber, $row['id']);
        if ($stmt->execute()) {
            $passCount++;
        }
    }
    
    echo "<p class='success'>✅ Generated $passCount missing pass numbers</p>";
    echo "<p><a href='debug-payment.php'>🔄 Refresh to see changes</a></p>";
}

echo "<h3>🔗 Navigation</h3>";
echo "<p>";
echo "<a href='user-dashboard.php'>User Dashboard</a> | ";
echo "<a href='payment.php?application_id=1'>Test Payment Page</a> | ";
echo "<a href='admin-dashboard.php'>Admin Dashboard</a>";
echo "</p>";

echo "</body></html>";
?>
