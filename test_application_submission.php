<?php
// Test Application Submission and ID Generation
session_start();
include('includes/dbconnection.php');
include('includes/config.php');

// Set a test user session (use existing user ID)
if (!isset($_SESSION['uid'])) {
    // Get the first user from the database for testing
    $userQuery = "SELECT id FROM users LIMIT 1";
    $userResult = $con->query($userQuery);
    if ($userResult && $userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $_SESSION['uid'] = $user['id'];
        echo "<p style='color: orange;'>⚠️ Using test user ID: {$user['id']} for testing</p>";
    } else {
        echo "<p style='color: red;'>❌ No users found in database. Please register a user first.</p>";
        exit();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Application Submission - Nrupatunga Digital Bus Pass System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .content {
            padding: 30px;
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            color: #0c5460;
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        .btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        .test-result {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .id-display {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #28a745;
            text-align: center;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test Application Submission</h1>
            <p>Verify Application ID generation in real submission</p>
        </div>
        <div class="content">
            <?php
            $message = '';
            $messageType = '';
            $generatedApplicationId = '';

            if (isset($_POST['test_submit'])) {
                try {
                    echo "<div class='test-result'>";
                    echo "<h4>🔧 Processing Test Application Submission</h4>";
                    
                    // Get test data
                    $passTypeQuery = "SELECT * FROM bus_pass_types LIMIT 1";
                    $passTypeResult = $con->query($passTypeQuery);
                    $passType = $passTypeResult->fetch_assoc();
                    
                    $categoryQuery = "SELECT * FROM categories LIMIT 1";
                    $categoryResult = $con->query($categoryQuery);
                    $category = $categoryResult->fetch_assoc();
                    
                    if (!$passType || !$category) {
                        throw new Exception("Missing pass types or categories in database");
                    }
                    
                    echo "<div class='info'>Using Pass Type: {$passType['type_name']} (₹{$passType['amount']})</div>";
                    echo "<div class='info'>Using Category: {$category['category_name']}</div>";
                    
                    // Test data
                    $name = "Test User " . date('His');
                    $dob = "1990-01-01";
                    $gender = "Male";
                    $phone = "9876543210";
                    $address = "Test Address, Test City, Test State - 123456";
                    $source = "Test Source";
                    $destination = "Test Destination";
                    $passTypeId = $passType['id'];
                    $categoryId = $category['id'];
                    
                    // Calculate amount
                    $amount = $passType['amount'];
                    $tax = calculateTax($amount);
                    $totalAmount = $amount + $tax;
                    
                    echo "<div class='info'>Total Amount: ₹{$totalAmount} (Base: ₹{$amount} + Tax: ₹{$tax})</div>";
                    
                    // Get user email
                    $userQuery = "SELECT email FROM users WHERE id = ?";
                    $userStmt = $con->prepare($userQuery);
                    $userStmt->bind_param("i", $_SESSION['uid']);
                    $userStmt->execute();
                    $userResult = $userStmt->get_result();
                    $userEmail = $userResult->fetch_assoc()['email'];
                    
                    // Check table structure
                    $tableColumns = [];
                    $columnsResult = $con->query("DESCRIBE bus_pass_applications");
                    while ($column = $columnsResult->fetch_assoc()) {
                        $tableColumns[] = $column['Field'];
                    }
                    
                    $hasApplicationId = in_array('application_id', $tableColumns);
                    $hasPhotoPath = in_array('photo_path', $tableColumns);
                    $hasEmail = in_array('email', $tableColumns);
                    
                    echo "<div class='info'>";
                    echo "Column Check: ";
                    echo "application_id=" . ($hasApplicationId ? "✅" : "❌") . " ";
                    echo "photo_path=" . ($hasPhotoPath ? "✅" : "❌") . " ";
                    echo "email=" . ($hasEmail ? "✅" : "❌");
                    echo "</div>";
                    
                    // Generate Application ID
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
                        } while ($result->num_rows > 0);
                        
                        return $applicationId;
                    }
                    
                    // Build query
                    $fields = ['user_id', 'pass_type_id', 'category_id', 'applicant_name', 'date_of_birth', 'gender', 'phone', 'address', 'source', 'destination', 'amount', 'status'];
                    $values = [$_SESSION['uid'], $passTypeId, $categoryId, $name, $dob, $gender, $phone, $address, $source, $destination, $totalAmount, 'Pending'];
                    $types = 'iiisssssssds';
                    
                    if ($hasApplicationId) {
                        $generatedApplicationId = generateApplicationId($con);
                        array_splice($fields, 3, 0, 'application_id');
                        array_splice($values, 3, 0, $generatedApplicationId);
                        $types = substr_replace($types, 's', 3, 0);
                        echo "<div class='success'>✅ Generated Application ID: <strong>{$generatedApplicationId}</strong></div>";
                    }
                    
                    if ($hasEmail) {
                        $emailIndex = array_search('phone', $fields) + 1;
                        array_splice($fields, $emailIndex, 0, 'email');
                        array_splice($values, $emailIndex, 0, $userEmail);
                        $types = substr_replace($types, 's', $emailIndex, 0);
                    }
                    
                    if ($hasPhotoPath) {
                        $photoIndex = array_search('destination', $fields) + 1;
                        array_splice($fields, $photoIndex, 0, 'photo_path');
                        array_splice($values, $photoIndex, 0, 'test_photo.jpg');
                        $types = substr_replace($types, 's', $photoIndex, 0);
                    }
                    
                    $fieldsList = implode(', ', $fields);
                    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
                    
                    echo "<div class='info'>";
                    echo "<strong>SQL Query:</strong><br>";
                    echo "<code>INSERT INTO bus_pass_applications ({$fieldsList}) VALUES ({$placeholders})</code>";
                    echo "</div>";
                    
                    // Execute the query
                    $query = "INSERT INTO bus_pass_applications ($fieldsList) VALUES ($placeholders)";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param($types, ...$values);
                    
                    if ($stmt->execute()) {
                        $applicationId = $con->insert_id;
                        
                        echo "<div class='success'>";
                        echo "<h4>🎉 Application Submitted Successfully!</h4>";
                        echo "<p><strong>Database ID:</strong> {$applicationId}</p>";
                        if ($generatedApplicationId) {
                            echo "<p><strong>Application ID:</strong> {$generatedApplicationId}</p>";
                        }
                        echo "</div>";
                        
                        $message = "Test application submitted successfully!";
                        $messageType = "success";
                    } else {
                        throw new Exception("Database insert failed: " . $stmt->error);
                    }
                    
                    echo "</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Test Failed: " . $e->getMessage() . "</div>";
                    $message = "Test failed: " . $e->getMessage();
                    $messageType = "error";
                }
            }
            ?>
            
            <?php if (!empty($message)): ?>
                <div class="<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                    <?php if ($generatedApplicationId): ?>
                        <div class="id-display">
                            Application ID: <?php echo $generatedApplicationId; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="test-result">
                <h4>🧪 Run Application Submission Test</h4>
                <p>This will create a test application with generated Application ID to verify the system is working.</p>
                
                <form method="post">
                    <button type="submit" name="test_submit" class="btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-play"></i> Run Test Submission
                    </button>
                </form>
            </div>
            
            <div class="info">
                <h4>📋 Test Details:</h4>
                <ul>
                    <li>✅ Uses existing pass types and categories</li>
                    <li>✅ Generates unique Application ID in BPMS format</li>
                    <li>✅ Tests complete database insertion</li>
                    <li>✅ Verifies all required columns exist</li>
                    <li>✅ Shows generated Application ID</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="apply-pass.php" class="btn">📝 Real Apply Pass Form</a>
                <a href="user-dashboard.php" class="btn">👤 User Dashboard</a>
                <a href="track-application.php" class="btn">🔍 Track Application</a>
                <a href="index.php" class="btn">🏠 Homepage</a>
            </div>
        </div>
    </div>
</body>
</html>
