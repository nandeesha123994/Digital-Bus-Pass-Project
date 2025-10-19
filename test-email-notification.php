<?php
session_start();
include('includes/dbconnection.php');
include('includes/config.php');
include('includes/email.php');

// Set admin session for testing
$_SESSION['admin_logged_in'] = true;

$message = '';
$messageType = '';

// Handle test email sending
if (isset($_POST['send_test_email'])) {
    $testEmail = trim($_POST['test_email']);
    $testStatus = $_POST['test_status'];
    $testRemarks = trim($_POST['test_remarks']);
    
    if (!empty($testEmail) && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            $emailSent = EmailService::sendStatusUpdate(
                $testEmail,
                'Test User',
                '12345',
                $testStatus,
                $testRemarks
            );
            
            if ($emailSent) {
                $message = "✅ Test email sent successfully to $testEmail!";
                $messageType = "success";
            } else {
                $message = "❌ Failed to send test email. Please check email configuration.";
                $messageType = "error";
            }
        } catch (Exception $e) {
            $message = "❌ Email error: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "❌ Please enter a valid email address.";
        $messageType = "error";
    }
}

// Get a real application for testing
$realAppQuery = "SELECT ba.id, ba.applicant_name, u.full_name as user_name, u.email as user_email, ba.status
                 FROM bus_pass_applications ba
                 LEFT JOIN users u ON ba.user_id = u.id
                 WHERE u.email IS NOT NULL
                 LIMIT 1";
$realAppResult = $con->query($realAppQuery);
$realApp = $realAppResult ? $realAppResult->fetch_assoc() : null;

// Handle real application email test
if (isset($_POST['send_real_email']) && $realApp) {
    $newStatus = $_POST['real_status'];
    $realRemarks = trim($_POST['real_remarks']);
    
    try {
        $emailSent = EmailService::sendStatusUpdate(
            $realApp['user_email'],
            $realApp['user_name'],
            $realApp['id'],
            $newStatus,
            $realRemarks
        );
        
        if ($emailSent) {
            $message = "✅ Real application email sent successfully to {$realApp['user_email']}!";
            $messageType = "success";
        } else {
            $message = "❌ Failed to send real application email.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "❌ Email error: " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Email Notifications - Bus Pass Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #007bff;
            text-align: center;
            margin-bottom: 30px;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="email"], select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        textarea {
            height: 80px;
            resize: vertical;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px 10px 0;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-size: 14px;
        }
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Test Email Notifications</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- Test Email Section -->
        <div class="test-section">
            <h3>🧪 Send Test Email</h3>
            <p>Send a test status update email to any email address:</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="test_email">Test Email Address:</label>
                    <input type="email" id="test_email" name="test_email" required 
                           placeholder="Enter email address to test">
                </div>
                
                <div class="form-group">
                    <label for="test_status">Test Status:</label>
                    <select id="test_status" name="test_status" required>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="test_remarks">Test Remarks:</label>
                    <textarea id="test_remarks" name="test_remarks" 
                              placeholder="Enter test admin remarks...">This is a test email notification from the Bus Pass Management System.</textarea>
                </div>
                
                <button type="submit" name="send_test_email" class="btn">
                    📧 Send Test Email
                </button>
            </form>
        </div>

        <?php if ($realApp): ?>
        <!-- Real Application Test Section -->
        <div class="test-section">
            <h3>🎯 Send Real Application Email</h3>
            <p>Send email notification for a real application:</p>
            
            <div class="info-box">
                <strong>Application Details:</strong><br>
                ID: <?php echo $realApp['id']; ?><br>
                Applicant: <?php echo htmlspecialchars($realApp['applicant_name']); ?><br>
                User: <?php echo htmlspecialchars($realApp['user_name']); ?><br>
                Email: <?php echo htmlspecialchars($realApp['user_email']); ?><br>
                Current Status: <?php echo htmlspecialchars($realApp['status']); ?>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="real_status">New Status:</label>
                    <select id="real_status" name="real_status" required>
                        <option value="Approved" <?php echo $realApp['status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $realApp['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="Pending" <?php echo $realApp['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="real_remarks">Admin Remarks:</label>
                    <textarea id="real_remarks" name="real_remarks" 
                              placeholder="Enter admin remarks...">Status updated via email notification test.</textarea>
                </div>
                
                <button type="submit" name="send_real_email" class="btn btn-success">
                    📧 Send Real Application Email
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="test-section">
            <h3>⚠️ No Real Applications Available</h3>
            <p>No applications with email addresses found in the database. Please create some test applications first.</p>
        </div>
        <?php endif; ?>

        <!-- Email Configuration Info -->
        <div class="test-section">
            <h3>⚙️ Email Configuration Status</h3>
            <div class="info-box">
                <strong>Email Settings:</strong><br>
                Email Notifications: <?php echo ENABLE_EMAIL_NOTIFICATIONS ? '✅ Enabled' : '❌ Disabled'; ?><br>
                Admin Email: <?php echo ADMIN_EMAIL; ?><br>
                Site URL: <?php echo SITE_URL; ?><br>
                <?php if (defined('SMTP_HOST')): ?>
                SMTP Host: <?php echo SMTP_HOST; ?><br>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-link">
            <a href="admin-dashboard.php">← Back to Admin Dashboard</a> |
            <a href="index.php">Home</a>
        </div>
    </div>
</body>
</html>
