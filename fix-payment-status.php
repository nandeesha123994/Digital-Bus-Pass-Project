<?php
session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix Payment Status</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .warning{color:#ffc107;} .info{color:#007bff;}";
echo "button{background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin:5px;}";
echo "button:hover{background:#0056b3;} .fix-btn{background:#28a745;} .fix-btn:hover{background:#1e7e34;}";
echo "</style></head><body>";

echo "<h2>🔧 Payment Status Fix Tool</h2>";

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'fix_completed_payments':
            echo "<h3>🔄 Fixing Completed Payments...</h3>";
            
            // Update applications that have completed payments but wrong status
            $query = "UPDATE bus_pass_applications ba 
                      JOIN payments p ON ba.id = p.application_id 
                      SET ba.payment_status = 'Paid' 
                      WHERE p.status = 'completed' AND ba.payment_status != 'Paid'";
            
            if ($con->query($query)) {
                $affected = $con->affected_rows;
                echo "<p class='success'>✅ Fixed $affected application(s) with completed payments</p>";
            } else {
                echo "<p class='error'>❌ Error: " . $con->error . "</p>";
            }
            break;
            
        case 'fix_pending_payments':
            echo "<h3>🔄 Fixing Pending Payments...</h3>";
            
            // Update applications without payment records to Payment_Required
            $query = "UPDATE bus_pass_applications ba 
                      LEFT JOIN payments p ON ba.id = p.application_id 
                      SET ba.payment_status = 'Payment_Required' 
                      WHERE p.id IS NULL AND ba.payment_status != 'Payment_Required' AND ba.status != 'Approved'";
            
            if ($con->query($query)) {
                $affected = $con->affected_rows;
                echo "<p class='success'>✅ Fixed $affected application(s) without payment records</p>";
            } else {
                echo "<p class='error'>❌ Error: " . $con->error . "</p>";
            }
            break;
            
        case 'generate_pass_numbers':
            echo "<h3>🔄 Generating Missing Pass Numbers...</h3>";
            
            // Generate pass numbers for paid applications without them
            $query = "SELECT id FROM bus_pass_applications WHERE payment_status = 'Paid' AND (pass_number IS NULL OR pass_number = '')";
            $result = $con->query($query);
            
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                $passNumber = 'BP' . date('Y') . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
                $updateQuery = "UPDATE bus_pass_applications SET pass_number = ? WHERE id = ?";
                $stmt = $con->prepare($updateQuery);
                $stmt->bind_param("si", $passNumber, $row['id']);
                if ($stmt->execute()) {
                    $count++;
                }
            }
            
            echo "<p class='success'>✅ Generated $count pass numbers</p>";
            break;
            
        case 'fix_all':
            echo "<h3>🔄 Running Complete Fix...</h3>";
            
            $con->begin_transaction();
            
            try {
                // Fix 1: Update completed payments
                $query1 = "UPDATE bus_pass_applications ba 
                          JOIN payments p ON ba.id = p.application_id 
                          SET ba.payment_status = 'Paid' 
                          WHERE p.status = 'completed' AND ba.payment_status != 'Paid'";
                $con->query($query1);
                $fix1 = $con->affected_rows;
                
                // Fix 2: Update applications without payments
                $query2 = "UPDATE bus_pass_applications ba 
                          LEFT JOIN payments p ON ba.id = p.application_id 
                          SET ba.payment_status = 'Payment_Required' 
                          WHERE p.id IS NULL AND ba.payment_status != 'Payment_Required' AND ba.status != 'Approved'";
                $con->query($query2);
                $fix2 = $con->affected_rows;
                
                // Fix 3: Generate missing pass numbers
                $query3 = "SELECT id FROM bus_pass_applications WHERE payment_status = 'Paid' AND (pass_number IS NULL OR pass_number = '')";
                $result3 = $con->query($query3);
                $fix3 = 0;
                
                while ($row = $result3->fetch_assoc()) {
                    $passNumber = 'BP' . date('Y') . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
                    $updateQuery = "UPDATE bus_pass_applications SET pass_number = ? WHERE id = ?";
                    $stmt = $con->prepare($updateQuery);
                    $stmt->bind_param("si", $passNumber, $row['id']);
                    if ($stmt->execute()) {
                        $fix3++;
                    }
                }
                
                $con->commit();
                
                echo "<p class='success'>✅ Complete fix applied successfully!</p>";
                echo "<ul>";
                echo "<li>Fixed $fix1 applications with completed payments</li>";
                echo "<li>Fixed $fix2 applications without payment records</li>";
                echo "<li>Generated $fix3 missing pass numbers</li>";
                echo "</ul>";
                
            } catch (Exception $e) {
                $con->rollback();
                echo "<p class='error'>❌ Error during fix: " . $e->getMessage() . "</p>";
            }
            break;
    }
    
    echo "<hr>";
}

// Show current status
echo "<h3>📊 Current Payment Status Overview</h3>";

$statusQuery = "SELECT 
                    payment_status,
                    COUNT(*) as count,
                    SUM(CASE WHEN pass_number IS NOT NULL AND pass_number != '' THEN 1 ELSE 0 END) as with_pass_number
                FROM bus_pass_applications 
                GROUP BY payment_status";
$statusResult = $con->query($statusQuery);

echo "<table border='1' style='border-collapse:collapse;width:100%;margin:20px 0;'>";
echo "<tr style='background:#f2f2f2;'><th>Payment Status</th><th>Count</th><th>With Pass Number</th></tr>";

while ($row = $statusResult->fetch_assoc()) {
    $statusClass = '';
    switch ($row['payment_status']) {
        case 'Paid': $statusClass = 'success'; break;
        case 'Pending': $statusClass = 'warning'; break;
        case 'Payment_Required': $statusClass = 'info'; break;
        default: $statusClass = 'error';
    }
    
    echo "<tr>";
    echo "<td class='$statusClass'><strong>" . htmlspecialchars($row['payment_status']) . "</strong></td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "<td>" . $row['with_pass_number'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show payment records status
echo "<h3>💳 Payment Records Status</h3>";
$paymentStatusQuery = "SELECT status, COUNT(*) as count FROM payments GROUP BY status";
$paymentStatusResult = $con->query($paymentStatusQuery);

echo "<table border='1' style='border-collapse:collapse;width:100%;margin:20px 0;'>";
echo "<tr style='background:#f2f2f2;'><th>Payment Record Status</th><th>Count</th></tr>";

while ($row = $paymentStatusResult->fetch_assoc()) {
    $statusClass = '';
    switch ($row['status']) {
        case 'completed': $statusClass = 'success'; break;
        case 'pending': $statusClass = 'warning'; break;
        case 'failed': $statusClass = 'error'; break;
        default: $statusClass = 'info';
    }
    
    echo "<tr>";
    echo "<td class='$statusClass'><strong>" . htmlspecialchars($row['status']) . "</strong></td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show mismatches
echo "<h3>⚠️ Status Mismatches</h3>";
$mismatchQuery = "SELECT 
                    COUNT(*) as total_mismatches,
                    SUM(CASE WHEN ba.payment_status = 'Paid' AND p.status IS NULL THEN 1 ELSE 0 END) as paid_no_record,
                    SUM(CASE WHEN ba.payment_status != 'Paid' AND p.status = 'completed' THEN 1 ELSE 0 END) as completed_not_paid,
                    SUM(CASE WHEN ba.payment_status = 'Paid' AND (ba.pass_number IS NULL OR ba.pass_number = '') THEN 1 ELSE 0 END) as paid_no_pass
                  FROM bus_pass_applications ba 
                  LEFT JOIN payments p ON ba.id = p.application_id";
$mismatchResult = $con->query($mismatchQuery);
$mismatches = $mismatchResult->fetch_assoc();

if ($mismatches['completed_not_paid'] > 0 || $mismatches['paid_no_record'] > 0 || $mismatches['paid_no_pass'] > 0) {
    echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<p class='warning'><strong>⚠️ Issues Found:</strong></p>";
    echo "<ul>";
    if ($mismatches['completed_not_paid'] > 0) {
        echo "<li>{$mismatches['completed_not_paid']} applications with completed payments but not marked as Paid</li>";
    }
    if ($mismatches['paid_no_record'] > 0) {
        echo "<li>{$mismatches['paid_no_record']} applications marked as Paid but no payment record found</li>";
    }
    if ($mismatches['paid_no_pass'] > 0) {
        echo "<li>{$mismatches['paid_no_pass']} paid applications without pass numbers</li>";
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<p class='success'>✅ No payment status mismatches found!</p>";
}

// Fix options
echo "<h3>🔧 Fix Options</h3>";
echo "<form method='post' style='margin:20px 0;'>";

echo "<button type='submit' name='action' value='fix_completed_payments' class='fix-btn'>";
echo "Fix Completed Payments ({$mismatches['completed_not_paid']} issues)";
echo "</button>";

echo "<button type='submit' name='action' value='fix_pending_payments'>";
echo "Fix Pending Payments";
echo "</button>";

echo "<button type='submit' name='action' value='generate_pass_numbers' class='fix-btn'>";
echo "Generate Pass Numbers ({$mismatches['paid_no_pass']} missing)";
echo "</button>";

echo "<button type='submit' name='action' value='fix_all' class='fix-btn' style='background:#dc3545;' onclick='return confirm(\"This will fix all payment status issues. Continue?\");'>";
echo "🚀 Fix All Issues";
echo "</button>";

echo "</form>";

echo "<h3>🔗 Navigation</h3>";
echo "<p>";
echo "<a href='user-dashboard.php'>User Dashboard</a> | ";
echo "<a href='admin-dashboard.php'>Admin Dashboard</a> | ";
echo "<a href='test-payment-status.php'>Test Payment Status</a>";
echo "</p>";

echo "</body></html>";
?>
