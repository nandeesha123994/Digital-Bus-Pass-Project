<?php
echo "<h1>🔍 Diagnostic Report: Reward Points Error</h1>";
echo "<p>Let's identify exactly what's causing the 'Unknown column reward_points' error...</p>";

include('includes/dbconnection.php');

echo "<div style='background: white; padding: 20px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
echo "<h2>📊 Database Diagnostic Report</h2>";

try {
    // 1. Check database connection
    echo "<h3>1. Database Connection</h3>";
    if ($con) {
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        
        // Get database name
        $dbName = $con->query("SELECT DATABASE() as db_name")->fetch_assoc()['db_name'];
        echo "<p><strong>Connected to database:</strong> $dbName</p>";
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
        exit;
    }
    
    // 2. Check if users table exists
    echo "<h3>2. Users Table Check</h3>";
    $tablesResult = $con->query("SHOW TABLES LIKE 'users'");
    if ($tablesResult->num_rows > 0) {
        echo "<p style='color: green;'>✅ 'users' table exists</p>";
        
        // Count users
        $userCount = $con->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
        echo "<p><strong>Total users in table:</strong> $userCount</p>";
    } else {
        echo "<p style='color: red;'>❌ 'users' table does NOT exist</p>";
        echo "<p><strong>PROBLEM IDENTIFIED:</strong> You need to create the users table first!</p>";
        exit;
    }
    
    // 3. Check users table structure
    echo "<h3>3. Users Table Structure Analysis</h3>";
    $structure = $con->query("DESCRIBE users");
    $columns = [];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $hasRewardPoints = false;
    while ($row = $structure->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        
        if ($row['Field'] === 'reward_points') {
            $hasRewardPoints = true;
        }
    }
    echo "</table>";
    
    echo "<p><strong>Total columns in users table:</strong> " . count($columns) . "</p>";
    echo "<p><strong>Columns found:</strong> " . implode(', ', $columns) . "</p>";
    
    // 4. Reward points column analysis
    echo "<h3>4. Reward Points Column Analysis</h3>";
    if ($hasRewardPoints) {
        echo "<p style='color: green;'>✅ 'reward_points' column EXISTS in users table</p>";
        
        // Check for NULL values
        $nullCheck = $con->query("SELECT COUNT(*) as null_count FROM users WHERE reward_points IS NULL");
        $nullCount = $nullCheck->fetch_assoc()['null_count'];
        
        if ($nullCount > 0) {
            echo "<p style='color: orange;'>⚠️ WARNING: $nullCount users have NULL reward_points</p>";
            echo "<p><strong>SOLUTION:</strong> Run this SQL: <code>UPDATE users SET reward_points = 0 WHERE reward_points IS NULL;</code></p>";
        } else {
            echo "<p style='color: green;'>✅ All users have valid reward_points values</p>";
        }
        
        // Show sample data
        $sampleData = $con->query("SELECT id, full_name, reward_points FROM users LIMIT 3");
        echo "<p><strong>Sample user data:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Name</th><th>Reward Points</th></tr>";
        while ($user = $sampleData->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . ($user['reward_points'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ 'reward_points' column is MISSING from users table</p>";
        echo "<p style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "<strong>🎯 PROBLEM IDENTIFIED:</strong> This is exactly why you're getting the error!<br>";
        echo "The code is trying to access a column that doesn't exist.";
        echo "</p>";
    }
    
    // 5. Check rewards system files
    echo "<h3>5. Rewards System Files Check</h3>";
    $requiredFiles = [
        'includes/rewards.php' => 'Core rewards system',
        'my-rewards.php' => 'User rewards page',
        'manage-rewards.php' => 'Admin rewards management'
    ];
    
    foreach ($requiredFiles as $file => $description) {
        if (file_exists($file)) {
            echo "<p style='color: green;'>✅ $file exists ($description)</p>";
        } else {
            echo "<p style='color: red;'>❌ $file missing ($description)</p>";
        }
    }
    
    // 6. Check rewards tables
    echo "<h3>6. Rewards System Tables Check</h3>";
    $rewardTables = ['rewards_rules', 'rewards_transactions', 'rewards_redemptions'];
    
    foreach ($rewardTables as $table) {
        $tableCheck = $con->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck->num_rows > 0) {
            $count = $con->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
            echo "<p style='color: green;'>✅ $table exists ($count records)</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $table missing (will be created during setup)</p>";
        }
    }
    
    // 7. Error reproduction test
    echo "<h3>7. Error Reproduction Test</h3>";
    try {
        $testQuery = "SELECT id, full_name, reward_points FROM users LIMIT 1";
        $testResult = $con->query($testQuery);
        
        if ($testResult) {
            echo "<p style='color: green;'>✅ Test query successful - No error reproduced</p>";
            echo "<p>The reward_points column is working correctly.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ERROR REPRODUCED: " . $e->getMessage() . "</p>";
        echo "<p>This confirms the column is missing.</p>";
    }
    
    // 8. Recommended solution
    echo "<h3>8. Recommended Solution</h3>";
    if (!$hasRewardPoints) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px;'>";
        echo "<h4>🎯 EXACT PROBLEM IDENTIFIED</h4>";
        echo "<p>The 'reward_points' column is missing from your users table.</p>";
        echo "<p><strong>IMMEDIATE FIX:</strong></p>";
        echo "<ol>";
        echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
        echo "<li>Select your database ($dbName)</li>";
        echo "<li>Click on 'users' table</li>";
        echo "<li>Go to 'SQL' tab</li>";
        echo "<li>Run this command:</li>";
        echo "</ol>";
        echo "<div style='background: #000; color: #0f0; padding: 15px; border-radius: 5px; font-family: monospace;'>";
        echo "ALTER TABLE users ADD COLUMN reward_points INT(11) DEFAULT 0 NOT NULL;";
        echo "</div>";
        echo "</div>";
        
        echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
        echo "<h4>🚀 AUTOMATIC FIX AVAILABLE</h4>";
        echo "<p>Or use the automatic fix in the step-by-step guide:</p>";
        echo "<a href='step-by-step-fix.php' style='background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>🔧 Go to Step-by-Step Fix</a>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px;'>";
        echo "<h4>✅ COLUMN EXISTS - DIFFERENT ISSUE</h4>";
        echo "<p>The reward_points column exists, so the error might be caused by:</p>";
        echo "<ul>";
        echo "<li>NULL values in the column</li>";
        echo "<li>Cached table structure</li>";
        echo "<li>Different database being accessed</li>";
        echo "<li>File permission issues</li>";
        echo "</ul>";
        echo "<p><strong>Try refreshing the page or restarting your web server.</strong></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Diagnostic error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='step-by-step-fix.php' style='background: #007bff; color: white; padding: 15px 30px; border-radius: 5px; text-decoration: none; margin: 10px;'>🔧 Step-by-Step Fix</a>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 15px 30px; border-radius: 5px; text-decoration: none; margin: 10px;'>🏠 Back to Home</a>";
echo "</div>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic Report</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        h1, h2, h3 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
        ol, ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
</body>
</html>
