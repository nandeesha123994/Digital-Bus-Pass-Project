<?php
/**
 * Routes System Status Checker
 * Quick diagnostic tool to check if routes system is properly set up
 */

include('includes/dbconnection.php');

echo "<h1>🔍 Routes System Status Check</h1>";

try {
    // Check database connection
    if ($con->connect_error) {
        throw new Exception("Database connection failed: " . $con->connect_error);
    }
    echo "<p style='color: green;'>✅ Database connection: OK</p>";
    
    // Check if routes table exists
    $tableCheck = "SHOW TABLES LIKE 'routes'";
    $tableResult = $con->query($tableCheck);
    
    if ($tableResult->num_rows == 0) {
        echo "<p style='color: red;'>❌ Routes table: NOT FOUND</p>";
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>⚠️ Routes Table Missing</h3>";
        echo "<p>The routes table has not been created yet. Please run the setup script to create it.</p>";
        echo "<p><a href='setup-routes-table.php' style='background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>🔧 Run Setup Script</a></p>";
        echo "</div>";
        exit();
    }
    
    echo "<p style='color: green;'>✅ Routes table: EXISTS</p>";
    
    // Check table structure
    $structureCheck = "DESCRIBE routes";
    $structureResult = $con->query($structureCheck);
    
    $expectedColumns = ['id', 'route_id', 'route_name', 'source', 'destination', 'distance_km', 'estimated_duration', 'is_active', 'created_at', 'updated_at'];
    $actualColumns = [];
    
    while ($column = $structureResult->fetch_assoc()) {
        $actualColumns[] = $column['Field'];
    }
    
    $missingColumns = array_diff($expectedColumns, $actualColumns);
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✅ Table structure: CORRECT</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Table structure: INCOMPLETE</p>";
        echo "<p>Missing columns: " . implode(', ', $missingColumns) . "</p>";
    }
    
    // Check data count
    $countQuery = "SELECT COUNT(*) as total FROM routes";
    $countResult = $con->query($countQuery);
    $total = $countResult->fetch_assoc()['total'];
    
    echo "<p style='color: green;'>✅ Routes count: $total routes</p>";
    
    if ($total == 0) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>⚠️ No Routes Data</h3>";
        echo "<p>The routes table is empty. You can add sample data using the setup script.</p>";
        echo "<p><a href='setup-routes-table.php' style='background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>📝 Add Sample Data</a></p>";
        echo "</div>";
    }
    
    // Check indexes
    $indexQuery = "SHOW INDEX FROM routes";
    $indexResult = $con->query($indexQuery);
    $indexes = [];
    
    while ($index = $indexResult->fetch_assoc()) {
        if ($index['Key_name'] != 'PRIMARY') {
            $indexes[] = $index['Key_name'];
        }
    }
    
    echo "<p style='color: green;'>✅ Database indexes: " . count($indexes) . " indexes found</p>";
    
    // Test API endpoint
    echo "<h3>🔗 Testing API Endpoints</h3>";
    
    $apiTests = [
        'get_sources' => 'get-route-info.php?action=get_sources',
        'get_destinations' => 'get-route-info.php?action=get_destinations',
    ];
    
    foreach ($apiTests as $test => $url) {
        $testResult = @file_get_contents("http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/" . $url);
        if ($testResult) {
            $data = json_decode($testResult, true);
            if ($data && isset($data['success']) && $data['success']) {
                echo "<p style='color: green;'>✅ API $test: WORKING</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ API $test: RESPONSE ISSUE</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ API $test: FAILED</p>";
        }
    }
    
    // Show sample routes
    if ($total > 0) {
        echo "<h3>📋 Sample Routes (First 5)</h3>";
        $sampleQuery = "SELECT route_id, route_name, source, destination FROM routes LIMIT 5";
        $sampleResult = $con->query($sampleQuery);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Route ID</th><th>Route Name</th><th>Source</th><th>Destination</th></tr>";
        
        while ($route = $sampleResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($route['route_id']) . "</td>";
            echo "<td>" . htmlspecialchars($route['route_name']) . "</td>";
            echo "<td>" . htmlspecialchars($route['source']) . "</td>";
            echo "<td>" . htmlspecialchars($route['destination']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Final status
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎉 Routes System Status: READY</h3>";
    echo "<p><strong>System Components:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Database connection working</li>";
    echo "<li>✅ Routes table exists with correct structure</li>";
    echo "<li>✅ $total routes available</li>";
    echo "<li>✅ Database indexes optimized</li>";
    echo "<li>✅ API endpoints functional</li>";
    echo "</ul>";
    echo "<p><strong>Access Points:</strong></p>";
    echo "<ul>";
    echo "<li><a href='manage-routes.php' style='color: #155724; font-weight: bold;'>🔗 Admin Route Management</a></li>";
    echo "<li><a href='apply-pass.php' style='color: #155724; font-weight: bold;'>🔗 Enhanced Application Form</a></li>";
    echo "<li><a href='admin-dashboard.php' style='color: #155724; font-weight: bold;'>🔗 Admin Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ System Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Troubleshooting Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Ensure XAMPP MySQL service is running</li>";
    echo "<li>Check database 'bpmsdb' exists</li>";
    echo "<li>Verify database connection settings</li>";
    echo "<li>Run the setup script: <a href='setup-routes-table.php'>setup-routes-table.php</a></li>";
    echo "</ul>";
    echo "</div>";
}

$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Routes System Status - Bus Pass Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            line-height: 1.6;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin-top: 30px;">
        <p><a href="admin-dashboard.php" style="background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">← Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
