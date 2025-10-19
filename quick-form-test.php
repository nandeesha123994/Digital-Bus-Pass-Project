<?php
/**
 * QUICK FORM TEST
 * Test form submission and redirect to payment
 */

session_start();
include('includes/dbconnection.php');

// Set test user session if not logged in
if (!isset($_SESSION['uid'])) {
    $_SESSION['uid'] = 1; // Test user ID
}

echo "<!DOCTYPE html>";
echo "<html><head><title>🚨 Quick Form Test</title>";
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
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🚨 Quick Form Test</h1>";

echo "<div class='info'>";
echo "<h3>🧪 Testing Form Submission → Payment Redirect</h3>";
echo "<p>This will test if the form properly redirects to the payment page with debit card and PhonePe options.</p>";
echo "</div>";

echo "<form method='post' action='process-application.php' enctype='multipart/form-data'>";

echo "<div class='form-group'>";
echo "<label>Name:</label>";
echo "<input type='text' name='name' value='Test User' required>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Date of Birth:</label>";
echo "<input type='date' name='dob' value='1990-01-01' required>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Gender:</label>";
echo "<select name='gender' required>";
echo "<option value='Male' selected>Male</option>";
echo "<option value='Female'>Female</option>";
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Phone:</label>";
echo "<input type='text' name='phone' value='12345678901' required>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Address:</label>";
echo "<textarea name='address' required>Test Address, Test City</textarea>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Source:</label>";
echo "<select name='source' required>";
$sourcesResult = $con->query("SELECT DISTINCT source FROM routes ORDER BY source");
if ($sourcesResult && $sourcesResult->num_rows > 0) {
    while ($row = $sourcesResult->fetch_assoc()) {
        $selected = $row['source'] === 'Central Bus Station' ? 'selected' : '';
        echo "<option value='{$row['source']}' $selected>{$row['source']}</option>";
    }
} else {
    echo "<option value='Central Bus Station' selected>Central Bus Station</option>";
}
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Destination:</label>";
echo "<select name='destination' required>";
$destinationsResult = $con->query("SELECT DISTINCT destination FROM routes ORDER BY destination");
if ($destinationsResult && $destinationsResult->num_rows > 0) {
    while ($row = $destinationsResult->fetch_assoc()) {
        $selected = $row['destination'] === 'City Mall' ? 'selected' : '';
        echo "<option value='{$row['destination']}' $selected>{$row['destination']}</option>";
    }
} else {
    echo "<option value='City Mall' selected>City Mall</option>";
}
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Pass Type:</label>";
echo "<select name='pass_type_id' required>";
$passTypesResult = $con->query("SELECT * FROM bus_pass_types ORDER BY type_name");
if ($passTypesResult && $passTypesResult->num_rows > 0) {
    while ($passType = $passTypesResult->fetch_assoc()) {
        $selected = $passType['type_name'] === 'Monthly Pass' ? 'selected' : '';
        echo "<option value='{$passType['id']}' $selected>{$passType['type_name']} - ₹{$passType['amount']}</option>";
    }
} else {
    echo "<option value='1' selected>Monthly Pass - ₹1200</option>";
}
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Category:</label>";
echo "<select name='category_id' required>";
$categoriesResult = $con->query("SELECT * FROM categories ORDER BY category_name");
if ($categoriesResult && $categoriesResult->num_rows > 0) {
    while ($category = $categoriesResult->fetch_assoc()) {
        $selected = $category['category_name'] === 'General' ? 'selected' : '';
        echo "<option value='{$category['id']}' $selected>{$category['category_name']}</option>";
    }
} else {
    echo "<option value='1' selected>General</option>";
}
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>ID Proof Type:</label>";
echo "<select name='id_proof_type' required>";
echo "<option value='Aadhaar Card' selected>Aadhaar Card</option>";
echo "<option value='PAN Card'>PAN Card</option>";
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>ID Proof Number:</label>";
echo "<input type='text' name='id_proof_number' value='123456789012' required>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Photo (JPG/PNG, max 5MB):</label>";
echo "<input type='file' name='photo' accept='image/jpeg,image/png' required>";
echo "<div style='font-size: 12px; color: #666; margin-top: 5px;'>Please select a photo to test the complete form submission</div>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<button type='submit' class='btn btn-danger'>🚨 TEST FORM SUBMISSION → PAYMENT</button>";
echo "</div>";

echo "</form>";

echo "<div class='success'>";
echo "<h4>✅ Expected Result:</h4>";
echo "<ol>";
echo "<li>Form submits to process-application.php</li>";
echo "<li>Data saves to database</li>";
echo "<li>Redirects to payment.php with application ID</li>";
echo "<li>Payment page shows Debit Card and PhonePe options</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='apply-pass.php' class='btn'>📝 Go to Real Application Form</a>";
echo "<a href='payment.php?application_id=1' class='btn'>💳 Test Payment Page Directly</a>";
echo "</div>";

echo "</div></body></html>";
?>
