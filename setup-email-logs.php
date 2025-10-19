<?php
/**
 * Setup Email Logs Table
 * Creates the email_logs table for tracking email activity
 */

include('includes/dbconnection.php');

echo "<h1>📧 Email Logs Table Setup</h1>";

try {
    // Check if table already exists
    $checkTable = "SHOW TABLES LIKE 'email_logs'";
    $result = $con->query($checkTable);
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Email logs table already exists. Checking structure...</p>";
        
        // Check table structure
        $describeQuery = "DESCRIBE email_logs";
        $describeResult = $con->query($describeQuery);
        
        echo "<h3>📊 Current Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $describeResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check data count
        $countQuery = "SELECT COUNT(*) as count FROM email_logs";
        $countResult = $con->query($countQuery);
        $count = $countResult->fetch_assoc()['count'];
        
        echo "<p style='color: blue;'>ℹ️ Table contains $count email log records.</p>";
        
    } else {
        echo "<p>📝 Creating email_logs table...</p>";
        
        // Create email_logs table
        $createTableSQL = "
        CREATE TABLE email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            message TEXT DEFAULT NULL,
            status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
            method VARCHAR(100) DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_sent_at (sent_at),
            INDEX idx_user_email (user_id, email),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        
        if ($con->query($createTableSQL)) {
            echo "<p style='color: green;'>✅ Email logs table created successfully!</p>";
        } else {
            throw new Exception("Error creating email_logs table: " . $con->error);
        }
        
        // Add some sample log entries
        echo "<p>📝 Adding sample email log entries...</p>";
        
        $sampleLogs = [
            ['admin@example.com', 'Test Email from Bus Pass System', 'sent', 'PHPMailer'],
            ['user@example.com', 'Bus Pass Application Approved - Application #123', 'sent', 'PHPMailer'],
            ['user2@example.com', 'Bus Pass Application Update - Application #124', 'sent', 'Built-in mail()'],
            ['test@example.com', 'Test Email Configuration', 'failed', 'PHPMailer - SMTP Error']
        ];
        
        $insertSQL = "INSERT INTO email_logs (email, subject, status, method) VALUES (?, ?, ?, ?)";
        $stmt = $con->prepare($insertSQL);
        
        $insertedCount = 0;
        foreach ($sampleLogs as $log) {
            $stmt->bind_param('ssss', $log[0], $log[1], $log[2], $log[3]);
            if ($stmt->execute()) {
                $insertedCount++;
            }
        }
        
        echo "<p style='color: green;'>✅ Inserted $insertedCount sample email log entries!</p>";
    }
    
    // Show recent email logs
    echo "<h3>📋 Recent Email Logs:</h3>";
    $recentLogsQuery = "SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 10";
    $recentLogsResult = $con->query($recentLogsQuery);
    
    if ($recentLogsResult && $recentLogsResult->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Email</th><th>Subject</th><th>Status</th><th>Method</th><th>Date</th>";
        echo "</tr>";
        
        while ($log = $recentLogsResult->fetch_assoc()) {
            $statusColor = $log['status'] === 'sent' ? 'green' : ($log['status'] === 'failed' ? 'red' : 'orange');
            echo "<tr>";
            echo "<td>" . $log['id'] . "</td>";
            echo "<td>" . htmlspecialchars($log['email']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($log['subject'], 0, 50)) . (strlen($log['subject']) > 50 ? '...' : '') . "</td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>" . ucfirst($log['status']) . "</td>";
            echo "<td>" . htmlspecialchars($log['method']) . "</td>";
            echo "<td>" . date('M j, Y g:i A', strtotime($log['sent_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: #666;'>No email logs found.</p>";
    }
    
    // Email statistics
    echo "<h3>📊 Email Statistics:</h3>";
    $statsQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                   FROM email_logs";
    $statsResult = $con->query($statsQuery);
    $stats = $statsResult->fetch_assoc();
    
    echo "<div style='display: flex; gap: 20px; margin: 20px 0;'>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; text-align: center;'>";
    echo "<h4 style='margin: 0; color: #2d5a2d;'>Total Emails</h4>";
    echo "<p style='font-size: 24px; font-weight: bold; margin: 5px 0; color: #2d5a2d;'>" . $stats['total'] . "</p>";
    echo "</div>";
    echo "<div style='background: #e8f8e8; padding: 15px; border-radius: 5px; text-align: center;'>";
    echo "<h4 style='margin: 0; color: #1a5a1a;'>Sent Successfully</h4>";
    echo "<p style='font-size: 24px; font-weight: bold; margin: 5px 0; color: #1a5a1a;'>" . $stats['sent'] . "</p>";
    echo "</div>";
    echo "<div style='background: #ffe8e8; padding: 15px; border-radius: 5px; text-align: center;'>";
    echo "<h4 style='margin: 0; color: #5a1a1a;'>Failed</h4>";
    echo "<p style='font-size: 24px; font-weight: bold; margin: 5px 0; color: #5a1a1a;'>" . $stats['failed'] . "</p>";
    echo "</div>";
    echo "<div style='background: #fff8e8; padding: 15px; border-radius: 5px; text-align: center;'>";
    echo "<h4 style='margin: 0; color: #5a4a1a;'>Pending</h4>";
    echo "<p style='font-size: 24px; font-weight: bold; margin: 5px 0; color: #5a4a1a;'>" . $stats['pending'] . "</p>";
    echo "</div>";
    echo "</div>";
    
    // Success message
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎉 Email Logs System Setup Complete!</h3>";
    echo "<p><strong>Features Available:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Email logs table created with proper structure</li>";
    echo "<li>✅ Automatic logging of all email activity</li>";
    echo "<li>✅ Email statistics and reporting</li>";
    echo "<li>✅ Failed email tracking for troubleshooting</li>";
    echo "<li>✅ Integration with MailHelper class</li>";
    echo "</ul>";
    echo "<p><strong>Access Points:</strong></p>";
    echo "<ul>";
    echo "<li><a href='admin-email-test.php' style='color: #155724; font-weight: bold;'>🔗 Email Test & Configuration</a></li>";
    echo "<li><a href='admin-dashboard.php' style='color: #155724; font-weight: bold;'>🔗 Admin Dashboard</a></li>";
    echo "<li><a href='view-email-logs.php' style='color: #155724; font-weight: bold;'>🔗 View All Email Logs</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Error Setting Up Email Logs</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Please check:</strong></p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL service is running</li>";
    echo "<li>Database 'bpmsdb' exists</li>";
    echo "<li>Users table exists (required for foreign key)</li>";
    echo "<li>Database connection settings in includes/dbconnection.php</li>";
    echo "</ul>";
    echo "</div>";
}

$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Logs Setup - Bus Pass Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            line-height: 1.6;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        h3 {
            color: #34495e;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin-top: 30px;">
        <p><a href="admin-email-test.php" style="background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">📧 Email Test & Configuration</a></p>
        <p><a href="admin-dashboard.php" style="background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">📊 Admin Dashboard</a></p>
    </div>
</body>
</html>
