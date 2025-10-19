<?php
/**
 * Test Redirect Functionality
 * This script will test if the redirect from application form to payment page works
 */

session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Redirect Functionality</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .test-section { background: white; margin: 15px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    .btn-success { background: #28a745; } .btn-success:hover { background: #218838; }
    .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
    .btn-warning { background: #ffc107; color: #212529; } .btn-warning:hover { background: #e0a800; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<h1>🧪 Test Redirect Functionality</h1>";

// Set test user session if not logged in
if (!isset($_SESSION['uid'])) {
    $_SESSION['uid'] = 1; // Test user ID
    echo "<div class='warning'>⚠️ Set test user session (UID: 1) for testing</div>";
}

// Test 1: Simple redirect test
echo "<div class='test-section'>";
echo "<h2>1. Simple Redirect Test</h2>";

if (isset($_GET['test_redirect'])) {
    $testAppId = $_GET['test_redirect'];
    
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    echo "<div class='info'>Testing redirect to payment page...</div>";
    
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
    
    // Multiple redirect methods
    header("Location: payment.php?application_id=" . $testAppId);
    
    echo "<script type='text/javascript'>";
    echo "setTimeout(function() { window.location.href = 'payment.php?application_id=" . $testAppId . "'; }, 1000);";
    echo "</script>";
    
    echo "<meta http-equiv='refresh' content='2;url=payment.php?application_id=" . $testAppId . "'>";
    
    echo "<div class='success'>";
    echo "<h3>🔄 Redirect Test in Progress</h3>";
    echo "<p>If redirect is working, you should be taken to the payment page automatically.</p>";
    echo "<p>If not, <a href='payment.php?application_id=" . $testAppId . "'>click here to go manually</a></p>";
    echo "</div>";
    
    exit();
}

echo "<p>Click the button below to test the redirect functionality:</p>";
echo "<a href='test-redirect.php?test_redirect=1' class='btn btn-warning'>🧪 Test Redirect to Payment Page</a>";
echo "</div>";

// Test 2: Check for existing applications
echo "<div class='test-section'>";
echo "<h2>2. Existing Applications for Testing</h2>";

$applicationsQuery = "SELECT id, application_id, applicant_name, amount, status, payment_status FROM bus_pass_applications ORDER BY id DESC LIMIT 5";
$applicationsResult = $con->query($applicationsQuery);

if ($applicationsResult && $applicationsResult->num_rows > 0) {
    echo "<div class='success'>✅ Found {$applicationsResult->num_rows} applications for testing</div>";
    echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #e9ecef;'><th style='border: 1px solid #dee2e6; padding: 8px;'>ID</th><th style='border: 1px solid #dee2e6; padding: 8px;'>App ID</th><th style='border: 1px solid #dee2e6; padding: 8px;'>Name</th><th style='border: 1px solid #dee2e6; padding: 8px;'>Amount</th><th style='border: 1px solid #dee2e6; padding: 8px;'>Test Redirect</th></tr>";
    
    while ($app = $applicationsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$app['id']}</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$app['application_id']}</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . htmlspecialchars($app['applicant_name']) . "</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>₹{$app['amount']}</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><a href='test-redirect.php?test_redirect={$app['id']}' class='btn btn-success'>Test Redirect</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠️ No applications found. Create one first using the application form.</div>";
}
echo "</div>";

// Test 3: Check payment page accessibility
echo "<div class='test-section'>";
echo "<h2>3. Payment Page Accessibility Test</h2>";

if (file_exists('payment.php')) {
    echo "<div class='success'>✅ payment.php file exists</div>";
    
    // Test direct access
    echo "<p>Test direct access to payment page:</p>";
    echo "<a href='payment.php?application_id=1' target='_blank' class='btn'>🔗 Direct Payment Page Access</a>";
    echo "<a href='payment.php' target='_blank' class='btn'>🔗 Payment Page (No ID)</a>";
} else {
    echo "<div class='error'>❌ payment.php file not found</div>";
}
echo "</div>";

// Test 4: Session data test
echo "<div class='test-section'>";
echo "<h2>4. Session Data Test</h2>";

if (isset($_SESSION['payment_details'])) {
    echo "<div class='success'>✅ Payment details found in session</div>";
    echo "<pre>" . print_r($_SESSION['payment_details'], true) . "</pre>";
    echo "<a href='payment.php?application_id={$_SESSION['payment_details']['application_id']}' target='_blank' class='btn btn-success'>🔗 Go to Payment with Session Data</a>";
} else {
    echo "<div class='info'>ℹ️ No payment details in session (normal if no recent application)</div>";
}

echo "<h4>Current Session Data:</h4>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
echo "</div>";

// Test 5: Create test application and redirect
echo "<div class='test-section'>";
echo "<h2>5. Complete Application + Redirect Test</h2>";

if (isset($_POST['create_and_redirect'])) {
    try {
        // Create a test application
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
            "Test User for Redirect",
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
            
            // Store payment details in session
            $_SESSION['payment_details'] = [
                'application_id' => $newAppId,
                'amount' => $amount,
                'pass_type' => 'Monthly Pass',
                'user_id' => $_SESSION['uid'],
                'name' => 'Test User for Redirect',
                'source' => 'Central Bus Station',
                'destination' => 'City Mall',
                'application_number' => $applicationId
            ];
            
            echo "<div class='success'>✅ Test application created successfully!</div>";
            echo "<div class='info'>Application ID: $newAppId<br>Application Number: $applicationId<br>Amount: ₹$amount</div>";
            
            // Clear any output buffers
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Redirect to payment page
            header("Location: payment.php?application_id=" . $newAppId);
            
            echo "<script type='text/javascript'>";
            echo "setTimeout(function() { window.location.href = 'payment.php?application_id=" . $newAppId . "'; }, 1000);";
            echo "</script>";
            
            echo "<div class='success'>";
            echo "<h3>🎉 Application Created - Redirecting to Payment</h3>";
            echo "<p>If redirect doesn't work automatically, <a href='payment.php?application_id=" . $newAppId . "'>click here</a></p>";
            echo "</div>";
            
            exit();
            
        } else {
            echo "<div class='error'>❌ Failed to create test application: " . $stmt->error . "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<p>This will create a complete test application and then redirect to payment:</p>";
    echo "<form method='post'>";
    echo "<button type='submit' name='create_and_redirect' class='btn btn-success'>🚀 Create Application & Test Redirect</button>";
    echo "</form>";
}
echo "</div>";

// Quick links
echo "<div class='test-section'>";
echo "<h2>6. Quick Links</h2>";
echo "<a href='apply-pass.php' class='btn'>📝 Real Application Form</a>";
echo "<a href='user-dashboard.php' class='btn'>🏠 User Dashboard</a>";
echo "<a href='test-form-submission.php' class='btn'>🧪 Test Form Submission</a>";
echo "<a href='check-current-status.php' class='btn'>📊 System Status</a>";
echo "</div>";

echo "</body></html>";
?>
