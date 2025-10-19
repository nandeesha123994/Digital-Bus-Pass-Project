<?php
/**
 * Track Status Page
 * Allows users to track their bus pass application status
 */

session_start();
include('includes/dbconnection.php');
include('includes/config.php');

$message = '';
$messageType = '';
$applicationData = null;

// Handle form submission
if ($_POST) {
    if (isset($_POST['track_by_id'])) {
        // Track by Application ID
        $applicationId = trim($_POST['application_id']);

        if (!empty($applicationId)) {
            // Check if application_id column exists
            $columnCheckQuery = "SHOW COLUMNS FROM bus_pass_applications LIKE 'application_id'";
            $columnExists = $con->query($columnCheckQuery)->num_rows > 0;

            // Check if it's a new format Application ID (BPMS2025XXXXXX) or old numeric ID
            if (preg_match('/^BPMS\d{4}\d{6}$/', $applicationId) && $columnExists) {
                // New format Application ID
                $query = "SELECT ba.*, u.full_name, u.email, u.phone, bpt.type_name as pass_type
                         FROM bus_pass_applications ba
                         JOIN users u ON ba.user_id = u.id
                         LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                         WHERE ba.application_id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("s", $applicationId);
            } else if (is_numeric($applicationId)) {
                // Old numeric ID format (for backward compatibility)
                $query = "SELECT ba.*, u.full_name, u.email, u.phone, bpt.type_name as pass_type
                         FROM bus_pass_applications ba
                         JOIN users u ON ba.user_id = u.id
                         LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                         WHERE ba.id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("i", $applicationId);
            } else if (preg_match('/^BPMS\d{4}\d{6}$/', $applicationId) && !$columnExists) {
                $messageType = 'error';
                $message = 'The Application ID system is being updated. Please use numeric IDs for now or contact support.';
                $stmt = null;
            } else {
                $messageType = 'error';
                $message = 'Invalid Application ID format. Please enter a valid Application ID (e.g., BPMS2025123456 or numeric ID).';
                $stmt = null;
            }

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $applicationData = $result->fetch_assoc();
                    $messageType = 'success';
                    $message = 'Application found successfully!';
                } else {
                    $messageType = 'error';
                    $message = 'No application found with this ID. Please check your Application ID and try again.';
                }
            }
        } else {
            $messageType = 'error';
            $message = 'Please enter a valid Application ID.';
        }
    }
}

// If user is logged in, redirect to dashboard
if (isset($_SESSION['uid'])) {
    header('Location: user-dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Application Status - Bus Pass Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .track-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        .track-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .track-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .track-header p {
            color: #666;
            font-size: 1.1rem;
        }
        .track-options {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }
        .track-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .track-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .track-option.active {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .track-option h3 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .track-option p {
            color: #666;
            margin: 0;
        }
        .status-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
            border-left: 4px solid #667eea;
        }
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
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
        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .detail-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .detail-value {
            color: #333;
            font-size: 1rem;
        }
        .back-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="track-container">
        <div class="track-header">
            <h1><i class="fas fa-search"></i> Track Application Status</h1>
            <p>Enter your Application ID to check your bus pass application status</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if (!$applicationData): ?>
            <div class="track-options">
                <div class="track-option active">
                    <h3><i class="fas fa-id-card"></i> Track by Application ID</h3>
                    <p>Enter your unique Application ID to view status</p>

                    <form method="post" style="margin-top: 20px;">
                        <div class="form-group">
                            <label for="application_id" class="form-label">Application ID</label>
                            <input type="text"
                                   id="application_id"
                                   name="application_id"
                                   class="form-control"
                                   placeholder="Enter your Application ID (e.g., BPMS2025123456)"
                                   required>
                        </div>
                        <button type="submit" name="track_by_id" class="btn btn-primary btn-lg" style="width: 100%;">
                            <i class="fas fa-search"></i> Track Status
                        </button>
                    </form>
                </div>

                <div class="track-option">
                    <h3><i class="fas fa-sign-in-alt"></i> Login to View All Applications</h3>
                    <p>Access your account to view all your applications and detailed information</p>

                    <div style="margin-top: 20px;">
                        <a href="login.php" class="btn btn-success btn-lg" style="width: 100%;">
                            <i class="fas fa-sign-in-alt"></i> Login to Account
                        </a>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Need Help?</strong><br>
                • Your Application ID is in the format BPMS2025XXXXXX (provided when you submit your application)<br>
                • Check your email for the Application ID<br>
                • Old numeric IDs (1, 2, 3...) are also supported for existing applications<br>
                • If you have an account, login to view all your applications<br>
                • Contact support if you need assistance
            </div>
        <?php else: ?>
            <!-- Display Application Status -->
            <div class="status-card">
                <div class="status-header">
                    <h3><i class="fas fa-file-alt"></i> Application <?php echo $applicationData['application_id'] ? $applicationData['application_id'] : '#' . $applicationData['id']; ?></h3>
                    <span class="status-badge status-<?php echo strtolower($applicationData['status']); ?>">
                        <?php echo ucfirst($applicationData['status']); ?>
                    </span>
                </div>

                <div class="status-details">
                    <div class="detail-item">
                        <div class="detail-label">Applicant Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($applicationData['full_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo htmlspecialchars($applicationData['email'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value"><?php echo htmlspecialchars($applicationData['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Pass Type</div>
                        <div class="detail-value"><?php echo htmlspecialchars($applicationData['pass_type'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">From Location</div>
                        <div class="detail-value"><?php echo htmlspecialchars($applicationData['source'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">To Location</div>
                        <div class="detail-value"><?php echo htmlspecialchars($applicationData['destination'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Application Date</div>
                        <div class="detail-value"><?php echo $applicationData['application_date'] ? date('M d, Y', strtotime($applicationData['application_date'])) : 'N/A'; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Payment Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-<?php echo strtolower($applicationData['payment_status']); ?>">
                                <?php echo ucfirst($applicationData['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ($applicationData['status'] === 'pending'): ?>
                    <div class="alert alert-warning" style="margin-top: 20px;">
                        <i class="fas fa-clock"></i>
                        <strong>Application Under Review</strong><br>
                        Your application is currently being reviewed by our team. You will receive an email notification once the status is updated.
                    </div>
                <?php elseif ($applicationData['status'] === 'approved' && $applicationData['payment_status'] === 'pending'): ?>
                    <div class="alert alert-success" style="margin-top: 20px;">
                        <i class="fas fa-check-circle"></i>
                        <strong>Application Approved!</strong><br>
                        Your application has been approved. Please complete the payment to receive your bus pass.
                        <div style="margin-top: 15px;">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-credit-card"></i> Complete Payment
                            </a>
                        </div>
                    </div>
                <?php elseif ($applicationData['status'] === 'approved' && $applicationData['payment_status'] === 'paid'): ?>
                    <div class="alert alert-success" style="margin-top: 20px;">
                        <i class="fas fa-check-circle"></i>
                        <strong>Bus Pass Ready!</strong><br>
                        Your payment has been processed and your bus pass is ready. Login to download your pass.
                        <div style="margin-top: 15px;">
                            <a href="login.php" class="btn btn-success">
                                <i class="fas fa-download"></i> Download Pass
                            </a>
                        </div>
                    </div>
                <?php elseif ($applicationData['status'] === 'rejected'): ?>
                    <div class="alert alert-danger" style="margin-top: 20px;">
                        <i class="fas fa-times-circle"></i>
                        <strong>Application Rejected</strong><br>
                        Unfortunately, your application has been rejected. Please contact support for more information or submit a new application.
                    </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 25px;">
                    <a href="track-status.php" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Track Another Application
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>
