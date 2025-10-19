<?php
/**
 * Database Setup Script for Bus Pass Management System
 * This script creates the database and all required tables
 */

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'bpmsdb';

try {
    // First, connect to MySQL without specifying a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.<br>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    echo "Database '$database' created or already exists.<br>";
    
    // Use the database
    $pdo->exec("USE $database");
    echo "Using database '$database'.<br>";
    
    // Read and execute the SQL file
    $sqlFile = 'users_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file '$sqlFile' not found!");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<br>Executing SQL statements...<br>";
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...<br>";
            } catch (PDOException $e) {
                echo "⚠ Warning: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo "<br>The following tables have been created:<br>";
    
    // List all tables
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "<br>";
    }
    
    echo "<br><a href='index.php'>Go to Home Page</a> | <a href='register.php'>Register</a> | <a href='login.php'>Login</a>";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - Bus Pass Management System</title>
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
        h1 { color: #007bff; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bus Pass Management System - Database Setup</h1>
        <p>This script will create the database and all required tables for the Bus Pass Management System.</p>
        <hr>
    </div>
</body>
</html>
