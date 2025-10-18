<?php
/**
 * Create Routes Table for Bus Pass Management System
 * This script creates the routes table and inserts sample data
 */

include('includes/dbconnection.php');

echo "<h2>Setting up Routes Management System...</h2>";

try {
    // Create routes table
    $createTableQuery = "
    CREATE TABLE IF NOT EXISTS routes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        route_id VARCHAR(20) NOT NULL UNIQUE,
        route_name VARCHAR(100) NOT NULL,
        source VARCHAR(100) NOT NULL,
        destination VARCHAR(100) NOT NULL,
        distance_km DECIMAL(6,2) DEFAULT NULL,
        estimated_duration VARCHAR(20) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_source (source),
        INDEX idx_destination (destination),
        INDEX idx_source_dest (source, destination),
        INDEX idx_route_id (route_id),
        INDEX idx_active (is_active)
    )";

    if ($con->query($createTableQuery)) {
        echo "<p style='color: green;'>✅ Routes table created successfully!</p>";
    } else {
        throw new Exception("Error creating routes table: " . $con->error);
    }

    // Check if table is empty and insert sample data
    $countQuery = "SELECT COUNT(*) as count FROM routes";
    $countResult = $con->query($countQuery);
    $count = $countResult->fetch_assoc()['count'];

    if ($count == 0) {
        echo "<p>📝 Inserting sample route data...</p>";
        
        // Sample routes data for different cities and areas
        $sampleRoutes = [
            ['R001', 'City Center Express', 'Bangalore Central', 'Electronic City', '25.5', '45 mins'],
            ['R002', 'Tech Park Shuttle', 'Whitefield', 'Koramangala', '18.2', '35 mins'],
            ['R003', 'Airport Connect', 'Majestic', 'Kempegowda Airport', '40.0', '60 mins'],
            ['R004', 'University Route', 'Jayanagar', 'Banashankari', '12.8', '25 mins'],
            ['R005', 'Mall Circuit', 'Brigade Road', 'Forum Mall', '8.5', '20 mins'],
            ['R006', 'IT Corridor', 'Silk Board', 'Marathahalli', '15.3', '30 mins'],
            ['R007', 'Metro Feeder', 'Indiranagar', 'MG Road Metro', '6.2', '15 mins'],
            ['R008', 'Hospital Route', 'Rajajinagar', 'Manipal Hospital', '9.7', '22 mins'],
            ['R009', 'Market Express', 'KR Market', 'Commercial Street', '7.4', '18 mins'],
            ['R010', 'Suburb Connect', 'Hebbal', 'Yelahanka', '14.6', '28 mins'],
            ['R011', 'Lake View', 'Ulsoor Lake', 'Lalbagh', '11.3', '24 mins'],
            ['R012', 'Business District', 'UB City Mall', 'Cubbon Park', '4.8', '12 mins'],
            ['R013', 'Education Hub', 'IISc', 'Indian Institute of Management', '16.9', '32 mins'],
            ['R014', 'Shopping Circuit', 'Chickpet', 'Avenue Road', '5.1', '14 mins'],
            ['R015', 'Residential Route', 'RT Nagar', 'Sadashivanagar', '8.9', '19 mins'],
            ['R016', 'Industrial Zone', 'Peenya', 'Rajajinagar Industrial Area', '13.2', '26 mins'],
            ['R017', 'Heritage Trail', 'Bangalore Palace', 'Tipu Sultan Fort', '12.5', '25 mins'],
            ['R018', 'Sports Complex', 'Kanteerava Stadium', 'Sree Kanteerava Stadium', '3.2', '8 mins'],
            ['R019', 'Night Service', 'Koramangala', 'HSR Layout', '7.8', '16 mins'],
            ['R020', 'Express Highway', 'Electronic City', 'Hosur Road', '22.1', '38 mins']
        ];

        $insertQuery = "INSERT INTO routes (route_id, route_name, source, destination, distance_km, estimated_duration) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($insertQuery);

        $successCount = 0;
        foreach ($sampleRoutes as $route) {
            $stmt->bind_param('ssssds', $route[0], $route[1], $route[2], $route[3], $route[4], $route[5]);
            if ($stmt->execute()) {
                $successCount++;
            }
        }

        echo "<p style='color: green;'>✅ Inserted $successCount sample routes successfully!</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Routes table already contains $count routes.</p>";
    }

    // Verify table structure
    echo "<h3>📊 Routes Table Structure:</h3>";
    $describeQuery = "DESCRIBE routes";
    $describeResult = $con->query($describeQuery);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $describeResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Show sample data
    echo "<h3>📋 Sample Routes Data:</h3>";
    $sampleQuery = "SELECT route_id, route_name, source, destination, distance_km, estimated_duration FROM routes LIMIT 10";
    $sampleResult = $con->query($sampleQuery);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Route ID</th><th>Route Name</th><th>Source</th><th>Destination</th><th>Distance</th><th>Duration</th></tr>";
    while ($row = $sampleResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['route_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['route_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['source']) . "</td>";
        echo "<td>" . htmlspecialchars($row['destination']) . "</td>";
        echo "<td>" . htmlspecialchars($row['distance_km']) . " km</td>";
        echo "<td>" . htmlspecialchars($row['estimated_duration']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎉 Routes Management System Setup Complete!</h4>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Routes table created with proper indexes</li>";
    echo "<li>✅ Sample route data inserted</li>";
    echo "<li>🔄 Ready to create admin management page</li>";
    echo "<li>🔄 Ready to update apply-pass form</li>";
    echo "</ul>";
    echo "<p><strong>Access:</strong></p>";
    echo "<ul>";
    echo "<li><a href='manage-routes.php' target='_blank'>Admin Route Management</a> (to be created)</li>";
    echo "<li><a href='apply-pass.php' target='_blank'>Updated Application Form</a> (to be updated)</li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Routes Table Setup - Bus Pass Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>🚌 Routes Management System Setup</h1>
    <p><a href="admin-dashboard.php">← Back to Admin Dashboard</a></p>
</body>
</html>
