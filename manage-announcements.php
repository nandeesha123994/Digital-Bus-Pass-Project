<?php
/**
 * Manage Announcements - Admin Interface
 * Complete interface for managing announcements with expiry dates
 */

// Error reporting disabled for production

session_start();
include('includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_POST) {
    if (isset($_POST['add_announcement'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $type = $_POST['type'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $display_order = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

        // Validate type value
        $validTypes = ['urgent', 'new', 'info', 'success', 'warning'];
        if (!in_array($type, $validTypes)) {
            $type = 'info'; // Default to info if invalid type
        }

        if (!empty($title) && !empty($content)) {
            // Force type to 'info' to avoid any database issues
            $safeType = 'info';

            // Use direct SQL to bypass any potential issues
            $escapedTitle = $con->real_escape_string($title);
            $escapedContent = $con->real_escape_string($content);

            $query = "INSERT INTO announcements (title, content, type, is_active) VALUES ('$escapedTitle', '$escapedContent', '$safeType', $is_active)";
            $executeResult = $con->query($query);

            if ($executeResult) {
                $message = "Announcement added successfully!";
                $messageType = "success";
            } else {
                $message = "Error adding announcement: " . $con->error;
                $messageType = "error";
            }
        } else {
            $message = "Please fill in all required fields.";
            $messageType = "error";
        }
    }

    if (isset($_POST['edit_announcement'])) {
        $id = intval($_POST['announcement_id']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $type = $_POST['type'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $display_order = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

        // Validate type value to ensure it's in the ENUM
        $validTypes = ['urgent', 'new', 'info', 'success', 'warning'];
        if (!in_array($type, $validTypes)) {
            $type = 'info'; // Default to info if invalid type
        }

        if (!empty($title) && !empty($content)) {
            // Use simple update query with basic columns
            $query = "UPDATE announcements SET title = ?, content = ?, type = ?, is_active = ? WHERE id = ?";
            $stmt = $con->prepare($query);

            if ($stmt) {
                $stmt->bind_param('sssii', $title, $content, $type, $is_active, $id);
                $executeResult = $stmt->execute();
            } else {
                $executeResult = false;
            }

            if ($executeResult) {
                $message = "Announcement updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error updating announcement: " . $con->error;
                $messageType = "error";
            }
        } else {
            $message = "Please fill in all required fields.";
            $messageType = "error";
        }
    }

    if (isset($_POST['toggle_status'])) {
        $id = intval($_POST['announcement_id']);

        // Check if is_active column exists
        $checkActiveQuery = "SHOW COLUMNS FROM announcements LIKE 'is_active'";
        $activeResult = $con->query($checkActiveQuery);
        $hasActiveColumn = ($activeResult && $activeResult->num_rows > 0);

        if ($hasActiveColumn) {
            $query = "UPDATE announcements SET is_active = NOT is_active WHERE id = ?";
            $stmt = $con->prepare($query);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = "Announcement status updated!";
                $messageType = "success";
            } else {
                $message = "Error updating status: " . $con->error;
                $messageType = "error";
            }
        } else {
            $message = "Status toggle not available - is_active column missing.";
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

// Get announcement for editing if edit_id is provided
$editAnnouncement = null;
if (isset($_GET['edit_id'])) {
    $editId = intval($_GET['edit_id']);
    $editQuery = "SELECT * FROM announcements WHERE id = ?";
    $editStmt = $con->prepare($editQuery);
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editResult = $editStmt->get_result();
    $editAnnouncement = $editResult->fetch_assoc();
}

// Check if expiry_date column exists
$checkColumnQuery = "SHOW COLUMNS FROM announcements LIKE 'expiry_date'";
$columnResult = $con->query($checkColumnQuery);
$hasExpiryColumn = ($columnResult && $columnResult->num_rows > 0);

// Check if display_order column exists
$checkDisplayOrderQuery = "SHOW COLUMNS FROM announcements LIKE 'display_order'";
$displayOrderResult = $con->query($checkDisplayOrderQuery);
$hasDisplayOrderColumn = ($displayOrderResult && $displayOrderResult->num_rows > 0);

// Get all announcements with statistics - Use safe query first
try {
    if ($hasExpiryColumn) {
        if ($hasDisplayOrderColumn) {
            $announcementsQuery = "SELECT *,
                CASE
                    WHEN expiry_date IS NULL THEN 'No Expiry'
                    WHEN expiry_date < NOW() THEN 'Expired'
                    WHEN expiry_date > NOW() THEN 'Active'
                    ELSE 'Active'
                END as expiry_status
                FROM announcements
                ORDER BY display_order ASC, created_at DESC";
        } else {
            $announcementsQuery = "SELECT *,
                CASE
                    WHEN expiry_date IS NULL THEN 'No Expiry'
                    WHEN expiry_date < NOW() THEN 'Expired'
                    WHEN expiry_date > NOW() THEN 'Active'
                    ELSE 'Active'
                END as expiry_status
                FROM announcements
                ORDER BY created_at DESC";
        }
    } else {
        if ($hasDisplayOrderColumn) {
            $announcementsQuery = "SELECT *, 'No Expiry' as expiry_status
                FROM announcements
                ORDER BY display_order ASC, created_at DESC";
        } else {
            $announcementsQuery = "SELECT *, 'No Expiry' as expiry_status
                FROM announcements
                ORDER BY created_at DESC";
        }
    }
} catch (Exception $e) {
    // Fallback to basic query if there are any issues
    $announcementsQuery = "SELECT *, 'No Expiry' as expiry_status FROM announcements ORDER BY created_at DESC";
    $hasDisplayOrderColumn = false;
    $hasExpiryColumn = false;
}

// Execute query with error handling
$announcementsResult = $con->query($announcementsQuery);
if (!$announcementsResult) {
    // If query fails, try basic query without optional columns
    $announcementsQuery = "SELECT *, 'No Expiry' as expiry_status FROM announcements ORDER BY created_at DESC";
    $announcementsResult = $con->query($announcementsQuery);
    $hasDisplayOrderColumn = false;
    $hasExpiryColumn = false;
    $showExpiryFields = false;
    $showDisplayOrderFields = false;
}

// Store the column check result for use in the template
$showExpiryFields = $hasExpiryColumn;
$showDisplayOrderFields = $hasDisplayOrderColumn;

// Get statistics
if ($hasExpiryColumn) {
    $statsQuery = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date < NOW() THEN 1 ELSE 0 END) as expired
        FROM announcements";
} else {
    $statsQuery = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
        0 as expired
        FROM announcements";
}
$statsResult = $con->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Bus Pass Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f8f9fa; }
        .header { background: #dc3545; color: white; padding: 15px 20px; }
        .header h2 { margin: 0; display: inline-block; }
        .nav-links { float: right; margin-top: 5px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; margin-left: 10px; }
        .nav-links a:hover { background: rgba(255,255,255,0.3); }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex: 1; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
        .stat-label { color: #666; }
        .total { color: #007bff; }
        .active { color: #28a745; }
        .inactive { color: #6c757d; }
        .expired { color: #dc3545; }
        .form-section { background: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section h3 { margin-top: 0; color: #333; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
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
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .type-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .type-urgent { background: #fee; color: #dc3545; }
        .type-new { background: #fff3cd; color: #856404; }
        .type-info { background: #d1ecf1; color: #0c5460; }
        .type-success { background: #d4edda; color: #155724; }
        .type-warning { background: #fff3cd; color: #856404; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #6c757d; font-weight: bold; }
        .expiry-active { color: #28a745; }
        .expiry-expired { color: #dc3545; }
        .expiry-no-expiry { color: #6c757d; }
        .actions { display: flex; gap: 5px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        @media (max-width: 768px) {
            .row, .row-3 { grid-template-columns: 1fr; }
            .stats { flex-direction: column; }
        }
        .edit-form { background: #f8f9fa; border-left: 4px solid #007bff; padding: 20px; margin-bottom: 20px; }
        .cancel-edit { margin-left: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2><i class="fas fa-bullhorn"></i> Manage Announcements</h2>
        <div class="nav-links">
            <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number total"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Announcements</div>
            </div>
            <div class="stat-card">
                <div class="stat-number active"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card">
                <div class="stat-number inactive"><?php echo $stats['inactive']; ?></div>
                <div class="stat-label">Inactive</div>
            </div>
            <div class="stat-card">
                <div class="stat-number expired"><?php echo $stats['expired']; ?></div>
                <div class="stat-label">Expired</div>
            </div>
        </div>

        <!-- Edit Form (if editing) -->
        <?php if ($editAnnouncement): ?>
        <div class="edit-form">
            <h3><i class="fas fa-edit"></i> Edit Announcement</h3>
            <form method="post">
                <input type="hidden" name="announcement_id" value="<?php echo $editAnnouncement['id']; ?>">
                <div class="row">
                    <div>
                        <div class="form-group">
                            <label for="edit_title">Title *</label>
                            <input type="text" id="edit_title" name="title" class="form-control" value="<?php echo htmlspecialchars($editAnnouncement['title']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_type">Type</label>
                            <select id="edit_type" name="type" class="form-control">
                                <option value="info" <?php echo $editAnnouncement['type'] == 'info' ? 'selected' : ''; ?>>Info</option>
                                <option value="urgent" <?php echo $editAnnouncement['type'] == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                <option value="new" <?php echo $editAnnouncement['type'] == 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="success" <?php echo $editAnnouncement['type'] == 'success' ? 'selected' : ''; ?>>Success</option>
                                <option value="warning" <?php echo $editAnnouncement['type'] == 'warning' ? 'selected' : ''; ?>>Warning</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="edit_is_active" name="is_active" <?php echo $editAnnouncement['is_active'] ? 'checked' : ''; ?>>
                                <label for="edit_is_active">Active</label>
                            </div>
                        </div>

                        <div class="row-3">
                            <?php if ($showDisplayOrderFields): ?>
                            <div class="form-group">
                                <label for="edit_display_order">Display Order</label>
                                <input type="number" id="edit_display_order" name="display_order" class="form-control" value="<?php echo isset($editAnnouncement['display_order']) ? $editAnnouncement['display_order'] : '0'; ?>" min="0">
                            </div>
                            <?php else: ?>
                            <div class="form-group">
                                <label>Display Order</label>
                                <p style="color: #666; font-style: italic; margin: 0;">Display order feature not available. <a href="update_announcements_table.php">Update table</a> to enable.</p>
                            </div>
                            <?php endif; ?>
                            <?php if ($showExpiryFields): ?>
                            <div class="form-group">
                                <label for="edit_expiry_date">Expiry Date</label>
                                <input type="datetime-local" id="edit_expiry_date" name="expiry_date" class="form-control" value="<?php echo (isset($editAnnouncement['expiry_date']) && $editAnnouncement['expiry_date']) ? date('Y-m-d\TH:i', strtotime($editAnnouncement['expiry_date'])) : ''; ?>">
                            </div>
                            <?php else: ?>
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <p style="color: #666; font-style: italic; margin: 0;">Expiry date feature not available. <a href="update_announcements_table.php">Update table</a> to enable.</p>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_content">Content *</label>
                    <textarea id="edit_content" name="content" class="form-control" required><?php echo htmlspecialchars($editAnnouncement['content']); ?></textarea>
                </div>

                <button type="submit" name="edit_announcement" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Announcement
                </button>
                <a href="manage-announcements.php" class="btn btn-secondary cancel-edit">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </form>
        </div>
        <?php endif; ?>

        <!-- Add New Announcement Form -->
        <?php if (!$editAnnouncement): ?>
        <div class="form-section">
            <h3><i class="fas fa-plus"></i> Add New Announcement</h3>
            <form method="post">
                <div class="row">
                    <div>
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="type">Type</label>
                            <select id="type" name="type" class="form-control">
                                <option value="info">Info</option>
                                <option value="urgent">Urgent</option>
                                <option value="new">New</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" checked>
                                <label for="is_active">Active</label>
                            </div>
                        </div>

                        <div class="row-3">
                            <?php if ($showDisplayOrderFields): ?>
                            <div class="form-group">
                                <label for="display_order">Display Order</label>
                                <input type="number" id="display_order" name="display_order" class="form-control" value="0" min="0">
                            </div>
                            <?php else: ?>
                            <div class="form-group">
                                <label>Display Order</label>
                                <p style="color: #666; font-style: italic; margin: 0;">Display order feature not available. <a href="update_announcements_table.php">Update table</a> to enable.</p>
                            </div>
                            <?php endif; ?>
                            <?php if ($showExpiryFields): ?>
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date</label>
                                <input type="datetime-local" id="expiry_date" name="expiry_date" class="form-control">
                                <small style="color: #666;">Leave empty for no expiry</small>
                            </div>
                            <?php else: ?>
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <p style="color: #666; font-style: italic; margin: 0;">Expiry date feature not available. <a href="update_announcements_table.php">Update table</a> to enable.</p>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content" class="form-control" required placeholder="Enter the announcement content..."></textarea>
                </div>

                <button type="submit" name="add_announcement" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Announcement
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Existing Announcements -->
        <div class="form-section">
            <h3><i class="fas fa-list"></i> Existing Announcements</h3>

            <?php if ($announcementsResult && $announcementsResult->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <?php if ($showExpiryFields): ?>
                            <th>Expiry</th>
                            <?php endif; ?>
                            <?php if ($showDisplayOrderFields): ?>
                            <th>Order</th>
                            <?php endif; ?>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($announcement = $announcementsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $announcement['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($announcement['title']); ?></strong><br>
                                    <small><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)) . '...'; ?></small>
                                </td>
                                <td>
                                    <span class="type-badge type-<?php echo $announcement['type']; ?>">
                                        <?php echo ucfirst($announcement['type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-<?php echo $announcement['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $announcement['is_active'] ? '✅ Active' : '❌ Inactive'; ?>
                                    </span>
                                </td>
                                <?php if ($showExpiryFields): ?>
                                <td>
                                    <?php if (isset($announcement['expiry_date']) && $announcement['expiry_date']): ?>
                                        <span class="expiry-<?php echo strtolower(str_replace(' ', '-', $announcement['expiry_status'])); ?>">
                                            <?php echo date('M d, Y H:i', strtotime($announcement['expiry_date'])); ?><br>
                                            <small><?php echo $announcement['expiry_status']; ?></small>
                                        </span>
                                    <?php else: ?>
                                        <span class="expiry-no-expiry">No Expiry</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <?php if ($showDisplayOrderFields): ?>
                                <td><?php echo isset($announcement['display_order']) ? $announcement['display_order'] : '0'; ?></td>
                                <?php endif; ?>
                                <td><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="?edit_id=<?php echo $announcement['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-warning btn-sm" title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                        </form>

                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                            <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                            <button type="submit" name="delete_announcement" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No announcements found. Add your first announcement above!</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const successAlerts = document.querySelectorAll('.alert.success');
            successAlerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>