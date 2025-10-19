<?php
// Direct SQL Fix for Reward Points Column
// This will forcefully add the missing column

include('includes/dbconnection.php');

echo "<h1>🔧 Direct SQL Fix for Reward Points</h1>";
echo "<p>Attempting to fix the reward_points column issue...</p>";

try {
    echo "<h3>Step 1: Check Current Database Structure</h3>";
    
    // First, let's see what tables exist
    $tables = $con->query("SHOW TABLES");
    echo "<p><strong>Available tables:</strong></p>";
    echo "<ul>";
    while ($table = $tables->fetch_array()) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
    
    echo "<h3>Step 2: Check Users Table Structure</h3>";
    
    // Check if users table exists
    $checkUsers = $con->query("SHOW TABLES LIKE 'users'");
    if ($checkUsers->num_rows == 0) {
        echo "<p style='color: red;'>❌ ERROR: 'users' table does not exist!</p>";
        echo "<p>Please ensure your database is properly set up with the users table.</p>";
        exit;
    }
    
    // Show current users table structure
    $structure = $con->query("DESCRIBE users");
    echo "<p><strong>Current users table structure:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasRewardPoints = false;
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
        
        if ($row['Field'] === 'reward_points') {
            $hasRewardPoints = true;
        }
    }
    echo "</table>";
    
    echo "<h3>Step 3: Add Reward Points Column</h3>";
    
    if ($hasRewardPoints) {
        echo "<p style='color: green;'>✅ reward_points column already exists!</p>";
        
        // Check for NULL values
        $nullCheck = $con->query("SELECT COUNT(*) as count FROM users WHERE reward_points IS NULL");
        $nullCount = $nullCheck->fetch_assoc()['count'];
        
        if ($nullCount > 0) {
            echo "<p style='color: orange;'>⚠️ Found $nullCount users with NULL reward_points. Fixing...</p>";
            $con->query("UPDATE users SET reward_points = 0 WHERE reward_points IS NULL");
            echo "<p style='color: green;'>✅ Fixed NULL values</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ reward_points column is missing. Adding it now...</p>";
        
        // Try different approaches to add the column
        $alterQueries = [
            "ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0",
            "ALTER TABLE users ADD reward_points INT DEFAULT 0",
            "ALTER TABLE users ADD COLUMN reward_points INT(11) DEFAULT 0",
            "ALTER TABLE users ADD reward_points INT(11) DEFAULT 0"
        ];
        
        $success = false;
        foreach ($alterQueries as $query) {
            echo "<p>Trying: <code>$query</code></p>";
            
            if ($con->query($query)) {
                echo "<p style='color: green;'>✅ Successfully added reward_points column!</p>";
                $success = true;
                break;
            } else {
                echo "<p style='color: red;'>❌ Failed: " . $con->error . "</p>";
            }
        }
        
        if (!$success) {
            echo "<p style='color: red;'>❌ All attempts failed. Manual intervention required.</p>";
            echo "<h4>Manual SQL Commands to Run in phpMyAdmin:</h4>";
            echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
            echo "ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0;\n";
            echo "UPDATE users SET reward_points = 0 WHERE reward_points IS NULL;";
            echo "</pre>";
            exit;
        }
        
        // Initialize existing users
        echo "<p>Initializing existing users with 0 points...</p>";
        $con->query("UPDATE users SET reward_points = 0 WHERE reward_points IS NULL");
        echo "<p style='color: green;'>✅ All users initialized with 0 points</p>";
    }
    
    echo "<h3>Step 4: Verify the Fix</h3>";
    
    // Verify the column exists now
    $verifyStructure = $con->query("DESCRIBE users");
    $columnExists = false;
    while ($row = $verifyStructure->fetch_assoc()) {
        if ($row['Field'] === 'reward_points') {
            $columnExists = true;
            echo "<p style='color: green;'>✅ reward_points column confirmed:</p>";
            echo "<ul>";
            echo "<li><strong>Type:</strong> " . $row['Type'] . "</li>";
            echo "<li><strong>Default:</strong> " . ($row['Default'] ?? 'NULL') . "</li>";
            echo "<li><strong>Null:</strong> " . $row['Null'] . "</li>";
            echo "</ul>";
            break;
        }
    }
    
    if (!$columnExists) {
        echo "<p style='color: red;'>❌ Column still not found after fix attempt!</p>";
        exit;
    }
    
    // Test with actual data
    echo "<h3>Step 5: Test with Sample Data</h3>";
    
    $userCount = $con->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    echo "<p><strong>Total users:</strong> $userCount</p>";
    
    if ($userCount > 0) {
        $sampleUsers = $con->query("SELECT id, full_name, reward_points FROM users LIMIT 5");
        echo "<p><strong>Sample users with reward points:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Reward Points</th></tr>";
        while ($user = $sampleUsers->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . $user['reward_points'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Step 6: Test Rewards System</h3>";
    
    // Test if we can now use the rewards system
    if (file_exists('includes/rewards.php')) {
        try {
            include_once('includes/rewards.php');
            $rewards = new RewardsSystem($con);
            
            // Test getting points for first user
            $firstUser = $con->query("SELECT id FROM users LIMIT 1")->fetch_assoc();
            if ($firstUser) {
                $points = $rewards->getUserPoints($firstUser['id']);
                echo "<p style='color: green;'>✅ RewardsSystem test successful!</p>";
                echo "<p>User {$firstUser['id']} has $points reward points</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ RewardsSystem test failed: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>🎉 SUCCESS!</h3>";
    echo "<p>The reward_points column has been successfully added and configured!</p>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li>Visit <a href='my-rewards.php'>My Rewards</a> page</li>";
    echo "<li>Access <a href='manage-rewards.php'>Admin Rewards Management</a></li>";
    echo "<li>Use the <a href='user-dashboard.php'>User Dashboard</a> with rewards</li>";
    echo "<li>Apply for passes to earn points automatically</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p>An error occurred: " . $e->getMessage() . "</p>";
    echo "<p><strong>Manual fix required:</strong></p>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin</li>";
    echo "<li>Select your database (usually 'bpmsdb')</li>";
    echo "<li>Click on 'users' table</li>";
    echo "<li>Go to 'Structure' tab</li>";
    echo "<li>Click 'Add column'</li>";
    echo "<li>Add column name: reward_points</li>";
    echo "<li>Type: INT</li>";
    echo "<li>Default: 0</li>";
    echo "<li>Save</li>";
    echo "</ol>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Direct SQL Fix</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        h1, h3 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        ul, ol { margin: 10px 0; padding-left: 20px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-top: 30px;">
        <a href="my-rewards.php" style="background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;">
            🎁 Test My Rewards
        </a>
        <a href="user-dashboard.php" style="background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;">
            👤 User Dashboard
        </a>
        <a href="manage-rewards.php" style="background: #ffc107; color: black; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;">
            ⚙️ Admin Panel
        </a>
    </div>
</body>
</html>
