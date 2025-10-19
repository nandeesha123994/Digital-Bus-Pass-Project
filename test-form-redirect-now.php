<?php
/**
 * URGENT: Test Form Redirect Issue
 * Quick test to see why form is not redirecting to payment page
 */

session_start();
include('includes/dbconnection.php');

// Set test user session if not logged in
if (!isset($_SESSION['uid'])) {
    $_SESSION['uid'] = 1; // Test user ID
}

echo "<!DOCTYPE html>";
echo "<html><head><title>🚨 URGENT: Form Redirect Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .test-section { background: white; margin: 15px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; cursor: pointer; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
    .btn-success { background: #28a745; } .btn-success:hover { background: #218838; }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<h1>🚨 URGENT: Form Redirect Test</h1>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_submit'])) {
    echo "<div class='test-section'>";
    echo "<h2>📝 Testing Form Submission Process</h2>";
    
    echo "<div class='info'>🔄 Processing form submission...</div>";
    
    try {
        // Simulate the exact same process as apply-pass.php
        $name = $_POST['name'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $source = $_POST['source'];
        $destination = $_POST['destination'];
        $pass_type_id = $_POST['pass_type_id'];
        $category_id = $_POST['category_id'];
        $id_proof_type = $_POST['id_proof_type'];
        $id_proof_number = $_POST['id_proof_number'];
        
        echo "<div class='success'>✅ Form data received successfully</div>";
        
        // Validate required fields
        $errors = [];
        if (empty($name)) $errors[] = "Name is required";
        if (empty($dob)) $errors[] = "Date of birth is required";
        if (empty($gender)) $errors[] = "Gender is required";
        if (empty($phone)) $errors[] = "Phone is required";
        if (empty($address)) $errors[] = "Address is required";
        if (empty($source)) $errors[] = "Source is required";
        if (empty($destination)) $errors[] = "Destination is required";
        if (empty($pass_type_id)) $errors[] = "Pass type is required";
        if (empty($category_id)) $errors[] = "Category is required";
        if (empty($id_proof_type)) $errors[] = "ID Proof type is required";
        if (empty($id_proof_number)) $errors[] = "ID Proof number is required";
        
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Photo upload error";
        }
        
        if (!empty($errors)) {
            echo "<div class='error'><strong>❌ Validation Errors:</strong><br>" . implode('<br>', $errors) . "</div>";
        } else {
            echo "<div class='success'>✅ All validation passed!</div>";
            
            // Generate application ID
            $applicationId = "TEST" . date('YmdHis');
            
            // Get pass type details
            $passTypeQuery = "SELECT * FROM bus_pass_types WHERE id = ?";
            $passTypeStmt = $con->prepare($passTypeQuery);
            $passTypeStmt->bind_param("i", $pass_type_id);
            $passTypeStmt->execute();
            $passType = $passTypeStmt->get_result()->fetch_assoc();
            
            if (!$passType) {
                echo "<div class='error'>❌ Invalid pass type ID: $pass_type_id</div>";
            } else {
                echo "<div class='success'>✅ Pass type found: {$passType['type_name']} - ₹{$passType['amount']}</div>";
                
                // Handle photo upload
                $uploadDir = 'uploads/photos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $photoExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $photoFileName = $applicationId . '_' . time() . '.' . $photoExt;
                $photoPath = $uploadDir . $photoFileName;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                    echo "<div class='success'>✅ Photo uploaded: $photoPath</div>";
                    
                    // Test database insertion
                    $insertQuery = "INSERT INTO bus_pass_applications (
                        user_id, application_id, applicant_name, date_of_birth, gender, phone, address,
                        source, destination, pass_type_id, category_id, amount, 
                        photo_path, id_proof_type, id_proof_number, status, application_date, email
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)";
                    
                    $stmt = $con->prepare($insertQuery);
                    if (!$stmt) {
                        echo "<div class='error'>❌ Database prepare error: " . $con->error . "</div>";
                    } else {
                        $userEmail = "test@example.com";
                        $stmt->bind_param(
                            "issssssssiidssss",
                            $_SESSION['uid'],
                            $applicationId,
                            $name,
                            $dob,
                            $gender,
                            $phone,
                            $address,
                            $source,
                            $destination,
                            $pass_type_id,
                            $category_id,
                            $passType['amount'],
                            $photoPath,
                            $id_proof_type,
                            $id_proof_number,
                            $userEmail
                        );
                        
                        if ($stmt->execute()) {
                            $newApplicationId = $stmt->insert_id;
                            echo "<div class='success'>✅ <strong>Database insertion successful!</strong><br>";
                            echo "Application ID: $newApplicationId<br>";
                            echo "Application Number: $applicationId<br>";
                            echo "Amount: ₹{$passType['amount']}</div>";
                            
                            // Store payment details in session
                            $_SESSION['payment_details'] = [
                                'application_id' => $newApplicationId,
                                'amount' => $passType['amount'],
                                'pass_type' => $passType['type_name'],
                                'user_id' => $_SESSION['uid'],
                                'name' => $name,
                                'source' => $source,
                                'destination' => $destination,
                                'application_number' => $applicationId
                            ];
                            
                            echo "<div class='success'>✅ Payment details stored in session</div>";
                            
                            // Test redirect
                            $redirectUrl = "payment.php?application_id=" . $newApplicationId;
                            echo "<div class='info'><strong>🔗 Redirect URL:</strong> <a href='$redirectUrl' target='_blank'>$redirectUrl</a></div>";
                            
                            echo "<div class='success'>";
                            echo "<h3>🎉 FORM SUBMISSION TEST SUCCESSFUL!</h3>";
                            echo "<p>The form submission process works perfectly. Now testing redirect...</p>";
                            echo "</div>";
                            
                            // Clear output buffers and redirect
                            if (ob_get_level()) {
                                ob_end_clean();
                            }
                            
                            // Immediate redirect test
                            echo "<script>";
                            echo "console.log('Testing redirect to payment page...');";
                            echo "setTimeout(function() {";
                            echo "  window.location.href = '$redirectUrl';";
                            echo "}, 2000);";
                            echo "</script>";
                            
                            echo "<div class='info'>";
                            echo "<h4>🔄 Automatic Redirect Test</h4>";
                            echo "<p>You should be redirected to the payment page in 2 seconds...</p>";
                            echo "<p>If not redirected, <a href='$redirectUrl' class='btn btn-success'>Click Here to Go to Payment Page</a></p>";
                            echo "</div>";
                            
                        } else {
                            echo "<div class='error'>❌ Database insertion failed: " . $stmt->error . "</div>";
                        }
                    }
                } else {
                    echo "<div class='error'>❌ Failed to upload photo</div>";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'><strong>❌ Error:</strong> " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
} else {
    // Show test form
    echo "<div class='test-section'>";
    echo "<h2>🧪 Quick Form Test</h2>";
    echo "<p><strong>This will test the exact same process as your application form:</strong></p>";
    
    echo "<form method='post' action='test-form-redirect-now.php' enctype='multipart/form-data'>";
    
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
    echo "<option value=''>Select Gender</option>";
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
    echo "<option value=''>Select Source</option>";
    $sourcesResult = $con->query("SELECT DISTINCT source FROM routes ORDER BY source");
    if ($sourcesResult) {
        while ($row = $sourcesResult->fetch_assoc()) {
            $selected = $row['source'] === 'Central Bus Station' ? 'selected' : '';
            echo "<option value='{$row['source']}' $selected>{$row['source']}</option>";
        }
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>Destination:</label>";
    echo "<select name='destination' required>";
    echo "<option value=''>Select Destination</option>";
    $destinationsResult = $con->query("SELECT DISTINCT destination FROM routes ORDER BY destination");
    if ($destinationsResult) {
        while ($row = $destinationsResult->fetch_assoc()) {
            $selected = $row['destination'] === 'City Mall' ? 'selected' : '';
            echo "<option value='{$row['destination']}' $selected>{$row['destination']}</option>";
        }
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>Pass Type:</label>";
    echo "<select name='pass_type_id' required>";
    echo "<option value=''>Select Pass Type</option>";
    $passTypesResult = $con->query("SELECT * FROM bus_pass_types ORDER BY type_name");
    if ($passTypesResult) {
        while ($passType = $passTypesResult->fetch_assoc()) {
            $selected = $passType['type_name'] === 'Monthly Pass' ? 'selected' : '';
            echo "<option value='{$passType['id']}' $selected>{$passType['type_name']} - ₹{$passType['amount']}</option>";
        }
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>Category:</label>";
    echo "<select name='category_id' required>";
    echo "<option value=''>Select Category</option>";
    $categoriesResult = $con->query("SELECT * FROM categories ORDER BY category_name");
    if ($categoriesResult) {
        while ($category = $categoriesResult->fetch_assoc()) {
            $selected = $category['category_name'] === 'General' ? 'selected' : '';
            echo "<option value='{$category['id']}' $selected>{$category['category_name']}</option>";
        }
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>ID Proof Type:</label>";
    echo "<select name='id_proof_type' required>";
    echo "<option value=''>Select ID Proof</option>";
    echo "<option value='Aadhaar Card' selected>Aadhaar Card</option>";
    echo "<option value='PAN Card'>PAN Card</option>";
    echo "<option value='Voter ID'>Voter ID</option>";
    echo "</select>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>ID Proof Number:</label>";
    echo "<input type='text' name='id_proof_number' value='123456789012' required>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>Photo (JPG/PNG, max 5MB):</label>";
    echo "<input type='file' name='photo' accept='image/jpeg,image/png' required>";
    echo "</div>";
    
    echo "<button type='submit' name='test_submit' class='btn btn-danger'>🚨 TEST FORM SUBMISSION & REDIRECT</button>";
    echo "</form>";
    echo "</div>";
    
    // Quick links
    echo "<div class='test-section'>";
    echo "<h2>🔗 Quick Actions</h2>";
    echo "<a href='apply-pass.php' class='btn'>📝 Go to Real Application Form</a>";
    echo "<a href='payment.php?application_id=1' class='btn'>💳 Test Payment Page Directly</a>";
    echo "<a href='ultimate-final-check.php' class='btn'>📊 System Status Check</a>";
    echo "</div>";
}

echo "</body></html>";
?>
