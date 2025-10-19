<?php
/**
 * System Status Checker for Bus Pass Management System
 * Quick diagnostic tool to check system health
 */

include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>System Status Check</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .status-card { background: white; margin: 15px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; } .error { color: #dc3545; } .warning { color: #ffc107; } .info { color: #17a2b8; }
    .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
    .metric { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .metric h4 { margin: 0 0 10px 0; color: #495057; }
    .metric .value { font-size: 1.5em; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
    th { background: #e9ecef; }
    .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
</style></head><body>";

echo "<h1>🔍 Bus Pass Management System - Status Check</h1>";

// Database Connection Check
echo "<div class='status-card'>";
echo "<h2>📊 Database Status</h2>";

if ($con) {
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Check tables
    $requiredTables = [
        'users' => 'User accounts',
        'bus_pass_types' => 'Pass types configuration',
        'bus_pass_applications' => 'Application records',
        'categories' => 'Application categories',
        'routes' => 'Bus routes',
        'notifications' => 'User notifications',
        'payments' => 'Payment records',
        'admin_users' => 'Admin accounts',
        'settings' => 'System settings'
    ];
    
    echo "<div class='status-grid'>";
    foreach ($requiredTables as $table => $description) {
        echo "<div class='metric'>";
        echo "<h4>$table</h4>";
        
        $result = $con->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $countResult = $con->query("SELECT COUNT(*) as count FROM $table");
            $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
            echo "<div class='value success'>✅ $count records</div>";
            echo "<small>$description</small>";
        } else {
            echo "<div class='value error'>❌ Missing</div>";
            echo "<small>$description</small>";
        }
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div class='error'>❌ Database connection failed</div>";
}
echo "</div>";

// Application Statistics
if ($con) {
    echo "<div class='status-card'>";
    echo "<h2>📈 Application Statistics</h2>";
    
    $stats = [];
    
    // Total applications
    $result = $con->query("SELECT COUNT(*) as count FROM bus_pass_applications");
    $stats['total_applications'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Applications by status
    $result = $con->query("SELECT status, COUNT(*) as count FROM bus_pass_applications GROUP BY status");
    $statusCounts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $statusCounts[$row['status']] = $row['count'];
        }
    }
    
    // Payment statistics
    $result = $con->query("SELECT payment_status, COUNT(*) as count FROM bus_pass_applications GROUP BY payment_status");
    $paymentCounts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $paymentCounts[$row['payment_status']] = $row['count'];
        }
    }
    
    echo "<div class='status-grid'>";
    echo "<div class='metric'>";
    echo "<h4>Total Applications</h4>";
    echo "<div class='value info'>{$stats['total_applications']}</div>";
    echo "</div>";
    
    echo "<div class='metric'>";
    echo "<h4>By Status</h4>";
    foreach ($statusCounts as $status => $count) {
        $class = $status === 'Approved' ? 'success' : ($status === 'Rejected' ? 'error' : 'warning');
        echo "<div class='$class'>$status: $count</div>";
    }
    echo "</div>";
    
    echo "<div class='metric'>";
    echo "<h4>Payment Status</h4>";
    foreach ($paymentCounts as $status => $count) {
        $class = $status === 'Paid' ? 'success' : ($status === 'Failed' ? 'error' : 'warning');
        echo "<div class='$class'>$status: $count</div>";
    }
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
}

// System Configuration Check
echo "<div class='status-card'>";
echo "<h2>⚙️ Configuration Status</h2>";

$configChecks = [
    'Database Config' => file_exists('includes/dbconnection.php'),
    'Email Config' => file_exists('includes/email_config.php'),
    'Main Config' => file_exists('includes/config.php'),
    'Upload Directory' => is_dir('uploads') && is_writable('uploads'),
    'Logs Directory' => is_dir('logs') && is_writable('logs'),
    'PHP Sessions' => session_status() !== PHP_SESSION_DISABLED
];

echo "<table>";
echo "<tr><th>Component</th><th>Status</th></tr>";
foreach ($configChecks as $component => $status) {
    $statusText = $status ? "<span class='success'>✅ OK</span>" : "<span class='error'>❌ Issue</span>";
    echo "<tr><td>$component</td><td>$statusText</td></tr>";
}
echo "</table>";
echo "</div>";

// Recent Errors
echo "<div class='status-card'>";
echo "<h2>🚨 Recent Errors</h2>";

if (file_exists('logs/error.log')) {
    $errorLog = file_get_contents('logs/error.log');
    $lines = array_filter(explode("\n", $errorLog));
    $recentErrors = array_slice($lines, -5); // Last 5 errors
    
    if (!empty($recentErrors)) {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        foreach ($recentErrors as $error) {
            echo "<div style='margin: 5px 0; color: #dc3545;'>" . htmlspecialchars($error) . "</div>";
        }
        echo "</div>";
    } else {
        echo "<div class='success'>✅ No recent errors found</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Error log file not found</div>";
}
echo "</div>";

// Quick Actions
echo "<div class='status-card'>";
echo "<h2>🚀 Quick Actions</h2>";
echo "<div>";
echo "<a href='fix-all-errors.php' class='btn'>🔧 Run Complete Fix</a>";
echo "<a href='verify-database.php' class='btn'>🔍 Database Verification</a>";
echo "<a href='index.php' class='btn'>🏠 Homepage</a>";
echo "<a href='admin-login.php' class='btn'>👨‍💼 Admin Login</a>";
echo "<a href='register.php' class='btn'>📝 User Registration</a>";
echo "</div>";
echo "</div>";

// System Information
echo "<div class='status-card'>";
echo "<h2>💻 System Information</h2>";
echo "<table>";
echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
echo "<tr><td>Document Root</td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
echo "<tr><td>Current Directory</td><td>" . __DIR__ . "</td></tr>";
echo "<tr><td>Upload Max Size</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
echo "<tr><td>Post Max Size</td><td>" . ini_get('post_max_size') . "</td></tr>";
echo "<tr><td>Memory Limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0; color: #6c757d;'>";
echo "<p>Last checked: " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='?' style='color: #007bff;'>🔄 Refresh Status</a></p>";
echo "</div>";

echo "</body></html>";
?>
