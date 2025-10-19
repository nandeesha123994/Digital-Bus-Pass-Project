<?php
/**
 * Test Review Submission and Display
 * This script allows you to test the complete review workflow
 */

session_start();
include('includes/dbconnection.php');

$message = '';
$messageType = '';

// Handle review submission
if (isset($_POST['submit_review'])) {
    try {
        $userId = $_POST['user_id'];
        $reviewText = trim($_POST['review_text']);
        $rating = intval($_POST['rating']);
        
        if (empty($reviewText) || $rating < 1 || $rating > 5) {
            throw new Exception("Please provide valid review text and rating (1-5)");
        }
        
        // Insert review
        $insertQuery = "INSERT INTO instant_reviews (user_id, review_text, rating, status) VALUES (?, ?, ?, 'active')";
        $stmt = $con->prepare($insertQuery);
        $stmt->bind_param('isi', $userId, $reviewText, $rating);
        
        if ($stmt->execute()) {
            $message = "Review submitted successfully! Review ID: " . $con->insert_id;
            $messageType = "success";
        } else {
            throw new Exception("Failed to submit review: " . $con->error);
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}

// Get users for dropdown
$usersQuery = "SELECT id, full_name, email FROM users ORDER BY full_name LIMIT 10";
$usersResult = $con->query($usersQuery);

// Get recent reviews
$reviewsQuery = "SELECT ir.id, ir.review_text, ir.rating, ir.created_at, ir.status, u.full_name as username
                 FROM instant_reviews ir
                 JOIN users u ON ir.user_id = u.id
                 ORDER BY ir.created_at DESC
                 LIMIT 10";
$reviewsResult = $con->query($reviewsQuery);

// Test homepage query
$homepageQuery = "SELECT ir.review_text, ir.rating, ir.created_at, u.full_name as username
                  FROM instant_reviews ir
                  JOIN users u ON ir.user_id = u.id
                  WHERE ir.status = 'active'
                  ORDER BY ir.created_at DESC
                  LIMIT 5";
$homepageResult = $con->query($homepageQuery);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Review Submission - Bus Pass Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            line-height: 1.6;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h1, h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        select, textarea, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .rating-input {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .rating-input input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        .btn:hover {
            background: #0056b3;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .stars {
            color: #ffc107;
        }
        .status-active {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        .status-hidden {
            background: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>🧪 Test Review Submission & Display</h1>
    
    <!-- Submit New Review -->
    <div class="container">
        <h2>📝 Submit Test Review</h2>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="user_id">Select User:</label>
                <select name="user_id" id="user_id" required>
                    <option value="">Choose a user...</option>
                    <?php if ($usersResult && $usersResult->num_rows > 0): ?>
                        <?php while ($user = $usersResult->fetch_assoc()): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['full_name']) . ' (' . htmlspecialchars($user['email']) . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="">No users found</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="rating">Rating:</label>
                <div class="rating-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label style="display: flex; align-items: center; margin-bottom: 0;">
                            <input type="radio" name="rating" value="<?php echo $i; ?>" required>
                            <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="review_text">Review Text:</label>
                <textarea name="review_text" id="review_text" placeholder="Write your review here..." required></textarea>
            </div>
            
            <button type="submit" name="submit_review" class="btn">Submit Test Review</button>
        </form>
    </div>
    
    <!-- Recent Reviews -->
    <div class="container">
        <h2>📋 Recent Reviews (All)</h2>
        
        <?php if ($reviewsResult && $reviewsResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($review = $reviewsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $review['id']; ?></td>
                            <td><?php echo htmlspecialchars($review['username']); ?></td>
                            <td>
                                <span class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                    <?php endfor; ?>
                                </span>
                                (<?php echo $review['rating']; ?>/5)
                            </td>
                            <td><?php echo htmlspecialchars(substr($review['review_text'], 0, 100)) . (strlen($review['review_text']) > 100 ? '...' : ''); ?></td>
                            <td>
                                <span class="status-<?php echo $review['status']; ?>">
                                    <?php echo ucfirst($review['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($review['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666; padding: 40px;">No reviews found in database.</p>
        <?php endif; ?>
    </div>
    
    <!-- Homepage Query Test -->
    <div class="container">
        <h2>🏠 Homepage Query Test (Active Reviews Only)</h2>
        
        <?php if ($homepageResult && $homepageResult->num_rows > 0): ?>
            <p style="color: green; font-weight: 600;">✅ Homepage query successful! Found <?php echo $homepageResult->num_rows; ?> active reviews.</p>
            
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($review = $homepageResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($review['username']); ?></td>
                            <td>
                                <span class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                    <?php endfor; ?>
                                </span>
                                (<?php echo $review['rating']; ?>/5)
                            </td>
                            <td><?php echo htmlspecialchars($review['review_text']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($review['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h4>✅ Reviews Should Now Display on Homepage!</h4>
                <p>The homepage query is working correctly. Reviews should now appear on the homepage.</p>
                <p><a href="index.php" style="color: #155724; font-weight: bold;">🔗 Test Homepage Now</a></p>
            </div>
        <?php else: ?>
            <p style="color: red; font-weight: 600;">❌ No active reviews found for homepage display.</p>
            <p>This means either:</p>
            <ul>
                <li>No reviews have been submitted yet</li>
                <li>All reviews have status 'hidden' instead of 'active'</li>
                <li>There's an issue with the database query</li>
            </ul>
            <p><strong>Solution:</strong> Submit a test review above to create active reviews for homepage display.</p>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div style="text-align: center; margin: 30px 0;">
        <a href="index.php" style="background: #28a745; color: white; padding: 12px 25px; border-radius: 5px; text-decoration: none; margin: 0 10px;">🏠 Test Homepage</a>
        <a href="instant-reviews-display.php" style="background: #6f42c1; color: white; padding: 12px 25px; border-radius: 5px; text-decoration: none; margin: 0 10px;">📋 All Reviews Page</a>
        <a href="review-system-diagnostic.php" style="background: #007bff; color: white; padding: 12px 25px; border-radius: 5px; text-decoration: none; margin: 0 10px;">🔍 Run Diagnostic</a>
    </div>
</body>
</html>
