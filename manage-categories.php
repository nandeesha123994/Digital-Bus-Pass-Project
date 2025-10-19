<?php
session_start();
include('includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle Add Category
if (isset($_POST['add_category'])) {
    $categoryName = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (!empty($categoryName)) {
        $checkQuery = "SELECT id FROM categories WHERE category_name = ?";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bind_param("s", $categoryName);
        $checkStmt->execute();

        if ($checkStmt->get_result()->num_rows > 0) {
            $message = "Category already exists!";
            $messageType = "error";
        } else {
            $insertQuery = "INSERT INTO categories (category_name, description) VALUES (?, ?)";
            $insertStmt = $con->prepare($insertQuery);
            $insertStmt->bind_param("ss", $categoryName, $description);

            if ($insertStmt->execute()) {
                $message = "Category added successfully!";
                $messageType = "success";
            } else {
                $message = "Error adding category: " . $con->error;
                $messageType = "error";
            }
        }
    } else {
        $message = "Category name is required!";
        $messageType = "error";
    }
}

// Handle Edit Category
if (isset($_POST['edit_category'])) {
    $categoryId = $_POST['category_id'];
    $categoryName = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!empty($categoryName)) {
        $checkQuery = "SELECT id FROM categories WHERE category_name = ? AND id != ?";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bind_param("si", $categoryName, $categoryId);
        $checkStmt->execute();

        if ($checkStmt->get_result()->num_rows > 0) {
            $message = "Category name already exists!";
            $messageType = "error";
        } else {
            $updateQuery = "UPDATE categories SET category_name = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?";
            $updateStmt = $con->prepare($updateQuery);
            $updateStmt->bind_param("ssii", $categoryName, $description, $isActive, $categoryId);

            if ($updateStmt->execute()) {
                $message = "Category updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error updating category: " . $con->error;
                $messageType = "error";
            }
        }
    } else {
        $message = "Category name is required!";
        $messageType = "error";
    }
}

// Handle Delete Category
if (isset($_POST['delete_category'])) {
    $categoryId = $_POST['category_id'];

    // Check if category is being used in applications
    $usageQuery = "SELECT COUNT(*) as count FROM bus_pass_applications WHERE category_id = ?";
    $usageStmt = $con->prepare($usageQuery);
    $usageStmt->bind_param("i", $categoryId);
    $usageStmt->execute();
    $usageCount = $usageStmt->get_result()->fetch_assoc()['count'];

    if ($usageCount > 0) {
        $message = "Cannot delete category. It is being used by $usageCount application(s). You can deactivate it instead.";
        $messageType = "error";
    } else {
        $deleteQuery = "DELETE FROM categories WHERE id = ?";
        $deleteStmt = $con->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $categoryId);

        if ($deleteStmt->execute()) {
            $message = "Category deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting category: " . $con->error;
            $messageType = "error";
        }
    }
}

// Check if is_active column exists
$columnsQuery = "SHOW COLUMNS FROM categories LIKE 'is_active'";
$columnExists = $con->query($columnsQuery)->num_rows > 0;

// Get all categories with conditional is_active selection
if ($columnExists) {
    $categoriesQuery = "SELECT c.*,
                               c.is_active,
                               COUNT(ba.id) as application_count
                        FROM categories c
                        LEFT JOIN bus_pass_applications ba ON c.id = ba.category_id
                        GROUP BY c.id
                        ORDER BY c.created_at DESC";
} else {
    $categoriesQuery = "SELECT c.*,
                               1 as is_active,
                               COUNT(ba.id) as application_count
                        FROM categories c
                        LEFT JOIN bus_pass_applications ba ON c.id = ba.category_id
                        GROUP BY c.id
                        ORDER BY c.created_at DESC";
}
$categoriesResult = $con->query($categoriesQuery);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; text-align: center; }
        .nav { background: #343a40; padding: 1rem; }
        .nav a { color: white; text-decoration: none; margin-right: 2rem; padding: 0.5rem 1rem; border-radius: 5px; transition: background 0.3s; }
        .nav a:hover { background: rgba(255,255,255,0.1); }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; border-radius: 10px 10px 0 0; }
        .card-body { padding: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; margin-right: 0.5rem; transition: all 0.3s; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .table tr:hover { background: #f8f9fa; }
        .message { padding: 1rem; margin-bottom: 1rem; border-radius: 5px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.75rem; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 0; width: 90%; max-width: 500px; border-radius: 10px; }
        .modal-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; border-radius: 10px 10px 0 0; }
        .modal-body { padding: 1.5rem; }
        .close { color: white; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { opacity: 0.7; }
        .row { display: flex; flex-wrap: wrap; margin: -0.5rem; }
        .col-md-6 { flex: 0 0 50%; padding: 0.5rem; }
        .col-md-12 { flex: 0 0 100%; padding: 0.5rem; }
        @media (max-width: 768px) {
            .col-md-6 { flex: 0 0 100%; }
            .nav a { display: block; margin: 0.25rem 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-tags"></i> Manage Transport Categories</h1>
        <p>Add, edit, and manage bus transport categories</p>
    </div>

    <div class="nav">
        <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage-announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
        <a href="admin-activity-log.php"><i class="fas fa-history"></i> Activity Log</a>
        <a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Category -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus"></i> Add New Category</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_name">Category Name:</label>
                                <input type="text" id="category_name" name="category_name" class="form-control" required placeholder="e.g., KSRTC, BMTC">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="description">Description:</label>
                                <input type="text" id="description" name="description" class="form-control" placeholder="Brief description of the transport category">
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Transport Categories</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Applications</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($category = $categoriesResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td>
                                <?php if (isset($category['is_active']) && $category['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $category['application_count']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($category['application_count'] == 0): ?>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="delete_category" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Category</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="post" id="editForm">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="form-group">
                        <label for="edit_category_name">Category Name:</label>
                        <input type="text" id="edit_category_name" name="category_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description:</label>
                        <input type="text" id="edit_description" name="description" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" id="edit_is_active"> Active
                        </label>
                    </div>
                    <button type="submit" name="edit_category" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Category
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editCategory(category) {
            document.getElementById('edit_category_id').value = category.id;
            document.getElementById('edit_category_name').value = category.category_name;
            document.getElementById('edit_description').value = category.description || '';
            document.getElementById('edit_is_active').checked = (category.is_active == 1 || category.is_active === undefined);
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
