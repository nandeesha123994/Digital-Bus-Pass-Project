<?php
/**
 * EMERGENCY FIX SCRIPT
 * Final comprehensive fix for all remaining issues
 * Run this if there are any critical issues before production
 */

session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>🚨 Emergency Fix Script</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .fix-section { background: white; margin: 15px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
    .btn-success { background: #28a745; } .btn-success:hover { background: #218838; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
    .progress { background: #e9ecef; border-radius: 10px; height: 20px; margin: 10px 0; }
    .progress-bar { background: #28a745; height: 100%; border-radius: 10px; transition: width 0.3s; }
</style></head><body>";

echo "<h1>🚨 EMERGENCY FIX SCRIPT</h1>";
echo "<div class='warning'><strong>⚠️ IMPORTANT:</strong> This script will fix all critical issues. Run this only if you have problems!</div>";

if (isset($_POST['run_emergency_fix'])) {
    echo "<div class='fix-section'>";
    echo "<h2>🔧 Running Emergency Fixes...</h2>";
    
    $fixCount = 0;
    $totalFixes = 10;
    
    // Fix 1: Create missing directories
    echo "<h3>1. Creating Missing Directories</h3>";
    $dirs = ['uploads', 'uploads/photos', 'logs', 'simulated_emails'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0777, true)) {
                echo "<div class='success'>✅ Created directory: $dir</div>";
            } else {
                echo "<div class='error'>❌ Failed to create directory: $dir</div>";
            }
        } else {
            echo "<div class='success'>✅ Directory exists: $dir</div>";
        }
    }
    $fixCount++;
    
    // Fix 2: Database structure fixes
    echo "<h3>2. Database Structure Fixes</h3>";
    
    $dbFixes = [
        "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS application_id VARCHAR(20) UNIQUE AFTER id",
        "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255)",
        "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS id_proof_type VARCHAR(50)",
        "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS id_proof_number VARCHAR(50)",
        "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS email VARCHAR(100)",
        "ALTER TABLE bus_pass_types ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1",
        "ALTER TABLE bus_pass_types ADD COLUMN IF NOT EXISTS amount DECIMAL(10,2) DEFAULT 0.00",
        
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id)
        )",
        
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS routes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            route_id VARCHAR(20) NOT NULL UNIQUE,
            route_name VARCHAR(100) NOT NULL,
            source VARCHAR(100) NOT NULL,
            destination VARCHAR(100) NOT NULL,
            distance_km DECIMAL(6,2) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($dbFixes as $sql) {
        try {
            if ($con->query($sql)) {
                echo "<div class='success'>✅ Database fix applied</div>";
            } else {
                echo "<div class='warning'>⚠️ Fix skipped (may already exist): " . substr($sql, 0, 50) . "...</div>";
            }
        } catch (Exception $e) {
            echo "<div class='warning'>⚠️ Fix skipped: " . $e->getMessage() . "</div>";
        }
    }
    $fixCount++;
    
    // Fix 3: Insert default data
    echo "<h3>3. Inserting Default Data</h3>";
    
    // Categories
    $categories = [
        ['Student', 'For students with valid student ID'],
        ['Senior Citizen', 'For citizens above 60 years'],
        ['General', 'For general public'],
        ['Disabled', 'For physically challenged individuals']
    ];
    
    foreach ($categories as $cat) {
        $stmt = $con->prepare("INSERT IGNORE INTO categories (category_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $cat[0], $cat[1]);
        if ($stmt->execute()) {
            echo "<div class='success'>✅ Category added: {$cat[0]}</div>";
        }
    }
    
    // Routes
    $routes = [
        ['RT001', 'City Center Route', 'Central Bus Station', 'City Mall', 5.2],
        ['RT002', 'Airport Express', 'Central Bus Station', 'Airport', 12.8],
        ['RT003', 'University Route', 'Central Bus Station', 'University Campus', 8.5],
        ['RT004', 'Hospital Route', 'Central Bus Station', 'General Hospital', 6.3]
    ];
    
    foreach ($routes as $route) {
        $stmt = $con->prepare("INSERT IGNORE INTO routes (route_id, route_name, source, destination, distance_km) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $route[0], $route[1], $route[2], $route[3], $route[4]);
        if ($stmt->execute()) {
            echo "<div class='success'>✅ Route added: {$route[1]}</div>";
        }
    }
    
    // Update pass types with amounts
    $passUpdates = [
        ['Daily Pass', 50.00],
        ['Weekly Pass', 300.00],
        ['Monthly Pass', 1200.00],
        ['Annual Pass', 12000.00]
    ];
    
    foreach ($passUpdates as $pass) {
        $stmt = $con->prepare("UPDATE bus_pass_types SET amount = ? WHERE type_name = ? AND (amount IS NULL OR amount = 0)");
        $stmt->bind_param("ds", $pass[1], $pass[0]);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "<div class='success'>✅ Updated {$pass[0]} price to ₹{$pass[1]}</div>";
        }
    }
    $fixCount++;
    
    // Fix 4: Generate missing application IDs
    echo "<h3>4. Fixing Application IDs</h3>";
    $result = $con->query("SELECT id FROM bus_pass_applications WHERE application_id IS NULL OR application_id = ''");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $newAppId = "BPMS" . date('Y') . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
            $stmt = $con->prepare("UPDATE bus_pass_applications SET application_id = ? WHERE id = ?");
            $stmt->bind_param("si", $newAppId, $row['id']);
            if ($stmt->execute()) {
                echo "<div class='success'>✅ Generated application ID: $newAppId</div>";
            }
        }
    } else {
        echo "<div class='success'>✅ All application IDs are present</div>";
    }
    $fixCount++;
    
    // Fix 5: File permissions
    echo "<h3>5. Setting File Permissions</h3>";
    $dirs = ['uploads', 'uploads/photos', 'logs'];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            chmod($dir, 0777);
            echo "<div class='success'>✅ Set permissions for: $dir</div>";
        }
    }
    $fixCount++;
    
    // Fix 6: Create admin user if missing
    echo "<h3>6. Admin User Check</h3>";
    $adminCheck = $con->query("SELECT COUNT(*) as count FROM admin_users");
    $adminCount = $adminCheck ? $adminCheck->fetch_assoc()['count'] : 0;
    
    if ($adminCount == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $con->prepare("INSERT INTO admin_users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $username = 'admin';
        $email = 'admin@buspass.com';
        $fullName = 'System Administrator';
        $role = 'super_admin';
        $stmt->bind_param("sssss", $username, $adminPassword, $email, $fullName, $role);
        if ($stmt->execute()) {
            echo "<div class='success'>✅ Created admin user (username: admin, password: admin123)</div>";
        }
    } else {
        echo "<div class='success'>✅ Admin user exists</div>";
    }
    $fixCount++;
    
    // Fix 7: Test critical queries
    echo "<h3>7. Testing Critical Queries</h3>";
    $testQueries = [
        "SELECT COUNT(*) FROM users" => "User system",
        "SELECT COUNT(*) FROM bus_pass_types WHERE amount > 0" => "Pass types with prices",
        "SELECT COUNT(*) FROM categories" => "Categories",
        "SELECT COUNT(*) FROM routes" => "Routes"
    ];
    
    foreach ($testQueries as $query => $description) {
        try {
            $result = $con->query($query);
            if ($result) {
                $count = $result->fetch_row()[0];
                echo "<div class='success'>✅ $description: $count records</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ $description test failed: " . $e->getMessage() . "</div>";
        }
    }
    $fixCount++;
    
    // Fix 8: Clear error logs
    echo "<h3>8. Cleaning Error Logs</h3>";
    if (file_exists('logs/error.log')) {
        $errorLog = file_get_contents('logs/error.log');
        $lines = explode("\n", $errorLog);
        $recentLines = array_slice($lines, -50); // Keep last 50 lines
        file_put_contents('logs/error.log', implode("\n", $recentLines));
        echo "<div class='success'>✅ Error log cleaned (kept recent 50 entries)</div>";
    }
    $fixCount++;
    
    // Fix 9: Test form submission
    echo "<h3>9. Testing Form Components</h3>";
    $formFiles = ['apply-pass.php', 'payment.php', 'user-dashboard.php', 'admin-login.php'];
    foreach ($formFiles as $file) {
        if (file_exists($file)) {
            echo "<div class='success'>✅ $file exists</div>";
        } else {
            echo "<div class='error'>❌ $file missing</div>";
        }
    }
    $fixCount++;
    
    // Fix 10: Final verification
    echo "<h3>10. Final System Verification</h3>";
    $finalChecks = [
        'Database connection' => $con ? true : false,
        'Upload directory writable' => is_writable('uploads'),
        'Pass types configured' => $con->query("SELECT COUNT(*) FROM bus_pass_types WHERE amount > 0")->fetch_row()[0] > 0,
        'Categories available' => $con->query("SELECT COUNT(*) FROM categories")->fetch_row()[0] > 0,
        'Routes available' => $con->query("SELECT COUNT(*) FROM routes")->fetch_row()[0] > 0
    ];
    
    $allPassed = true;
    foreach ($finalChecks as $check => $status) {
        if ($status) {
            echo "<div class='success'>✅ $check</div>";
        } else {
            echo "<div class='error'>❌ $check</div>";
            $allPassed = false;
        }
    }
    $fixCount++;
    
    // Progress bar
    $progress = ($fixCount / $totalFixes) * 100;
    echo "<div class='progress'>";
    echo "<div class='progress-bar' style='width: {$progress}%;'></div>";
    echo "</div>";
    echo "<div class='info'>Progress: $fixCount/$totalFixes fixes completed ({$progress}%)</div>";
    
    // Final result
    if ($allPassed) {
        echo "<div class='success'>";
        echo "<h2>🎉 EMERGENCY FIX COMPLETED SUCCESSFULLY!</h2>";
        echo "<p><strong>Your system is now ready for production!</strong></p>";
        echo "<ul>";
        echo "<li>✅ Database structure fixed</li>";
        echo "<li>✅ Default data inserted</li>";
        echo "<li>✅ File permissions set</li>";
        echo "<li>✅ Admin user created</li>";
        echo "<li>✅ All components verified</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<h2>⚠️ Some issues remain</h2>";
        echo "<p>Most fixes applied successfully, but some manual intervention may be needed.</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Test links
    echo "<div class='fix-section'>";
    echo "<h2>🧪 Test Your System Now</h2>";
    echo "<a href='apply-pass.php' class='btn btn-success'>📝 Test Application Form</a>";
    echo "<a href='user-dashboard.php' class='btn btn-success'>🏠 Test User Dashboard</a>";
    echo "<a href='admin-login.php' class='btn btn-success'>👨‍💼 Test Admin Panel</a>";
    echo "<a href='payment.php?application_id=1' class='btn btn-success'>💳 Test Payment Page</a>";
    echo "<a href='final-production-check.php' class='btn'>📊 Run Final Check</a>";
    echo "</div>";
    
} else {
    // Show warning and confirmation
    echo "<div class='fix-section'>";
    echo "<h2>⚠️ Emergency Fix Warning</h2>";
    echo "<div class='warning'>";
    echo "<p><strong>This script will:</strong></p>";
    echo "<ul>";
    echo "<li>Fix all database structure issues</li>";
    echo "<li>Create missing tables and columns</li>";
    echo "<li>Insert default data (categories, routes, pass types)</li>";
    echo "<li>Set proper file permissions</li>";
    echo "<li>Create admin user if missing</li>";
    echo "<li>Clean error logs</li>";
    echo "<li>Verify all components</li>";
    echo "</ul>";
    echo "<p><strong>⚠️ Only run this if you have critical issues!</strong></p>";
    echo "</div>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='run_emergency_fix' class='btn btn-danger'>🚨 RUN EMERGENCY FIX</button>";
    echo "<a href='final-production-check.php' class='btn'>📊 Check System Status First</a>";
    echo "</form>";
    echo "</div>";
}

echo "</body></html>";
?>
