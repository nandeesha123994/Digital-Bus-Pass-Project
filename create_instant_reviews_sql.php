<?php
session_start();
include('includes/dbconnection.php');

$message = '';
$messageType = '';

// Handle table creation
if (isset($_POST['create_table'])) {
    try {
        $con->begin_transaction();
        
        // Create instant_reviews table with exact specified fields
        $createTableSQL = "CREATE TABLE IF NOT EXISTS instant_reviews (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            review_text TEXT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'hidden') DEFAULT 'active',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status_created (status, created_at DESC),
            INDEX idx_user_created (user_id, created_at DESC)
        )";
        
        $con->query($createTableSQL);
        $message .= "✅ Created instant_reviews table with fields: id, user_id, review_text, rating, created_at, status\n";
        
        // Insert sample data for demonstration
        $sampleReviews = [
            [1, 'Excellent bus pass management system! Very user-friendly and efficient. The online application process is smooth and saves a lot of time.', 5],
            [2, 'Great service overall. The digital pass system is convenient and the support team is responsive. Minor improvements needed in mobile interface.', 4],
            [3, 'Outstanding experience! Quick approval process and easy to use dashboard. Highly recommend this system for all bus pass needs.', 5],
            [4, 'Good system with modern features. The application tracking is very helpful. Would like to see more payment options in the future.', 4],
            [5, 'Perfect solution for bus pass management. Clean interface, fast processing, and excellent customer service. Very satisfied!', 5]
        ];
        
        foreach ($sampleReviews as $index => $review) {
            $insertSample = $con->prepare("INSERT INTO instant_reviews (user_id, review_text, rating, created_at) VALUES (?, ?, ?, NOW() - INTERVAL ? DAY)");
            $daysAgo = ($index + 1) * 5; // 5, 10, 15, 20, 25 days ago
            $insertSample->bind_param("isii", $review[0], $review[1], $review[2], $daysAgo);
            $insertSample->execute();
        }
        $message .= "✅ Added sample reviews for demonstration\n";
        
        $con->commit();
        $message .= "\n🎉 Instant Reviews table created successfully with instant submission capability!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Error creating instant_reviews table: " . $e->getMessage();
        $messageType = "error";
    }
}

// Check current table status
$tableStatus = [];
try {
    // Check if instant_reviews table exists
    $checkTable = $con->query("SHOW TABLES LIKE 'instant_reviews'");
    $tableStatus['exists'] = $checkTable->num_rows > 0;
    
    if ($tableStatus['exists']) {
        // Get table structure
        $structure = $con->query("DESCRIBE instant_reviews");
        $tableStatus['fields'] = [];
        while ($field = $structure->fetch_assoc()) {
            $tableStatus['fields'][] = $field;
        }
        
        // Get review count
        $countResult = $con->query("SELECT COUNT(*) as count FROM instant_reviews WHERE status = 'active'");
        $tableStatus['review_count'] = $countResult->fetch_assoc()['count'];
        
        // Get average rating
        $avgResult = $con->query("SELECT AVG(rating) as avg_rating FROM instant_reviews WHERE status = 'active'");
        $tableStatus['avg_rating'] = round($avgResult->fetch_assoc()['avg_rating'], 1);
    }
    
} catch (Exception $e) {
    $tableStatus['error'] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Instant Reviews Table - Nrupatunga Digital Bus Pass System</title>
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
        
        .create-button { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin: 20px 0; }
        .create-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3); }
        
        .table-structure { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .table-structure table { width: 100%; border-collapse: collapse; }
        .table-structure th, .table-structure td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .table-structure th { background: #007bff; color: white; }
        
        .quick-links { display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap; }
        .quick-link { background: #007bff; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .quick-link:hover { background: #0056b3; transform: translateY(-2px); text-decoration: none; color: white; }
        
        .features-info { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
        .features-info h4 { margin: 0 0 15px 0; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-database"></i> Create Instant Reviews Table</h1>
            <p>Set up the instant_reviews table with specified fields for immediate review submission</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="features-info">
                <h4><i class="fas fa-list"></i> Table Specifications</h4>
                <ul>
                    <li><strong>Table Name:</strong> instant_reviews</li>
                    <li><strong>Fields:</strong> id, user_id, review_text, rating, created_at, status</li>
                    <li><strong>Functionality:</strong> Instant submission without admin approval</li>
                    <li><strong>Auto Display:</strong> Reviews appear immediately after submission</li>
                    <li><strong>Dashboard Integration:</strong> Status checking and success display</li>
                </ul>
            </div>
            
            <div class="status-section">
                <h3><i class="fas fa-chart-bar"></i> Table Status</h3>
                
                <div class="status-grid">
                    <div class="status-card <?php echo $tableStatus['exists'] ? 'good' : 'bad'; ?>">
                        <h4>instant_reviews Table</h4>
                        <div class="icon <?php echo $tableStatus['exists'] ? 'good' : 'bad'; ?>">
                            <i class="fas fa-<?php echo $tableStatus['exists'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <p><?php echo $tableStatus['exists'] ? 'EXISTS' : 'NOT FOUND'; ?></p>
                        <?php if (isset($tableStatus['review_count'])): ?>
                        <small><?php echo $tableStatus['review_count']; ?> active reviews</small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($tableStatus['avg_rating'])): ?>
                    <div class="status-card good">
                        <h4>Average Rating</h4>
                        <div class="icon good">
                            <i class="fas fa-star"></i>
                        </div>
                        <p><?php echo $tableStatus['avg_rating']; ?>/5 Stars</p>
                        <small>From active reviews</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="status-card <?php echo $tableStatus['exists'] ? 'good' : 'bad'; ?>">
                        <h4>Integration Status</h4>
                        <div class="icon <?php echo $tableStatus['exists'] ? 'good' : 'bad'; ?>">
                            <i class="fas fa-<?php echo $tableStatus['exists'] ? 'link' : 'unlink'; ?>"></i>
                        </div>
                        <p><?php echo $tableStatus['exists'] ? 'INTEGRATED' : 'PENDING'; ?></p>
                        <small><?php echo $tableStatus['exists'] ? 'Ready for use' : 'Needs setup'; ?></small>
                    </div>
                </div>
            </div>
            
            <?php if (isset($tableStatus['fields']) && !empty($tableStatus['fields'])): ?>
            <div class="table-structure">
                <h4><i class="fas fa-table"></i> Table Structure</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableStatus['fields'] as $field): ?>
                        <tr>
                            <td><strong><?php echo $field['Field']; ?></strong></td>
                            <td><?php echo $field['Type']; ?></td>
                            <td><?php echo $field['Null']; ?></td>
                            <td><?php echo $field['Key']; ?></td>
                            <td><?php echo $field['Default'] ?? 'NULL'; ?></td>
                            <td><?php echo $field['Extra']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!$tableStatus['exists']): ?>
            <div style="text-align: center; margin: 30px 0;">
                <form method="POST">
                    <button type="submit" name="create_table" class="create-button" onclick="return confirm('Create the instant_reviews table?\n\nThis will:\n- Create table with fields: id, user_id, review_text, rating, created_at, status\n- Enable instant review submission\n- Add sample reviews for demonstration\n- Integrate with dashboard\n\nContinue?')">
                        <i class="fas fa-plus-circle"></i> Create instant_reviews Table
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div style="text-align: center; margin: 30px 0; padding: 20px; background: #d4edda; border-radius: 8px; color: #155724;">
                <h3><i class="fas fa-check-circle"></i> instant_reviews Table Ready!</h3>
                <p>The table is created and integrated. Users can now submit reviews instantly without admin approval.</p>
            </div>
            <?php endif; ?>
            
            <div class="quick-links">
                <a href="user-dashboard.php" class="quick-link">
                    <i class="fas fa-user"></i> User Dashboard
                </a>
                <a href="instant-reviews-display.php" class="quick-link">
                    <i class="fas fa-comments"></i> View Reviews
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
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating table...';
                    button.disabled = true;
                }
            });
        });
    </script>
</body>
</html>
