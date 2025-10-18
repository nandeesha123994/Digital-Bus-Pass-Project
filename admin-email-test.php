<?php
session_start();
include('includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once 'includes/mailHelper.php';
require_once 'includes/email-templates.php';

$message = '';
$messageType = '';
$testResults = [];

// Handle email configuration update
if (isset($_POST['update_config'])) {
    $newConfig = [
        'smtp_username' => trim($_POST['smtp_username']),
        'smtp_password' => trim($_POST['smtp_password']),
        'from_email' => trim($_POST['from_email']),
        'from_name' => trim($_POST['from_name']),
        'enable_debug' => isset($_POST['enable_debug'])
    ];
    
    MailHelper::updateConfig($newConfig);
    $message = "Email configuration updated successfully!";
    $messageType = "success";
}

// Handle test email sending
if (isset($_POST['send_test'])) {
    $testEmail = trim($_POST['test_email']);
    $testType = $_POST['test_type'];
    
    if (!empty($testEmail) && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            switch ($testType) {
                case 'basic':
                    $result = MailHelper::sendTestEmail($testEmail);
                    $testResults[] = [
                        'type' => 'Basic Test Email',
                        'recipient' => $testEmail,
                        'status' => $result ? 'Success' : 'Failed',
                        'message' => $result ? 'Test email sent successfully!' : 'Failed to send test email'
                    ];
                    break;
                    
                case 'approval':
                    $emailData = [
                        'user_name' => 'John Doe (Test User)',
                        'application_id' => 'TEST001',
                        'pass_type' => 'Monthly Pass',
                        'pass_number' => 'BP2024TEST001',
                        'valid_from' => date('Y-m-d'),
                        'valid_until' => date('Y-m-d', strtotime('+30 days')),
                        'remarks' => 'This is a test approval email.',
                        'dashboard_url' => 'http://localhost/buspassmsfull/user-dashboard.php'
                    ];
                    $template = EmailTemplates::getTemplate('approval', $emailData);
                    $result = MailHelper::sendEmail($testEmail, '🎉 Test Approval Email', $template, true);
                    $testResults[] = [
                        'type' => 'Approval Email Template',
                        'recipient' => $testEmail,
                        'status' => $result ? 'Success' : 'Failed',
                        'message' => $result ? 'Approval email template sent successfully!' : 'Failed to send approval email'
                    ];
                    break;
                    
                case 'rejection':
                    $emailData = [
                        'user_name' => 'Jane Smith (Test User)',
                        'application_id' => 'TEST002',
                        'pass_type' => 'Weekly Pass',
                        'remarks' => 'This is a test rejection email. Documents were incomplete.',
                        'support_url' => 'http://localhost/buspassmsfull/contact-support.php',
                        'reapply_url' => 'http://localhost/buspassmsfull/apply-pass.php'
                    ];
                    $template = EmailTemplates::getTemplate('rejection', $emailData);
                    $result = MailHelper::sendEmail($testEmail, '📋 Test Rejection Email', $template, true);
                    $testResults[] = [
                        'type' => 'Rejection Email Template',
                        'recipient' => $testEmail,
                        'status' => $result ? 'Success' : 'Failed',
                        'message' => $result ? 'Rejection email template sent successfully!' : 'Failed to send rejection email'
                    ];
                    break;
            }
        } catch (Exception $e) {
            $testResults[] = [
                'type' => ucfirst($testType) . ' Test',
                'recipient' => $testEmail,
                'status' => 'Error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    } else {
        $message = "Please enter a valid email address for testing.";
        $messageType = "error";
    }
}

// Get current configuration
$currentConfig = MailHelper::getConfig();

// Check email logs table
$emailLogsExists = false;
try {
    $checkTable = "SHOW TABLES LIKE 'email_logs'";
    $result = $con->query($checkTable);
    $emailLogsExists = ($result && $result->num_rows > 0);
} catch (Exception $e) {
    $emailLogsExists = false;
}

// Get recent email logs if table exists
$recentLogs = [];
if ($emailLogsExists) {
    try {
        $logsQuery = "SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 10";
        $logsResult = $con->query($logsQuery);
        while ($log = $logsResult->fetch_assoc()) {
            $recentLogs[] = $log;
        }
    } catch (Exception $e) {
        // Silent fail
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email System Test & Configuration - Bus Pass Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .header h1 {
            color: #2d3748;
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .nav-links a {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 30px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .card-body {
            padding: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3748;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }
        .btn-warning {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            color: white;
        }
        .btn-info {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .message.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        .test-results {
            margin-top: 20px;
        }
        .test-result {
            background: #f7fafc;
            border-left: 4px solid #4299e1;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 0 8px 8px 0;
        }
        .test-result.success {
            border-left-color: #48bb78;
            background: #f0fff4;
        }
        .test-result.failed {
            border-left-color: #f56565;
            background: #fffafa;
        }
        .test-result.error {
            border-left-color: #ed8936;
            background: #fffaf0;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .config-label {
            font-weight: 600;
            color: #4a5568;
        }
        .config-value {
            color: #718096;
            font-family: monospace;
        }
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }
        .table tr:hover {
            background: #f7fafc;
        }
        .status-success {
            color: #38a169;
            font-weight: 600;
        }
        .status-failed {
            color: #e53e3e;
            font-weight: 600;
        }
        .status-error {
            color: #dd6b20;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .nav-links {
                justify-content: center;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Email System Test & Configuration</h1>
            <div class="nav-links">
                <a href="admin-dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="manage-routes.php"><i class="fas fa-route"></i> Routes</a>
                <a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Test Results -->
        <?php if (!empty($testResults)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-test-tube"></i> Test Results
                </div>
                <div class="card-body">
                    <?php foreach ($testResults as $result): ?>
                        <div class="test-result <?php echo strtolower($result['status']); ?>">
                            <h4><?php echo htmlspecialchars($result['type']); ?></h4>
                            <p><strong>Recipient:</strong> <?php echo htmlspecialchars($result['recipient']); ?></p>
                            <p><strong>Status:</strong> <span class="status-<?php echo strtolower($result['status']); ?>"><?php echo htmlspecialchars($result['status']); ?></span></p>
                            <p><strong>Message:</strong> <?php echo htmlspecialchars($result['message']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Email Configuration -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cog"></i> Email Configuration
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="smtp_username">SMTP Username (Gmail):</label>
                            <input type="email" id="smtp_username" name="smtp_username" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentConfig['smtp_username']); ?>" 
                                   placeholder="your.email@gmail.com">
                        </div>
                        <div class="form-group">
                            <label for="smtp_password">SMTP Password (App Password):</label>
                            <input type="password" id="smtp_password" name="smtp_password" class="form-control" 
                                   value="<?php echo $currentConfig['smtp_password'] !== '***hidden***' ? htmlspecialchars($currentConfig['smtp_password']) : ''; ?>" 
                                   placeholder="Gmail App Password">
                        </div>
                        <div class="form-group">
                            <label for="from_email">From Email:</label>
                            <input type="email" id="from_email" name="from_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentConfig['from_email']); ?>" 
                                   placeholder="noreply@buspass.com">
                        </div>
                        <div class="form-group">
                            <label for="from_name">From Name:</label>
                            <input type="text" id="from_name" name="from_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentConfig['from_name']); ?>" 
                                   placeholder="Bus Pass Management System">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="enable_debug" <?php echo $currentConfig['enable_debug'] ? 'checked' : ''; ?>>
                            Enable Debug Mode (for troubleshooting)
                        </label>
                    </div>
                    
                    <button type="submit" name="update_config" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Configuration
                    </button>
                </form>
            </div>
        </div>

        <!-- Test Email Sending -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-paper-plane"></i> Test Email Sending
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="test_email">Test Email Address:</label>
                            <input type="email" id="test_email" name="test_email" class="form-control" 
                                   placeholder="test@example.com" required>
                        </div>
                        <div class="form-group">
                            <label for="test_type">Email Type:</label>
                            <select id="test_type" name="test_type" class="form-control" required>
                                <option value="basic">Basic Test Email</option>
                                <option value="approval">Approval Email Template</option>
                                <option value="rejection">Rejection Email Template</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="send_test" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Send Test Email
                    </button>
                </form>
            </div>
        </div>

        <!-- Current Configuration Display -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Current Configuration
            </div>
            <div class="card-body">
                <?php foreach ($currentConfig as $key => $value): ?>
                    <div class="config-item">
                        <span class="config-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</span>
                        <span class="config-value"><?php echo htmlspecialchars($value); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Email Logs -->
        <?php if ($emailLogsExists && !empty($recentLogs)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Recent Email Logs
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Recipient</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y g:i A', strtotime($log['sent_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['email']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($log['subject'], 0, 50)) . (strlen($log['subject']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <span class="status-<?php echo strtolower($log['status']); ?>">
                                                <?php echo ucfirst($log['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['method']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Setup Instructions -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-question-circle"></i> Setup Instructions
            </div>
            <div class="card-body">
                <h4>📧 Gmail SMTP Setup:</h4>
                <ol>
                    <li><strong>Enable 2-Factor Authentication</strong> on your Gmail account</li>
                    <li><strong>Generate App Password:</strong>
                        <ul>
                            <li>Go to Google Account settings</li>
                            <li>Security → 2-Step Verification → App passwords</li>
                            <li>Generate password for "Mail"</li>
                            <li>Use this password in the SMTP Password field above</li>
                        </ul>
                    </li>
                    <li><strong>Update Configuration</strong> with your Gmail credentials</li>
                    <li><strong>Test Email</strong> to verify setup</li>
                </ol>
                
                <h4>🔧 Alternative SMTP Providers:</h4>
                <p>You can also use other SMTP providers like SendGrid, Mailgun, or your hosting provider's SMTP service. Just update the configuration accordingly.</p>
                
                <h4>📝 Email Templates:</h4>
                <p>The system includes professional email templates for:</p>
                <ul>
                    <li>✅ Application Approval notifications</li>
                    <li>❌ Application Rejection notifications</li>
                    <li>📋 General status updates</li>
                    <li>🧪 System testing</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
