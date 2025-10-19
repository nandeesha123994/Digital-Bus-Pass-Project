<?php
/**
 * Mark Notification as Read
 * Handles AJAX requests to mark notifications as read or dismissed
 */

session_start();
include('includes/dbconnection.php');

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['notification_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$notificationId = intval($input['notification_id']);
$userId = $_SESSION['uid'];
$isDismiss = isset($input['dismiss']) && $input['dismiss'];

try {
    $con->begin_transaction();
    
    // Verify notification belongs to current user
    $verifyQuery = "SELECT id FROM notifications WHERE id = ? AND user_id = ?";
    $verifyStmt = $con->prepare($verifyQuery);
    $verifyStmt->bind_param("ii", $notificationId, $userId);
    $verifyStmt->execute();
    
    if ($verifyStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Notification not found']);
        exit();
    }
    
    // Mark notification as read
    $updateQuery = "UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?";
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->bind_param("ii", $notificationId, $userId);
    
    if ($updateStmt->execute()) {
        // Log the read action
        $logQuery = "UPDATE notification_log SET status = 'read', read_at = NOW() WHERE user_id = ? AND notification_type IN (SELECT type FROM notifications WHERE id = ?)";
        $logStmt = $con->prepare($logQuery);
        $logStmt->bind_param("ii", $userId, $notificationId);
        $logStmt->execute();
        
        $con->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $isDismiss ? 'Notification dismissed' : 'Notification marked as read'
        ]);
    } else {
        throw new Exception('Failed to update notification');
    }
    
} catch (Exception $e) {
    $con->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$con->close();
?>
