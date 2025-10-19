<?php
session_start();
include('includes/dbconnection.php');
include('includes/config.php');

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

$applicationId = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;

if (!$applicationId) {
    header('Location: user-dashboard.php?error=invalid_application');
    exit();
}

// Get application details
$query = "SELECT ba.*, bpt.type_name, bpt.duration_days, u.full_name, u.email
          FROM bus_pass_applications ba
          JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          JOIN users u ON ba.user_id = u.id
          WHERE ba.id = ? AND ba.user_id = ? AND ba.status = 'Approved'";
$stmt = $con->prepare($query);
$stmt->bind_param("ii", $applicationId, $_SESSION['uid']);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();

if (!$application) {
    header('Location: user-dashboard.php?error=application_not_found');
    exit();
}

// Check if pass is eligible for renewal (within 10 days of expiry or expired)
$currentDate = new DateTime();
$expiryDate = new DateTime($application['valid_until']);
$daysUntilExpiry = $currentDate->diff($expiryDate)->days;
$isExpired = $currentDate > $expiryDate;
$isNearExpiry = $daysUntilExpiry <= 10;

if (!$isExpired && !$isNearExpiry) {
    header('Location: user-dashboard.php?error=not_eligible_for_renewal');
    exit();
}

// Handle renewal submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_renewal'])) {
    try {
        $con->begin_transaction();

        // Check if there's already a pending renewal for this application
        $pendingCheck = $con->prepare("SELECT id FROM bus_pass_applications
                                      WHERE user_id = ? AND status = 'Approved'
                                      AND valid_from > ? AND admin_remarks LIKE '%renewed%'");
        $pendingCheck->bind_param("is", $_SESSION['uid'], $application['valid_until']);
        $pendingCheck->execute();

        if ($pendingCheck->get_result()->num_rows > 0) {
            throw new Exception("You already have a renewed pass. Please check your dashboard.");
        }
        
        // Generate new application ID for renewal
        $newApplicationId = 'BPMS' . date('Y') . sprintf('%06d', rand(100000, 999999));
        
        // Check if application ID already exists
        $checkQuery = "SELECT id FROM bus_pass_applications WHERE application_id = ?";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bind_param("s", $newApplicationId);
        $checkStmt->execute();
        
        // Generate new ID if collision detected
        while ($checkStmt->get_result()->num_rows > 0) {
            $newApplicationId = 'BPMS' . date('Y') . sprintf('%06d', rand(100000, 999999));
            $checkStmt->bind_param("s", $newApplicationId);
            $checkStmt->execute();
        }
        
        // Generate new pass number
        $newPassNumber = 'BP' . date('Y') . sprintf('%06d', rand(100000, 999999));
        
        // Set new validity dates (30 days from current date or expiry date, whichever is later)
        $startDate = $isExpired ? date('Y-m-d') : $application['valid_until'];
        $newValidFrom = date('Y-m-d', strtotime($startDate . ' +1 day'));
        $newValidUntil = date('Y-m-d', strtotime($newValidFrom . ' +30 days'));
        
        // Create renewal application
        $renewalQuery = "INSERT INTO bus_pass_applications 
                        (user_id, pass_type_id, application_id, applicant_name, date_of_birth, 
                         gender, phone, address, source, destination, amount, status, 
                         payment_status, pass_number, valid_from, valid_until, 
                         application_date, processed_date, admin_remarks)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', 'Paid', ?, ?, ?, NOW(), NOW(), ?)";
        
        $renewalStmt = $con->prepare($renewalQuery);
        $renewalAmount = 100.00; // Fixed renewal amount
        $renewalRemarks = 'Pass renewed automatically - Extended validity period';
        
        $renewalStmt->bind_param("iissssssssdsssss", 
            $application['user_id'],
            $application['pass_type_id'],
            $newApplicationId,
            $application['applicant_name'],
            $application['date_of_birth'],
            $application['gender'],
            $application['phone'],
            $application['address'],
            $application['source'],
            $application['destination'],
            $renewalAmount,
            $newPassNumber,
            $newValidFrom,
            $newValidUntil,
            $renewalRemarks
        );
        
        if (!$renewalStmt->execute()) {
            throw new Exception("Failed to create renewal application");
        }
        
        $newApplicationDbId = $con->insert_id;
        
        // Create payment record
        $paymentQuery = "INSERT INTO payments 
                        (application_id, user_id, amount, currency, payment_method, 
                         transaction_id, status, payment_date, metadata)
                        VALUES (?, ?, ?, 'INR', 'renewal', ?, 'completed', NOW(), ?)";
        
        $transactionId = 'REN_' . date('YmdHis') . '_' . $newApplicationDbId;
        $metadata = json_encode([
            'renewal_type' => 'automatic',
            'original_application_id' => $applicationId,
            'original_pass_number' => $application['pass_number'],
            'renewed_at' => date('Y-m-d H:i:s')
        ]);
        
        $paymentStmt = $con->prepare($paymentQuery);
        $paymentStmt->bind_param("iidss", $newApplicationDbId, $application['user_id'], $renewalAmount, $transactionId, $metadata);
        
        if (!$paymentStmt->execute()) {
            throw new Exception("Failed to create payment record");
        }
        
        $con->commit();
        
        // Send email notification (if email system is available)
        try {
            if (class_exists('Email')) {
                Email::sendPassActivation(
                    $application['email'],
                    $application['full_name'],
                    $newPassNumber,
                    $newValidFrom,
                    $newValidUntil
                );
            }
        } catch (Exception $emailError) {
            // Email failed but don't stop the process
            error_log("Email notification failed: " . $emailError->getMessage());
        }

        // Redirect to success page
        header("Location: user-dashboard.php?renewal_success=1&new_application_id=$newApplicationDbId");
        exit();
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "Error processing renewal: " . $e->getMessage();
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew Bus Pass - Nrupatunga Digital Bus Pass System</title>
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

        .header {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px rgba(21, 101, 192, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .header-text h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .renewal-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .renewal-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .renewal-header h2 {
            color: #f59e0b;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .renewal-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .current-pass-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #1565c0;
        }

        .current-pass-info h3 {
            color: #1565c0;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .info-value {
            color: #333;
            font-size: 1rem;
        }

        .renewal-details {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .renewal-details h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .renewal-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .summary-item {
            text-align: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .summary-value {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .summary-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .renewal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-confirm {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-confirm:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .expiry-warning {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        .expiry-warning.near-expiry {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .renewal-card {
                padding: 1.5rem;
            }
            
            .renewal-actions {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .renewal-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-bus"></i>
                </div>
                <div class="header-text">
                    <h1>Renew Bus Pass</h1>
                </div>
            </div>
            <a href="user-dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <div class="container">
        <div class="renewal-card">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="renewal-header">
                <h2>
                    <i class="fas fa-redo"></i>
                    Renew Your Bus Pass
                </h2>
                <p>Extend your bus pass validity for another 30 days</p>
            </div>

            <?php if ($isExpired): ?>
                <div class="expiry-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Your pass has expired on <?php echo date('M d, Y', strtotime($application['valid_until'])); ?>
                </div>
            <?php else: ?>
                <div class="expiry-warning near-expiry">
                    <i class="fas fa-clock"></i>
                    Your pass expires in <?php echo $daysUntilExpiry; ?> day<?php echo $daysUntilExpiry != 1 ? 's' : ''; ?> on <?php echo date('M d, Y', strtotime($application['valid_until'])); ?>
                </div>
            <?php endif; ?>

            <div class="current-pass-info">
                <h3><i class="fas fa-id-card"></i> Current Pass Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Pass Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($application['pass_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Holder Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($application['applicant_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Route</span>
                        <span class="info-value"><?php echo htmlspecialchars($application['source'] . ' → ' . $application['destination']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Pass Type</span>
                        <span class="info-value"><?php echo htmlspecialchars($application['type_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Valid Until</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($application['valid_until'])); ?></span>
                    </div>
                </div>
            </div>

            <div class="renewal-details">
                <h3><i class="fas fa-calendar-plus"></i> Renewal Details</h3>
                <div class="renewal-summary">
                    <div class="summary-item">
                        <div class="summary-value">30 Days</div>
                        <div class="summary-label">Extension Period</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">₹100.00</div>
                        <div class="summary-label">Renewal Fee</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo date('M d, Y', strtotime($isExpired ? '+1 day' : $application['valid_until'] . ' +1 day')); ?></div>
                        <div class="summary-label">New Start Date</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo date('M d, Y', strtotime($isExpired ? '+31 days' : $application['valid_until'] . ' +31 days')); ?></div>
                        <div class="summary-label">New Expiry Date</div>
                    </div>
                </div>
            </div>

            <form method="POST" action="">
                <div class="renewal-actions">
                    <a href="user-dashboard.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                    <button type="submit" name="confirm_renewal" class="btn btn-confirm">
                        <i class="fas fa-check"></i>
                        Confirm Renewal (₹100.00)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add confirmation dialog
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to renew your bus pass for ₹100.00? This action cannot be undone.')) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitBtn = document.querySelector('.btn-confirm');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
            }
        });
    </script>
</body>
</html>
