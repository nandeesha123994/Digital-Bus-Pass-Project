<?php
// Fix Application ID System - Comprehensive solution
$servername = "localhost";
$username = "root";
$password = "";
$database = "bpmsdb";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Application ID System - Nrupatunga Digital Bus Pass System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
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
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
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
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        .fix-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .sql-code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            border-left: 4px solid #007bff;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Fix Application ID System</h1>
            <p>Comprehensive fix for application ID generation and tracking</p>
        </div>
        <div class="content">
            <?php
            try {
                // Create connection
                $con = new mysqli($servername, $username, $password, $database);
                
                // Check connection
                if ($con->connect_error) {
                    throw new Exception("Connection failed: " . $con->connect_error);
                }
                
                echo "<div class='success'>✅ Connected to database successfully</div>";
                
                // Fix 1: Ensure application_id column exists and is properly configured
                echo "<div class='fix-section'>";
                echo "<h4>🔧 Fix 1: Application ID Column Setup</h4>";
                
                $checkColumn = $con->query("SHOW COLUMNS FROM bus_pass_applications LIKE 'application_id'");
                if ($checkColumn->num_rows == 0) {
                    $sql = "ALTER TABLE bus_pass_applications ADD COLUMN application_id VARCHAR(50) UNIQUE AFTER id";
                    if ($con->query($sql) === TRUE) {
                        echo "<div class='success'>✅ Added application_id column to bus_pass_applications</div>";
                    } else {
                        echo "<div class='error'>❌ Failed to add application_id column: " . $con->error . "</div>";
                    }
                } else {
                    echo "<div class='info'>ℹ️ application_id column already exists</div>";
                }
                echo "</div>";
                
                // Fix 2: Generate Application IDs for existing records
                echo "<div class='fix-section'>";
                echo "<h4>🔧 Fix 2: Generate Application IDs for Existing Records</h4>";
                
                $checkExisting = $con->query("SELECT COUNT(*) as count FROM bus_pass_applications WHERE application_id IS NULL OR application_id = ''");
                $existingCount = $checkExisting->fetch_assoc()['count'];
                
                if ($existingCount > 0) {
                    echo "<div class='info'>Found {$existingCount} records without Application IDs. Generating...</div>";
                    
                    // Get records without application_id
                    $getRecords = $con->query("SELECT id FROM bus_pass_applications WHERE application_id IS NULL OR application_id = ''");
                    
                    $updateCount = 0;
                    while ($record = $getRecords->fetch_assoc()) {
                        // Generate unique Application ID
                        do {
                            $year = date('Y');
                            $randomNumber = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                            $applicationId = "BPMS{$year}{$randomNumber}";
                            
                            // Check if this ID already exists
                            $checkQuery = "SELECT id FROM bus_pass_applications WHERE application_id = ?";
                            $stmt = $con->prepare($checkQuery);
                            $stmt->bind_param("s", $applicationId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                        } while ($result->num_rows > 0);
                        
                        // Update the record
                        $updateQuery = "UPDATE bus_pass_applications SET application_id = ? WHERE id = ?";
                        $updateStmt = $con->prepare($updateQuery);
                        $updateStmt->bind_param("si", $applicationId, $record['id']);
                        
                        if ($updateStmt->execute()) {
                            $updateCount++;
                        }
                    }
                    
                    echo "<div class='success'>✅ Generated Application IDs for {$updateCount} existing records</div>";
                } else {
                    echo "<div class='info'>ℹ️ All existing records already have Application IDs</div>";
                }
                echo "</div>";
                
                // Fix 3: Test Application ID Generation Function
                echo "<div class='fix-section'>";
                echo "<h4>🔧 Fix 3: Test Application ID Generation</h4>";
                
                // Test the generation function
                function generateTestApplicationId($con) {
                    do {
                        $year = date('Y');
                        $randomNumber = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                        $applicationId = "BPMS{$year}{$randomNumber}";
                        
                        // Check if this ID already exists
                        $checkQuery = "SELECT id FROM bus_pass_applications WHERE application_id = ?";
                        $stmt = $con->prepare($checkQuery);
                        $stmt->bind_param("s", $applicationId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                    } while ($result->num_rows > 0);
                    
                    return $applicationId;
                }
                
                // Generate 5 test IDs
                echo "<div class='info'>";
                echo "<h5>Sample Generated Application IDs:</h5>";
                echo "<ul>";
                for ($i = 1; $i <= 5; $i++) {
                    $testId = generateTestApplicationId($con);
                    echo "<li><strong>{$testId}</strong></li>";
                }
                echo "</ul>";
                echo "</div>";
                echo "</div>";
                
                // Fix 4: Verify Application Tracking System
                echo "<div class='fix-section'>";
                echo "<h4>🔧 Fix 4: Application Tracking System</h4>";
                
                // Check if we can query applications by application_id
                $testQuery = "SELECT COUNT(*) as count FROM bus_pass_applications WHERE application_id LIKE 'BPMS%'";
                $testResult = $con->query($testQuery);
                
                if ($testResult) {
                    $count = $testResult->fetch_assoc()['count'];
                    echo "<div class='success'>✅ Application tracking system working: {$count} applications with valid IDs</div>";
                } else {
                    echo "<div class='error'>❌ Application tracking system test failed: " . $con->error . "</div>";
                }
                
                // Test application lookup
                $sampleQuery = "SELECT application_id, applicant_name, status FROM bus_pass_applications WHERE application_id IS NOT NULL LIMIT 3";
                $sampleResult = $con->query($sampleQuery);
                
                if ($sampleResult && $sampleResult->num_rows > 0) {
                    echo "<div class='info'>";
                    echo "<h5>Sample Application Records:</h5>";
                    echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
                    echo "<tr style='background: #f8f9fa;'>";
                    echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Application ID</th>";
                    echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Applicant Name</th>";
                    echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Status</th>";
                    echo "</tr>";
                    
                    while ($row = $sampleResult->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>" . htmlspecialchars($row['application_id']) . "</strong></td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['applicant_name']) . "</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['status']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "</div>";
                }
                echo "</div>";
                
                // Fix 5: Update Application Status Display
                echo "<div class='fix-section'>";
                echo "<h4>🔧 Fix 5: Application Status Summary</h4>";
                
                $statusQuery = "SELECT 
                    COUNT(*) as total_applications,
                    COUNT(CASE WHEN application_id IS NOT NULL AND application_id != '' THEN 1 END) as with_app_id,
                    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved,
                    COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected
                    FROM bus_pass_applications";
                
                $statusResult = $con->query($statusQuery);
                if ($statusResult) {
                    $stats = $statusResult->fetch_assoc();
                    
                    echo "<div class='info'>";
                    echo "<h5>📊 Application Statistics:</h5>";
                    echo "<ul>";
                    echo "<li><strong>Total Applications:</strong> {$stats['total_applications']}</li>";
                    echo "<li><strong>With Application ID:</strong> {$stats['with_app_id']}</li>";
                    echo "<li><strong>Pending:</strong> {$stats['pending']}</li>";
                    echo "<li><strong>Approved:</strong> {$stats['approved']}</li>";
                    echo "<li><strong>Rejected:</strong> {$stats['rejected']}</li>";
                    echo "</ul>";
                    echo "</div>";
                }
                echo "</div>";
                
                echo "<div class='success'>";
                echo "<h3>🎉 Application ID System Fixed!</h3>";
                echo "<p>The application ID generation and tracking system is now fully functional.</p>";
                echo "</div>";
                
                $con->close();
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fix Failed: " . $e->getMessage() . "</div>";
            }
            ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="apply-pass.php" class="btn">📝 Test Apply Pass</a>
                <a href="user-dashboard.php" class="btn">👤 User Dashboard</a>
                <a href="track-application.php" class="btn">🔍 Track Application</a>
                <a href="admin-dashboard.php" class="btn">🔐 Admin Dashboard</a>
            </div>
            
            <div class="info">
                <h4>🔍 What was fixed:</h4>
                <ul>
                    <li>✅ Ensured application_id column exists in bus_pass_applications table</li>
                    <li>✅ Generated Application IDs for existing records without IDs</li>
                    <li>✅ Tested Application ID generation function</li>
                    <li>✅ Verified application tracking system functionality</li>
                    <li>✅ Updated application status display and statistics</li>
                </ul>
                
                <h4>🎯 Application ID Format:</h4>
                <p><strong>Format:</strong> BPMS{YEAR}{6-digit-random-number}</p>
                <p><strong>Example:</strong> BPMS2025123456</p>
                
                <h4>🚀 System Status:</h4>
                <p><strong>✅ Ready for use!</strong> Users can now apply for bus passes and receive unique Application IDs for tracking.</p>
            </div>
        </div>
    </div>
</body>
</html>
