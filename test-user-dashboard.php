<?php
session_start();
include('includes/dbconnection.php');

$message = '';
$messageType = '';

// Handle auto-login for testing
if (isset($_POST['auto_login'])) {
    $userId = intval($_POST['user_id']);
    
    // Check if user exists
    $userQuery = "SELECT id, full_name, email FROM users WHERE id = ?";
    $userStmt = $con->prepare($userQuery);
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $_SESSION['uid'] = $user['id'];
        $_SESSION['username'] = $user['full_name'];
        $message = "✅ Logged in as: " . $user['full_name'] . " (ID: " . $user['id'] . ")";
        $messageType = "success";
        
        // Redirect to user dashboard
        header("refresh:2;url=user-dashboard.php");
    } else {
        $message = "❌ User ID $userId not found!";
        $messageType = "error";
    }
}

// Handle create test user
if (isset($_POST['create_test_user'])) {
    try {
        $con->begin_transaction();
        
        // Create test user
        $userQuery = "INSERT INTO users (full_name, email, phone, password, created_at) VALUES ('Test User', 'testuser@example.com', '1234567890', ?, NOW())";
        $userStmt = $con->prepare($userQuery);
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $userStmt->bind_param("s", $hashedPassword);
        $userStmt->execute();
        $userId = $con->insert_id;
        
        // Create test pass type if needed
        $checkPassType = $con->query("SELECT id FROM bus_pass_types LIMIT 1");
        if ($checkPassType->num_rows == 0) {
            $passTypeQuery = "INSERT INTO bus_pass_types (type_name, duration_days, created_at) VALUES ('Monthly Pass', 30, NOW())";
            $con->query($passTypeQuery);
            $passTypeId = $con->insert_id;
        } else {
            $passTypeId = $checkPassType->fetch_assoc()['id'];
        }
        
        // Create test application
        $appQuery = "INSERT INTO bus_pass_applications (
            user_id, pass_type_id, applicant_name, phone, address, 
            source, destination, amount, status, payment_status, 
            application_date, pass_number, valid_from, valid_until
        ) VALUES (
            ?, ?, 'Test User', '1234567890', 'Test Address', 
            'City A', 'City B', 100.00, 'Approved', 'Paid', 
            NOW(), 'BP2024000001', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        )";
        $appStmt = $con->prepare($appQuery);
        $appStmt->bind_param("ii", $userId, $passTypeId);
        $appStmt->execute();
        
        $con->commit();
        $message = "✅ Test user created successfully! User ID: $userId, Email: testuser@example.com, Password: password123";
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error creating test user: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get existing users
$usersQuery = "SELECT id, full_name, email, created_at FROM users ORDER BY id DESC LIMIT 10";
$users = $con->query($usersQuery);

// Check current session
$currentUser = null;
if (isset($_SESSION['uid'])) {
    $currentUserQuery = "SELECT full_name, email FROM users WHERE id = ?";
    $currentUserStmt = $con->prepare($currentUserQuery);
    $currentUserStmt->bind_param("i", $_SESSION['uid']);
    $currentUserStmt->execute();
    $currentUserResult = $currentUserStmt->get_result();
    $currentUser = $currentUserResult->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test User Dashboard - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.5rem; }
        .content { padding: 40px; }
        
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        
        .session-info { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .session-info h4 { margin: 0 0 10px 0; color: #007bff; }
        
        .test-section { background: #f8f9fa; padding: 30px; border-radius: 10px; margin: 30px 0; border-left: 4px solid #28a745; }
        .test-section h3 { margin: 0 0 20px 0; color: #28a745; }
        
        .users-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .users-table th, .users-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .users-table th { background: #f8f9fa; font-weight: 600; }
        .users-table tr:hover { background: #f8f9fa; }
        
        .test-button { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600; margin: 5px; }
        .test-button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); }
        
        .create-button { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
        .create-button:hover { box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3); }
        
        .login-button { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #000; }
        .login-button:hover { box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3); }
        
        .dashboard-button { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        .dashboard-button:hover { box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3); }
        
        .quick-links { display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap; }
        .quick-link { background: #007bff; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .quick-link:hover { background: #0056b3; transform: translateY(-2px); text-decoration: none; color: white; }
        
        .error-info { background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .error-info h4 { margin: 0 0 10px 0; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-check"></i> Test User Dashboard</h1>
            <p>Fix and test user dashboard session errors</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="error-info">
                <h4><i class="fas fa-bug"></i> Error Fixed</h4>
                <p><strong>Original Error:</strong> "Undefined array key 'uname'" and "htmlspecialchars(): Passing null to parameter"</p>
                <p><strong>Solution:</strong> Added user information retrieval from database and replaced $_SESSION['uname'] with proper $userName variable.</p>
                <ul>
                    <li>✅ Added user query to get full_name from database</li>
                    <li>✅ Set default fallback name if user not found</li>
                    <li>✅ Replaced $_SESSION['uname'] with $userName variable</li>
                    <li>✅ Added proper null checking and error handling</li>
                </ul>
            </div>
            
            <div class="session-info">
                <h4><i class="fas fa-info-circle"></i> Current Session Status</h4>
                <?php if ($currentUser): ?>
                    <p><strong>✅ Logged In:</strong> <?php echo htmlspecialchars($currentUser['full_name']); ?> (<?php echo htmlspecialchars($currentUser['email']); ?>)</p>
                    <p><strong>User ID:</strong> <?php echo $_SESSION['uid']; ?></p>
                    <a href="user-dashboard.php" class="test-button dashboard-button">
                        <i class="fas fa-dashboard"></i> Go to User Dashboard
                    </a>
                    <a href="logout.php" class="test-button">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <p><strong>❌ Not Logged In</strong> - Use the login options below to test the dashboard</p>
                <?php endif; ?>
            </div>
            
            <div class="test-section">
                <h3><i class="fas fa-plus"></i> Create Test User</h3>
                <p>Create a new test user with sample application data.</p>
                
                <form method="POST">
                    <button type="submit" name="create_test_user" class="test-button create-button">
                        <i class="fas fa-user-plus"></i> Create Test User
                    </button>
                </form>
                <p><small>This will create: Test User (testuser@example.com) with password: password123</small></p>
            </div>
            
            <div class="test-section">
                <h3><i class="fas fa-users"></i> Existing Users - Quick Login</h3>
                
                <?php if ($users && $users->num_rows > 0): ?>
                    <table class="users-table">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Quick Login</th>
                        </tr>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="auto_login" class="test-button login-button">
                                        <i class="fas fa-sign-in-alt"></i> Login as <?php echo htmlspecialchars($user['full_name']); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No users found. Create a test user first.</p>
                <?php endif; ?>
            </div>
            
            <div class="quick-links">
                <a href="user-dashboard.php" class="quick-link">
                    <i class="fas fa-dashboard"></i> User Dashboard
                </a>
                <a href="admin-dashboard.php" class="quick-link">
                    <i class="fas fa-cog"></i> Admin Dashboard
                </a>
                <a href="test-admin-approve.php" class="quick-link">
                    <i class="fas fa-check"></i> Test Admin Approve
                </a>
                <a href="login.php" class="quick-link">
                    <i class="fas fa-sign-in-alt"></i> Regular Login
                </a>
            </div>
            
            <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff;">
                <h4><i class="fas fa-lightbulb"></i> Testing Instructions</h4>
                <ol>
                    <li><strong>Create Test User</strong> - Click button to create user with sample data</li>
                    <li><strong>Quick Login</strong> - Click "Login as [Name]" for any user</li>
                    <li><strong>Test Dashboard</strong> - Go to user dashboard to verify no errors</li>
                    <li><strong>Check Welcome Message</strong> - Verify user name displays correctly</li>
                    <li><strong>Test Print Functionality</strong> - Check if print buttons work for approved applications</li>
                </ol>
                <p><strong>Expected Result: User dashboard loads without errors and displays user name correctly!</strong></p>
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
