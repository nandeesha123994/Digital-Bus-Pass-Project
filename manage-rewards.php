<?php
session_start();
include('includes/dbconnection.php');
include('includes/rewards.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    // Auto-login for testing
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
}

$rewards = new RewardsSystem($con);
$message = '';
$messageType = '';

// Handle reward rule updates
if (isset($_POST['update_rule'])) {
    $ruleId = intval($_POST['rule_id']);
    $pointsAwarded = intval($_POST['points_awarded']);
    $description = trim($_POST['description']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($rewards->updateRewardRule($ruleId, $pointsAwarded, $description, $isActive)) {
        $message = "✅ Reward rule updated successfully!";
        $messageType = "success";
    } else {
        $message = "❌ Error updating reward rule!";
        $messageType = "error";
    }
}

// Handle bulk point award
if (isset($_POST['award_bulk_points'])) {
    $actionType = $_POST['bulk_action_type'];
    $pointsToAward = intval($_POST['bulk_points']);
    $description = trim($_POST['bulk_description']);
    
    try {
        $con->begin_transaction();
        
        // Get all users
        $usersQuery = "SELECT id FROM users";
        $usersResult = $con->query($usersQuery);
        $awardedCount = 0;
        
        while ($user = $usersResult->fetch_assoc()) {
            if ($rewards->awardPoints($user['id'], $actionType, null, $description)) {
                $awardedCount++;
            }
        }
        
        $con->commit();
        $message = "✅ Successfully awarded $pointsToAward points to $awardedCount users!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error awarding bulk points: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get reward rules
$rewardRules = $rewards->getRewardRules();

// Get rewards statistics
$stats = $rewards->getRewardsStats();

// Get recent transactions
$recentTransactionsQuery = "SELECT rt.*, u.full_name 
                           FROM rewards_transactions rt
                           JOIN users u ON rt.user_id = u.id
                           ORDER BY rt.transaction_date DESC 
                           LIMIT 20";
$recentTransactions = $con->query($recentTransactionsQuery);

// Get top users by points
$topUsersQuery = "SELECT id, full_name, email, reward_points 
                  FROM users 
                  WHERE reward_points > 0 
                  ORDER BY reward_points DESC 
                  LIMIT 10";
$topUsers = $con->query($topUsersQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rewards Rules - Nrupatunga Digital Bus Pass System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: #333; }
        
        .header { background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%); color: white; padding: 1.5rem 0; box-shadow: 0 4px 20px rgba(21, 101, 192, 0.3); }
        .header-content { max-width: 1400px; margin: 0 auto; padding: 0 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; gap: 1rem; }
        .logo-icon { width: 60px; height: 60px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
        .header-text h1 { font-size: 1.8rem; font-weight: 600; margin: 0; }
        .header-subtitle { font-size: 1rem; opacity: 0.9; font-weight: 300; }
        
        .nav-links { display: flex; gap: 1rem; align-items: center; }
        .nav-links a { color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 25px; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem; font-weight: 500; background: rgba(255, 255, 255, 0.1); }
        .nav-links a:hover { background: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }
        
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        
        .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 15px; padding: 1.5rem; text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); }
        .stat-number { font-size: 2.5rem; font-weight: 700; color: #ff6b6b; margin-bottom: 0.5rem; }
        .stat-label { font-size: 1rem; color: #666; }
        
        .section { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }
        .section-title { font-size: 1.5rem; font-weight: 600; color: #333; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .rules-grid { display: grid; gap: 1.5rem; }
        .rule-card { border: 2px solid #e0e0e0; border-radius: 15px; padding: 1.5rem; transition: all 0.3s ease; }
        .rule-card:hover { border-color: #ff6b6b; box-shadow: 0 5px 20px rgba(255, 107, 107, 0.1); }
        
        .rule-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .rule-title { font-size: 1.2rem; font-weight: 600; color: #333; }
        .rule-status { padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; }
        .rule-status.active { background: #d4edda; color: #155724; }
        .rule-status.inactive { background: #f8d7da; color: #721c24; }
        
        .rule-form { display: grid; grid-template-columns: 1fr 1fr 2fr auto auto; gap: 1rem; align-items: end; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem; color: #555; }
        .form-group input, .form-group textarea { padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.9rem; transition: border-color 0.3s ease; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #ff6b6b; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; margin-top: 1.5rem; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; }
        
        .update-btn { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .update-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3); }
        
        .bulk-award-section { background: #fff3cd; border: 2px solid #ffc107; border-radius: 15px; padding: 1.5rem; margin-bottom: 2rem; }
        .bulk-form { display: grid; grid-template-columns: 1fr 1fr 2fr auto; gap: 1rem; align-items: end; }
        .award-btn { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #000; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .award-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3); }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .data-table th, .data-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .data-table th { background: #f8f9fa; font-weight: 600; }
        .data-table tr:hover { background: #f8f9fa; }
        
        .points-earned { color: #28a745; font-weight: 600; }
        .points-redeemed { color: #dc3545; font-weight: 600; }
        
        .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        
        @media (max-width: 768px) {
            .rule-form, .bulk-form { grid-template-columns: 1fr; }
            .two-column { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="header-text">
                    <h1>Manage Rewards Rules</h1>
                    <div class="header-subtitle">Configure reward policies and point systems</div>
                </div>
            </div>
            <div class="nav-links">
                <a href="admin-dashboard.php">
                    <i class="fas fa-dashboard"></i> Admin Dashboard
                </a>
                <a href="my-rewards.php">
                    <i class="fas fa-gift"></i> View Rewards
                </a>
                <a href="user-dashboard.php">
                    <i class="fas fa-user"></i> User View
                </a>
                <a href="index.php">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_points_awarded']); ?></div>
                <div class="stat-label">Total Points Awarded</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_points_redeemed']); ?></div>
                <div class="stat-label">Total Points Redeemed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['active_users']); ?></div>
                <div class="stat-label">Users with Points</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_redemptions']); ?></div>
                <div class="stat-label">Total Redemptions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₹<?php echo number_format($stats['total_points_redeemed'] * 0.10, 2); ?></div>
                <div class="stat-label">Total Discounts Given</div>
            </div>
        </div>

        <!-- Bulk Point Award -->
        <div class="bulk-award-section">
            <h3 style="margin: 0 0 1rem 0; color: #856404;"><i class="fas fa-bullhorn"></i> Award Points to All Users</h3>
            <form method="POST">
                <div class="bulk-form">
                    <div class="form-group">
                        <label>Action Type</label>
                        <input type="text" name="bulk_action_type" placeholder="e.g., special_bonus" required>
                    </div>
                    <div class="form-group">
                        <label>Points to Award</label>
                        <input type="number" name="bulk_points" min="1" max="1000" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="bulk_description" placeholder="e.g., Special festival bonus" required>
                    </div>
                    <button type="submit" name="award_bulk_points" class="award-btn" onclick="return confirm('Award points to ALL users?\n\nThis action cannot be undone!')">
                        <i class="fas fa-gift"></i> Award to All
                    </button>
                </div>
            </form>
        </div>

        <!-- Reward Rules Management -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-rules"></i> Reward Rules Configuration
            </h2>
            
            <div class="rules-grid">
                <?php while ($rule = $rewardRules->fetch_assoc()): ?>
                <div class="rule-card">
                    <div class="rule-header">
                        <div class="rule-title"><?php echo ucwords(str_replace('_', ' ', $rule['action_type'])); ?></div>
                        <div class="rule-status <?php echo $rule['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $rule['is_active'] ? 'Active' : 'Inactive'; ?>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                        <div class="rule-form">
                            <div class="form-group">
                                <label>Points Awarded</label>
                                <input type="number" name="points_awarded" value="<?php echo $rule['points_awarded']; ?>" min="0" max="1000" required>
                            </div>
                            <div class="form-group">
                                <label>Action Type</label>
                                <input type="text" value="<?php echo $rule['action_type']; ?>" readonly style="background: #f8f9fa;">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" required><?php echo htmlspecialchars($rule['description']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="is_active" id="active_<?php echo $rule['id']; ?>" <?php echo $rule['is_active'] ? 'checked' : ''; ?>>
                                    <label for="active_<?php echo $rule['id']; ?>">Active</label>
                                </div>
                            </div>
                            <button type="submit" name="update_rule" class="update-btn">
                                <i class="fas fa-save"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Data Tables -->
        <div class="two-column">
            <!-- Recent Transactions -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i> Recent Transactions
                </h2>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Points</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transaction = $recentTransactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['full_name']); ?></td>
                            <td><?php echo ucwords(str_replace('_', ' ', $transaction['action_type'])); ?></td>
                            <td>
                                <?php if ($transaction['points_earned'] > 0): ?>
                                    <span class="points-earned">+<?php echo $transaction['points_earned']; ?></span>
                                <?php elseif ($transaction['points_redeemed'] > 0): ?>
                                    <span class="points-redeemed">-<?php echo $transaction['points_redeemed']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, H:i', strtotime($transaction['transaction_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Users -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-trophy"></i> Top Users by Points
                </h2>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while ($user = $topUsers->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><strong>#<?php echo $rank++; ?></strong></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="points-earned"><?php echo number_format($user['reward_points']); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add loading states to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    button.disabled = true;
                }
            });
        });
    </script>
</body>
</html>
