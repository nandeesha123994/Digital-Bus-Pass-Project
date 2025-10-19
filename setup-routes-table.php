<?php
/**
 * Simple Routes Table Setup Script
 * This script creates the routes table and inserts sample data
 */

include('includes/dbconnection.php');

echo "<h1>🚌 Routes Table Setup</h1>";
echo "<p>Setting up the routes management system...</p>";

try {
    // First, check if table exists
    $checkTable = "SHOW TABLES LIKE 'routes'";
    $result = $con->query($checkTable);
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Routes table already exists. Checking structure...</p>";
        
        // Check if table has data
        $countQuery = "SELECT COUNT(*) as count FROM routes";
        $countResult = $con->query($countQuery);
        $count = $countResult->fetch_assoc()['count'];
        
        echo "<p style='color: blue;'>ℹ️ Table contains $count routes.</p>";
        
        if ($count == 0) {
            echo "<p>📝 Table is empty. Adding sample data...</p>";
            // Jump to insert data section
            goto insertData;
        } else {
            echo "<p style='color: green;'>✅ Routes table is ready with existing data!</p>";
            goto showResults;
        }
    }
    
    echo "<p>📝 Creating routes table...</p>";
    
    // Create routes table
    $createTableSQL = "
    CREATE TABLE routes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        route_id VARCHAR(20) NOT NULL UNIQUE,
        route_name VARCHAR(100) NOT NULL,
        source VARCHAR(100) NOT NULL,
        destination VARCHAR(100) NOT NULL,
        distance_km DECIMAL(6,2) DEFAULT NULL,
        estimated_duration VARCHAR(20) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($con->query($createTableSQL)) {
        echo "<p style='color: green;'>✅ Routes table created successfully!</p>";
    } else {
        throw new Exception("Error creating routes table: " . $con->error);
    }
    
    // Add indexes
    echo "<p>📝 Adding database indexes...</p>";
    
    $indexes = [
        "CREATE INDEX idx_source ON routes(source)",
        "CREATE INDEX idx_destination ON routes(destination)", 
        "CREATE INDEX idx_source_dest ON routes(source, destination)",
        "CREATE INDEX idx_route_id ON routes(route_id)",
        "CREATE INDEX idx_active ON routes(is_active)"
    ];
    
    foreach ($indexes as $indexSQL) {
        $con->query($indexSQL); // Don't throw error if index already exists
    }
    
    echo "<p style='color: green;'>✅ Database indexes added!</p>";
    
    insertData:
    
    // Insert sample data
    echo "<p>📝 Inserting sample route data...</p>";
    
    $sampleRoutes = [
        ['R001', 'City Center Express', 'Bangalore Central', 'Electronic City', 25.5, '45 mins'],
        ['R002', 'Tech Park Shuttle', 'Whitefield', 'Koramangala', 18.2, '35 mins'],
        ['R003', 'Airport Connect', 'Majestic', 'Kempegowda Airport', 40.0, '60 mins'],
        ['R004', 'University Route', 'Jayanagar', 'Banashankari', 12.8, '25 mins'],
        ['R005', 'Mall Circuit', 'Brigade Road', 'Forum Mall', 8.5, '20 mins'],
        ['R006', 'IT Corridor', 'Silk Board', 'Marathahalli', 15.3, '30 mins'],
        ['R007', 'Metro Feeder', 'Indiranagar', 'MG Road Metro', 6.2, '15 mins'],
        ['R008', 'Hospital Route', 'Rajajinagar', 'Manipal Hospital', 9.7, '22 mins'],
        ['R009', 'Market Express', 'KR Market', 'Commercial Street', 7.4, '18 mins'],
        ['R010', 'Suburb Connect', 'Hebbal', 'Yelahanka', 14.6, '28 mins'],
        ['R011', 'Lake View', 'Ulsoor Lake', 'Lalbagh', 11.3, '24 mins'],
        ['R012', 'Business District', 'UB City Mall', 'Cubbon Park', 4.8, '12 mins'],
        ['R013', 'Education Hub', 'IISc', 'Indian Institute of Management', 16.9, '32 mins'],
        ['R014', 'Shopping Circuit', 'Chickpet', 'Avenue Road', 5.1, '14 mins'],
        ['R015', 'Residential Route', 'RT Nagar', 'Sadashivanagar', 8.9, '19 mins']
    ];
    
    $insertSQL = "INSERT INTO routes (route_id, route_name, source, destination, distance_km, estimated_duration) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($insertSQL);
    
    $successCount = 0;
    foreach ($sampleRoutes as $route) {
        $stmt->bind_param('ssssds', $route[0], $route[1], $route[2], $route[3], $route[4], $route[5]);
        if ($stmt->execute()) {
            $successCount++;
        }
    }
    
    echo "<p style='color: green;'>✅ Inserted $successCount sample routes successfully!</p>";
    
    showResults:
    
    // Show current routes
    echo "<h3>📋 Current Routes in Database:</h3>";
    $showRoutesSQL = "SELECT route_id, route_name, source, destination, distance_km, estimated_duration FROM routes ORDER BY route_id LIMIT 10";
    $routesResult = $con->query($showRoutesSQL);
    
    if ($routesResult && $routesResult->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 10px;'>Route ID</th>";
        echo "<th style='padding: 10px;'>Route Name</th>";
        echo "<th style='padding: 10px;'>Source</th>";
        echo "<th style='padding: 10px;'>Destination</th>";
        echo "<th style='padding: 10px;'>Distance</th>";
        echo "<th style='padding: 10px;'>Duration</th>";
        echo "</tr>";
        
        while ($route = $routesResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='padding: 8px;'><strong>" . htmlspecialchars($route['route_id']) . "</strong></td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($route['route_name']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($route['source']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($route['destination']) . "</td>";
            echo "<td style='padding: 8px;'>" . ($route['distance_km'] ? number_format($route['distance_km'], 1) . ' km' : 'N/A') . "</td>";
            echo "<td style='padding: 8px;'>" . ($route['estimated_duration'] ? htmlspecialchars($route['estimated_duration']) : 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Get total count
    $totalQuery = "SELECT COUNT(*) as total FROM routes";
    $totalResult = $con->query($totalQuery);
    $total = $totalResult->fetch_assoc()['total'];
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎉 Routes Management System Setup Complete!</h3>";
    echo "<p><strong>Database Status:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Routes table created and ready</li>";
    echo "<li>✅ Database indexes optimized</li>";
    echo "<li>✅ $total sample routes inserted</li>";
    echo "<li>✅ System ready for use</li>";
    echo "</ul>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='manage-routes.php' style='color: #155724; font-weight: bold;'>🔗 Access Admin Route Management</a></li>";
    echo "<li><a href='apply-pass.php' style='color: #155724; font-weight: bold;'>🔗 Test Enhanced Application Form</a></li>";
    echo "<li><a href='admin-dashboard.php' style='color: #155724; font-weight: bold;'>🔗 Go to Admin Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Error Setting Up Routes System</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Please check:</strong></p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL service is running</li>";
    echo "<li>Database 'bpmsdb' exists</li>";
    echo "<li>Database connection settings in includes/dbconnection.php</li>";
    echo "</ul>";
    echo "</div>";
}

$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Routes Table Setup - Bus Pass Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
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
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
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
