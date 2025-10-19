<?php
include('includes/dbconnection.php');

echo "<h2>Rewards System Setup</h2>";

// Start transaction
$con->begin_transaction();

try {
    // 1. Add columns to users table
    echo "<h3>1. Adding columns to users table...</h3>";
    
    // Check if reward_points column exists
    $checkPoints = "SHOW COLUMNS FROM users LIKE 'reward_points'";
    $result = $con->query($checkPoints);
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0";
        if (!$con->query($sql)) {
            throw new Exception("Error adding reward_points column: " . $con->error);
        }
        echo "✅ Added reward_points column<br>";
    } else {
        echo "✅ reward_points column already exists<br>";
    }
    
    // Check if pass_count column exists
    $checkCount = "SHOW COLUMNS FROM users LIKE 'pass_count'";
    $result = $con->query($checkCount);
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN pass_count INT DEFAULT 0";
        if (!$con->query($sql)) {
            throw new Exception("Error adding pass_count column: " . $con->error);
        }
        echo "✅ Added pass_count column<br>";
    } else {
        echo "✅ pass_count column already exists<br>";
    }
    
    // 2. Create rewards_rules table
    echo "<h3>2. Creating rewards_rules table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS rewards_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pass_type VARCHAR(50) NOT NULL,
        points_awarded INT NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    if (!$con->query($sql)) {
        throw new Exception("Error creating rewards_rules table: " . $con->error);
    }
    echo "✅ Created rewards_rules table<br>";
    
    // 3. Create rewards_transactions table
    echo "<h3>3. Creating rewards_transactions table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS rewards_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        pass_type VARCHAR(50) NOT NULL,
        points_earned INT NOT NULL,
        application_id INT,
        description TEXT,
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (application_id) REFERENCES bus_pass_applications(id) ON DELETE SET NULL
    )";
    if (!$con->query($sql)) {
        throw new Exception("Error creating rewards_transactions table: " . $con->error);
    }
    echo "✅ Created rewards_transactions table<br>";
    
    // 4. Insert default reward rules
    echo "<h3>4. Setting up default reward rules...</h3>";
    
    // First, check if rules already exist
    $checkRules = "SELECT COUNT(*) as count FROM rewards_rules";
    $result = $con->query($checkRules);
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $rules = [
            ['Daily Pass', 1, 'Earn 1 point for daily pass'],
            ['Weekly Pass', 7, 'Earn 7 points for weekly pass'],
            ['Monthly Pass', 35, 'Earn 35 points for monthly pass'],
            ['Annual Pass', 350, 'Earn 350 points for annual pass']
        ];
        
        $stmt = $con->prepare("INSERT INTO rewards_rules (pass_type, points_awarded, description) VALUES (?, ?, ?)");
        foreach ($rules as $rule) {
            $stmt->bind_param("sis", $rule[0], $rule[1], $rule[2]);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting reward rule: " . $stmt->error);
            }
        }
        echo "✅ Added default reward rules<br>";
    } else {
        echo "✅ Reward rules already exist<br>";
    }
    
    // Commit transaction
    $con->commit();
    echo "<h3>✅ Setup completed successfully!</h3>";
    echo "<p>You can now <a href='check-rewards-status.php'>check the rewards system status</a> or <a href='admin-rewards.php'>access the rewards dashboard</a>.</p>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $con->rollback();
    echo "<h3>❌ Error during setup:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Please try running the setup again or contact support if the issue persists.</p>";
}
?> 