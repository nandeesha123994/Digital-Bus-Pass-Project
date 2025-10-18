<?php
include('includes/dbconnection.php');

echo "<h2>Rewards System Status Check</h2>";

// Check users table columns
$checkUsersColumns = "SHOW COLUMNS FROM users LIKE 'reward_points'";
$result = $con->query($checkUsersColumns);
echo "<h3>1. Users Table Check:</h3>";
if ($result->num_rows > 0) {
    echo "✅ reward_points column exists in users table<br>";
} else {
    echo "❌ reward_points column is missing from users table<br>";
}

$checkPassCount = "SHOW COLUMNS FROM users LIKE 'pass_count'";
$result = $con->query($checkPassCount);
if ($result->num_rows > 0) {
    echo "✅ pass_count column exists in users table<br>";
} else {
    echo "❌ pass_count column is missing from users table<br>";
}

// Check rewards_rules table
$checkRulesTable = "SHOW TABLES LIKE 'rewards_rules'";
$result = $con->query($checkRulesTable);
echo "<h3>2. Rewards Rules Table Check:</h3>";
if ($result->num_rows > 0) {
    echo "✅ rewards_rules table exists<br>";
    
    // Check if rules are populated
    $checkRules = "SELECT COUNT(*) as count FROM rewards_rules";
    $result = $con->query($checkRules);
    $row = $result->fetch_assoc();
    echo "Found {$row['count']} reward rules<br>";
} else {
    echo "❌ rewards_rules table is missing<br>";
}

// Check rewards_transactions table
$checkTransactionsTable = "SHOW TABLES LIKE 'rewards_transactions'";
$result = $con->query($checkTransactionsTable);
echo "<h3>3. Rewards Transactions Table Check:</h3>";
if ($result->num_rows > 0) {
    echo "✅ rewards_transactions table exists<br>";
    
    // Check if any transactions exist
    $checkTransactions = "SELECT COUNT(*) as count FROM rewards_transactions";
    $result = $con->query($checkTransactions);
    $row = $result->fetch_assoc();
    echo "Found {$row['count']} reward transactions<br>";
} else {
    echo "❌ rewards_transactions table is missing<br>";
}

// Check if any users have reward points
$checkUserPoints = "SELECT COUNT(*) as count FROM users WHERE reward_points > 0";
$result = $con->query($checkUserPoints);
$row = $result->fetch_assoc();
echo "<h3>4. User Points Check:</h3>";
echo "Found {$row['count']} users with reward points<br>";

// Display any errors
if ($con->error) {
    echo "<h3>❌ Database Errors:</h3>";
    echo $con->error;
}

// Add setup button if any issues are found
$setupNeeded = false;
$checkAll = "SHOW COLUMNS FROM users LIKE 'reward_points'";
$result = $con->query($checkAll);
if ($result->num_rows == 0) {
    $setupNeeded = true;
}

if ($setupNeeded) {
    echo "<h3>Setup Required:</h3>";
    echo "<form action='setup-rewards-db.php' method='post'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>Run Rewards Setup</button>";
    echo "</form>";
}
?> 