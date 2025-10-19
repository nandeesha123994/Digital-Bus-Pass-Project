<?php
session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Force Fix Payment Status</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .warning{color:#ffc107;} .info{color:#007bff;}";
echo "button{background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin:5px;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}";
echo "</style></head><body>";

echo "<h2>🔧 Force Fix Payment Status</h2>";

// Check current status
echo "<h3>📊 Current Status</h3>";
$statusQuery = "SELECT payment_status, COUNT(*) as count FROM bus_pass_applications GROUP BY payment_status";
$statusResult = $con->query($statusQuery);

echo "<table>";
echo "<tr><th>Payment Status</th><th>Count</th></tr>";
while ($row = $statusResult->fetch_assoc()) {
    $class = $row['payment_status'] === 'Paid' ? 'success' : ($row['payment_status'] === 'Pending' ? 'warning' : 'info');
    echo "<tr><td class='$class'><strong>{$row['payment_status']}</strong></td><td>{$row['count']}</td></tr>";
}
echo "</table>";

// Force fix all payments
if (isset($_POST['force_fix'])) {
    echo "<h3>🔄 Force Fixing All Payment Issues...</h3>";
    
    $con->begin_transaction();
    
    try {
        // Step 1: Create payments table if it doesn't exist
        $createPaymentsTable = "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'completed',
            transaction_id VARCHAR(100) NOT NULL,
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_application_id (application_id),
            INDEX idx_user_id (user_id)
        )";
        
        if ($con->query($createPaymentsTable)) {
            echo "<p class='success'>✅ Payments table ensured</p>";
        }
        
        // Step 2: Get all applications that should be paid but aren't
        $applicationsQuery = "SELECT id, user_id, amount, applicant_name FROM bus_pass_applications WHERE payment_status != 'Paid' AND amount > 0";
        $applicationsResult = $con->query($applicationsQuery);
        
        $fixedCount = 0;
        while ($app = $applicationsResult->fetch_assoc()) {
            // Check if payment record exists
            $checkPaymentQuery = "SELECT id FROM payments WHERE application_id = ?";
            $checkStmt = $con->prepare($checkPaymentQuery);
            $checkStmt->bind_param("i", $app['id']);
            $checkStmt->execute();
            $paymentExists = $checkStmt->get_result()->fetch_assoc();
            
            if (!$paymentExists) {
                // Create payment record
                $transactionId = 'FIX_' . time() . '_' . $app['id'];
                $insertPaymentQuery = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) VALUES (?, ?, ?, 'demo', 'completed', ?, NOW())";
                $insertStmt = $con->prepare($insertPaymentQuery);
                $insertStmt->bind_param("iids", $app['id'], $app['user_id'], $app['amount'], $transactionId);
                $insertStmt->execute();
                
                echo "<p class='info'>📝 Created payment record for application {$app['id']} ({$app['applicant_name']})</p>";
            }
            
            // Update application status to Paid
            $updateQuery = "UPDATE bus_pass_applications SET payment_status = 'Paid' WHERE id = ?";
            $updateStmt = $con->prepare($updateQuery);
            $updateStmt->bind_param("i", $app['id']);
            
            if ($updateStmt->execute()) {
                $fixedCount++;
                echo "<p class='success'>✅ Fixed payment status for application {$app['id']} ({$app['applicant_name']})</p>";
            }
        }
        
        // Step 3: Generate missing pass numbers
        $passQuery = "SELECT id FROM bus_pass_applications WHERE payment_status = 'Paid' AND (pass_number IS NULL OR pass_number = '')";
        $passResult = $con->query($passQuery);
        $passCount = 0;
        
        while ($row = $passResult->fetch_assoc()) {
            $passNumber = 'BP' . date('Y') . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
            $updatePassQuery = "UPDATE bus_pass_applications SET pass_number = ?, processed_date = NOW() WHERE id = ?";
            $stmt = $con->prepare($updatePassQuery);
            $stmt->bind_param("si", $passNumber, $row['id']);
            if ($stmt->execute()) {
                $passCount++;
                echo "<p class='success'>✅ Generated pass number $passNumber for application {$row['id']}</p>";
            }
        }
        
        $con->commit();
        
        echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h4 class='success'>🎉 Force Fix Complete!</h4>";
        echo "<p>✅ Fixed $fixedCount payment statuses</p>";
        echo "<p>✅ Generated $passCount pass numbers</p>";
        echo "<p>✅ All applications should now show correct payment status</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        $con->rollback();
        echo "<p class='error'>❌ Error during force fix: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// Manual fix for specific application
if (isset($_POST['fix_specific'])) {
    $appId = intval($_POST['app_id']);
    
    echo "<h3>🔧 Fixing Application ID: $appId</h3>";
    
    $con->begin_transaction();
    
    try {
        // Get application details
        $getAppQuery = "SELECT * FROM bus_pass_applications WHERE id = ?";
        $getAppStmt = $con->prepare($getAppQuery);
        $getAppStmt->bind_param("i", $appId);
        $getAppStmt->execute();
        $app = $getAppStmt->get_result()->fetch_assoc();
        
        if (!$app) {
            throw new Exception("Application not found");
        }
        
        // Create payment record if doesn't exist
        $checkPaymentQuery = "SELECT id FROM payments WHERE application_id = ?";
        $checkStmt = $con->prepare($checkPaymentQuery);
        $checkStmt->bind_param("i", $appId);
        $checkStmt->execute();
        $paymentExists = $checkStmt->get_result()->fetch_assoc();
        
        if (!$paymentExists) {
            $transactionId = 'MANUAL_' . time() . '_' . $appId;
            $insertPaymentQuery = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) VALUES (?, ?, ?, 'demo', 'completed', ?, NOW())";
            $insertStmt = $con->prepare($insertPaymentQuery);
            $insertStmt->bind_param("iids", $appId, $app['user_id'], $app['amount'], $transactionId);
            $insertStmt->execute();
            echo "<p class='success'>✅ Created payment record</p>";
        }
        
        // Update payment status
        $updateQuery = "UPDATE bus_pass_applications SET payment_status = 'Paid' WHERE id = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("i", $appId);
        $updateStmt->execute();
        
        // Generate pass number if missing
        if (empty($app['pass_number'])) {
            $passNumber = 'BP' . date('Y') . str_pad($appId, 6, '0', STR_PAD_LEFT);
            $updatePassQuery = "UPDATE bus_pass_applications SET pass_number = ?, processed_date = NOW() WHERE id = ?";
            $passStmt = $con->prepare($updatePassQuery);
            $passStmt->bind_param("si", $passNumber, $appId);
            $passStmt->execute();
            echo "<p class='success'>✅ Generated pass number: $passNumber</p>";
        }
        
        $con->commit();
        echo "<p class='success'>✅ Application $appId fixed successfully!</p>";
        
    } catch (Exception $e) {
        $con->rollback();
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// Show current applications
echo "<h3>📋 Current Applications (Last 10)</h3>";
$appsQuery = "SELECT id, application_id, applicant_name, payment_status, pass_number, amount FROM bus_pass_applications ORDER BY id DESC LIMIT 10";
$appsResult = $con->query($appsQuery);

if ($appsResult && $appsResult->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>App ID</th><th>Name</th><th>Payment Status</th><th>Pass Number</th><th>Amount</th><th>Action</th></tr>";
    while ($app = $appsResult->fetch_assoc()) {
        $statusClass = $app['payment_status'] === 'Paid' ? 'success' : 'warning';
        
        echo "<tr>";
        echo "<td>{$app['id']}</td>";
        echo "<td>" . htmlspecialchars($app['application_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($app['applicant_name']) . "</td>";
        echo "<td class='$statusClass'><strong>{$app['payment_status']}</strong></td>";
        echo "<td>" . htmlspecialchars($app['pass_number'] ?? 'Not assigned') . "</td>";
        echo "<td>₹" . number_format($app['amount'], 2) . "</td>";
        echo "<td>";
        if ($app['payment_status'] !== 'Paid') {
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='app_id' value='{$app['id']}'>";
            echo "<button type='submit' name='fix_specific' style='background:#28a745;'>Fix This</button>";
            echo "</form>";
        } else {
            echo "<span class='success'>✅ OK</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Action buttons
echo "<h3>🚀 Actions</h3>";
echo "<form method='post' style='margin:20px 0;'>";
echo "<button type='submit' name='force_fix' onclick='return confirm(\"This will force fix ALL payment statuses. Continue?\")' style='background:#dc3545;font-size:16px;padding:15px 30px;'>🔧 FORCE FIX ALL PAYMENTS</button>";
echo "</form>";

echo "<h3>🔗 Navigation</h3>";
echo "<p>";
echo "<a href='user-dashboard.php'>User Dashboard</a> | ";
echo "<a href='debug-payment.php'>Debug Payment</a> | ";
echo "<a href='admin-dashboard.php'>Admin Dashboard</a>";
echo "</p>";

echo "</body></html>";
?>
