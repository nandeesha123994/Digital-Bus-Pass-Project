<?php
/**
 * Download QR Code
 * Handles QR code download requests for bus passes
 */

session_start();
include('includes/dbconnection.php');
include('includes/qr-generator.php');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Get application ID
$applicationId = isset($_GET['app_id']) ? intval($_GET['app_id']) : 0;

if ($applicationId <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid application ID');
}

try {
    // Get application details
    $query = "SELECT ba.*, u.full_name, bpt.type_name 
              FROM bus_pass_applications ba
              JOIN users u ON ba.user_id = u.id
              LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
              WHERE ba.id = ? AND ba.user_id = ? AND ba.status = 'Approved' AND ba.pass_number IS NOT NULL";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $applicationId, $_SESSION['uid']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('HTTP/1.1 404 Not Found');
        exit('Pass not found or not approved');
    }
    
    $pass = $result->fetch_assoc();
    
    // Generate QR code data
    $route = $pass['source'] . ' → ' . $pass['destination'];
    $qrResult = QRCodeGenerator::createBrandedQR(
        $pass['id'],
        $pass['pass_number'],
        $pass['full_name'],
        $route,
        $pass['valid_until'],
        $pass['type_name'] ?? 'Standard'
    );
    
    if (!$qrResult['success']) {
        header('HTTP/1.1 500 Internal Server Error');
        exit('Failed to generate QR code');
    }
    
    // Download the QR code
    $filename = 'bus_pass_qr_' . $pass['pass_number'] . '.png';
    
    if ($qrResult['type'] === 'google') {
        // Download from Google Charts API
        $imageData = file_get_contents($qrResult['url']);
        
        if ($imageData === false) {
            header('HTTP/1.1 500 Internal Server Error');
            exit('Failed to download QR code');
        }
        
        // Set headers for download
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($imageData));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Output the image
        echo $imageData;
        
    } else {
        // Local file
        if (file_exists($qrResult['path'])) {
            // Set headers for download
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($qrResult['path']));
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            
            // Output the file
            readfile($qrResult['path']);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            exit('QR code file not found');
        }
    }
    
} catch (Exception $e) {
    error_log('QR Download Error: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Internal server error');
}

$con->close();
?>
