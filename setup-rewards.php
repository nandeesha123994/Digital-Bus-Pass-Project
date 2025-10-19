<?php
session_start();
include('includes/dbconnection.php');

$message = '';
$messageType = '';

// Handle database setup
if (isset($_POST['setup_rewards'])) {
    try {
        $con->begin_transaction();
        
        // 1. Add reward_points and pass_count columns to users table
        $checkRewardPoints = $con->query("SHOW COLUMNS FROM users LIKE 'reward_points'");
        if ($checkRewardPoints->num_rows == 0) {
            $con->query("ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0 AFTER phone");
            $message .= "✅ Added reward_points column to users table\n";
        }
        
        $checkPassCount = $con->query("SHOW COLUMNS FROM users LIKE 'pass_count'");
        if ($checkPassCount->num_rows == 0) {
            $con->query("ALTER TABLE users ADD COLUMN pass_count INT DEFAULT 0 AFTER reward_points");
            $message .= "✅ Added pass_count column to users table\n";
        }
        
        // 2. Create rewards_rules table
        $createRulesTable = "CREATE TABLE IF NOT EXISTS rewards_rules (
            id INT PRIMARY KEY AUTO_INCREMENT,
            pass_type VARCHAR(50) NOT NULL UNIQUE,
            points_awarded INT NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $con->query($createRulesTable);
        $message .= "✅ Created rewards_rules table\n";
        
        // 3. Create rewards_transactions table
        $createTransactionsTable = "CREATE TABLE IF NOT EXISTS rewards_transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            pass_type VARCHAR(50) NOT NULL,
            points_earned INT NOT NULL,
            application_id INT NULL,
            description TEXT,
            transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (application_id) REFERENCES bus_pass_applications(id) ON DELETE SET NULL
        )";
        $con->query($createTransactionsTable);
        $message .= "✅ Created rewards_transactions table\n";
        
        // 4. Insert default reward rules
        $insertRules = "INSERT IGNORE INTO rewards_rules (pass_type, points_awarded, description) VALUES 
            ('Daily Pass', 1, 'Points awarded for daily pass'),
            ('Monthly Pass', 35, 'Points awarded for monthly pass'),
            ('Quarterly Pass', 150, 'Points awarded for quarterly pass'),
            ('Annual Pass', 350, 'Points awarded for annual pass')";
        $con->query($insertRules);
        $message .= "✅ Inserted default reward rules\n";
        
        // Add missing columns to bus_pass_applications table
        $alterQueries = [
            "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS dob DATE AFTER applicant_name",
            "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS gender VARCHAR(10) AFTER dob",
            "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS phone VARCHAR(15) AFTER gender",
            "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS address TEXT AFTER phone",
            "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS source VARCHAR(100) AFTER address",
            "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS destination VARCHAR(100) AFTER source",
            "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) AFTER destination",
            "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS id_proof_type VARCHAR(50) AFTER photo_path",
            "ALTER TABLE bus_pass_applications ADD COLUMN IF NOT EXISTS id_proof_number VARCHAR(50) AFTER id_proof_type"
        ];

        foreach ($alterQueries as $query) {
            try {
                $con->query($query);
            } catch (Exception $e) {
                // Log error but continue with other queries
                error_log("Error executing query: " . $query . " - " . $e->getMessage());
            }
        }
        
        $con->commit();
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error setting up rewards system: " . $e->getMessage();
        $messageType = "error";
    }
}

// Check current status
$hasRewardPoints = false;
$hasPassCount = false;
$hasRulesTable = false;
$hasTransactionsTable = false;

$columns = $con->query("SHOW COLUMNS FROM users");
while ($column = $columns->fetch_assoc()) {
    if ($column['Field'] === 'reward_points') $hasRewardPoints = true;
    if ($column['Field'] === 'pass_count') $hasPassCount = true;
}

$tables = $con->query("SHOW TABLES");
while ($table = $tables->fetch_array()) {
    if ($table[0] === 'rewards_rules') $hasRulesTable = true;
    if ($table[0] === 'rewards_transactions') $hasTransactionsTable = true;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Rewards System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status-item {
            margin: 5px 0;
        }
        .status-item.success {
            color: #155724;
        }
        .status-item.error {
            color: #721c24;
        }
        .setup-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .setup-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Setup Rewards System</h1>
    
    <?php if ($message): ?>
        <div class="<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
            <?php echo nl2br(htmlspecialchars($message)); ?>
        </div>
    <?php endif; ?>
    
    <div class="status">
        <h2>Current Status:</h2>
        <div class="status-item <?php echo $hasRewardPoints ? 'success' : 'error'; ?>">
            <?php echo $hasRewardPoints ? '✅' : '❌'; ?> reward_points column in users table
        </div>
        <div class="status-item <?php echo $hasPassCount ? 'success' : 'error'; ?>">
            <?php echo $hasPassCount ? '✅' : '❌'; ?> pass_count column in users table
        </div>
        <div class="status-item <?php echo $hasRulesTable ? 'success' : 'error'; ?>">
            <?php echo $hasRulesTable ? '✅' : '❌'; ?> rewards_rules table
        </div>
        <div class="status-item <?php echo $hasTransactionsTable ? 'success' : 'error'; ?>">
            <?php echo $hasTransactionsTable ? '✅' : '❌'; ?> rewards_transactions table
        </div>
    </div>
    
    <?php if (!$hasRewardPoints || !$hasPassCount || !$hasRulesTable || !$hasTransactionsTable): ?>
        <form method="POST">
            <button type="submit" name="setup_rewards" class="setup-button">
                Setup Rewards System
            </button>
        </form>
    <?php else: ?>
        <div class="success">
            ✅ Rewards system is fully set up and ready to use!
        </div>
    <?php endif; ?>
</body>
</html> 