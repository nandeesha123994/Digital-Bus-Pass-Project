<?php
session_start();
include('includes/dbconnection.php');
include('includes/config.php');

$application = null;
$message = '';
$messageType = '';

// Check for error messages from download
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'download_failed':
            $message = "Download failed. Please try again or contact support.";
            $messageType = "error";
            break;
        case 'pass_not_ready':
            $message = "Pass is not ready for download yet. Please wait for approval.";
            $messageType = "error";
            break;
    }
}

if (isset($_POST['track'])) {
    $applicationId = trim($_POST['application_id']);

    if (empty($applicationId)) {
        $message = "Please enter an Application ID";
        $messageType = "error";
    } else {
        // Try to find the application by different ID formats
        $query = "";
        $searchParam = "";

        // Check if it's in BPMS format (e.g., BPMS2025123456)
        if (preg_match('/^BPMS\d{10}$/', $applicationId)) {
            $query = "SELECT ba.*, bpt.type_name, bpt.duration_days, c.category_name, u.full_name as user_name, u.email as user_email
                      FROM bus_pass_applications ba
                      LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                      LEFT JOIN categories c ON ba.category_id = c.id
                      LEFT JOIN users u ON ba.user_id = u.id
                      WHERE ba.application_id = ?";
            $searchParam = $applicationId;
        }
        // Check if it's a numeric ID (e.g., 123, #123)
        elseif (preg_match('/^#?(\d+)$/', $applicationId, $matches)) {
            $numericId = $matches[1];
            $query = "SELECT ba.*, bpt.type_name, bpt.duration_days, c.category_name, u.full_name as user_name, u.email as user_email
                      FROM bus_pass_applications ba
                      LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                      LEFT JOIN categories c ON ba.category_id = c.id
                      LEFT JOIN users u ON ba.user_id = u.id
                      WHERE ba.id = ?";
            $searchParam = $numericId;
        }
        // Try searching by application_id as string (fallback)
        else {
            $query = "SELECT ba.*, bpt.type_name, bpt.duration_days, c.category_name, u.full_name as user_name, u.email as user_email
                      FROM bus_pass_applications ba
                      LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
                      LEFT JOIN categories c ON ba.category_id = c.id
                      LEFT JOIN users u ON ba.user_id = u.id
                      WHERE ba.application_id = ? OR ba.id = ?";
            $searchParam = $applicationId;
        }

        if (!empty($query)) {
            $stmt = $con->prepare($query);

            if (strpos($query, 'OR') !== false) {
                // For the fallback query with OR condition
                $stmt->bind_param("ss", $searchParam, $searchParam);
            } else {
                $stmt->bind_param("s", $searchParam);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $application = $result->fetch_assoc();
                $message = "Application found successfully!";
                $messageType = "success";
            } else {
                $message = "No application found with ID: " . htmlspecialchars($applicationId) . ". Please check your Application ID and try again.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid Application ID format. Please enter a valid Application ID (e.g., BPMS2025123456 or numeric ID).";
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
    <title>Track Application - Nrupatunga Digital Bus Pass System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/color-schemes.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modern Indigo/Violet Theme for Track Application */
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

        .track-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .track-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(90, 79, 207, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        .track-form h3 {
            color: #4B0082;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .application-details {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(90, 79, 207, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #4B0082;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 1.25rem;
            border: 2px solid #ECE8FF;
            border-radius: 12px;
            font-size: 1.1rem;
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

        .btn {
            background: linear-gradient(90deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            border: none;
            padding: 1.25rem 2.5rem;
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

        .btn-success {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #20c997 0%, #17a2b8 100%);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        .btn-primary {
            background: linear-gradient(90deg, #5A4FCF 0%, #6A67D5 100%);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 234, 167, 0.2) 100%);
            color: #856404;
            border: 2px solid rgba(255, 193, 7, 0.3);
        }

        .status-approved {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2) 0%, rgba(195, 230, 203, 0.2) 100%);
            color: #155724;
            border: 2px solid rgba(40, 167, 69, 0.3);
        }

        .status-rejected {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2) 0%, rgba(245, 198, 203, 0.2) 100%);
            color: #721c24;
            border: 2px solid rgba(220, 53, 69, 0.3);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 0;
            border-bottom: 1px solid rgba(90, 79, 207, 0.1);
            transition: all 0.3s ease;
        }

        .detail-row:hover {
            background: rgba(90, 79, 207, 0.05);
            margin: 0 -1rem;
            padding: 1.25rem 1rem;
            border-radius: 8px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #4B0082;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-value {
            flex: 2;
            text-align: right;
            color: #212529;
            font-weight: 500;
        }

        .progress-timeline {
            margin: 2.5rem 0;
            background: linear-gradient(135deg, rgba(90, 79, 207, 0.05) 0%, rgba(106, 103, 213, 0.05) 100%);
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid rgba(90, 79, 207, 0.1);
        }

        .progress-timeline h4 {
            color: #4B0082;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            position: relative;
            padding: 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .timeline-item:hover {
            background: rgba(255, 255, 255, 0.7);
            transform: translateX(5px);
        }

        .timeline-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .timeline-icon.completed {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .timeline-icon.current {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: #333;
        }

        .timeline-icon.pending {
            background: #e9ecef;
            color: #6c757d;
        }

        .search-tips {
            background: linear-gradient(135deg, rgba(90, 79, 207, 0.1) 0%, rgba(106, 103, 213, 0.1) 100%);
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            border: 1px solid rgba(90, 79, 207, 0.2);
        }

        .search-tips h5 {
            color: #5A4FCF;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-tips ul {
            list-style: none;
            padding: 0;
        }

        .search-tips li {
            margin: 0.75rem 0;
            color: #4B0082;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-tips li::before {
            content: '✓';
            background: linear-gradient(135deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
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

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .track-container {
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

            .track-form,
            .application-details {
                padding: 1.5rem;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .detail-value {
                text-align: left;
            }

            .timeline-item {
                flex-direction: column;
                text-align: center;
            }

            .timeline-icon {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><i class="fas fa-search"></i> Track Your Application</h2>
    </div>

    <div class="nav">
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="user-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="apply-pass.php"><i class="fas fa-plus"></i> Apply Pass</a>
        <?php if (isset($_SESSION['uid'])): ?>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <?php endif; ?>
    </div>

    <div class="track-container">
        <!-- Search Form -->
        <div class="track-form">
            <h3><i class="fas fa-search"></i> Track Your Application</h3>

            <form method="post">
                <div class="form-group">
                    <label for="application_id" class="form-label">
                        <i class="fas fa-id-card"></i> Enter Application ID:
                    </label>
                    <input type="text"
                           id="application_id"
                           name="application_id"
                           class="form-control"
                           placeholder="e.g., BPMS2025123456 or #123"
                           value="<?php echo isset($_POST['application_id']) ? htmlspecialchars($_POST['application_id']) : ''; ?>"
                           required>
                </div>

                <button type="submit" name="track" class="btn">
                    <i class="fas fa-search"></i> Track Application
                </button>
            </form>

            <div class="search-tips">
                <h5><i class="fas fa-lightbulb"></i> Search Tips:</h5>
                <ul>
                    <li><strong>BPMS Format:</strong> BPMS2025123456 (received after application submission)</li>
                    <li><strong>Numeric ID:</strong> 123 or #123 (database record ID)</li>
                    <li><strong>Case Sensitive:</strong> Please enter the ID exactly as provided</li>
                </ul>
            </div>
        </div>

        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Application Details -->
        <?php if ($application): ?>
            <div class="application-details">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h3 style="color: #4B0082; font-size: 1.8rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-file-alt"></i> Application Details
                    </h3>
                    <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                        <?php
                        $statusIcon = '';
                        switch($application['status']) {
                            case 'Approved':
                                $statusIcon = 'fas fa-check-circle';
                                break;
                            case 'Rejected':
                                $statusIcon = 'fas fa-times-circle';
                                break;
                            default:
                                $statusIcon = 'fas fa-clock';
                        }
                        ?>
                        <i class="<?php echo $statusIcon; ?>"></i>
                        <?php echo $application['status']; ?>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-id-card"></i> Application ID:</span>
                    <span class="detail-value">
                        <strong><?php echo $application['application_id'] ? htmlspecialchars($application['application_id']) : '#' . $application['id']; ?></strong>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-user"></i> Applicant Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($application['applicant_name']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-ticket-alt"></i> Pass Type:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($application['type_name']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-bus"></i> Transport Category:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($application['category_name']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-route"></i> Route:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($application['source']) . ' → ' . htmlspecialchars($application['destination']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-rupee-sign"></i> Amount:</span>
                    <span class="detail-value"><strong>₹<?php echo number_format($application['amount'], 2); ?></strong></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-calendar"></i> Application Date:</span>
                    <span class="detail-value"><?php echo date('F j, Y \a\t g:i A', strtotime($application['application_date'])); ?></span>
                </div>

                <?php if ($application['processed_date']): ?>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-check-circle"></i> Processed Date:</span>
                    <span class="detail-value"><?php echo date('F j, Y \a\t g:i A', strtotime($application['processed_date'])); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($application['admin_remarks']): ?>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-comment"></i> Admin Remarks:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($application['admin_remarks']); ?></span>
                </div>
                <?php endif; ?>

                <!-- Progress Timeline -->
                <div class="progress-timeline">
                    <h4><i class="fas fa-tasks"></i> Application Progress</h4>

                    <div class="timeline-item">
                        <div class="timeline-icon completed">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <strong>Application Submitted</strong><br>
                            <small><?php echo date('M j, Y', strtotime($application['application_date'])); ?></small>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-icon <?php echo ($application['status'] != 'Pending') ? 'completed' : 'current'; ?>">
                            <i class="fas fa-<?php echo ($application['status'] != 'Pending') ? 'check' : 'clock'; ?>"></i>
                        </div>
                        <div>
                            <strong>Under Review</strong><br>
                            <small><?php echo ($application['status'] != 'Pending') ? 'Completed' : 'In Progress'; ?></small>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-icon <?php echo ($application['status'] == 'Approved') ? 'completed' : (($application['status'] == 'Rejected') ? 'completed' : 'pending'); ?>">
                            <i class="fas fa-<?php echo ($application['status'] == 'Approved') ? 'check' : (($application['status'] == 'Rejected') ? 'times' : 'clock'); ?>"></i>
                        </div>
                        <div>
                            <strong>Decision Made</strong><br>
                            <small>
                                <?php
                                if ($application['status'] == 'Approved') {
                                    echo 'Application Approved';
                                } elseif ($application['status'] == 'Rejected') {
                                    echo 'Application Rejected';
                                } else {
                                    echo 'Pending Decision';
                                }
                                ?>
                            </small>
                        </div>
                    </div>

                    <?php if ($application['status'] == 'Approved'): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon <?php echo ($application['pass_number']) ? 'completed' : 'current'; ?>">
                            <i class="fas fa-<?php echo ($application['pass_number']) ? 'id-card' : 'clock'; ?>"></i>
                        </div>
                        <div>
                            <strong>Pass Generation</strong><br>
                            <small><?php echo ($application['pass_number']) ? 'Pass Ready: ' . $application['pass_number'] : 'Generating Pass'; ?></small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div style="text-align: center; margin-top: 2rem;">
                    <?php if ($application['status'] == 'Approved' && $application['pass_number']): ?>
                        <a href="download-pass.php?id=<?php echo $application['id']; ?>"
                           class="btn btn-success"
                           target="_blank"
                           onclick="this.innerHTML='<i class=\"fas fa-spinner fa-spin\"></i> Generating Pass...'; setTimeout(() => this.innerHTML='<i class=\"fas fa-download\"></i> Download Pass', 3000);">
                            <i class="fas fa-download"></i> Download Pass
                        </a>
                        <p style="margin-top: 1rem; color: #28a745; font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i>
                            Click to download your bus pass. The pass will open in a new tab where you can save it as PDF.
                        </p>
                    <?php elseif ($application['status'] == 'Approved' && !$application['pass_number']): ?>
                        <button class="btn" style="background: #6c757d; cursor: not-allowed;" disabled>
                            <i class="fas fa-clock"></i> Pass Being Generated
                        </button>
                        <p style="margin-top: 1rem; color: #6c757d; font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i>
                            Your application is approved. Pass generation is in progress.
                        </p>
                    <?php else: ?>
                        <button class="btn" style="background: #6c757d; cursor: not-allowed;" disabled>
                            <i class="fas fa-lock"></i> Download Not Available
                        </button>
                        <p style="margin-top: 1rem; color: #6c757d; font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i>
                            Download will be available once your application is approved and pass is generated.
                        </p>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['uid']) && $_SESSION['uid'] == $application['user_id']): ?>
                        <a href="user-dashboard.php" class="btn btn-primary" style="margin-left: 1rem;">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                    <?php endif; ?>

                    <a href="track-application.php" class="btn" style="background: #6c757d; margin-left: 1rem;">
                        <i class="fas fa-search"></i> Track Another Application
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
