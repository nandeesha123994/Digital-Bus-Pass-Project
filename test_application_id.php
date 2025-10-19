<?php
/**
 * Test Application ID Generation
 * This script tests the Application ID generation function
 */

include('includes/dbconnection.php');

// Function to generate unique Application ID (same as in apply-pass.php)
function generateApplicationId($con) {
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
        
    } while ($result->num_rows > 0); // Keep generating until we get a unique ID
    
    return $applicationId;
}

echo "<h1>Application ID Generation Test</h1>";

echo "<h2>Testing Application ID Generation</h2>";

// Generate 10 test Application IDs
echo "<h3>Generated Application IDs:</h3>";
echo "<ul>";
for ($i = 1; $i <= 10; $i++) {
    $testId = generateApplicationId($con);
    echo "<li><strong>$testId</strong> - " . (preg_match('/^BPMS\d{4}\d{6}$/', $testId) ? "✅ Valid Format" : "❌ Invalid Format") . "</li>";
}
echo "</ul>";

// Test format validation
echo "<h3>Format Validation Tests:</h3>";
$testCases = [
    'BPMS2025123456' => true,
    'BPMS2025000001' => true,
    'BPMS2025999999' => true,
    'BPMS20251234567' => false, // Too long
    'BPMS202512345' => false,   // Too short
    'BPMS202A123456' => false,  // Invalid year
    'BPM2025123456' => false,   // Wrong prefix
    'bpms2025123456' => false,  // Lowercase
    '123456' => false,          // Numeric only
    '' => false                 // Empty
];

echo "<ul>";
foreach ($testCases as $testId => $expected) {
    $isValid = preg_match('/^BPMS\d{4}\d{6}$/', $testId);
    $result = ($isValid && $expected) || (!$isValid && !$expected);
    $status = $result ? "✅ Pass" : "❌ Fail";
    echo "<li><strong>$testId</strong> - Expected: " . ($expected ? "Valid" : "Invalid") . ", Got: " . ($isValid ? "Valid" : "Invalid") . " - $status</li>";
}
echo "</ul>";

// Check existing Application IDs in database
echo "<h3>Existing Application IDs in Database:</h3>";
$query = "SELECT id, application_id, applicant_name, created_at FROM bus_pass_applications ORDER BY id DESC LIMIT 10";
$result = $con->query($query);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Application ID</th><th>Applicant Name</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $appId = $row['application_id'] ? $row['application_id'] : '<em>Not Set</em>';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($appId) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['applicant_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No applications found in database.</p>";
}

// Test tracking functionality
echo "<h3>Test Tracking Functionality:</h3>";
echo "<p>You can test the tracking functionality by:</p>";
echo "<ul>";
echo "<li>Using any of the generated Application IDs above</li>";
echo "<li>Going to <a href='track-status.php' target='_blank'>Track Status Page</a></li>";
echo "<li>Entering an Application ID to test the search</li>";
echo "</ul>";

echo "<h3>Test Application Submission:</h3>";
echo "<p>To test the complete flow:</p>";
echo "<ol>";
echo "<li><a href='login.php' target='_blank'>Login</a> to your account</li>";
echo "<li><a href='apply-pass.php' target='_blank'>Apply for a Bus Pass</a></li>";
echo "<li>Complete the form and submit</li>";
echo "<li>Check the success message for your Application ID</li>";
echo "<li>Use the Application ID to track status</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a></p>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Application ID Test - Bus Pass Management System</title>
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
            max-width: 1000px;
        }
        h1 { color: #007bff; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        h3 { color: #555; }
        table { width: 100%; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        ul { margin: 10px 0; }
        li { margin: 5px 0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Content is generated by PHP above -->
    </div>
</body>
</html>
