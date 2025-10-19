<?php
session_start();
include('includes/dbconnection.php');

echo "<h2>Quick Rewards System Setup</h2>";
echo "<p>Setting up the rewards system database...</p>";

try {
    $con->begin_transaction();
    
    // 1. Add reward_points column to users table
    echo "<p>1. Adding reward_points column to users table...</p>";
    $checkColumn = $con->query("SHOW COLUMNS FROM users LIKE 'reward_points'");
    if ($checkColumn->num_rows == 0) {
        $con->query("ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0 AFTER phone");
        echo "<p style='color: green;'>✅ Added reward_points column to users table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ reward_points column already exists</p>";
    }
    
    // 2. Create rewards_rules table
    echo "<p>2. Creating rewards_rules table...</p>";
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
    echo "<p style='color: green;'>✅ Created rewards_rules table</p>";
    
    // 3. Create rewards_transactions table
    echo "<p>3. Creating rewards_transactions table...</p>";
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
    echo "<p style='color: green;'>✅ Created rewards_transactions table</p>";
    
    // 4. Create rewards_redemptions table
    echo "<p>4. Creating rewards_redemptions table...</p>";
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
    echo "<p style='color: green;'>✅ Created rewards_redemptions table</p>";
    
    // 5. Insert default reward rules
    echo "<p>5. Inserting default reward rules...</p>";
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
            echo "<p style='color: green;'>✅ Added rule: {$rule[0]} ({$rule[1]} points)</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Rule already exists: {$rule[0]}</p>";
        }
    }
    
    // 6. Update existing users with initial points
    echo "<p>6. Updating existing users with initial reward points...</p>";
    $con->query("UPDATE users SET reward_points = 0 WHERE reward_points IS NULL");
    echo "<p style='color: green;'>✅ Updated existing users with initial reward points</p>";
    
    $con->commit();
    
    echo "<h3 style='color: green;'>🎉 Rewards System Setup Completed Successfully!</h3>";
    echo "<p><strong>What was created:</strong></p>";
    echo "<ul>";
    echo "<li>✅ reward_points column in users table</li>";
    echo "<li>✅ rewards_rules table with 6 default rules</li>";
    echo "<li>✅ rewards_transactions table for tracking point activities</li>";
    echo "<li>✅ rewards_redemptions table for discount redemptions</li>";
    echo "<li>✅ Default reward rules configured and active</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='my-rewards.php'>Test My Rewards Page</a></li>";
    echo "<li><a href='manage-rewards.php'>Access Admin Rewards Management</a></li>";
    echo "<li><a href='user-dashboard.php'>Check User Dashboard Integration</a></li>";
    echo "<li><a href='apply-pass.php'>Apply for Pass to Earn Points</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    $con->rollback();
    echo "<h3 style='color: red;'>❌ Error Setting Up Rewards System</h3>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quick Rewards Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2 { color: #333; }
        p { margin: 10px 0; }
        ul { margin: 10px 0; padding-left: 20px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
        <h4>Database Setup Complete!</h4>
        <p>The rewards system database structure has been created and is ready to use.</p>
        <p><strong>You can now:</strong></p>
        <ul>
            <li>Visit the <a href="my-rewards.php">My Rewards</a> page</li>
            <li>Access <a href="manage-rewards.php">Admin Rewards Management</a></li>
            <li>Apply for a bus pass to automatically earn points</li>
            <li>Test the complete rewards functionality</li>
        </ul>
    </div>
</body>
</html>
