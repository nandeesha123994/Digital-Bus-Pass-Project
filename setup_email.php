<?php
/**
 * Email Configuration Setup for Bus Pass Management System
 * This script helps configure email settings and install PHPMailer
 */

$message = '';
$messageType = '';
$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Handle form submissions
if ($_POST) {
    switch ($currentStep) {
        case 1:
            // Email provider selection
            if (isset($_POST['email_provider'])) {
                $provider = $_POST['email_provider'];
                header("Location: setup_email.php?step=2&provider=$provider");
                exit();
            }
            break;
            
        case 2:
            // Configuration setup
            if (isset($_POST['save_config'])) {
                $result = saveEmailConfiguration($_POST);
                $message = $result['message'];
                $messageType = $result['type'];
            }
            break;
            
        case 3:
            // PHPMailer installation
            if (isset($_POST['install_phpmailer'])) {
                $result = installPHPMailer();
                $message = $result['message'];
                $messageType = $result['type'];
            }
            break;
    }
}

function saveEmailConfiguration($data) {
    $configFile = 'includes/config.php';
    
    if (!file_exists($configFile)) {
        return ['message' => 'Config file not found!', 'type' => 'error'];
    }
    
    $configContent = file_get_contents($configFile);
    
    // Update email settings
    $updates = [
        'ADMIN_EMAIL' => $data['admin_email'],
        'SMTP_HOST' => $data['smtp_host'],
        'SMTP_PORT' => (int)$data['smtp_port'],
        'SMTP_USERNAME' => $data['smtp_username'],
        'SMTP_PASSWORD' => $data['smtp_password'],
        'SMTP_ENCRYPTION' => $data['smtp_encryption'],
        'ENABLE_EMAIL_NOTIFICATIONS' => isset($data['enable_emails']) ? 'true' : 'false'
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
        return ['message' => 'Email configuration saved successfully!', 'type' => 'success'];
    } else {
        return ['message' => 'Failed to save configuration. Check file permissions.', 'type' => 'error'];
    }
}

function installPHPMailer() {
    // Check if composer is available
    $composerPath = '';
    $possiblePaths = ['composer', 'composer.phar', '../composer.phar', '../../composer.phar'];
    
    foreach ($possiblePaths as $path) {
        if (shell_exec("$path --version 2>nul") !== null) {
            $composerPath = $path;
            break;
        }
    }
    
    if (empty($composerPath)) {
        return [
            'message' => 'Composer not found. Please install Composer first or download PHPMailer manually.',
            'type' => 'error'
        ];
    }
    
    // Try to install PHPMailer
    $output = shell_exec("$composerPath require phpmailer/phpmailer 2>&1");
    
    if (strpos($output, 'phpmailer/phpmailer') !== false) {
        return [
            'message' => 'PHPMailer installed successfully! You can now use advanced email features.',
            'type' => 'success'
        ];
    } else {
        return [
            'message' => 'Failed to install PHPMailer. Error: ' . $output,
            'type' => 'error'
        ];
    }
}

// Get current settings
include_once 'includes/config.php';

$providers = [
    'gmail' => [
        'name' => 'Gmail',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'instructions' => 'Enable 2FA and generate an App Password'
    ],
    'outlook' => [
        'name' => 'Outlook/Hotmail',
        'smtp_host' => 'smtp-mail.outlook.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'instructions' => 'Use your regular email password'
    ],
    'yahoo' => [
        'name' => 'Yahoo Mail',
        'smtp_host' => 'smtp.mail.yahoo.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'instructions' => 'Generate an App Password in Yahoo settings'
    ],
    'custom' => [
        'name' => 'Custom SMTP',
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'instructions' => 'Contact your hosting provider for SMTP settings'
    ]
];

$selectedProvider = isset($_GET['provider']) ? $_GET['provider'] : 'gmail';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Setup - Bus Pass Management System</title>
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
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 25px;
            font-weight: bold;
        }
        .step.active {
            background: #007bff;
            color: white;
        }
        .step.inactive {
            background: #e9ecef;
            color: #6c757d;
        }
        .provider-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .provider-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .provider-card:hover, .provider-card.selected {
            border-color: #007bff;
            background: #f8f9ff;
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #333;
        }
        input, select { 
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
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope-open"></i> Email Configuration Setup</h1>
            <p>Configure email settings for the Bus Pass Management System</p>
        </div>

        <div class="step-indicator">
            <div class="step <?php echo $currentStep == 1 ? 'active' : 'inactive'; ?>">
                <i class="fas fa-server"></i> 1. Choose Provider
            </div>
            <div class="step <?php echo $currentStep == 2 ? 'active' : 'inactive'; ?>">
                <i class="fas fa-cog"></i> 2. Configure
            </div>
            <div class="step <?php echo $currentStep == 3 ? 'active' : 'inactive'; ?>">
                <i class="fas fa-download"></i> 3. Install PHPMailer
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($currentStep == 1): ?>
            <!-- Step 1: Provider Selection -->
            <h3>Step 1: Choose Your Email Provider</h3>
            <form method="post">
                <div class="provider-grid">
                    <?php foreach ($providers as $key => $provider): ?>
                        <div class="provider-card" onclick="selectProvider('<?php echo $key; ?>')">
                            <input type="radio" name="email_provider" value="<?php echo $key; ?>" id="provider_<?php echo $key; ?>" style="display: none;">
                            <h4><?php echo $provider['name']; ?></h4>
                            <p><?php echo $provider['instructions']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn">Next: Configure Settings</button>
            </form>

        <?php elseif ($currentStep == 2): ?>
            <!-- Step 2: Configuration -->
            <h3>Step 2: Configure Email Settings</h3>
            <?php $provider = $providers[$selectedProvider]; ?>
            
            <div class="info-box">
                <strong>Selected Provider:</strong> <?php echo $provider['name']; ?><br>
                <strong>Instructions:</strong> <?php echo $provider['instructions']; ?>
            </div>

            <form method="post">
                <div class="form-group">
                    <label for="admin_email">Admin Email Address:</label>
                    <input type="email" id="admin_email" name="admin_email" required 
                           value="<?php echo htmlspecialchars(ADMIN_EMAIL); ?>">
                </div>
                
                <div class="form-group">
                    <label for="smtp_host">SMTP Host:</label>
                    <input type="text" id="smtp_host" name="smtp_host" required 
                           value="<?php echo $provider['smtp_host'] ?: htmlspecialchars(SMTP_HOST); ?>">
                </div>
                
                <div class="form-group">
                    <label for="smtp_port">SMTP Port:</label>
                    <input type="number" id="smtp_port" name="smtp_port" required 
                           value="<?php echo $provider['smtp_port'] ?: SMTP_PORT; ?>">
                </div>
                
                <div class="form-group">
                    <label for="smtp_encryption">Encryption:</label>
                    <select id="smtp_encryption" name="smtp_encryption" required>
                        <option value="tls" <?php echo ($provider['smtp_encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo ($provider['smtp_encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="smtp_username">Email Username:</label>
                    <input type="email" id="smtp_username" name="smtp_username" required 
                           value="<?php echo htmlspecialchars(SMTP_USERNAME); ?>">
                </div>
                
                <div class="form-group">
                    <label for="smtp_password">Email Password (App Password for Gmail):</label>
                    <input type="password" id="smtp_password" name="smtp_password" required 
                           placeholder="Enter your email password or app password">
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="enable_emails" name="enable_emails" 
                               <?php echo ENABLE_EMAIL_NOTIFICATIONS ? 'checked' : ''; ?>>
                        <label for="enable_emails">Enable Email Notifications</label>
                    </div>
                </div>
                
                <button type="submit" name="save_config" class="btn">Save Configuration</button>
                <a href="setup_email.php?step=3" class="btn btn-success">Next: Install PHPMailer</a>
            </form>

        <?php elseif ($currentStep == 3): ?>
            <!-- Step 3: PHPMailer Installation -->
            <h3>Step 3: Install PHPMailer (Recommended)</h3>
            
            <div class="info-box">
                <strong>Why PHPMailer?</strong><br>
                PHPMailer provides better email delivery, authentication support, and error handling compared to PHP's built-in mail() function.
            </div>

            <?php if (class_exists('PHPMailer\PHPMailer\PHPMailer')): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> PHPMailer is already installed and ready to use!
                </div>
                <a href="test_email.php" class="btn btn-success">Test Email System</a>
            <?php else: ?>
                <p>PHPMailer is not currently installed. Click the button below to install it automatically.</p>
                
                <form method="post">
                    <button type="submit" name="install_phpmailer" class="btn">
                        <i class="fas fa-download"></i> Install PHPMailer
                    </button>
                </form>
                
                <div class="info-box" style="margin-top: 20px;">
                    <strong>Manual Installation:</strong><br>
                    If automatic installation fails, you can install PHPMailer manually:<br>
                    1. Download from: <a href="https://github.com/PHPMailer/PHPMailer" target="_blank">GitHub</a><br>
                    2. Or use Composer: <code>composer require phpmailer/phpmailer</code>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="test_email.php" style="color: #007bff; text-decoration: none; margin-right: 20px;">
                📧 Test Email System
            </a>
            <a href="index.php" style="color: #007bff; text-decoration: none;">
                🏠 Back to Home
            </a>
        </div>
    </div>

    <script>
        function selectProvider(provider) {
            // Remove selected class from all cards
            document.querySelectorAll('.provider-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById('provider_' + provider).checked = true;
        }
    </script>
</body>
</html>
