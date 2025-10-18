<?php
session_start();
include('includes/dbconnection.php');
include('process-rewards.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Handle reward rule updates
if (isset($_POST['update_rule'])) {
    try {
        $ruleId = $_POST['rule_id'];
        $points = $_POST['points'];
        $description = $_POST['description'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $updateQuery = "UPDATE rewards_rules 
                       SET points_awarded = ?, 
                           description = ?, 
                           is_active = ? 
                       WHERE id = ?";
        $stmt = $con->prepare($updateQuery);
        $stmt->bind_param("isii", $points, $description, $isActive, $ruleId);
        $stmt->execute();
        
        $message = "✅ Reward rule updated successfully";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "❌ Error updating reward rule: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get all reward rules
$rulesQuery = "SELECT * FROM rewards_rules ORDER BY points_awarded ASC";
$rules = $con->query($rulesQuery);

// Get top users
$topUsers = getTopUsers($con, 20);

// Get recent transactions
$transactionsQuery = "SELECT rt.*, u.full_name 
                     FROM rewards_transactions rt 
                     JOIN users u ON rt.user_id = u.id 
                     ORDER BY rt.transaction_date DESC 
                     LIMIT 20";
$transactions = $con->query($transactionsQuery);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rewards Management - Admin Dashboard</title>
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
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
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
        .points {
            font-weight: bold;
            color: #007bff;
        }
        .status-active {
            color: #28a745;
        }
        .status-inactive {
            color: #dc3545;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
        .edit-form {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .edit-form.active {
            display: block;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input[type="number"],
        .form-group input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group input[type="checkbox"] {
            margin-right: 5px;
        }
        .button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .button:hover {
            background: #0056b3;
        }
        .button.edit {
            background: #28a745;
        }
        .button.edit:hover {
            background: #218838;
        }
        .button.cancel {
            background: #dc3545;
        }
        .button.cancel:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-star"></i> Rewards Management</h1>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Reward Rules Section -->
        <div class="section">
            <h2><i class="fas fa-cog"></i> Reward Rules</h2>
            <table>
                <thead>
                    <tr>
                        <th>Pass Type</th>
                        <th>Points Awarded</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($rule = $rules->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rule['pass_type']); ?></td>
                            <td class="points"><?php echo $rule['points_awarded']; ?> pts</td>
                            <td><?php echo htmlspecialchars($rule['description']); ?></td>
                            <td>
                                <span class="status-<?php echo $rule['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $rule['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="button edit" onclick="toggleEditForm(<?php echo $rule['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <form class="edit-form" id="edit-form-<?php echo $rule['id']; ?>" method="POST">
                                    <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                    <div class="form-group">
                                        <label>Points Awarded:</label>
                                        <input type="number" name="points" value="<?php echo $rule['points_awarded']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Description:</label>
                                        <input type="text" name="description" value="<?php echo htmlspecialchars($rule['description']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="is_active" <?php echo $rule['is_active'] ? 'checked' : ''; ?>>
                                            Active
                                        </label>
                                    </div>
                                    <button type="submit" name="update_rule" class="button">Save Changes</button>
                                    <button type="button" class="button cancel" onclick="toggleEditForm(<?php echo $rule['id']; ?>)">Cancel</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Top Users Section -->
        <div class="section">
            <h2><i class="fas fa-trophy"></i> Top Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th>Badge</th>
                        <th>Points</th>
                        <th>Passes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topUsers as $index => $user): ?>
                        <tr>
                            <td>#<?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td>
                                <span class="badge <?php echo strtolower($user['badge']); ?>">
                                    <?php echo $user['badge']; ?>
                                </span>
                            </td>
                            <td class="points"><?php echo number_format($user['reward_points']); ?> pts</td>
                            <td><?php echo $user['pass_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Transactions Section -->
        <div class="section">
            <h2><i class="fas fa-history"></i> Recent Transactions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Pass Type</th>
                        <th>Points</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($transaction = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['pass_type']); ?></td>
                            <td class="points">+<?php echo $transaction['points_earned']; ?> pts</td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function toggleEditForm(ruleId) {
            const form = document.getElementById('edit-form-' + ruleId);
            form.classList.toggle('active');
        }
    </script>
</body>
</html> 