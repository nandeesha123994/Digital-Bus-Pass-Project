<?php
session_start();
include('includes/dbconnection.php');

$message = '';
$messageType = '';

// Handle database fixes
if (isset($_POST['fix_action'])) {
    $action = $_POST['fix_action'];
    
    try {
        switch ($action) {
            case 'check_columns':
                $message = "📋 Database Column Check Results:\n\n";
                
                // Check bus_pass_applications table
                $result = $con->query("DESCRIBE bus_pass_applications");
                $message .= "✅ bus_pass_applications table columns:\n";
                while ($row = $result->fetch_assoc()) {
                    $message .= "- {$row['Field']} ({$row['Type']})\n";
                }
                
                // Check bus_pass_types table
                $result = $con->query("DESCRIBE bus_pass_types");
                $message .= "\n✅ bus_pass_types table columns:\n";
                while ($row = $result->fetch_assoc()) {
                    $message .= "- {$row['Field']} ({$row['Type']})\n";
                }
                
                // Check payments table
                $result = $con->query("DESCRIBE payments");
                $message .= "\n✅ payments table columns:\n";
                while ($row = $result->fetch_assoc()) {
                    $message .= "- {$row['Field']} ({$row['Type']})\n";
                }
                
                $messageType = "success";
                break;
                
            case 'add_missing_columns':
                $con->begin_transaction();
                
                // Check and add application_id column if missing
                $checkAppId = $con->query("SHOW COLUMNS FROM bus_pass_applications LIKE 'application_id'");
                if ($checkAppId->num_rows == 0) {
                    $con->query("ALTER TABLE bus_pass_applications ADD COLUMN application_id VARCHAR(20) UNIQUE AFTER id");
                    $message .= "✅ Added application_id column to bus_pass_applications\n";
                }
                
                // Check and add valid_from column if missing
                $checkValidFrom = $con->query("SHOW COLUMNS FROM bus_pass_applications LIKE 'valid_from'");
                if ($checkValidFrom->num_rows == 0) {
                    $con->query("ALTER TABLE bus_pass_applications ADD COLUMN valid_from DATE AFTER pass_number");
                    $message .= "✅ Added valid_from column to bus_pass_applications\n";
                }
                
                // Check and add valid_until column if missing
                $checkValidUntil = $con->query("SHOW COLUMNS FROM bus_pass_applications LIKE 'valid_until'");
                if ($checkValidUntil->num_rows == 0) {
                    $con->query("ALTER TABLE bus_pass_applications ADD COLUMN valid_until DATE AFTER valid_from");
                    $message .= "✅ Added valid_until column to bus_pass_applications\n";
                }
                
                // Update existing records with application IDs
                $updateAppIds = "UPDATE bus_pass_applications SET application_id = CONCAT('BPMS', YEAR(NOW()), LPAD(id, 6, '0')) WHERE application_id IS NULL";
                $con->query($updateAppIds);
                $affected = $con->affected_rows;
                if ($affected > 0) {
                    $message .= "✅ Generated application IDs for $affected records\n";
                }
                
                $con->commit();
                $message = $message ?: "✅ All required columns already exist!";
                $messageType = "success";
                break;
                
            case 'fix_sample_data':
                $con->begin_transaction();
                
                // Create sample application if none exist
                $checkApps = $con->query("SELECT COUNT(*) as count FROM bus_pass_applications");
                $appCount = $checkApps->fetch_assoc()['count'];
                
                if ($appCount == 0) {
                    // Create sample user if needed
                    $checkUser = $con->query("SELECT id FROM users LIMIT 1");
                    if ($checkUser->num_rows == 0) {
                        $userQuery = "INSERT INTO users (full_name, email, phone, password, created_at) VALUES ('Test User', 'test@example.com', '1234567890', '" . password_hash('password', PASSWORD_DEFAULT) . "', NOW())";
                        $con->query($userQuery);
                        $userId = $con->insert_id;
                    } else {
                        $userId = $checkUser->fetch_assoc()['id'];
                    }
                    
                    // Create sample pass type if needed
                    $checkPassType = $con->query("SELECT id FROM bus_pass_types LIMIT 1");
                    if ($checkPassType->num_rows == 0) {
                        $passTypeQuery = "INSERT INTO bus_pass_types (type_name, duration_days, created_at) VALUES ('Monthly Pass', 30, NOW())";
                        $con->query($passTypeQuery);
                        $passTypeId = $con->insert_id;
                    } else {
                        $passTypeId = $checkPassType->fetch_assoc()['id'];
                    }
                    
                    // Create sample application
                    $appQuery = "INSERT INTO bus_pass_applications (
                        user_id, pass_type_id, applicant_name, phone, address, 
                        source, destination, amount, status, payment_status, 
                        application_date, application_id, pass_number, valid_from, valid_until
                    ) VALUES (
                        ?, ?, 'Test Applicant', '1234567890', 'Test Address', 
                        'City A', 'City B', 100.00, 'Approved', 'Paid', 
                        NOW(), 'BPMS2024000001', 'BP2024000001', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                    )";
                    $appStmt = $con->prepare($appQuery);
                    $appStmt->bind_param("iis", $userId, $passTypeId, 'Test Applicant');
                    $appStmt->execute();
                    
                    $message .= "✅ Created sample application with all required fields\n";
                }
                
                $con->commit();
                $message = $message ?: "✅ Sample data already exists!";
                $messageType = "success";
                break;
        }
        
    } catch (Exception $e) {
        if (isset($con)) {
            $con->rollback();
        }
        $message = "❌ Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get current database status
$dbStatus = [];
try {
    // Check tables exist
    $tables = ['bus_pass_applications', 'bus_pass_types', 'payments', 'users'];
    foreach ($tables as $table) {
        $result = $con->query("SHOW TABLES LIKE '$table'");
        $dbStatus[$table] = $result->num_rows > 0;
    }
    
    // Check record counts
    foreach ($tables as $table) {
        if ($dbStatus[$table]) {
            $result = $con->query("SELECT COUNT(*) as count FROM $table");
            $dbStatus[$table . '_count'] = $result->fetch_assoc()['count'];
        }
    }
    
} catch (Exception $e) {
    $dbStatus['error'] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Database Columns - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%); min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.5rem; }
        .content { padding: 40px; }
        
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; white-space: pre-line; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        
        .status-section { background: #f8f9fa; padding: 30px; border-radius: 10px; margin: 30px 0; border-left: 4px solid #007bff; }
        .status-section h3 { margin: 0 0 20px 0; color: #007bff; }
        
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .status-card { background: white; padding: 20px; border-radius: 8px; border: 2px solid #ddd; text-align: center; }
        .status-card.good { border-color: #28a745; }
        .status-card.bad { border-color: #dc3545; }
        .status-card h4 { margin: 0 0 10px 0; }
        .status-card .count { font-size: 2rem; font-weight: bold; margin: 10px 0; }
        .status-card .count.good { color: #28a745; }
        .status-card .count.bad { color: #dc3545; }
        
        .fix-button { background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin: 10px; }
        .fix-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3); }
        
        .check-button { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
        .check-button:hover { box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3); }
        
        .sample-button { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .sample-button:hover { box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3); }
        
        .quick-links { display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap; }
        .quick-link { background: #007bff; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .quick-link:hover { background: #0056b3; transform: translateY(-2px); text-decoration: none; color: white; }
        
        .error-info { background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .error-info h4 { margin: 0 0 10px 0; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-database"></i> Fix Database Columns</h1>
            <p>Resolve database column errors and missing fields</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="error-info">
                <h4><i class="fas fa-info-circle"></i> Common Database Column Errors</h4>
                <ul>
                    <li><strong>"Unknown column 'bpt.amount'"</strong> → Amount is stored in bus_pass_applications, not bus_pass_types</li>
                    <li><strong>"Unknown column 'application_id'"</strong> → Missing application_id column in bus_pass_applications</li>
                    <li><strong>"Unknown column 'valid_from/valid_until'"</strong> → Missing validity date columns</li>
                    <li><strong>Print functionality not working</strong> → Missing required columns for pass generation</li>
                </ul>
            </div>
            
            <div class="status-section">
                <h3><i class="fas fa-chart-bar"></i> Database Status</h3>
                
                <div class="status-grid">
                    <?php foreach (['bus_pass_applications', 'bus_pass_types', 'payments', 'users'] as $table): ?>
                    <div class="status-card <?php echo $dbStatus[$table] ? 'good' : 'bad'; ?>">
                        <h4><?php echo ucwords(str_replace('_', ' ', $table)); ?></h4>
                        <div class="count <?php echo $dbStatus[$table] ? 'good' : 'bad'; ?>">
                            <?php echo $dbStatus[$table] ? '✅' : '❌'; ?>
                        </div>
                        <p><?php echo $dbStatus[$table] ? 'Table exists' : 'Table missing'; ?></p>
                        <?php if ($dbStatus[$table] && isset($dbStatus[$table . '_count'])): ?>
                        <p><strong><?php echo $dbStatus[$table . '_count']; ?></strong> records</p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="status-section">
                <h3><i class="fas fa-tools"></i> Database Fix Tools</h3>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="fix_action" value="check_columns">
                    <button type="submit" class="fix-button check-button">
                        <i class="fas fa-search"></i> Check All Columns
                    </button>
                </form>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="fix_action" value="add_missing_columns">
                    <button type="submit" class="fix-button" onclick="return confirm('Add missing columns to database?\n\nThis will:\n- Add application_id column\n- Add valid_from column\n- Add valid_until column\n- Generate application IDs for existing records\n\nContinue?')">
                        <i class="fas fa-plus"></i> Add Missing Columns
                    </button>
                </form>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="fix_action" value="fix_sample_data">
                    <button type="submit" class="fix-button sample-button" onclick="return confirm('Create sample data for testing?\n\nThis will create:\n- Sample user account\n- Sample pass type\n- Sample application with all fields\n\nContinue?')">
                        <i class="fas fa-database"></i> Create Sample Data
                    </button>
                </form>
            </div>
            
            <div class="quick-links">
                <a href="complete-payment-now.php" class="quick-link">
                    <i class="fas fa-bolt"></i> Complete Payment
                </a>
                <a href="fix-payment-and-print.php" class="quick-link">
                    <i class="fas fa-tools"></i> Fix Payment Issues
                </a>
                <a href="user-dashboard.php" class="quick-link">
                    <i class="fas fa-dashboard"></i> User Dashboard
                </a>
                <a href="admin-dashboard.php" class="quick-link">
                    <i class="fas fa-cog"></i> Admin Dashboard
                </a>
            </div>
            
            <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff;">
                <h4><i class="fas fa-lightbulb"></i> How to Fix Column Errors</h4>
                <ol>
                    <li><strong>Check Columns</strong> - See what columns exist in each table</li>
                    <li><strong>Add Missing Columns</strong> - Automatically add required columns</li>
                    <li><strong>Create Sample Data</strong> - Add test data for functionality testing</li>
                    <li><strong>Test Functionality</strong> - Verify payment and print features work</li>
                </ol>
                <p><strong>After running fixes, all database column errors should be resolved!</strong></p>
            </div>
        </div>
    </div>
    
    <script>
        // Add loading states to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    button.disabled = true;
                }
            });
        });
    </script>
</body>
</html>
