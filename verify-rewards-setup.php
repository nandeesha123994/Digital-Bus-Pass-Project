<?php
session_start();
include('includes/dbconnection.php');

echo "<h2>🔍 Rewards System Verification</h2>";
echo "<p>Checking if all rewards system components are properly set up...</p>";

$allGood = true;

try {
    // Check if reward_points column exists in users table
    echo "<h3>1. Users Table - reward_points Column</h3>";
    $checkColumn = $con->query("SHOW COLUMNS FROM users LIKE 'reward_points'");
    if ($checkColumn->num_rows > 0) {
        echo "<p style='color: green;'>✅ reward_points column exists in users table</p>";
        
        // Check if users have been initialized
        $userCount = $con->query("SELECT COUNT(*) as count FROM users WHERE reward_points IS NOT NULL")->fetch_assoc()['count'];
        echo "<p style='color: green;'>✅ $userCount users have reward_points initialized</p>";
    } else {
        echo "<p style='color: red;'>❌ reward_points column missing from users table</p>";
        $allGood = false;
    }
    
    // Check rewards_rules table
    echo "<h3>2. Rewards Rules Table</h3>";
    $checkTable = $con->query("SHOW TABLES LIKE 'rewards_rules'");
    if ($checkTable->num_rows > 0) {
        echo "<p style='color: green;'>✅ rewards_rules table exists</p>";
        
        $rulesCount = $con->query("SELECT COUNT(*) as count FROM rewards_rules")->fetch_assoc()['count'];
        echo "<p style='color: green;'>✅ $rulesCount reward rules configured</p>";
        
        // Show active rules
        $activeRules = $con->query("SELECT action_type, points_awarded FROM rewards_rules WHERE is_active = 1");
        echo "<p><strong>Active Rules:</strong></p>";
        echo "<ul>";
        while ($rule = $activeRules->fetch_assoc()) {
            echo "<li>{$rule['action_type']}: {$rule['points_awarded']} points</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ rewards_rules table missing</p>";
        $allGood = false;
    }
    
    // Check rewards_transactions table
    echo "<h3>3. Rewards Transactions Table</h3>";
    $checkTable = $con->query("SHOW TABLES LIKE 'rewards_transactions'");
    if ($checkTable->num_rows > 0) {
        echo "<p style='color: green;'>✅ rewards_transactions table exists</p>";
        
        $transactionCount = $con->query("SELECT COUNT(*) as count FROM rewards_transactions")->fetch_assoc()['count'];
        echo "<p style='color: green;'>✅ $transactionCount transactions recorded</p>";
    } else {
        echo "<p style='color: red;'>❌ rewards_transactions table missing</p>";
        $allGood = false;
    }
    
    // Check rewards_redemptions table
    echo "<h3>4. Rewards Redemptions Table</h3>";
    $checkTable = $con->query("SHOW TABLES LIKE 'rewards_redemptions'");
    if ($checkTable->num_rows > 0) {
        echo "<p style='color: green;'>✅ rewards_redemptions table exists</p>";
        
        $redemptionCount = $con->query("SELECT COUNT(*) as count FROM rewards_redemptions")->fetch_assoc()['count'];
        echo "<p style='color: green;'>✅ $redemptionCount redemptions processed</p>";
    } else {
        echo "<p style='color: red;'>❌ rewards_redemptions table missing</p>";
        $allGood = false;
    }
    
    // Test rewards system functionality
    echo "<h3>5. Rewards System Functionality Test</h3>";
    if (file_exists('includes/rewards.php')) {
        echo "<p style='color: green;'>✅ rewards.php file exists</p>";
        
        try {
            include_once('includes/rewards.php');
            $rewards = new RewardsSystem($con);
            echo "<p style='color: green;'>✅ RewardsSystem class loaded successfully</p>";
            
            // Test getting reward rules
            $rules = $rewards->getRewardRules();
            if ($rules && $rules->num_rows > 0) {
                echo "<p style='color: green;'>✅ getRewardRules() method working</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ getRewardRules() returned no results</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error testing RewardsSystem: " . $e->getMessage() . "</p>";
            $allGood = false;
        }
    } else {
        echo "<p style='color: red;'>❌ rewards.php file missing</p>";
        $allGood = false;
    }
    
    // Overall status
    echo "<h3>📊 Overall Status</h3>";
    if ($allGood) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
        echo "<h4>🎉 Rewards System is Fully Operational!</h4>";
        echo "<p>All components are properly set up and working correctly.</p>";
        echo "</div>";
        
        echo "<h4>🚀 Ready to Use:</h4>";
        echo "<ul>";
        echo "<li><a href='my-rewards.php' style='color: #007bff;'>My Rewards Dashboard</a> - User rewards interface</li>";
        echo "<li><a href='manage-rewards.php' style='color: #007bff;'>Manage Rewards</a> - Admin management panel</li>";
        echo "<li><a href='user-dashboard.php' style='color: #007bff;'>User Dashboard</a> - With rewards integration</li>";
        echo "<li><a href='apply-pass.php' style='color: #007bff;'>Apply for Pass</a> - Earn points automatically</li>";
        echo "</ul>";
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
        echo "<h4>⚠️ Rewards System Setup Incomplete</h4>";
        echo "<p>Some components are missing or not working properly.</p>";
        echo "<p><strong>Solution:</strong> Run the <a href='quick-setup-rewards.php'>Quick Setup Script</a> to fix any issues.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<h4>❌ Database Connection Error</h4>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Rewards Setup</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            max-width: 900px; 
            margin: 50px auto; 
            padding: 20px; 
            background: #f8f9fa;
        }
        h2 { color: #333; text-align: center; }
        h3 { color: #495057; border-bottom: 2px solid #dee2e6; padding-bottom: 5px; }
        h4 { color: #495057; }
        p { margin: 8px 0; }
        ul { margin: 10px 0; padding-left: 20px; }
        li { margin: 5px 0; }
        a { color: #007bff; text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <!-- Content is generated by PHP above -->
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" style="background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">
            🏠 Back to Home
        </a>
        <a href="user-dashboard.php" style="background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-left: 10px;">
            👤 User Dashboard
        </a>
        <a href="manage-rewards.php" style="background: #ffc107; color: black; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-left: 10px;">
            ⚙️ Admin Rewards
        </a>
    </div>
</body>
</html>
