<?php
/**
 * Daily Notification Checker
 * Automatically checks for expiring bus passes and sends notifications
 * This script should be run daily via cron job or task scheduler
 */

include('includes/dbconnection.php');
include('includes/email-functions.php'); // We'll create this for email functionality

// Set execution time limit for long-running script
set_time_limit(300); // 5 minutes

// Log file for debugging
$logFile = 'logs/notification-checker.log';
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("=== Daily Notification Checker Started ===");

try {
    $con->begin_transaction();
    
    // Get current date
    $currentDate = new DateTime();
    $currentDateStr = $currentDate->format('Y-m-d');
    
    logMessage("Checking for expiring passes on: $currentDateStr");
    
    // Find all approved bus passes that are expiring within the next 7 days
    $query = "
        SELECT 
            ba.id as application_id,
            ba.user_id,
            ba.applicant_name,
            ba.pass_number,
            ba.source,
            ba.destination,
            ba.valid_until,
            u.email,
            u.full_name,
            ns.email_notifications,
            ns.in_app_notifications,
            ns.expiry_warning_days,
            ns.urgent_warning_days,
            ns.last_email_sent,
            DATEDIFF(ba.valid_until, CURDATE()) as days_until_expiry
        FROM bus_pass_applications ba
        JOIN users u ON ba.user_id = u.id
        LEFT JOIN notification_settings ns ON u.id = ns.user_id
        WHERE ba.status = 'Approved' 
        AND ba.pass_number IS NOT NULL
        AND ba.valid_until IS NOT NULL
        AND DATEDIFF(ba.valid_until, CURDATE()) BETWEEN 0 AND 7
        AND DATEDIFF(ba.valid_until, CURDATE()) >= 0
        ORDER BY ba.valid_until ASC
    ";
    
    $stmt = $con->prepare($query);
    $stmt->execute();
    $expiringPasses = $stmt->get_result();
    
    $totalPasses = $expiringPasses->num_rows;
    logMessage("Found $totalPasses passes expiring within 7 days");
    
    $notificationsSent = 0;
    $emailsSent = 0;
    $errors = 0;
    
    while ($pass = $expiringPasses->fetch_assoc()) {
        try {
            $daysUntilExpiry = $pass['days_until_expiry'];
            $userId = $pass['user_id'];
            $applicationId = $pass['application_id'];
            
            logMessage("Processing pass {$pass['pass_number']} for user {$pass['full_name']} (expires in $daysUntilExpiry days)");
            
            // Determine notification type based on days until expiry
            $notificationType = '';
            $templateName = '';
            $priority = 'normal';
            
            if ($daysUntilExpiry <= 0) {
                $notificationType = 'expired';
                $templateName = 'expiry_urgent_3_days'; // Use urgent template for expired
                $priority = 'high';
            } elseif ($daysUntilExpiry <= 3) {
                $notificationType = 'expiry_urgent';
                $templateName = 'expiry_urgent_3_days';
                $priority = 'high';
            } elseif ($daysUntilExpiry <= 7) {
                $notificationType = 'expiry_warning';
                $templateName = 'expiry_warning_7_days';
                $priority = 'normal';
            } else {
                continue; // Skip if more than 7 days
            }
            
            // Check if we've already sent this type of notification for this pass
            $checkExisting = "
                SELECT id FROM notifications 
                WHERE user_id = ? AND application_id = ? AND type = ? 
                AND DATE(created_at) = CURDATE()
            ";
            $checkStmt = $con->prepare($checkExisting);
            $checkStmt->bind_param("iis", $userId, $applicationId, $notificationType);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                logMessage("Notification already sent today for pass {$pass['pass_number']}");
                continue;
            }
            
            // Create notification title and message
            $title = '';
            $message = '';
            
            switch ($notificationType) {
                case 'expired':
                    $title = '🚨 Your Bus Pass Has Expired!';
                    $message = "Your bus pass ({$pass['pass_number']}) has expired. Please renew immediately to continue using bus services.";
                    break;
                case 'expiry_urgent':
                    $title = "🚨 Bus Pass Expires in $daysUntilExpiry Days!";
                    $message = "URGENT: Your bus pass ({$pass['pass_number']}) expires in $daysUntilExpiry days on " . date('M d, Y', strtotime($pass['valid_until'])) . ". Renew now to avoid travel disruption.";
                    break;
                case 'expiry_warning':
                    $title = "⚠️ Bus Pass Expires in $daysUntilExpiry Days";
                    $message = "Your bus pass ({$pass['pass_number']}) expires on " . date('M d, Y', strtotime($pass['valid_until'])) . ". Consider renewing soon to ensure uninterrupted travel.";
                    break;
            }
            
            // Create in-app notification if enabled
            if ($pass['in_app_notifications'] !== false) { // Default to true if null
                $insertNotification = "
                    INSERT INTO notifications (user_id, application_id, type, title, message, created_at, metadata)
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)
                ";
                
                $metadata = json_encode([
                    'days_until_expiry' => $daysUntilExpiry,
                    'pass_number' => $pass['pass_number'],
                    'route' => $pass['source'] . ' → ' . $pass['destination'],
                    'expiry_date' => $pass['valid_until'],
                    'priority' => $priority
                ]);
                
                $notifStmt = $con->prepare($insertNotification);
                $notifStmt->bind_param("iissss", $userId, $applicationId, $notificationType, $title, $message, $metadata);
                
                if ($notifStmt->execute()) {
                    $notificationsSent++;
                    logMessage("In-app notification created for user $userId");
                } else {
                    logMessage("Failed to create in-app notification: " . $notifStmt->error);
                    $errors++;
                }
            }
            
            // Send email notification if enabled
            if ($pass['email_notifications'] !== false && !empty($pass['email'])) { // Default to true if null
                // Check if we should send email (avoid spam)
                $shouldSendEmail = true;
                if ($pass['last_email_sent']) {
                    $lastEmailDate = new DateTime($pass['last_email_sent']);
                    $daysSinceLastEmail = $currentDate->diff($lastEmailDate)->days;
                    
                    // Don't send email if we sent one in the last 24 hours for the same type
                    if ($daysSinceLastEmail < 1) {
                        $shouldSendEmail = false;
                        logMessage("Skipping email - already sent within 24 hours");
                    }
                }
                
                if ($shouldSendEmail) {
                    $emailSent = sendExpiryNotificationEmail(
                        $pass['email'],
                        $pass['full_name'],
                        $pass['pass_number'],
                        $pass['source'] . ' → ' . $pass['destination'],
                        $pass['valid_until'],
                        $daysUntilExpiry,
                        $templateName
                    );
                    
                    if ($emailSent) {
                        $emailsSent++;
                        
                        // Update last email sent timestamp
                        $updateEmailTime = "UPDATE notification_settings SET last_email_sent = NOW() WHERE user_id = ?";
                        $updateStmt = $con->prepare($updateEmailTime);
                        $updateStmt->bind_param("i", $userId);
                        $updateStmt->execute();
                        
                        logMessage("Email sent successfully to {$pass['email']}");
                    } else {
                        logMessage("Failed to send email to {$pass['email']}");
                        $errors++;
                    }
                }
            }
            
            // Log the notification attempt
            $logNotification = "
                INSERT INTO notification_log (user_id, application_id, notification_type, delivery_method, status, email_address, subject, sent_at, metadata)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ";
            
            $deliveryMethod = 'both';
            $status = 'sent';
            $subject = $title;
            $logMetadata = json_encode([
                'days_until_expiry' => $daysUntilExpiry,
                'notification_type' => $notificationType,
                'email_enabled' => $pass['email_notifications'],
                'in_app_enabled' => $pass['in_app_notifications']
            ]);
            
            $logStmt = $con->prepare($logNotification);
            $logStmt->bind_param("iissssss", $userId, $applicationId, $notificationType, $deliveryMethod, $status, $pass['email'], $subject, $logMetadata);
            $logStmt->execute();
            
        } catch (Exception $e) {
            logMessage("Error processing pass {$pass['pass_number']}: " . $e->getMessage());
            $errors++;
        }
    }
    
    $con->commit();
    
    // Summary
    logMessage("=== Daily Notification Checker Completed ===");
    logMessage("Total passes checked: $totalPasses");
    logMessage("In-app notifications sent: $notificationsSent");
    logMessage("Emails sent: $emailsSent");
    logMessage("Errors encountered: $errors");
    
    // If running via web (for testing), show results
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "<h1>🔔 Daily Notification Checker Results</h1>";
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ Notification Check Completed</h3>";
        echo "<p><strong>Total passes checked:</strong> $totalPasses</p>";
        echo "<p><strong>In-app notifications sent:</strong> $notificationsSent</p>";
        echo "<p><strong>Emails sent:</strong> $emailsSent</p>";
        echo "<p><strong>Errors encountered:</strong> $errors</p>";
        echo "</div>";
        
        if ($errors > 0) {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>⚠️ Some Errors Occurred</h3>";
            echo "<p>Check the log file for details: $logFile</p>";
            echo "</div>";
        }
        
        echo "<p><a href='user-dashboard.php'>View Dashboard</a> | <a href='view-notification-logs.php'>View Logs</a></p>";
    }
    
} catch (Exception $e) {
    $con->rollback();
    logMessage("FATAL ERROR: " . $e->getMessage());
    
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "<h1>❌ Error in Notification Checker</h1>";
        echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    }
}

$con->close();

/**
 * Function to send expiry notification email
 */
function sendExpiryNotificationEmail($email, $userName, $passNumber, $route, $expiryDate, $daysUntilExpiry, $templateName) {
    global $con;
    
    try {
        // Get email template
        $templateQuery = "SELECT subject, html_content, text_content FROM email_templates WHERE template_name = ? AND is_active = TRUE";
        $templateStmt = $con->prepare($templateQuery);
        $templateStmt->bind_param("s", $templateName);
        $templateStmt->execute();
        $template = $templateStmt->get_result()->fetch_assoc();
        
        if (!$template) {
            logMessage("Email template not found: $templateName");
            return false;
        }
        
        // Replace template variables
        $variables = [
            '{{user_name}}' => $userName,
            '{{pass_number}}' => $passNumber,
            '{{expiry_date}}' => date('M d, Y', strtotime($expiryDate)),
            '{{route}}' => $route,
            '{{renewal_link}}' => 'http://localhost/buspassmsfull/user-dashboard.php'
        ];
        
        $subject = str_replace(array_keys($variables), array_values($variables), $template['subject']);
        $htmlContent = str_replace(array_keys($variables), array_values($variables), $template['html_content']);
        $textContent = str_replace(array_keys($variables), array_values($variables), $template['text_content']);
        
        // For now, we'll simulate email sending (replace with actual email function)
        // In production, integrate with PHPMailer, SendGrid, or similar service
        
        logMessage("EMAIL SIMULATION - To: $email, Subject: $subject");
        
        // Simulate successful email sending
        return true;
        
    } catch (Exception $e) {
        logMessage("Email sending error: " . $e->getMessage());
        return false;
    }
}
?>
