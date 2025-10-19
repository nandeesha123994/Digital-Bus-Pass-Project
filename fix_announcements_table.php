<?php
// Fix announcements table - Add missing is_active column
$servername = "localhost";
$username = "root";
$password = "";
$database = "bpmsdb";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Announcements Table - Nrupatunga Digital Bus Pass System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            <h1>🔧 Fix Announcements Table</h1>
            <p>Adding missing is_active column</p>
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
                
                // Check if is_active column exists
                $checkColumn = $con->query("SHOW COLUMNS FROM announcements LIKE 'is_active'");
                
                if ($checkColumn->num_rows == 0) {
                    // Add is_active column
                    $sql = "ALTER TABLE announcements ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER type";
                    
                    if ($con->query($sql) === TRUE) {
                        echo "<div class='success'>✅ Added is_active column to announcements table</div>";
                    } else {
                        throw new Exception("Error adding is_active column: " . $con->error);
                    }
                } else {
                    echo "<div class='info'>ℹ️ is_active column already exists in announcements table</div>";
                }
                
                // Update existing announcements to be active
                $sql = "UPDATE announcements SET is_active = TRUE WHERE is_active IS NULL";
                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Updated existing announcements to active status</div>";
                }
                
                // Verify the table structure
                echo "<div class='info'>";
                echo "<h4>📋 Current announcements table structure:</h4>";
                $result = $con->query("DESCRIBE announcements");
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
                
                // Test the fix by running a query that uses is_active
                $testQuery = "SELECT COUNT(*) as count FROM announcements WHERE is_active = TRUE";
                $testResult = $con->query($testQuery);
                
                if ($testResult) {
                    $count = $testResult->fetch_assoc()['count'];
                    echo "<div class='success'>✅ Test query successful: Found {$count} active announcements</div>";
                } else {
                    throw new Exception("Test query failed: " . $con->error);
                }
                
                echo "<div class='success'>";
                echo "<h3>🎉 Fix Applied Successfully!</h3>";
                echo "<p>The announcements table now has the is_active column and the 'Apply Pass' button should work correctly.</p>";
                echo "</div>";
                
                $con->close();
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fix Failed: " . $e->getMessage() . "</div>";
                echo "<div class='info'>";
                echo "<h4>Troubleshooting:</h4>";
                echo "<ul>";
                echo "<li>Make sure the database 'bpmsdb' exists</li>";
                echo "<li>Verify the announcements table exists</li>";
                echo "<li>Check MySQL permissions</li>";
                echo "</ul>";
                echo "</div>";
            }
            ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" class="btn">🏠 Go to Homepage</a>
                <a href="apply-pass.php" class="btn">📝 Test Apply Pass</a>
                <a href="admin-dashboard.php" class="btn">🔐 Admin Dashboard</a>
            </div>
            
            <div class="info">
                <h4>🔍 What was fixed:</h4>
                <ul>
                    <li>✅ Added missing <code>is_active</code> column to announcements table</li>
                    <li>✅ Set default value to TRUE for existing announcements</li>
                    <li>✅ Verified the fix with a test query</li>
                    <li>✅ The "Apply Pass" button should now work correctly</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
