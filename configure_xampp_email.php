<?php
/**
 * XAMPP Email Configuration Helper
 * Configures XAMPP to work with email sending
 */

$message = '';
$messageType = '';

if (isset($_POST['configure_xampp'])) {
    $result = configureXAMPPEmail();
    $message = $result['message'];
    $messageType = $result['type'];
}

if (isset($_POST['use_gmail'])) {
    $result = configureGmailSMTP($_POST);
    $message = $result['message'];
    $messageType = $result['type'];
}

function configureXAMPPEmail() {
    // Get XAMPP installation path
    $xamppPath = 'C:\\xampp';
    if (!is_dir($xamppPath)) {
        $xamppPath = 'C:\\xampp';
        if (!is_dir($xamppPath)) {
            return ['message' => 'XAMPP installation not found. Please check your XAMPP path.', 'type' => 'error'];
        }
    }
    
    $phpIniPath = $xamppPath . '\\php\\php.ini';
    
    if (!file_exists($phpIniPath)) {
        return ['message' => 'php.ini file not found at: ' . $phpIniPath, 'type' => 'error'];
    }
    
    // Read php.ini
    $phpIni = file_get_contents($phpIniPath);
    
    // Configure for local mail server
    $mailConfig = [
        'SMTP = localhost',
        'smtp_port = 25',
        'sendmail_from = admin@localhost'
    ];
    
    // Update or add mail configuration
    foreach ($mailConfig as $config) {
        $key = explode(' = ', $config)[0];
        if (strpos($phpIni, $key) !== false) {
            $phpIni = preg_replace("/^$key\s*=.*$/m", $config, $phpIni);
        } else {
            $phpIni .= "\n" . $config;
        }
    }
    
    // Backup original file
    copy($phpIniPath, $phpIniPath . '.backup.' . date('Y-m-d-H-i-s'));
    
    if (file_put_contents($phpIniPath, $phpIni)) {
        return [
            'message' => 'XAMPP email configuration updated successfully! Please restart Apache.',
            'type' => 'success'
        ];
    } else {
        return [
            'message' => 'Failed to update php.ini. Please check file permissions.',
            'type' => 'error'
        ];
    }
}

function configureGmailSMTP($data) {
    $configFile = 'includes/config.php';
    
    if (!file_exists($configFile)) {
        return ['message' => 'Config file not found!', 'type' => 'error'];
    }
    
    $configContent = file_get_contents($configFile);
    
    // Update Gmail SMTP settings
    $updates = [
        'SMTP_HOST' => 'smtp.gmail.com',
        'SMTP_PORT' => 587,
        'SMTP_ENCRYPTION' => 'tls',
        'SMTP_USERNAME' => $data['gmail_email'],
        'SMTP_PASSWORD' => $data['gmail_password'],
        'ADMIN_EMAIL' => $data['gmail_email'],
        'ENABLE_EMAIL_NOTIFICATIONS' => 'true'
    ];
    
    foreach ($updates as $key => $value) {
        if (is_string($value)) {
            $pattern = "/define\('$key', '.*?'\);/";
            $replacement = "define('$key', '$value');";
        } else {
            $pattern = "/define\('$key', .*?\);/";
            $replacement = "define('$key', $value);";
        }
        $configContent = preg_replace($pattern, $replacement, $configContent);
    }
    
    if (file_put_contents($configFile, $configContent)) {
        return [
            'message' => 'Gmail SMTP configuration saved successfully!',
            'type' => 'success'
        ];
    } else {
        return [
            'message' => 'Failed to save configuration.',
            'type' => 'error'
        ];
    }
}

// Check current configuration
include_once 'includes/config.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>XAMPP Email Configuration - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 { color: #007bff; margin: 0; }
        .option-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        .option-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        }
        .option-card h3 {
            color: #007bff;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #333;
        }
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            box-sizing: border-box;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .btn { 
            background: #007bff; 
            color: white; 
            padding: 12px 30px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .message { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 6px; 
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
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            color: #856404;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-server"></i> XAMPP Email Configuration</h1>
            <p>Quick setup for email functionality in XAMPP environment</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="option-card">
            <h3><i class="fab fa-google"></i> Option 1: Use Gmail SMTP (Recommended)</h3>
            <p>Configure the system to use Gmail's SMTP server for reliable email delivery.</p>
            
            <div class="warning-box">
                <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                You need to enable 2-Factor Authentication on your Gmail account and generate an App Password.
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="gmail_email">Gmail Address:</label>
                    <input type="email" id="gmail_email" name="gmail_email" required 
                           placeholder="your_email@gmail.com">
                </div>
                
                <div class="form-group">
                    <label for="gmail_password">Gmail App Password:</label>
                    <input type="password" id="gmail_password" name="gmail_password" required 
                           placeholder="16-character app password">
                </div>
                
                <button type="submit" name="use_gmail" class="btn btn-success">
                    <i class="fas fa-cog"></i> Configure Gmail SMTP
                </button>
            </form>
            
            <div class="info-box">
                <strong>How to get Gmail App Password:</strong><br>
                1. Go to your Google Account settings<br>
                2. Security → 2-Step Verification → App passwords<br>
                3. Generate password for "Mail"<br>
                4. Use the 16-character password above
            </div>
        </div>

        <div class="option-card">
            <h3><i class="fas fa-server"></i> Option 2: Configure XAMPP Local Mail</h3>
            <p>Configure XAMPP to use local mail server (requires additional setup).</p>
            
            <div class="warning-box">
                <strong><i class="fas fa-exclamation-triangle"></i> Note:</strong>
                This option requires installing a local mail server like Mercury or hMailServer.
            </div>
            
            <form method="post">
                <button type="submit" name="configure_xampp" class="btn btn-warning">
                    <i class="fas fa-wrench"></i> Configure XAMPP Mail
                </button>
            </form>
            
            <div class="info-box">
                <strong>Additional Steps Required:</strong><br>
                1. Install Mercury Mail Server (included with XAMPP)<br>
                2. Configure Mercury in XAMPP Control Panel<br>
                3. Restart Apache after configuration
            </div>
        </div>

        <div class="option-card">
            <h3><i class="fas fa-download"></i> Option 3: Use Advanced Email Setup</h3>
            <p>Use the comprehensive email setup wizard with PHPMailer support.</p>
            
            <a href="setup_email.php" class="btn">
                <i class="fas fa-magic"></i> Launch Email Setup Wizard
            </a>
            
            <div class="info-box">
                <strong>Features:</strong><br>
                • Support for multiple email providers<br>
                • PHPMailer installation<br>
                • Advanced SMTP configuration<br>
                • Step-by-step guidance
            </div>
        </div>

        <div class="option-card">
            <h3><i class="fas fa-info-circle"></i> Current Configuration</h3>
            <div class="code-block">
                SMTP Host: <?php echo SMTP_HOST; ?><br>
                SMTP Port: <?php echo SMTP_PORT; ?><br>
                Admin Email: <?php echo ADMIN_EMAIL; ?><br>
                Email Notifications: <?php echo ENABLE_EMAIL_NOTIFICATIONS ? 'Enabled' : 'Disabled'; ?>
            </div>
            
            <a href="test_email.php" class="btn">
                <i class="fas fa-envelope"></i> Test Current Configuration
            </a>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="test_email.php" style="color: #007bff; text-decoration: none; margin-right: 20px;">
                📧 Test Email System
            </a>
            <a href="index.php" style="color: #007bff; text-decoration: none;">
                🏠 Back to Home
            </a>
        </div>
    </div>
</body>
</html>
