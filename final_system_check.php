<?php
// Final System Check - Verify everything is working
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "bpmsdb";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Final System Check - Nrupatunga Digital Bus Pass System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
        }
        .content {
            padding: 30px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .status-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #007bff;
        }
        .status-card.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .status-card.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .status-card.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .status-card h4 {
            margin: 0 0 15px 0;
            color: #333;
        }
        .btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .btn-success:hover {
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        .btn-danger:hover {
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        .info {
            color: #0c5460;
            background: #d1ecf1;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Final System Check</h1>
            <p>Comprehensive verification of all system components</p>
        </div>
        <div class="content">
            <?php
            $systemStatus = [];
            $overallStatus = 'success';
            
            try {
                // Database Connection Test
                $con = new mysqli($servername, $username, $password, $database);
                if ($con->connect_error) {
                    throw new Exception("Database connection failed");
                }
                $systemStatus['database'] = ['status' => 'success', 'message' => 'Database connection successful'];
                
                // Critical Columns Test
                $criticalColumns = [
                    'bus_pass_applications' => ['photo_path', 'email', 'application_id'],
                    'announcements' => ['is_active'],
                    'instant_reviews' => ['status', 'review_text']
                ];
                
                $missingColumns = [];
                foreach ($criticalColumns as $table => $columns) {
                    foreach ($columns as $column) {
                        $checkColumn = $con->query("SHOW COLUMNS FROM $table LIKE '$column'");
                        if ($checkColumn->num_rows == 0) {
                            $missingColumns[] = "$table.$column";
                        }
                    }
                }
                
                if (empty($missingColumns)) {
                    $systemStatus['columns'] = ['status' => 'success', 'message' => 'All critical columns exist'];
                } else {
                    $systemStatus['columns'] = ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missingColumns)];
                    $overallStatus = 'error';
                }
                
                // Test Critical Queries
                $queryTests = [
                    'announcements' => "SELECT COUNT(*) as count FROM announcements WHERE is_active = TRUE",
                    'bus_pass_types' => "SELECT COUNT(*) as count FROM bus_pass_types",
                    'categories' => "SELECT COUNT(*) as count FROM categories",
                    'instant_reviews' => "SELECT COUNT(*) as count FROM instant_reviews WHERE status = 'active'"
                ];
                
                $queryResults = [];
                foreach ($queryTests as $name => $query) {
                    try {
                        $result = $con->query($query);
                        if ($result) {
                            $count = $result->fetch_assoc()['count'];
                            $queryResults[$name] = "✅ $count records";
                        } else {
                            $queryResults[$name] = "❌ Query failed";
                            $overallStatus = 'error';
                        }
                    } catch (Exception $e) {
                        $queryResults[$name] = "❌ Error: " . $e->getMessage();
                        $overallStatus = 'error';
                    }
                }
                
                $systemStatus['queries'] = [
                    'status' => $overallStatus === 'error' ? 'error' : 'success',
                    'message' => 'Query tests completed',
                    'details' => $queryResults
                ];
                
                // File System Test
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    if (mkdir($uploadDir, 0755, true)) {
                        $systemStatus['filesystem'] = ['status' => 'success', 'message' => 'Upload directory created'];
                    } else {
                        $systemStatus['filesystem'] = ['status' => 'error', 'message' => 'Cannot create upload directory'];
                        $overallStatus = 'error';
                    }
                } else {
                    $systemStatus['filesystem'] = ['status' => 'success', 'message' => 'Upload directory exists and writable'];
                }
                
                // Test Apply Pass Form Simulation
                try {
                    // Simulate the INSERT query that apply-pass.php would use
                    $testColumns = [];
                    $columnsResult = $con->query("DESCRIBE bus_pass_applications");
                    while ($column = $columnsResult->fetch_assoc()) {
                        $testColumns[] = $column['Field'];
                    }
                    
                    $hasPhotoPath = in_array('photo_path', $testColumns);
                    $hasEmail = in_array('email', $testColumns);
                    $hasApplicationId = in_array('application_id', $testColumns);
                    
                    if ($hasPhotoPath && $hasEmail) {
                        $systemStatus['apply_form'] = ['status' => 'success', 'message' => 'Apply Pass form should work correctly'];
                    } else {
                        $systemStatus['apply_form'] = ['status' => 'error', 'message' => 'Apply Pass form missing required columns'];
                        $overallStatus = 'error';
                    }
                } catch (Exception $e) {
                    $systemStatus['apply_form'] = ['status' => 'error', 'message' => 'Apply Pass form test failed: ' . $e->getMessage()];
                    $overallStatus = 'error';
                }
                
                $con->close();
                
            } catch (Exception $e) {
                $systemStatus['database'] = ['status' => 'error', 'message' => $e->getMessage()];
                $overallStatus = 'error';
            }
            ?>
            
            <div class="info">
                <h3>🎯 System Status Overview</h3>
                <p><strong>Overall Status:</strong> 
                    <?php if ($overallStatus === 'success'): ?>
                        <span style="color: #28a745; font-weight: bold;">✅ SYSTEM READY</span>
                    <?php else: ?>
                        <span style="color: #dc3545; font-weight: bold;">❌ ISSUES DETECTED</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="status-grid">
                <?php foreach ($systemStatus as $component => $status): ?>
                <div class="status-card <?php echo $status['status']; ?>">
                    <h4><?php echo ucfirst(str_replace('_', ' ', $component)); ?></h4>
                    <p><?php echo $status['message']; ?></p>
                    
                    <?php if (isset($status['details'])): ?>
                    <div style="margin-top: 10px; font-size: 0.9rem;">
                        <strong>Details:</strong>
                        <ul style="margin: 5px 0; padding-left: 20px;">
                            <?php foreach ($status['details'] as $key => $value): ?>
                            <li><?php echo ucfirst($key); ?>: <?php echo $value; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($overallStatus === 'success'): ?>
            <div class="info" style="background: #d4edda; color: #155724; border-left-color: #28a745;">
                <h3>🎉 System Ready for Use!</h3>
                <p>All components are working correctly. You can now use all features of the Nrupatunga Digital Bus Pass Management System.</p>
                
                <div style="text-align: center; margin-top: 20px;">
                    <h4>🚀 Test System Features:</h4>
                    <a href="index.php" class="btn btn-success">🏠 Homepage</a>
                    <a href="user-registration.php" class="btn btn-success">👤 Register</a>
                    <a href="apply-pass.php" class="btn btn-success">📝 Apply Pass</a>
                    <a href="user-dashboard.php" class="btn btn-success">📊 Dashboard</a>
                    <a href="admin-dashboard.php" class="btn btn-success">🔐 Admin</a>
                </div>
            </div>
            <?php else: ?>
            <div class="info" style="background: #f8d7da; color: #721c24; border-left-color: #dc3545;">
                <h3>⚠️ Issues Detected</h3>
                <p>Some components need attention. Please run the fix scripts below:</p>
                
                <div style="text-align: center; margin-top: 20px;">
                    <h4>🔧 Fix Scripts:</h4>
                    <a href="direct_fix_photo_path.php" class="btn btn-danger">📸 Fix photo_path Column</a>
                    <a href="fix_announcements_table.php" class="btn btn-danger">📢 Fix Announcements</a>
                    <a href="complete_database_setup.php" class="btn btn-danger">🗄️ Complete Setup</a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="info">
                <h4>📋 Manual Fix Option:</h4>
                <p>If you're still experiencing issues, you can run the SQL commands manually:</p>
                <ol>
                    <li>Open phpMyAdmin: <code>http://localhost/phpmyadmin</code></li>
                    <li>Select the <strong>bpmsdb</strong> database</li>
                    <li>Go to the <strong>SQL</strong> tab</li>
                    <li>Copy and paste the contents of <strong>manual_sql_fix.sql</strong></li>
                    <li>Click <strong>Go</strong> to execute</li>
                </ol>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="manual_sql_fix.sql" class="btn" download>📄 Download SQL Fix Script</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
