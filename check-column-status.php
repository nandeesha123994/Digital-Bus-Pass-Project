<?php
include('includes/dbconnection.php');

echo "<h1>🔍 Column Status Check</h1>";

try {
    // Check if reward_points column exists
    $result = $con->query("SHOW COLUMNS FROM users LIKE 'reward_points'");
    
    if ($result->num_rows > 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>✅ SUCCESS: reward_points column EXISTS!</h2>";
        
        $column = $result->fetch_assoc();
        echo "<p><strong>Column details:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Type:</strong> " . $column['Type'] . "</li>";
        echo "<li><strong>Null:</strong> " . $column['Null'] . "</li>";
        echo "<li><strong>Default:</strong> " . ($column['Default'] ?? 'NULL') . "</li>";
        echo "</ul>";
        
        // Check user data
        $userCheck = $con->query("SELECT COUNT(*) as total, SUM(reward_points) as total_points FROM users");
        $stats = $userCheck->fetch_assoc();
        
        echo "<p><strong>User statistics:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Total users:</strong> " . $stats['total'] . "</li>";
        echo "<li><strong>Total points:</strong> " . ($stats['total_points'] ?? 0) . "</li>";
        echo "</ul>";
        
        echo "<p><strong>✅ The Rewards System should now work!</strong></p>";
        echo "</div>";
        
        echo "<h3>🚀 Test the Rewards System:</h3>";
        echo "<div style='text-align: center; margin: 20px 0;'>";
        echo "<a href='my-rewards.php' style='background: #28a745; color: white; padding: 15px 25px; border-radius: 5px; text-decoration: none; margin: 10px; display: inline-block;'>🎁 My Rewards</a>";
        echo "<a href='user-dashboard.php' style='background: #007bff; color: white; padding: 15px 25px; border-radius: 5px; text-decoration: none; margin: 10px; display: inline-block;'>👤 User Dashboard</a>";
        echo "<a href='manage-rewards.php' style='background: #ffc107; color: black; padding: 15px 25px; border-radius: 5px; text-decoration: none; margin: 10px; display: inline-block;'>⚙️ Admin Panel</a>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>❌ PROBLEM: reward_points column is MISSING!</h2>";
        echo "<p>The reward_points column does not exist in the users table.</p>";
        echo "</div>";
        
        echo "<h3>🔧 Quick Fix Options:</h3>";
        echo "<div style='background: #fff3cd; color: #856404; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h4>Option 1: Automatic Fix</h4>";
        echo "<p>Click this button to automatically add the column:</p>";
        echo "<form method='POST' style='margin: 10px 0;'>";
        echo "<button type='submit' name='auto_fix' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>🔧 Auto-Fix Column</button>";
        echo "</form>";
        echo "</div>";
        
        echo "<div style='background: #e7f3ff; color: #004085; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h4>Option 2: Manual Fix (phpMyAdmin)</h4>";
        echo "<p>1. Open phpMyAdmin</p>";
        echo "<p>2. Select your database (usually 'bpmsdb')</p>";
        echo "<p>3. Run this SQL command:</p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0;</pre>";
        echo "</div>";
    }
    
    // Handle auto-fix
    if (isset($_POST['auto_fix'])) {
        echo "<h3>🔧 Attempting Auto-Fix...</h3>";
        
        $alterQuery = "ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0";
        
        if ($con->query($alterQuery)) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>✅ Successfully added reward_points column!</p>";
            echo "</div>";
            
            // Initialize users
            $con->query("UPDATE users SET reward_points = 0 WHERE reward_points IS NULL");
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>✅ Initialized all users with 0 points!</p>";
            echo "</div>";
            
            echo "<p><strong>🎉 Fix completed! Refresh this page to verify.</strong></p>";
            echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
            
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>❌ Auto-fix failed: " . $con->error . "</p>";
            echo "<p>Please use the manual fix method.</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection.</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Check Column Status</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px; 
            background: #f8f9fa;
        }
        h1, h2, h3 { color: #333; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        ul { margin: 10px 0; padding-left: 20px; }
        a { text-decoration: none; }
        a:hover { opacity: 0.8; }
        button:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">
            🏠 Back to Home
        </a>
    </div>
</body>
</html>
