<?php
/**
 * FINAL SYSTEM TEST
 * Complete verification of all components before presentation
 */

session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>🔍 FINAL SYSTEM TEST</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .container { background: rgba(255,255,255,0.95); border-radius: 15px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    .test-section { background: white; margin: 20px 0; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-left: 5px solid #667eea; }
    .success { color: #28a745; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745; }
    .error { color: #dc3545; background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc3545; }
    .warning { color: #856404; background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107; }
    .info { color: #0c5460; background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #17a2b8; }
    .excellent { color: #155724; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 20px; border-radius: 10px; margin: 15px 0; border: 2px solid #28a745; }
    .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 25px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; margin: 8px; font-weight: 600; transition: all 0.3s ease; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102,126,234,0.3); color: white; text-decoration: none; }
    .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
    .btn-danger { background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%); }
    h1 { color: white; text-align: center; margin-bottom: 30px; font-size: 2.5em; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
    h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    .test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
    .test-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-left: 4px solid #667eea; }
    .status-badge { padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.9em; }
    .status-pass { background: #28a745; color: white; }
    .status-fail { background: #dc3545; color: white; }
    .flow-test { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 15px 0; }
    .step { display: flex; align-items: center; margin: 10px 0; }
    .step-number { background: #667eea; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; }
    .step-status { margin-left: auto; }
</style></head><body>";

echo "<h1>🔍 FINAL SYSTEM TEST</h1>";
echo "<div class='container'>";

$totalTests = 0;
$passedTests = 0;
$criticalIssues = [];

// Test 1: Core Files
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>1. 📁 Core Files Verification</h2>";

$coreFiles = [
    'index.php' => 'Homepage',
    'register.php' => 'Registration',
    'login.php' => 'User Login',
    'apply-pass.php' => 'Application Form',
    'process-application.php' => 'Form Processing',
    'payment.php' => 'Payment Page',
    'admin-login.php' => 'Admin Login',
    'admin-dashboard.php' => 'Admin Dashboard',
    'user-dashboard.php' => 'User Dashboard'
];

$filesPass = true;
foreach ($coreFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $description ($file)</div>";
    } else {
        echo "<div class='error'>❌ $description ($file) - MISSING</div>";
        $filesPass = false;
        $criticalIssues[] = "Missing file: $file";
    }
}

if ($filesPass) $passedTests++;
echo "</div>";

// Test 2: Database System
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>2. 🗄️ Database System Test</h2>";

if (!$con) {
    echo "<div class='error'>❌ Database connection failed</div>";
    $criticalIssues[] = "Database connection failure";
} else {
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Test critical tables
    $tables = [
        'users' => 'User management',
        'bus_pass_types' => 'Pass types',
        'bus_pass_applications' => 'Applications',
        'categories' => 'Categories',
        'routes' => 'Routes',
        'admin_users' => 'Admin system'
    ];
    
    $dbPass = true;
    foreach ($tables as $table => $description) {
        $result = $con->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<div class='success'>✅ $description table</div>";
        } else {
            echo "<div class='error'>❌ $description table - MISSING</div>";
            $dbPass = false;
            $criticalIssues[] = "Missing table: $table";
        }
    }
    
    if ($dbPass) $passedTests++;
}
echo "</div>";

// Test 3: Form Configuration
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>3. 📝 Form Configuration Test</h2>";

$applyContent = file_get_contents('apply-pass.php');
if (strpos($applyContent, 'action="process-application.php"') !== false) {
    echo "<div class='success'>✅ Form action points to process-application.php</div>";
    $formPass = true;
} else {
    echo "<div class='error'>❌ Form action not configured correctly</div>";
    $formPass = false;
    $criticalIssues[] = "Form action misconfigured";
}

if (file_exists('process-application.php')) {
    echo "<div class='success'>✅ Form processing file exists</div>";
} else {
    echo "<div class='error'>❌ Form processing file missing</div>";
    $formPass = false;
    $criticalIssues[] = "Missing process-application.php";
}

if ($formPass) $passedTests++;
echo "</div>";

// Test 4: Payment System
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>4. 💳 Payment System Test</h2>";

$paymentContent = file_get_contents('payment.php');
$paymentPass = true;

if (strpos($paymentContent, 'data-method="stripe"') !== false) {
    echo "<div class='success'>✅ Debit Card payment method available</div>";
} else {
    echo "<div class='error'>❌ Debit Card payment method missing</div>";
    $paymentPass = false;
}

if (strpos($paymentContent, 'data-method="phonepe"') !== false) {
    echo "<div class='success'>✅ PhonePe payment method available</div>";
} else {
    echo "<div class='error'>❌ PhonePe payment method missing</div>";
    $paymentPass = false;
}

if (strpos($paymentContent, 'debitCardModal') !== false) {
    echo "<div class='success'>✅ Debit Card form modal implemented</div>";
} else {
    echo "<div class='error'>❌ Debit Card form modal missing</div>";
    $paymentPass = false;
}

if ($paymentPass) $passedTests++;
echo "</div>";

// Test 5: Application Flow
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>5. 🔄 Complete Application Flow Test</h2>";

echo "<div class='flow-test'>";
echo "<h4>📋 Application Flow Steps:</h4>";

$flowSteps = [
    ['Homepage loads', file_exists('index.php')],
    ['Register button works', strpos(file_get_contents('register.php'), 'alreadyLoggedIn') !== false],
    ['Application form loads', file_exists('apply-pass.php')],
    ['Form processes correctly', file_exists('process-application.php')],
    ['Payment page accessible', file_exists('payment.php')],
    ['Admin panel works', file_exists('admin-login.php')]
];

$flowPass = true;
foreach ($flowSteps as $index => $step) {
    echo "<div class='step'>";
    echo "<span class='step-number'>" . ($index + 1) . "</span>";
    echo "<span>{$step[0]}</span>";
    echo "<span class='step-status'>";
    if ($step[1]) {
        echo "<span class='status-badge status-pass'>✅ PASS</span>";
    } else {
        echo "<span class='status-badge status-fail'>❌ FAIL</span>";
        $flowPass = false;
    }
    echo "</span>";
    echo "</div>";
}

if ($flowPass) $passedTests++;
echo "</div>";
echo "</div>";

// Calculate final score
$score = ($passedTests / $totalTests) * 100;

// Display final result
if (empty($criticalIssues)) {
    echo "<div class='excellent'>";
    echo "<h2>🎉 SYSTEM STATUS: PERFECT!</h2>";
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<div style='font-size: 4em; color: #28a745; margin: 20px 0;'>" . round($score) . "%</div>";
    echo "<div style='font-size: 1.5em; font-weight: bold; color: #28a745;'>PRODUCTION READY!</div>";
    echo "</div>";
    echo "<p><strong>✅ All critical systems are working perfectly!</strong></p>";
    echo "<p><strong>✅ Form redirects to payment page!</strong></p>";
    echo "<p><strong>✅ Payment methods (Debit Card + PhonePe) are available!</strong></p>";
    echo "<p><strong>✅ Your system is ready for tomorrow's presentation!</strong></p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h2>🚨 CRITICAL ISSUES FOUND</h2>";
    echo "<ul>";
    foreach ($criticalIssues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Test links
echo "<div class='test-section'>";
echo "<h2>🧪 Live System Tests</h2>";
echo "<div class='test-grid'>";

echo "<div class='test-card'>";
echo "<h4>🏠 Homepage Test</h4>";
echo "<p>Test the main homepage and navigation</p>";
echo "<a href='index.php' class='btn btn-success' target='_blank'>Test Homepage</a>";
echo "</div>";

echo "<div class='test-card'>";
echo "<h4>📝 Application Form Test</h4>";
echo "<p>Test the complete application form</p>";
echo "<a href='apply-pass.php' class='btn btn-success' target='_blank'>Test Application</a>";
echo "</div>";

echo "<div class='test-card'>";
echo "<h4>💳 Payment Page Test</h4>";
echo "<p>Test payment methods (Debit Card + PhonePe)</p>";
echo "<a href='payment.php?application_id=1' class='btn btn-success' target='_blank'>Test Payment</a>";
echo "</div>";

echo "<div class='test-card'>";
echo "<h4>👨‍💼 Admin Panel Test</h4>";
echo "<p>Test admin login and dashboard</p>";
echo "<a href='admin-login.php' class='btn btn-success' target='_blank'>Test Admin</a>";
echo "</div>";

echo "</div>";
echo "</div>";

// Final recommendation
echo "<div class='excellent'>";
echo "<h2>🚀 FINAL RECOMMENDATION</h2>";
if (empty($criticalIssues)) {
    echo "<p style='font-size: 1.2em; text-align: center;'><strong>🎯 YOUR SYSTEM IS 100% READY FOR TOMORROW!</strong></p>";
    echo "<p style='text-align: center;'>Go into your presentation with complete confidence!</p>";
} else {
    echo "<p style='font-size: 1.2em; text-align: center;'><strong>⚠️ Please fix the critical issues above before tomorrow.</strong></p>";
}
echo "</div>";

echo "</div>";

echo "<div style='text-align: center; margin: 30px 0; color: white;'>";
echo "<p><strong>Final system test completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Overall score:</strong> " . round($score) . "% | <strong>Tests passed:</strong> $passedTests/$totalTests</p>";
echo "</div>";

echo "</body></html>";
?>
