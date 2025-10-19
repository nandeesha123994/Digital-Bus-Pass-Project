<?php
session_start();
include('includes/dbconnection.php');
include('includes/rewards.php');

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

$rewards = new RewardsSystem($con);
$message = '';
$messageType = '';

// Handle point redemption
if (isset($_POST['redeem_points'])) {
    $pointsToRedeem = intval($_POST['points_to_redeem']);
    $redemptionType = $_POST['redemption_type'];

    $result = $rewards->redeemPoints($_SESSION['uid'], $pointsToRedeem, $redemptionType);

    if ($result['success']) {
        $message = "✅ Successfully redeemed $pointsToRedeem points for ₹" . number_format($result['discount_amount'], 2) . " discount! Remaining points: " . $result['remaining_points'];
        $messageType = "success";
    } else {
        $message = "❌ " . $result['message'];
        $messageType = "error";
    }
}

// Get user information
$userQuery = "SELECT full_name, email, reward_points FROM users WHERE id = ?";
$userStmt = $con->prepare($userQuery);
$userStmt->bind_param("i", $_SESSION['uid']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Get user's transaction history
$transactions = $rewards->getUserTransactions($_SESSION['uid'], 20);

// Get user's redemption history
$redemptions = $rewards->getUserRedemptions($_SESSION['uid'], 10);

// Get redemption options
$redemptionOptions = $rewards->getRedemptionOptions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rewards - Nrupatunga Digital Bus Pass System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: #333; }

        .header { background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%); color: white; padding: 1.5rem 0; box-shadow: 0 4px 20px rgba(21, 101, 192, 0.3); }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; gap: 1rem; }
        .logo-icon { width: 60px; height: 60px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
        .header-text h1 { font-size: 1.8rem; font-weight: 600; margin: 0; }
        .header-subtitle { font-size: 1rem; opacity: 0.9; font-weight: 300; }

        .nav-links { display: flex; gap: 1rem; align-items: center; }
        .nav-links a { color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 25px; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem; font-weight: 500; background: rgba(255, 255, 255, 0.1); }
        .nav-links a:hover { background: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }

        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }

        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }

        .rewards-overview { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }
        .points-display { text-align: center; margin-bottom: 2rem; }
        .points-number { font-size: 4rem; font-weight: 700; color: #ff6b6b; margin: 1rem 0; }
        .points-label { font-size: 1.2rem; color: #666; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 15px; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }

        .section { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }
        .section-title { font-size: 1.5rem; font-weight: 600; color: #333; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }

        .redemption-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .redemption-card { border: 2px solid #e0e0e0; border-radius: 15px; padding: 1.5rem; text-align: center; transition: all 0.3s ease; }
        .redemption-card:hover { border-color: #ff6b6b; transform: translateY(-5px); box-shadow: 0 10px 25px rgba(255, 107, 107, 0.2); }
        .redemption-card.available { border-color: #28a745; }
        .redemption-card.unavailable { opacity: 0.6; }

        .redemption-icon { font-size: 3rem; color: #ff6b6b; margin-bottom: 1rem; }
        .redemption-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem; }
        .redemption-points { font-size: 1.1rem; color: #666; margin-bottom: 1rem; }
        .redemption-description { font-size: 0.9rem; color: #888; margin-bottom: 1.5rem; }

        .redeem-btn { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .redeem-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3); }
        .redeem-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; box-shadow: none; }

        .transaction-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .transaction-table th, .transaction-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .transaction-table th { background: #f8f9fa; font-weight: 600; }
        .transaction-table tr:hover { background: #f8f9fa; }

        .points-earned { color: #28a745; font-weight: 600; }
        .points-redeemed { color: #dc3545; font-weight: 600; }

        .empty-state { text-align: center; padding: 3rem; color: #666; }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; color: #ddd; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="header-text">
                    <h1>My Rewards</h1>
                    <div class="header-subtitle">Earn points and redeem exciting rewards</div>
                </div>
            </div>
            <div class="nav-links">
                <a href="user-dashboard.php">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
                <a href="apply-pass.php">
                    <i class="fas fa-plus"></i> Apply Pass
                </a>
                <a href="index.php">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
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

        <!-- Rewards Overview -->
        <div class="rewards-overview">
            <div class="points-display">
                <div class="points-number"><?php echo number_format($user['reward_points']); ?></div>
                <div class="points-label">Reward Points Available</div>
            </div>

            <div class="stats-grid">
                <?php
                $totalEarned = 0;
                $totalRedeemed = 0;
                $transactionCount = 0;

                $allTransactions = $rewards->getUserTransactions($_SESSION['uid'], 1000);
                while ($transaction = $allTransactions->fetch_assoc()) {
                    $totalEarned += $transaction['points_earned'];
                    $totalRedeemed += $transaction['points_redeemed'];
                    $transactionCount++;
                }
                ?>

                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalEarned); ?></div>
                    <div class="stat-label">Total Points Earned</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalRedeemed); ?></div>
                    <div class="stat-label">Total Points Redeemed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $transactionCount; ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format($totalRedeemed * 0.10, 2); ?></div>
                    <div class="stat-label">Total Savings</div>
                </div>
            </div>
        </div>

        <!-- Redemption Options -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-gift"></i> Redeem Your Points
            </h2>

            <div class="redemption-grid">
                <?php foreach ($redemptionOptions as $option): ?>
                    <?php $canRedeem = $user['reward_points'] >= $option['points_required']; ?>
                    <div class="redemption-card <?php echo $canRedeem ? 'available' : 'unavailable'; ?>">
                        <div class="redemption-icon">
                            <i class="fas fa-<?php echo $canRedeem ? 'gift' : 'lock'; ?>"></i>
                        </div>
                        <div class="redemption-title"><?php echo $option['name']; ?></div>
                        <div class="redemption-points"><?php echo number_format($option['points_required']); ?> Points</div>
                        <div class="redemption-description"><?php echo $option['description']; ?></div>

                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="points_to_redeem" value="<?php echo $option['points_required']; ?>">
                            <input type="hidden" name="redemption_type" value="<?php echo $option['type']; ?>">
                            <button type="submit" name="redeem_points" class="redeem-btn"
                                    <?php echo !$canRedeem ? 'disabled' : ''; ?>
                                    onclick="return confirm('Redeem <?php echo $option['points_required']; ?> points for <?php echo $option['name']; ?>?')">
                                <?php echo $canRedeem ? 'Redeem Now' : 'Insufficient Points'; ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-history"></i> Recent Transactions
            </h2>

            <?php if ($transactions && $transactions->num_rows > 0): ?>
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transaction = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                            <td><?php echo ucwords(str_replace('_', ' ', $transaction['action_type'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td>
                                <?php if ($transaction['points_earned'] > 0): ?>
                                    <span class="points-earned">+<?php echo $transaction['points_earned']; ?></span>
                                <?php elseif ($transaction['points_redeemed'] > 0): ?>
                                    <span class="points-redeemed">-<?php echo $transaction['points_redeemed']; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>No Transactions Yet</h3>
                    <p>Start earning points by applying for bus passes and completing actions!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- How to Earn Points -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-lightbulb"></i> How to Earn Points
            </h2>

            <div class="stats-grid">
                <?php
                $rules = $rewards->getRewardRules();
                while ($rule = $rules->fetch_assoc()):
                    if ($rule['is_active']):
                ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $rule['points_awarded']; ?></div>
                    <div class="stat-label"><?php echo ucwords(str_replace('_', ' ', $rule['action_type'])); ?></div>
                </div>
                <?php
                    endif;
                endwhile;
                ?>
            </div>

            <div style="margin-top: 2rem; padding: 1.5rem; background: #e7f3ff; border-radius: 10px; border-left: 4px solid #007bff;">
                <h4 style="margin: 0 0 1rem 0; color: #007bff;"><i class="fas fa-info-circle"></i> Point Values</h4>
                <p style="margin: 0; color: #666;">• 1 Point = ₹0.10 discount value</p>
                <p style="margin: 0; color: #666;">• Points never expire</p>
                <p style="margin: 0; color: #666;">• Minimum redemption: 100 points (₹10 discount)</p>
            </div>
        </div>
    </div>

    <script>
        // Add loading states to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button && !button.disabled) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    button.disabled = true;
                }
            });
        });
    </script>
</body>
</html>
