<?php
// Fix Categories Table - Add missing is_active column
$servername = "localhost";
$username = "root";
$password = "";
$database = "bpmsdb";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Categories Table - Nrupatunga Digital Bus Pass System</title>
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
            <h1>🔧 Fix Categories Table</h1>
            <p>Adding missing is_active column to categories table</p>
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
                echo "<h4>📋 Current categories table structure:</h4>";
                $result = $con->query("DESCRIBE categories");
                echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr style='background: #f8f9fa;'>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Field</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Type</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Null</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Key</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Default</th>";
                echo "</tr>";
                
                $hasIsActive = false;
                while ($row = $result->fetch_assoc()) {
                    if ($row['Field'] == 'is_active') {
                        $hasIsActive = true;
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
                
                if (!$hasIsActive) {
                    // Add is_active column
                    echo "<div class='info'>";
                    echo "<h4>🔧 Adding is_active column...</h4>";
                    echo "</div>";
                    
                    $sql = "ALTER TABLE categories ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER description";
                    
                    echo "<div class='sql-code'>";
                    echo "<strong>Executing SQL:</strong><br>";
                    echo htmlspecialchars($sql);
                    echo "</div>";
                    
                    if ($con->query($sql) === TRUE) {
                        echo "<div class='success'>✅ Successfully added is_active column to categories table</div>";
                        
                        // Update existing categories to be active
                        $updateSql = "UPDATE categories SET is_active = TRUE WHERE is_active IS NULL";
                        if ($con->query($updateSql) === TRUE) {
                            echo "<div class='success'>✅ Updated existing categories to be active</div>";
                        }
                    } else {
                        echo "<div class='error'>❌ Failed to add is_active column: " . $con->error . "</div>";
                    }
                } else {
                    echo "<div class='info'>ℹ️ is_active column already exists in categories table</div>";
                }
                
                // Add updated_at column if it doesn't exist
                $hasUpdatedAt = false;
                $result = $con->query("DESCRIBE categories");
                while ($row = $result->fetch_assoc()) {
                    if ($row['Field'] == 'updated_at') {
                        $hasUpdatedAt = true;
                        break;
                    }
                }
                
                if (!$hasUpdatedAt) {
                    echo "<div class='info'>";
                    echo "<h4>🔧 Adding updated_at column...</h4>";
                    echo "</div>";
                    
                    $sql = "ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
                    
                    echo "<div class='sql-code'>";
                    echo "<strong>Executing SQL:</strong><br>";
                    echo htmlspecialchars($sql);
                    echo "</div>";
                    
                    if ($con->query($sql) === TRUE) {
                        echo "<div class='success'>✅ Successfully added updated_at column to categories table</div>";
                    } else {
                        echo "<div class='error'>❌ Failed to add updated_at column: " . $con->error . "</div>";
                    }
                } else {
                    echo "<div class='info'>ℹ️ updated_at column already exists in categories table</div>";
                }
                
                // Show final table structure
                echo "<div class='info'>";
                echo "<h4>📋 Final categories table structure:</h4>";
                $result = $con->query("DESCRIBE categories");
                echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr style='background: #f8f9fa;'>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Field</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Type</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Null</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Key</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Default</th>";
                echo "</tr>";
                
                $finalHasIsActive = false;
                while ($row = $result->fetch_assoc()) {
                    if ($row['Field'] == 'is_active') {
                        $finalHasIsActive = true;
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
                if ($finalHasIsActive) {
                    echo "<div class='success'>";
                    echo "<h3>🎉 Categories Table Fixed Successfully!</h3>";
                    echo "<p>The categories table now has the is_active column and manage-categories.php should work without errors.</p>";
                    echo "</div>";
                    
                    // Test query
                    $testQuery = "SELECT COUNT(*) as count FROM categories WHERE is_active = TRUE";
                    $testResult = $con->query($testQuery);
                    
                    if ($testResult) {
                        $count = $testResult->fetch_assoc()['count'];
                        echo "<div class='success'>✅ Test query successful: Found {$count} active categories</div>";
                    }
                } else {
                    echo "<div class='error'>";
                    echo "<h3>❌ Failed to Fix Categories Table</h3>";
                    echo "<p>The is_active column could not be added. Please check MySQL permissions and try again.</p>";
                    echo "</div>";
                }
                
                $con->close();
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fix Failed: " . $e->getMessage() . "</div>";
            }
            ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="manage-categories.php" class="btn">🏷️ Test Manage Categories</a>
                <a href="admin-dashboard.php" class="btn">🔐 Admin Dashboard</a>
                <a href="index.php" class="btn">🏠 Homepage</a>
            </div>
            
            <div class="info">
                <h4>🔍 What was fixed:</h4>
                <ul>
                    <li>✅ Added missing <code>is_active</code> column to categories table</li>
                    <li>✅ Added missing <code>updated_at</code> column for tracking changes</li>
                    <li>✅ Set all existing categories to active by default</li>
                    <li>✅ Updated manage-categories.php to handle missing columns gracefully</li>
                    <li>✅ Fixed JavaScript function to handle undefined values</li>
                </ul>
                
                <h4>🚀 After this fix:</h4>
                <p>The manage-categories.php page should work without the "Undefined array key 'is_active'" error.</p>
            </div>
        </div>
    </div>
</body>
</html>
