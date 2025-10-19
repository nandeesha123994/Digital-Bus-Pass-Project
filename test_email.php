<?php
/**
 * Email Testing Script
 * Test the email functionality of the Bus Pass Management System
 */

include('includes/config.php');
include('includes/email.php');

$message = '';
$messageType = '';

if (isset($_POST['test_email'])) {
    $testEmail = trim($_POST['email']);
    $emailType = $_POST['email_type'];

    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        $success = false;

        switch ($emailType) {
            case 'application_confirmation':
                $success = EmailService::sendApplicationConfirmation(
                    $testEmail,
                    'Test User',
                    12345,
                    'Monthly Pass',
                    85.00
                );
                break;

            case 'payment_confirmation':
                $success = EmailService::sendPaymentConfirmation(
                    $testEmail,
                    'Test User',
                    12345,
                    'DEMO_' . time(),
                    85.00,
                    'BP2024001'
                );
                break;

            case 'status_update':
                $success = EmailService::sendStatusUpdate(
                    $testEmail,
                    'Test User',
                    12345,
                    'Approved',
                    'Your application has been approved. Please collect your pass.'
                );
                break;

            case 'pass_activation':
                $success = EmailService::sendPassActivation(
                    $testEmail,
                    'Test User',
                    'BP2024001',
                    date('Y-m-d'),
                    date('Y-m-d', strtotime('+30 days'))
                );
                break;
        }

        if ($success) {
            $message = "Test email sent successfully to $testEmail!";
            $messageType = "success";
        } else {
            $lastError = error_get_last();
            $errorDetails = $lastError ? $lastError['message'] : 'Unknown error';
            $message = "Failed to send email. Error: " . $errorDetails . "<br><br>";
            $message .= "Common solutions:<br>";
            $message .= "• <a href='configure_xampp_email.php'>Configure XAMPP Email</a><br>";
            $message .= "• <a href='setup_email.php'>Use Email Setup Wizard</a><br>";
            $message .= "• Check if PHPMailer is installed<br>";
            $message .= "• Verify SMTP credentials in config.php";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Test - Bus Pass Management System</title>
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
            max-width: 600px;
            margin: 0 auto;
        }
        h1 { color: #007bff; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #0056b3; }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .config-status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .config-enabled { background: #d4edda; color: #155724; }
        .config-disabled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚌 Email System Test</h1>

        <div class="info-box">
            <strong>📧 Email Configuration Status:</strong><br>
            <div class="config-status <?php echo ENABLE_EMAIL_NOTIFICATIONS ? 'config-enabled' : 'config-disabled'; ?>">
                Email Notifications: <?php echo ENABLE_EMAIL_NOTIFICATIONS ? '✅ Enabled' : '❌ Disabled'; ?>
            </div>
            <br>
            <strong>SMTP Settings:</strong><br>
            Host: <?php echo SMTP_HOST; ?><br>
            Port: <?php echo SMTP_PORT; ?><br>
            Username: <?php echo SMTP_USERNAME; ?><br>
            Admin Email: <?php echo ADMIN_EMAIL; ?>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="email">Test Email Address:</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your email address">
            </div>

            <div class="form-group">
                <label for="email_type">Email Type to Test:</label>
                <select id="email_type" name="email_type" required>
                    <option value="">Select Email Type</option>
                    <option value="application_confirmation" <?php echo (isset($_POST['email_type']) && $_POST['email_type'] == 'application_confirmation') ? 'selected' : ''; ?>>
                        Application Confirmation
                    </option>
                    <option value="payment_confirmation" <?php echo (isset($_POST['email_type']) && $_POST['email_type'] == 'payment_confirmation') ? 'selected' : ''; ?>>
                        Payment Confirmation
                    </option>
                    <option value="status_update" <?php echo (isset($_POST['email_type']) && $_POST['email_type'] == 'status_update') ? 'selected' : ''; ?>>
                        Status Update (Approved)
                    </option>
                    <option value="pass_activation" <?php echo (isset($_POST['email_type']) && $_POST['email_type'] == 'pass_activation') ? 'selected' : ''; ?>>
                        Pass Activation
                    </option>
                </select>
            </div>

            <button type="submit" name="test_email">📧 Send Test Email</button>
        </form>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <h3>📋 Email Templates Available:</h3>
            <ul>
                <li><strong>Application Confirmation:</strong> Sent when user submits application</li>
                <li><strong>Payment Confirmation:</strong> Sent when payment is successful</li>
                <li><strong>Status Update:</strong> Sent when admin updates application status</li>
                <li><strong>Pass Activation:</strong> Sent when pass is approved and activated</li>
            </ul>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
            <strong>⚠️ Note:</strong> If emails are not being sent, check:
            <ul>
                <li>SMTP credentials in config.php</li>
                <li>Gmail app password (if using Gmail)</li>
                <li>Spam/junk folder</li>
                <li>Server mail configuration</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="color: #007bff; text-decoration: none;">← Back to Home</a> |
            <a href="setup_database.php" style="color: #007bff; text-decoration: none;">Database Setup</a> |
            <a href="admin-login.php" style="color: #007bff; text-decoration: none;">Admin Login</a>
        </div>
    </div>
</body>
</html>
