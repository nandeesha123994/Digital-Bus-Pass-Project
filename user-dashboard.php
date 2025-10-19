<?php
session_start();
include('includes/dbconnection.php');
include('includes/config.php');

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// Get user information
$userQuery = "SELECT full_name, email FROM users WHERE id = ?";
$userStmt = $con->prepare($userQuery);
$userStmt->bind_param("i", $_SESSION['uid']);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

// Set default name if user not found
$userName = $user ? $user['full_name'] : 'User';

// Handle instant review submission
$reviewMessage = '';
$reviewMessageType = '';
$instantReviewsTableExists = false;

// Check if instant_reviews table exists with correct structure
try {
    $tableCheck = $con->query("SHOW TABLES LIKE 'instant_reviews'");
    $instantReviewsTableExists = ($tableCheck && $tableCheck->num_rows > 0);

    // Verify table has correct fields
    if ($instantReviewsTableExists) {
        $fieldsCheck = $con->query("DESCRIBE instant_reviews");
        $fields = [];
        while ($field = $fieldsCheck->fetch_assoc()) {
            $fields[] = $field['Field'];
        }
        $requiredFields = ['id', 'user_id', 'review_text', 'rating', 'created_at', 'status'];
        $instantReviewsTableExists = count(array_intersect($requiredFields, $fields)) === count($requiredFields);
    }
} catch (Exception $e) {
    $instantReviewsTableExists = false;
}

if (isset($_POST['submit_review'])) {
    if (!$instantReviewsTableExists) {
        $reviewMessage = "Instant reviews system is not yet set up. Please contact the administrator.";
        $reviewMessageType = "error";
    } else {
        $reviewText = trim($_POST['review_text']);
        $rating = intval($_POST['rating']);

        // Input validation
        if (empty($reviewText)) {
            $reviewMessage = "Please write a review before submitting.";
            $reviewMessageType = "error";
        } elseif (strlen($reviewText) < 10) {
            $reviewMessage = "Review must be at least 10 characters long.";
            $reviewMessageType = "error";
        } elseif (strlen($reviewText) > 1000) {
            $reviewMessage = "Review must be less than 1000 characters.";
            $reviewMessageType = "error";
        } elseif ($rating < 1 || $rating > 5) {
            $reviewMessage = "Please select a valid rating (1-5 stars).";
            $reviewMessageType = "error";
        } else {
            // Basic spam protection
            $spamWords = ['spam', 'fake', 'scam', 'hack', 'virus', 'malware'];
            $reviewLower = strtolower($reviewText);
            $isSpam = false;
            foreach ($spamWords as $word) {
                if (strpos($reviewLower, $word) !== false) {
                    $isSpam = true;
                    break;
                }
            }

            if ($isSpam) {
                $reviewMessage = "Review contains inappropriate content. Please revise your review.";
                $reviewMessageType = "error";
            } else {
                try {
                    // Check if user has submitted a review recently (prevent spam)
                    $checkRecent = $con->prepare("SELECT id FROM instant_reviews WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                    $checkRecent->bind_param("i", $_SESSION['uid']);
                    $checkRecent->execute();

                    if ($checkRecent->get_result()->num_rows > 0) {
                        $reviewMessage = "You can only submit one review per hour. Please try again later.";
                        $reviewMessageType = "info";
                    } else {
                        // Get user's IP and user agent for tracking
                        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
                        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

                        // Insert new instant review (immediately active and visible)
                        $insertReview = $con->prepare("INSERT INTO instant_reviews (user_id, review_text, rating, status) VALUES (?, ?, ?, 'active')");
                        $insertReview->bind_param("isi", $_SESSION['uid'], $reviewText, $rating);

                        if ($insertReview->execute()) {
                            $reviewMessage = "🎉 Thank you for your review! Your feedback is now live and visible to everyone. We appreciate your input!";
                            $reviewMessageType = "success";
                        } else {
                            $reviewMessage = "Error submitting review. Please try again.";
                            $reviewMessageType = "error";
                        }
                    }
                } catch (Exception $e) {
                    $reviewMessage = "Error submitting review. Please try again later.";
                    $reviewMessageType = "error";
                }
            }
        }
    }
}

// Get user's recent reviews for display
$userReviews = [];
$canSubmitReview = true;
if ($instantReviewsTableExists) {
    try {
        // Get user's recent reviews (last 5)
        $getUserReviews = $con->prepare("SELECT rating, review_text, created_at, status FROM instant_reviews WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $getUserReviews->bind_param("i", $_SESSION['uid']);
        $getUserReviews->execute();
        $userReviewsResult = $getUserReviews->get_result();
        while ($review = $userReviewsResult->fetch_assoc()) {
            $userReviews[] = $review;
        }

        // Check if user can submit a new review (not within last hour)
        $checkRecent = $con->prepare("SELECT id FROM instant_reviews WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $checkRecent->bind_param("i", $_SESSION['uid']);
        $checkRecent->execute();
        $canSubmitReview = $checkRecent->get_result()->num_rows == 0;

    } catch (Exception $e) {
        $userReviews = [];
        $canSubmitReview = true;
    }
}

// Get user's applications with payment info
$query = "SELECT ba.*, bpt.type_name, bpt.duration_days,
                 p.transaction_id, p.payment_method, p.payment_date, p.status as payment_record_status,
                 c.category_name as transport_category
          FROM bus_pass_applications ba
          JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          LEFT JOIN payments p ON ba.id = p.application_id AND p.status = 'completed'
          LEFT JOIN categories c ON ba.category_id = c.id
          WHERE ba.user_id = ?
          ORDER BY ba.application_date DESC";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $_SESSION['uid']);
$stmt->execute();
$applications = $stmt->get_result();

// Get renewal statistics
$renewalStats = [
    'total_renewals' => 0,
    'active_passes' => 0,
    'expired_passes' => 0
];

$statsQuery = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN admin_remarks LIKE '%renewed%' THEN 1 ELSE 0 END) as renewals,
                SUM(CASE WHEN valid_until >= CURDATE() AND status = 'Approved' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN valid_until < CURDATE() AND status = 'Approved' THEN 1 ELSE 0 END) as expired
               FROM bus_pass_applications
               WHERE user_id = ?";
$statsStmt = $con->prepare($statsQuery);
$statsStmt->bind_param("i", $_SESSION['uid']);
$statsStmt->execute();
$statsResult = $statsStmt->get_result()->fetch_assoc();

$renewalStats['total_renewals'] = $statsResult['renewals'] ?? 0;
$renewalStats['active_passes'] = $statsResult['active'] ?? 0;
$renewalStats['expired_passes'] = $statsResult['expired'] ?? 0;

// Handle success messages
$successMessage = '';
$renewalSuccess = false;
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'payment_success':
            $successMessage = 'Payment completed successfully! Your application is being processed.';
            break;
        case 'already_paid':
            $successMessage = 'This application has already been paid for.';
            break;
    }
}

// Check for PhonePe payment success
if (isset($_GET['payment_success']) && $_GET['payment_success'] == '1') {
    $txnId = isset($_GET['txn_id']) ? htmlspecialchars($_GET['txn_id']) : '';
    $passNumber = isset($_GET['pass_number']) ? htmlspecialchars($_GET['pass_number']) : '';

    $successMessage = "🎉 Payment Successful via PhonePe!<br>";
    $successMessage .= "Transaction ID: <strong>$txnId</strong><br>";
    if ($passNumber) {
        $successMessage .= "Pass Number: <strong>$passNumber</strong><br>";
    }
    $successMessage .= "Your bus pass has been approved and is ready for download.";
}

// Check for payment errors
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_payment_data':
            $successMessage = "❌ Invalid payment data received. Please try again.";
            break;
        case 'application_not_found':
            $successMessage = "❌ Application not found or access denied.";
            break;
        case 'payment_processing_failed':
            $successMessage = "❌ Payment processing failed. Please contact support.";
            break;
    }
}

// Handle renewal success
if (isset($_GET['renewal_success']) && $_GET['renewal_success'] == '1') {
    $renewalSuccess = true;
    $successMessage = 'Your pass has been renewed successfully! Your new pass is now active.';
}

// Notification system removed

// Display payment success/error messages
if (isset($_SESSION['payment_success']) && $_SESSION['payment_success']) {
    echo '<div class="alert alert-success">
            <i class="fas fa-check-circle"></i> ' . $_SESSION['payment_message'] . '
            <br>Your Pass Number: <strong>' . $_SESSION['ticket_number'] . '</strong>
          </div>';
    // Clear the session variables
    unset($_SESSION['payment_success']);
    unset($_SESSION['payment_message']);
    unset($_SESSION['ticket_number']);
}

if (isset($_SESSION['payment_error']) && $_SESSION['payment_error']) {
    echo '<div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> ' . $_SESSION['payment_message'] . '
          </div>';
    // Clear the session variables
    unset($_SESSION['payment_error']);
    unset($_SESSION['payment_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Nrupatunga Digital Bus Pass System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 50%, #f0f8ff 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px rgba(21, 101, 192, 0.3);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .header-text h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }

        .header-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Navigation Styles */
        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.9rem;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-links a i {
            font-size: 1rem;
        }

        /* Apply Now Button */
        .apply-btn {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%) !important;
            color: white !important;
            font-weight: 600;
            padding: 1rem 2rem !important;
            border-radius: 30px !important;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
            position: relative;
            overflow: hidden;
            border: none !important;
        }

        .apply-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .apply-btn:hover::before {
            left: 100%;
        }

        .apply-btn:hover {
            background: linear-gradient(135deg, #66bb6a 0%, #388e3c 100%) !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5) !important;
        }

        /* Success Alert */
        .alert {
            max-width: 1200px;
            margin: 2rem auto 0;
            padding: 1rem 2rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }

        .alert i {
            font-size: 1.2rem;
            color: #28a745;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            align-items: start;
        }

        /* Main Content */
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #1565c0 0%, #4caf50 100%);
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1565c0;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .section-title i {
            font-size: 1.5rem;
        }

        .help-support-btn {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(23, 162, 184, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .help-support-btn:hover {
            background: linear-gradient(135deg, #138496 0%, #0f6674 100%);
            transform: translateY(-50%) translateY(-2px);
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
        }

        .help-support-btn i {
            font-size: 1rem;
        }

        /* Pass Cards */
        .pass-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .pass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #1565c0 0%, #4caf50 100%);
        }

        .pass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .pass-active {
            border-color: #4caf50 !important;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%) !important;
        }

        .pass-active::before {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%) !important;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .card-info {
            flex: 1;
        }

        .card-status {
            text-align: right;
            min-width: 150px;
        }

        .application-id {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1565c0;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-row {
            display: flex;
            margin-bottom: 0.5rem;
            align-items: center;
            gap: 0.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 100px;
        }

        .info-value {
            color: #333;
        }

        .pass-number {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            color: #1565c0;
            border: 1px solid rgba(21, 101, 192, 0.2);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-approved {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-rejected {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .payment-status {
            margin-top: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .payment-status-paid {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .payment-status-pending, .payment-status-payment_required {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }

        .payment-status-failed {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(21, 101, 192, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(21, 101, 192, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #ffa726 0%, #ff9800 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #66bb6a 0%, #388e3c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        /* Renewal Button Styles */
        .btn-renew {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 0.5rem;
        }

        .btn-renew:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
            color: white;
        }

        .btn-renew i {
            font-size: 1rem;
            animation: rotate 2s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .btn-renew:hover i {
            animation-duration: 0.5s;
        }

        .renewal-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .renewal-badge.expired {
            background: rgba(220, 53, 69, 0.8);
            animation: pulse 1.5s infinite;
        }

        .renewal-badge.expiring {
            background: rgba(255, 193, 7, 0.8);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        /* Help & Support Modal Styles */
        .help-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .help-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .help-modal {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s ease;
        }

        .help-modal-overlay.show .help-modal {
            transform: scale(1) translateY(0);
        }

        .help-modal-header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 15px 15px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .help-modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .help-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .help-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .help-modal-body {
            padding: 2rem;
        }

        .help-tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 2rem;
            gap: 0.5rem;
        }

        .help-tab {
            background: none;
            border: none;
            padding: 1rem 1.5rem;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            border-radius: 8px 8px 0 0;
        }

        .help-tab.active {
            color: #17a2b8;
            border-bottom-color: #17a2b8;
            background: rgba(23, 162, 184, 0.1);
        }

        .help-tab:hover {
            color: #17a2b8;
            background: rgba(23, 162, 184, 0.05);
        }

        .help-content {
            display: none;
        }

        .help-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* FAQ Styles */
        .faq-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .faq-question {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 600;
            color: #495057;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: #e9ecef;
            color: #17a2b8;
        }

        .faq-question.active {
            background: #17a2b8;
            color: white;
        }

        .faq-answer {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }

        .faq-answer.active {
            padding: 1.5rem;
            max-height: 500px;
        }

        .faq-icon {
            transition: transform 0.3s ease;
        }

        .faq-question.active .faq-icon {
            transform: rotate(180deg);
        }

        /* Contact Info Styles */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .contact-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        .contact-card i {
            font-size: 2rem;
            color: #17a2b8;
            margin-bottom: 1rem;
        }

        .contact-card h4 {
            margin: 0 0 0.5rem 0;
            color: #495057;
        }

        .contact-card p {
            margin: 0;
            color: #6c757d;
        }

        /* Support Form Styles */
        .support-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #17a2b8;
            box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .help-modal {
                width: 95%;
                margin: 1rem;
            }

            .help-modal-header {
                padding: 1rem 1.5rem;
            }

            .help-modal-body {
                padding: 1.5rem;
            }

            .help-tabs {
                flex-wrap: wrap;
            }

            .help-tab {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }

            .help-support-btn {
                position: static;
                transform: none;
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
            }

            .section-title {
                flex-direction: column;
                text-align: center;
            }
        }







        /* Tooltip Styles */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 8px 12px;
            position: absolute;
            z-index: 1001;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.85rem;
            font-weight: 400;
            text-transform: none;
            letter-spacing: normal;
        }

        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .card-footer {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .date-info {
            font-size: 0.85rem;
            color: #666;
        }

        .admin-remarks {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 0.75rem;
            border-radius: 8px;
            border-left: 4px solid #1565c0;
            font-size: 0.9rem;
            color: #555;
            margin-top: 0.5rem;
        }

        /* Bus Image Section */
        .bus-image-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            position: sticky;
            top: 2rem;
        }

        .bus-image {
            width: 100%;
            max-width: 300px;
            height: auto;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }

        .bus-image:hover {
            transform: scale(1.05);
        }

        .bus-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);
            padding: 1.5rem;
            border-radius: 15px;
            margin-top: 1rem;
        }

        .bus-info h3 {
            color: #1565c0;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .bus-info p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .no-applications {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .no-applications i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .no-applications h3 {
            color: #1565c0;
            margin-bottom: 1rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 0 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-links a {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }

            .card-header {
                flex-direction: column;
                gap: 1rem;
            }

            .card-status {
                text-align: left;
                min-width: auto;
            }

            .card-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-bus"></i>
                </div>
                <div class="header-text">
                    <h1>Welcome, <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>!</h1>
                    <div class="header-subtitle">Nrupatunga Digital Bus Pass Dashboard</div>
                </div>
            </div>
            <div class="nav-links">
                <a href="apply-pass.php" class="apply-btn">
                    <i class="fas fa-plus"></i> Apply for Bus Pass
                </a>
                <a href="rewards-dashboard.php" class="rewards-btn">
                    <i class="fas fa-star"></i> My Rewards
                </a>
                <a href="index.php">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Success Alert -->
    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Main Container -->
    <div class="container">
        <!-- Notification system removed -->

        <!-- Main Content -->
        <div class="main-content">
            <!-- Rewards Navigation -->
            <?php include('includes/rewards-nav.php'); ?>
            
            <div class="section-title">
                <i class="fas fa-id-card"></i>
                Your Bus Pass Applications
                <button class="help-support-btn" onclick="openHelpModal()">
                    <i class="fas fa-question-circle"></i> Help & Support
                </button>
            </div>
            <?php if ($applications->num_rows > 0): ?>
                <?php while ($app = $applications->fetch_assoc()): ?>
                    <div class="pass-card <?php echo $app['status'] === 'Approved' ? 'pass-active' : ''; ?>">
                        <div class="card-header">
                            <div class="card-info">
                                <div class="application-id">
                                    <i class="fas fa-ticket-alt"></i>
                                    Application <?php echo isset($app['application_id']) && $app['application_id'] ? htmlspecialchars($app['application_id'], ENT_QUOTES, 'UTF-8') : '#' . htmlspecialchars($app['id'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>

                                <div class="info-row">
                                    <span class="info-label"><i class="fas fa-bus"></i> Pass Type:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($app['type_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label"><i class="fas fa-user"></i> Applicant:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($app['applicant_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label"><i class="fas fa-route"></i> Route:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($app['source'] . ' → ' . $app['destination'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label"><i class="fas fa-rupee-sign"></i> Amount:</span>
                                    <span class="info-value"><?php echo formatCurrency($app['amount']); ?></span>
                                </div>

                                <?php if ($app['pass_number']): ?>
                                    <div class="info-row">
                                        <span class="info-label"><i class="fas fa-id-badge"></i> Pass Number:</span>
                                        <span class="pass-number"><?php echo htmlspecialchars($app['pass_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($app['valid_from'] && $app['valid_until']): ?>
                                    <div class="info-row">
                                        <span class="info-label"><i class="fas fa-calendar-alt"></i> Valid Period:</span>
                                        <span class="info-value"><?php echo date('M d, Y', strtotime($app['valid_from'])); ?> to <?php echo date('M d, Y', strtotime($app['valid_until'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-status">
                                <div class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                    <i class="fas fa-<?php echo $app['status'] === 'Approved' ? 'check-circle' : ($app['status'] === 'Rejected' ? 'times-circle' : 'clock'); ?>"></i>
                                    <?php echo htmlspecialchars($app['status'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>

                                <div class="payment-status payment-status-<?php echo strtolower($app['payment_status']); ?>">
                                    <i class="fas fa-<?php echo $app['payment_status'] === 'Paid' ? 'check' : ($app['payment_status'] === 'Failed' ? 'times' : 'clock'); ?>"></i>
                                    <?php echo htmlspecialchars($app['payment_status'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>

                                <?php if ($app['payment_status'] === 'Payment_Required'): ?>
                                    <a href="payment.php?application_id=<?php echo $app['id']; ?>" class="btn btn-warning">
                                        <i class="fas fa-credit-card"></i> Complete Payment
                                    </a>
                                <?php elseif ($app['transaction_id']): ?>
                                    <div style="font-size: 0.8rem; color: #666; margin: 0.5rem 0; text-align: right;">
                                        <div><strong>Transaction ID:</strong></div>
                                        <div style="font-family: monospace; font-size: 0.75rem;"><?php echo htmlspecialchars($app['transaction_id'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div><strong>Method:</strong> <?php echo ucfirst($app['payment_method']); ?></div>
                                        <div><strong>Date:</strong> <?php echo date('M d, Y', strtotime($app['payment_date'])); ?></div>
                                    </div>
                                    <?php
                                    // Get payment ID for receipt
                                    $paymentQuery = "SELECT id FROM payments WHERE application_id = ? AND user_id = ?";
                                    $paymentStmt = $con->prepare($paymentQuery);
                                    $paymentStmt->bind_param("ii", $app['id'], $_SESSION['uid']);
                                    $paymentStmt->execute();
                                    $paymentResult = $paymentStmt->get_result()->fetch_assoc();
                                    if ($paymentResult):
                                    ?>
                                    <a href="payment_receipt.php?payment_id=<?php echo $paymentResult['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-receipt"></i> View Receipt
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Print Bus Pass Button for Approved Applications -->
                                <?php if ($app['status'] === 'Approved' && $app['pass_number']): ?>
                                    <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                                        <a href="generate-bus-pass.php?application_id=<?php echo $app['id']; ?>"
                                           class="btn btn-success" target="_blank" title="View and print your bus pass">
                                            <i class="fas fa-eye"></i> View Bus Pass
                                        </a>
                                        <a href="download-bus-pass-pdf.php?application_id=<?php echo $app['id']; ?>"
                                           class="btn btn-primary" target="_blank" title="Download PDF version">
                                            <i class="fas fa-download"></i> Download PDF
                                        </a>

                                        <?php
                                        // Check if pass is within 10 days of expiry or expired
                                        if (!empty($app['valid_until'])) {
                                            $currentDate = new DateTime();
                                            $expiryDate = new DateTime($app['valid_until']);
                                            $interval = $currentDate->diff($expiryDate);
                                            $daysUntilExpiry = $interval->days;
                                            $isExpired = $currentDate > $expiryDate;
                                            $isNearExpiry = !$isExpired && $daysUntilExpiry <= 10;

                                            // Show renewal status for all passes
                                            if ($isExpired || $isNearExpiry): ?>
                                                <!-- RENEWAL BUTTON START -->
                                                <div class="tooltip">
                                                    <button class="btn btn-renew"
                                                            style="display: flex !important; width: 100% !important; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; color: white !important; border: none !important; padding: 0.75rem 1.5rem !important; border-radius: 10px !important; cursor: pointer !important; align-items: center !important; justify-content: space-between !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; transition: all 0.3s ease !important; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3) !important;"
                                                            onclick="openRenewalModal(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars($app['applicant_name']); ?>', '<?php echo $app['source']; ?>', '<?php echo $app['destination']; ?>', <?php echo $app['pass_type_id']; ?>)">
                                                        <span><i class="fas fa-redo"></i> Renew Pass</span>
                                                        <?php if ($isExpired): ?>
                                                            <span class="renewal-badge expired" style="background: rgba(220, 53, 69, 0.8) !important; padding: 0.25rem 0.5rem !important; border-radius: 12px !important; font-size: 0.75rem !important; margin-left: 0.5rem !important; color: white !important;">Expired</span>
                                                        <?php else: ?>
                                                            <span class="renewal-badge expiring" style="background: rgba(255, 193, 7, 0.8) !important; padding: 0.25rem 0.5rem !important; border-radius: 12px !important; font-size: 0.75rem !important; margin-left: 0.5rem !important; color: #212529 !important;"><?php echo $daysUntilExpiry; ?> days left</span>
                                                        <?php endif; ?>
                                                    </button>
                                                    <span class="tooltiptext">Renew your pass for another month</span>
                                                </div>
                                                <!-- RENEWAL BUTTON END -->
                                            <?php else: ?>
                                                <!-- RENEWAL STATUS INFO -->
                                                <div style="background: rgba(108, 117, 125, 0.1); padding: 0.75rem; border-radius: 10px; text-align: center; margin-top: 0.5rem; border: 1px solid rgba(108, 117, 125, 0.2);">
                                                    <div style="color: #6c757d; font-size: 0.9rem; font-weight: 600;">
                                                        <i class="fas fa-info-circle"></i> Renewal Available in <?php echo max(0, $daysUntilExpiry - 10); ?> days
                                                    </div>
                                                    <div style="color: #6c757d; font-size: 0.8rem; margin-top: 0.25rem;">
                                                        (When pass has ≤10 days remaining)
                                                    </div>
                                                </div>
                                            <?php endif;
                                        } // End of valid_until check ?>
                                    </div>
                                <?php endif; ?>


                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="date-info">
                                <div><i class="fas fa-calendar-plus"></i> <strong>Applied:</strong> <?php echo date('M d, Y H:i', strtotime($app['application_date'])); ?></div>
                                <?php if ($app['processed_date']): ?>
                                    <div><i class="fas fa-calendar-check"></i> <strong>Processed:</strong> <?php echo date('M d, Y H:i', strtotime($app['processed_date'])); ?></div>
                                <?php endif; ?>
                            </div>

                            <?php if ($app['admin_remarks']): ?>
                                <div class="admin-remarks">
                                    <strong><i class="fas fa-comment-alt"></i> Admin Remarks:</strong><br>
                                    <?php echo htmlspecialchars($app['admin_remarks'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-applications">
                    <i class="fas fa-bus"></i>
                    <h3>No Bus Pass Applications Yet</h3>
                    <p>You haven't applied for any bus passes yet. Start your journey by applying for a bus pass today!</p>
                    <a href="apply-pass.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Apply for Bus Pass
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bus Image Section -->
        <div class="bus-image-section">
            <img src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                 alt="Modern Bus" class="bus-image">

            <div class="bus-info">
                <h3><i class="fas fa-bus"></i> Public Transportation</h3>
                <p>Experience convenient and eco-friendly travel with our modern bus pass system. Get access to all major routes across the city with a single pass.</p>

                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(21, 101, 192, 0.2);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span><i class="fas fa-route"></i> Routes:</span>
                        <strong>50+ Available</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span><i class="fas fa-clock"></i> Service:</span>
                        <strong>24/7 Support</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span><i class="fas fa-leaf"></i> Eco-Friendly:</span>
                        <strong>Green Travel</strong>
                    </div>
                </div>
            </div>

            <!-- Instant User Reviews Section -->
            <?php if ($instantReviewsTableExists): ?>
            <div style="margin-top: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; color: white;">
                <h4 style="margin: 0 0 1rem 0; text-align: center;">
                    <i class="fas fa-comments"></i> Share Your Experience - Instant Public Reviews
                </h4>
                <p style="margin: 0 0 1rem 0; text-align: center; opacity: 0.9; font-size: 0.9rem;">
                    Your review will be instantly visible to everyone! Help others with your feedback.
                </p>
            <?php else: ?>
            <div style="margin-top: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%); border-radius: 15px; color: white; text-align: center;">
                <h4 style="margin: 0 0 1rem 0;"><i class="fas fa-tools"></i> Instant Reviews System Setup Required</h4>
                <p style="margin: 0 0 1rem 0; opacity: 0.9;">The instant reviews system needs to be set up by an administrator.</p>
                <a href="create_instant_reviews_sql.php" style="background: rgba(255, 255, 255, 0.2); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-block; transition: all 0.3s ease;">
                    <i class="fas fa-database"></i> Create instant_reviews Table
                </a>
            </div>
            <?php endif; ?>

            <?php if ($instantReviewsTableExists): ?>

                <?php if ($reviewMessage): ?>
                <div style="background: <?php echo $reviewMessageType === 'success' ? 'rgba(40, 167, 69, 0.2)' : ($reviewMessageType === 'error' ? 'rgba(220, 53, 69, 0.2)' : 'rgba(23, 162, 184, 0.2)'); ?>; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(255,255,255,0.3);">
                    <i class="fas fa-<?php echo $reviewMessageType === 'success' ? 'check-circle' : ($reviewMessageType === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                    <?php echo $reviewMessage; ?>
                </div>
                <?php endif; ?>

                <!-- Display User's Previous Reviews -->
                <?php if (!empty($userReviews)): ?>
                <div style="background: rgba(255,255,255,0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.3); margin-bottom: 1rem;">
                    <h5 style="margin: 0 0 1rem 0;"><i class="fas fa-history"></i> Your Recent Reviews</h5>
                    <?php foreach ($userReviews as $review): ?>
                    <div style="background: rgba(255,255,255,0.05); padding: 0.75rem; border-radius: 6px; margin-bottom: 0.5rem; border-left: 3px solid #ffd700;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <div>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span style="color: <?php echo $i <= $review['rating'] ? '#ffd700' : 'rgba(255,255,255,0.3)'; ?>; font-size: 1.1rem;">⭐</span>
                                <?php endfor; ?>
                            </div>
                            <small style="opacity: 0.8;"><?php echo date('M d, Y H:i', strtotime($review['created_at'])); ?></small>
                        </div>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.9;"><?php echo htmlspecialchars($review['review_text']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Review Submission Form -->
                <?php if ($canSubmitReview): ?>
                <form method="POST" style="margin-top: 1rem;" id="reviewForm">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Rate Your Experience:</label>
                        <div class="star-rating" style="display: flex; gap: 5px; margin-bottom: 1rem; justify-content: center;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" style="display: none;" required>
                            <label for="star<?php echo $i; ?>" class="star" style="font-size: 2rem; color: rgba(255,255,255,0.3); cursor: pointer; transition: all 0.3s ease; text-shadow: 0 0 10px rgba(255,215,0,0.5);" data-rating="<?php echo $i; ?>">⭐</label>
                            <?php endfor; ?>
                        </div>
                        <div id="ratingText" style="text-align: center; font-size: 0.9rem; opacity: 0.8; margin-bottom: 1rem;"></div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Write Your Review:</label>
                        <textarea name="review_text" id="reviewText" placeholder="Share your experience with our bus pass management system... (minimum 10 characters)"
                                  style="width: 100%; padding: 0.75rem; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.1); color: white; resize: vertical; min-height: 120px; font-family: inherit;"
                                  required maxlength="1000"></textarea>
                        <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.8rem; opacity: 0.7;">
                            <span id="charCount">0/1000 characters</span>
                            <span>Minimum 10 characters required</span>
                        </div>
                    </div>

                    <button type="submit" name="submit_review" id="submitBtn" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; padding: 1rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; width: 100%; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);">
                        <i class="fas fa-rocket"></i> Submit Instant Review
                    </button>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8rem; opacity: 0.8; text-align: center;">
                        <i class="fas fa-info-circle"></i> Your review will be instantly visible to everyone!
                    </p>
                </form>
                <?php else: ?>
                <div style="background: rgba(255,193,7,0.2); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,193,7,0.5); text-align: center;">
                    <h5 style="margin: 0 0 0.5rem 0;"><i class="fas fa-clock"></i> Review Cooldown</h5>
                    <p style="margin: 0; opacity: 0.9;">You can submit another review in an hour. This helps prevent spam and ensures quality feedback.</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div style="margin-top: 1.5rem; text-align: center;">
                <a href="apply-pass.php" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-plus"></i> Apply for New Pass
                </a>
            </div>
        </div>


    </div>

    <style>
        /* Star Rating Styles */
        .star-rating {
            display: flex;
            gap: 0.25rem;
            align-items: center;
        }

        .star-rating .star {
            cursor: pointer;
            color: rgba(255, 255, 255, 0.3);
            transition: all 0.2s ease;
            font-size: 1.5rem;
        }

        .star-rating .star:hover,
        .star-rating .star.active {
            color: #ffc107;
            transform: scale(1.1);
        }

        .star-rating .star:hover ~ .star {
            color: rgba(255, 255, 255, 0.3);
        }

        /* Review form styles */
        textarea::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        button[name="submit_review"]:hover {
            background: rgba(255, 255, 255, 0.3) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
    </style>

    <script>
        // Enhanced Star Rating and Form Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star-rating .star');
            const ratingInputs = document.querySelectorAll('input[name="rating"]');
            const ratingText = document.getElementById('ratingText');
            const reviewText = document.getElementById('reviewText');
            const charCount = document.getElementById('charCount');
            const submitBtn = document.getElementById('submitBtn');

            const ratingTexts = {
                1: '⭐ Poor - Not satisfied',
                2: '⭐⭐ Fair - Below expectations',
                3: '⭐⭐⭐ Good - Meets expectations',
                4: '⭐⭐⭐⭐ Very Good - Exceeds expectations',
                5: '⭐⭐⭐⭐⭐ Excellent - Outstanding service!'
            };

            // Star rating functionality
            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));

                    // Set the radio button value
                    ratingInputs[index].checked = true;

                    // Update star display and text
                    updateStarDisplay(rating);
                    if (ratingText) {
                        ratingText.textContent = ratingTexts[rating];
                        ratingText.style.color = '#ffd700';
                    }
                });

                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    updateStarDisplay(rating);
                    if (ratingText) {
                        ratingText.textContent = ratingTexts[rating];
                        ratingText.style.color = '#ffd700';
                    }
                });
            });

            // Reset stars on mouse leave
            const starRating = document.querySelector('.star-rating');
            if (starRating) {
                starRating.addEventListener('mouseleave', function() {
                    const checkedRating = document.querySelector('input[name="rating"]:checked');
                    const rating = checkedRating ? parseInt(checkedRating.value) : 0;
                    updateStarDisplay(rating);
                    if (ratingText) {
                        if (rating > 0) {
                            ratingText.textContent = ratingTexts[rating];
                            ratingText.style.color = '#ffd700';
                        } else {
                            ratingText.textContent = 'Click stars to rate your experience';
                            ratingText.style.color = 'rgba(255,255,255,0.7)';
                        }
                    }
                });
            }

            function updateStarDisplay(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.style.color = '#ffd700';
                        star.style.transform = 'scale(1.1)';
                        star.style.textShadow = '0 0 15px rgba(255,215,0,0.8)';
                    } else {
                        star.style.color = 'rgba(255,255,255,0.3)';
                        star.style.transform = 'scale(1)';
                        star.style.textShadow = '0 0 10px rgba(255,215,0,0.3)';
                    }
                });
            }

            // Character count functionality
            if (reviewText && charCount) {
                reviewText.addEventListener('input', function() {
                    const length = this.value.length;
                    charCount.textContent = `${length}/1000 characters`;

                    if (length < 10) {
                        charCount.style.color = '#ff6b6b';
                    } else if (length > 900) {
                        charCount.style.color = '#ffa500';
                    } else {
                        charCount.style.color = '#28a745';
                    }

                    // Update submit button state
                    updateSubmitButton();
                });
            }

            // Form validation
            function updateSubmitButton() {
                if (submitBtn && reviewText) {
                    const hasRating = document.querySelector('input[name="rating"]:checked');
                    const hasValidText = reviewText.value.length >= 10;

                    if (hasRating && hasValidText) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.cursor = 'pointer';
                    } else {
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.6';
                        submitBtn.style.cursor = 'not-allowed';
                    }
                }
            }

            // Initial setup
            if (ratingText) {
                ratingText.textContent = 'Click stars to rate your experience';
                ratingText.style.color = 'rgba(255,255,255,0.7)';
            }

            updateSubmitButton();

            // Form submission with loading state
            const reviewForm = document.getElementById('reviewForm');
            if (reviewForm) {
                reviewForm.addEventListener('submit', function() {
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                        submitBtn.disabled = true;
                    }
                });
            }
        });

        // Add smooth animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.pass-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Show renewal success toast if applicable
            <?php if ($renewalSuccess): ?>
            setTimeout(() => {
                showRenewalToast('Your pass has been renewed successfully!');
            }, 1000);
            <?php endif; ?>

            // Add hover effects for better interactivity
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.02)';
                });

                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });

        // Renewal Modal Functions
        function openRenewalModal(applicationId, applicantName, source, destination, passTypeId) {
            // Create modal HTML
            const modalHTML = `
                <div id="renewalModal" class="renewal-modal-overlay">
                    <div class="renewal-modal">
                        <div class="renewal-modal-header">
                            <h3><i class="fas fa-redo"></i> Renew Bus Pass</h3>
                            <button class="close-modal" onclick="closeRenewalModal()">&times;</button>
                        </div>
                        <div class="renewal-modal-body">
                            <div class="renewal-info">
                                <h4>Pass Renewal Details</h4>
                                <div class="renewal-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Name:</span>
                                        <span class="detail-value">${applicantName}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Route:</span>
                                        <span class="detail-value">${source} → ${destination}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Renewal Period:</span>
                                        <span class="detail-value">30 Days</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Amount:</span>
                                        <span class="detail-value amount">₹100.00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="renewal-actions">
                                <button class="btn-cancel" onclick="closeRenewalModal()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button class="btn-proceed" onclick="proceedToRenewal(${applicationId})">
                                    <i class="fas fa-credit-card"></i> Proceed to Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Show modal with animation
            setTimeout(() => {
                document.getElementById('renewalModal').classList.add('show');
            }, 10);
        }

        function closeRenewalModal() {
            const modal = document.getElementById('renewalModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }

        function proceedToRenewal(applicationId) {
            // Show loading state
            const proceedBtn = document.querySelector('.btn-proceed');
            if (proceedBtn) {
                proceedBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                proceedBtn.disabled = true;
            }

            // Redirect to renewal payment page
            setTimeout(() => {
                window.location.href = `renew-pass.php?application_id=${applicationId}`;
            }, 1000);
        }

        function showRenewalToast(message) {
            const toast = document.createElement('div');
            toast.className = 'renewal-toast';
            toast.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('renewal-modal-overlay')) {
                closeRenewalModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRenewalModal();
            }
        });

        // Help & Support Modal Functions
        function openHelpModal() {
            const modalHTML = `
                <div id="helpModal" class="help-modal-overlay">
                    <div class="help-modal">
                        <div class="help-modal-header">
                            <h3><i class="fas fa-question-circle"></i> Help & Support</h3>
                            <button class="help-close-btn" onclick="closeHelpModal()">&times;</button>
                        </div>
                        <div class="help-modal-body">
                            <div class="help-tabs">
                                <button class="help-tab active" onclick="showHelpTab('faq')">
                                    <i class="fas fa-question"></i> FAQs
                                </button>
                                <button class="help-tab" onclick="showHelpTab('contact')">
                                    <i class="fas fa-phone"></i> Contact
                                </button>
                                <button class="help-tab" onclick="showHelpTab('support')">
                                    <i class="fas fa-headset"></i> Support
                                </button>
                            </div>

                            <!-- FAQ Content -->
                            <div id="faq-content" class="help-content active">
                                <h4 style="margin-bottom: 1.5rem; color: #17a2b8;">
                                    <i class="fas fa-question-circle"></i> Frequently Asked Questions
                                </h4>

                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>How do I apply for a bus pass?</span>
                                        <i class="fas fa-chevron-down faq-icon"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <p>To apply for a bus pass:</p>
                                        <ol>
                                            <li>Click on "Apply for New Pass" button on your dashboard</li>
                                            <li>Fill in all required personal and travel details</li>
                                            <li>Upload necessary documents (ID proof, photo, etc.)</li>
                                            <li>Select your pass type and route</li>
                                            <li>Complete the payment process</li>
                                            <li>Wait for admin approval (usually 2-3 business days)</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>How can I renew my bus pass?</span>
                                        <i class="fas fa-chevron-down faq-icon"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <p>You can renew your bus pass when it's within 10 days of expiry:</p>
                                        <ul>
                                            <li>Look for the amber "Renew Pass" button on your dashboard</li>
                                            <li>Click the button to open the renewal modal</li>
                                            <li>Review the details and proceed to payment</li>
                                            <li>Your pass will be automatically extended for 30 days</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>What documents do I need for application?</span>
                                        <i class="fas fa-chevron-down faq-icon"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <p>Required documents include:</p>
                                        <ul>
                                            <li><strong>ID Proof:</strong> Aadhaar Card, PAN Card, or Passport</li>
                                            <li><strong>Address Proof:</strong> Utility bill, bank statement, or rental agreement</li>
                                            <li><strong>Passport Photo:</strong> Recent color photograph</li>
                                            <li><strong>Student ID:</strong> For student passes (if applicable)</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>How long does approval take?</span>
                                        <i class="fas fa-chevron-down faq-icon"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <p>Application processing times:</p>
                                        <ul>
                                            <li><strong>Standard Processing:</strong> 2-3 business days</li>
                                            <li><strong>Peak Season:</strong> Up to 5 business days</li>
                                            <li><strong>Document Issues:</strong> Additional 1-2 days for clarification</li>
                                        </ul>
                                        <p>You'll receive email notifications about status updates.</p>
                                    </div>
                                </div>

                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>What payment methods are accepted?</span>
                                        <i class="fas fa-chevron-down faq-icon"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <p>We accept the following payment methods:</p>
                                        <ul>
                                            <li><strong>Credit/Debit Cards:</strong> Visa, MasterCard, RuPay</li>
                                            <li><strong>Net Banking:</strong> All major banks supported</li>
                                            <li><strong>UPI:</strong> PhonePe, Google Pay, Paytm</li>
                                            <li><strong>Digital Wallets:</strong> Paytm, Amazon Pay</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>Can I track my application status?</span>
                                        <i class="fas fa-chevron-down faq-icon"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <p>Yes! You can track your application in several ways:</p>
                                        <ul>
                                            <li>Check your dashboard for real-time status updates</li>
                                            <li>Use your Application ID to track progress</li>
                                            <li>Receive email notifications for status changes</li>
                                            <li>Contact support for detailed status information</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Content -->
                            <div id="contact-content" class="help-content">
                                <h4 style="margin-bottom: 1.5rem; color: #17a2b8;">
                                    <i class="fas fa-phone"></i> Contact Information
                                </h4>

                                <div class="contact-grid">
                                    <div class="contact-card">
                                        <i class="fas fa-phone"></i>
                                        <h4>Phone Support</h4>
                                        <p><strong>+91 1800-123-4567</strong></p>
                                        <p>Mon-Fri: 9:00 AM - 6:00 PM</p>
                                        <p>Sat: 9:00 AM - 2:00 PM</p>
                                    </div>

                                    <div class="contact-card">
                                        <i class="fas fa-envelope"></i>
                                        <h4>Email Support</h4>
                                        <p><strong>support@buspass.com</strong></p>
                                        <p>Response within 24 hours</p>
                                        <p>For general inquiries</p>
                                    </div>

                                    <div class="contact-card">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <h4>Office Address</h4>
                                        <p><strong>Nrupatunga Bus Pass Office</strong></p>
                                        <p>123 Transport Hub, City Center</p>
                                        <p>Mumbai, Maharashtra 400001</p>
                                    </div>

                                    <div class="contact-card">
                                        <i class="fas fa-clock"></i>
                                        <h4>Office Hours</h4>
                                        <p><strong>Monday - Friday</strong></p>
                                        <p>9:00 AM - 6:00 PM</p>
                                        <p><strong>Saturday:</strong> 9:00 AM - 2:00 PM</p>
                                    </div>
                                </div>

                                <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #17a2b8;">
                                    <h5 style="margin: 0 0 1rem 0; color: #0d47a1;">
                                        <i class="fas fa-info-circle"></i> Emergency Contact
                                    </h5>
                                    <p style="margin: 0; color: #1565c0;">
                                        For urgent issues or emergencies, call our 24/7 helpline:
                                        <strong>+91 9876-543-210</strong>
                                    </p>
                                </div>
                            </div>

                            <!-- Support Content -->
                            <div id="support-content" class="help-content">
                                <h4 style="margin-bottom: 1.5rem; color: #17a2b8;">
                                    <i class="fas fa-headset"></i> Submit Support Request
                                </h4>

                                <div class="support-form">
                                    <form id="supportForm" onsubmit="submitSupportForm(event)">
                                        <div class="form-group">
                                            <label for="supportName">Full Name *</label>
                                            <input type="text" id="supportName" name="name" required
                                                   placeholder="Enter your full name">
                                        </div>

                                        <div class="form-group">
                                            <label for="supportEmail">Email Address *</label>
                                            <input type="email" id="supportEmail" name="email" required
                                                   placeholder="Enter your email address">
                                        </div>

                                        <div class="form-group">
                                            <label for="supportPhone">Phone Number</label>
                                            <input type="tel" id="supportPhone" name="phone"
                                                   placeholder="Enter your phone number">
                                        </div>

                                        <div class="form-group">
                                            <label for="supportCategory">Issue Category *</label>
                                            <select id="supportCategory" name="category" required>
                                                <option value="">Select issue category</option>
                                                <option value="application">Application Issues</option>
                                                <option value="payment">Payment Problems</option>
                                                <option value="renewal">Pass Renewal</option>
                                                <option value="technical">Technical Issues</option>
                                                <option value="account">Account Problems</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="supportPriority">Priority Level *</label>
                                            <select id="supportPriority" name="priority" required>
                                                <option value="">Select priority</option>
                                                <option value="low">Low - General inquiry</option>
                                                <option value="medium">Medium - Non-urgent issue</option>
                                                <option value="high">High - Urgent issue</option>
                                                <option value="critical">Critical - Emergency</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="supportSubject">Subject *</label>
                                            <input type="text" id="supportSubject" name="subject" required
                                                   placeholder="Brief description of your issue">
                                        </div>

                                        <div class="form-group">
                                            <label for="supportMessage">Detailed Description *</label>
                                            <textarea id="supportMessage" name="message" required
                                                      placeholder="Please provide detailed information about your issue, including any error messages, steps you've taken, and what you expected to happen."></textarea>
                                        </div>

                                        <button type="submit" class="submit-btn">
                                            <i class="fas fa-paper-plane"></i> Submit Support Request
                                        </button>
                                    </form>
                                </div>

                                <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; margin-top: 1.5rem; border-left: 4px solid #ffc107;">
                                    <p style="margin: 0; color: #856404;">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Note:</strong> Support requests are typically responded to within 24 hours.
                                        For urgent issues, please call our phone support line.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHTML);
            setTimeout(() => {
                document.getElementById('helpModal').classList.add('show');
            }, 10);
        }

        function closeHelpModal() {
            const modal = document.getElementById('helpModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }

        function showHelpTab(tabName) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.help-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.help-content').forEach(content => content.classList.remove('active'));

            // Add active class to clicked tab and corresponding content
            event.target.classList.add('active');
            document.getElementById(tabName + '-content').classList.add('active');
        }

        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const isActive = element.classList.contains('active');

            // Close all other FAQs
            document.querySelectorAll('.faq-question').forEach(q => {
                q.classList.remove('active');
                q.nextElementSibling.classList.remove('active');
            });

            // Toggle current FAQ
            if (!isActive) {
                element.classList.add('active');
                answer.classList.add('active');
            }
        }

        function submitSupportForm(event) {
            event.preventDefault();

            const submitBtn = event.target.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;

            // Simulate form submission (replace with actual form handling)
            setTimeout(() => {
                alert('Support request submitted successfully! We will get back to you within 24 hours.');
                closeHelpModal();
            }, 2000);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('help-modal-overlay')) {
                closeHelpModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHelpModal();
            }
        });




    </script>

    <!-- Renewal Modal Styles -->
    <style>
        .renewal-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .renewal-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .renewal-modal {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            transform: scale(0.8) translateY(20px);
            transition: all 0.3s ease;
        }

        .renewal-modal-overlay.show .renewal-modal {
            transform: scale(1) translateY(0);
        }

        .renewal-modal-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .renewal-modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .renewal-modal-body {
            padding: 2rem;
        }

        .renewal-info h4 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .renewal-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
        }

        .detail-value {
            color: #333;
        }

        .detail-value.amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: #f59e0b;
        }

        .renewal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-proceed {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-proceed:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-proceed:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .renewal-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            z-index: 1001;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .renewal-toast.show {
            transform: translateX(0);
        }

        .renewal-toast i {
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .renewal-modal {
                width: 95%;
                margin: 1rem;
            }

            .renewal-modal-body {
                padding: 1.5rem;
            }

            .renewal-actions {
                flex-direction: column;
            }

            .renewal-toast {
                right: 10px;
                left: 10px;
                transform: translateY(-100%);
            }

            .renewal-toast.show {
                transform: translateY(0);
            }
        }
    </style>
</body>
</html>