<?php
session_start();
include('includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle table creation
if (isset($_POST['create_table'])) {
    try {
        // Create admin_actions table
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS admin_actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id VARCHAR(100) NOT NULL,
            admin_name VARCHAR(255) NOT NULL,
            application_id INT NOT NULL,
            applicant_name VARCHAR(255) NOT NULL,
            action VARCHAR(100) NOT NULL,
            old_status VARCHAR(50),
            new_status VARCHAR(50),
            remarks TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            INDEX idx_admin_id (admin_id),
            INDEX idx_application_id (application_id),
            INDEX idx_timestamp (timestamp),
            INDEX idx_action (action)
        )";
        
        if ($con->query($createTableSQL)) {
            $message = "✅ Admin actions table created successfully!";
            $messageType = "success";
            
            // Insert sample data
            $sampleDataSQL = "
            INSERT INTO admin_actions (admin_id, admin_name, application_id, applicant_name, action, old_status, new_status, remarks, timestamp, ip_address) VALUES
            ('admin', 'System Administrator', 1, 'John Doe', 'Status Update', 'Pending', 'Approved', 'Application meets all requirements', '2024-12-15 10:30:00', '127.0.0.1'),
            ('admin', 'System Administrator', 2, 'Jane Smith', 'Status Update', 'Pending', 'Rejected', 'ID proof document is not clear', '2024-12-15 11:15:00', '127.0.0.1'),
            ('admin', 'System Administrator', 3, 'Bob Johnson', 'Bulk Action: Approve', 'Pending', 'Approved', 'Bulk approved by admin', '2024-12-15 14:20:00', '127.0.0.1'),
            ('admin', 'System Administrator', 4, 'Alice Brown', 'Bulk Action: Approve', 'Pending', 'Approved', 'Bulk approved by admin', '2024-12-15 14:20:00', '127.0.0.1'),
            ('admin', 'System Administrator', 5, 'Charlie Wilson', 'Status Update', 'Approved', 'Rejected', 'Payment verification failed', '2024-12-15 16:45:00', '127.0.0.1')
            ";
            
            if ($con->query($sampleDataSQL)) {
                $message .= " Sample data inserted successfully!";
            } else {
                $message .= " Table created but sample data insertion failed: " . $con->error;
            }
        } else {
            $message = "❌ Error creating table: " . $con->error;
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "❌ Exception: " . $e->getMessage();
        $messageType = "error";
    }
}

// Check if table exists
$tableExists = false;
$result = $con->query("SHOW TABLES LIKE 'admin_actions'");
if ($result && $result->num_rows > 0) {
    $tableExists = true;
    
    // Get table info
    $countResult = $con->query("SELECT COUNT(*) as count FROM admin_actions");
    $recordCount = $countResult ? $countResult->fetch_assoc()['count'] : 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Activity Log - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .status-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
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
            text-decoration: none;
            display: inline-block;
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
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
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
        .table-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #28a745;
        }
        .info-number {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 5px;
        }
        .info-label {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-database"></i> Activity Log Setup</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="status-section">
            <h3><i class="fas fa-info-circle"></i> Database Status</h3>
            
            <?php if ($tableExists): ?>
                <div class="info-box" style="background: #d4edda; color: #155724; border-left-color: #28a745;">
                    <strong>✅ Admin Actions Table Status:</strong> Table exists and is ready to use!
                </div>
                
                <div class="table-info">
                    <div class="info-card">
                        <div class="info-number"><?php echo $recordCount; ?></div>
                        <div class="info-label">Total Records</div>
                    </div>
                    <div class="info-card">
                        <div class="info-number">✅</div>
                        <div class="info-label">Table Status</div>
                    </div>
                    <div class="info-card">
                        <div class="info-number">4</div>
                        <div class="info-label">Indexes</div>
                    </div>
                </div>
                
                <p><strong>Table Structure:</strong></p>
                <ul>
                    <li><strong>id</strong> - Auto-increment primary key</li>
                    <li><strong>admin_id</strong> - Admin identifier</li>
                    <li><strong>admin_name</strong> - Admin display name</li>
                    <li><strong>application_id</strong> - Related application ID</li>
                    <li><strong>applicant_name</strong> - Applicant name for quick reference</li>
                    <li><strong>action</strong> - Type of action performed</li>
                    <li><strong>old_status</strong> - Previous status (if applicable)</li>
                    <li><strong>new_status</strong> - New status (if applicable)</li>
                    <li><strong>remarks</strong> - Admin remarks/comments</li>
                    <li><strong>timestamp</strong> - When the action occurred</li>
                    <li><strong>ip_address</strong> - Admin's IP address</li>
                    <li><strong>user_agent</strong> - Browser information</li>
                </ul>
                
                <div style="margin-top: 20px;">
                    <a href="admin-activity-log.php" class="btn btn-success">
                        <i class="fas fa-history"></i> View Activity Log
                    </a>
                    <a href="admin-dashboard.php" class="btn">
                        <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                    </a>
                </div>
                
            <?php else: ?>
                <div class="info-box" style="background: #fff3cd; color: #856404; border-left-color: #ffc107;">
                    <strong>⚠️ Admin Actions Table Status:</strong> Table does not exist. Click the button below to create it.
                </div>
                
                <p><strong>What will be created:</strong></p>
                <ul>
                    <li>Admin actions table with proper structure and indexes</li>
                    <li>Sample data for testing the activity log functionality</li>
                    <li>All necessary fields for comprehensive audit logging</li>
                </ul>
                
                <form method="POST">
                    <button type="submit" name="create_table" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Admin Actions Table
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="status-section">
            <h3><i class="fas fa-cogs"></i> Activity Log Features</h3>
            <p>Once the table is created, the Activity Log will provide:</p>
            <ul>
                <li><strong>Complete Audit Trail:</strong> Track all admin actions with timestamps</li>
                <li><strong>Status Change Logging:</strong> Record old and new status for applications</li>
                <li><strong>Admin Identification:</strong> Know which admin performed each action</li>
                <li><strong>Detailed Remarks:</strong> Store admin comments and reasons</li>
                <li><strong>IP Tracking:</strong> Security logging with IP addresses</li>
                <li><strong>Filtering & Search:</strong> Advanced filtering by admin, date, action type</li>
                <li><strong>Statistics:</strong> Activity statistics and trends</li>
                <li><strong>Bulk Action Tracking:</strong> Log bulk approvals and rejections</li>
            </ul>
        </div>

        <div class="back-link">
            <a href="admin-dashboard.php">← Back to Admin Dashboard</a> |
            <a href="index.php">Home</a>
            <?php if ($tableExists): ?>
                | <a href="admin-activity-log.php">View Activity Log →</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
