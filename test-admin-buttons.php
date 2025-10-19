<?php
session_start();
include('includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Admin Buttons</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .warning{color:#ffc107;} .info{color:#007bff;}";
echo "button{background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin:5px;}";
echo ".test-btn{background:#28a745;} .view-btn{background:#17a2b8;}";
echo "</style></head><body>";

echo "<h2>🔧 Admin Dashboard Button Test</h2>";

// Test get-application-details.php
if (isset($_GET['test_details'])) {
    $appId = intval($_GET['test_details']);
    echo "<h3>🔍 Testing get-application-details.php for Application ID: $appId</h3>";
    
    // Simulate the AJAX call
    $url = "get-application-details.php?id=$appId";
    echo "<p><strong>URL:</strong> <a href='$url' target='_blank'>$url</a></p>";
    
    // Test the actual call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/buspassmsfull/$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre style='background:#f8f9fa;padding:15px;border-radius:5px;overflow:auto;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    // Try to decode JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "<p class='success'>✅ JSON Response Valid</p>";
        if ($data['success']) {
            echo "<p class='success'>✅ Application data loaded successfully</p>";
        } else {
            echo "<p class='error'>❌ Error: " . $data['message'] . "</p>";
        }
    } else {
        echo "<p class='error'>❌ Invalid JSON response</p>";
    }
    
    echo "<hr>";
}

// Show applications for testing
echo "<h3>📋 Applications Available for Testing</h3>";
$appsQuery = "SELECT id, applicant_name, status, payment_status FROM bus_pass_applications ORDER BY id DESC LIMIT 10";
$appsResult = $con->query($appsQuery);

if ($appsResult && $appsResult->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Status</th><th>Payment Status</th><th>Test Actions</th></tr>";
    while ($app = $appsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$app['id']}</td>";
        echo "<td>" . htmlspecialchars($app['applicant_name']) . "</td>";
        echo "<td>{$app['status']}</td>";
        echo "<td>{$app['payment_status']}</td>";
        echo "<td>";
        echo "<a href='?test_details={$app['id']}' class='test-btn' style='text-decoration:none;color:white;padding:5px 10px;border-radius:3px;'>Test Details</a> ";
        echo "<button onclick='testViewButton({$app['id']})' class='view-btn'>Test View Button</button>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No applications found</p>";
}

// JavaScript test functions
echo "<script>";
echo "function testViewButton(appId) {";
echo "    console.log('Testing view button for application:', appId);";
echo "    ";
echo "    // Test the AJAX call";
echo "    fetch('get-application-details.php?id=' + appId)";
echo "        .then(response => {";
echo "            console.log('Response status:', response.status);";
echo "            return response.json();";
echo "        })";
echo "        .then(data => {";
echo "            console.log('Response data:', data);";
echo "            if (data.success) {";
echo "                alert('✅ View button working! Application data loaded successfully.');";
echo "            } else {";
echo "                alert('❌ Error: ' + data.message);";
echo "            }";
echo "        })";
echo "        .catch(error => {";
echo "            console.error('Error:', error);";
echo "            alert('❌ AJAX Error: ' + error.message);";
echo "        });";
echo "}";
echo "</script>";

// Test modal functionality
echo "<h3>🔧 Test Modal Functionality</h3>";
echo "<button onclick='testModal()' style='background:#dc3545;'>Test Modal</button>";

echo "<div id='testModal' style='display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;'>";
echo "<div style='position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:30px;border-radius:10px;max-width:500px;width:90%;'>";
echo "<h4>Test Modal</h4>";
echo "<p>This is a test modal to check if modal functionality works.</p>";
echo "<button onclick='closeTestModal()' style='background:#dc3545;'>Close Modal</button>";
echo "</div>";
echo "</div>";

echo "<script>";
echo "function testModal() {";
echo "    document.getElementById('testModal').style.display = 'block';";
echo "}";
echo "function closeTestModal() {";
echo "    document.getElementById('testModal').style.display = 'none';";
echo "}";
echo "</script>";

// Check JavaScript errors
echo "<h3>🔍 JavaScript Error Check</h3>";
echo "<p>Open browser console (F12) and check for any JavaScript errors when clicking buttons.</p>";

// Direct test of admin dashboard
echo "<h3>🔗 Direct Tests</h3>";
echo "<p>";
echo "<a href='admin-dashboard.php' target='_blank' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>Open Admin Dashboard</a>";
echo "<a href='get-application-details.php?id=1' target='_blank' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;display:inline-block;'>Test Details API</a>";
echo "</p>";

// Check if admin session is working
echo "<h3>🔐 Admin Session Check</h3>";
echo "<p class='success'>✅ Admin logged in: " . ($_SESSION['admin_logged_in'] ? 'Yes' : 'No') . "</p>";
echo "<p>Admin username: " . ($_SESSION['admin_username'] ?? 'Not set') . "</p>";

// Database connection test
echo "<h3>🗄️ Database Connection Test</h3>";
if ($con) {
    echo "<p class='success'>✅ Database connected successfully</p>";
    
    // Test query
    $testQuery = "SELECT COUNT(*) as count FROM bus_pass_applications";
    $testResult = $con->query($testQuery);
    if ($testResult) {
        $count = $testResult->fetch_assoc()['count'];
        echo "<p class='success'>✅ Database query working - $count applications found</p>";
    } else {
        echo "<p class='error'>❌ Database query failed: " . $con->error . "</p>";
    }
} else {
    echo "<p class='error'>❌ Database connection failed</p>";
}

echo "</body></html>";
?>
