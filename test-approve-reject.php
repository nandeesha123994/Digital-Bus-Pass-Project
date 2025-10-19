<?php
session_start();
include('includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Approve/Reject Functionality</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .warning{color:#ffc107;} .info{color:#007bff;}";
echo "button{background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin:5px;}";
echo ".approve-btn{background:#28a745;} .reject-btn{background:#dc3545;} .paid-btn{background:#ffc107;color:black;}";
echo "</style></head><body>";

echo "<h2>🔧 Test Approve/Reject Functionality</h2>";

$message = '';
$messageType = '';

// Handle test actions
if (isset($_POST['test_action'])) {
    $action = $_POST['test_action'];
    $applicationId = intval($_POST['application_id']);
    
    echo "<h3>🧪 Testing $action for Application ID: $applicationId</h3>";
    
    // Get current application status
    $currentQuery = "SELECT * FROM bus_pass_applications WHERE id = ?";
    $currentStmt = $con->prepare($currentQuery);
    $currentStmt->bind_param("i", $applicationId);
    $currentStmt->execute();
    $current = $currentStmt->get_result()->fetch_assoc();
    
    if (!$current) {
        echo "<p class='error'>❌ Application not found!</p>";
    } else {
        echo "<p><strong>Before:</strong> Status = {$current['status']}, Payment = {$current['payment_status']}</p>";
        
        $success = false;
        $newStatus = $current['status'];
        $newPaymentStatus = $current['payment_status'];
        
        switch ($action) {
            case 'approve':
                $updateQuery = "UPDATE bus_pass_applications SET status = 'Approved', admin_remarks = 'Test approval by admin', processed_date = NOW() WHERE id = ?";
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("i", $applicationId);
                $success = $updateStmt->execute();
                $newStatus = 'Approved';
                break;
                
            case 'reject':
                $updateQuery = "UPDATE bus_pass_applications SET status = 'Rejected', admin_remarks = 'Test rejection by admin', processed_date = NOW() WHERE id = ?";
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("i", $applicationId);
                $success = $updateStmt->execute();
                $newStatus = 'Rejected';
                break;
                
            case 'mark_paid':
                $updateQuery = "UPDATE bus_pass_applications SET payment_status = 'Paid', admin_remarks = 'Test payment update by admin', processed_date = NOW() WHERE id = ?";
                $updateStmt = $con->prepare($updateQuery);
                $updateStmt->bind_param("i", $applicationId);
                $success = $updateStmt->execute();
                $newPaymentStatus = 'Paid';
                break;
        }
        
        if ($success) {
            echo "<p class='success'>✅ Update successful!</p>";
            echo "<p><strong>After:</strong> Status = $newStatus, Payment = $newPaymentStatus</p>";
            
            // Verify the change
            $verifyQuery = "SELECT status, payment_status, admin_remarks FROM bus_pass_applications WHERE id = ?";
            $verifyStmt = $con->prepare($verifyQuery);
            $verifyStmt->bind_param("i", $applicationId);
            $verifyStmt->execute();
            $verified = $verifyStmt->get_result()->fetch_assoc();
            
            echo "<p><strong>Verified:</strong> Status = {$verified['status']}, Payment = {$verified['payment_status']}</p>";
            echo "<p><strong>Remarks:</strong> {$verified['admin_remarks']}</p>";
        } else {
            echo "<p class='error'>❌ Update failed: " . $con->error . "</p>";
        }
    }
    
    echo "<hr>";
}

// Show applications for testing
echo "<h3>📋 Applications Available for Testing</h3>";
$appsQuery = "SELECT id, applicant_name, status, payment_status, amount FROM bus_pass_applications ORDER BY id DESC LIMIT 10";
$appsResult = $con->query($appsQuery);

if ($appsResult && $appsResult->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Status</th><th>Payment Status</th><th>Amount</th><th>Test Actions</th></tr>";
    while ($app = $appsResult->fetch_assoc()) {
        $statusClass = '';
        switch ($app['status']) {
            case 'Approved': $statusClass = 'success'; break;
            case 'Rejected': $statusClass = 'error'; break;
            default: $statusClass = 'warning';
        }
        
        $paymentClass = $app['payment_status'] === 'Paid' ? 'success' : 'warning';
        
        echo "<tr>";
        echo "<td>{$app['id']}</td>";
        echo "<td>" . htmlspecialchars($app['applicant_name']) . "</td>";
        echo "<td class='$statusClass'><strong>{$app['status']}</strong></td>";
        echo "<td class='$paymentClass'><strong>{$app['payment_status']}</strong></td>";
        echo "<td>₹" . number_format($app['amount'], 2) . "</td>";
        echo "<td>";
        
        // Test buttons
        if ($app['status'] !== 'Approved') {
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='application_id' value='{$app['id']}'>";
            echo "<button type='submit' name='test_action' value='approve' class='approve-btn'>Test Approve</button>";
            echo "</form>";
        }
        
        if ($app['status'] !== 'Rejected') {
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='application_id' value='{$app['id']}'>";
            echo "<button type='submit' name='test_action' value='reject' class='reject-btn'>Test Reject</button>";
            echo "</form>";
        }
        
        if ($app['payment_status'] !== 'Paid') {
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='application_id' value='{$app['id']}'>";
            echo "<button type='submit' name='test_action' value='mark_paid' class='paid-btn'>Test Mark Paid</button>";
            echo "</form>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No applications found</p>";
}

// Test database connection and permissions
echo "<h3>🗄️ Database Test</h3>";
if ($con) {
    echo "<p class='success'>✅ Database connected</p>";
    
    // Test UPDATE permission
    $testQuery = "SELECT id FROM bus_pass_applications LIMIT 1";
    $testResult = $con->query($testQuery);
    if ($testResult && $testResult->num_rows > 0) {
        $testApp = $testResult->fetch_assoc();
        $testId = $testApp['id'];
        
        // Try a harmless update
        $updateTest = "UPDATE bus_pass_applications SET processed_date = processed_date WHERE id = $testId";
        if ($con->query($updateTest)) {
            echo "<p class='success'>✅ UPDATE permission working</p>";
        } else {
            echo "<p class='error'>❌ UPDATE permission failed: " . $con->error . "</p>";
        }
    }
} else {
    echo "<p class='error'>❌ Database connection failed</p>";
}

// Show current admin session
echo "<h3>🔐 Admin Session Status</h3>";
echo "<p class='success'>✅ Admin logged in: " . ($_SESSION['admin_logged_in'] ? 'Yes' : 'No') . "</p>";
echo "<p>Admin username: " . ($_SESSION['admin_username'] ?? 'Not set') . "</p>";

// Quick links
echo "<h3>🔗 Quick Links</h3>";
echo "<p>";
echo "<a href='admin-actions.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>Admin Actions Dashboard</a>";
echo "<a href='simple-admin-view.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>Simple Admin View</a>";
echo "<a href='admin-dashboard.php' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>Full Admin Dashboard</a>";
echo "</p>";

// JavaScript test
echo "<h3>🔧 JavaScript Test</h3>";
echo "<button onclick='testJavaScript()' style='background:#17a2b8;'>Test JavaScript</button>";
echo "<div id='jsTest'></div>";

echo "<script>";
echo "function testJavaScript() {";
echo "    document.getElementById('jsTest').innerHTML = '<p class=\"success\">✅ JavaScript is working!</p>';";
echo "}";
echo "</script>";

echo "</body></html>";
?>
