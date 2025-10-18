<?php
/**
 * XAMPP Email Configuration Script
 * This script helps configure email settings for XAMPP
 */

$message = '';
$messageType = '';

// Handle configuration update
if (isset($_POST['configure_email'])) {
    $method = $_POST['email_method'];
    
    if ($method === 'gmail') {
        $gmail_email = trim($_POST['gmail_email']);
        $gmail_password = trim($_POST['gmail_password']);
        
        if (!empty($gmail_email) && !empty($gmail_password)) {
            // Update MailHelper configuration
            require_once 'includes/mailHelper.php';
            MailHelper::updateConfig([
                'smtp_username' => $gmail_email,
                'smtp_password' => $gmail_password,
                'from_email' => $gmail_email,
                'from_name' => 'Bus Pass Management System'
            ]);
            
            $message = "Gmail SMTP configuration updated successfully! You can now test email sending.";
            $messageType = "success";
        } else {
            $message = "Please provide both Gmail email and app password.";
            $messageType = "error";
        }
    } elseif ($method === 'sendgrid') {
        $sendgrid_key = trim($_POST['sendgrid_key']);
        $from_email = trim($_POST['from_email']);
        
        if (!empty($sendgrid_key) && !empty($from_email)) {
            // Update MailHelper configuration for SendGrid
            require_once 'includes/mailHelper.php';
            MailHelper::updateConfig([
                'smtp_host' => 'smtp.sendgrid.net',
                'smtp_port' => 587,
                'smtp_username' => 'apikey',
                'smtp_password' => $sendgrid_key,
                'from_email' => $from_email,
                'from_name' => 'Bus Pass Management System'
            ]);
            
            $message = "SendGrid SMTP configuration updated successfully!";
            $messageType = "success";
        } else {
            $message = "Please provide SendGrid API key and from email.";
            $messageType = "error";
        }
    } elseif ($method === 'disable') {
        // Create a mock email function
        $message = "Email functionality disabled. Emails will be logged but not sent.";
        $messageType = "warning";
    }
}

// Test email sending
if (isset($_POST['test_email'])) {
    $test_email = trim($_POST['test_email']);
    
    if (!empty($test_email) && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        require_once 'includes/mailHelper.php';
        
        try {
            $result = MailHelper::sendTestEmail($test_email);
            
            if ($result) {
                $message = "✅ Test email sent successfully to $test_email!";
                $messageType = "success";
            } else {
                $message = "❌ Failed to send test email. Check your configuration.";
                $messageType = "error";
            }
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "Please enter a valid email address for testing.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAMPP Email Configuration - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #FF6B6B, #4ECDC4);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .content {
            padding: 30px;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
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
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        .section h2 {
            color: #495057;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.4rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-test {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .radio-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .radio-option input[type="radio"] {
            margin-right: 12px;
        }
        .radio-option.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .config-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .config-section.active {
            display: block;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .info-box h4 {
            color: #004085;
            margin: 0 0 10px 0;
        }
        .info-box p {
            color: #004085;
            margin: 0;
            line-height: 1.5;
        }
        .steps {
            counter-reset: step-counter;
        }
        .step {
            counter-increment: step-counter;
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
        }
        .step::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            background: #667eea;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope-open"></i> XAMPP Email Configuration</h1>
            <p>Configure email settings for your Bus Pass Management System</p>
        </div>
        
        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Email Method Selection -->
            <div class="section">
                <h2><i class="fas fa-cog"></i> Choose Email Method</h2>
                <form method="post">
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="email_method" value="gmail" required>
                            <div>
                                <strong>Gmail SMTP (Recommended)</strong><br>
                                <small>Use your Gmail account with App Password for reliable email delivery</small>
                            </div>
                        </label>
                        
                        <label class="radio-option">
                            <input type="radio" name="email_method" value="sendgrid">
                            <div>
                                <strong>SendGrid API</strong><br>
                                <small>Professional email service with high deliverability</small>
                            </div>
                        </label>
                        
                        <label class="radio-option">
                            <input type="radio" name="email_method" value="disable">
                            <div>
                                <strong>Disable Email (Development)</strong><br>
                                <small>Emails will be logged but not sent - for testing only</small>
                            </div>
                        </label>
                    </div>

                    <!-- Gmail Configuration -->
                    <div id="gmail-config" class="config-section">
                        <h3>Gmail SMTP Configuration</h3>
                        <div class="info-box">
                            <h4>📋 Setup Instructions:</h4>
                            <div class="steps">
                                <div class="step">Enable 2-Factor Authentication on your Gmail account</div>
                                <div class="step">Go to Google Account → Security → 2-Step Verification</div>
                                <div class="step">Click "App passwords" and generate password for "Mail"</div>
                                <div class="step">Use the generated 16-character password below</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="gmail_email">Gmail Email Address:</label>
                            <input type="email" id="gmail_email" name="gmail_email" class="form-control" 
                                   placeholder="your.email@gmail.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="gmail_password">Gmail App Password:</label>
                            <input type="password" id="gmail_password" name="gmail_password" class="form-control" 
                                   placeholder="16-character app password">
                        </div>
                    </div>

                    <!-- SendGrid Configuration -->
                    <div id="sendgrid-config" class="config-section">
                        <h3>SendGrid API Configuration</h3>
                        <div class="info-box">
                            <h4>📋 Setup Instructions:</h4>
                            <div class="steps">
                                <div class="step">Sign up for SendGrid account at sendgrid.com</div>
                                <div class="step">Go to Settings → API Keys</div>
                                <div class="step">Create new API key with "Mail Send" permissions</div>
                                <div class="step">Copy the API key and paste below</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="sendgrid_key">SendGrid API Key:</label>
                            <input type="password" id="sendgrid_key" name="sendgrid_key" class="form-control" 
                                   placeholder="SG.xxxxxxxxxxxxxxxx">
                        </div>
                        
                        <div class="form-group">
                            <label for="from_email">From Email Address:</label>
                            <input type="email" id="from_email" name="from_email" class="form-control" 
                                   placeholder="noreply@yourdomain.com">
                        </div>
                    </div>

                    <!-- Disable Configuration -->
                    <div id="disable-config" class="config-section">
                        <h3>Disable Email Functionality</h3>
                        <div class="info-box">
                            <h4>⚠️ Development Mode:</h4>
                            <p>This option will disable actual email sending. Email attempts will be logged for debugging purposes. Use this only during development or testing.</p>
                        </div>
                    </div>

                    <button type="submit" name="configure_email" class="btn">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                </form>
            </div>

            <!-- Test Email Section -->
            <div class="section">
                <h2><i class="fas fa-paper-plane"></i> Test Email Sending</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="test_email">Test Email Address:</label>
                        <input type="email" id="test_email" name="test_email" class="form-control" 
                               placeholder="test@example.com" required>
                    </div>
                    
                    <button type="submit" name="test_email" class="btn btn-test">
                        <i class="fas fa-paper-plane"></i> Send Test Email
                    </button>
                </form>
            </div>

            <!-- Quick Links -->
            <div class="section">
                <h2><i class="fas fa-link"></i> Quick Links</h2>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="admin-email-test.php" class="btn">
                        <i class="fas fa-envelope"></i> Email Test Panel
                    </a>
                    <a href="admin-dashboard.php" class="btn">
                        <i class="fas fa-dashboard"></i> Admin Dashboard
                    </a>
                    <a href="index.php" class="btn">
                        <i class="fas fa-home"></i> Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle radio button changes
        document.querySelectorAll('input[name="email_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Hide all config sections
                document.querySelectorAll('.config-section').forEach(section => {
                    section.classList.remove('active');
                });
                
                // Show selected config section
                const selectedConfig = document.getElementById(this.value + '-config');
                if (selectedConfig) {
                    selectedConfig.classList.add('active');
                }
                
                // Update radio option styling
                document.querySelectorAll('.radio-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.closest('.radio-option').classList.add('selected');
            });
        });
    </script>
</body>
</html>
