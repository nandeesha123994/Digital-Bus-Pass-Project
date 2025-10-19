<?php
/**
 * Fix Application ID Error
 * This script will add the application_id column and fix any related issues
 */

include('includes/dbconnection.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Application ID Error - Bus Pass Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 1000px; }
        h1 { color: #dc3545; }
        h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .step { background: #e9ecef; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Fix Application ID Error</h1>
        <p>This script will resolve the 'Unknown column application_id' error by adding the missing column to the database.</p>
        <hr>";

// Function to generate unique Application ID
function generateApplicationId($con) {
    do {
        $year = date('Y');
        $randomNumber = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $applicationId = "BPMS{$year}{$randomNumber}";
        
        // Check if this ID already exists
        $checkQuery = "SELECT id FROM bus_pass_applications WHERE application_id = ?";
        $stmt = $con->prepare($checkQuery);
        if ($stmt) {
            $stmt->bind_param("s", $applicationId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                break; // Unique ID found
            }
        } else {
            break; // If column doesn't exist, just return the ID
        }
    } while (true);
    
    return $applicationId;
}

try {
    echo "<div class='step'>";
    echo "<h2>Step 1: Checking Current Database Structure</h2>";
    
    // Check if table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'bus_pass_applications'";
    $tableResult = $con->query($tableCheckQuery);
    
    if ($tableResult->num_rows == 0) {
        echo "<span class='error'>❌ Error: bus_pass_applications table does not exist!</span><br>";
        echo "<p>Please run the database setup script first: <a href='setup_database.php'>Setup Database</a></p>";
        exit;
    } else {
        echo "<span class='success'>✅ bus_pass_applications table exists</span><br>";
    }
    
    // Check if application_id column exists
    $columnCheckQuery = "SHOW COLUMNS FROM bus_pass_applications LIKE 'application_id'";
    $columnResult = $con->query($columnCheckQuery);
    
    if ($columnResult->num_rows == 0) {
        echo "<span class='warning'>⚠️ application_id column does not exist</span><br>";
        $needsColumn = true;
    } else {
        echo "<span class='success'>✅ application_id column already exists</span><br>";
        $needsColumn = false;
    }
    echo "</div>";
    
    if ($needsColumn) {
        echo "<div class='step'>";
        echo "<h2>Step 2: Adding application_id Column</h2>";
        
        $alterQuery = "ALTER TABLE bus_pass_applications ADD COLUMN application_id VARCHAR(20) UNIQUE AFTER id";
        
        if ($con->query($alterQuery)) {
            echo "<span class='success'>✅ Successfully added application_id column</span><br>";
        } else {
            echo "<span class='error'>❌ Error adding column: " . $con->error . "</span><br>";
            echo "<p>You can try running this SQL command manually in phpMyAdmin:</p>";
            echo "<code>ALTER TABLE bus_pass_applications ADD COLUMN application_id VARCHAR(20) UNIQUE AFTER id;</code>";
            exit;
        }
        echo "</div>";
    }
    
    echo "<div class='step'>";
    echo "<h2>Step 3: Updating Existing Records</h2>";
    
    // Check for records without Application IDs
    $selectQuery = "SELECT id, applicant_name FROM bus_pass_applications WHERE application_id IS NULL OR application_id = ''";
    $existingRecords = $con->query($selectQuery);
    
    if ($existingRecords && $existingRecords->num_rows > 0) {
        echo "<span class='info'>📝 Found {$existingRecords->num_rows} records without Application IDs</span><br>";
        echo "<p>Updating records...</p>";
        
        $updateCount = 0;
        while ($row = $existingRecords->fetch_assoc()) {
            $applicationId = generateApplicationId($con);
            $updateQuery = "UPDATE bus_pass_applications SET application_id = ? WHERE id = ?";
            $stmt = $con->prepare($updateQuery);
            $stmt->bind_param("si", $applicationId, $row['id']);
            
            if ($stmt->execute()) {
                echo "✅ Updated record ID {$row['id']} ({$row['applicant_name']}) → <strong>$applicationId</strong><br>";
                $updateCount++;
            } else {
                echo "❌ Failed to update record ID {$row['id']}: " . $con->error . "<br>";
            }
        }
        echo "<span class='success'>✅ Updated $updateCount records successfully</span><br>";
    } else {
        echo "<span class='success'>✅ All records already have Application IDs</span><br>";
    }
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Step 4: Verification</h2>";
    
    // Show current table structure
    echo "<h3>Current Table Structure:</h3>";
    $structureQuery = "DESCRIBE bus_pass_applications";
    $structureResult = $con->query($structureQuery);
    
    if ($structureResult) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structureResult->fetch_assoc()) {
            $highlight = ($row['Field'] == 'application_id') ? 'style="background: #d4edda; font-weight: bold;"' : '';
            echo "<tr $highlight>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show sample records
    echo "<h3>Sample Records with Application IDs:</h3>";
    $sampleQuery = "SELECT id, application_id, applicant_name, created_at FROM bus_pass_applications ORDER BY id DESC LIMIT 5";
    $sampleResult = $con->query($sampleQuery);
    
    if ($sampleResult && $sampleResult->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Application ID</th><th>Applicant Name</th><th>Created At</th></tr>";
        while ($row = $sampleResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['application_id']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['applicant_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No records found in the database.</p>";
    }
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>🎉 Fix Completed Successfully!</h2>";
    echo "<p>The application_id column has been added and all records have been updated.</p>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li><a href='apply-pass.php'>Submit a new bus pass application</a> (will generate BPMS format ID)</li>";
    echo "<li><a href='track-status.php'>Track application status</a> (supports both formats)</li>";
    echo "<li><a href='user-dashboard.php'>View your dashboard</a> (shows formatted IDs)</li>";
    echo "<li><a href='test_application_id.php'>Test the Application ID system</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='step'>";
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
    echo "<p>Please check your database connection and try again.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a> | <a href='apply-pass.php'>Apply for Pass</a> | <a href='track-status.php'>Track Status</a></p>";
echo "</div></body></html>";
?>
