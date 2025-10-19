<?php
/**
 * Manage Announcements - Admin Interface
 * Simple interface for managing announcements
 */

session_start();
include('includes/dbconnection.php');

// Simple authentication check (you can enhance this)
$isAdmin = isset($_SESSION['admin_logged_in']) || isset($_GET['admin_access']);

if (!$isAdmin) {
    echo "<h1>Access Denied</h1>";
    echo "<p>Please <a href='admin-login.php'>login as admin</a> to manage announcements.</p>";
    echo "<p>Or use <a href='?admin_access=1'>temporary admin access</a> for demo purposes.</p>";
    exit;
}

// Remove display_order column from announcements table
$con->query("ALTER TABLE announcements DROP COLUMN IF EXISTS display_order");

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_announcement'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $status = $_POST['status'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        if (empty($title) || empty($content)) {
            $message = "Title and content are required";
            $messageType = "error";
        } else {
            $stmt = $con->prepare("INSERT INTO announcements (title, content, status, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $title, $content, $status, $start_date, $end_date);
            
            if ($stmt->execute()) {
                $message = "Announcement added successfully";
                $messageType = "success";
            } else {
                $message = "Error adding announcement";
                $messageType = "error";
            }
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $id = intval($_POST['announcement_id']);
        $query = "UPDATE announcements SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Announcement status updated!";
            $messageType = "success";
        } else {
            $message = "Error updating status: " . $con->error;
            $messageType = "error";
        }
    }
    
    if (isset($_POST['delete_announcement'])) {
        $id = intval($_POST['announcement_id']);
        $query = "DELETE FROM announcements WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Announcement deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting announcement: " . $con->error;
            $messageType = "error";
        }
    }
}

// Get all announcements
$announcements = $con->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Bus Pass Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #007bff; margin-bottom: 30px; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-control:focus { border-color: #007bff; outline: none; }
        textarea.form-control { height: 100px; resize: vertical; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .type-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .type-urgent { background: #fee; color: #dc3545; }
        .type-new { background: #fff3cd; color: #856404; }
        .type-info { background: #d1ecf1; color: #0c5460; }
        .type-success { background: #d4edda; color: #155724; }
        .type-warning { background: #fff3cd; color: #856404; }
        .actions { display: flex; gap: 5px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-bullhorn"></i> Manage Announcements</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Announcement Form -->
        <div class="form-container">
            <h3>Add New Announcement</h3>
            <form method="POST" class="announcement-form">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content">Content:</label>
                    <textarea id="content" name="content" required></textarea>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" required>
                </div>
                <button type="submit" name="add_announcement" class="btn btn-primary">Add Announcement</button>
            </form>
        </div>

        <!-- Announcements List -->
        <div class="announcements-list">
            <h3>Current Announcements</h3>
            <?php if ($announcements->num_rows > 0): ?>
                <?php while ($announcement = $announcements->fetch_assoc()): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            <span class="status-badge <?php echo $announcement['status']; ?>">
                                <?php echo ucfirst($announcement['status']); ?>
                            </span>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                        <div class="announcement-meta">
                            <span>Start: <?php echo date('M d, Y', strtotime($announcement['start_date'])); ?></span>
                            <span>End: <?php echo date('M d, Y', strtotime($announcement['end_date'])); ?></span>
                        </div>
                        <div class="announcement-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <button type="submit" name="delete_announcement" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this announcement?')">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No announcements found.</p>
            <?php endif; ?>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="admin-dashboard.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </a>
        </div>
    </div>
</body>
</html>
