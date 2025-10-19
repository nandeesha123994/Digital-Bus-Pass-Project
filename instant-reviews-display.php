<?php
session_start();
include('includes/dbconnection.php');

// Check if instant_reviews table exists
$tableExists = false;
$reviews = null;
$totalReviews = 0;
$stats = [
    'total_reviews' => 0,
    'avg_rating' => 4.8,
    'five_star' => 0,
    'four_star' => 0,
    'three_star' => 0,
    'two_star' => 0,
    'one_star' => 0
];

try {
    // Check if instant_reviews table exists with correct structure
    $tableCheck = $con->query("SHOW TABLES LIKE 'instant_reviews'");
    $tableExists = ($tableCheck && $tableCheck->num_rows > 0);

    if ($tableExists) {
        // Verify table has correct fields
        $fieldsCheck = $con->query("DESCRIBE instant_reviews");
        $fields = [];
        while ($field = $fieldsCheck->fetch_assoc()) {
            $fields[] = $field['Field'];
        }
        $requiredFields = ['id', 'user_id', 'review_text', 'rating', 'created_at', 'status'];
        $tableExists = count(array_intersect($requiredFields, $fields)) === count($requiredFields);

        if ($tableExists) {
            // Get reviews
            $reviewsQuery = "SELECT u.full_name as username, ir.review_text, ir.rating, ir.created_at
                             FROM instant_reviews ir
                             JOIN users u ON ir.user_id = u.id
                             WHERE ir.status = 'active'
                             ORDER BY ir.created_at DESC
                             LIMIT 20";
            $reviews = $con->query($reviewsQuery);

            // Get statistics
            $statsQuery = "SELECT
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM instant_reviews WHERE status = 'active'";
            $statsResult = $con->query($statsQuery);
            if ($statsResult) {
                $stats = $statsResult->fetch_assoc();
            }
        }
    }
} catch (Exception $e) {
    $tableExists = false;
    $reviews = null;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Instant Reviews Display - Nrupatunga Digital Bus Pass System</title>
    <link rel="stylesheet" href="assets/css/color-schemes.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); min-height: 100vh; }

        .header { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 20px 0; margin-bottom: 30px; }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; margin: 0; font-size: 2.5rem; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: #333; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.3); border-radius: 25px; transition: all 0.3s ease; }
        .nav-links a:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        .stats-section { background: white; border-radius: 15px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-card { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #667eea; margin-bottom: 5px; }
        .stat-label { color: #666; font-weight: 600; }

        .reviews-section { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .review-card { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #667eea; transition: all 0.3s ease; }
        .review-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .reviewer-info { display: flex; align-items: center; gap: 15px; }
        .reviewer-avatar { width: 50px; height: 50px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem; }
        .reviewer-details h4 { margin: 0; color: #333; }
        .reviewer-details .date { color: #666; font-size: 0.9rem; }

        .rating-display { display: flex; gap: 2px; }
        .rating-display .star { color: #ffd700; font-size: 1.2rem; }
        .rating-display .star.empty { color: #ddd; }

        .review-text { color: #555; line-height: 1.6; font-size: 1.1rem; margin-top: 15px; }

        .no-reviews { text-align: center; padding: 60px 20px; color: #666; }
        .no-reviews i { font-size: 4rem; margin-bottom: 20px; color: #ddd; }

        .setup-required { text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%); border-radius: 15px; color: white; margin: 30px 0; }
        .setup-required i { font-size: 4rem; margin-bottom: 20px; opacity: 0.9; }
        .setup-required h3 { margin: 0 0 15px 0; font-size: 2rem; }
        .setup-required p { margin: 0 0 25px 0; font-size: 1.1rem; opacity: 0.9; }
        .setup-required a { background: rgba(255,255,255,0.2); color: white; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease; border: 2px solid rgba(255,255,255,0.3); }
        .setup-required a:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }

        @media (max-width: 768px) {
            .header-content { flex-direction: column; gap: 20px; text-align: center; }
            .header h1 { font-size: 2rem; }
            .nav-links { flex-wrap: wrap; justify-content: center; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
            .review-header { flex-direction: column; gap: 15px; text-align: center; }
            .reviewer-info { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-comments"></i> Instant Reviews</h1>
            <div class="nav-links">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="user-dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
                <a href="apply-pass.php"><i class="fas fa-plus"></i> Apply Pass</a>
                <a href="create_instant_reviews_sql.php"><i class="fas fa-database"></i> Setup</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!$tableExists): ?>
            <!-- Setup Required Message -->
            <div class="setup-required">
                <i class="fas fa-database"></i>
                <h3>instant_reviews Table Setup Required</h3>
                <p>The instant_reviews table with fields (id, user_id, review_text, rating, created_at, status) needs to be created to display user reviews.</p>
                <a href="create_instant_reviews_sql.php">
                    <i class="fas fa-plus-circle"></i>
                    Create instant_reviews Table
                </a>
            </div>
        <?php else: ?>
            <!-- Statistics Section -->
            <div class="stats-section">
                <h2 style="margin: 0 0 20px 0; color: #333;"><i class="fas fa-chart-bar"></i> Review Statistics</h2>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_reviews']; ?></div>
                        <div class="stat-label">Total Reviews</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_reviews'] > 0 ? round(($stats['five_star'] / $stats['total_reviews']) * 100) : 0; ?>%</div>
                        <div class="stat-label">5-Star Reviews</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_reviews'] > 0 ? round((($stats['five_star'] + $stats['four_star']) / $stats['total_reviews']) * 100) : 0; ?>%</div>
                        <div class="stat-label">Positive Reviews</div>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <h2 style="margin: 0 0 30px 0; color: #333;"><i class="fas fa-star"></i> User Reviews (Instant Display)</h2>

                <?php if ($reviews && $reviews->num_rows > 0): ?>
                    <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <?php echo strtoupper(substr($review['username'], 0, 1)); ?>
                                </div>
                                <div class="reviewer-details">
                                    <h4><?php echo htmlspecialchars($review['username']); ?></h4>
                                    <div class="date"><?php echo date('F j, Y \a\t g:i A', strtotime($review['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="rating-display">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $review['rating'] ? '' : 'empty'; ?>">⭐</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-text">
                            "<?php echo htmlspecialchars($review['review_text']); ?>"
                        </div>
                    </div>
                    <?php endwhile; ?>

                    <div style="text-align: center; margin-top: 30px;">
                        <a href="user-dashboard.php" style="background: #667eea; color: white; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px;">
                            <i class="fas fa-plus"></i> Submit Your Review
                        </a>
                    </div>

                <?php else: ?>
                    <div class="no-reviews">
                        <i class="fas fa-comments"></i>
                        <h3>No Reviews Yet</h3>
                        <p>Be the first to share your experience with our bus pass management system!</p>
                        <a href="user-dashboard.php" style="background: #667eea; color: white; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; margin-top: 20px; display: inline-block;">
                            <i class="fas fa-plus"></i> Write First Review
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.review-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
