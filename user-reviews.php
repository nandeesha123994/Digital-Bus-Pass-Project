<?php
session_start();
include('includes/dbconnection.php');

// Check if instant_reviews table exists
$tableExists = false;
$reviews = null;
$totalReviews = 0;
$totalPages = 0;
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
    // Check if instant_reviews table exists
    $tableCheck = $con->query("SHOW TABLES LIKE 'instant_reviews'");
    $tableExists = ($tableCheck && $tableCheck->num_rows > 0);

    if ($tableExists) {
        // Pagination settings
        $reviewsPerPage = 10;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $reviewsPerPage;

        // Get filter parameters
        $ratingFilter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
        $sortOrder = isset($_GET['sort']) && $_GET['sort'] === 'oldest' ? 'ASC' : 'DESC';

        // Build query conditions
        $whereConditions = ['is_public = 1'];
        $params = [];
        $types = '';

        if ($ratingFilter > 0 && $ratingFilter <= 5) {
            $whereConditions[] = 'rating = ?';
            $params[] = $ratingFilter;
            $types .= 'i';
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM instant_reviews WHERE $whereClause";
        if (!empty($params)) {
            $countStmt = $con->prepare($countQuery);
            $countStmt->bind_param($types, ...$params);
            $countStmt->execute();
            $totalReviews = $countStmt->get_result()->fetch_assoc()['total'];
        } else {
            $totalReviews = $con->query($countQuery)->fetch_assoc()['total'];
        }

        $totalPages = ceil($totalReviews / $reviewsPerPage);

        // Get reviews with pagination
        $reviewsQuery = "SELECT username, rating, comments, created_at
                         FROM instant_reviews
                         WHERE $whereClause
                         ORDER BY created_at $sortOrder
                         LIMIT ? OFFSET ?";

        $params[] = $reviewsPerPage;
        $params[] = $offset;
        $types .= 'ii';

        $reviewsStmt = $con->prepare($reviewsQuery);
        $reviewsStmt->bind_param($types, ...$params);
        $reviewsStmt->execute();
        $reviews = $reviewsStmt->get_result();

        // Get statistics
        $statsQuery = "SELECT
            COUNT(*) as total_reviews,
            AVG(rating) as avg_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM instant_reviews WHERE is_public = 1";
        $statsResult = $con->query($statsQuery);
        if ($statsResult) {
            $stats = $statsResult->fetch_assoc();
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
    <title>User Reviews - Nrupatunga Digital Bus Pass System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }

        .header { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 20px 0; margin-bottom: 30px; }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: white; margin: 0; font-size: 2.5rem; }
        .nav-links { display: flex; gap: 15px; }
        .nav-links a { color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 25px; transition: all 0.3s ease; }
        .nav-links a:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        .stats-section { background: white; border-radius: 15px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #667eea; margin-bottom: 5px; }
        .stat-label { color: #666; font-weight: 600; }

        .rating-breakdown { margin-top: 20px; }
        .rating-bar { display: flex; align-items: center; margin: 10px 0; }
        .rating-bar .stars { width: 80px; }
        .rating-bar .bar { flex: 1; height: 20px; background: #e9ecef; border-radius: 10px; margin: 0 15px; overflow: hidden; }
        .rating-bar .fill { height: 100%; background: linear-gradient(135deg, #ffd700, #ffed4e); transition: width 0.3s ease; }
        .rating-bar .count { width: 50px; text-align: right; font-weight: 600; }

        .filters-section { background: white; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .filters-grid { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .filter-group { display: flex; align-items: center; gap: 10px; }
        .filter-group select { padding: 10px 15px; border: 2px solid #ddd; border-radius: 25px; background: white; }
        .filter-group select:focus { border-color: #667eea; outline: none; }

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

        .pagination { display: flex; justify-content: center; gap: 10px; margin-top: 30px; }
        .pagination a, .pagination span { padding: 10px 15px; background: white; border: 2px solid #ddd; border-radius: 5px; text-decoration: none; color: #333; transition: all 0.3s ease; }
        .pagination a:hover { background: #667eea; color: white; border-color: #667eea; }
        .pagination .current { background: #667eea; color: white; border-color: #667eea; }

        .no-reviews { text-align: center; padding: 60px 20px; color: #666; }
        .no-reviews i { font-size: 4rem; margin-bottom: 20px; color: #ddd; }

        @media (max-width: 768px) {
            .header-content { flex-direction: column; gap: 20px; text-align: center; }
            .header h1 { font-size: 2rem; }
            .nav-links { flex-wrap: wrap; justify-content: center; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
            .filters-grid { flex-direction: column; align-items: stretch; }
            .review-header { flex-direction: column; gap: 15px; text-align: center; }
            .reviewer-info { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-comments"></i> User Reviews</h1>
            <div class="nav-links">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="user-dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
                <a href="apply-pass.php"><i class="fas fa-plus"></i> Apply Pass</a>
            </div>
        </div>
    </div>

    <div class="container">
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
                    <div class="stat-number"><?php echo round(($stats['five_star'] / max($stats['total_reviews'], 1)) * 100); ?>%</div>
                    <div class="stat-label">5-Star Reviews</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_reviews'] > 0 ? round(($stats['five_star'] + $stats['four_star']) / $stats['total_reviews'] * 100) : 0; ?>%</div>
                    <div class="stat-label">Positive Reviews</div>
                </div>
            </div>

            <div class="rating-breakdown">
                <h3 style="margin: 0 0 15px 0; color: #333;">Rating Breakdown</h3>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <?php
                $count = $stats[['', 'one_star', 'two_star', 'three_star', 'four_star', 'five_star'][$i]];
                $percentage = $stats['total_reviews'] > 0 ? ($count / $stats['total_reviews']) * 100 : 0;
                ?>
                <div class="rating-bar">
                    <div class="stars">
                        <?php for ($j = 1; $j <= 5; $j++): ?>
                            <span class="star" style="color: <?php echo $j <= $i ? '#ffd700' : '#ddd'; ?>;">⭐</span>
                        <?php endfor; ?>
                    </div>
                    <div class="bar">
                        <div class="fill" style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                    <div class="count"><?php echo $count; ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Filters Section -->
        <?php if ($tableExists): ?>
        <div class="filters-section">
            <form method="GET" class="filters-grid">
                <div class="filter-group">
                    <label for="rating"><strong>Filter by Rating:</strong></label>
                    <select name="rating" id="rating" onchange="this.form.submit()">
                        <option value="0" <?php echo (isset($ratingFilter) && $ratingFilter == 0) ? 'selected' : ''; ?>>All Ratings</option>
                        <option value="5" <?php echo (isset($ratingFilter) && $ratingFilter == 5) ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (5 Stars)</option>
                        <option value="4" <?php echo (isset($ratingFilter) && $ratingFilter == 4) ? 'selected' : ''; ?>>⭐⭐⭐⭐ (4 Stars)</option>
                        <option value="3" <?php echo (isset($ratingFilter) && $ratingFilter == 3) ? 'selected' : ''; ?>>⭐⭐⭐ (3 Stars)</option>
                        <option value="2" <?php echo (isset($ratingFilter) && $ratingFilter == 2) ? 'selected' : ''; ?>>⭐⭐ (2 Stars)</option>
                        <option value="1" <?php echo (isset($ratingFilter) && $ratingFilter == 1) ? 'selected' : ''; ?>>⭐ (1 Star)</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort"><strong>Sort by:</strong></label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo (isset($sortOrder) && $sortOrder == 'DESC') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo (isset($sortOrder) && $sortOrder == 'ASC') ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>

                <div style="margin-left: auto;">
                    <strong>Showing <?php echo isset($offset) ? min($offset + 1, $totalReviews) : 0; ?>-<?php echo isset($offset) ? min($offset + (isset($reviewsPerPage) ? $reviewsPerPage : 10), $totalReviews) : 0; ?> of <?php echo $totalReviews; ?> reviews</strong>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2 style="margin: 0 0 30px 0; color: #333;"><i class="fas fa-star"></i> Customer Reviews</h2>

            <?php if (!$tableExists): ?>
                <!-- Setup Required Message -->
                <div style="text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%); border-radius: 15px; color: white; margin: 30px 0;">
                    <i class="fas fa-tools" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.9;"></i>
                    <h3 style="margin: 0 0 15px 0; font-size: 2rem;">Instant Reviews System Setup Required</h3>
                    <p style="margin: 0 0 25px 0; font-size: 1.1rem; opacity: 0.9;">The instant reviews system needs to be set up by an administrator to display user reviews.</p>
                    <a href="setup-instant-reviews.php" style="background: rgba(255,255,255,0.2); color: white; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease; border: 2px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-rocket"></i>
                        Setup Instant Reviews System
                    </a>
                </div>
            <?php elseif ($reviews && $reviews->num_rows > 0): ?>
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
                        "<?php echo htmlspecialchars($review['comments']); ?>"
                    </div>
                </div>
                <?php endwhile; ?>

                <!-- Pagination -->
                <?php if ($tableExists && $totalPages > 1): ?>
                <div class="pagination">
                    <?php if (isset($page) && $page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&rating=<?php echo isset($ratingFilter) ? $ratingFilter : 0; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, (isset($page) ? $page : 1) - 2); $i <= min($totalPages, (isset($page) ? $page : 1) + 2); $i++): ?>
                        <?php if ($i == (isset($page) ? $page : 1)): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&rating=<?php echo isset($ratingFilter) ? $ratingFilter : 0; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if (isset($page) && $page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&rating=<?php echo isset($ratingFilter) ? $ratingFilter : 0; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-reviews">
                    <i class="fas fa-comments"></i>
                    <h3>No Reviews Found</h3>
                    <p>Be the first to share your experience with our bus pass management system!</p>
                    <a href="user-dashboard.php" style="background: #667eea; color: white; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; margin-top: 20px; display: inline-block;">
                        <i class="fas fa-plus"></i> Write a Review
                    </a>
                </div>
            <?php endif; ?>
        </div>
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
