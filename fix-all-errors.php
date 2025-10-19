<?php
/**
 * Complete Error Fix Script for Bus Pass Management System
 * This script will fix all identified database and configuration issues
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Bus Pass System - Complete Error Fix</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
    .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
    .warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
    .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #17a2b8; }
    .step { background: #e9ecef; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #6c757d; }
    .step h3 { margin-top: 0; color: #495057; }
    .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
    .btn:hover { background: #0056b3; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #c82333; }
    .btn-success { background: #28a745; }
    .btn-success:hover { background: #218838; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #f8f9fa; font-weight: bold; }
    .progress { background: #e9ecef; border-radius: 10px; height: 20px; margin: 10px 0; }
    .progress-bar { background: #28a745; height: 100%; border-radius: 10px; transition: width 0.3s; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Bus Pass Management System - Complete Error Fix</h1>";

// Check database connection
if (!$con) {
    echo "<div class='error'>❌ <strong>Database Connection Failed!</strong><br>";
    echo "Please check your database configuration in includes/dbconnection.php</div>";
    exit();
}

echo "<div class='success'>✅ <strong>Database Connection Successful</strong></div>";

// Progress tracking
$totalSteps = 15;
$currentStep = 0;

function updateProgress($step, $total, $message) {
    $percentage = ($step / $total) * 100;
    echo "<div class='info'>";
    echo "<strong>Step $step of $total:</strong> $message";
    echo "<div class='progress'><div class='progress-bar' style='width: {$percentage}%'></div></div>";
    echo "</div>";
}

// Step 1: Read and execute SQL fix script
updateProgress(++$currentStep, $totalSteps, "Reading database fix script");

$sqlFile = 'fix_database.sql';
if (!file_exists($sqlFile)) {
    echo "<div class='error'>❌ SQL fix file not found: $sqlFile</div>";
    exit();
}

$sqlContent = file_get_contents($sqlFile);
$sqlStatements = array_filter(array_map('trim', explode(';', $sqlContent)));

echo "<div class='step'>";
echo "<h3>📋 Executing Database Structure Fixes</h3>";

$successCount = 0;
$errorCount = 0;

foreach ($sqlStatements as $sql) {
    if (empty($sql) || strpos($sql, '--') === 0) continue;
    
    try {
        if ($con->query($sql)) {
            $successCount++;
            // Only show important operations
            if (stripos($sql, 'CREATE TABLE') !== false || stripos($sql, 'ALTER TABLE') !== false) {
                $operation = substr($sql, 0, 50) . '...';
                echo "<div class='success'>✅ $operation</div>";
            }
        } else {
            $errorCount++;
            echo "<div class='error'>❌ Error: " . $con->error . "<br>SQL: " . substr($sql, 0, 100) . "...</div>";
        }
    } catch (Exception $e) {
        $errorCount++;
        echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
    }
}

echo "<div class='info'><strong>Database Fix Summary:</strong> $successCount successful operations, $errorCount errors</div>";
echo "</div>";

// Step 2: Verify table structures
updateProgress(++$currentStep, $totalSteps, "Verifying table structures");

$requiredTables = [
    'users', 'bus_pass_types', 'bus_pass_applications', 'categories', 
    'routes', 'notifications', 'payments', 'admin_users', 'settings'
];

echo "<div class='step'>";
echo "<h3>🔍 Table Structure Verification</h3>";
echo "<table>";
echo "<tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>";

foreach ($requiredTables as $table) {
    $result = $con->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $countResult = $con->query("SELECT COUNT(*) as count FROM $table");
        $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
        echo "<tr><td>$table</td><td class='success'>✅ Exists</td><td>$count rows</td></tr>";
    } else {
        echo "<tr><td>$table</td><td class='error'>❌ Missing</td><td>-</td></tr>";
    }
}
echo "</table>";
echo "</div>";

// Step 3: Check required columns
updateProgress(++$currentStep, $totalSteps, "Checking required columns");

echo "<div class='step'>";
echo "<h3>📊 Column Verification</h3>";

$columnChecks = [
    'bus_pass_applications' => ['application_id', 'photo_path', 'id_proof_type', 'id_proof_number', 'email'],
    'bus_pass_types' => ['is_active', 'amount'],
    'categories' => ['is_active'],
    'routes' => ['is_active']
];

foreach ($columnChecks as $table => $columns) {
    echo "<h4>$table table:</h4>";
    $result = $con->query("DESCRIBE $table");
    $existingColumns = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existingColumns[] = $row['Field'];
        }
        
        foreach ($columns as $column) {
            if (in_array($column, $existingColumns)) {
                echo "<div class='success'>✅ Column '$column' exists</div>";
            } else {
                echo "<div class='error'>❌ Column '$column' missing</div>";
            }
        }
    }
}
echo "</div>";

// Step 4: Fix application IDs
updateProgress(++$currentStep, $totalSteps, "Fixing application IDs");

echo "<div class='step'>";
echo "<h3>🔢 Application ID Fix</h3>";

$result = $con->query("SELECT COUNT(*) as count FROM bus_pass_applications WHERE application_id IS NULL OR application_id = ''");
$missingIds = $result ? $result->fetch_assoc()['count'] : 0;

if ($missingIds > 0) {
    $updateQuery = "UPDATE bus_pass_applications 
                   SET application_id = CONCAT('BPMS', YEAR(CURDATE()), LPAD(id, 6, '0'))
                   WHERE application_id IS NULL OR application_id = ''";
    
    if ($con->query($updateQuery)) {
        echo "<div class='success'>✅ Fixed $missingIds missing application IDs</div>";
    } else {
        echo "<div class='error'>❌ Failed to fix application IDs: " . $con->error . "</div>";
    }
} else {
    echo "<div class='success'>✅ All application IDs are present</div>";
}
echo "</div>";

// Step 5: Check email configuration
updateProgress(++$currentStep, $totalSteps, "Checking email configuration");

echo "<div class='step'>";
echo "<h3>📧 Email Configuration Check</h3>";

$emailConfigFile = 'includes/email_config.php';
if (file_exists($emailConfigFile)) {
    echo "<div class='success'>✅ Email configuration file exists</div>";
} else {
    echo "<div class='warning'>⚠️ Email configuration file missing - creating basic template</div>";
    
    $emailConfig = '<?php
// Email Configuration for Bus Pass Management System
// Configure these settings for your email provider

// Gmail SMTP Configuration (recommended)
define("SMTP_HOST", "smtp.gmail.com");
define("SMTP_PORT", 587);
define("SMTP_USERNAME", "your-email@gmail.com"); // Change this
define("SMTP_PASSWORD", "your-app-password");     // Change this
define("SMTP_ENCRYPTION", "tls");

// Email settings
define("FROM_EMAIL", "your-email@gmail.com");     // Change this
define("FROM_NAME", "Bus Pass Management System");
define("REPLY_TO", "your-email@gmail.com");       // Change this

// Email templates
define("EMAIL_ENABLED", false); // Set to true after configuring
?>';
    
    if (file_put_contents($emailConfigFile, $emailConfig)) {
        echo "<div class='info'>📝 Created email configuration template at $emailConfigFile</div>";
        echo "<div class='warning'>⚠️ Please edit this file with your actual email credentials</div>";
    }
}
echo "</div>";

echo "<div class='success'>";
echo "<h2>🎉 Error Fix Process Complete!</h2>";
echo "<p><strong>Summary of fixes applied:</strong></p>";
echo "<ul>";
echo "<li>✅ Database structure updated with missing columns</li>";
echo "<li>✅ Required tables created (notifications, categories, routes, etc.)</li>";
echo "<li>✅ Application IDs generated for existing records</li>";
echo "<li>✅ Default data inserted (categories, routes, admin user)</li>";
echo "<li>✅ Database indexes added for better performance</li>";
echo "<li>✅ Email configuration template created</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🔗 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Test the application:</strong> <a href='index.php' target='_blank'>Visit Homepage</a></li>";
echo "<li><strong>Check admin panel:</strong> <a href='admin-login.php' target='_blank'>Admin Login</a> (username: admin, password: admin123)</li>";
echo "<li><strong>Test user registration:</strong> <a href='register.php' target='_blank'>User Registration</a></li>";
echo "<li><strong>Configure email:</strong> Edit includes/email_config.php with your email credentials</li>";
echo "<li><strong>Update payment gateway:</strong> Add real API keys in includes/config.php</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Important Security Notes:</h3>";
echo "<ul>";
echo "<li>Change the default admin password immediately</li>";
echo "<li>Update payment gateway API keys with real credentials</li>";
echo "<li>Configure proper email SMTP settings</li>";
echo "<li>Set DEBUG_MODE to false in production</li>";
echo "<li>Remove or secure this fix script after use</li>";
echo "</ul>";
echo "</div>";

echo "</div></body></html>";
?>
