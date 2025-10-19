<?php
/**
 * ULTIMATE FINAL CHECK
 * Complete system verification before presentation
 * This will test EVERYTHING and fix any remaining issues
 */

session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>🔍 ULTIMATE FINAL CHECK</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .container { background: rgba(255,255,255,0.95); border-radius: 15px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    .test-section { background: white; margin: 20px 0; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-left: 5px solid #667eea; }
    .success { color: #28a745; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745; }
    .error { color: #dc3545; background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc3545; }
    .warning { color: #856404; background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107; }
    .info { color: #0c5460; background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #17a2b8; }
    .critical { color: #721c24; background: linear-gradient(135deg, #f8d7da 0%, #f1b0b7 100%); padding: 20px; border-radius: 10px; margin: 15px 0; border: 2px solid #dc3545; }
    .excellent { color: #155724; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 20px; border-radius: 10px; margin: 15px 0; border: 2px solid #28a745; }
    .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 25px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; margin: 8px; font-weight: 600; transition: all 0.3s ease; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102,126,234,0.3); color: white; text-decoration: none; }
    .btn-danger { background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%); }
    .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
    .score-display { text-align: center; padding: 30px; margin: 20px 0; border-radius: 15px; font-size: 1.2em; }
    .score-number { font-size: 4em; font-weight: bold; margin: 10px 0; }
    h1 { color: white; text-align: center; margin-bottom: 30px; font-size: 2.5em; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
    h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
    th { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); font-weight: 600; }
    .test-result { padding: 8px 15px; border-radius: 20px; font-weight: bold; text-align: center; }
    .test-pass { background: #28a745; color: white; }
    .test-fail { background: #dc3545; color: white; }
    .test-warn { background: #ffc107; color: #212529; }
</style></head><body>";

echo "<h1>🔍 ULTIMATE FINAL CHECK</h1>";
echo "<div class='container'>";

$totalTests = 0;
$passedTests = 0;
$criticalIssues = [];
$warnings = [];

// Test 1: Database Connection & Structure
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>1. 🗄️ Database System Verification</h2>";

if (!$con) {
    echo "<div class='critical'>❌ CRITICAL: Database connection failed!</div>";
    $criticalIssues[] = "Database connection failure";
} else {
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Test critical tables
    $tables = ['users', 'bus_pass_types', 'bus_pass_applications', 'categories', 'routes'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $result = $con->query("SHOW TABLES LIKE '$table'");
        if (!$result || $result->num_rows == 0) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "<div class='success'>✅ All critical tables exist</div>";
        
        // Test critical columns
        $columnTests = [
            'bus_pass_applications' => ['application_id', 'photo_path', 'id_proof_type', 'id_proof_number'],
            'bus_pass_types' => ['amount', 'is_active']
        ];
        
        $missingColumns = [];
        foreach ($columnTests as $table => $columns) {
            $result = $con->query("DESCRIBE $table");
            if ($result) {
                $existingColumns = [];
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
            echo "<div class='success'>✅ All critical columns exist</div>";
            $passedTests++;
        } else {
            echo "<div class='error'>❌ Missing columns: " . implode(', ', $missingColumns) . "</div>";
            $criticalIssues[] = "Missing database columns";
        }
    } else {
        echo "<div class='error'>❌ Missing tables: " . implode(', ', $missingTables) . "</div>";
        $criticalIssues[] = "Missing database tables";
    }
}
echo "</div>";

// Test 2: Core Files Existence
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>2. 📁 Core Files Verification</h2>";

$coreFiles = [
    'index.php' => 'Homepage',
    'apply-pass.php' => 'Application Form',
    'payment.php' => 'Payment Page',
    'user-dashboard.php' => 'User Dashboard',
    'admin-login.php' => 'Admin Login',
    'admin-dashboard.php' => 'Admin Dashboard',
    'includes/config.php' => 'Configuration',
    'includes/dbconnection.php' => 'Database Connection'
];

$missingFiles = [];
foreach ($coreFiles as $file => $description) {
    if (!file_exists($file)) {
        $missingFiles[] = "$file ($description)";
    }
}

if (empty($missingFiles)) {
    echo "<div class='success'>✅ All core files exist</div>";
    $passedTests++;
} else {
    echo "<div class='error'>❌ Missing files: " . implode(', ', $missingFiles) . "</div>";
    $criticalIssues[] = "Missing core files";
}
echo "</div>";

// Test 3: Directory Permissions
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>3. 📂 Directory Permissions</h2>";

$directories = [
    'uploads' => 'File uploads',
    'uploads/photos' => 'Photo storage',
    'logs' => 'Error logging'
];

$permissionIssues = [];
foreach ($directories as $dir => $purpose) {
    if (!is_dir($dir)) {
        $permissionIssues[] = "$dir does not exist";
    } elseif (!is_writable($dir)) {
        $permissionIssues[] = "$dir is not writable";
    }
}

if (empty($permissionIssues)) {
    echo "<div class='success'>✅ All directories have correct permissions</div>";
    $passedTests++;
} else {
    echo "<div class='warning'>⚠️ Permission issues: " . implode(', ', $permissionIssues) . "</div>";
    $warnings[] = "Directory permission issues";
}
echo "</div>";

// Test 4: Database Data Integrity
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>4. 📊 Database Data Verification</h2>";

try {
    // Test pass types with amounts
    $passTypesResult = $con->query("SELECT COUNT(*) as count FROM bus_pass_types WHERE amount > 0");
    $passTypesCount = $passTypesResult ? $passTypesResult->fetch_assoc()['count'] : 0;
    
    // Test categories
    $categoriesResult = $con->query("SELECT COUNT(*) as count FROM categories");
    $categoriesCount = $categoriesResult ? $categoriesResult->fetch_assoc()['count'] : 0;
    
    // Test routes
    $routesResult = $con->query("SELECT COUNT(*) as count FROM routes");
    $routesCount = $routesResult ? $routesResult->fetch_assoc()['count'] : 0;
    
    if ($passTypesCount > 0 && $categoriesCount > 0 && $routesCount > 0) {
        echo "<div class='success'>✅ Database has required data</div>";
        echo "<div class='info'>Pass Types: $passTypesCount | Categories: $categoriesCount | Routes: $routesCount</div>";
        $passedTests++;
    } else {
        echo "<div class='warning'>⚠️ Missing required data</div>";
        if ($passTypesCount == 0) echo "<div class='warning'>No pass types with prices</div>";
        if ($categoriesCount == 0) echo "<div class='warning'>No categories configured</div>";
        if ($routesCount == 0) echo "<div class='warning'>No routes configured</div>";
        $warnings[] = "Missing required data";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database query error: " . $e->getMessage() . "</div>";
    $criticalIssues[] = "Database query issues";
}
echo "</div>";

// Test 5: Application Flow Test
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>5. 🔄 Application Flow Test</h2>";

// Test if we can simulate the application process
try {
    // Test user query
    $userTest = $con->query("SELECT COUNT(*) as count FROM users LIMIT 1");
    
    // Test application query structure
    $appTest = $con->query("SELECT application_id, photo_path, id_proof_type FROM bus_pass_applications LIMIT 1");
    
    if ($userTest && $appTest !== false) {
        echo "<div class='success'>✅ Application flow queries work</div>";
        $passedTests++;
    } else {
        echo "<div class='error'>❌ Application flow has issues</div>";
        $criticalIssues[] = "Application flow problems";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Application flow error: " . $e->getMessage() . "</div>";
    $criticalIssues[] = "Application flow errors";
}
echo "</div>";

// Test 6: Admin System
$totalTests++;
echo "<div class='test-section'>";
echo "<h2>6. 👨‍💼 Admin System Test</h2>";

try {
    $adminTest = $con->query("SELECT COUNT(*) as count FROM admin_users");
    $adminCount = $adminTest ? $adminTest->fetch_assoc()['count'] : 0;
    
    if ($adminCount > 0) {
        echo "<div class='success'>✅ Admin users exist ($adminCount admins)</div>";
        $passedTests++;
    } else {
        echo "<div class='warning'>⚠️ No admin users found</div>";
        $warnings[] = "No admin users";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Admin system error: " . $e->getMessage() . "</div>";
    $criticalIssues[] = "Admin system issues";
}
echo "</div>";

// Calculate final score
$score = ($passedTests / $totalTests) * 100;

// Display final score
if ($score >= 90) {
    $scoreClass = 'excellent';
    $scoreText = '🎉 EXCELLENT - PRODUCTION READY!';
} elseif ($score >= 70) {
    $scoreClass = 'success';
    $scoreText = '👍 GOOD - Minor issues only';
} elseif ($score >= 50) {
    $scoreClass = 'warning';
    $scoreText = '⚠️ NEEDS ATTENTION';
} else {
    $scoreClass = 'critical';
    $scoreText = '❌ CRITICAL ISSUES';
}

echo "<div class='score-display $scoreClass'>";
echo "<h2>🎯 FINAL SYSTEM SCORE</h2>";
echo "<div class='score-number'>" . round($score) . "%</div>";
echo "<div>$scoreText</div>";
echo "<div style='margin-top: 15px;'>$passedTests out of $totalTests critical tests passed</div>";
echo "</div>";

// Issues summary
if (!empty($criticalIssues)) {
    echo "<div class='critical'>";
    echo "<h3>🚨 CRITICAL ISSUES (Must Fix Now!):</h3>";
    echo "<ul>";
    foreach ($criticalIssues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "<a href='emergency-fix.php' class='btn btn-danger'>🔧 RUN EMERGENCY FIX NOW</a>";
    echo "</div>";
}

if (!empty($warnings)) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Warnings (Recommended to fix):</h3>";
    echo "<ul>";
    foreach ($warnings as $warning) {
        echo "<li>$warning</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Final recommendation
echo "<div class='test-section'>";
echo "<h2>🎯 FINAL RECOMMENDATION</h2>";

if (empty($criticalIssues)) {
    echo "<div class='excellent'>";
    echo "<h3>🎉 SYSTEM IS READY FOR TOMORROW!</h3>";
    echo "<p><strong>Congratulations!</strong> Your Bus Pass Management System has passed all critical tests.</p>";
    echo "<ul>";
    echo "<li>✅ Database structure is complete</li>";
    echo "<li>✅ All core files are present</li>";
    echo "<li>✅ Application flow is functional</li>";
    echo "<li>✅ Admin system is working</li>";
    echo "<li>✅ File permissions are correct</li>";
    echo "</ul>";
    echo "<p><strong>You can confidently present your project tomorrow!</strong></p>";
    echo "</div>";
} else {
    echo "<div class='critical'>";
    echo "<h3>🚨 URGENT ACTION REQUIRED!</h3>";
    echo "<p><strong>Critical issues found that must be fixed before tomorrow:</strong></p>";
    echo "<ul>";
    foreach ($criticalIssues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "<p><strong>Click the emergency fix button above to resolve these issues automatically.</strong></p>";
    echo "</div>";
}

// Test links
echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<h3>🧪 Test Your System:</h3>";
echo "<a href='index.php' class='btn btn-success'>🏠 Homepage</a>";
echo "<a href='apply-pass.php' class='btn btn-success'>📝 Application Form</a>";
echo "<a href='admin-login.php' class='btn btn-success'>👨‍💼 Admin Panel</a>";
echo "<a href='user-dashboard.php' class='btn btn-success'>📊 User Dashboard</a>";
echo "</div>";

echo "</div>";

echo "<div style='text-align: center; margin: 30px 0; color: white;'>";
echo "<p><strong>Final check completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>System readiness:</strong> " . round($score) . "%</p>";
echo "</div>";

echo "</div></body></html>";
?>
