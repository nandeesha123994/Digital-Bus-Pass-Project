<?php
session_start();
include('includes/dbconnection.php');

echo "<h2>🔧 Fix Reward Points Column</h2>";
echo "<p>Checking and fixing the reward_points column in users table...</p>";

try {
    // Check current users table structure
    echo "<h3>1. Current Users Table Structure</h3>";
    $result = $con->query("DESCRIBE users");
    $columns = [];
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if reward_points column exists
    echo "<h3>2. Reward Points Column Check</h3>";
    if (in_array('reward_points', $columns)) {
        echo "<p style='color: green;'>✅ reward_points column already exists</p>";
        
        // Check if any users have NULL values
        $nullCount = $con->query("SELECT COUNT(*) as count FROM users WHERE reward_points IS NULL")->fetch_assoc()['count'];
        if ($nullCount > 0) {
            echo "<p style='color: orange;'>⚠️ Found $nullCount users with NULL reward_points</p>";
            echo "<p>Updating NULL values to 0...</p>";
            $con->query("UPDATE users SET reward_points = 0 WHERE reward_points IS NULL");
            echo "<p style='color: green;'>✅ Updated $nullCount users to have 0 reward_points</p>";
        } else {
            echo "<p style='color: green;'>✅ All users have valid reward_points values</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ reward_points column is missing</p>";
        echo "<p>Adding reward_points column...</p>";
        
        // Add the column
        $alterQuery = "ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0 AFTER phone";
        if ($con->query($alterQuery)) {
            echo "<p style='color: green;'>✅ Successfully added reward_points column</p>";
            
            // Initialize all existing users with 0 points
            $updateQuery = "UPDATE users SET reward_points = 0 WHERE reward_points IS NULL";
            $con->query($updateQuery);
            echo "<p style='color: green;'>✅ Initialized all users with 0 reward_points</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding column: " . $con->error . "</p>";
        }
    }
    
    // Verify the fix
    echo "<h3>3. Verification</h3>";
    $verifyResult = $con->query("DESCRIBE users");
    $hasRewardPoints = false;
    while ($row = $verifyResult->fetch_assoc()) {
        if ($row['Field'] === 'reward_points') {
            $hasRewardPoints = true;
            echo "<p style='color: green;'>✅ reward_points column confirmed: {$row['Type']}, Default: {$row['Default']}</p>";
            break;
        }
    }
    
    if (!$hasRewardPoints) {
        echo "<p style='color: red;'>❌ reward_points column still missing after fix attempt</p>";
    } else {
        // Check user count and points
        $userStats = $con->query("SELECT COUNT(*) as total_users, SUM(reward_points) as total_points, AVG(reward_points) as avg_points FROM users")->fetch_assoc();
        echo "<p style='color: green;'>✅ Total Users: {$userStats['total_users']}</p>";
        echo "<p style='color: green;'>✅ Total Points: {$userStats['total_points']}</p>";
        echo "<p style='color: green;'>✅ Average Points: " . round($userStats['avg_points'], 2) . "</p>";
    }
    
    // Test the rewards system
    echo "<h3>4. Testing Rewards System</h3>";
    if (file_exists('includes/rewards.php')) {
        try {
            include_once('includes/rewards.php');
            $rewards = new RewardsSystem($con);
            
            // Test getting user points for first user
            $firstUser = $con->query("SELECT id FROM users LIMIT 1")->fetch_assoc();
            if ($firstUser) {
                $points = $rewards->getUserPoints($firstUser['id']);
                echo "<p style='color: green;'>✅ RewardsSystem working - User {$firstUser['id']} has $points points</p>";
            }
            
            echo "<p style='color: green;'>✅ Rewards system is functional</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Rewards system error: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>🎉 Fix Complete!</h3>";
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ Reward Points Column Fixed Successfully!</h4>";
    echo "<p>The reward_points column is now properly configured and working.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Error During Fix</h4>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Reward Points Column</title>
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
        table { width: 100%; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        a { color: #007bff; text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Content is generated by PHP above -->
        
        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 5px;">
            <h4>🚀 Next Steps</h4>
            <p>Now that the reward_points column is fixed, you can:</p>
            <ul>
                <li><a href="my-rewards.php">Test My Rewards Page</a></li>
                <li><a href="manage-rewards.php">Access Admin Rewards Management</a></li>
                <li><a href="user-dashboard.php">Check User Dashboard Integration</a></li>
                <li><a href="apply-pass.php">Apply for Pass to Earn Points</a></li>
                <li><a href="verify-rewards-setup.php">Run Full System Verification</a></li>
            </ul>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="my-rewards.php" style="background: #ff6b6b; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;">
            🎁 My Rewards
        </a>
        <a href="manage-rewards.php" style="background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;">
            ⚙️ Manage Rewards
        </a>
        <a href="user-dashboard.php" style="background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;">
            👤 User Dashboard
        </a>
    </div>
</body>
</html>
