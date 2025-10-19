<?php
/**
 * QUICK REDIRECT TEST
 * Test if the form redirect is now working
 */

session_start();
include('includes/dbconnection.php');

// Set test user session
if (!isset($_SESSION['uid'])) {
    $_SESSION['uid'] = 1;
}

echo "<!DOCTYPE html>";
echo "<html><head><title>🚨 Quick Redirect Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f8f9fa; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0; }
    .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 10px 5px; cursor: pointer; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    .btn-success { background: #28a745; } .btn-success:hover { background: #218838; }
    .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
    h1 { color: #333; text-align: center; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🚨 Quick Redirect Test</h1>";

if (isset($_POST['test_redirect'])) {
    // Simulate successful application submission
    $testApplicationId = 999; // Test ID
    
    // Store test payment details
    $_SESSION['payment_details'] = [
        'application_id' => $testApplicationId,
        'amount' => 1200.00,
        'pass_type' => 'Monthly Pass',
        'user_id' => $_SESSION['uid'],
        'name' => 'Test User',
        'source' => 'Central Bus Station',
        'destination' => 'City Mall',
        'application_number' => 'TEST' . date('YmdHis')
    ];
    
    echo "<div class='success'>✅ Test data prepared</div>";
    echo "<div class='info'>🔄 Testing redirect in 2 seconds...</div>";
    
    // Test the same redirect logic as apply-pass.php
    echo "<script>";
    echo "console.log('Testing redirect...');";
    echo "setTimeout(function() {";
    echo "  window.location.href = 'payment.php?application_id=" . $testApplicationId . "';";
    echo "}, 2000);";
    echo "</script>";
    
    echo "<div class='info'>";
    echo "<p><strong>If redirect works:</strong> You should be taken to payment page automatically</p>";
    echo "<p><strong>If redirect fails:</strong> <a href='payment.php?application_id=" . $testApplicationId . "' class='btn btn-success'>Click here manually</a></p>";
    echo "</div>";
    
} else {
    echo "<div class='info'>";
    echo "<h3>🧪 Test Form Redirect</h3>";
    echo "<p>This will test if the form redirect to payment page is working after the fix.</p>";
    echo "</div>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='test_redirect' class='btn btn-danger'>🚨 TEST REDIRECT NOW</button>";
    echo "</form>";
    
    echo "<div class='info'>";
    echo "<h4>📋 What this test does:</h4>";
    echo "<ul>";
    echo "<li>Simulates successful form submission</li>";
    echo "<li>Sets up payment session data</li>";
    echo "<li>Tests redirect to payment page</li>";
    echo "<li>Uses same logic as real application form</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='apply-pass.php' class='btn'>📝 Go to Real Application Form</a>";
echo "<a href='payment.php?application_id=1' class='btn'>💳 Test Payment Page</a>";
echo "</div>";

echo "</div></body></html>";
?>
