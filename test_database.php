<?php
/**
 * Database Test Script
 * This script tests if all required tables exist
 */

include('includes/dbconnection.php');

echo "<h2>Database Connection Test</h2>";

// Test connection
if ($con->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $con->connect_error . "</p>";
    exit();
} else {
    echo "<p style='color: green;'>✅ Connected to database successfully!</p>";
}

// Check if required tables exist
$requiredTables = [
    'users',
    'bus_pass_types', 
    'bus_pass_applications',
    'payments',
    'settings'
];

echo "<h3>Checking Required Tables:</h3>";

foreach ($requiredTables as $table) {
    $result = $con->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' is missing</p>";
    }
}

// If bus_pass_applications table exists, test a simple query
$result = $con->query("SHOW TABLES LIKE 'bus_pass_applications'");
if ($result->num_rows > 0) {
    echo "<h3>Testing bus_pass_applications table:</h3>";
    $testQuery = "SELECT COUNT(*) as count FROM bus_pass_applications";
    $result = $con->query($testQuery);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Query successful! Found " . $row['count'] . " applications in the table.</p>";
    } else {
        echo "<p style='color: red;'>❌ Query failed: " . $con->error . "</p>";
    }
}

echo "<br><a href='index.php'>Go to Home Page</a>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Test - Bus Pass Management System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background: #f8f9fa; 
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bus Pass Management System - Database Test</h1>
    </div>
</body>
</html>
