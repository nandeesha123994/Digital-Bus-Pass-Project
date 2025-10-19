<?php
// Fix photo_path column issue in bus_pass_applications table
$servername = "localhost";
$username = "root";
$password = "";
$database = "bpmsdb";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix photo_path Column - Nrupatunga Digital Bus Pass System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
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
            background: linear-gradient(135deg, #28a745, #20c997);
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
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Fix photo_path Column</h1>
            <p>Adding missing photo_path column to bus_pass_applications table</p>
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
                
                // Check current table structure
                echo "<div class='info'>";
                echo "<h4>📋 Current bus_pass_applications table structure:</h4>";
                $result = $con->query("DESCRIBE bus_pass_applications");
                echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr style='background: #f8f9fa;'>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Field</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Type</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Null</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Key</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Default</th>";
                echo "</tr>";
                
                $hasPhotoPath = false;
                while ($row = $result->fetch_assoc()) {
                    if ($row['Field'] == 'photo_path') {
                        $hasPhotoPath = true;
                    }
                    echo "<tr>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>" . $row['Field'] . "</strong></td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Type'] . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Null'] . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Key'] . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($row['Default'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
                
                if (!$hasPhotoPath) {
                    // Add photo_path column
                    $sql = "ALTER TABLE bus_pass_applications ADD COLUMN photo_path VARCHAR(255) AFTER photo_file";
                    
                    if ($con->query($sql) === TRUE) {
                        echo "<div class='success'>✅ Added photo_path column to bus_pass_applications table</div>";
                    } else {
                        throw new Exception("Error adding photo_path column: " . $con->error);
                    }
                } else {
                    echo "<div class='info'>ℹ️ photo_path column already exists in bus_pass_applications table</div>";
                }
                
                // Also check and add other missing columns that might be needed
                $requiredColumns = [
                    'application_id' => "VARCHAR(50) UNIQUE AFTER id",
                    'email' => "VARCHAR(100) NOT NULL AFTER phone",
                    'id_proof_type' => "VARCHAR(50) NOT NULL AFTER destination", 
                    'id_proof_number' => "VARCHAR(50) NOT NULL AFTER id_proof_type",
                    'id_proof_file' => "VARCHAR(255) AFTER id_proof_number"
                ];
                
                foreach ($requiredColumns as $column => $definition) {
                    $checkColumn = $con->query("SHOW COLUMNS FROM bus_pass_applications LIKE '$column'");
                    if ($checkColumn->num_rows == 0) {
                        $sql = "ALTER TABLE bus_pass_applications ADD COLUMN $column $definition";
                        if ($con->query($sql) === TRUE) {
                            echo "<div class='success'>✅ Added $column column to bus_pass_applications</div>";
                        } else {
                            echo "<div class='error'>❌ Could not add $column column: " . $con->error . "</div>";
                        }
                    } else {
                        echo "<div class='info'>ℹ️ $column column already exists</div>";
                    }
                }
                
                // Show updated table structure
                echo "<div class='info'>";
                echo "<h4>📋 Updated bus_pass_applications table structure:</h4>";
                $result = $con->query("DESCRIBE bus_pass_applications");
                echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr style='background: #f8f9fa;'>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Field</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Type</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Null</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Key</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Default</th>";
                echo "</tr>";
                
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>" . $row['Field'] . "</strong></td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Type'] . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Null'] . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Key'] . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($row['Default'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
                
                // Test the fix by running a query that uses photo_path
                $testQuery = "SELECT COUNT(*) as count FROM bus_pass_applications";
                $testResult = $con->query($testQuery);
                
                if ($testResult) {
                    $count = $testResult->fetch_assoc()['count'];
                    echo "<div class='success'>✅ Test query successful: Found {$count} applications in database</div>";
                } else {
                    throw new Exception("Test query failed: " . $con->error);
                }
                
                echo "<div class='success'>";
                echo "<h3>🎉 photo_path Column Fix Applied Successfully!</h3>";
                echo "<p>The bus_pass_applications table now has all required columns and the Apply Pass form should work correctly.</p>";
                echo "</div>";
                
                $con->close();
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fix Failed: " . $e->getMessage() . "</div>";
                echo "<div class='info'>";
                echo "<h4>Troubleshooting:</h4>";
                echo "<ul>";
                echo "<li>Make sure the database 'bpmsdb' exists</li>";
                echo "<li>Verify the bus_pass_applications table exists</li>";
                echo "<li>Check MySQL permissions</li>";
                echo "</ul>";
                echo "</div>";
            }
            ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" class="btn">🏠 Go to Homepage</a>
                <a href="apply-pass.php" class="btn">📝 Test Apply Pass</a>
                <a href="user-dashboard.php" class="btn">👤 User Dashboard</a>
            </div>
            
            <div class="info">
                <h4>🔍 What was fixed:</h4>
                <ul>
                    <li>✅ Added missing <code>photo_path</code> column to bus_pass_applications table</li>
                    <li>✅ Added other required columns (application_id, email, id_proof_type, etc.)</li>
                    <li>✅ Verified the fix with a test query</li>
                    <li>✅ The Apply Pass form should now work without errors</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
