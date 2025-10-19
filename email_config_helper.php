<?php
/**
 * Email Configuration Helper
 * Helps users set up email configuration easily
 */

$message = '';
$messageType = '';

if (isset($_POST['save_config'])) {
    $adminEmail = trim($_POST['admin_email']);
    $smtpHost = trim($_POST['smtp_host']);
    $smtpPort = (int)$_POST['smtp_port'];
    $smtpUsername = trim($_POST['smtp_username']);
    $smtpPassword = trim($_POST['smtp_password']);
    $enableEmails = isset($_POST['enable_emails']) ? 'true' : 'false';
    
    // Read current config file
    $configFile = 'includes/config.php';
    $configContent = file_get_contents($configFile);
    
    // Update email settings
    $configContent = preg_replace("/define\('ADMIN_EMAIL', '.*?'\);/", "define('ADMIN_EMAIL', '$adminEmail');", $configContent);
    $configContent = preg_replace("/define\('SMTP_HOST', '.*?'\);/", "define('SMTP_HOST', '$smtpHost');", $configContent);
    $configContent = preg_replace("/define\('SMTP_PORT', .*?\);/", "define('SMTP_PORT', $smtpPort);", $configContent);
    $configContent = preg_replace("/define\('SMTP_USERNAME', '.*?'\);/", "define('SMTP_USERNAME', '$smtpUsername');", $configContent);
    $configContent = preg_replace("/define\('SMTP_PASSWORD', '.*?'\);/", "define('SMTP_PASSWORD', '$smtpPassword');", $configContent);
    $configContent = preg_replace("/define\('ENABLE_EMAIL_NOTIFICATIONS', .*?\);/", "define('ENABLE_EMAIL_NOTIFICATIONS', $enableEmails);", $configContent);
    
    if (file_put_contents($configFile, $configContent)) {
        $message = "Email configuration saved successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to save configuration. Please check file permissions.";
        $messageType = "error";
    }
}

// Read current settings
include('includes/config.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Configuration Helper - Bus Pass Management</title>
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
        h1 { color: #007bff; }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #333;
        }
        input, select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            box-sizing: border-box;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        button { 
            background: #007bff; 
            color: white; 
            padding: 12px 30px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: 600;
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
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .provider-examples {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .provider-examples h4 {
            margin-top: 0;
            color: #495057;
        }
        .provider-example {
            margin-bottom: 10px;
            padding: 10px;
            background: white;
            border-radius: 3px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Configuration Helper</h1>
        
        <div class="info-box">
            <strong>📋 Setup Instructions:</strong><br>
            1. Choose your email provider below<br>
            2. Fill in your email credentials<br>
            3. For Gmail: Enable 2FA and generate an App Password<br>
            4. Test the configuration using the email test page
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="admin_email">Admin Email Address:</label>
                <input type="email" id="admin_email" name="admin_email" required 
                       value="<?php echo htmlspecialchars(ADMIN_EMAIL); ?>"
                       placeholder="admin@yourdomain.com">
            </div>
            
            <div class="form-group">
                <label for="smtp_host">SMTP Host:</label>
                <input type="text" id="smtp_host" name="smtp_host" required 
                       value="<?php echo htmlspecialchars(SMTP_HOST); ?>"
                       placeholder="smtp.gmail.com">
            </div>
            
            <div class="form-group">
                <label for="smtp_port">SMTP Port:</label>
                <input type="number" id="smtp_port" name="smtp_port" required 
                       value="<?php echo SMTP_PORT; ?>"
                       placeholder="587">
            </div>
            
            <div class="form-group">
                <label for="smtp_username">SMTP Username (Email):</label>
                <input type="email" id="smtp_username" name="smtp_username" required 
                       value="<?php echo htmlspecialchars(SMTP_USERNAME); ?>"
                       placeholder="your_email@gmail.com">
            </div>
            
            <div class="form-group">
                <label for="smtp_password">SMTP Password (App Password for Gmail):</label>
                <input type="password" id="smtp_password" name="smtp_password" required 
                       value="<?php echo htmlspecialchars(SMTP_PASSWORD); ?>"
                       placeholder="Your app password">
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="enable_emails" name="enable_emails" 
                           <?php echo ENABLE_EMAIL_NOTIFICATIONS ? 'checked' : ''; ?>>
                    <label for="enable_emails">Enable Email Notifications</label>
                </div>
            </div>
            
            <button type="submit" name="save_config">💾 Save Configuration</button>
        </form>
        
        <div class="provider-examples">
            <h4>📮 Common Email Provider Settings:</h4>
            
            <div class="provider-example">
                <strong>Gmail:</strong><br>
                Host: smtp.gmail.com | Port: 587<br>
                <small>Note: Enable 2FA and use App Password instead of regular password</small>
            </div>
            
            <div class="provider-example">
                <strong>Outlook/Hotmail:</strong><br>
                Host: smtp-mail.outlook.com | Port: 587
            </div>
            
            <div class="provider-example">
                <strong>Yahoo:</strong><br>
                Host: smtp.mail.yahoo.com | Port: 587
            </div>
            
            <div class="provider-example">
                <strong>Custom SMTP:</strong><br>
                Contact your hosting provider for SMTP settings
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="test_email.php" style="color: #007bff; text-decoration: none; margin-right: 20px;">
                📧 Test Email Configuration
            </a>
            <a href="index.php" style="color: #007bff; text-decoration: none;">
                🏠 Back to Home
            </a>
        </div>
    </div>
</body>
</html>
