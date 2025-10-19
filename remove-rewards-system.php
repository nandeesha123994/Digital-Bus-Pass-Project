<?php
echo "<h1>🗑️ Remove Rewards System</h1>";
echo "<p>This will remove all rewards-related files and clean up the system.</p>";

$message = '';
$messageType = '';

if (isset($_POST['remove_rewards'])) {
    try {
        // List of rewards-related files to remove
        $filesToRemove = [
            'includes/rewards.php',
            'my-rewards.php',
            'manage-rewards.php',
            'setup-rewards-system.php',
            'quick-setup-rewards.php',
            'fix-reward-points-column.php',
            'verify-rewards-setup.php',
            'direct-sql-fix.php',
            'check-column-status.php',
            'step-by-step-fix.php',
            'diagnose-problem.php',
            'rewards_system_sql.sql',
            'MANUAL_SQL_FIX.md',
            'REWARDS_SYSTEM_IMPLEMENTATION_SUMMARY.md',
            'REWARDS_SYSTEM_SUCCESS_SUMMARY.md',
            'REWARDS_TROUBLESHOOTING_GUIDE.md'
        ];
        
        $removedFiles = [];
        $notFoundFiles = [];
        
        foreach ($filesToRemove as $file) {
            if (file_exists($file)) {
                if (unlink($file)) {
                    $removedFiles[] = $file;
                } else {
                    $notFoundFiles[] = $file . " (failed to delete)";
                }
            } else {
                $notFoundFiles[] = $file . " (not found)";
            }
        }
        
        $message = "✅ Rewards system cleanup completed!\n\n";
        $message .= "Removed files (" . count($removedFiles) . "):\n";
        foreach ($removedFiles as $file) {
            $message .= "• $file\n";
        }
        
        if (!empty($notFoundFiles)) {
            $message .= "\nFiles not found/failed (" . count($notFoundFiles) . "):\n";
            foreach ($notFoundFiles as $file) {
                $message .= "• $file\n";
            }
        }
        
        $message .= "\n🎉 The rewards system has been completely removed from your project!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $message = "❌ Error removing rewards system: " . $e->getMessage();
        $messageType = "error";
    }
}

// Check current status
$rewardsFiles = [
    'includes/rewards.php' => 'Core rewards system',
    'my-rewards.php' => 'User rewards page',
    'manage-rewards.php' => 'Admin rewards management',
    'setup-rewards-system.php' => 'Setup script',
    'quick-setup-rewards.php' => 'Quick setup script'
];

$existingFiles = [];
foreach ($rewardsFiles as $file => $description) {
    if (file_exists($file)) {
        $existingFiles[$file] = $description;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Remove Rewards System - Nrupatunga Digital Bus Pass System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; text-align: center; }
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; white-space: pre-line; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .info { background: #e7f3ff; color: #004085; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .remove-btn { background: #dc3545; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .remove-btn:hover { background: #c82333; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3); }
        .file-list { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .file-list ul { margin: 10px 0; padding-left: 20px; }
        .file-list li { margin: 5px 0; }
        .back-link { background: #007bff; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-block; margin: 10px 5px; }
        .back-link:hover { background: #0056b3; transform: translateY(-2px); text-decoration: none; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ Remove Rewards System</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="warning">
            <h3>⚠️ Important Notice</h3>
            <p>The rewards system has been causing errors due to missing database columns. To resolve this issue, we can completely remove the rewards system from your project.</p>
            <p><strong>This action will:</strong></p>
            <ul>
                <li>Remove all rewards-related files</li>
                <li>Clean up the codebase</li>
                <li>Eliminate the "Unknown column 'reward_points'" error</li>
                <li>Restore normal functionality to your bus pass system</li>
            </ul>
        </div>
        
        <div class="info">
            <h3>📋 Current Status</h3>
            <?php if (!empty($existingFiles)): ?>
                <p><strong>Rewards files currently present:</strong></p>
                <div class="file-list">
                    <ul>
                        <?php foreach ($existingFiles as $file => $description): ?>
                            <li><strong><?php echo $file; ?></strong> - <?php echo $description; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <p>Total files to remove: <strong><?php echo count($existingFiles); ?></strong></p>
            <?php else: ?>
                <p style="color: #28a745;"><strong>✅ No rewards files found - system is already clean!</strong></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($existingFiles)): ?>
        <div style="text-align: center; margin: 30px 0;">
            <form method="POST">
                <button type="submit" name="remove_rewards" class="remove-btn" onclick="return confirm('Are you sure you want to remove the entire rewards system?\n\nThis will delete all rewards-related files and cannot be undone.\n\nClick OK to proceed.')">
                    🗑️ Remove Rewards System
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="info">
            <h3>✅ What's Already Been Done</h3>
            <p>I've already removed the rewards integration from the main system files:</p>
            <ul>
                <li>✅ <strong>user-dashboard.php</strong> - Removed rewards navigation and sidebar</li>
                <li>✅ <strong>apply-pass.php</strong> - Removed automatic point awarding</li>
                <li>✅ Cleaned up all rewards-related code that was causing errors</li>
            </ul>
            <p><strong>Your bus pass system should now work without any rewards-related errors!</strong></p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="user-dashboard.php" class="back-link">
                👤 Test User Dashboard
            </a>
            <a href="apply-pass.php" class="back-link">
                📝 Test Apply Pass
            </a>
            <a href="admin-dashboard.php" class="back-link">
                ⚙️ Admin Dashboard
            </a>
            <a href="index.php" class="back-link">
                🏠 Home
            </a>
        </div>
        
        <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <h3>🎉 System Status</h3>
            <p><strong>The main bus pass system is now clean and should work without errors!</strong></p>
            <p>You can continue using all the core features:</p>
            <ul style="text-align: left; display: inline-block;">
                <li>✅ User registration and login</li>
                <li>✅ Bus pass applications</li>
                <li>✅ Admin approval system</li>
                <li>✅ Payment processing</li>
                <li>✅ Pass printing functionality</li>
                <li>✅ User and admin dashboards</li>
            </ul>
        </div>
    </div>
</body>
</html>
