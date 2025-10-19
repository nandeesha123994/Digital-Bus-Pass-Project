<?php
// Direct fix for photo_path column issue
$servername = "localhost";
$username = "root";
$password = "";
$database = "bpmsdb";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Fix photo_path Column - Nrupatunga Digital Bus Pass System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
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
            <h1>🔧 Direct Fix photo_path Column</h1>
            <p>Emergency fix for missing photo_path column</p>
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
                
                // First, let's see the current table structure
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
                $hasPhotoFile = false;
                $columns = [];
                
                while ($row = $result->fetch_assoc()) {
                    $columns[] = $row['Field'];
                    if ($row['Field'] == 'photo_path') {
                        $hasPhotoPath = true;
                    }
                    if ($row['Field'] == 'photo_file') {
                        $hasPhotoFile = true;
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
                
                echo "<div class='info'>";
                echo "<h4>🔍 Column Analysis:</h4>";
                echo "<p><strong>photo_path exists:</strong> " . ($hasPhotoPath ? "✅ Yes" : "❌ No") . "</p>";
                echo "<p><strong>photo_file exists:</strong> " . ($hasPhotoFile ? "✅ Yes" : "❌ No") . "</p>";
                echo "<p><strong>Total columns:</strong> " . count($columns) . "</p>";
                echo "</div>";
                
                // Now let's add the missing column
                if (!$hasPhotoPath) {
                    echo "<div class='info'>";
                    echo "<h4>🔧 Adding photo_path column...</h4>";
                    echo "</div>";
                    
                    // Try different approaches to add the column
                    $sqlCommands = [];
                    
                    if ($hasPhotoFile) {
                        // Add after photo_file
                        $sqlCommands[] = "ALTER TABLE bus_pass_applications ADD COLUMN photo_path VARCHAR(255) AFTER photo_file";
                    } else {
                        // Add after amount
                        $sqlCommands[] = "ALTER TABLE bus_pass_applications ADD COLUMN photo_path VARCHAR(255) AFTER amount";
                    }
                    
                    foreach ($sqlCommands as $sql) {
                        echo "<div class='sql-code'>";
                        echo "<strong>Executing SQL:</strong><br>";
                        echo htmlspecialchars($sql);
                        echo "</div>";
                        
                        if ($con->query($sql) === TRUE) {
                            echo "<div class='success'>✅ Successfully added photo_path column</div>";
                            break;
                        } else {
                            echo "<div class='error'>❌ Failed to add photo_path column: " . $con->error . "</div>";
                        }
                    }
                } else {
                    echo "<div class='info'>ℹ️ photo_path column already exists</div>";
                }
                
                // Let's also ensure other required columns exist
                $requiredColumns = [
                    'email' => "VARCHAR(100) NOT NULL",
                    'id_proof_type' => "VARCHAR(50)",
                    'id_proof_number' => "VARCHAR(50)",
                    'id_proof_file' => "VARCHAR(255)"
                ];
                
                foreach ($requiredColumns as $column => $definition) {
                    if (!in_array($column, $columns)) {
                        $sql = "ALTER TABLE bus_pass_applications ADD COLUMN $column $definition";
                        echo "<div class='sql-code'>";
                        echo "<strong>Adding missing column:</strong><br>";
                        echo htmlspecialchars($sql);
                        echo "</div>";
                        
                        if ($con->query($sql) === TRUE) {
                            echo "<div class='success'>✅ Successfully added $column column</div>";
                        } else {
                            echo "<div class='error'>❌ Failed to add $column column: " . $con->error . "</div>";
                        }
                    } else {
                        echo "<div class='info'>ℹ️ $column column already exists</div>";
                    }
                }
                
                // Show final table structure
                echo "<div class='info'>";
                echo "<h4>📋 Final bus_pass_applications table structure:</h4>";
                $result = $con->query("DESCRIBE bus_pass_applications");
                echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr style='background: #f8f9fa;'>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Field</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Type</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Null</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Key</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Default</th>";
                echo "</tr>";
                
                $finalHasPhotoPath = false;
                while ($row = $result->fetch_assoc()) {
                    if ($row['Field'] == 'photo_path') {
                        $finalHasPhotoPath = true;
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
                
                // Test the fix
                if ($finalHasPhotoPath) {
                    echo "<div class='success'>";
                    echo "<h3>🎉 photo_path Column Successfully Added!</h3>";
                    echo "<p>The bus_pass_applications table now has the photo_path column. The Apply Pass form should work correctly now.</p>";
                    echo "</div>";
                    
                    // Test query
                    $testQuery = "SELECT COUNT(*) as count FROM bus_pass_applications";
                    $testResult = $con->query($testQuery);
                    
                    if ($testResult) {
                        $count = $testResult->fetch_assoc()['count'];
                        echo "<div class='success'>✅ Test query successful: Found {$count} applications in database</div>";
                    }
                } else {
                    echo "<div class='error'>";
                    echo "<h3>❌ Failed to Add photo_path Column</h3>";
                    echo "<p>The photo_path column could not be added. Please check MySQL permissions and try again.</p>";
                    echo "</div>";
                }
                
                $con->close();
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fix Failed: " . $e->getMessage() . "</div>";
            }
            ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="apply-pass.php" class="btn">📝 Test Apply Pass</a>
                <a href="index.php" class="btn">🏠 Go to Homepage</a>
                <a href="user-dashboard.php" class="btn">👤 User Dashboard</a>
            </div>
            
            <div class="info">
                <h4>🔍 What this fix does:</h4>
                <ul>
                    <li>✅ Checks current table structure</li>
                    <li>✅ Adds missing <code>photo_path</code> column</li>
                    <li>✅ Adds other required columns (email, id_proof_type, etc.)</li>
                    <li>✅ Verifies the fix with test queries</li>
                    <li>✅ Shows complete table structure</li>
                </ul>
                
                <h4>🚀 After this fix:</h4>
                <p>The Apply Pass form should work without the "Unknown column 'photo_path'" error.</p>
            </div>
        </div>
    </div>
</body>
</html>
