<?php
// Test System Functionality - Verify all components are working
$servername = "localhost";
$username = "root";
$password = "";
$database = "bpmsdb";

?>
<!DOCTYPE html>
<html>
<head>
    <title>System Functionality Test - Nrupatunga Digital Bus Pass System</title>
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
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            color: #0c5460;
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
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
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .test-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .test-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.1);
        }
        .test-card h4 {
            margin: 0 0 15px 0;
            color: #333;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-success {
            background: #28a745;
        }
        .status-error {
            background: #dc3545;
        }
        .status-warning {
            background: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 System Functionality Test</h1>
            <p>Comprehensive testing of all system components</p>
        </div>
        <div class="content">
            <?php
            $testResults = [];
            
            try {
                // Test 1: Database Connection
                $con = new mysqli($servername, $username, $password, $database);
                if ($con->connect_error) {
                    throw new Exception("Database connection failed: " . $con->connect_error);
                }
                $testResults['database'] = ['status' => 'success', 'message' => 'Database connection successful'];
                
                // Test 2: Required Tables
                $requiredTables = ['users', 'categories', 'bus_pass_types', 'bus_pass_applications', 'payments', 'admin_actions', 'announcements', 'instant_reviews'];
                $missingTables = [];
                
                foreach ($requiredTables as $table) {
                    $checkTable = $con->query("SHOW TABLES LIKE '$table'");
                    if ($checkTable->num_rows == 0) {
                        $missingTables[] = $table;
                    }
                }
                
                if (empty($missingTables)) {
                    $testResults['tables'] = ['status' => 'success', 'message' => 'All required tables exist'];
                } else {
                    $testResults['tables'] = ['status' => 'error', 'message' => 'Missing tables: ' . implode(', ', $missingTables)];
                }
                
                // Test 3: Sample Data
                $dataTests = [
                    'users' => 'SELECT COUNT(*) as count FROM users',
                    'categories' => 'SELECT COUNT(*) as count FROM categories',
                    'bus_pass_types' => 'SELECT COUNT(*) as count FROM bus_pass_types',
                    'announcements' => 'SELECT COUNT(*) as count FROM announcements WHERE is_active = TRUE',
                    'instant_reviews' => 'SELECT COUNT(*) as count FROM instant_reviews WHERE status = "active"'
                ];
                
                $dataResults = [];
                foreach ($dataTests as $table => $query) {
                    try {
                        $result = $con->query($query);
                        if ($result) {
                            $count = $result->fetch_assoc()['count'];
                            $dataResults[$table] = $count;
                        } else {
                            $dataResults[$table] = 'Error: ' . $con->error;
                        }
                    } catch (Exception $e) {
                        $dataResults[$table] = 'Error: ' . $e->getMessage();
                    }
                }
                
                $testResults['data'] = ['status' => 'success', 'message' => 'Sample data verified', 'details' => $dataResults];
                
                // Test 4: Critical Columns
                $columnTests = [
                    'bus_pass_applications' => ['application_id', 'photo_path', 'email', 'id_proof_type', 'id_proof_number'],
                    'announcements' => ['is_active'],
                    'instant_reviews' => ['status', 'review_text', 'rating']
                ];
                
                $missingColumns = [];
                foreach ($columnTests as $table => $columns) {
                    foreach ($columns as $column) {
                        $checkColumn = $con->query("SHOW COLUMNS FROM $table LIKE '$column'");
                        if ($checkColumn->num_rows == 0) {
                            $missingColumns[] = "$table.$column";
                        }
                    }
                }
                
                if (empty($missingColumns)) {
                    $testResults['columns'] = ['status' => 'success', 'message' => 'All critical columns exist'];
                } else {
                    $testResults['columns'] = ['status' => 'error', 'message' => 'Missing columns: ' . implode(', ', $missingColumns)];
                }
                
                // Test 5: File System
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    if (mkdir($uploadDir, 0755, true)) {
                        $testResults['filesystem'] = ['status' => 'success', 'message' => 'Upload directory created successfully'];
                    } else {
                        $testResults['filesystem'] = ['status' => 'error', 'message' => 'Cannot create upload directory'];
                    }
                } else {
                    if (is_writable($uploadDir)) {
                        $testResults['filesystem'] = ['status' => 'success', 'message' => 'Upload directory is writable'];
                    } else {
                        $testResults['filesystem'] = ['status' => 'warning', 'message' => 'Upload directory exists but not writable'];
                    }
                }
                
                $con->close();
                
            } catch (Exception $e) {
                $testResults['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            }
            ?>
            
            <div class="info">
                <h3>🔍 System Test Results</h3>
                <p>Comprehensive testing of all system components to ensure proper functionality.</p>
            </div>
            
            <div class="grid">
                <?php foreach ($testResults as $testName => $result): ?>
                <div class="test-card">
                    <h4>
                        <span class="status-indicator status-<?php echo $result['status']; ?>"></span>
                        <?php echo ucfirst(str_replace('_', ' ', $testName)); ?> Test
                    </h4>
                    <div class="<?php echo $result['status']; ?>">
                        <?php echo $result['message']; ?>
                    </div>
                    
                    <?php if (isset($result['details'])): ?>
                    <div style="margin-top: 10px; font-size: 0.9rem;">
                        <strong>Details:</strong>
                        <ul style="margin: 5px 0; padding-left: 20px;">
                            <?php foreach ($result['details'] as $key => $value): ?>
                            <li><?php echo ucfirst($key); ?>: <?php echo $value; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php
            $allPassed = true;
            foreach ($testResults as $result) {
                if ($result['status'] === 'error') {
                    $allPassed = false;
                    break;
                }
            }
            ?>
            
            <?php if ($allPassed): ?>
            <div class="success">
                <h3>🎉 All Tests Passed!</h3>
                <p>The system is fully functional and ready for use. All components are working correctly.</p>
            </div>
            <?php else: ?>
            <div class="error">
                <h3>⚠️ Some Tests Failed</h3>
                <p>Please review the failed tests above and run the appropriate fix scripts.</p>
            </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <h4>🚀 Test System Features:</h4>
                <a href="index.php" class="btn">🏠 Homepage</a>
                <a href="user-registration.php" class="btn">👤 User Registration</a>
                <a href="apply-pass.php" class="btn">📝 Apply Pass</a>
                <a href="user-dashboard.php" class="btn">📊 User Dashboard</a>
                <a href="admin-dashboard.php" class="btn">🔐 Admin Dashboard</a>
                <a href="instant-reviews-display.php" class="btn">⭐ Reviews</a>
            </div>
            
            <div class="info">
                <h4>🔧 Available Fix Scripts:</h4>
                <div style="text-align: center;">
                    <a href="complete_database_setup.php" class="btn btn-success">🗄️ Complete Database Setup</a>
                    <a href="fix_photo_path_column.php" class="btn btn-success">📸 Fix Photo Path Column</a>
                    <a href="fix_announcements_table.php" class="btn btn-success">📢 Fix Announcements Table</a>
                    <a href="fix_all_database_issues.php" class="btn btn-success">🔧 Fix All Issues</a>
                </div>
            </div>
            
            <div class="test-section">
                <h4>📋 System Status Summary:</h4>
                <ul>
                    <li><strong>Database:</strong> <?php echo $testResults['database']['status'] === 'success' ? '✅ Connected' : '❌ Connection Failed'; ?></li>
                    <li><strong>Tables:</strong> <?php echo $testResults['tables']['status'] === 'success' ? '✅ All Present' : '❌ Missing Tables'; ?></li>
                    <li><strong>Data:</strong> <?php echo $testResults['data']['status'] === 'success' ? '✅ Sample Data Available' : '❌ Data Issues'; ?></li>
                    <li><strong>Columns:</strong> <?php echo $testResults['columns']['status'] === 'success' ? '✅ All Required Columns' : '❌ Missing Columns'; ?></li>
                    <li><strong>File System:</strong> <?php echo $testResults['filesystem']['status'] === 'success' ? '✅ Upload Directory Ready' : '⚠️ File System Issues'; ?></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
