<?php
session_start();
include('includes/dbconnection.php');

$message = '';
$messageType = '';

// Handle database setup
if (isset($_POST['setup_instant_reviews'])) {
    try {
        $con->begin_transaction();
        
        // Drop existing reviews table if it exists (to avoid conflicts with previous implementation)
        $con->query("DROP TABLE IF EXISTS reviews");
        
        // Create new instant_reviews table
        $createInstantReviewsTable = "CREATE TABLE IF NOT EXISTS instant_reviews (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            username VARCHAR(100) NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comments TEXT NOT NULL,
            is_public BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_public_created (is_public, created_at DESC),
            INDEX idx_user_created (user_id, created_at DESC)
        )";
        $con->query($createInstantReviewsTable);
        $message .= "✅ Created instant_reviews table\n";
        
        // Insert sample reviews for demonstration
        $sampleReviews = [
            [1, 'John Doe', 5, 'Excellent bus pass management system! Very user-friendly and efficient. The online application process is smooth and saves a lot of time.'],
            [2, 'Priya Sharma', 4, 'Great service overall. The digital pass system is convenient and the support team is responsive. Minor improvements needed in mobile interface.'],
            [3, 'Rajesh Kumar', 5, 'Outstanding experience! Quick approval process and easy to use dashboard. Highly recommend this system for all bus pass needs.'],
            [4, 'Anita Reddy', 4, 'Good system with modern features. The application tracking is very helpful. Would like to see more payment options in the future.'],
            [5, 'Vikram Singh', 5, 'Perfect solution for bus pass management. Clean interface, fast processing, and excellent customer service. Very satisfied!']
        ];
        
        foreach ($sampleReviews as $review) {
            $insertSample = $con->prepare("INSERT INTO instant_reviews (user_id, username, rating, comments, created_at) VALUES (?, ?, ?, ?, NOW() - INTERVAL ? DAY)");
            $daysAgo = rand(1, 30); // Random days ago for variety
            $insertSample->bind_param("isisi", $review[0], $review[1], $review[2], $review[3], $daysAgo);
            $insertSample->execute();
        }
        $message .= "✅ Added sample reviews for demonstration\n";
        
        $con->commit();
        $message .= "\n🎉 Instant Reviews System setup completed successfully!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error setting up instant reviews system: " . $e->getMessage();
        $messageType = "error";
    }
}

// Check current setup status
$setupStatus = [];
try {
    // Check if instant_reviews table exists
    $checkTable = $con->query("SHOW TABLES LIKE 'instant_reviews'");
    $setupStatus['instant_reviews_table'] = $checkTable->num_rows > 0;
    
    // Check if reviews exist
    if ($setupStatus['instant_reviews_table']) {
        $reviewsCount = $con->query("SELECT COUNT(*) as count FROM instant_reviews")->fetch_assoc()['count'];
        $setupStatus['reviews_count'] = $reviewsCount;
        
        // Get average rating
        $avgRating = $con->query("SELECT AVG(rating) as avg_rating FROM instant_reviews WHERE is_public = 1")->fetch_assoc()['avg_rating'];
        $setupStatus['avg_rating'] = round($avgRating, 1);
    }
    
} catch (Exception $e) {
    $setupStatus['error'] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Instant Reviews System - Nrupatunga Digital Bus Pass System</title>
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
        
        .warning { background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-comments"></i> Setup Instant Reviews System</h1>
            <p>Initialize the instant public review and rating system</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="features-info">
                <h4><i class="fas fa-star"></i> Instant Reviews System Features</h4>
                <ul>
                    <li><strong>Instant Public Visibility</strong> - Reviews appear immediately without admin approval</li>
                    <li><strong>Star Rating System</strong> - 1-5 star rating with visual feedback</li>
                    <li><strong>User Comments</strong> - Detailed feedback and testimonials</li>
                    <li><strong>Spam Protection</strong> - Input validation and content filtering</li>
                    <li><strong>Homepage Display</strong> - Latest 5 reviews shown on homepage</li>
                    <li><strong>Dedicated Reviews Page</strong> - Complete review listing with pagination</li>
                    <li><strong>User Dashboard Integration</strong> - Easy review submission form</li>
                    <li><strong>Real-time Updates</strong> - Dynamic content without page refresh</li>
                </ul>
            </div>
            
            <div class="warning">
                <h4><i class="fas fa-exclamation-triangle"></i> Important Notice</h4>
                <p><strong>This will replace the existing reviews system with instant public reviews.</strong></p>
                <p>The new system allows users to submit reviews that are immediately visible to the public without requiring admin approval. This provides better user engagement but requires proper spam protection.</p>
            </div>
            
            <div class="status-section">
                <h3><i class="fas fa-chart-bar"></i> Current Setup Status</h3>
                
                <div class="status-grid">
                    <div class="status-card <?php echo $setupStatus['instant_reviews_table'] ? 'good' : 'bad'; ?>">
                        <h4>Instant Reviews Table</h4>
                        <div class="icon <?php echo $setupStatus['instant_reviews_table'] ? 'good' : 'bad'; ?>">
                            <i class="fas fa-<?php echo $setupStatus['instant_reviews_table'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <p><?php echo $setupStatus['instant_reviews_table'] ? 'Created' : 'Missing'; ?></p>
                        <?php if (isset($setupStatus['reviews_count'])): ?>
                        <small><?php echo $setupStatus['reviews_count']; ?> reviews</small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($setupStatus['avg_rating'])): ?>
                    <div class="status-card good">
                        <h4>Average Rating</h4>
                        <div class="icon good">
                            <i class="fas fa-star"></i>
                        </div>
                        <p><?php echo $setupStatus['avg_rating']; ?>/5 Stars</p>
                        <small>Public Reviews</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!$setupStatus['instant_reviews_table']): ?>
            <div style="text-align: center; margin: 30px 0;">
                <form method="POST">
                    <button type="submit" name="setup_instant_reviews" class="setup-button" onclick="return confirm('Setup the instant reviews system?\n\nThis will:\n- Create instant_reviews table\n- Replace existing reviews system\n- Enable immediate public visibility\n- Add sample reviews for demonstration\n\nContinue?')">
                        <i class="fas fa-rocket"></i> Setup Instant Reviews System
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div style="text-align: center; margin: 30px 0; padding: 20px; background: #d4edda; border-radius: 8px; color: #155724;">
                <h3><i class="fas fa-check-circle"></i> Instant Reviews System Ready!</h3>
                <p>The database structure is set up and ready to use with <?php echo $setupStatus['reviews_count']; ?> reviews.</p>
            </div>
            <?php endif; ?>
            
            <div class="quick-links">
                <a href="user-dashboard.php" class="quick-link">
                    <i class="fas fa-user"></i> User Dashboard
                </a>
                <a href="user-reviews.php" class="quick-link">
                    <i class="fas fa-comments"></i> View All Reviews
                </a>
                <a href="index.php" class="quick-link">
                    <i class="fas fa-home"></i> Homepage
                </a>
                <a href="admin-dashboard.php" class="quick-link">
                    <i class="fas fa-cog"></i> Admin Dashboard
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
