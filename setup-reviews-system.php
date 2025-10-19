<?php
session_start();
include('includes/dbconnection.php');

$message = '';
$messageType = '';

// Handle database setup
if (isset($_POST['setup_reviews'])) {
    try {
        $con->begin_transaction();
        
        // Create reviews table
        $createReviewsTable = "CREATE TABLE IF NOT EXISTS reviews (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            review_text TEXT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            status ENUM('pending', 'approved', 'hidden') DEFAULT 'pending',
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            approved_at TIMESTAMP NULL,
            approved_by INT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )";
        $con->query($createReviewsTable);
        $message .= "✅ Created reviews table\n";
        
        $con->commit();
        $message .= "\n🎉 Reviews System setup completed successfully!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error setting up reviews system: " . $e->getMessage();
        $messageType = "error";
    }
}

// Check current setup status
$setupStatus = [];
try {
    // Check if reviews table exists
    $checkTable = $con->query("SHOW TABLES LIKE 'reviews'");
    $setupStatus['reviews_table'] = $checkTable->num_rows > 0;
    
    // Check if reviews exist
    if ($setupStatus['reviews_table']) {
        $reviewsCount = $con->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
        $setupStatus['reviews_count'] = $reviewsCount;
    }
    
} catch (Exception $e) {
    $setupStatus['error'] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Reviews System - Nrupatunga Digital Bus Pass System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.5rem; }
        .content { padding: 40px; }
        
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; font-weight: 600; white-space: pre-line; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        
        .status-section { background: #f8f9fa; padding: 30px; border-radius: 10px; margin: 30px 0; border-left: 4px solid #007bff; }
        .status-section h3 { margin: 0 0 20px 0; color: #007bff; }
        
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .status-card { background: white; padding: 20px; border-radius: 8px; border: 2px solid #ddd; text-align: center; }
        .status-card.good { border-color: #28a745; }
        .status-card.bad { border-color: #dc3545; }
        .status-card h4 { margin: 0 0 10px 0; }
        .status-card .icon { font-size: 2rem; margin: 10px 0; }
        .status-card .icon.good { color: #28a745; }
        .status-card .icon.bad { color: #dc3545; }
        
        .setup-button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin: 20px 0; }
        .setup-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); }
        
        .features-info { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .features-info h4 { margin: 0 0 15px 0; color: #007bff; }
        
        .quick-links { display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap; }
        .quick-link { background: #007bff; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .quick-link:hover { background: #0056b3; transform: translateY(-2px); text-decoration: none; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> Setup Reviews System</h1>
            <p>Initialize the user review and rating system</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="features-info">
                <h4><i class="fas fa-star"></i> Reviews System Features</h4>
                <ul>
                    <li><strong>User Reviews</strong> - Users can submit reviews with star ratings</li>
                    <li><strong>Star Rating System</strong> - 1-5 star rating with visual feedback</li>
                    <li><strong>Review Management</strong> - Admin approval/moderation system</li>
                    <li><strong>Homepage Display</strong> - Show approved reviews on homepage</li>
                    <li><strong>User Dashboard Integration</strong> - Review form in user dashboard</li>
                    <li><strong>Admin Dashboard</strong> - Complete review management panel</li>
                </ul>
            </div>
            
            <div class="status-section">
                <h3><i class="fas fa-chart-bar"></i> Current Setup Status</h3>
                
                <div class="status-grid">
                    <div class="status-card <?php echo $setupStatus['reviews_table'] ? 'good' : 'bad'; ?>">
                        <h4>Reviews Table</h4>
                        <div class="icon <?php echo $setupStatus['reviews_table'] ? 'good' : 'bad'; ?>">
                            <i class="fas fa-<?php echo $setupStatus['reviews_table'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <p><?php echo $setupStatus['reviews_table'] ? 'Created' : 'Missing'; ?></p>
                        <?php if (isset($setupStatus['reviews_count'])): ?>
                        <small><?php echo $setupStatus['reviews_count']; ?> reviews</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (!$setupStatus['reviews_table']): ?>
            <div style="text-align: center; margin: 30px 0;">
                <form method="POST">
                    <button type="submit" name="setup_reviews" class="setup-button" onclick="return confirm('Setup the reviews system?\n\nThis will:\n- Create reviews table\n- Set up database structure\n- Enable review functionality\n\nContinue?')">
                        <i class="fas fa-rocket"></i> Setup Reviews System
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div style="text-align: center; margin: 30px 0; padding: 20px; background: #d4edda; border-radius: 8px; color: #155724;">
                <h3><i class="fas fa-check-circle"></i> Reviews System Ready!</h3>
                <p>The database structure is set up and ready to use.</p>
            </div>
            <?php endif; ?>
            
            <div class="quick-links">
                <a href="user-dashboard.php" class="quick-link">
                    <i class="fas fa-user"></i> User Dashboard
                </a>
                <a href="admin-dashboard.php" class="quick-link">
                    <i class="fas fa-cog"></i> Admin Dashboard
                </a>
                <a href="manage-reviews.php" class="quick-link">
                    <i class="fas fa-star"></i> Manage Reviews
                </a>
                <a href="index.php" class="quick-link">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Add loading states to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Setting up...';
                    button.disabled = true;
                }
            });
        });
    </script>
</body>
</html>
