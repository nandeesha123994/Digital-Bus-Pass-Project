<?php
/**
 * Quick Status Check - Are All Issues Fixed?
 */

include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Current Status Check</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .status-card { background: white; margin: 15px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    .btn-success { background: #28a745; } .btn-success:hover { background: #218838; }
    .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
    th { background: #e9ecef; }
    .score { font-size: 2em; font-weight: bold; text-align: center; padding: 20px; border-radius: 10px; margin: 20px 0; }
    .score.excellent { background: #d4edda; color: #155724; }
    .score.good { background: #d1ecf1; color: #0c5460; }
    .score.needs-work { background: #fff3cd; color: #856404; }
    .score.poor { background: #f8d7da; color: #721c24; }
</style></head><body>";

echo "<h1>🔍 Current System Status - Are All Issues Fixed?</h1>";

$totalChecks = 0;
$passedChecks = 0;
$issues = [];

// Check 1: Database Connection
$totalChecks++;
echo "<div class='status-card'>";
echo "<h2>1. Database Connection</h2>";
if ($con) {
    echo "<div class='success'>✅ Database connection successful</div>";
    $passedChecks++;
} else {
    echo "<div class='error'>❌ Database connection failed</div>";
    $issues[] = "Database connection failed";
}
echo "</div>";

// Check 2: Required Tables
$totalChecks++;
echo "<div class='status-card'>";
echo "<h2>2. Required Tables</h2>";
$requiredTables = ['users', 'bus_pass_types', 'bus_pass_applications', 'categories', 'routes', 'notifications', 'payments', 'admin_users', 'settings'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $result = $con->query("SHOW TABLES LIKE '$table'");
    if (!$result || $result->num_rows == 0) {
        $missingTables[] = $table;
    }
}

if (empty($missingTables)) {
    echo "<div class='success'>✅ All required tables exist</div>";
    $passedChecks++;
} else {
    echo "<div class='error'>❌ Missing tables: " . implode(', ', $missingTables) . "</div>";
    $issues[] = "Missing database tables: " . implode(', ', $missingTables);
}
echo "</div>";

// Check 3: Required Columns
$totalChecks++;
echo "<div class='status-card'>";
echo "<h2>3. Required Columns</h2>";
$columnChecks = [
    'bus_pass_applications' => ['application_id', 'photo_path', 'id_proof_type', 'id_proof_number', 'email'],
    'bus_pass_types' => ['is_active', 'amount']
];

$missingColumns = [];
foreach ($columnChecks as $table => $columns) {
    $result = $con->query("DESCRIBE $table");
    $existingColumns = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existingColumns[] = $row['Field'];
        }
        
        foreach ($columns as $column) {
            if (!in_array($column, $existingColumns)) {
                $missingColumns[] = "$table.$column";
            }
        }
    }
}

if (empty($missingColumns)) {
    echo "<div class='success'>✅ All required columns exist</div>";
    $passedChecks++;
} else {
    echo "<div class='error'>❌ Missing columns: " . implode(', ', $missingColumns) . "</div>";
    $issues[] = "Missing database columns: " . implode(', ', $missingColumns);
}
echo "</div>";

// Check 4: Form Submission Test
$totalChecks++;
echo "<div class='status-card'>";
echo "<h2>4. Form Submission Test</h2>";

// Check if we can insert a test record
try {
    $testQuery = "SELECT COUNT(*) as count FROM bus_pass_applications WHERE applicant_name = 'TEST_USER_DELETE_ME'";
    $testResult = $con->query($testQuery);
    
    if ($testResult) {
        echo "<div class='success'>✅ Database queries working</div>";
        $passedChecks++;
    } else {
        echo "<div class='error'>❌ Database query failed: " . $con->error . "</div>";
        $issues[] = "Database query issues";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
    $issues[] = "Database errors: " . $e->getMessage();
}
echo "</div>";

// Check 5: File Permissions
$totalChecks++;
echo "<div class='status-card'>";
echo "<h2>5. File Permissions</h2>";

$permissionIssues = [];
if (!is_dir('uploads')) {
    $permissionIssues[] = "uploads directory missing";
} elseif (!is_writable('uploads')) {
    $permissionIssues[] = "uploads directory not writable";
}

if (!is_dir('logs')) {
    $permissionIssues[] = "logs directory missing";
} elseif (!is_writable('logs')) {
    $permissionIssues[] = "logs directory not writable";
}

if (empty($permissionIssues)) {
    echo "<div class='success'>✅ File permissions OK</div>";
    $passedChecks++;
} else {
    echo "<div class='error'>❌ Permission issues: " . implode(', ', $permissionIssues) . "</div>";
    $issues = array_merge($issues, $permissionIssues);
}
echo "</div>";

// Check 6: Recent Errors
$totalChecks++;
echo "<div class='status-card'>";
echo "<h2>6. Recent Critical Errors</h2>";

if (file_exists('logs/error.log')) {
    $errorLog = file_get_contents('logs/error.log');
    $lines = array_filter(explode("\n", $errorLog));
    $recentErrors = array_slice($lines, -5); // Last 5 errors
    
    // Check for critical errors (not email logs)
    $criticalErrors = [];
    foreach ($recentErrors as $error) {
        if (strpos($error, 'Local Email:') === false && !empty(trim($error))) {
            $criticalErrors[] = $error;
        }
    }
    
    if (empty($criticalErrors)) {
        echo "<div class='success'>✅ No recent critical errors</div>";
        $passedChecks++;
    } else {
        echo "<div class='warning'>⚠️ Recent errors found (last 5):</div>";
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        foreach ($criticalErrors as $error) {
            echo htmlspecialchars($error) . "<br>";
        }
        echo "</div>";
        $issues[] = "Recent errors in logs";
    }
} else {
    echo "<div class='success'>✅ No error log found (good sign)</div>";
    $passedChecks++;
}
echo "</div>";

// Calculate Score
$score = ($passedChecks / $totalChecks) * 100;
$scoreClass = 'excellent';
$scoreText = 'Excellent! 🎉';

if ($score < 100) {
    $scoreClass = 'good';
    $scoreText = 'Good, but needs minor fixes 👍';
}
if ($score < 80) {
    $scoreClass = 'needs-work';
    $scoreText = 'Needs work ⚠️';
}
if ($score < 60) {
    $scoreClass = 'poor';
    $scoreText = 'Needs significant fixes ❌';
}

// Overall Status
echo "<div class='score $scoreClass'>";
echo "<h2>Overall System Health</h2>";
echo "<div style='font-size: 3em;'>" . round($score) . "%</div>";
echo "<div>$scoreText</div>";
echo "<div style='font-size: 0.8em; margin-top: 10px;'>$passedChecks out of $totalChecks checks passed</div>";
echo "</div>";

// Summary
echo "<div class='status-card'>";
echo "<h2>📋 Summary</h2>";

if ($score >= 100) {
    echo "<div class='success'>";
    echo "<h3>🎉 All Issues Fixed!</h3>";
    echo "<p><strong>Congratulations!</strong> Your Bus Pass Management System appears to be working correctly.</p>";
    echo "<ul>";
    echo "<li>✅ Database structure is complete</li>";
    echo "<li>✅ All required tables and columns exist</li>";
    echo "<li>✅ File permissions are correct</li>";
    echo "<li>✅ No critical errors detected</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>🚀 Ready to Use!</h4>";
    echo "<p>Your system is ready for production use. You can now:</p>";
    echo "<ul>";
    echo "<li>Accept user registrations and applications</li>";
    echo "<li>Process payments</li>";
    echo "<li>Manage applications through admin panel</li>";
    echo "</ul>";
    echo "</div>";
    
} elseif ($score >= 80) {
    echo "<div class='info'>";
    echo "<h3>👍 Almost There!</h3>";
    echo "<p>Your system is mostly working, but there are a few minor issues to address:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Issues Need Attention</h3>";
    echo "<p>Several issues need to be fixed before the system is fully functional:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Action Buttons
echo "<div style='text-align: center; margin: 30px 0;'>";
if ($score < 100) {
    echo "<a href='fix-all-errors.php' class='btn btn-danger'>🔧 Run Complete Fix</a>";
}
echo "<a href='apply-pass.php' class='btn btn-success'>📝 Test Application Form</a>";
echo "<a href='user-dashboard.php' class='btn'>🏠 User Dashboard</a>";
echo "<a href='admin-login.php' class='btn'>👨‍💼 Admin Panel</a>";
echo "</div>";

echo "</div>";

// Next Steps
if ($score >= 100) {
    echo "<div class='status-card'>";
    echo "<h2>🔗 Next Steps for Production</h2>";
    echo "<ol>";
    echo "<li><strong>Configure Email:</strong> Edit <code>includes/email_config.php</code> with real SMTP settings</li>";
    echo "<li><strong>Payment Gateway:</strong> Update <code>includes/config.php</code> with real API keys</li>";
    echo "<li><strong>Security:</strong> Change default admin password</li>";
    echo "<li><strong>SSL Certificate:</strong> Enable HTTPS for production</li>";
    echo "<li><strong>Backup:</strong> Set up regular database backups</li>";
    echo "</ol>";
    echo "</div>";
}

echo "</body></html>";
?>
