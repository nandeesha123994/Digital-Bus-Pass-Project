<?php
/**
 * PROCESS APPLICATION - DIRECT REDIRECT TO PAYMENT
 * This file processes the application and redirects directly to payment
 */

session_start();
include('includes/dbconnection.php');
include('includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit();
}

// Function to generate unique Application ID
function generateApplicationId($con) {
    do {
        $year = date('Y');
        $randomNumber = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $applicationId = "BPMS{$year}{$randomNumber}";

        $checkQuery = "SELECT id FROM bus_pass_applications WHERE application_id = ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->bind_param("s", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();

    } while ($result->num_rows > 0);

    return $applicationId;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get user information
        $userQuery = "SELECT full_name, email FROM users WHERE id = ?";
        $userStmt = $con->prepare($userQuery);
        $userStmt->bind_param("i", $_SESSION['uid']);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();

        if (!$user) {
            throw new Exception("User not found");
        }

        // Get form data
        $name = trim($_POST['name'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $source = trim($_POST['source'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $passTypeId = intval($_POST['pass_type_id'] ?? 0);
        $categoryId = intval($_POST['category_id'] ?? 0);
        $idProofType = trim($_POST['id_proof_type'] ?? '');
        $idProofNumber = trim($_POST['id_proof_number'] ?? '');

        // Basic validation
        if (empty($name) || empty($dob) || empty($gender) || empty($phone) || 
            empty($address) || empty($source) || empty($destination) || 
            $passTypeId <= 0 || $categoryId <= 0 || empty($idProofType) || empty($idProofNumber)) {
            throw new Exception("All fields are required");
        }

        // Handle photo upload
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Photo upload is required");
        }

        $photo = $_FILES['photo'];
        $allowedTypes = ['image/jpeg', 'image/png'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $photo['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("Only JPG and PNG images are allowed");
        }
        
        if ($photo['size'] > 5 * 1024 * 1024) {
            throw new Exception("Photo size should be less than 5MB");
        }

        // Generate application ID
        $generatedApplicationId = generateApplicationId($con);
        
        // Get pass type details
        $passTypeQuery = "SELECT * FROM bus_pass_types WHERE id = ?";
        $passTypeStmt = $con->prepare($passTypeQuery);
        $passTypeStmt->bind_param("i", $passTypeId);
        $passTypeStmt->execute();
        $passType = $passTypeStmt->get_result()->fetch_assoc();
        
        if (!$passType) {
            throw new Exception("Invalid pass type selected");
        }

        $amount = $passType['amount'];
        
        // Handle photo upload
        $uploadDir = 'uploads/photos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $photoExt = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
        $photoFileName = $generatedApplicationId . '_' . time() . '.' . $photoExt;
        $photoPath = $uploadDir . $photoFileName;
        
        if (!move_uploaded_file($photo['tmp_name'], $photoPath)) {
            throw new Exception("Failed to save the uploaded photo");
        }

        // Insert application into database
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
            $idProofType,
            $idProofNumber,
            $user['email']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to submit application: " . $stmt->error);
        }

        $applicationId = $stmt->insert_id;
        
        // Store payment details in session
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

        // DIRECT REDIRECT TO PAYMENT PAGE
        $redirectUrl = "payment.php?application_id=" . $applicationId;
        
        // Clear any output and redirect
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header("Location: " . $redirectUrl, true, 302);
        exit();

    } catch (Exception $e) {
        // If there's an error, redirect back to form with error message
        $_SESSION['form_error'] = $e->getMessage();
        header("Location: apply-pass.php");
        exit();
    }
} else {
    // If not POST request, redirect to form
    header("Location: apply-pass.php");
    exit();
}
?>
