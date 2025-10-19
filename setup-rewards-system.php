<?php
session_start();
include('includes/dbconnection.php');

$message = '';
$messageType = '';

// Handle database setup
if (isset($_POST['setup_rewards'])) {
    try {
        $con->begin_transaction();
        
        // 1. Add reward_points column to users table
        $checkColumn = $con->query("SHOW COLUMNS FROM users LIKE 'reward_points'");
        if ($checkColumn->num_rows == 0) {
            $con->query("ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0 AFTER phone");
            $message .= "✅ Added reward_points column to users table\n";
        }
        
        // 2. Create rewards_rules table
        $createRulesTable = "CREATE TABLE IF NOT EXISTS rewards_rules (
            id INT PRIMARY KEY AUTO_INCREMENT,
            action_type VARCHAR(50) NOT NULL UNIQUE,
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
            action_type VARCHAR(50) NOT NULL,
            points_earned INT NOT NULL,
            points_redeemed INT DEFAULT 0,
            reference_id INT NULL,
            description TEXT,
            transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $con->query($createTransactionsTable);
        $message .= "✅ Created rewards_transactions table\n";
        
        // 4. Create rewards_redemptions table
        $createRedemptionsTable = "CREATE TABLE IF NOT EXISTS rewards_redemptions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            redemption_type VARCHAR(50) NOT NULL,
            points_used INT NOT NULL,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            application_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            admin_remarks TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (application_id) REFERENCES bus_pass_applications(id) ON DELETE SET NULL
        )";
        $con->query($createRedemptionsTable);
        $message .= "✅ Created rewards_redemptions table\n";
        
        // 5. Insert default reward rules
        $defaultRules = [
            ['pass_application', 50, 'Points earned for applying for a new bus pass'],
            ['pass_renewal', 30, 'Points earned for renewing an existing bus pass'],
            ['referral_signup', 100, 'Points earned when a referred user signs up'],
            ['referral_first_pass', 150, 'Points earned when a referred user applies for their first pass'],
            ['payment_completion', 25, 'Points earned for completing payment on time'],
            ['profile_completion', 20, 'Points earned for completing profile information']
        ];
        
        foreach ($defaultRules as $rule) {
            $checkRule = $con->prepare("SELECT id FROM rewards_rules WHERE action_type = ?");
            $checkRule->bind_param("s", $rule[0]);
            $checkRule->execute();
            
            if ($checkRule->get_result()->num_rows == 0) {
                $insertRule = $con->prepare("INSERT INTO rewards_rules (action_type, points_awarded, description) VALUES (?, ?, ?)");
                $insertRule->bind_param("sis", $rule[0], $rule[1], $rule[2]);
                $insertRule->execute();
            }
        }
        $message .= "✅ Inserted default reward rules\n";
        
        // 6. Update existing users with initial points
        $con->query("UPDATE users SET reward_points = 0 WHERE reward_points IS NULL");
        $message .= "✅ Updated existing users with initial reward points\n";
        
        $con->commit();
        $message .= "\n🎉 Rewards System setup completed successfully!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error setting up rewards system: " . $e->getMessage();
        $messageType = "error";
    }
}

// Check current setup status
$setupStatus = [];
try {
    // Check if reward_points column exists
    $checkColumn = $con->query("SHOW COLUMNS FROM users LIKE 'reward_points'");
    $setupStatus['users_column'] = $checkColumn->num_rows > 0;
    
    // Check if tables exist
    $tables = ['rewards_rules', 'rewards_transactions', 'rewards_redemptions'];
    foreach ($tables as $table) {
        $checkTable = $con->query("SHOW TABLES LIKE '$table'");
        $setupStatus[$table] = $checkTable->num_rows > 0;
    }
    
    // Check if rules exist
    if ($setupStatus['rewards_rules']) {
        $rulesCount = $con->query("SELECT COUNT(*) as count FROM rewards_rules")->fetch_assoc()['count'];
        $setupStatus['rules_count'] = $rulesCount;
    }
    
} catch (Exception $e) {
    $setupStatus['error'] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Rewards System - Nrupatunga Digital Bus Pass System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; padding: 30px; text-align: center; }
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
        .status-card .icon { font-size: 2rem; margin: 10px 0; }
        .status-card .icon.good { color: #28a745; }
        .status-card .icon.bad { color: #dc3545; }
        
        .setup-button { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin: 20px 0; }
        .setup-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3); }
        
        .features-info { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .features-info h4 { margin: 0 0 15px 0; color: #007bff; }
        
        .quick-links { display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap; }
        .quick-link { background: #007bff; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .quick-link:hover { background: #0056b3; transform: translateY(-2px); text-decoration: none; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-gift"></i> Setup Rewards System</h1>
            <p>Initialize the comprehensive rewards and points system</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="features-info">
                <h4><i class="fas fa-star"></i> Rewards System Features</h4>
                <ul>
                    <li><strong>User Reward Points</strong> - Track points for each user</li>
                    <li><strong>Automatic Point Crediting</strong> - Points awarded for actions like applications, renewals, referrals</li>
                    <li><strong>My Rewards Dashboard</strong> - User section to view points and redemption options</li>
                    <li><strong>Admin Rewards Management</strong> - Configure reward rules and policies</li>
                    <li><strong>Point Redemption</strong> - Users can redeem points for discounts</li>
                    <li><strong>Transaction History</strong> - Complete audit trail of all point activities</li>
                </ul>
            </div>
            
            <div class="status-section">
                <h3><i class="fas fa-chart-bar"></i> Current Setup Status</h3>
                
                <div class="status-grid">
                    <div class="status-card <?php echo $setupStatus['users_column'] ? 'good' : 'bad'; ?>">
                        <h4>User Points Column</h4>
                        <div class="icon <?php echo $setupStatus['users_column'] ? 'good' : 'bad'; ?>">
                            <i class="fas fa-<?php echo $setupStatus['users_column'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <p><?php echo $setupStatus['users_column'] ? 'Added' : 'Missing'; ?></p>
                    </div>
                    
                    <div class="status-card <?php echo $setupStatus['rewards_rules'] ? 'good' : 'bad'; ?>">
                        <h4>Rewards Rules</h4>
                        <div class="icon <?php echo $setupStatus['rewards_rules'] ? 'good' : 'bad'; ?>">
                            <i class="fas fa-<?php echo $setupStatus['rewards_rules'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <p><?php echo $setupStatus['rewards_rules'] ? 'Created' : 'Missing'; ?></p>
                        <?php if (isset($setupStatus['rules_count'])): ?>
                        <small><?php echo $setupStatus['rules_count']; ?> rules</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="status-card <?php echo $setupStatus['rewards_transactions'] ? 'good' : 'bad'; ?>">
                        <h4>Transactions Table</h4>
                        <div class="icon <?php echo $setupStatus['rewards_transactions'] ? 'good' : 'bad'; ?>">
                            <i class="fas fa-<?php echo $setupStatus['rewards_transactions'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <p><?php echo $setupStatus['rewards_transactions'] ? 'Created' : 'Missing'; ?></p>
                    </div>
                    
                    <div class="status-card <?php echo $setupStatus['rewards_redemptions'] ? 'good' : 'bad'; ?>">
                        <h4>Redemptions Table</h4>
                        <div class="icon <?php echo $setupStatus['rewards_redemptions'] ? 'good' : 'bad'; ?>">
                            <i class="fas fa-<?php echo $setupStatus['rewards_redemptions'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <p><?php echo $setupStatus['rewards_redemptions'] ? 'Created' : 'Missing'; ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (!$setupStatus['users_column'] || !$setupStatus['rewards_rules'] || !$setupStatus['rewards_transactions'] || !$setupStatus['rewards_redemptions']): ?>
            <div style="text-align: center; margin: 30px 0;">
                <form method="POST">
                    <button type="submit" name="setup_rewards" class="setup-button" onclick="return confirm('Setup the complete rewards system?\n\nThis will:\n- Add reward_points column to users\n- Create rewards tables\n- Insert default reward rules\n- Initialize existing users\n\nContinue?')">
                        <i class="fas fa-rocket"></i> Setup Rewards System
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div style="text-align: center; margin: 30px 0; padding: 20px; background: #d4edda; border-radius: 8px; color: #155724;">
                <h3><i class="fas fa-check-circle"></i> Rewards System Ready!</h3>
                <p>All components are set up and ready to use.</p>
            </div>
            <?php endif; ?>
            
            <div class="quick-links">
                <a href="user-dashboard.php" class="quick-link">
                    <i class="fas fa-user"></i> User Dashboard
                </a>
                <a href="admin-dashboard.php" class="quick-link">
                    <i class="fas fa-cog"></i> Admin Dashboard
                </a>
                <a href="manage-rewards.php" class="quick-link">
                    <i class="fas fa-gift"></i> Manage Rewards
                </a>
                <a href="index.php" class="quick-link">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Add loading states to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Setting up...';
                    button.disabled = true;
                }
            });
        });
    </script>
</body>
</html>
