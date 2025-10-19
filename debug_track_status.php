<?php
/**
 * Debug Track Status - Check database structure and test queries
 */

include('includes/dbconnection.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Track Status - Bus Pass Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 1200px; }
        h1 { color: #007bff; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Debug Track Status System</h1>
        <p>This script helps debug the track status functionality and database structure.</p>
        <hr>";

try {
    echo "<h2>1. Database Table Structure</h2>";
    
    // Show bus_pass_applications table structure
    echo "<h3>bus_pass_applications Table Structure:</h3>";
    $structureQuery = "DESCRIBE bus_pass_applications";
    $structureResult = $con->query($structureQuery);
    
    if ($structureResult) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structureResult->fetch_assoc()) {
            $highlight = ($row['Field'] == 'application_id') ? 'style="background: #d4edda;"' : '';
            echo "<tr $highlight>";
            echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>2. Sample Data</h2>";
    
    // Show sample records
    echo "<h3>Recent Applications:</h3>";
    $sampleQuery = "SELECT ba.id, ba.application_id, ba.applicant_name, ba.source, ba.destination, 
                           ba.status, ba.payment_status, ba.application_date, ba.created_at,
                           bpt.type_name, u.full_name, u.email, u.phone
                    FROM bus_pass_applications ba
                    LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                    LEFT JOIN users u ON ba.user_id = u.id
                    ORDER BY ba.id DESC LIMIT 5";
    
    $sampleResult = $con->query($sampleQuery);
    
    if ($sampleResult && $sampleResult->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>App ID</th><th>Applicant</th><th>User Name</th><th>Email</th><th>Phone</th><th>Pass Type</th><th>Route</th><th>Status</th><th>Date</th></tr>";
        while ($row = $sampleResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['application_id'] ?? 'NULL') . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['applicant_name'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['phone'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['type_name'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars(($row['source'] ?? '') . ' → ' . ($row['destination'] ?? '')) . "</td>";
            echo "<td>" . htmlspecialchars($row['status'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['application_date'] ?? $row['created_at'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No records found in the database.</p>";
    }
    
    echo "<h2>3. Test Queries</h2>";
    
    // Test the exact query used in track-status.php
    echo "<h3>Testing Track Status Query:</h3>";
    
    if ($sampleResult && $sampleResult->num_rows > 0) {
        // Reset the result pointer
        $sampleResult->data_seek(0);
        $testRow = $sampleResult->fetch_assoc();
        
        if ($testRow) {
            $testId = $testRow['id'];
            $testAppId = $testRow['application_id'];
            
            echo "<p><strong>Testing with:</strong></p>";
            echo "<ul>";
            echo "<li>Numeric ID: $testId</li>";
            if ($testAppId) {
                echo "<li>Application ID: $testAppId</li>";
            } else {
                echo "<li>Application ID: Not set</li>";
            }
            echo "</ul>";
            
            // Test numeric ID query
            echo "<h4>Query by Numeric ID ($testId):</h4>";
            $testQuery = "SELECT ba.*, u.full_name, u.email, u.phone, bpt.type_name as pass_type
                         FROM bus_pass_applications ba
                         JOIN users u ON ba.user_id = u.id
                         LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                         WHERE ba.id = ?";
            
            $stmt = $con->prepare($testQuery);
            $stmt->bind_param("i", $testId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
                echo "<span class='success'>✅ Query successful</span><br>";
                echo "<strong>Retrieved data:</strong><br>";
                echo "<pre>";
                foreach ($data as $key => $value) {
                    echo "$key: " . ($value ?? 'NULL') . "\n";
                }
                echo "</pre>";
            } else {
                echo "<span class='error'>❌ No results found</span><br>";
            }
            
            // Test Application ID query if available
            if ($testAppId) {
                echo "<h4>Query by Application ID ($testAppId):</h4>";
                $testQuery2 = "SELECT ba.*, u.full_name, u.email, u.phone, bpt.type_name as pass_type
                              FROM bus_pass_applications ba
                              JOIN users u ON ba.user_id = u.id
                              LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                              WHERE ba.application_id = ?";
                
                $stmt2 = $con->prepare($testQuery2);
                $stmt2->bind_param("s", $testAppId);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                
                if ($result2->num_rows > 0) {
                    echo "<span class='success'>✅ Application ID query successful</span><br>";
                } else {
                    echo "<span class='error'>❌ Application ID query failed</span><br>";
                }
            }
        }
    }
    
    echo "<h2>4. Field Mapping Check</h2>";
    echo "<p>Checking if all required fields exist in the query results:</p>";
    
    $requiredFields = [
        'id' => 'Application ID (numeric)',
        'application_id' => 'Application ID (formatted)',
        'full_name' => 'User full name',
        'email' => 'User email',
        'phone' => 'User phone',
        'pass_type' => 'Pass type name',
        'source' => 'From location',
        'destination' => 'To location',
        'status' => 'Application status',
        'payment_status' => 'Payment status',
        'application_date' => 'Application date'
    ];
    
    echo "<table>";
    echo "<tr><th>Field</th><th>Description</th><th>Status</th></tr>";
    
    if (isset($data)) {
        foreach ($requiredFields as $field => $description) {
            $exists = array_key_exists($field, $data);
            $hasValue = $exists && !empty($data[$field]);
            $status = $exists ? ($hasValue ? '✅ Exists with value' : '⚠️ Exists but empty') : '❌ Missing';
            
            echo "<tr>";
            echo "<td><strong>$field</strong></td>";
            echo "<td>$description</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<h2>5. Recommendations</h2>";
    echo "<ul>";
    echo "<li><strong>Test the track status page:</strong> <a href='track-status.php' target='_blank'>Track Status</a></li>";
    echo "<li><strong>Try with numeric ID:</strong> Use one of the IDs from the sample data above</li>";
    if (isset($testAppId) && $testAppId) {
        echo "<li><strong>Try with Application ID:</strong> Use $testAppId</li>";
    }
    echo "<li><strong>Check error logs:</strong> Look for any PHP errors in the browser console</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a> | <a href='track-status.php'>Track Status</a> | <a href='fix_application_id_error.php'>Fix Application ID</a></p>";
echo "</div></body></html>";
?>
