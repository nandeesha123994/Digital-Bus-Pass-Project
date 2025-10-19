<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>Step-by-Step Fix for Reward Points Error</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .step { background: white; margin: 20px 0; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .step h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .step h3 { color: #007bff; }
        .code { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; margin: 10px 0; font-family: monospace; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #0056b3; }
        .button.success { background: #28a745; }
        .button.danger { background: #dc3545; }
        ol { padding-left: 20px; }
        li { margin: 10px 0; }
        img { max-width: 100%; border: 1px solid #ddd; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔧 Step-by-Step Fix: 'Unknown column reward_points' Error</h1>";

echo "<div class='step'>
<h2>📋 STEP 1: Understand the Problem</h2>
<p><strong>Error:</strong> <code>Exception: Unknown column 'reward_points' in 'field list'</code></p>
<p><strong>Cause:</strong> The 'reward_points' column does not exist in the 'users' table in your database.</p>
<p><strong>Solution:</strong> We need to add this column to the users table.</p>
</div>";

echo "<div class='step'>
<h2>🔍 STEP 2: Check Current Database Status</h2>
<h3>Let's check if the column exists:</h3>";

include('includes/dbconnection.php');

try {
    // Check if users table exists
    $tablesResult = $con->query("SHOW TABLES LIKE 'users'");
    if ($tablesResult->num_rows == 0) {
        echo "<div class='error'>❌ ERROR: 'users' table does not exist in your database!</div>";
        echo "<p>You need to set up the basic bus pass system first before adding rewards.</p>";
        exit;
    } else {
        echo "<div class='success'>✅ 'users' table exists</div>";
    }
    
    // Check current table structure
    echo "<h3>Current 'users' table structure:</h3>";
    $structure = $con->query("DESCRIBE users");
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Column Name</th><th>Data Type</th><th>Null</th><th>Default</th></tr>";
    
    $hasRewardPoints = false;
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        
        if ($row['Field'] === 'reward_points') {
            $hasRewardPoints = true;
        }
    }
    echo "</table>";
    
    if ($hasRewardPoints) {
        echo "<div class='success'>✅ 'reward_points' column already exists!</div>";
        echo "<p>The column exists, but there might be another issue. Let's check for NULL values...</p>";
        
        $nullCheck = $con->query("SELECT COUNT(*) as count FROM users WHERE reward_points IS NULL");
        $nullCount = $nullCheck->fetch_assoc()['count'];
        
        if ($nullCount > 0) {
            echo "<div class='warning'>⚠️ Found $nullCount users with NULL reward_points values</div>";
            echo "<p>We need to fix these NULL values.</p>";
        } else {
            echo "<div class='success'>✅ All users have valid reward_points values</div>";
        }
    } else {
        echo "<div class='error'>❌ 'reward_points' column is MISSING from the users table</div>";
        echo "<p>This is the source of your error. We need to add this column.</p>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection error: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='step'>
<h2>🛠️ STEP 3: Fix the Problem</h2>
<p>Choose one of these methods to fix the issue:</p>

<h3>Method A: Automatic Fix (Click Button)</h3>
<form method='POST' style='margin: 20px 0;'>
    <button type='submit' name='auto_fix' class='button success' onclick='return confirm(\"Add reward_points column to users table?\")'>
        🔧 Automatically Add reward_points Column
    </button>
</form>";

// Handle automatic fix
if (isset($_POST['auto_fix'])) {
    echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🔧 Attempting Automatic Fix...</h4>";
    
    try {
        // Check if column already exists
        $checkColumn = $con->query("SHOW COLUMNS FROM users LIKE 'reward_points'");
        
        if ($checkColumn->num_rows > 0) {
            echo "<p>✅ Column already exists. Checking for NULL values...</p>";
            $con->query("UPDATE users SET reward_points = 0 WHERE reward_points IS NULL");
            echo "<p>✅ Fixed any NULL values</p>";
        } else {
            echo "<p>Adding reward_points column...</p>";
            $alterQuery = "ALTER TABLE users ADD COLUMN reward_points INT(11) DEFAULT 0 NOT NULL";
            
            if ($con->query($alterQuery)) {
                echo "<p>✅ Successfully added reward_points column!</p>";
                
                // Initialize existing users
                $con->query("UPDATE users SET reward_points = 0 WHERE reward_points IS NULL");
                echo "<p>✅ Initialized all existing users with 0 points</p>";
                
                echo "<div class='success'>🎉 AUTOMATIC FIX COMPLETED SUCCESSFULLY!</div>";
                echo "<p><strong>The error should now be resolved. Test the rewards system below.</strong></p>";
                
            } else {
                echo "<div class='error'>❌ Automatic fix failed: " . $con->error . "</div>";
                echo "<p>Please try the manual method below.</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error during automatic fix: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

echo "<h3>Method B: Manual Fix (phpMyAdmin)</h3>
<div class='warning'>
<p><strong>If the automatic fix didn't work, follow these manual steps:</strong></p>
<ol>
    <li><strong>Open phpMyAdmin</strong>
        <ul>
            <li>Go to: <code>http://localhost/phpmyadmin</code></li>
            <li>Login with your MySQL credentials</li>
        </ul>
    </li>
    <li><strong>Select Your Database</strong>
        <ul>
            <li>Click on your database name (usually 'bpmsdb' or similar)</li>
            <li>You should see a list of tables including 'users'</li>
        </ul>
    </li>
    <li><strong>Open SQL Tab</strong>
        <ul>
            <li>Click on the 'SQL' tab at the top</li>
            <li>You'll see a text area where you can enter SQL commands</li>
        </ul>
    </li>
    <li><strong>Run This SQL Command</strong>
        <div class='code'>ALTER TABLE users ADD COLUMN reward_points INT(11) DEFAULT 0 NOT NULL;</div>
        <ul>
            <li>Copy the command above</li>
            <li>Paste it into the SQL text area</li>
            <li>Click 'Go' button</li>
        </ul>
    </li>
    <li><strong>Initialize Existing Users</strong>
        <div class='code'>UPDATE users SET reward_points = 0 WHERE reward_points IS NULL;</div>
        <ul>
            <li>Run this second command to set all users to 0 points</li>
        </ul>
    </li>
</ol>
</div>";

echo "</div>";

echo "<div class='step'>
<h2>✅ STEP 4: Verify the Fix</h2>
<p>After applying the fix, let's verify it worked:</p>";

// Re-check the column status
try {
    $verifyColumn = $con->query("SHOW COLUMNS FROM users LIKE 'reward_points'");
    
    if ($verifyColumn->num_rows > 0) {
        $columnInfo = $verifyColumn->fetch_assoc();
        echo "<div class='success'>
        ✅ SUCCESS! reward_points column now exists!
        <ul>
            <li><strong>Type:</strong> " . $columnInfo['Type'] . "</li>
            <li><strong>Default:</strong> " . ($columnInfo['Default'] ?? 'NULL') . "</li>
            <li><strong>Null:</strong> " . $columnInfo['Null'] . "</li>
        </ul>
        </div>";
        
        // Check user data
        $userStats = $con->query("SELECT COUNT(*) as total_users, SUM(reward_points) as total_points FROM users");
        $stats = $userStats->fetch_assoc();
        
        echo "<p><strong>User Statistics:</strong></p>";
        echo "<ul>";
        echo "<li>Total Users: " . $stats['total_users'] . "</li>";
        echo "<li>Total Points in System: " . ($stats['total_points'] ?? 0) . "</li>";
        echo "</ul>";
        
    } else {
        echo "<div class='error'>❌ Column still missing. Please try the manual method.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Verification error: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='step'>
<h2>🧪 STEP 5: Test the Rewards System</h2>
<p>Now test if the rewards system works:</p>

<div style='text-align: center; margin: 30px 0;'>
    <a href='my-rewards.php' class='button success'>🎁 Test My Rewards Page</a>
    <a href='user-dashboard.php' class='button'>👤 Test User Dashboard</a>
    <a href='manage-rewards.php' class='button'>⚙️ Test Admin Panel</a>
</div>

<p><strong>Expected Results:</strong></p>
<ul>
    <li>✅ My Rewards page should load without errors</li>
    <li>✅ User dashboard should show rewards section</li>
    <li>✅ No more 'Unknown column' errors</li>
    <li>✅ Apply for pass should award points automatically</li>
</ul>
</div>";

echo "<div class='step'>
<h2>🔧 STEP 6: Complete Rewards System Setup</h2>
<p>If the column fix worked, complete the rewards system setup:</p>

<form method='POST' style='margin: 20px 0;'>
    <button type='submit' name='setup_rewards' class='button' onclick='return confirm(\"Set up complete rewards system with tables and rules?\")'>
        🚀 Complete Rewards System Setup
    </button>
</form>";

// Handle complete setup
if (isset($_POST['setup_rewards'])) {
    echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🚀 Setting up complete rewards system...</h4>";
    
    try {
        // Create rewards tables
        $tables = [
            "rewards_rules" => "CREATE TABLE IF NOT EXISTS rewards_rules (
                id INT PRIMARY KEY AUTO_INCREMENT,
                action_type VARCHAR(50) NOT NULL UNIQUE,
                points_awarded INT NOT NULL,
                description TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "rewards_transactions" => "CREATE TABLE IF NOT EXISTS rewards_transactions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                points_earned INT NOT NULL,
                points_redeemed INT DEFAULT 0,
                reference_id INT NULL,
                description TEXT,
                transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "rewards_redemptions" => "CREATE TABLE IF NOT EXISTS rewards_redemptions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                redemption_type VARCHAR(50) NOT NULL,
                points_used INT NOT NULL,
                discount_amount DECIMAL(10,2) DEFAULT 0,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                application_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $tableName => $createSQL) {
            if ($con->query($createSQL)) {
                echo "<p>✅ Created $tableName table</p>";
            } else {
                echo "<p>⚠️ $tableName table: " . $con->error . "</p>";
            }
        }
        
        // Insert default rules
        $rules = [
            ['pass_application', 50, 'Points earned for applying for a new bus pass'],
            ['pass_renewal', 30, 'Points earned for renewing an existing bus pass'],
            ['referral_signup', 100, 'Points earned when a referred user signs up'],
            ['referral_first_pass', 150, 'Points earned when a referred user applies for their first pass'],
            ['payment_completion', 25, 'Points earned for completing payment on time'],
            ['profile_completion', 20, 'Points earned for completing profile information']
        ];
        
        foreach ($rules as $rule) {
            $insertRule = $con->prepare("INSERT IGNORE INTO rewards_rules (action_type, points_awarded, description) VALUES (?, ?, ?)");
            $insertRule->bind_param("sis", $rule[0], $rule[1], $rule[2]);
            if ($insertRule->execute()) {
                echo "<p>✅ Added rule: {$rule[0]} ({$rule[1]} points)</p>";
            }
        }
        
        echo "<div class='success'>🎉 COMPLETE REWARDS SYSTEM SETUP FINISHED!</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Setup error: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

echo "</div>";

echo "<div class='step'>
<h2>🎯 STEP 7: Final Verification</h2>
<p>Run this final check to ensure everything is working:</p>

<a href='verify-rewards-setup.php' class='button success'>🔍 Run Complete System Verification</a>

<p style='margin-top: 20px;'><strong>If verification passes, your rewards system is fully operational!</strong></p>
</div>";

echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin: 30px 0; text-align: center;'>
<h2>🎉 Summary</h2>
<p>After completing these steps, the 'Unknown column reward_points' error will be completely resolved!</p>
<p><strong>The rewards system will be fully functional with automatic point awarding and redemption capabilities.</strong></p>
</div>";

echo "</body></html>";
?>
