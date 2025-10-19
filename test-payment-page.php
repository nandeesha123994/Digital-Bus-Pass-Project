<?php
/**
 * Test Payment Page Access
 * Check if payment.php is working correctly
 */

session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Payment Page</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .test-section { background: white; margin: 15px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
    th { background: #e9ecef; }
</style></head><body>";

echo "<h1>🧪 Test Payment Page Access</h1>";

// Set test user session if not logged in
if (!isset($_SESSION['uid'])) {
    $_SESSION['uid'] = 1; // Test user ID
    echo "<div class='warning'>⚠️ Set test user session (UID: 1)</div>";
}

// Test 1: Check if payment.php file exists
echo "<div class='test-section'>";
echo "<h2>1. Payment File Check</h2>";
if (file_exists('payment.php')) {
    echo "<div class='success'>✅ payment.php file exists</div>";
} else {
    echo "<div class='error'>❌ payment.php file not found</div>";
}
echo "</div>";

// Test 2: Check database for applications
echo "<div class='test-section'>";
echo "<h2>2. Application Records Check</h2>";

$applicationsQuery = "SELECT id, application_id, applicant_name, amount, status, payment_status FROM bus_pass_applications ORDER BY id DESC LIMIT 5";
$applicationsResult = $con->query($applicationsQuery);

if ($applicationsResult && $applicationsResult->num_rows > 0) {
    echo "<div class='success'>✅ Found {$applicationsResult->num_rows} recent applications</div>";
    echo "<table>";
    echo "<tr><th>ID</th><th>App ID</th><th>Name</th><th>Amount</th><th>Status</th><th>Payment</th><th>Test Link</th></tr>";
    
    while ($app = $applicationsResult->fetch_assoc()) {
        $statusClass = $app['payment_status'] === 'Paid' ? 'success' : 'warning';
        echo "<tr>";
        echo "<td>{$app['id']}</td>";
        echo "<td>{$app['application_id']}</td>";
        echo "<td>" . htmlspecialchars($app['applicant_name']) . "</td>";
        echo "<td>₹{$app['amount']}</td>";
        echo "<td>{$app['status']}</td>";
        echo "<td class='$statusClass'>{$app['payment_status']}</td>";
        echo "<td><a href='payment.php?application_id={$app['id']}' target='_blank' class='btn'>Test Payment</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠️ No applications found in database</div>";
}
echo "</div>";

// Test 3: Create a test application for payment testing
echo "<div class='test-section'>";
echo "<h2>3. Create Test Application</h2>";

if (isset($_POST['create_test_app'])) {
    try {
        // Generate test application
        $applicationId = "TEST" . date('YmdHis');
        $amount = 1200.00;
        
        $insertQuery = "INSERT INTO bus_pass_applications (
            user_id, application_id, applicant_name, date_of_birth, gender, phone, address,
            source, destination, pass_type_id, category_id, amount, 
            photo_path, id_proof_type, id_proof_number, status, application_date, email
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)";
        
        $stmt = $con->prepare($insertQuery);
        $stmt->bind_param(
            "issssssssiidssss",
            $_SESSION['uid'],
            $applicationId,
            "Test User for Payment",
            "1990-01-01",
            "Male",
            "1234567890",
            "Test Address",
            "Central Bus Station",
            "City Mall",
            1, // Pass type ID
            1, // Category ID
            $amount,
            "uploads/test_photo.jpg",
            "Aadhaar Card",
            "123456789012",
            "test@example.com"
        );
        
        if ($stmt->execute()) {
            $newAppId = $stmt->insert_id;
            echo "<div class='success'>✅ Test application created successfully!</div>";
            echo "<div class='info'>Application ID: $newAppId<br>Application Number: $applicationId<br>Amount: ₹$amount</div>";
            echo "<a href='payment.php?application_id=$newAppId' target='_blank' class='btn'>🔗 Test Payment Page</a>";
        } else {
            echo "<div class='error'>❌ Failed to create test application: " . $stmt->error . "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error creating test application: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<p>Create a test application to test the payment page:</p>";
    echo "<form method='post'>";
    echo "<button type='submit' name='create_test_app' class='btn'>🧪 Create Test Application</button>";
    echo "</form>";
}
echo "</div>";

// Test 4: Direct payment page access test
echo "<div class='test-section'>";
echo "<h2>4. Direct Payment Page Tests</h2>";
echo "<p>Test different scenarios:</p>";

echo "<a href='payment.php?application_id=1' target='_blank' class='btn'>Test with App ID 1</a>";
echo "<a href='payment.php?application_id=999' target='_blank' class='btn'>Test with Invalid ID</a>";
echo "<a href='payment.php' target='_blank' class='btn'>Test without App ID</a>";
echo "</div>";

// Test 5: Session and redirect simulation
echo "<div class='test-section'>";
echo "<h2>5. Redirect Simulation Test</h2>";

if (isset($_POST['test_redirect'])) {
    $testAppId = $_POST['test_app_id'];
    
    // Store test payment details in session
    $_SESSION['payment_details'] = [
        'application_id' => $testAppId,
        'amount' => 1200.00,
        'pass_type' => 'Monthly Pass',
        'user_id' => $_SESSION['uid'],
        'name' => 'Test User',
        'source' => 'Central Bus Station',
        'destination' => 'City Mall',
        'application_number' => 'TEST' . date('YmdHis')
    ];
    
    echo "<div class='success'>✅ Payment details stored in session</div>";
    echo "<div class='info'>Session data prepared for application ID: $testAppId</div>";
    
    // Simulate the redirect that would happen in apply-pass.php
    $redirectUrl = "payment.php?application_id=" . $testAppId;
    echo "<div class='info'><strong>Simulated redirect URL:</strong> <a href='$redirectUrl' target='_blank'>$redirectUrl</a></div>";
    
    // JavaScript redirect test
    echo "<script>
        setTimeout(function() {
            if (confirm('Test automatic redirect to payment page?')) {
                window.open('$redirectUrl', '_blank');
            }
        }, 2000);
    </script>";
    
} else {
    echo "<p>Test the redirect process from application form to payment:</p>";
    echo "<form method='post'>";
    echo "<label>Application ID to test: </label>";
    echo "<input type='number' name='test_app_id' value='1' min='1' required>";
    echo "<button type='submit' name='test_redirect' class='btn'>🔄 Test Redirect</button>";
    echo "</form>";
}
echo "</div>";

// Test 6: Check for common issues
echo "<div class='test-section'>";
echo "<h2>6. Common Issues Check</h2>";

$issues = [];

// Check if user exists
$userCheck = $con->query("SELECT COUNT(*) as count FROM users WHERE id = {$_SESSION['uid']}");
$userExists = $userCheck && $userCheck->fetch_assoc()['count'] > 0;
if (!$userExists) {
    $issues[] = "Test user (ID: {$_SESSION['uid']}) does not exist in database";
}

// Check if pass types exist
$passTypeCheck = $con->query("SELECT COUNT(*) as count FROM bus_pass_types");
$passTypeCount = $passTypeCheck ? $passTypeCheck->fetch_assoc()['count'] : 0;
if ($passTypeCount == 0) {
    $issues[] = "No pass types found in database";
}

// Check if categories exist
$categoryCheck = $con->query("SELECT COUNT(*) as count FROM categories");
$categoryCount = $categoryCheck ? $categoryCheck->fetch_assoc()['count'] : 0;
if ($categoryCount == 0) {
    $issues[] = "No categories found in database";
}

// Check uploads directory
if (!is_dir('uploads/photos')) {
    $issues[] = "uploads/photos directory does not exist";
} elseif (!is_writable('uploads/photos')) {
    $issues[] = "uploads/photos directory is not writable";
}

if (empty($issues)) {
    echo "<div class='success'>✅ No common issues detected</div>";
} else {
    echo "<div class='error'><strong>❌ Issues found:</strong><br>";
    foreach ($issues as $issue) {
        echo "• $issue<br>";
    }
    echo "</div>";
}
echo "</div>";

// Quick links
echo "<div class='test-section'>";
echo "<h2>7. Quick Links</h2>";
echo "<a href='apply-pass.php' class='btn'>📝 Application Form</a>";
echo "<a href='user-dashboard.php' class='btn'>🏠 User Dashboard</a>";
echo "<a href='test-form-submission.php' class='btn'>🧪 Test Form Submission</a>";
echo "<a href='debug-apply-form.php' class='btn'>🔍 Debug Form</a>";
echo "</div>";

echo "</body></html>";
?>
