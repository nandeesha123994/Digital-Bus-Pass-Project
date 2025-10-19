<?php
session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Direct Database Fix</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .warning{color:#ffc107;} .info{color:#007bff;}";
echo "button{background:#007bff;color:white;padding:15px 30px;border:none;border-radius:5px;cursor:pointer;margin:10px;font-size:16px;}";
echo ".fix-btn{background:#dc3545;} .test-btn{background:#28a745;}";
echo "</style></head><body>";

echo "<h2>🔧 Direct Database Fix for Payment Status</h2>";

// Show current database state
echo "<h3>📊 Current Database State</h3>";

// Check bus_pass_applications table
echo "<h4>Bus Pass Applications Table:</h4>";
$appsQuery = "SELECT id, application_id, applicant_name, payment_status, status, pass_number, amount, user_id FROM bus_pass_applications ORDER BY id DESC LIMIT 10";
$appsResult = $con->query($appsQuery);

if ($appsResult) {
    echo "<table>";
    echo "<tr><th>ID</th><th>App ID</th><th>Name</th><th>Payment Status</th><th>App Status</th><th>Pass Number</th><th>Amount</th><th>User ID</th></tr>";
    while ($app = $appsResult->fetch_assoc()) {
        $paymentClass = $app['payment_status'] === 'Paid' ? 'success' : 'warning';
        $statusClass = $app['status'] === 'Approved' ? 'success' : 'info';
        
        echo "<tr>";
        echo "<td>{$app['id']}</td>";
        echo "<td>" . htmlspecialchars($app['application_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($app['applicant_name']) . "</td>";
        echo "<td class='$paymentClass'><strong>{$app['payment_status']}</strong></td>";
        echo "<td class='$statusClass'><strong>{$app['status']}</strong></td>";
        echo "<td>" . htmlspecialchars($app['pass_number'] ?? 'None') . "</td>";
        echo "<td>₹" . number_format($app['amount'], 2) . "</td>";
        echo "<td>{$app['user_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ Error querying applications: " . $con->error . "</p>";
}

// Check payments table
echo "<h4>Payments Table:</h4>";
$paymentsQuery = "SELECT * FROM payments ORDER BY payment_date DESC LIMIT 10";
$paymentsResult = $con->query($paymentsQuery);

if ($paymentsResult) {
    if ($paymentsResult->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>App ID</th><th>User ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Transaction ID</th><th>Date</th></tr>";
        while ($payment = $paymentsResult->fetch_assoc()) {
            $statusClass = $payment['status'] === 'completed' ? 'success' : 'warning';
            
            echo "<tr>";
            echo "<td>{$payment['id']}</td>";
            echo "<td>{$payment['application_id']}</td>";
            echo "<td>{$payment['user_id']}</td>";
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
} else {
    echo "<p class='error'>❌ Error querying payments: " . $con->error . "</p>";
}

// Direct SQL execution
if (isset($_POST['execute_sql'])) {
    $sql = trim($_POST['sql_command']);
    
    if (!empty($sql)) {
        echo "<h3>🔄 Executing SQL Command</h3>";
        echo "<p><strong>Command:</strong> <code>" . htmlspecialchars($sql) . "</code></p>";
        
        if ($con->query($sql)) {
            $affected = $con->affected_rows;
            echo "<p class='success'>✅ SQL executed successfully! Affected rows: $affected</p>";
        } else {
            echo "<p class='error'>❌ SQL Error: " . $con->error . "</p>";
        }
        
        echo "<hr>";
    }
}

// Predefined fixes
if (isset($_POST['fix_action'])) {
    $action = $_POST['fix_action'];
    
    echo "<h3>🔧 Executing Fix: $action</h3>";
    
    switch ($action) {
        case 'update_all_to_paid':
            $sql = "UPDATE bus_pass_applications SET payment_status = 'Paid' WHERE amount > 0";
            if ($con->query($sql)) {
                $affected = $con->affected_rows;
                echo "<p class='success'>✅ Updated $affected applications to 'Paid' status</p>";
            } else {
                echo "<p class='error'>❌ Error: " . $con->error . "</p>";
            }
            break;
            
        case 'generate_pass_numbers':
            $getAppsQuery = "SELECT id FROM bus_pass_applications WHERE payment_status = 'Paid' AND (pass_number IS NULL OR pass_number = '')";
            $getAppsResult = $con->query($getAppsQuery);
            $count = 0;
            
            while ($row = $getAppsResult->fetch_assoc()) {
                $passNumber = 'BP' . date('Y') . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
                $updateSql = "UPDATE bus_pass_applications SET pass_number = '$passNumber', processed_date = NOW() WHERE id = {$row['id']}";
                if ($con->query($updateSql)) {
                    $count++;
                }
            }
            echo "<p class='success'>✅ Generated $count pass numbers</p>";
            break;
            
        case 'create_payment_records':
            $getAppsQuery = "SELECT id, user_id, amount, applicant_name FROM bus_pass_applications WHERE payment_status = 'Paid'";
            $getAppsResult = $con->query($getAppsQuery);
            $count = 0;
            
            while ($app = $getAppsResult->fetch_assoc()) {
                // Check if payment record exists
                $checkSql = "SELECT id FROM payments WHERE application_id = {$app['id']}";
                $checkResult = $con->query($checkSql);
                
                if ($checkResult->num_rows == 0) {
                    $transactionId = 'AUTO_' . time() . '_' . $app['id'];
                    $insertSql = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) VALUES ({$app['id']}, {$app['user_id']}, {$app['amount']}, 'auto', 'completed', '$transactionId', NOW())";
                    if ($con->query($insertSql)) {
                        $count++;
                    }
                }
            }
            echo "<p class='success'>✅ Created $count payment records</p>";
            break;
            
        case 'approve_paid_applications':
            $sql = "UPDATE bus_pass_applications SET status = 'Approved' WHERE payment_status = 'Paid' AND status != 'Approved'";
            if ($con->query($sql)) {
                $affected = $con->affected_rows;
                echo "<p class='success'>✅ Approved $affected paid applications</p>";
            } else {
                echo "<p class='error'>❌ Error: " . $con->error . "</p>";
            }
            break;
            
        case 'complete_fix':
            echo "<p class='info'>🔄 Running complete fix...</p>";
            
            $con->begin_transaction();
            
            try {
                // Step 1: Update all applications with amount > 0 to Paid
                $sql1 = "UPDATE bus_pass_applications SET payment_status = 'Paid' WHERE amount > 0";
                $con->query($sql1);
                $step1 = $con->affected_rows;
                echo "<p class='success'>✅ Step 1: Updated $step1 applications to 'Paid'</p>";
                
                // Step 2: Generate pass numbers
                $getAppsQuery = "SELECT id FROM bus_pass_applications WHERE payment_status = 'Paid' AND (pass_number IS NULL OR pass_number = '')";
                $getAppsResult = $con->query($getAppsQuery);
                $step2 = 0;
                
                while ($row = $getAppsResult->fetch_assoc()) {
                    $passNumber = 'BP' . date('Y') . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
                    $updateSql = "UPDATE bus_pass_applications SET pass_number = '$passNumber', processed_date = NOW() WHERE id = {$row['id']}";
                    if ($con->query($updateSql)) {
                        $step2++;
                    }
                }
                echo "<p class='success'>✅ Step 2: Generated $step2 pass numbers</p>";
                
                // Step 3: Create payment records
                $getAppsQuery2 = "SELECT id, user_id, amount FROM bus_pass_applications WHERE payment_status = 'Paid'";
                $getAppsResult2 = $con->query($getAppsQuery2);
                $step3 = 0;
                
                while ($app = $getAppsResult2->fetch_assoc()) {
                    $checkSql = "SELECT id FROM payments WHERE application_id = {$app['id']}";
                    $checkResult = $con->query($checkSql);
                    
                    if ($checkResult->num_rows == 0) {
                        $transactionId = 'COMPLETE_' . time() . '_' . $app['id'];
                        $insertSql = "INSERT INTO payments (application_id, user_id, amount, payment_method, status, transaction_id, payment_date) VALUES ({$app['id']}, {$app['user_id']}, {$app['amount']}, 'auto', 'completed', '$transactionId', NOW())";
                        if ($con->query($insertSql)) {
                            $step3++;
                        }
                    }
                }
                echo "<p class='success'>✅ Step 3: Created $step3 payment records</p>";
                
                // Step 4: Approve paid applications
                $sql4 = "UPDATE bus_pass_applications SET status = 'Approved' WHERE payment_status = 'Paid' AND status != 'Approved'";
                $con->query($sql4);
                $step4 = $con->affected_rows;
                echo "<p class='success'>✅ Step 4: Approved $step4 applications</p>";
                
                $con->commit();
                
                echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
                echo "<h4 class='success'>🎉 COMPLETE FIX SUCCESSFUL!</h4>";
                echo "<p>✅ All payment statuses have been fixed</p>";
                echo "<p>✅ Pass numbers generated</p>";
                echo "<p>✅ Payment records created</p>";
                echo "<p>✅ Applications approved</p>";
                echo "<p><strong>Go check your user dashboard now!</strong></p>";
                echo "</div>";
                
            } catch (Exception $e) {
                $con->rollback();
                echo "<p class='error'>❌ Transaction failed: " . $e->getMessage() . "</p>";
            }
            break;
    }
    
    echo "<hr>";
}

// Quick fix buttons
echo "<h3>🚀 Quick Fix Actions</h3>";
echo "<form method='post' style='margin:20px 0;'>";

echo "<button type='submit' name='fix_action' value='complete_fix' class='fix-btn' onclick='return confirm(\"This will fix ALL payment issues. Continue?\")'>🔧 COMPLETE FIX (Recommended)</button><br>";

echo "<button type='submit' name='fix_action' value='update_all_to_paid' class='test-btn'>1. Update All to Paid</button>";
echo "<button type='submit' name='fix_action' value='generate_pass_numbers' class='test-btn'>2. Generate Pass Numbers</button>";
echo "<button type='submit' name='fix_action' value='create_payment_records' class='test-btn'>3. Create Payment Records</button>";
echo "<button type='submit' name='fix_action' value='approve_paid_applications' class='test-btn'>4. Approve Paid Apps</button>";

echo "</form>";

// Manual SQL execution
echo "<h3>🔧 Manual SQL Execution</h3>";
echo "<form method='post'>";
echo "<textarea name='sql_command' rows='4' cols='80' placeholder='Enter SQL command here...'></textarea><br>";
echo "<button type='submit' name='execute_sql' onclick='return confirm(\"Execute this SQL command?\")'>Execute SQL</button>";
echo "</form>";

// Suggested SQL commands
echo "<h4>💡 Suggested SQL Commands:</h4>";
echo "<ul>";
echo "<li><code>UPDATE bus_pass_applications SET payment_status = 'Paid' WHERE amount > 0;</code></li>";
echo "<li><code>UPDATE bus_pass_applications SET status = 'Approved' WHERE payment_status = 'Paid';</code></li>";
echo "<li><code>SELECT * FROM bus_pass_applications WHERE payment_status != 'Paid';</code></li>";
echo "<li><code>SELECT COUNT(*) as total, payment_status FROM bus_pass_applications GROUP BY payment_status;</code></li>";
echo "</ul>";

echo "<h3>🔗 Navigation</h3>";
echo "<p>";
echo "<a href='user-dashboard.php' target='_blank'>User Dashboard</a> | ";
echo "<a href='admin-dashboard.php' target='_blank'>Admin Dashboard</a> | ";
echo "<a href='debug-payment.php'>Debug Payment</a>";
echo "</p>";

echo "</body></html>";
?>
