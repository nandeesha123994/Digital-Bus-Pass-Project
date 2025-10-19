<?php
session_start();
include('includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['aid'])) {
    header('Location: admin-login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle review actions
if (isset($_POST['action']) && isset($_POST['review_id'])) {
    $reviewId = intval($_POST['review_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $updateQuery = "UPDATE reviews SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?";
        $stmt = $con->prepare($updateQuery);
        $stmt->bind_param("ii", $_SESSION['aid'], $reviewId);
        
        if ($stmt->execute()) {
            $message = "Review approved successfully!";
            $messageType = "success";
        } else {
            $message = "Error approving review.";
            $messageType = "error";
        }
    } elseif ($action === 'hide') {
        $updateQuery = "UPDATE reviews SET status = 'hidden' WHERE id = ?";
        $stmt = $con->prepare($updateQuery);
        $stmt->bind_param("i", $reviewId);
        
        if ($stmt->execute()) {
            $message = "Review hidden successfully!";
            $messageType = "success";
        } else {
            $message = "Error hiding review.";
            $messageType = "error";
        }
    } elseif ($action === 'delete') {
        $deleteQuery = "DELETE FROM reviews WHERE id = ?";
        $stmt = $con->prepare($deleteQuery);
        $stmt->bind_param("i", $reviewId);
        
        if ($stmt->execute()) {
            $message = "Review deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting review.";
            $messageType = "error";
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$whereClause = '';
if ($filter === 'pending') {
    $whereClause = "WHERE r.status = 'pending'";
} elseif ($filter === 'approved') {
    $whereClause = "WHERE r.status = 'approved'";
} elseif ($filter === 'hidden') {
    $whereClause = "WHERE r.status = 'hidden'";
}

// Get reviews with user information
$reviewsQuery = "SELECT r.*, u.full_name, u.email 
                 FROM reviews r 
                 JOIN users u ON r.user_id = u.id 
                 $whereClause 
                 ORDER BY r.submitted_at DESC";
$reviewsResult = $con->query($reviewsQuery);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_reviews,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reviews,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_reviews,
    SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden_reviews,
    AVG(rating) as average_rating
    FROM reviews";
$statsResult = $con->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Reviews - Nrupatunga Digital Bus Pass System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.5rem; }
        .content { padding: 30px; }
        
        .message { padding: 15px; border-radius: 8px; margin: 20px 0; font-weight: 600; }
        .message.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; text-align: center; }
        .stat-card h3 { margin: 0; font-size: 2rem; }
        .stat-card p { margin: 10px 0 0 0; opacity: 0.9; }
        
        .filters { margin: 20px 0; display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn { padding: 10px 20px; border: none; border-radius: 25px; background: #f8f9fa; color: #333; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .filter-btn.active, .filter-btn:hover { background: #667eea; color: white; }
        
        .reviews-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .reviews-table th, .reviews-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .reviews-table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .reviews-table tr:hover { background: #f8f9fa; }
        
        .rating-stars { color: #ffc107; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-hidden { background: #f8d7da; color: #721c24; }
        
        .action-btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.8rem; font-weight: 600; margin: 2px; transition: all 0.3s ease; }
        .btn-approve { background: #28a745; color: white; }
        .btn-hide { background: #ffc107; color: #333; }
        .btn-delete { background: #dc3545; color: white; }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        
        .review-text { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .review-text:hover { white-space: normal; overflow: visible; }
        
        .back-link { background: #6c757d; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-block; margin: 10px 5px; }
        .back-link:hover { background: #545b62; transform: translateY(-2px); text-decoration: none; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> Manage Reviews</h1>
            <p>Review and moderate user feedback</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $stats['total_reviews']; ?></h3>
                    <p><i class="fas fa-comments"></i> Total Reviews</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['pending_reviews']; ?></h3>
                    <p><i class="fas fa-clock"></i> Pending</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['approved_reviews']; ?></h3>
                    <p><i class="fas fa-check"></i> Approved</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format($stats['average_rating'], 1); ?>/5</h3>
                    <p><i class="fas fa-star"></i> Average Rating</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All Reviews
                </a>
                <a href="?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending (<?php echo $stats['pending_reviews']; ?>)
                </a>
                <a href="?filter=approved" class="filter-btn <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                    <i class="fas fa-check"></i> Approved (<?php echo $stats['approved_reviews']; ?>)
                </a>
                <a href="?filter=hidden" class="filter-btn <?php echo $filter === 'hidden' ? 'active' : ''; ?>">
                    <i class="fas fa-eye-slash"></i> Hidden (<?php echo $stats['hidden_reviews']; ?>)
                </a>
            </div>
            
            <!-- Reviews Table -->
            <table class="reviews-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reviewsResult->num_rows > 0): ?>
                        <?php while ($review = $reviewsResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($review['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($review['email']); ?></small>
                            </td>
                            <td>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? '#ffc107' : '#ddd'; ?>;"></i>
                                    <?php endfor; ?>
                                </div>
                                <small>(<?php echo $review['rating']; ?>/5)</small>
                            </td>
                            <td>
                                <div class="review-text" title="<?php echo htmlspecialchars($review['review_text']); ?>">
                                    "<?php echo htmlspecialchars($review['review_text']); ?>"
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $review['status']; ?>">
                                    <?php echo ucfirst($review['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($review['submitted_at'])); ?><br>
                                <small><?php echo date('g:i A', strtotime($review['submitted_at'])); ?></small>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    
                                    <?php if ($review['status'] !== 'approved'): ?>
                                    <button type="submit" name="action" value="approve" class="action-btn btn-approve" onclick="return confirm('Approve this review?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($review['status'] !== 'hidden'): ?>
                                    <button type="submit" name="action" value="hide" class="action-btn btn-hide" onclick="return confirm('Hide this review?')">
                                        <i class="fas fa-eye-slash"></i> Hide
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="action" value="delete" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i><br>
                                No reviews found for the selected filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="admin-dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                </a>
                <a href="index.php" class="back-link">
                    <i class="fas fa-home"></i> View Homepage
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Add loading states to action buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    button.disabled = true;
                }
            });
        });
        
        // Auto-refresh page every 30 seconds for pending reviews
        if (window.location.search.includes('filter=pending')) {
            setTimeout(() => {
                location.reload();
            }, 30000);
        }
    </script>
</body>
</html>
