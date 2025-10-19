<?php
// Debug version of apply-pass.php to see what's happening
session_start();
include('includes/dbconnection.php');
include('includes/config.php');

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit();
}

echo "<h2>Debug: Apply Pass Form Analysis</h2>";

// Check table structure
echo "<h3>1. Table Structure Analysis</h3>";
$tableColumns = [];
$columnsResult = $con->query("DESCRIBE bus_pass_applications");
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

while ($column = $columnsResult->fetch_assoc()) {
    $tableColumns[] = $column['Field'];
    echo "<tr>";
    echo "<td><strong>" . $column['Field'] . "</strong></td>";
    echo "<td>" . $column['Type'] . "</td>";
    echo "<td>" . $column['Null'] . "</td>";
    echo "<td>" . $column['Key'] . "</td>";
    echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>2. Column Existence Check</h3>";
$hasApplicationId = in_array('application_id', $tableColumns);
$hasPhotoPath = in_array('photo_path', $tableColumns);
$hasEmail = in_array('email', $tableColumns);

echo "<ul>";
echo "<li><strong>application_id exists:</strong> " . ($hasApplicationId ? "✅ YES" : "❌ NO") . "</li>";
echo "<li><strong>photo_path exists:</strong> " . ($hasPhotoPath ? "✅ YES" : "❌ NO") . "</li>";
echo "<li><strong>email exists:</strong> " . ($hasEmail ? "✅ YES" : "❌ NO") . "</li>";
echo "</ul>";

echo "<h3>3. Application ID Generation Test</h3>";
if ($hasApplicationId) {
    echo "<p style='color: green;'>✅ application_id column exists - Application ID will be generated</p>";
    
    // Test the generation function
    function generateTestApplicationId($con) {
        do {
            $year = date('Y');
            $randomNumber = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $applicationId = "BPMS{$year}{$randomNumber}";
            
            // Check if this ID already exists
            $checkQuery = "SELECT id FROM bus_pass_applications WHERE application_id = ?";
            $stmt = $con->prepare($checkQuery);
            $stmt->bind_param("s", $applicationId);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0);
        
        return $applicationId;
    }
    
    $testId = generateTestApplicationId($con);
    echo "<p><strong>Sample Generated ID:</strong> <code style='background: #f0f0f0; padding: 5px;'>{$testId}</code></p>";
} else {
    echo "<p style='color: red;'>❌ application_id column does NOT exist - Application ID will NOT be generated</p>";
    echo "<p><strong>Solution:</strong> Run the fix script to add the application_id column</p>";
}

echo "<h3>4. Fix Application ID Column</h3>";
if (!$hasApplicationId) {
    echo "<p>Adding application_id column...</p>";
    
    $sql = "ALTER TABLE bus_pass_applications ADD COLUMN application_id VARCHAR(50) UNIQUE AFTER id";
    if ($con->query($sql) === TRUE) {
        echo "<p style='color: green;'>✅ Successfully added application_id column</p>";
        $hasApplicationId = true;
    } else {
        echo "<p style='color: red;'>❌ Failed to add application_id column: " . $con->error . "</p>";
    }
}

echo "<h3>5. Fix Photo Path Column</h3>";
if (!$hasPhotoPath) {
    echo "<p>Adding photo_path column...</p>";
    
    $sql = "ALTER TABLE bus_pass_applications ADD COLUMN photo_path VARCHAR(255) AFTER destination";
    if ($con->query($sql) === TRUE) {
        echo "<p style='color: green;'>✅ Successfully added photo_path column</p>";
        $hasPhotoPath = true;
    } else {
        echo "<p style='color: red;'>❌ Failed to add photo_path column: " . $con->error . "</p>";
    }
}

echo "<h3>6. Fix Email Column</h3>";
if (!$hasEmail) {
    echo "<p>Adding email column...</p>";
    
    $sql = "ALTER TABLE bus_pass_applications ADD COLUMN email VARCHAR(100) AFTER phone";
    if ($con->query($sql) === TRUE) {
        echo "<p style='color: green;'>✅ Successfully added email column</p>";
        $hasEmail = true;
    } else {
        echo "<p style='color: red;'>❌ Failed to add email column: " . $con->error . "</p>";
    }
}

echo "<h3>7. Final Table Structure</h3>";
$finalColumnsResult = $con->query("DESCRIBE bus_pass_applications");
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Field</th><th>Type</th><th>Status</th></tr>";

$finalColumns = [];
while ($column = $finalColumnsResult->fetch_assoc()) {
    $finalColumns[] = $column['Field'];
    $status = "✅ OK";
    if ($column['Field'] == 'application_id' || $column['Field'] == 'photo_path' || $column['Field'] == 'email') {
        $status = "✅ REQUIRED";
    }
    
    echo "<tr>";
    echo "<td><strong>" . $column['Field'] . "</strong></td>";
    echo "<td>" . $column['Type'] . "</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>8. Test Application ID Generation (Final)</h3>";
$finalHasApplicationId = in_array('application_id', $finalColumns);

if ($finalHasApplicationId) {
    echo "<p style='color: green;'>✅ Application ID generation is now ENABLED</p>";
    
    // Generate 3 test IDs
    echo "<p><strong>Sample Generated Application IDs:</strong></p>";
    echo "<ul>";
    for ($i = 1; $i <= 3; $i++) {
        $testId = generateTestApplicationId($con);
        echo "<li><code style='background: #e8f5e8; padding: 5px; font-weight: bold;'>{$testId}</code></li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ Application ID generation is still DISABLED</p>";
}

echo "<h3>9. Summary</h3>";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<h4>System Status:</h4>";
echo "<ul>";
echo "<li><strong>application_id column:</strong> " . ($finalHasApplicationId ? "✅ EXISTS" : "❌ MISSING") . "</li>";
echo "<li><strong>photo_path column:</strong> " . (in_array('photo_path', $finalColumns) ? "✅ EXISTS" : "❌ MISSING") . "</li>";
echo "<li><strong>email column:</strong> " . (in_array('email', $finalColumns) ? "✅ EXISTS" : "❌ MISSING") . "</li>";
echo "<li><strong>Application ID Generation:</strong> " . ($finalHasApplicationId ? "✅ ENABLED" : "❌ DISABLED") . "</li>";
echo "</ul>";

if ($finalHasApplicationId) {
    echo "<p style='color: green; font-weight: bold;'>🎉 The apply-pass.php form should now generate Application IDs correctly!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ There are still issues that need to be resolved.</p>";
}
echo "</div>";

echo "<h3>10. Next Steps</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<ol>";
echo "<li><strong>Test the Apply Pass form:</strong> <a href='apply-pass.php' target='_blank'>apply-pass.php</a></li>";
echo "<li><strong>Check User Dashboard:</strong> <a href='user-dashboard.php' target='_blank'>user-dashboard.php</a></li>";
echo "<li><strong>Track Applications:</strong> <a href='track-application.php' target='_blank'>track-application.php</a></li>";
echo "</ol>";
echo "</div>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; }";
echo "h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }";
echo "h3 { color: #007bff; margin-top: 30px; }";
echo "table { margin: 10px 0; }";
echo "th { background: #f8f9fa; padding: 8px; text-align: left; }";
echo "td { padding: 8px; }";
echo "code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }";
echo "</style>";
?>

<div style="text-align: center; margin-top: 30px; padding: 20px; background: #e8f5e8; border-radius: 10px;">
    <h3>🔧 Debug Complete</h3>
    <p>This debug script has analyzed and fixed the Application ID generation issue.</p>
    <a href="apply-pass.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">📝 Test Apply Pass</a>
    <a href="user-dashboard.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">👤 User Dashboard</a>
    <a href="index.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">🏠 Homepage</a>
</div>
