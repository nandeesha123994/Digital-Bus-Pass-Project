<?php
session_start();
include('includes/dbconnection.php');
include('process-rewards.php');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// Get user's rewards information
$rewards = getUserRewards($con, $_SESSION['uid']);
if (!$rewards) {
    $error = "Error loading rewards information";
}

// Get top users
$topUsers = getTopUsers($con, 10);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Rewards - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .rewards-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .rewards-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .points-display {
            text-align: center;
            font-size: 2em;
            color: #007bff;
            margin: 10px 0;
        }
        .badge-display {
            text-align: center;
            margin: 10px 0;
        }
        .badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            color: white;
        }
        .badge.bronze {
            background: #cd7f32;
        }
        .badge.silver {
            background: #c0c0c0;
        }
        .badge.gold {
            background: #ffd700;
            color: #333;
        }
        .badge.platinum {
            background: #e5e4e2;
            color: #333;
        }
        .badge.diamond {
            background: #b9f2ff;
            color: #333;
        }
        .transactions-list {
            list-style: none;
            padding: 0;
        }
        .transaction-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .transaction-item:last-child {
            border-bottom: none;
        }
        .points-earned {
            color: #28a745;
            font-weight: bold;
        }
        .leaderboard {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        .rank {
            width: 30px;
            font-weight: bold;
            color: #666;
        }
        .user-info {
            flex-grow: 1;
            margin-left: 10px;
        }
        .user-points {
            font-weight: bold;
            color: #007bff;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="rewards-header">
            <h1><i class="fas fa-star"></i> My Rewards</h1>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <div class="rewards-grid">
                <!-- Points Card -->
                <div class="rewards-card">
                    <h2><i class="fas fa-coins"></i> My Points</h2>
                    <div class="points-display">
                        <?php echo number_format($rewards['points']); ?>
                    </div>
                    <p style="text-align: center;">Total points earned</p>
                </div>
                
                <!-- Badge Card -->
                <div class="rewards-card">
                    <h2><i class="fas fa-medal"></i> My Badge</h2>
                    <div class="badge-display">
                        <span class="badge <?php echo strtolower($rewards['badge']); ?>">
                            <?php echo $rewards['badge']; ?> Member
                        </span>
                    </div>
                    <p style="text-align: center;">
                        <?php
                        $nextLevel = '';
                        $pointsNeeded = 0;
                        if ($rewards['badge'] === 'Bronze') {
                            $nextLevel = 'Silver';
                            $pointsNeeded = 100 - $rewards['points'];
                        } elseif ($rewards['badge'] === 'Silver') {
                            $nextLevel = 'Gold';
                            $pointsNeeded = 250 - $rewards['points'];
                        } elseif ($rewards['badge'] === 'Gold') {
                            $nextLevel = 'Platinum';
                            $pointsNeeded = 500 - $rewards['points'];
                        } elseif ($rewards['badge'] === 'Platinum') {
                            $nextLevel = 'Diamond';
                            $pointsNeeded = 1000 - $rewards['points'];
                        }
                        
                        if ($nextLevel) {
                            echo "Need " . $pointsNeeded . " more points for " . $nextLevel . " badge";
                        } else {
                            echo "You've reached the highest level!";
                        }
                        ?>
                    </p>
                </div>
                
                <!-- Recent Transactions Card -->
                <div class="rewards-card">
                    <h2><i class="fas fa-history"></i> Recent Activity</h2>
                    <ul class="transactions-list">
                        <?php foreach ($rewards['recent_transactions'] as $transaction): ?>
                            <li class="transaction-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($transaction['pass_type']); ?></strong>
                                    <div><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></div>
                                </div>
                                <span class="points-earned">+<?php echo $transaction['points_earned']; ?> pts</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Leaderboard -->
            <div class="leaderboard">
                <h2><i class="fas fa-trophy"></i> Top Members</h2>
                <?php foreach ($topUsers as $index => $user): ?>
                    <div class="leaderboard-item">
                        <div class="rank">#<?php echo $index + 1; ?></div>
                        <div class="user-info">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                            <span class="badge <?php echo strtolower($user['badge']); ?>">
                                <?php echo $user['badge']; ?>
                            </span>
                        </div>
                        <div class="user-points">
                            <?php echo number_format($user['reward_points']); ?> pts
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 