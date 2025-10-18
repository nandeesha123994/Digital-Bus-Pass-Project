<?php
session_start();
include('includes/dbconnection.php');
include('includes/config.php');
include('includes/email.php');
include('includes/admin-logger.php');

// Debug: Check session status
echo "<!-- Debug Info: ";
echo "Admin logged in: " . (isset($_SESSION['admin_logged_in']) ? 'Yes' : 'No') . ", ";
echo "Admin value: " . ($_SESSION['admin_logged_in'] ?? 'Not set') . ", ";
echo "User ID: " . ($_SESSION['uid'] ?? 'Not set');
echo " -->";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Initialize admin logger
AdminLogger::init($con);

// Get admin details for logging
$adminId = $_SESSION['admin_username'] ?? 'admin';
$adminName = $_SESSION['admin_name'] ?? 'System Administrator';

$message = '';
$messageType = '';

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_applications']) && !empty($_POST['selected_applications'])) {
    $bulkAction = $_POST['bulk_action'];
    $selectedIds = $_POST['selected_applications'];
    $affectedRows = 0;

    // Validate that all selected IDs are integers
    $selectedIds = array_filter($selectedIds, 'is_numeric');
    $selectedIds = array_map('intval', $selectedIds);

    if (!empty($selectedIds)) {
        $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';

        switch ($bulkAction) {
            case 'approve':
                // First get application details for emails
                $emailQuery = "SELECT ba.id, ba.applicant_name, u.full_name as user_name, u.email as user_email, bpt.duration_days
                              FROM bus_pass_applications ba
                              LEFT JOIN users u ON ba.user_id = u.id
                              LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                              WHERE ba.id IN ($placeholders)";
                $emailStmt = $con->prepare($emailQuery);
                $emailStmt->bind_param(str_repeat('i', count($selectedIds)), ...$selectedIds);
                $emailStmt->execute();
                $emailApps = $emailStmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $bulkQuery = "UPDATE bus_pass_applications SET status = 'Approved', admin_remarks = 'Bulk approved by admin', processed_date = NOW() WHERE id IN ($placeholders)";
                $stmt = $con->prepare($bulkQuery);
                $stmt->bind_param(str_repeat('i', count($selectedIds)), ...$selectedIds);
                if ($stmt->execute()) {
                    $affectedRows = $stmt->affected_rows;

                    // Log bulk approval actions
                    foreach ($emailApps as $app) {
                        AdminLogger::logBulkAction(
                            $adminId,
                            $adminName,
                            $app['id'],
                            $app['applicant_name'],
                            'Approve',
                            'Approved',
                            'Bulk approved by admin'
                        );
                    }

                    // Send emails to all approved applications
                    $emailsSent = 0;
                    foreach ($emailApps as $app) {
                        if (!empty($app['user_email'])) {
                            try {
                                if (EmailService::sendStatusUpdate(
                                    $app['user_email'],
                                    $app['user_name'],
                                    $app['id'],
                                    'Approved',
                                    'Bulk approved by admin'
                                )) {
                                    $emailsSent++;
                                }
                            } catch (Exception $e) {
                                logError("Bulk email failed for application {$app['id']}: " . $e->getMessage());
                            }
                        }
                    }

                    $message = "✅ Successfully approved $affectedRows application(s). Email notifications sent to $emailsSent user(s).";
                    $messageType = "success";
                }
                break;

            case 'reject':
                // First get application details for emails
                $emailQuery = "SELECT ba.id, ba.applicant_name, u.full_name as user_name, u.email as user_email
                              FROM bus_pass_applications ba
                              LEFT JOIN users u ON ba.user_id = u.id
                              WHERE ba.id IN ($placeholders)";
                $emailStmt = $con->prepare($emailQuery);
                $emailStmt->bind_param(str_repeat('i', count($selectedIds)), ...$selectedIds);
                $emailStmt->execute();
                $emailApps = $emailStmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $bulkQuery = "UPDATE bus_pass_applications SET status = 'Rejected', admin_remarks = 'Bulk rejected by admin', processed_date = NOW() WHERE id IN ($placeholders)";
                $stmt = $con->prepare($bulkQuery);
                $stmt->bind_param(str_repeat('i', count($selectedIds)), ...$selectedIds);
                if ($stmt->execute()) {
                    $affectedRows = $stmt->affected_rows;

                    // Log bulk rejection actions
                    foreach ($emailApps as $app) {
                        AdminLogger::logBulkAction(
                            $adminId,
                            $adminName,
                            $app['id'],
                            $app['applicant_name'],
                            'Reject',
                            'Rejected',
                            'Bulk rejected by admin'
                        );
                    }

                    // Send emails to all rejected applications
                    $emailsSent = 0;
                    foreach ($emailApps as $app) {
                        if (!empty($app['user_email'])) {
                            try {
                                if (EmailService::sendStatusUpdate(
                                    $app['user_email'],
                                    $app['user_name'],
                                    $app['id'],
                                    'Rejected',
                                    'Bulk rejected by admin'
                                )) {
                                    $emailsSent++;
                                }
                            } catch (Exception $e) {
                                logError("Bulk email failed for application {$app['id']}: " . $e->getMessage());
                            }
                        }
                    }

                    $message = "✅ Successfully rejected $affectedRows application(s). Email notifications sent to $emailsSent user(s).";
                    $messageType = "success";
                }
                break;

            case 'payment_required':
                $bulkQuery = "UPDATE bus_pass_applications SET payment_status = 'Pending', admin_remarks = 'Payment required - bulk action' WHERE id IN ($placeholders)";
                $stmt = $con->prepare($bulkQuery);
                $stmt->bind_param(str_repeat('i', count($selectedIds)), ...$selectedIds);
                if ($stmt->execute()) {
                    $affectedRows = $stmt->affected_rows;
                    $message = "Successfully marked $affectedRows application(s) as payment required.";
                    $messageType = "success";
                }
                break;

            case 'mark_paid':
                // First get application details for logging
                $emailQuery = "SELECT ba.id, ba.applicant_name, u.full_name as user_name, u.email as user_email
                              FROM bus_pass_applications ba
                              LEFT JOIN users u ON ba.user_id = u.id
                              WHERE ba.id IN ($placeholders)";
                $emailStmt = $con->prepare($emailQuery);
                $emailStmt->bind_param(str_repeat('i', count($selectedIds)), ...$selectedIds);
                $emailStmt->execute();
                $emailApps = $emailStmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $bulkQuery = "UPDATE bus_pass_applications SET payment_status = 'Paid', admin_remarks = 'Payment marked as paid by admin - bulk action' WHERE id IN ($placeholders)";
                $stmt = $con->prepare($bulkQuery);
                $stmt->bind_param(str_repeat('i', count($selectedIds)), ...$selectedIds);
                if ($stmt->execute()) {
                    $affectedRows = $stmt->affected_rows;

                    // Log bulk payment update actions
                    foreach ($emailApps as $app) {
                        AdminLogger::logBulkAction(
                            $adminId,
                            $adminName,
                            $app['id'],
                            $app['applicant_name'],
                            'Mark Paid',
                            'Paid',
                            'Payment marked as paid by admin - bulk action'
                        );
                    }

                    $message = "✅ Successfully marked $affectedRows application(s) as paid.";
                    $messageType = "success";
                }
                break;

            case 'delete':
                // First delete related payments
                $deletePaymentsQuery = "DELETE FROM payments WHERE application_id IN ($placeholders)";
                $stmt = $con->prepare($deletePaymentsQuery);
                $stmt->bind_param(str_repeat('i', count($selectedIds)), ...$selectedIds);
                $stmt->execute();

                // Then delete applications
                $deleteQuery = "DELETE FROM bus_pass_applications WHERE id IN ($placeholders)";
                $stmt = $con->prepare($deleteQuery);
                $stmt->bind_param(str_repeat('i', count($selectedIds)), ...$selectedIds);
                if ($stmt->execute()) {
                    $affectedRows = $stmt->affected_rows;
                    $message = "Successfully deleted $affectedRows application(s) and related data.";
                    $messageType = "success";
                }
                break;

            default:
                $message = "Invalid bulk action selected.";
                $messageType = "error";
        }
    } else {
        $message = "No valid applications selected for bulk action.";
        $messageType = "error";
    }
}

// Handle direct approve/reject actions
if (isset($_GET['direct_action']) && isset($_GET['app_id'])) {
    $action = $_GET['direct_action'];
    $applicationId = intval($_GET['app_id']);

    // Get application details
    $appQuery = "SELECT ba.*, u.full_name as user_name, u.email as user_email
                 FROM bus_pass_applications ba
                 LEFT JOIN users u ON ba.user_id = u.id
                 WHERE ba.id = ?";
    $appStmt = $con->prepare($appQuery);
    $appStmt->bind_param("i", $applicationId);
    $appStmt->execute();
    $appDetails = $appStmt->get_result()->fetch_assoc();

    if ($appDetails) {
        $success = false;
        $newStatus = $appDetails['status'];
        $remarks = '';

        switch ($action) {
            case 'approve':
                $newStatus = 'Approved';
                $remarks = 'Application approved by admin';

                // Generate pass number and set validity dates for immediate printing
                $passNumber = 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);
                $validFrom = date('Y-m-d');
                $validUntil = date('Y-m-d', strtotime('+30 days'));

                // Update with all fields needed for printing (bypass payment requirement)
                $updateQuery = "UPDATE bus_pass_applications SET
                               status = 'Approved',
                               payment_status = 'Paid',
                               pass_number = ?,
                               valid_from = ?,
                               valid_until = ?,
                               admin_remarks = ?,
                               processed_date = NOW()
                               WHERE id = ?";
                break;
            case 'reject':
                $newStatus = 'Rejected';
                $remarks = 'Application rejected by admin';
                $updateQuery = "UPDATE bus_pass_applications SET status = 'Rejected', admin_remarks = ?, processed_date = NOW() WHERE id = ?";
                break;
            case 'mark_paid':
                $remarks = 'Payment marked as paid by admin';
                $updateQuery = "UPDATE bus_pass_applications SET payment_status = 'Paid', admin_remarks = ?, processed_date = NOW() WHERE id = ?";
                break;
        }

        if (isset($updateQuery)) {
            $updateStmt = $con->prepare($updateQuery);

            // Different parameter binding based on action
            if ($action === 'approve') {
                $updateStmt->bind_param("ssssi", $passNumber, $validFrom, $validUntil, $remarks, $applicationId);
            } else {
                $updateStmt->bind_param("si", $remarks, $applicationId);
            }

            if ($updateStmt->execute()) {
                // Log the action
                AdminLogger::logStatusUpdate(
                    $adminId,
                    $adminName,
                    $applicationId,
                    $appDetails['applicant_name'],
                    $appDetails['status'],
                    $newStatus,
                    $remarks
                );

                // Send email notification
                if (!empty($appDetails['user_email'])) {
                    try {
                        EmailService::sendStatusUpdate(
                            $appDetails['user_email'],
                            $appDetails['user_name'],
                            $applicationId,
                            $newStatus,
                            $remarks
                        );
                    } catch (Exception $e) {
                        logError("Email sending failed: " . $e->getMessage());
                    }
                }

                $message = "✅ Application #$applicationId " . strtolower($action) . "d successfully!";
                $messageType = "success";
            } else {
                $message = "❌ Error updating application: " . $con->error;
                $messageType = "error";
            }
        }
    } else {
        $message = "❌ Application not found!";
        $messageType = "error";
    }
}

// Include email functionality
require_once 'includes/mailHelper.php';
require_once 'includes/email-templates.php';

// Handle application status updates
if (isset($_POST['update_status'])) {
    $applicationId = $_POST['application_id'];
    $newStatus = $_POST['status'];
    $newPaymentStatus = $_POST['payment_status'] ?? null;
    $remarks = trim($_POST['remarks']);

    // Get application details for email
    $appQuery = "SELECT ba.*, u.full_name as user_name, u.email as user_email,
                        bpt.type_name, bpt.duration_days
                 FROM bus_pass_applications ba
                 LEFT JOIN users u ON ba.user_id = u.id
                 LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                 WHERE ba.id = ?";
    $appStmt = $con->prepare($appQuery);
    $appStmt->bind_param("i", $applicationId);
    $appStmt->execute();
    $appDetails = $appStmt->get_result()->fetch_assoc();

    $updateQuery = "UPDATE bus_pass_applications SET status = ?, admin_remarks = ?, processed_date = NOW()";
    $params = [$newStatus, $remarks];
    $types = "ss";

    // Add payment status update if provided
    if ($newPaymentStatus !== null && !empty($newPaymentStatus)) {
        $updateQuery .= ", payment_status = ?";
        $params[] = $newPaymentStatus;
        $types .= "s";
    }

    // If approved, set validity dates
    if ($newStatus === 'Approved' && $appDetails) {
        $validFrom = date('Y-m-d');
        $validUntil = date('Y-m-d', strtotime("+{$appDetails['duration_days']} days"));
        $updateQuery .= ", valid_from = ?, valid_until = ?";
        $params[] = $validFrom;
        $params[] = $validUntil;
        $types .= "ss";
    }

    $updateQuery .= " WHERE id = ?";
    $params[] = $applicationId;
    $types .= "i";

    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->bind_param($types, ...$params);

    if ($updateStmt->execute()) {
        $emailSent = false;
        $emailError = '';

        // Log the status update action
        if ($appDetails) {
            $logRemarks = $remarks;
            if ($newPaymentStatus !== null && $newPaymentStatus !== $appDetails['payment_status']) {
                $logRemarks .= " | Payment status changed from '{$appDetails['payment_status']}' to '$newPaymentStatus'";
            }

            AdminLogger::logStatusUpdate(
                $adminId,
                $adminName,
                $applicationId,
                $appDetails['applicant_name'],
                $appDetails['status'], // old status
                $newStatus, // new status
                $logRemarks
            );
        }

        // Send status update email using enhanced MailHelper
        if ($appDetails && !empty($appDetails['user_email'])) {
            try {
                // Prepare email data
                $emailData = [
                    'user_name' => $appDetails['user_name'],
                    'application_id' => $applicationId,
                    'pass_type' => $appDetails['type_name'],
                    'remarks' => $remarks,
                    'dashboard_url' => (defined('SITE_URL') ? SITE_URL : 'http://localhost/buspassmsfull') . '/user-dashboard.php',
                    'support_url' => (defined('SITE_URL') ? SITE_URL : 'http://localhost/buspassmsfull') . '/contact-support.php',
                    'reapply_url' => (defined('SITE_URL') ? SITE_URL : 'http://localhost/buspassmsfull') . '/apply-pass.php'
                ];

                // Add pass details if approved
                if ($newStatus === 'Approved' && isset($validFrom) && isset($validUntil)) {
                    $emailData['pass_number'] = $appDetails['pass_number'] ?? 'BP' . date('Y') . str_pad($applicationId, 6, '0', STR_PAD_LEFT);
                    $emailData['valid_from'] = $validFrom;
                    $emailData['valid_until'] = $validUntil;
                }

                // Send appropriate email based on status
                if ($newStatus === 'Approved') {
                    $emailTemplate = EmailTemplates::getTemplate('approval', $emailData);
                    $subject = "🎉 Bus Pass Application Approved - Application #$applicationId";
                } elseif ($newStatus === 'Rejected') {
                    $emailTemplate = EmailTemplates::getTemplate('rejection', $emailData);
                    $subject = "📋 Bus Pass Application Update - Application #$applicationId";
                } else {
                    $emailTemplate = EmailTemplates::getTemplate('general', array_merge($emailData, [
                        'subject' => "Bus Pass Application Update",
                        'message' => "Your application status has been updated to: $newStatus. " . ($remarks ? "Notes: $remarks" : "")
                    ]));
                    $subject = "📋 Bus Pass Application Update - Application #$applicationId";
                }

                $emailSent = MailHelper::sendEmail(
                    $appDetails['user_email'],
                    $subject,
                    $emailTemplate,
                    true
                );

            } catch (Exception $e) {
                $emailError = $e->getMessage();
                error_log("Email sending failed: " . $emailError);
            }
        }

        // Set success message based on email status and updates made
        $updateDetails = [];
        if ($newStatus !== $appDetails['status']) {
            $updateDetails[] = "status updated to '$newStatus'";
        }
        if ($newPaymentStatus !== null && $newPaymentStatus !== $appDetails['payment_status']) {
            $updateDetails[] = "payment status updated to '$newPaymentStatus'";
        }

        $updateSummary = !empty($updateDetails) ? implode(', ', $updateDetails) : 'application updated';

        if ($emailSent) {
            $message = "✅ Application $updateSummary successfully! Email notification sent to {$appDetails['user_email']}.";
        } else if (!empty($appDetails['user_email'])) {
            $message = "⚠️ Application $updateSummary successfully, but email notification failed to send. " .
                      (!empty($emailError) ? "Error: $emailError" : "Please check email configuration.");
        } else {
            $message = "✅ Application $updateSummary successfully! (No email address available for notification)";
        }
        $messageType = "success";
    } else {
        $message = "❌ Error updating application status: " . $con->error;
        $messageType = "error";
    }
}

// Handle filters and search
$whereConditions = [];
$params = [];
$types = "";

// Status filter
if (!empty($_GET['status_filter']) && $_GET['status_filter'] !== 'all') {
    $whereConditions[] = "ba.status = ?";
    $params[] = $_GET['status_filter'];
    $types .= "s";
}

// Payment status filter
if (!empty($_GET['payment_filter']) && $_GET['payment_filter'] !== 'all') {
    $whereConditions[] = "ba.payment_status = ?";
    $params[] = $_GET['payment_filter'];
    $types .= "s";
}

// Pass type filter
if (!empty($_GET['pass_type_filter']) && $_GET['pass_type_filter'] !== 'all') {
    $whereConditions[] = "bpt.type_name = ?";
    $params[] = $_GET['pass_type_filter'];
    $types .= "s";
}

// Date range filter
if (!empty($_GET['date_from'])) {
    $whereConditions[] = "DATE(ba.application_date) >= ?";
    $params[] = $_GET['date_from'];
    $types .= "s";
}

if (!empty($_GET['date_to'])) {
    $whereConditions[] = "DATE(ba.application_date) <= ?";
    $params[] = $_GET['date_to'];
    $types .= "s";
}

// Search filter
if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $whereConditions[] = "(ba.applicant_name LIKE ? OR ba.id LIKE ? OR u.full_name LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Build the query
$query = "SELECT ba.*, u.full_name as user_name, u.email as user_email,
                 p.transaction_id, p.payment_method, p.payment_date,
                 bpt.type_name, bpt.duration_days,
                 c.category_name as transport_category
          FROM bus_pass_applications ba
          JOIN users u ON ba.user_id = u.id
          JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          LEFT JOIN payments p ON ba.id = p.application_id
          LEFT JOIN categories c ON ba.category_id = c.id";

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

$query .= " ORDER BY ba.application_date DESC";

// Execute query with parameters
if (!empty($params)) {
    $stmt = $con->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $applications = $stmt->get_result();
} else {
    $applications = $con->query($query);
}

// Get pass types for filter dropdown
$passTypesQuery = "SELECT DISTINCT type_name FROM bus_pass_types ORDER BY type_name";
$passTypesResult = $con->query($passTypesQuery);

// Get statistics
$statsQuery = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM bus_pass_applications";
$statsResult = $con->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Check instant_reviews table status
$instantReviewsStatus = [
    'exists' => false,
    'has_correct_structure' => false,
    'review_count' => 0,
    'avg_rating' => 0
];

try {
    $tableCheck = $con->query("SHOW TABLES LIKE 'instant_reviews'");
    $instantReviewsStatus['exists'] = ($tableCheck && $tableCheck->num_rows > 0);

    if ($instantReviewsStatus['exists']) {
        // Verify table structure
        $fieldsCheck = $con->query("DESCRIBE instant_reviews");
        $fields = [];
        while ($field = $fieldsCheck->fetch_assoc()) {
            $fields[] = $field['Field'];
        }
        $requiredFields = ['id', 'user_id', 'review_text', 'rating', 'created_at', 'status'];
        $instantReviewsStatus['has_correct_structure'] = count(array_intersect($requiredFields, $fields)) === count($requiredFields);

        if ($instantReviewsStatus['has_correct_structure']) {
            // Get review statistics
            $reviewStatsQuery = "SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM instant_reviews WHERE status = 'active'";
            $reviewStatsResult = $con->query($reviewStatsQuery);
            if ($reviewStatsResult) {
                $reviewStats = $reviewStatsResult->fetch_assoc();
                $instantReviewsStatus['review_count'] = $reviewStats['count'];
                $instantReviewsStatus['avg_rating'] = round($reviewStats['avg_rating'], 1);
            }
        }
    }
} catch (Exception $e) {
    $instantReviewsStatus['exists'] = false;
}

// Get data for charts
// 1. Monthly applications trend (last 12 months)
$monthlyTrendQuery = "SELECT
    DATE_FORMAT(application_date, '%Y-%m') as month,
    COUNT(*) as count
    FROM bus_pass_applications
    WHERE application_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(application_date, '%Y-%m')
    ORDER BY month";
$monthlyTrendResult = $con->query($monthlyTrendQuery);

$monthlyData = [];
$monthlyLabels = [];
while ($row = $monthlyTrendResult->fetch_assoc()) {
    $monthlyLabels[] = date('M Y', strtotime($row['month'] . '-01'));
    $monthlyData[] = (int)$row['count'];
}

// 2. Pass type distribution
$passTypeQuery = "SELECT
    bpt.type_name,
    COUNT(ba.id) as count
    FROM bus_pass_applications ba
    JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
    GROUP BY bpt.type_name
    ORDER BY count DESC";
$passTypeResult = $con->query($passTypeQuery);

$passTypeLabels = [];
$passTypeData = [];
$passTypeColors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
$colorIndex = 0;
while ($row = $passTypeResult->fetch_assoc()) {
    $passTypeLabels[] = $row['type_name'];
    $passTypeData[] = (int)$row['count'];
}

// 3. Approval vs Rejection rate
$approvalData = [
    (int)$stats['approved'],
    (int)$stats['rejected'],
    (int)$stats['pending']
];
$approvalLabels = ['Approved', 'Rejected', 'Pending'];

// 4. Payment completion percentage
$paymentQuery = "SELECT
    payment_status,
    COUNT(*) as count
    FROM bus_pass_applications
    GROUP BY payment_status";
$paymentResult = $con->query($paymentQuery);

$paymentLabels = [];
$paymentData = [];
while ($row = $paymentResult->fetch_assoc()) {
    $paymentLabels[] = ucfirst($row['payment_status']);
    $paymentData[] = (int)$row['count'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Nrupatunga Digital Bus Pass System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Override admin styles for dashboard specific needs */
        body {
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #F8FAFC;
            color: #1F2937;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .header {
            background: #1E3A8A;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            display: inline-block;
            float: left;
        }

        .logout {
            float: right;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .logout a, .logout button {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.1);
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout a:hover, .logout button:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-1px);
            text-decoration: none;
        }

        .header::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Mobile responsiveness for header */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }
            .header h2 {
                font-size: 1.2rem;
                float: none;
                display: block;
                margin-bottom: 1rem;
            }
            .logout {
                float: none;
                justify-content: center;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .logout a, .logout button {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border: 1px solid #E5E7EB;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: #6B7280;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pending { color: #F59E0B; }
        .approved { color: #10B981; }
        .rejected { color: #EF4444; }
        .total { color: #1F2937; }
        .applications {
            background: #FFFFFF;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border: 1px solid #E5E7EB;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .applications h3 {
            margin: 0;
            padding: 1.5rem;
            background: #F8FAFC;
            border-bottom: 1px solid #E5E7EB;
            color: #1F2937;
            font-size: 1.2rem;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
            color: #1F2937;
        }

        th {
            background: #F8FAFC;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr:hover {
            background: #F8FAFC;
        }

        .status-pending {
            background: #FEF3C7;
            color: #92400E;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-approved {
            background: #D1FAE5;
            color: #065F46;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-rejected {
            background: #FECACA;
            color: #991B1B;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-status-paid {
            background: #DBEAFE;
            color: #1E40AF;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-status-pending {
            background: #FEF3C7;
            color: #92400E;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-status-failed {
            background: #FECACA;
            color: #991B1B;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .category-badge {
            background: #2563EB;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-form {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-form select, .action-form input[type="text"] {
            padding: 0.5rem;
            border: 2px solid #E5E7EB;
            border-radius: 6px;
            background: white;
            color: #1F2937;
            font-size: 0.875rem;
        }

        .action-form select:focus, .action-form input[type="text"]:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .action-form button {
            padding: 0.5rem 1rem;
            background: #10B981;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .action-form button:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .action-form button.reject-btn {
            background: #EF4444;
        }

        .action-form button.reject-btn:hover {
            background: #DC2626;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            border: 1px solid transparent;
            font-weight: 500;
        }

        .message.success {
            background: #D1FAE5;
            border-color: #10B981;
            color: #065F46;
        }

        .message.error {
            background: #FECACA;
            border-color: #EF4444;
            color: #991B1B;
        }

        .photo-link {
            color: #2563EB;
            text-decoration: none;
            font-weight: 500;
        }

        .photo-link:hover {
            text-decoration: underline;
            color: #1D4ED8;
        }

        /* Filter Section Styles */
        .filters-section {
            background: #FFFFFF;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border: 1px solid #E5E7EB;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .filters-header {
            background: #F8FAFC;
            padding: 1.5rem;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .filters-header h4 {
            margin: 0;
            color: #1F2937;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .filters-toggle {
            background: #2563EB;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filters-toggle:hover {
            background: #1D4ED8;
            transform: translateY(-1px);
        }

        .filters-content {
            padding: 1.5rem;
            display: none;
        }

        .filters-content.active {
            display: block;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1F2937;
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.75rem;
            border: 2px solid #E5E7EB;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
            transition: all 0.3s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #2563EB;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-section {
            grid-column: 1 / -1;
            display: flex;
            gap: 1rem;
            align-items: end;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #2563EB;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #E5E7EB;
        }

        .btn-filter {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #2563EB;
            color: white;
        }

        .btn-primary:hover {
            background: #1D4ED8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6B7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4B5563;
            transform: translateY(-1px);
        }

        .results-info {
            background: #F8FAFC;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #6B7280;
            border: 1px solid #E5E7EB;
        }

        .results-info strong {
            color: #1F2937;
        }

        /* Mobile responsiveness for filters */
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .search-section {
                flex-direction: column;
                gap: 10px;
            }

            .filter-actions {
                flex-direction: column;
            }

            .filters-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

        /* Reports Modal Styles */
        .reports-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .reports-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reports-content {
            background: white;
            border-radius: 15px;
            padding: 0;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .reports-header {
            background: #1E3A8A;
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .reports-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .close-reports {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .close-reports:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }

        .reports-body {
            padding: 30px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 20px;
        }

        .chart-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .chart-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .chart-title i {
            color: #667eea;
            font-size: 1.3rem;
        }

        .chart-canvas {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .chart-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }

        .chart-stat {
            text-align: center;
        }

        .chart-stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .chart-stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .view-reports-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .view-reports-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Mobile responsiveness for reports */
        @media (max-width: 768px) {
            .reports-content {
                width: 98%;
                margin: 10px;
                max-height: 95vh;
            }

            .reports-header {
                padding: 15px 20px;
            }

            .reports-header h3 {
                font-size: 1.3rem;
            }

            .reports-body {
                padding: 20px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .chart-container {
                padding: 20px;
            }

            .chart-canvas {
                height: 250px;
            }

            .chart-stats {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Bulk Actions Styles */
        .bulk-actions-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
            border-left: 4px solid #007bff;
        }

        .bulk-actions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .bulk-actions-header h4 {
            margin: 0;
            color: #333;
            font-size: 1.1rem;
        }

        .selected-count {
            background: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .bulk-actions-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .bulk-select-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bulk-select-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .bulk-action-select {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
            min-width: 200px;
        }

        .bulk-action-select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .bulk-action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-bulk-primary {
            background: #007bff;
            color: white;
        }

        .btn-bulk-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .btn-bulk-primary:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .btn-bulk-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-bulk-secondary:hover {
            background: #545b62;
        }

        /* Table checkbox styles */
        .checkbox-cell {
            width: 40px;
            text-align: center;
            padding: 8px !important;
        }

        .row-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #007bff;
        }

        .select-all-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #007bff;
        }

        .selected-row {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }

        /* View & Update Button */
        .view-update-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .view-update-btn:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }

        .view-update-btn i {
            font-size: 0.9rem;
        }

        /* Direct Action Buttons */
        .action-buttons-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }

        .direct-actions {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .direct-action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
            color: white;
            min-width: 80px;
            justify-content: center;
        }

        .direct-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            text-decoration: none;
            color: white;
        }

        .approve-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .approve-btn:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
        }

        .reject-btn {
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
        }

        .reject-btn:hover {
            background: linear-gradient(135deg, #c82333 0%, #dc2626 100%);
        }

        .paid-btn {
            background: linear-gradient(135deg, #ffc107 0%, #f39c12 100%);
            color: #000 !important;
        }

        .paid-btn:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d68910 100%);
            color: #000 !important;
        }

        /* Bulk action confirmation */
        .bulk-confirmation {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            display: none;
        }

        .bulk-confirmation.show {
            display: block;
        }

        .bulk-confirmation strong {
            color: #721c24;
        }

        /* Mobile responsiveness for bulk actions */
        @media (max-width: 768px) {
            .bulk-actions-form {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .bulk-select-group {
                flex-direction: column;
                align-items: stretch;
                gap: 5px;
            }

            .bulk-action-select {
                min-width: auto;
                width: 100%;
            }

            .bulk-actions-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

        /* Application Details Modal Styles */
        .app-details-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }

        .app-details-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .app-details-content {
            background: white;
            border-radius: 15px;
            padding: 0;
            width: 95%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 70px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        .app-details-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .app-details-header h3 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .close-app-details {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .close-app-details:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }

        .app-details-body {
            padding: 30px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .details-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #28a745;
        }

        .details-section h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .details-section h4 i {
            color: #28a745;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .detail-value {
            color: #333;
            font-size: 0.9rem;
            text-align: right;
            max-width: 60%;
            word-wrap: break-word;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .payment-paid {
            background: #d4edda;
            color: #155724;
        }

        .payment-pending {
            background: #fff3cd;
            color: #856404;
        }

        .payment-failed {
            background: #f8d7da;
            color: #721c24;
        }

        /* ID Proof Section */
        .id-proof-section {
            grid-column: 1 / -1;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #007bff;
            text-align: center;
        }

        .id-proof-section h4 {
            color: #007bff;
            margin-bottom: 15px;
        }

        .id-proof-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
        }

        .id-proof-container:hover {
            border-color: #007bff;
        }

        .id-proof-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .id-proof-image:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .no-id-proof {
            color: #6c757d;
            font-style: italic;
            padding: 40px;
        }

        /* Action Controls */
        .action-controls {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
            border-left: 4px solid #dc3545;
        }

        .action-controls h4 {
            margin: 0 0 20px 0;
            color: #dc3545;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-group select,
        .form-group textarea {
            padding: 10px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .action-buttons {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }

        .action-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-update-primary {
            background: #007bff;
            color: white;
        }

        .btn-update-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .btn-update-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-update-secondary:hover {
            background: #545b62;
        }

        /* Mobile responsiveness for modal */
        @media (max-width: 768px) {
            .app-details-content {
                width: 98%;
                margin: 10px;
                max-height: 95vh;
            }

            .app-details-header {
                padding: 15px 20px;
            }

            .app-details-header h3 {
                font-size: 1.2rem;
            }

            .app-details-body {
                padding: 20px;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .action-form {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .id-proof-image {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Admin Dashboard</h2>
        <div class="logout">
            <button onclick="openReportsModal()" type="button">
                <i class="fas fa-chart-bar"></i> Reports
            </button>
            <a href="admin-activity-log.php">
                <i class="fas fa-history"></i> Activity Log
            </a>
            <a href="index.php">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="manage-categories.php">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="manage-routes.php">
                <i class="fas fa-route"></i> Routes
            </a>
            <a href="manage-announcements.php">
                <i class="fas fa-bullhorn"></i> Announcements
            </a>
            <a href="manage-support.php">
                <i class="fas fa-headset"></i> Support
            </a>
            <a href="manage-reviews.php">
                <i class="fas fa-star"></i> Reviews
            </a>
            <a href="admin-logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="stat-card" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div class="stat-number total" style="font-size: 2em; font-weight: bold; color: #1E3A8A;"><?php echo $stats['total']; ?></div>
                <div class="stat-label" style="color: #666; margin-top: 5px;">Total Applications</div>
            </div>
            <div class="stat-card" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div class="stat-number pending" style="font-size: 2em; font-weight: bold; color: #FFA500;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label" style="color: #666; margin-top: 5px;">Pending</div>
            </div>
            <div class="stat-card" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div class="stat-number approved" style="font-size: 2em; font-weight: bold; color: #28a745;"><?php echo $stats['approved']; ?></div>
                <div class="stat-label" style="color: #666; margin-top: 5px;">Approved</div>
            </div>
            <div class="stat-card" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div class="stat-number rejected" style="font-size: 2em; font-weight: bold; color: #dc3545;"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label" style="color: #666; margin-top: 5px;">Rejected</div>
            </div>
        </div>

        <!-- Instant Reviews Status Card -->
        <div class="stats" style="margin-bottom: 20px;">
            <div class="stat-card" style="background: <?php echo $instantReviewsStatus['exists'] && $instantReviewsStatus['has_correct_structure'] ? 'linear-gradient(135deg, #d4edda, #c3e6cb)' : 'linear-gradient(135deg, #fff3cd, #ffeaa7)'; ?>; border-left: 4px solid <?php echo $instantReviewsStatus['exists'] && $instantReviewsStatus['has_correct_structure'] ? '#28a745' : '#ffc107'; ?>;">
                <div class="stat-number" style="color: <?php echo $instantReviewsStatus['exists'] && $instantReviewsStatus['has_correct_structure'] ? '#28a745' : '#856404'; ?>; font-size: 1.5em;">
                    <i class="fas fa-<?php echo $instantReviewsStatus['exists'] && $instantReviewsStatus['has_correct_structure'] ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                </div>
                <div class="stat-label" style="color: <?php echo $instantReviewsStatus['exists'] && $instantReviewsStatus['has_correct_structure'] ? '#155724' : '#856404'; ?>; font-weight: 600;">
                    instant_reviews Table
                </div>
                <div style="font-size: 0.9rem; margin-top: 5px; color: <?php echo $instantReviewsStatus['exists'] && $instantReviewsStatus['has_correct_structure'] ? '#155724' : '#856404'; ?>;">
                    <?php if ($instantReviewsStatus['exists'] && $instantReviewsStatus['has_correct_structure']): ?>
                        ✅ Active with <?php echo $instantReviewsStatus['review_count']; ?> reviews
                        <?php if ($instantReviewsStatus['avg_rating'] > 0): ?>
                            <br>⭐ <?php echo $instantReviewsStatus['avg_rating']; ?>/5 avg rating
                        <?php endif; ?>
                    <?php elseif ($instantReviewsStatus['exists']): ?>
                        ⚠️ Table exists but incorrect structure
                    <?php else: ?>
                        ❌ Table not found - needs setup
                    <?php endif; ?>
                </div>
                <?php if (!$instantReviewsStatus['exists'] || !$instantReviewsStatus['has_correct_structure']): ?>
                <div style="margin-top: 10px;">
                    <a href="create_instant_reviews_sql.php" style="background: #007bff; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fas fa-plus-circle"></i> Create Table
                    </a>
                </div>
                <?php else: ?>
                <div style="margin-top: 10px;">
                    <a href="instant-reviews-display.php" style="background: #28a745; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fas fa-eye"></i> View Reviews
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-header">
                <h4><i class="fas fa-filter"></i> Filter & Search Applications</h4>
                <button type="button" class="filters-toggle" onclick="toggleFilters()">
                    <i class="fas fa-chevron-down" id="filter-icon"></i> Show Filters
                </button>
            </div>
            <div class="filters-content" id="filters-content">
                <form method="GET" action="" id="filter-form">
                    <div class="filters-grid">
                        <!-- Status Filter -->
                        <div class="filter-group">
                            <label for="status_filter">Application Status</label>
                            <select name="status_filter" id="status_filter">
                                <option value="all" <?php echo (!isset($_GET['status_filter']) || $_GET['status_filter'] == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="Pending" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>

                        <!-- Payment Status Filter -->
                        <div class="filter-group">
                            <label for="payment_filter">Payment Status</label>
                            <select name="payment_filter" id="payment_filter">
                                <option value="all" <?php echo (!isset($_GET['payment_filter']) || $_GET['payment_filter'] == 'all') ? 'selected' : ''; ?>>All Payment Status</option>
                                <option value="Paid" <?php echo (isset($_GET['payment_filter']) && $_GET['payment_filter'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="Pending" <?php echo (isset($_GET['payment_filter']) && $_GET['payment_filter'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Failed" <?php echo (isset($_GET['payment_filter']) && $_GET['payment_filter'] == 'Failed') ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>

                        <!-- Pass Type Filter -->
                        <div class="filter-group">
                            <label for="pass_type_filter">Pass Type</label>
                            <select name="pass_type_filter" id="pass_type_filter">
                                <option value="all" <?php echo (!isset($_GET['pass_type_filter']) || $_GET['pass_type_filter'] == 'all') ? 'selected' : ''; ?>>All Pass Types</option>
                                <?php if ($passTypesResult && $passTypesResult->num_rows > 0): ?>
                                    <?php while ($passType = $passTypesResult->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($passType['type_name']); ?>"
                                                <?php echo (isset($_GET['pass_type_filter']) && $_GET['pass_type_filter'] == $passType['type_name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($passType['type_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div class="filter-group">
                            <label for="date_from">Date From</label>
                            <input type="date" name="date_from" id="date_from"
                                   value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                        </div>

                        <!-- Date To -->
                        <div class="filter-group">
                            <label for="date_to">Date To</label>
                            <input type="date" name="date_to" id="date_to"
                                   value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                        </div>

                        <!-- Search Section -->
                        <div class="search-section">
                            <div class="filter-group" style="flex: 1;">
                                <label for="search">Search</label>
                                <input type="text" name="search" id="search" class="search-input"
                                       placeholder="Search by Applicant Name, Application ID, or User Name..."
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="button" class="btn-filter btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear All
                        </button>
                        <button type="submit" class="btn-filter btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Info -->
        <?php
        $totalResults = $applications->num_rows;
        $hasFilters = !empty($_GET['status_filter']) || !empty($_GET['payment_filter']) ||
                     !empty($_GET['pass_type_filter']) || !empty($_GET['date_from']) ||
                     !empty($_GET['date_to']) || !empty($_GET['search']);
        ?>
        <?php if ($hasFilters): ?>
            <div class="results-info">
                <i class="fas fa-info-circle"></i>
                Showing <strong><?php echo $totalResults; ?></strong> result(s) based on your filters.
                <a href="admin-dashboard.php" style="color: #007bff; text-decoration: none;">
                    <i class="fas fa-times"></i> Clear all filters
                </a>
            </div>
        <?php endif; ?>

        <!-- Bulk Actions Section -->
        <div class="bulk-actions-container" id="bulkActionsContainer" style="display: none;">
            <div class="bulk-actions-header">
                <h4><i class="fas fa-tasks"></i> Bulk Actions</h4>
                <span class="selected-count" id="selectedCount">0 selected</span>
            </div>
            <form method="POST" id="bulkActionsForm" onsubmit="return confirmBulkAction()">
                <div class="bulk-actions-form">
                    <div class="bulk-select-group">
                        <label for="bulk_action">Action:</label>
                        <select name="bulk_action" id="bulk_action" class="bulk-action-select" required>
                            <option value="">Select Action</option>
                            <option value="approve">
                                <i class="fas fa-check"></i> Approve Selected
                            </option>
                            <option value="reject">
                                <i class="fas fa-times"></i> Reject Selected
                            </option>
                            <option value="payment_required">
                                <i class="fas fa-credit-card"></i> Mark as Payment Required
                            </option>
                            <option value="mark_paid">
                                <i class="fas fa-check-circle"></i> Mark as Paid
                            </option>
                            <option value="delete">
                                <i class="fas fa-trash"></i> Delete Selected
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="bulk-action-btn btn-bulk-primary" id="bulkActionBtn" disabled>
                        <i class="fas fa-play"></i> Apply Action
                    </button>

                    <button type="button" class="bulk-action-btn btn-bulk-secondary" onclick="clearSelection()">
                        <i class="fas fa-times"></i> Clear Selection
                    </button>
                </div>

                <!-- Hidden inputs for selected applications -->
                <div id="selectedApplicationsInputs"></div>

                <!-- Confirmation message -->
                <div class="bulk-confirmation" id="bulkConfirmation">
                    <strong>Warning:</strong> <span id="confirmationMessage"></span>
                </div>
            </form>
        </div>

        <div class="applications">
            <h3>Bus Pass Applications</h3>
            <?php if ($applications->num_rows > 0): ?>
                <table>
                    <tr>
                        <th class="checkbox-cell">
                            <input type="checkbox" class="select-all-checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>ID</th>
                        <th>User</th>
                        <th>Applicant</th>
                        <th>Category</th>
                        <th>Pass Type</th>
                        <th>Route</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Applied Date</th>
                        <th>Action</th>
                    </tr>
                    <?php while ($app = $applications->fetch_assoc()): ?>
                    <tr class="application-row" data-id="<?php echo $app['id']; ?>">
                        <td class="checkbox-cell">
                            <input type="checkbox" class="row-checkbox" value="<?php echo $app['id']; ?>" onchange="updateSelection()">
                        </td>
                        <td><?php echo htmlspecialchars($app['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php echo htmlspecialchars($app['user_name'], ENT_QUOTES, 'UTF-8'); ?><br>
                            <small><?php echo htmlspecialchars($app['user_email'], ENT_QUOTES, 'UTF-8'); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($app['applicant_name'], ENT_QUOTES, 'UTF-8'); ?><br>
                            <small>DOB: <?php echo htmlspecialchars($app['date_of_birth'], ENT_QUOTES, 'UTF-8'); ?></small><br>
                            <small><?php echo htmlspecialchars($app['gender'], ENT_QUOTES, 'UTF-8'); ?></small>
                        </td>
                        <td>
                            <span class="category-badge">
                                <?php echo htmlspecialchars($app['transport_category'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($app['type_name'], ENT_QUOTES, 'UTF-8'); ?><br>
                            <small><?php echo formatCurrency($app['amount']); ?></small>
                            <?php if ($app['pass_number']): ?>
                                <br><small>Pass: <?php echo htmlspecialchars($app['pass_number'], ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($app['source'] . ' → ' . $app['destination'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="payment-status-<?php echo strtolower($app['payment_status']); ?>">
                                <?php echo htmlspecialchars($app['payment_status'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <?php if ($app['transaction_id']): ?>
                                <br><small>ID: <?php echo htmlspecialchars($app['transaction_id'], ENT_QUOTES, 'UTF-8'); ?></small>
                                <br><small><?php echo ucfirst($app['payment_method']); ?></small>
                                <?php if ($app['payment_date']): ?>
                                    <br><small><?php echo date('M d, Y', strtotime($app['payment_date'])); ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="status-<?php echo strtolower($app['status']); ?>">
                            <?php echo htmlspecialchars($app['status'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($app['admin_remarks']): ?>
                                <br><small><?php echo htmlspecialchars($app['admin_remarks'], ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php endif; ?>
                            <?php if ($app['valid_from'] && $app['valid_until']): ?>
                                <br><small>Valid: <?php echo date('M d', strtotime($app['valid_from'])); ?> - <?php echo date('M d, Y', strtotime($app['valid_until'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($app['application_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <div class="action-buttons-container">
                                <!-- Direct Action Buttons -->
                                <div class="direct-actions">
                                    <?php if ($app['status'] !== 'Approved'): ?>
                                    <a href="?direct_action=approve&app_id=<?php echo $app['id']; ?>"
                                       class="direct-action-btn approve-btn"
                                       onclick="return confirm('✅ APPROVE Application #<?php echo $app['id']; ?> for <?php echo htmlspecialchars($app['applicant_name']); ?>?')"
                                       title="Approve Application">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <?php endif; ?>

                                    <?php if ($app['status'] !== 'Rejected'): ?>
                                    <a href="?direct_action=reject&app_id=<?php echo $app['id']; ?>"
                                       class="direct-action-btn reject-btn"
                                       onclick="return confirm('❌ REJECT Application #<?php echo $app['id']; ?> for <?php echo htmlspecialchars($app['applicant_name']); ?>?')"
                                       title="Reject Application">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                    <?php endif; ?>

                                    <?php if ($app['payment_status'] !== 'Paid'): ?>
                                    <a href="?direct_action=mark_paid&app_id=<?php echo $app['id']; ?>"
                                       class="direct-action-btn paid-btn"
                                       onclick="return confirm('💳 Mark Application #<?php echo $app['id']; ?> as PAID?')"
                                       title="Mark as Paid">
                                        <i class="fas fa-credit-card"></i> Mark Paid
                                    </a>
                                    <?php endif; ?>
                                </div>

                                <!-- View & Update Button -->
                                <button onclick="openApplicationDetails(<?php echo $app['id']; ?>)"
                                        class="view-update-btn"
                                        title="View full details and update application">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p style="padding: 20px; text-align: center; color: #666;">No applications found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reports Modal -->
    <div id="reportsModal" class="reports-modal">
        <div class="reports-content">
            <div class="reports-header">
                <h3><i class="fas fa-chart-line"></i> Analytics & Reports Dashboard</h3>
                <button class="close-reports" onclick="closeReportsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="reports-body">
                <div class="charts-grid">
                    <!-- Monthly Applications Trend -->
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fas fa-chart-line"></i>
                            Monthly Applications Trend
                        </div>
                        <div class="chart-canvas">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                        <div class="chart-stats">
                            <div class="chart-stat">
                                <div class="chart-stat-number"><?php echo array_sum($monthlyData); ?></div>
                                <div class="chart-stat-label">Total (12 months)</div>
                            </div>
                            <div class="chart-stat">
                                <div class="chart-stat-number"><?php echo !empty($monthlyData) ? round(array_sum($monthlyData) / count($monthlyData)) : 0; ?></div>
                                <div class="chart-stat-label">Monthly Average</div>
                            </div>
                        </div>
                    </div>

                    <!-- Pass Type Distribution -->
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fas fa-chart-pie"></i>
                            Pass Type Distribution
                        </div>
                        <div class="chart-canvas">
                            <canvas id="passTypeChart"></canvas>
                        </div>
                        <div class="chart-stats">
                            <div class="chart-stat">
                                <div class="chart-stat-number"><?php echo count($passTypeLabels); ?></div>
                                <div class="chart-stat-label">Pass Types</div>
                            </div>
                            <div class="chart-stat">
                                <div class="chart-stat-number"><?php echo !empty($passTypeData) ? max($passTypeData) : 0; ?></div>
                                <div class="chart-stat-label">Most Popular</div>
                            </div>
                        </div>
                    </div>

                    <!-- Approval vs Rejection Rate -->
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fas fa-chart-donut"></i>
                            Application Status Distribution
                        </div>
                        <div class="chart-canvas">
                            <canvas id="approvalChart"></canvas>
                        </div>
                        <div class="chart-stats">
                            <div class="chart-stat">
                                <div class="chart-stat-number"><?php echo $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100) : 0; ?>%</div>
                                <div class="chart-stat-label">Approval Rate</div>
                            </div>
                            <div class="chart-stat">
                                <div class="chart-stat-number"><?php echo $stats['total'] > 0 ? round(($stats['rejected'] / $stats['total']) * 100) : 0; ?>%</div>
                                <div class="chart-stat-label">Rejection Rate</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Completion Percentage -->
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fas fa-credit-card"></i>
                            Payment Status Overview
                        </div>
                        <div class="chart-canvas">
                            <canvas id="paymentChart"></canvas>
                        </div>
                        <div class="chart-stats">
                            <?php
                            $totalPayments = array_sum($paymentData);
                            $paidIndex = array_search('Paid', $paymentLabels);
                            $paidCount = $paidIndex !== false ? $paymentData[$paidIndex] : 0;
                            $paymentRate = $totalPayments > 0 ? round(($paidCount / $totalPayments) * 100) : 0;
                            ?>
                            <div class="chart-stat">
                                <div class="chart-stat-number"><?php echo $paymentRate; ?>%</div>
                                <div class="chart-stat-label">Payment Success</div>
                            </div>
                            <div class="chart-stat">
                                <div class="chart-stat-number"><?php echo $paidCount; ?></div>
                                <div class="chart-stat-label">Completed Payments</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Details Modal -->
    <div id="appDetailsModal" class="app-details-modal">
        <div class="app-details-content">
            <div class="app-details-header">
                <h3><i class="fas fa-file-alt"></i> Application Details & Actions</h3>
                <button class="close-app-details" onclick="closeApplicationDetails()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="app-details-body" id="appDetailsBody">
                <!-- Content will be loaded dynamically -->
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff;"></i>
                    <p style="margin-top: 15px; color: #666;">Loading application details...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-sidebar">
        <div class="admin-info">
            <i class="fas fa-user-shield"></i>
            <h3>Admin Panel</h3>
        </div>
        <nav class="admin-nav">
            <a href="admin-dashboard.php" class="nav-item active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="manage-applications.php" class="nav-item">
                <i class="fas fa-file-alt"></i> Applications
            </a>
            <?php include('includes/rewards-nav.php'); ?>
            <a href="manage-routes.php" class="nav-item">
                <i class="fas fa-route"></i> Routes
            </a>
            <a href="manage-announcements.php" class="nav-item">
                <i class="fas fa-bullhorn"></i> Announcements
            </a>
            <a href="manage-support.php" class="nav-item">
                <i class="fas fa-headset"></i> Support
            </a>
            <a href="admin-logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <script>
        // Fix navigation button issues
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure all navigation links work properly
            const navLinks = document.querySelectorAll('.logout a');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Prevent any potential freezing
                    if (this.href && this.href !== '#') {
                        // Allow normal navigation
                        return true;
                    }
                    e.preventDefault();
                });
            });

            // Ensure buttons work properly
            const navButtons = document.querySelectorAll('.logout button');
            navButtons.forEach(button => {
                button.style.pointerEvents = 'auto';
                button.style.cursor = 'pointer';
            });
        });

        // Toggle filters visibility
        function toggleFilters() {
            const filtersContent = document.getElementById('filters-content');
            const filterIcon = document.getElementById('filter-icon');
            const toggleButton = document.querySelector('.filters-toggle');

            if (filtersContent.classList.contains('active')) {
                filtersContent.classList.remove('active');
                filterIcon.className = 'fas fa-chevron-down';
                toggleButton.innerHTML = '<i class="fas fa-chevron-down" id="filter-icon"></i> Show Filters';
            } else {
                filtersContent.classList.add('active');
                filterIcon.className = 'fas fa-chevron-up';
                toggleButton.innerHTML = '<i class="fas fa-chevron-up" id="filter-icon"></i> Hide Filters';
            }
        }

        // Clear all filters
        function clearFilters() {
            // Reset all form fields
            document.getElementById('status_filter').value = 'all';
            document.getElementById('payment_filter').value = 'all';
            document.getElementById('pass_type_filter').value = 'all';
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            document.getElementById('search').value = '';

            // Submit the form to clear filters
            window.location.href = 'admin-dashboard.php';
        }

        // Auto-submit form when filters change (optional)
        function setupAutoFilter() {
            const filterElements = [
                'status_filter', 'payment_filter', 'pass_type_filter',
                'date_from', 'date_to'
            ];

            filterElements.forEach(function(elementId) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.addEventListener('change', function() {
                        // Optional: Auto-submit on filter change
                        // document.getElementById('filter-form').submit();
                    });
                }
            });
        }

        // Search functionality with Enter key
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('filter-form').submit();
            }
        });

        // Initialize auto-filter setup
        document.addEventListener('DOMContentLoaded', function() {
            setupAutoFilter();

            // Show filters if any filter is active
            const hasActiveFilters = <?php echo $hasFilters ? 'true' : 'false'; ?>;
            if (hasActiveFilters) {
                toggleFilters();
            }
        });

        // Add loading state to filter button
        document.getElementById('filter-form').addEventListener('submit', function() {
            const submitButton = document.querySelector('.btn-primary');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
            submitButton.disabled = true;

            // Re-enable after a short delay (in case of quick response)
            setTimeout(function() {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }, 2000);
        });

        // Highlight active filters
        function highlightActiveFilters() {
            const filterInputs = document.querySelectorAll('#filters-content select, #filters-content input');
            filterInputs.forEach(function(input) {
                if (input.value && input.value !== 'all' && input.value !== '') {
                    input.style.borderColor = '#007bff';
                    input.style.backgroundColor = '#f8f9ff';
                }
            });
        }

        // Call highlight function on page load
        document.addEventListener('DOMContentLoaded', highlightActiveFilters);

        // Reports Modal Functions
        function openReportsModal() {
            document.getElementById('reportsModal').classList.add('active');
            document.body.style.overflow = 'hidden';

            // Initialize charts after modal is opened
            setTimeout(initializeCharts, 100);
        }

        function closeReportsModal() {
            document.getElementById('reportsModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('reportsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReportsModal();
            }
        });

        // Initialize all charts
        function initializeCharts() {
            initMonthlyTrendChart();
            initPassTypeChart();
            initApprovalChart();
            initPaymentChart();
        }

        // Monthly Applications Trend Chart
        function initMonthlyTrendChart() {
            const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($monthlyLabels); ?>,
                    datasets: [{
                        label: 'Applications',
                        data: <?php echo json_encode($monthlyData); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        }
                    }
                }
            });
        }

        // Pass Type Distribution Chart
        function initPassTypeChart() {
            const ctx = document.getElementById('passTypeChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($passTypeLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($passTypeData); ?>,
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Approval vs Rejection Chart
        function initApprovalChart() {
            const ctx = document.getElementById('approvalChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($approvalLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($approvalData); ?>,
                        backgroundColor: [
                            '#28a745',
                            '#dc3545',
                            '#ffc107'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Payment Status Chart
        function initPaymentChart() {
            const ctx = document.getElementById('paymentChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($paymentLabels); ?>,
                    datasets: [{
                        label: 'Count',
                        data: <?php echo json_encode($paymentData); ?>,
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderColor: [
                            '#1e7e34',
                            '#e0a800',
                            '#c82333'
                        ],
                        borderWidth: 1,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeReportsModal();
            }
        });

        // Bulk Actions Functionality
        let selectedApplications = [];

        // Toggle select all checkbox
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');

            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
                const row = checkbox.closest('tr');
                if (selectAllCheckbox.checked) {
                    row.classList.add('selected-row');
                } else {
                    row.classList.remove('selected-row');
                }
            });

            updateSelection();
        }

        // Update selection when individual checkboxes change
        function updateSelection() {
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            const bulkActionsContainer = document.getElementById('bulkActionsContainer');
            const selectedCount = document.getElementById('selectedCount');
            const bulkActionBtn = document.getElementById('bulkActionBtn');
            const selectedApplicationsInputs = document.getElementById('selectedApplicationsInputs');

            selectedApplications = [];
            let checkedCount = 0;

            rowCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (checkbox.checked) {
                    selectedApplications.push(checkbox.value);
                    row.classList.add('selected-row');
                    checkedCount++;
                } else {
                    row.classList.remove('selected-row');
                }
            });

            // Update select all checkbox state
            if (checkedCount === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (checkedCount === rowCheckboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
            }

            // Show/hide bulk actions container
            if (selectedApplications.length > 0) {
                bulkActionsContainer.style.display = 'block';
                selectedCount.textContent = `${selectedApplications.length} selected`;
                bulkActionBtn.disabled = false;

                // Create hidden inputs for selected applications
                selectedApplicationsInputs.innerHTML = '';
                selectedApplications.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_applications[]';
                    input.value = id;
                    selectedApplicationsInputs.appendChild(input);
                });
            } else {
                bulkActionsContainer.style.display = 'none';
                bulkActionBtn.disabled = true;
                selectedApplicationsInputs.innerHTML = '';
            }
        }

        // Clear all selections
        function clearSelection() {
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');

            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('tr').classList.remove('selected-row');
            });

            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            updateSelection();
        }

        // Confirm bulk action before submission
        function confirmBulkAction() {
            const bulkAction = document.getElementById('bulk_action').value;
            const selectedCount = selectedApplications.length;
            const confirmationDiv = document.getElementById('bulkConfirmation');
            const confirmationMessage = document.getElementById('confirmationMessage');

            if (!bulkAction) {
                alert('Please select an action to perform.');
                return false;
            }

            if (selectedCount === 0) {
                alert('Please select at least one application.');
                return false;
            }

            let actionText = '';
            let warningClass = '';

            switch (bulkAction) {
                case 'approve':
                    actionText = `approve ${selectedCount} application(s)`;
                    break;
                case 'reject':
                    actionText = `reject ${selectedCount} application(s)`;
                    warningClass = 'danger';
                    break;
                case 'payment_required':
                    actionText = `mark ${selectedCount} application(s) as payment required`;
                    break;
                case 'delete':
                    actionText = `permanently delete ${selectedCount} application(s) and all related data`;
                    warningClass = 'danger';
                    break;
            }

            const confirmMessage = `Are you sure you want to ${actionText}? This action cannot be undone.`;

            if (bulkAction === 'delete') {
                const doubleConfirm = confirm(confirmMessage + '\n\nType "DELETE" to confirm this destructive action.');
                if (doubleConfirm) {
                    const userInput = prompt('Please type "DELETE" to confirm:');
                    return userInput === 'DELETE';
                }
                return false;
            } else {
                return confirm(confirmMessage);
            }
        }

        // Initialize bulk actions on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to existing checkboxes
            updateSelection();

            // Add change listener to bulk action select
            document.getElementById('bulk_action').addEventListener('change', function() {
                const bulkActionBtn = document.getElementById('bulkActionBtn');
                bulkActionBtn.disabled = !this.value || selectedApplications.length === 0;
            });
        });

        // Application Details Modal Functions
        function openApplicationDetails(applicationId) {
            console.log('Opening application details for ID:', applicationId);

            const modal = document.getElementById('appDetailsModal');
            const modalBody = document.getElementById('appDetailsBody');

            if (!modal) {
                console.error('Modal element not found');
                alert('Modal not found. Please refresh the page.');
                return;
            }

            if (!modalBody) {
                console.error('Modal body element not found');
                alert('Modal body not found. Please refresh the page.');
                return;
            }

            // Show modal with loading state
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Reset content to loading state
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff;"></i>
                    <p style="margin-top: 15px; color: #666;">Loading application details...</p>
                </div>
            `;

            // Fetch application details with better error handling
            fetch('get-application-details.php?id=' + applicationId)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        displayApplicationDetails(data.application);
                    } else {
                        modalBody.innerHTML = `
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                                <p style="margin-top: 15px; color: #dc3545;">Error loading application details: ${data.message}</p>
                                <button onclick="closeApplicationDetails()" style="margin-top: 15px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Close</button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                            <p style="margin-top: 15px; color: #dc3545;">Error loading application details: ${error.message}</p>
                            <p style="margin-top: 10px; color: #666;">Please check your internet connection and try again.</p>
                            <button onclick="closeApplicationDetails()" style="margin-top: 15px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Close</button>
                        </div>
                    `;
                });
        }

        function closeApplicationDetails() {
            const modal = document.getElementById('appDetailsModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function displayApplicationDetails(app) {
            const modalBody = document.getElementById('appDetailsBody');

            const statusBadgeClass = `status-${app.status.toLowerCase()}`;
            const paymentBadgeClass = `payment-${app.payment_status.toLowerCase()}`;

            modalBody.innerHTML = `
                <div class="details-grid">
                    <!-- Personal Information -->
                    <div class="details-section">
                        <h4><i class="fas fa-user"></i> Personal Information</h4>
                        <div class="detail-item">
                            <span class="detail-label">Full Name:</span>
                            <span class="detail-value">${app.applicant_name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date of Birth:</span>
                            <span class="detail-value">${app.date_of_birth}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Gender:</span>
                            <span class="detail-value">${app.gender}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Contact:</span>
                            <span class="detail-value">${app.contact_number}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value">${app.user_email}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Address:</span>
                            <span class="detail-value">${app.address}</span>
                        </div>
                    </div>

                    <!-- Application Information -->
                    <div class="details-section">
                        <h4><i class="fas fa-bus"></i> Application Information</h4>
                        <div class="detail-item">
                            <span class="detail-label">Application ID:</span>
                            <span class="detail-value">#${app.id}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Pass Type:</span>
                            <span class="detail-value">${app.type_name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Route:</span>
                            <span class="detail-value">${app.source} → ${app.destination}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Amount:</span>
                            <span class="detail-value">₹${app.amount}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Applied Date:</span>
                            <span class="detail-value">${new Date(app.application_date).toLocaleDateString()}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <span class="status-badge ${statusBadgeClass}">${app.status}</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Status:</span>
                            <span class="detail-value">
                                <span class="payment-badge ${paymentBadgeClass}">${app.payment_status}</span>
                            </span>
                        </div>
                        ${app.pass_number ? `
                        <div class="detail-item">
                            <span class="detail-label">Pass Number:</span>
                            <span class="detail-value">${app.pass_number}</span>
                        </div>
                        ` : ''}
                        ${app.valid_from && app.valid_until ? `
                        <div class="detail-item">
                            <span class="detail-label">Validity:</span>
                            <span class="detail-value">${new Date(app.valid_from).toLocaleDateString()} - ${new Date(app.valid_until).toLocaleDateString()}</span>
                        </div>
                        ` : ''}
                    </div>

                    <!-- ID Proof Section -->
                    <div class="id-proof-section">
                        <h4><i class="fas fa-id-card"></i> ID Proof Document</h4>
                        <div class="id-proof-container">
                            ${app.id_proof_path ? `
                                <img src="${app.id_proof_path}" alt="ID Proof" class="id-proof-image" onclick="window.open('${app.id_proof_path}', '_blank')">
                                <p style="margin-top: 10px; color: #666; font-size: 0.9rem;">Click image to view full size</p>
                            ` : `
                                <div class="no-id-proof">
                                    <i class="fas fa-file-image" style="font-size: 3rem; color: #dee2e6; margin-bottom: 15px;"></i>
                                    <p>No ID proof uploaded</p>
                                </div>
                            `}
                        </div>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="action-controls">
                    <h4><i class="fas fa-cogs"></i> Update Application</h4>
                    <form method="POST" class="action-form" onsubmit="return updateApplicationStatus(event)">
                        <input type="hidden" name="application_id" value="${app.id}">
                        <input type="hidden" name="update_status" value="1">

                        <div class="form-group">
                            <label for="modal_status">Application Status:</label>
                            <select name="status" id="modal_status" required>
                                <option value="Pending" ${app.status === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Approved" ${app.status === 'Approved' ? 'selected' : ''}>Approved</option>
                                <option value="Rejected" ${app.status === 'Rejected' ? 'selected' : ''}>Rejected</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="modal_payment_status">Payment Status:</label>
                            <select name="payment_status" id="modal_payment_status">
                                <option value="">Keep Current (${app.payment_status})</option>
                                <option value="Pending" ${app.payment_status === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Paid" ${app.payment_status === 'Paid' ? 'selected' : ''}>Paid</option>
                                <option value="Failed" ${app.payment_status === 'Failed' ? 'selected' : ''}>Failed</option>
                                <option value="Refunded" ${app.payment_status === 'Refunded' ? 'selected' : ''}>Refunded</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="modal_remarks">Admin Remarks:</label>
                            <textarea name="remarks" id="modal_remarks" placeholder="Enter remarks or reason for decision...">${app.admin_remarks || ''}</textarea>
                        </div>

                        <div class="action-buttons">
                            <button type="button" class="action-btn btn-update-secondary" onclick="closeApplicationDetails()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="action-btn btn-update-primary">
                                <i class="fas fa-save"></i> Update Application
                            </button>
                        </div>
                    </form>
                </div>
            `;
        }

        function updateApplicationStatus(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // Show loading state
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitButton.disabled = true;

            // Submit form data
            fetch('admin-dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Close modal and reload page to show updated data
                closeApplicationDetails();
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating application. Please try again.');

                // Restore button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            });

            return false;
        }

        // Close modal when clicking outside
        document.getElementById('appDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeApplicationDetails();
            }
        });

        // Keyboard shortcuts for modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeApplicationDetails();
            }
        });
    </script>
</body>
</html>
