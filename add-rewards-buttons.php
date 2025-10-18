<?php
// This script will add the rewards buttons to both dashboards

// 1. First, make sure the includes directory exists
if (!is_dir('includes')) {
    mkdir('includes', 0777, true);
}

// 2. Create the rewards-nav.php file
$rewardsNavContent = '<?php
// Get user\'s reward points if logged in
$userPoints = 0;
$userBadge = "Bronze";
if (isset($_SESSION["uid"])) {
    $pointsQuery = "SELECT reward_points FROM users WHERE id = ?";
    $stmt = $con->prepare($pointsQuery);
    $stmt->bind_param("i", $_SESSION["uid"]);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $userPoints = $row["reward_points"];
        // Determine badge based on points
        if ($userPoints >= 1000) {
            $userBadge = "Diamond";
        } elseif ($userPoints >= 500) {
            $userBadge = "Platinum";
        } elseif ($userPoints >= 250) {
            $userBadge = "Gold";
        } elseif ($userPoints >= 100) {
            $userBadge = "Silver";
        }
    }
}
?>

<!-- User Dashboard Navigation -->
<?php if (isset($_SESSION["uid"])): ?>
<div class="nav-item rewards-nav">
    <a href="rewards-dashboard.php" class="rewards-link">
        <i class="fas fa-star"></i>
        <span class="rewards-text">My Rewards</span>
        <span class="rewards-badge <?php echo strtolower($userBadge); ?>">
            <?php echo $userPoints; ?> pts
        </span>
    </a>
</div>
<?php endif; ?>

<!-- Admin Dashboard Navigation -->
<?php if (isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true): ?>
<div class="nav-item rewards-nav">
    <a href="admin-rewards.php" class="rewards-link">
        <i class="fas fa-star"></i>
        <span class="rewards-text">Rewards Management</span>
    </a>
</div>
<?php endif; ?>

<style>
.rewards-nav {
    margin: 10px 0;
}

.rewards-link {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 5px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.rewards-link:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}

.rewards-text {
    margin-left: 10px;
    font-weight: 500;
}

.rewards-badge {
    margin-left: auto;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.9em;
    font-weight: bold;
}

.rewards-badge.bronze {
    background: #cd7f32;
    color: white;
}

.rewards-badge.silver {
    background: #c0c0c0;
    color: white;
}

.rewards-badge.gold {
    background: #ffd700;
    color: #333;
}

.rewards-badge.platinum {
    background: #e5e4e2;
    color: #333;
}

.rewards-badge.diamond {
    background: #b9f2ff;
    color: #333;
}
</style>';

file_put_contents('includes/rewards-nav.php', $rewardsNavContent);

// 3. Add rewards navigation to user-dashboard.php
$userDashboardContent = file_get_contents('user-dashboard.php');
$searchPattern = '/<a href="apply-pass.php" class="nav-item">\s*<i class="fas fa-plus-circle"><\/i> Apply for Pass\s*<\/a>/';
$replacement = '<a href="apply-pass.php" class="nav-item">
    <i class="fas fa-plus-circle"></i> Apply for Pass
</a>
<?php include("includes/rewards-nav.php"); ?>';
$userDashboardContent = preg_replace($searchPattern, $replacement, $userDashboardContent);
file_put_contents('user-dashboard.php', $userDashboardContent);

// 4. Add rewards navigation to admin-dashboard.php
$adminDashboardContent = file_get_contents('admin-dashboard.php');
$searchPattern = '/<a href="manage-applications.php" class="nav-item">\s*<i class="fas fa-file-alt"><\/i> Applications\s*<\/a>/';
$replacement = '<a href="manage-applications.php" class="nav-item">
    <i class="fas fa-file-alt"></i> Applications
</a>
<?php include("includes/rewards-nav.php"); ?>';
$adminDashboardContent = preg_replace($searchPattern, $replacement, $adminDashboardContent);
file_put_contents('admin-dashboard.php', $adminDashboardContent);

echo "✅ Rewards navigation has been added to both dashboards!<br>";
echo "Please refresh your browser to see the changes.";
?> 