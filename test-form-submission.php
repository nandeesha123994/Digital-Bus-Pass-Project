<?php
/**
 * Test Form Submission for Bus Pass Application
 * This will help identify why the form is not redirecting to payment
 */

session_start();
include('includes/dbconnection.php');

// Set user session for testing if not logged in
if (!isset($_SESSION['uid'])) {
    // For testing purposes, set a test user ID
    // In production, redirect to login
    $_SESSION['uid'] = 1; // Assuming user ID 1 exists
    echo "<div style='background: #fff3cd; padding: 10px; margin: 10px; border-radius: 5px; color: #856404;'>
          ⚠️ <strong>Test Mode:</strong> Using test user ID 1. In production, user must be logged in.
          </div>";
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Form Submission</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
    input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
    button { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
    button:hover { background: #0056b3; }
    .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<h1>🧪 Test Form Submission</h1>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    echo "<div class='info'><strong>📝 Form Submitted!</strong> Processing...</div>";
    
    // Debug: Show all POST data
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h3>FILES Data Received:</h3>";
    echo "<pre>" . print_r($_FILES, true) . "</pre>";
    
    try {
        // Get user information
        $userQuery = "SELECT full_name, email FROM users WHERE id = ?";
        $userStmt = $con->prepare($userQuery);
        $userStmt->bind_param("i", $_SESSION['uid']);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        
        if (!$user) {
            throw new Exception("User not found with ID: " . $_SESSION['uid']);
        }
        
        echo "<div class='success'>✅ User found: {$user['full_name']} ({$user['email']})</div>";
        
        // Validate required fields
        $requiredFields = ['name', 'dob', 'gender', 'phone', 'address', 'source', 'destination', 'pass_type_id', 'category_id', 'id_proof_type', 'id_proof_number'];
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = "Missing field: $field";
            }
        }
        
        // Check photo upload
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Photo upload error: " . ($_FILES['photo']['error'] ?? 'No file uploaded');
        }
        
        if (!empty($errors)) {
            echo "<div class='error'><strong>❌ Validation Errors:</strong><br>" . implode('<br>', $errors) . "</div>";
        } else {
            echo "<div class='success'>✅ All validation passed!</div>";
            
            // Generate application ID
            $applicationId = "BPMS" . date('Y') . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            // Get pass type details
            $passTypeQuery = "SELECT * FROM bus_pass_types WHERE id = ?";
            $passTypeStmt = $con->prepare($passTypeQuery);
            $passTypeStmt->bind_param("i", $_POST['pass_type_id']);
            $passTypeStmt->execute();
            $passType = $passTypeStmt->get_result()->fetch_assoc();
            
            if (!$passType) {
                throw new Exception("Invalid pass type ID: " . $_POST['pass_type_id']);
            }
            
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
                echo "<h3>🔄 Testing Database Insertion...</h3>";
                
                $insertQuery = "INSERT INTO bus_pass_applications (
                    user_id, application_id, applicant_name, date_of_birth, gender, phone, address,
                    source, destination, pass_type_id, category_id, amount, 
                    photo_path, id_proof_type, id_proof_number, status, application_date, email
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)";
                
                $stmt = $con->prepare($insertQuery);
                if (!$stmt) {
                    throw new Exception("Database prepare error: " . $con->error);
                }
                
                $stmt->bind_param(
                    "issssssssiidssss",
                    $_SESSION['uid'],
                    $applicationId,
                    $_POST['name'],
                    $_POST['dob'],
                    $_POST['gender'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['source'],
                    $_POST['destination'],
                    $_POST['pass_type_id'],
                    $_POST['category_id'],
                    $passType['amount'],
                    $photoPath,
                    $_POST['id_proof_type'],
                    $_POST['id_proof_number'],
                    $user['email']
                );
                
                if ($stmt->execute()) {
                    $newApplicationId = $stmt->insert_id;
                    echo "<div class='success'>✅ <strong>Database insertion successful!</strong><br>";
                    echo "Application ID: $newApplicationId<br>";
                    echo "Application Number: $applicationId<br>";
                    echo "Amount: ₹{$passType['amount']}</div>";
                    
                    echo "<div class='info'><strong>🔄 Now testing redirect to payment page...</strong></div>";
                    
                    // Store payment details in session
                    $_SESSION['payment_details'] = [
                        'application_id' => $newApplicationId,
                        'amount' => $passType['amount'],
                        'pass_type' => $passType['type_name'],
                        'user_id' => $_SESSION['uid'],
                        'name' => $_POST['name'],
                        'source' => $_POST['source'],
                        'destination' => $_POST['destination'],
                        'application_number' => $applicationId
                    ];
                    
                    echo "<div class='success'>✅ Payment details stored in session</div>";
                    
                    // Test redirect (but don't actually redirect for debugging)
                    $redirectUrl = "payment.php?application_id=" . $newApplicationId;
                    echo "<div class='info'><strong>🔗 Would redirect to:</strong> <a href='$redirectUrl' target='_blank'>$redirectUrl</a></div>";
                    echo "<div class='success'><strong>🎉 FORM SUBMISSION TEST SUCCESSFUL!</strong><br>";
                    echo "The form would normally redirect to the payment page now.</div>";
                    
                } else {
                    throw new Exception("Database insertion failed: " . $stmt->error);
                }
                
            } else {
                throw new Exception("Failed to upload photo");
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'><strong>❌ Error:</strong> " . $e->getMessage() . "</div>";
    }
}

// Show test form
echo "<div class='form-container'>";
echo "<h2>📝 Test Application Form</h2>";
echo "<p>This simplified form will test the submission process step by step.</p>";

echo "<form method='post' action='test-form-submission.php' enctype='multipart/form-data'>";

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
// Get sources from routes
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
// Get destinations from routes
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
// Get pass types
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
// Get categories
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

echo "<button type='submit' name='submit'>🧪 Test Submit Application</button>";
echo "</form>";
echo "</div>";

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<a href='apply-pass.php' style='color: #007bff; text-decoration: none;'>← Back to Real Application Form</a>";
echo "</div>";

echo "</body></html>";
?>
