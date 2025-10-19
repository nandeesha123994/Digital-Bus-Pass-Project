<?php
/**
 * Debug Script for Bus Pass Application Form Submission
 * This script will help identify why the form is not redirecting to payment page
 */

session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug - Application Form Submission</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .debug-section { background: white; margin: 15px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
    th { background: #e9ecef; }
</style></head><body>";

echo "<h1>🔍 Debug: Bus Pass Application Form Submission</h1>";

// Check if user is logged in
echo "<div class='debug-section'>";
echo "<h2>1. Session Check</h2>";
if (isset($_SESSION['uid'])) {
    echo "<div class='success'>✅ User is logged in (UID: {$_SESSION['uid']})</div>";
    
    // Get user details
    $userQuery = "SELECT * FROM users WHERE id = ?";
    $userStmt = $con->prepare($userQuery);
    $userStmt->bind_param("i", $_SESSION['uid']);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();
    
    if ($user) {
        echo "<div class='info'>User: {$user['full_name']} ({$user['email']})</div>";
    }
} else {
    echo "<div class='error'>❌ User is not logged in</div>";
    echo "<p><a href='login.php' class='btn'>Login First</a></p>";
}
echo "</div>";

// Check database tables
echo "<div class='debug-section'>";
echo "<h2>2. Database Tables Check</h2>";

$requiredTables = ['bus_pass_types', 'categories', 'routes', 'bus_pass_applications'];
foreach ($requiredTables as $table) {
    $result = $con->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $countResult = $con->query("SELECT COUNT(*) as count FROM $table");
        $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
        echo "<div class='success'>✅ $table table exists ($count records)</div>";
    } else {
        echo "<div class='error'>❌ $table table missing</div>";
    }
}
echo "</div>";

// Check required columns
echo "<div class='debug-section'>";
echo "<h2>3. Database Columns Check</h2>";

$columnChecks = [
    'bus_pass_applications' => ['application_id', 'photo_path', 'id_proof_type', 'id_proof_number', 'email'],
    'bus_pass_types' => ['amount']
];

foreach ($columnChecks as $table => $columns) {
    echo "<h4>$table:</h4>";
    $result = $con->query("DESCRIBE $table");
    $existingColumns = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existingColumns[] = $row['Field'];
        }
        
        foreach ($columns as $column) {
            if (in_array($column, $existingColumns)) {
                echo "<div class='success'>✅ $column column exists</div>";
            } else {
                echo "<div class='error'>❌ $column column missing</div>";
            }
        }
    }
}
echo "</div>";

// Check form data if submitted
echo "<div class='debug-section'>";
echo "<h2>4. Form Submission Debug</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='info'>📝 Form was submitted via POST</div>";
    
    echo "<h4>POST Data:</h4>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h4>FILES Data:</h4>";
    echo "<pre>" . print_r($_FILES, true) . "</pre>";
    
    // Check if submit button was clicked
    if (isset($_POST['submit'])) {
        echo "<div class='success'>✅ Submit button was clicked</div>";
    } else {
        echo "<div class='error'>❌ Submit button not detected in POST data</div>";
    }
    
    // Validate required fields
    $requiredFields = ['name', 'dob', 'gender', 'phone', 'address', 'source', 'destination', 'pass_type_id', 'category_id'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (empty($missingFields)) {
        echo "<div class='success'>✅ All required fields are present</div>";
    } else {
        echo "<div class='error'>❌ Missing fields: " . implode(', ', $missingFields) . "</div>";
    }
    
    // Check file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        echo "<div class='success'>✅ Photo file uploaded successfully</div>";
        echo "<div class='info'>File: {$_FILES['photo']['name']} ({$_FILES['photo']['size']} bytes)</div>";
    } else {
        echo "<div class='error'>❌ Photo upload issue</div>";
        if (isset($_FILES['photo'])) {
            echo "<div class='warning'>Upload error code: {$_FILES['photo']['error']}</div>";
        }
    }
    
} else {
    echo "<div class='warning'>⚠️ No form submission detected</div>";
}
echo "</div>";

// Test form
echo "<div class='debug-section'>";
echo "<h2>5. Test Form</h2>";
echo "<p>Use this simplified form to test the submission process:</p>";

echo "<form method='post' action='debug-apply-form.php' enctype='multipart/form-data'>";
echo "<table>";
echo "<tr><td>Name:</td><td><input type='text' name='name' value='Test User' required></td></tr>";
echo "<tr><td>Date of Birth:</td><td><input type='date' name='dob' value='1990-01-01' required></td></tr>";
echo "<tr><td>Gender:</td><td><select name='gender' required><option value='Male'>Male</option><option value='Female'>Female</option></select></td></tr>";
echo "<tr><td>Phone:</td><td><input type='text' name='phone' value='1234567890' required></td></tr>";
echo "<tr><td>Address:</td><td><textarea name='address' required>Test Address</textarea></td></tr>";
echo "<tr><td>Source:</td><td><input type='text' name='source' value='Test Source' required></td></tr>";
echo "<tr><td>Destination:</td><td><input type='text' name='destination' value='Test Destination' required></td></tr>";
echo "<tr><td>Pass Type:</td><td><select name='pass_type_id' required>";

// Get pass types
$passTypesResult = $con->query("SELECT * FROM bus_pass_types ORDER BY type_name");
if ($passTypesResult) {
    while ($passType = $passTypesResult->fetch_assoc()) {
        echo "<option value='{$passType['id']}'>{$passType['type_name']} - ₹{$passType['amount']}</option>";
    }
}
echo "</select></td></tr>";

echo "<tr><td>Category:</td><td><select name='category_id' required>";
// Get categories
$categoriesResult = $con->query("SELECT * FROM categories ORDER BY category_name");
if ($categoriesResult) {
    while ($category = $categoriesResult->fetch_assoc()) {
        echo "<option value='{$category['id']}'>{$category['category_name']}</option>";
    }
}
echo "</select></td></tr>";

echo "<tr><td>ID Proof Type:</td><td><select name='id_proof_type' required><option value='Aadhar'>Aadhar</option><option value='PAN'>PAN</option></select></td></tr>";
echo "<tr><td>ID Proof Number:</td><td><input type='text' name='id_proof_number' value='123456789012' required></td></tr>";
echo "<tr><td>Photo:</td><td><input type='file' name='photo' accept='image/*' required></td></tr>";
echo "</table>";
echo "<br><button type='submit' name='submit' class='btn'>🧪 Test Submit</button>";
echo "</form>";
echo "</div>";

// Quick links
echo "<div class='debug-section'>";
echo "<h2>6. Quick Actions</h2>";
echo "<a href='apply-pass.php' class='btn'>📝 Go to Application Form</a>";
echo "<a href='user-dashboard.php' class='btn'>🏠 User Dashboard</a>";
echo "<a href='payment.php?application_id=1' class='btn'>💳 Test Payment Page</a>";
echo "<a href='fix-all-errors.php' class='btn'>🔧 Run Database Fix</a>";
echo "</div>";

// Check error logs
echo "<div class='debug-section'>";
echo "<h2>7. Recent Error Logs</h2>";
if (file_exists('logs/error.log')) {
    $errorLog = file_get_contents('logs/error.log');
    $lines = array_filter(explode("\n", $errorLog));
    $recentErrors = array_slice($lines, -10); // Last 10 errors
    
    if (!empty($recentErrors)) {
        echo "<pre style='max-height: 300px; overflow-y: auto;'>";
        foreach ($recentErrors as $error) {
            echo htmlspecialchars($error) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<div class='success'>✅ No recent errors found</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Error log file not found</div>";
}
echo "</div>";

echo "</body></html>";
?>
