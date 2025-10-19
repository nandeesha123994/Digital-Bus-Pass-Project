<?php
session_start();
require_once 'includes/dbconnection.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    // Update session
    $_SESSION['payment_status'] = $data['status'];
    $_SESSION['payment_method'] = 'phonepe';
    $_SESSION['upi_id'] = $data['upi_id'];

    // Update database
    $applicationId = $_SESSION['application_id'] ?? null;
    if ($applicationId) {
        $stmt = $conn->prepare("UPDATE bus_pass_applications SET 
            payment_status = ?, 
            payment_method = ?,
            payment_details = ?,
            payment_date = NOW()
            WHERE application_id = ?");
        
        $paymentDetails = json_encode([
            'upi_id' => $data['upi_id'],
            'amount' => $data['amount']
        ]);
        
        $stmt->bind_param("ssss", 
            $data['status'],
            'phonepe',
            $paymentDetails,
            $applicationId
        );
        
        $stmt->execute();
    }

    // Send success response
    echo json_encode(['status' => 'success']);
} else {
    // Send error response
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
}
?> 