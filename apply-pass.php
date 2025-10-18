<?php
ob_start(); // Start output buffering
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('includes/dbconnection.php');
include('includes/config.php');
include('includes/email.php');

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$messageType = '';
$generatedApplicationId = '';

// Function to generate unique Application ID
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

// Get user information
$userQuery = "SELECT full_name, email FROM users WHERE id = ?";
$userStmt = $con->prepare($userQuery);
$userStmt->bind_param("i", $_SESSION['uid']);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

// Get all pass types
$passTypesQuery = "SELECT * FROM bus_pass_types ORDER BY type_name";
$passTypesResult = $con->query($passTypesQuery);
$passTypes = [];
while ($row = $passTypesResult->fetch_assoc()) {
    $passTypes[] = $row;
}

// Get all categories
$categoriesQuery = "SELECT * FROM categories ORDER BY category_name";
$categoriesResult = $con->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Get all routes
$routesQuery = "SELECT DISTINCT source, destination FROM routes ORDER BY source, destination";
$routesResult = $con->query($routesQuery);
$routes = [];
$sources = [];
$destinations = [];
while ($row = $routesResult->fetch_assoc()) {
    $routes[] = $row;
    if (!in_array($row['source'], $sources)) {
        $sources[] = $row['source'];
    }
    if (!in_array($row['destination'], $destinations)) {
        $destinations[] = $row['destination'];
    }
}
sort($sources);
sort($destinations);

// Handle form submission
$errors = [];
$success = false;
$response = ['success' => false, 'message' => '', 'application_id' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    error_log('DEBUG: Entered form submission block');
    // Get user information first
    $userQuery = "SELECT full_name, email FROM users WHERE id = ?";
    $userStmt = $con->prepare($userQuery);
    $userStmt->bind_param("i", $_SESSION['uid']);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();

    if (!$user) {
        $message = "User information not found. Please try logging in again.";
        $messageType = "error";
    } else {
        // Validate and process form data
        $name = trim($_POST['name'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $source = trim($_POST['source'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $passTypeId = intval($_POST['pass_type_id'] ?? 0);
        $categoryId = intval($_POST['category_id'] ?? 0);
        
        // Validation
        $errors = [];
        if (empty($name)) $errors[] = "Name is required";
        if (empty($dob)) $errors[] = "Date of birth is required";
        if (empty($gender)) $errors[] = "Gender is required";
        if (empty($phone)) $errors[] = "Phone number is required";
        if (empty($address)) $errors[] = "Address is required";
        if (empty($source)) $errors[] = "Source is required";
        if (empty($destination)) $errors[] = "Destination is required";
        if ($passTypeId <= 0) $errors[] = "Please select a pass type";
        if ($categoryId <= 0) $errors[] = "Please select a category";
        
        // Photo validation
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = "Please select a photo to upload";
        } else {
            $photo = $_FILES['photo'];
            $allowedTypes = ['image/jpeg', 'image/png'];
            
            // Check file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $photo['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = "Only JPG and PNG images are allowed";
            }
            
            // Check file size (5MB limit)
            if ($photo['size'] > 5 * 1024 * 1024) {
                $errors[] = "Photo size should be less than 5MB";
            }
        }
        
        // ID Proof validation
        if (empty($_POST['id_proof_type'])) $errors[] = "ID Proof type is required";
        if (empty($_POST['id_proof_number'])) $errors[] = "ID Proof number is required";
        
        if (empty($errors)) {
            error_log('DEBUG: Validation passed, proceeding to DB insert and redirect');
            try {
                // Generate unique application ID
                $generatedApplicationId = generateApplicationId($con);
                
                // Get pass type details
                $passTypeQuery = "SELECT * FROM bus_pass_types WHERE id = ?";
                $passTypeStmt = $con->prepare($passTypeQuery);
                $passTypeStmt->bind_param("i", $passTypeId);
                $passTypeStmt->execute();
                $passType = $passTypeStmt->get_result()->fetch_assoc();
                
                if ($passType) {
                    $amount = $passType['amount'];
                    
                    // Handle photo upload
                    $uploadDir = 'uploads/photos/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $photoExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    $photoFileName = $generatedApplicationId . '_' . time() . '.' . $photoExt;
                    $photoPath = $uploadDir . $photoFileName;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                        // Insert the application
                        $insertQuery = "INSERT INTO bus_pass_applications (
                            user_id, application_id, applicant_name, date_of_birth, gender, phone, address,
                            source, destination, pass_type_id, category_id, amount, 
                            photo_path, id_proof_type, id_proof_number, status, application_date, email
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)";
                        
                        $stmt = $con->prepare($insertQuery);
                        if (!$stmt) {
                            throw new Exception("Database error: " . $con->error);
                        }
                        
                        $stmt->bind_param(
                            "issssssssiidssss",
                            $_SESSION['uid'],
                            $generatedApplicationId,
                            $name,
                            $dob,
                            $gender,
                            $phone,
                            $address,
                            $source,
                            $destination,
                            $passTypeId,
                            $categoryId,
                            $amount,
                            $photoPath,
                            $_POST['id_proof_type'],
                            $_POST['id_proof_number'],
                            $user['email']
                        );
                        
                        if ($stmt->execute()) {
                            $applicationId = $stmt->insert_id;
                            
                            // Store application details in session for payment
                            $_SESSION['payment_details'] = [
                                'application_id' => $applicationId,
                                'amount' => $amount,
                                'pass_type' => $passType['type_name'],
                                'user_id' => $_SESSION['uid'],
                                'name' => $name,
                                'source' => $source,
                                'destination' => $destination,
                                'application_number' => $generatedApplicationId
                            ];

                            error_log('DEBUG: Redirecting to payment.php?application_id=' . $applicationId);

                            // Store application ID in session for backup
                            $_SESSION['last_application_id'] = $applicationId;

                            // MULTIPLE REDIRECT METHODS TO ENSURE IT WORKS

                            // Method 1: Clear all output buffers
                            while (ob_get_level()) {
                                ob_end_clean();
                            }

                            // Method 2: Try direct header redirect
                            if (!headers_sent()) {
                                header("Location: payment.php?application_id=" . $applicationId, true, 302);
                                exit();
                            }

                            // Method 3: JavaScript + Meta refresh + Manual link (if headers already sent)
                            echo "<!DOCTYPE html><html><head>";
                            echo "<title>Redirecting to Payment</title>";
                            echo "<script>window.location.href='payment.php?application_id=" . $applicationId . "';</script>";
                            echo "<meta http-equiv='refresh' content='0;url=payment.php?application_id=" . $applicationId . "'>";
                            echo "</head><body>";
                            echo "<div style='text-align:center;padding:50px;font-family:Arial;background:#f8f9fa;'>";
                            echo "<h2 style='color:#28a745;'>✅ Application Submitted Successfully!</h2>";
                            echo "<p style='font-size:1.2em;margin:20px 0;'>🔄 Redirecting to payment page...</p>";
                            echo "<p>If not redirected automatically:</p>";
                            echo "<a href='payment.php?application_id=" . $applicationId . "' style='background:#28a745;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-weight:bold;'>💳 Click Here for Payment</a>";
                            echo "<br><br>";
                            echo "<a href='manual-payment-redirect.php?application_id=" . $applicationId . "' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔄 Alternative Redirect Page</a>";
                            echo "</div></body></html>";
                            exit();
                        } else {
                            throw new Exception("Failed to submit application: " . $stmt->error);
                        }
                    } else {
                        throw new Exception("Failed to save the uploaded photo");
                    }
                } else {
                    throw new Exception("Invalid pass type selected");
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $messageType = "error";
                error_log("Application submission error: " . $e->getMessage());
            }
        } else {
            $message = implode("<br>", $errors);
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Bus Pass - Bus Pass Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/color-schemes.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Indigo/Violet Theme for Bus Pass Application */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #F8F9FA 0%, #ECE8FF 100%);
            color: #212529;
            line-height: 1.6;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(90deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
            box-shadow: 0 4px 20px rgba(90, 79, 207, 0.3);
            margin-bottom: 2rem;
        }

        .header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .nav {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 0;
            text-align: center;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }

        .nav a {
            color: #5A4FCF;
            text-decoration: none;
            margin: 0 1.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav a:hover {
            background: linear-gradient(90deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(90, 79, 207, 0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(90, 79, 207, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(90deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4B0082;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ECE8FF;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            color: #212529;
        }

        .form-control:focus {
            outline: none;
            border-color: #5A4FCF;
            box-shadow: 0 0 0 3px rgba(90, 79, 207, 0.1);
            background: white;
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%235A4FCF' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .pass-types-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .pass-type-card {
            border: 2px solid #ECE8FF;
            border-radius: 15px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .pass-type-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(90, 79, 207, 0.2);
            border-color: #5A4FCF;
        }

        .pass-type-card.selected {
            border-color: #5A4FCF;
            background: linear-gradient(135deg, #EDEBFF 0%, #D8D4FF 100%);
            box-shadow: 0 8px 25px rgba(90, 79, 207, 0.3);
        }

        .pass-type-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .pass-type-header h4 {
            margin: 0;
            color: #4B0082;
            font-size: 1.2rem;
        }

        .duration {
            background: #5A4FCF;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .pass-type-details {
            color: #666;
        }

        .pass-type-details p {
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #5A4FCF;
        }

        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ECE8FF;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #5A4FCF;
            box-shadow: 0 0 0 3px rgba(90, 79, 207, 0.1);
        }

        .form-select option {
            padding: 0.5rem;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -0.75rem;
        }

        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 0.75rem;
        }

        .col-md-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
            padding: 0 0.75rem;
        }

        .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            padding: 0 0.75rem;
        }

        /* Sticky Guidelines Sidebar */
        .guidelines-sidebar {
            position: sticky;
            top: 2rem;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .guidelines-sidebar:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 45px rgba(90, 79, 207, 0.15);
        }

        /* Enhanced Pass Type Selection Animation */
        .pass-type-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(90, 79, 207, 0.1), transparent);
            transition: left 0.5s ease;
            z-index: 1;
        }

        .pass-type-card:hover::before {
            left: 100%;
        }

        .pass-type-card.selected {
            animation: selectCard 0.5s ease-out;
        }

        @keyframes selectCard {
            0% {
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(90, 79, 207, 0.1);
            }
            50% {
                transform: scale(1.02);
                box-shadow: 0px 0px 15px rgba(90, 79, 207, 0.3);
            }
            100% {
                transform: translateY(-2px);
                box-shadow: 0px 0px 10px rgba(90, 79, 207, 0.2), 0 8px 25px rgba(90, 79, 207, 0.3);
            }
        }

        .message {
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .message.success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(32, 201, 151, 0.1) 100%);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .message.error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(255, 107, 107, 0.1) 100%);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .btn {
            background: linear-gradient(90deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(90, 79, 207, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(90, 79, 207, 0.4);
            background: linear-gradient(90deg, #4B0082 0%, #5A4FCF 100%);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .header h2 {
                font-size: 2rem;
            }

            .nav a {
                margin: 0 0.5rem;
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .col-md-6,
            .col-md-8,
            .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            /* Disable sticky on mobile */
            .guidelines-sidebar {
                position: static;
                margin-top: 2rem;
            }

            .pass-type-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .pass-type-price {
                font-size: 1.4rem;
            }
        }

        /* Form Validation Styles */
        .form-control:invalid {
            border-color: #dc3545;
        }

        .form-control:valid {
            border-color: #28a745;
        }

        /* Loading Animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #5A4FCF;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Route Information Styles */
        .route-info {
            margin: 1.5rem 0;
            animation: slideDown 0.3s ease-out;
        }

        .route-card {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(32, 201, 151, 0.05) 100%);
            border: 2px solid rgba(40, 167, 69, 0.2);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
        }

        .route-header {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .route-body {
            padding: 1.5rem;
        }

        .route-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .route-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            border: 1px solid rgba(40, 167, 69, 0.1);
        }

        .route-label {
            font-weight: 600;
            color: #495057;
        }

        .route-value {
            font-weight: 700;
            color: #28a745;
        }

        .no-route-message {
            margin: 1.5rem 0;
            animation: slideDown 0.3s ease-out;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: 1px solid;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 235, 59, 0.1) 100%);
            color: #856404;
            border-color: rgba(255, 193, 7, 0.3);
        }

        .route-suggestions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 193, 7, 0.2);
        }

        .suggestion-item {
            background: rgba(255, 255, 255, 0.8);
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border: 1px solid rgba(255, 193, 7, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .suggestion-item:hover {
            background: rgba(255, 193, 7, 0.1);
            border-color: rgba(255, 193, 7, 0.4);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Progress Indicator */
        .progress-indicator {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(90, 79, 207, 0.1);
            border: 1px solid rgba(90, 79, 207, 0.1);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .progress-step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ECE8FF;
            color: #5A4FCF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .progress-step.active .progress-step-circle {
            background: linear-gradient(135deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(90, 79, 207, 0.3);
        }

        .progress-step-label {
            font-size: 0.85rem;
            color: #666;
            text-align: center;
            font-weight: 500;
        }

        .progress-step.active .progress-step-label {
            color: #5A4FCF;
            font-weight: 600;
        }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: #ECE8FF;
            z-index: -1;
        }

        .progress-step:last-child .progress-line {
            display: none;
        }

        .photo-upload-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .photo-preview {
            width: 200px;
            height: 200px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .photo-preview:hover {
            border-color: #5A4FCF;
            background: #f0f0f0;
        }

        .photo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .photo-preview .placeholder {
            text-align: center;
            color: #666;
        }

        .photo-preview .placeholder i {
            font-size: 48px;
            margin-bottom: 10px;
            color: #5A4FCF;
        }

        .photo-upload-dropdown {
            position: relative;
            margin-bottom: 15px;
        }

        .photo-upload-btn {
            background: linear-gradient(135deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 16px;
            min-width: 200px;
        }

        .photo-upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(90, 79, 207, 0.2);
        }

        .photo-upload-options {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 8px 0;
            min-width: 200px;
            display: none;
            z-index: 1000;
            margin-top: 5px;
        }

        .photo-upload-options.show {
            display: block;
        }

        .photo-option {
            padding: 12px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.3s ease;
        }

        .photo-option:hover {
            background: #f8f9fa;
        }

        .photo-option i {
            width: 20px;
            text-align: center;
            color: #5A4FCF;
        }

        .photo-requirements {
            text-align: left;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .photo-requirements ul {
            list-style-type: none;
            padding-left: 20px;
            margin: 10px 0 0 0;
        }

        .photo-requirements li {
            position: relative;
            margin-bottom: 8px;
            color: #666;
        }

        .photo-requirements li:before {
            content: "•";
            color: #5A4FCF;
            position: absolute;
            left: -15px;
        }

        .photo-requirements i {
            color: #5A4FCF;
            margin-right: 5px;
        }

        /* Camera Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #333;
        }

        .camera-container {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            position: relative;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 4/3;
        }

        #camera, #photo-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .camera-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .camera-btn {
            background: linear-gradient(135deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 16px;
            min-width: 160px;
        }

        .camera-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(90, 79, 207, 0.2);
        }

        .camera-btn.secondary {
            background: #6c757d;
        }

        .camera-btn.secondary:hover {
            background: #5a6268;
        }

        .camera-btn i {
            font-size: 16px;
        }

        #photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><i class="fas fa-id-card"></i> Apply for Bus Pass</h2>
    </div>

    <div class="nav">
        <a href="user-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="progress-steps">
                <div class="progress-step active">
                    <div class="progress-step-circle">1</div>
                    <div class="progress-step-label">Application Form</div>
                    <div class="progress-line"></div>
                </div>
                <div class="progress-step">
                    <div class="progress-step-circle">2</div>
                    <div class="progress-step-label">Payment</div>
                    <div class="progress-line"></div>
                </div>
                <div class="progress-step">
                    <div class="progress-step-circle">3</div>
                    <div class="progress-step-label">Verification</div>
                    <div class="progress-line"></div>
                </div>
                <div class="progress-step">
                    <div class="progress-step-circle">4</div>
                    <div class="progress-step-label">Pass Ready</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-id-card"></i> Bus Pass Application Form
                    </div>
                    <div class="card-body">
                        <form id="applicationForm" method="post" action="process-application.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <!-- Transport Category Selection -->
                            <div class="form-group">
                                <label class="form-label" for="category_id"><i class="fas fa-bus"></i> Select Transport Category:</label>
                                <select id="category_id" name="category_id" class="form-control form-select" required>
                                    <option value="">Choose Transport Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                        <?php if (!empty($category['description'])): ?>
                                            - <?php echo htmlspecialchars($category['description']); ?>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Pass Type Selection -->
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-ticket-alt"></i> Select Pass Type:</label>
                                <div class="pass-types-container">
                                    <?php foreach ($passTypes as $passType): ?>
                                    <div class="pass-type-card" onclick="selectPassType(<?php echo $passType['id']; ?>)">
                                        <input type="radio" name="pass_type_id" id="pass_<?php echo $passType['id']; ?>" 
                                               value="<?php echo $passType['id']; ?>" style="display: none;">
                                        <div class="pass-type-header">
                                            <h4><?php echo htmlspecialchars($passType['type_name']); ?></h4>
                                            <span class="duration"><?php echo $passType['duration_days']; ?> days</span>
                                        </div>
                                        <div class="pass-type-details">
                                            <p><?php echo htmlspecialchars($passType['description']); ?></p>
                                            <div class="price">₹<?php echo number_format($passType['amount'], 2); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="name"><i class="fas fa-user"></i> Full Name:</label>
                                        <input type="text" id="name" name="name" class="form-control" required
                                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="dob"><i class="fas fa-calendar"></i> Date of Birth:</label>
                                        <input type="date" id="dob" name="dob" class="form-control" required
                                               value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="gender"><i class="fas fa-venus-mars"></i> Gender:</label>
                                        <select id="gender" name="gender" class="form-control form-select" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="phone"><i class="fas fa-phone"></i> Phone Number:</label>
                                        <input type="tel" id="phone" name="phone" class="form-control" required
                                               pattern="[0-9]{11}" 
                                               maxlength="11" 
                                               title="Please enter exactly 11 digits"
                                               placeholder="Enter 11-digit phone number">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="address"><i class="fas fa-map-marker-alt"></i> Address:</label>
                                <textarea id="address" name="address" class="form-control" rows="3" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="source"><i class="fas fa-map-pin"></i> Source (From):</label>
                                        <select id="source" name="source" class="form-control form-select" required>
                                            <option value="">Select Source Location</option>
                                            <?php foreach ($sources as $source): ?>
                                            <option value="<?php echo htmlspecialchars($source); ?>"
                                                    <?php echo (isset($_POST['source']) && $_POST['source'] == $source) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($source); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="destination"><i class="fas fa-flag-checkered"></i> Destination (To):</label>
                                        <select id="destination" name="destination" class="form-control form-select" required>
                                            <option value="">Select Destination</option>
                                            <?php foreach ($destinations as $destination): ?>
                                            <option value="<?php echo htmlspecialchars($destination); ?>"
                                                    <?php echo (isset($_POST['destination']) && $_POST['destination'] == $destination) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($destination); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Route Information Display -->
                            <div id="routeInfo" class="route-info" style="display: none;">
                                <div class="route-card">
                                    <div class="route-header">
                                        <i class="fas fa-route"></i> Route Information
                                    </div>
                                    <div class="route-body">
                                        <div class="route-details">
                                            <div class="route-item">
                                                <span class="route-label">Route ID:</span>
                                                <span class="route-value" id="routeId">-</span>
                                            </div>
                                            <div class="route-item">
                                                <span class="route-label">Route Name:</span>
                                                <span class="route-value" id="routeName">-</span>
                                            </div>
                                            <div class="route-item">
                                                <span class="route-label">Distance:</span>
                                                <span class="route-value" id="routeDistance">-</span>
                                            </div>
                                            <div class="route-item">
                                                <span class="route-label">Duration:</span>
                                                <span class="route-value" id="routeDuration">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- No Route Found Message -->
                            <div id="noRouteFound" class="no-route-message" style="display: none;">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>No Direct Route Found</strong><br>
                                    <span id="noRouteText">No matching route available for the selected source and destination.</span>
                                    <div id="routeSuggestions" style="margin-top: 10px;"></div>
                                </div>
                            </div>

                            <!-- ID Proof Section -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="id_proof_type"><i class="fas fa-id-card"></i> ID Proof Type:</label>
                                        <select id="id_proof_type" name="id_proof_type" class="form-control form-select" required>
                                            <option value="">Select ID Proof Type</option>
                                            <option value="Aadhaar Card" <?php echo (isset($_POST['id_proof_type']) && $_POST['id_proof_type'] == 'Aadhaar Card') ? 'selected' : ''; ?>>Aadhaar Card</option>
                                            <option value="Voter ID" <?php echo (isset($_POST['id_proof_type']) && $_POST['id_proof_type'] == 'Voter ID') ? 'selected' : ''; ?>>Voter ID</option>
                                            <option value="Driving License" <?php echo (isset($_POST['id_proof_type']) && $_POST['id_proof_type'] == 'Driving License') ? 'selected' : ''; ?>>Driving License</option>
                                            <option value="PAN Card" <?php echo (isset($_POST['id_proof_type']) && $_POST['id_proof_type'] == 'PAN Card') ? 'selected' : ''; ?>>PAN Card</option>
                                            <option value="Passport" <?php echo (isset($_POST['id_proof_type']) && $_POST['id_proof_type'] == 'Passport') ? 'selected' : ''; ?>>Passport</option>
                                            <option value="Student ID" <?php echo (isset($_POST['id_proof_type']) && $_POST['id_proof_type'] == 'Student ID') ? 'selected' : ''; ?>>Student ID</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="id_proof_number"><i class="fas fa-hashtag"></i> ID Proof Number:</label>
                                        <input type="text" id="id_proof_number" name="id_proof_number" class="form-control" required
                                               value="<?php echo isset($_POST['id_proof_number']) ? htmlspecialchars($_POST['id_proof_number']) : ''; ?>"
                                               placeholder="Enter ID proof number">
                                    </div>
                                </div>
                            </div>

                            <!-- Photo Upload Section -->
                            <div class="form-group mb-4">
                                <label class="form-label">Upload Photo <span class="text-danger">*</span></label>
                                <div class="photo-upload-container">
                                    <div id="photoPreview" class="photo-preview">
                                        <div class="placeholder">
                                            <i class="fas fa-user"></i>
                                            <p>Click to upload photo</p>
                                        </div>
                                    </div>
                                    <div class="photo-upload-dropdown">
                                        <button type="button" id="uploadDropdownBtn" class="photo-upload-btn">
                                            <i class="fas fa-camera"></i> Upload Photo
                                        </button>
                                        <div id="uploadOptions" class="photo-upload-options">
                                            <div id="startCameraOption" class="photo-option">
                                                <i class="fas fa-camera"></i> Take Photo
                                            </div>
                                            <div id="choosePhotoOption" class="photo-option">
                                                <i class="fas fa-image"></i> Choose from Gallery
                                            </div>
                                        </div>
                                    </div>
                                    <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png" required style="display: none;">
                                    <div class="photo-requirements">
                                        <p class="text-muted small mt-2">
                                            <i class="fas fa-info-circle"></i> Requirements:
                                            <ul class="mt-1">
                                                <li>Format: JPG or PNG only</li>
                                                <li>Max size: 5MB</li>
                                                <li>Clear, front-facing photo</li>
                                            </ul>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Camera Modal -->
                            <div id="cameraModal" class="modal">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h2>Take Photo</h2>
                                        <span class="close">&times;</span>
                                    </div>
                                    <div class="modal-body">
                                        <div class="camera-container">
                                            <video id="camera" autoplay playsinline></video>
                                            <canvas id="canvas" style="display: none;"></canvas>
                                            <div id="photo-preview" style="display: none;">
                                                <img id="captured-photo" alt="Captured photo">
                                            </div>
                                        </div>
                                        <div class="camera-controls">
                                            <button id="capturePhoto" class="camera-btn">
                                                <i class="fas fa-camera"></i> Capture Photo
                                            </button>
                                            <button id="retakePhoto" class="camera-btn secondary" style="display: none;">
                                                <i class="fas fa-redo"></i> Retake
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check-circle me-2"></i>Submit Application
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card guidelines-sidebar">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Application Guidelines
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: #28a745; font-weight: 600;">
                                <i class="fas fa-check-circle"></i> Fill all required fields
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: #28a745; font-weight: 600;">
                                <i class="fas fa-check-circle"></i> Upload a clear photo
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: #28a745; font-weight: 600;">
                                <i class="fas fa-check-circle"></i> Provide valid contact details
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: #28a745; font-weight: 600;">
                                <i class="fas fa-check-circle"></i> Select appropriate pass type
                            </div>
                        </div>

                        <div style="background: linear-gradient(135deg, rgba(90, 79, 207, 0.1) 0%, rgba(106, 103, 213, 0.1) 100%); padding: 1.5rem; border-radius: 12px; border: 1px solid rgba(90, 79, 207, 0.2);">
                            <div style="color: #5A4FCF; font-weight: 600; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-lightbulb"></i> Processing Information
                            </div>
                            <p style="margin: 0; color: #4B0082; line-height: 1.5;">
                                Processing time is 1-2 business days after payment confirmation. You'll receive email updates on your application status.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function selectPassType(typeId) {
            // Remove selected class from all cards
            document.querySelectorAll('.pass-type-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');

            // Check the radio button
            document.getElementById('pass_' + typeId).checked = true;
        }

        // Auto-select first pass type if none selected
        document.addEventListener('DOMContentLoaded', function() {
            const firstPassType = document.querySelector('.pass-type-card');
            if (firstPassType && !document.querySelector('input[name="pass_type_id"]:checked')) {
                firstPassType.click();
            }

            // Initialize route management
            initializeRouteManagement();
        });

        // Route Management Functions
        function initializeRouteManagement() {
            loadSources();
            setupEventListeners();
        }

        function setupEventListeners() {
            const sourceSelect = document.getElementById('source');
            const destinationSelect = document.getElementById('destination');

            if (sourceSelect) {
                sourceSelect.addEventListener('change', function() {
                    loadDestinations(this.value);
                    checkRoute();
                });
            }

            if (destinationSelect) {
                destinationSelect.addEventListener('change', function() {
                    checkRoute();
                });
            }
        }

        function loadSources() {
            fetch('get-route-info.php?action=get_sources')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const sourceSelect = document.getElementById('source');
                        sourceSelect.innerHTML = '<option value="">Select Source Location</option>';

                        data.sources.forEach(source => {
                            const option = document.createElement('option');
                            option.value = source;
                            option.textContent = source;
                            sourceSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading sources:', error);
                });
        }

        function loadDestinations(source = '') {
            const url = source ?
                `get-route-info.php?action=get_destinations&source=${encodeURIComponent(source)}` :
                'get-route-info.php?action=get_destinations';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const destinationSelect = document.getElementById('destination');
                        destinationSelect.innerHTML = '<option value="">Select Destination</option>';

                        data.destinations.forEach(destination => {
                            const option = document.createElement('option');
                            option.value = destination;
                            option.textContent = destination;
                            destinationSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading destinations:', error);
                });
        }

        function checkRoute() {
            const source = document.getElementById('source').value;
            const destination = document.getElementById('destination').value;

            // Hide both route info and no route message
            document.getElementById('routeInfo').style.display = 'none';
            document.getElementById('noRouteFound').style.display = 'none';

            if (!source || !destination) {
                return;
            }

            // Show loading state
            const routeInfo = document.getElementById('routeInfo');
            routeInfo.style.display = 'block';
            routeInfo.style.opacity = '0.5';

            fetch(`get-route-info.php?action=find_route&source=${encodeURIComponent(source)}&destination=${encodeURIComponent(destination)}`)
                .then(response => response.json())
                .then(data => {
                    routeInfo.style.opacity = '1';

                    if (data.success && data.route) {
                        displayRouteInfo(data.route);
                    } else {
                        displayNoRouteFound(data.message, data.suggestions || []);
                    }
                })
                .catch(error => {
                    console.error('Error checking route:', error);
                    routeInfo.style.opacity = '1';
                    displayNoRouteFound('Error checking route information. Please try again.');
                });
        }

        function displayRouteInfo(route) {
            document.getElementById('routeId').textContent = route.route_id;
            document.getElementById('routeName').textContent = route.route_name;
            document.getElementById('routeDistance').textContent = route.distance_km ?
                `${route.distance_km} km` : 'Not specified';
            document.getElementById('routeDuration').textContent = route.estimated_duration || 'Not specified';

            document.getElementById('routeInfo').style.display = 'block';
            document.getElementById('noRouteFound').style.display = 'none';
        }

        function displayNoRouteFound(message, suggestions = []) {
            document.getElementById('routeInfo').style.display = 'none';
            document.getElementById('noRouteFound').style.display = 'block';
            document.getElementById('noRouteText').textContent = message;

            const suggestionsContainer = document.getElementById('routeSuggestions');
            suggestionsContainer.innerHTML = '';

            if (suggestions.length > 0) {
                const suggestionsTitle = document.createElement('div');
                suggestionsTitle.innerHTML = '<strong>Similar routes available:</strong>';
                suggestionsTitle.style.marginBottom = '10px';
                suggestionsContainer.appendChild(suggestionsTitle);

                suggestions.forEach(suggestion => {
                    const suggestionDiv = document.createElement('div');
                    suggestionDiv.className = 'suggestion-item';
                    suggestionDiv.innerHTML = `
                        <strong>${suggestion.route_id}</strong> - ${suggestion.route_name}<br>
                        <small>${suggestion.source} → ${suggestion.destination}</small>
                    `;
                    suggestionDiv.onclick = () => {
                        document.getElementById('source').value = suggestion.source;
                        document.getElementById('destination').value = suggestion.destination;
                        checkRoute();
                    };
                    suggestionsContainer.appendChild(suggestionDiv);
                });
            }
        }

        // Form submission handling
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('applicationForm');

            form.addEventListener('submit', function(e) {
                console.log('Form submission started');

                // Show loading state immediately
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                // Basic validation only - let PHP handle detailed validation
                const requiredFields = ['name', 'dob', 'gender', 'phone', 'address', 'source', 'destination', 'pass_type_id', 'category_id', 'id_proof_type', 'id_proof_number'];
                let missingFields = [];

                requiredFields.forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (!input || !input.value.trim()) {
                        missingFields.push(field);
                    }
                });

                // Check if photo is selected (more lenient check)
                const photoInput = document.getElementById('photoInput');
                if (!photoInput || !photoInput.files || photoInput.files.length === 0) {
                    missingFields.push('photo');
                }

                if (missingFields.length > 0) {
                    e.preventDefault();
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Submit Application';
                    showMessage('Please fill in all required fields: ' + missingFields.join(', '), 'error');
                    return;
                }

                // If we get here, allow form submission
                console.log('Form validation passed, submitting...');
                // Form will submit normally to PHP
            });
        });

        // Function to show messages
        function showMessage(message, type = 'success') {
            const messageDiv = document.createElement('div');
            messageDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
            messageDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Insert message at the top of the form
            const form = document.getElementById('applicationForm');
            form.insertBefore(messageDiv, form.firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        // Photo upload functionality
        document.addEventListener('DOMContentLoaded', function() {
            const photoInput = document.getElementById('photoInput');
            const uploadDropdownBtn = document.getElementById('uploadDropdownBtn');
            const uploadOptions = document.getElementById('uploadOptions');
            const startCameraOption = document.getElementById('startCameraOption');
            const choosePhotoOption = document.getElementById('choosePhotoOption');
            const photoPreview = document.getElementById('photoPreview');
            const cameraModal = document.getElementById('cameraModal');
            const closeModal = document.querySelector('.close');
            const capturePhotoBtn = document.getElementById('capturePhoto');
            const retakePhotoBtn = document.getElementById('retakePhoto');
            const camera = document.getElementById('camera');
            const canvas = document.getElementById('canvas');
            const photoPreviewDiv = document.getElementById('photo-preview');
            const capturedPhoto = document.getElementById('captured-photo');

            let stream = null;

            // Toggle dropdown
            uploadDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                uploadOptions.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!uploadDropdownBtn.contains(e.target) && !uploadOptions.contains(e.target)) {
                    uploadOptions.classList.remove('show');
                }
            });

            // Start camera option
            startCameraOption.addEventListener('click', function(e) {
                e.stopPropagation();
                uploadOptions.classList.remove('show');
                startCamera();
            });

            // Choose photo option
            choosePhotoOption.addEventListener('click', function(e) {
                e.stopPropagation();
                uploadOptions.classList.remove('show');
                photoInput.click();
            });

            // Close modal
            closeModal.addEventListener('click', function() {
                stopCamera();
                cameraModal.style.display = 'none';
            });

            // Start camera function
            async function startCamera() {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { 
                            facingMode: 'user',
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        } 
                    });
                    
                    camera.srcObject = stream;
                    cameraModal.style.display = 'block';
                    photoPreviewDiv.style.display = 'none';
                    camera.style.display = 'block';
                    capturePhotoBtn.style.display = 'inline-flex';
                    retakePhotoBtn.style.display = 'none';
                    
                } catch (err) {
                    showMessage('Camera access denied: ' + err.message, 'error');
                }
            }

            // Stop camera function
            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
            }

            // Capture photo
            capturePhotoBtn.addEventListener('click', function() {
                canvas.width = camera.videoWidth;
                canvas.height = camera.videoHeight;
                const context = canvas.getContext('2d');
                context.drawImage(camera, 0, 0, canvas.width, canvas.height);
                
                const imageData = canvas.toDataURL('image/jpeg', 0.8);
                capturedPhoto.src = imageData;
                
                camera.style.display = 'none';
                photoPreviewDiv.style.display = 'block';
                capturePhotoBtn.style.display = 'none';
                retakePhotoBtn.style.display = 'inline-flex';
            });

            // Retake photo
            retakePhotoBtn.addEventListener('click', function() {
                photoPreviewDiv.style.display = 'none';
                camera.style.display = 'block';
                capturePhotoBtn.style.display = 'inline-flex';
                retakePhotoBtn.style.display = 'none';
            });

            // Handle file selection
            photoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Check file size (5MB limit)
                    if (file.size > 5 * 1024 * 1024) {
                        showMessage('File size exceeds 5MB limit', 'error');
                        this.value = '';
                        return;
                    }
                    
                    // Check file type
                    const validTypes = ['image/jpeg', 'image/png'];
                    if (!validTypes.includes(file.type)) {
                        showMessage('Invalid file type. Please upload JPG or PNG', 'error');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        photoPreview.innerHTML = `<img src="${e.target.result}" alt="Selected photo">`;
                        showMessage('Photo uploaded successfully!', 'success');
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Click on preview to trigger file input
            photoPreview.addEventListener('click', function() {
                photoInput.click();
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === cameraModal) {
                    stopCamera();
                    cameraModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>