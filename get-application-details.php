<?php
session_start();
include('includes/dbconnection.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access - admin not logged in']);
    exit();
}

// Check if application ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit();
}

$applicationId = intval($_GET['id']);

try {
    // First, let's check if the application exists
    $checkQuery = "SELECT COUNT(*) as count FROM bus_pass_applications WHERE id = ?";
    $checkStmt = $con->prepare($checkQuery);
    $checkStmt->bind_param("i", $applicationId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $count = $checkResult->fetch_assoc()['count'];

    if ($count == 0) {
        echo json_encode(['success' => false, 'message' => 'Application not found']);
        exit();
    }

    // Get application details with all related information
    $query = "SELECT ba.*,
                     u.full_name as user_name,
                     u.email as user_email,
                     bpt.type_name,
                     bpt.duration_days,
                     p.transaction_id,
                     p.payment_method,
                     p.payment_date
              FROM bus_pass_applications ba
              LEFT JOIN users u ON ba.user_id = u.id
              LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
              LEFT JOIN payments p ON ba.id = p.application_id
              WHERE ba.id = ?";

    $stmt = $con->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $con->error]);
        exit();
    }

    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Application not found after query']);
        exit();
    }

    $application = $result->fetch_assoc();

    // Format the data for JSON response with null checks
    $response = [
        'success' => true,
        'application' => [
            'id' => $application['id'] ?? '',
            'applicant_name' => $application['applicant_name'] ?? '',
            'date_of_birth' => $application['date_of_birth'] ?? '',
            'gender' => $application['gender'] ?? '',
            'contact_number' => $application['contact_number'] ?? '',
            'address' => $application['address'] ?? '',
            'user_name' => $application['user_name'] ?? '',
            'user_email' => $application['user_email'] ?? '',
            'type_name' => $application['type_name'] ?? '',
            'duration_days' => $application['duration_days'] ?? '',
            'source' => $application['source'] ?? '',
            'destination' => $application['destination'] ?? '',
            'amount' => $application['amount'] ?? '0',
            'application_date' => $application['application_date'] ?? '',
            'status' => $application['status'] ?? 'Pending',
            'payment_status' => $application['payment_status'] ?? 'Pending',
            'admin_remarks' => $application['admin_remarks'] ?? '',
            'pass_number' => $application['pass_number'] ?? '',
            'valid_from' => $application['valid_from'] ?? '',
            'valid_until' => $application['valid_until'] ?? '',
            'processed_date' => $application['processed_date'] ?? '',
            'id_proof_path' => $application['id_proof_path'] ?? '',
            'transaction_id' => $application['transaction_id'] ?? '',
            'payment_method' => $application['payment_method'] ?? '',
            'payment_date' => $application['payment_date'] ?? ''
        ]
    ];

    // Set content type to JSON
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
