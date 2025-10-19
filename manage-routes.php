<?php
session_start();
include('includes/dbconnection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Check if routes table exists
$tableCheck = "SHOW TABLES LIKE 'routes'";
$tableResult = $con->query($tableCheck);

if ($tableResult->num_rows == 0) {
    // Routes table doesn't exist, redirect to setup
    header('Location: setup-routes-table.php');
    exit();
}

$message = '';
$messageType = '';

// Handle Add Route
if (isset($_POST['add_route'])) {
    $routeName = trim($_POST['route_name']);
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);
    $distance = !empty($_POST['distance_km']) ? floatval($_POST['distance_km']) : null;
    $duration = trim($_POST['estimated_duration']);

    if (!empty($routeName) && !empty($source) && !empty($destination)) {
        // Auto-generate route ID
        $lastRouteQuery = "SELECT route_id FROM routes ORDER BY id DESC LIMIT 1";
        $lastRouteResult = $con->query($lastRouteQuery);
        
        if ($lastRouteResult && $lastRouteResult->num_rows > 0) {
            $lastRoute = $lastRouteResult->fetch_assoc();
            $lastNumber = intval(substr($lastRoute['route_id'], 1));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        $routeId = 'R' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // Check if route already exists
        $checkQuery = "SELECT id FROM routes WHERE source = ? AND destination = ?";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bind_param("ss", $source, $destination);
        $checkStmt->execute();

        if ($checkStmt->get_result()->num_rows > 0) {
            $message = "Route from $source to $destination already exists!";
            $messageType = "error";
        } else {
            $insertQuery = "INSERT INTO routes (route_id, route_name, source, destination, distance_km, estimated_duration) VALUES (?, ?, ?, ?, ?, ?)";
            $insertStmt = $con->prepare($insertQuery);
            $insertStmt->bind_param("ssssds", $routeId, $routeName, $source, $destination, $distance, $duration);

            if ($insertStmt->execute()) {
                $message = "Route added successfully with ID: $routeId";
                $messageType = "success";
            } else {
                $message = "Error adding route: " . $con->error;
                $messageType = "error";
            }
        }
    } else {
        $message = "Route name, source, and destination are required!";
        $messageType = "error";
    }
}

// Handle Edit Route
if (isset($_POST['edit_route'])) {
    $routeId = $_POST['route_id'];
    $routeName = trim($_POST['route_name']);
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);
    $distance = !empty($_POST['distance_km']) ? floatval($_POST['distance_km']) : null;
    $duration = trim($_POST['estimated_duration']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!empty($routeName) && !empty($source) && !empty($destination)) {
        $updateQuery = "UPDATE routes SET route_name = ?, source = ?, destination = ?, distance_km = ?, estimated_duration = ?, is_active = ? WHERE id = ?";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bind_param("sssdsis", $routeName, $source, $destination, $distance, $duration, $isActive, $routeId);

        if ($updateStmt->execute()) {
            $message = "Route updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating route: " . $con->error;
            $messageType = "error";
        }
    } else {
        $message = "Route name, source, and destination are required!";
        $messageType = "error";
    }
}

// Handle Delete Route
if (isset($_POST['delete_route'])) {
    $routeId = $_POST['route_id'];
    
    $deleteQuery = "DELETE FROM routes WHERE id = ?";
    $deleteStmt = $con->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $routeId);

    if ($deleteStmt->execute()) {
        $message = "Route deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting route: " . $con->error;
        $messageType = "error";
    }
}

// Get all routes
$routesQuery = "SELECT * FROM routes ORDER BY route_id ASC";
$routesResult = $con->query($routesQuery);

// Get unique sources and destinations for dropdowns
$sourcesQuery = "SELECT DISTINCT source FROM routes WHERE is_active = 1 ORDER BY source";
$sourcesResult = $con->query($sourcesQuery);
$sources = [];
while ($row = $sourcesResult->fetch_assoc()) {
    $sources[] = $row['source'];
}

$destinationsQuery = "SELECT DISTINCT destination FROM routes WHERE is_active = 1 ORDER BY destination";
$destinationsResult = $con->query($destinationsQuery);
$destinations = [];
while ($row = $destinationsResult->fetch_assoc()) {
    $destinations[] = $row['destination'];
}

// Get statistics
$totalRoutesQuery = "SELECT COUNT(*) as total FROM routes";
$activeRoutesQuery = "SELECT COUNT(*) as active FROM routes WHERE is_active = 1";
$totalRoutes = $con->query($totalRoutesQuery)->fetch_assoc()['total'];
$activeRoutes = $con->query($activeRoutesQuery)->fetch_assoc()['active'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - Bus Pass Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header h1 {
            color: #2d3748;
            font-weight: 700;
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-links a {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #4a5568;
            font-weight: 500;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 30px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3748;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
            position: sticky;
            top: 0;
        }

        .table tr:hover {
            background: #f7fafc;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-danger {
            background: #fed7d7;
            color: #742a2a;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .message.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 30px;
        }

        .close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .nav-links {
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .table th,
            .table td {
                padding: 10px 8px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-route"></i> Route Management</h1>
            <div class="nav-links">
                <a href="admin-dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="manage-categories.php"><i class="fas fa-tags"></i> Categories</a>
                <a href="manage-announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
                <a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalRoutes; ?></div>
                <div class="stat-label">Total Routes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $activeRoutes; ?></div>
                <div class="stat-label">Active Routes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($sources); ?></div>
                <div class="stat-label">Source Locations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($destinations); ?></div>
                <div class="stat-label">Destinations</div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Route -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus"></i> Add New Route
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="route_name">Route Name:</label>
                            <input type="text" id="route_name" name="route_name" class="form-control" required
                                   placeholder="e.g., City Center Express">
                        </div>
                        <div class="form-group">
                            <label for="source">Source:</label>
                            <input type="text" id="source" name="source" class="form-control" required
                                   placeholder="e.g., Bangalore Central" list="source-list">
                            <datalist id="source-list">
                                <?php foreach ($sources as $source): ?>
                                    <option value="<?php echo htmlspecialchars($source); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="destination">Destination:</label>
                            <input type="text" id="destination" name="destination" class="form-control" required
                                   placeholder="e.g., Electronic City" list="destination-list">
                            <datalist id="destination-list">
                                <?php foreach ($destinations as $destination): ?>
                                    <option value="<?php echo htmlspecialchars($destination); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="distance_km">Distance (km):</label>
                            <input type="number" id="distance_km" name="distance_km" class="form-control"
                                   step="0.1" min="0" placeholder="e.g., 25.5">
                        </div>
                        <div class="form-group">
                            <label for="estimated_duration">Estimated Duration:</label>
                            <input type="text" id="estimated_duration" name="estimated_duration" class="form-control"
                                   placeholder="e.g., 45 mins">
                        </div>
                    </div>
                    <button type="submit" name="add_route" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Route
                    </button>
                </form>
            </div>
        </div>

        <!-- Routes List -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Routes
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Route ID</th>
                                <th>Route Name</th>
                                <th>Source</th>
                                <th>Destination</th>
                                <th>Distance</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($routesResult && $routesResult->num_rows > 0): ?>
                                <?php while ($route = $routesResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($route['route_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                        <td><?php echo htmlspecialchars($route['source']); ?></td>
                                        <td><?php echo htmlspecialchars($route['destination']); ?></td>
                                        <td>
                                            <?php if ($route['distance_km']): ?>
                                                <?php echo number_format($route['distance_km'], 1); ?> km
                                            <?php else: ?>
                                                <span style="color: #999;">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($route['estimated_duration']): ?>
                                                <?php echo htmlspecialchars($route['estimated_duration']); ?>
                                            <?php else: ?>
                                                <span style="color: #999;">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($route['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-warning btn-sm" onclick="editRoute(<?php echo $route['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteRoute(<?php echo $route['id']; ?>, '<?php echo htmlspecialchars($route['route_id']); ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                                        <i class="fas fa-route" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i><br>
                                        No routes found. Add your first route above!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Route Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Route</h3>
                <button class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post" id="editForm">
                    <input type="hidden" name="route_id" id="edit_route_id">

                    <div class="form-group">
                        <label for="edit_route_name">Route Name:</label>
                        <input type="text" id="edit_route_name" name="route_name" class="form-control" required>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_source">Source:</label>
                            <input type="text" id="edit_source" name="source" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_destination">Destination:</label>
                            <input type="text" id="edit_destination" name="destination" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_distance_km">Distance (km):</label>
                            <input type="number" id="edit_distance_km" name="distance_km" class="form-control" step="0.1" min="0">
                        </div>
                        <div class="form-group">
                            <label for="edit_estimated_duration">Estimated Duration:</label>
                            <input type="text" id="edit_estimated_duration" name="estimated_duration" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" id="edit_is_active" style="margin-right: 8px;">
                            Route is Active
                        </label>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" name="edit_route" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Route
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
                <button class="close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete route <strong id="deleteRouteId"></strong>?</p>
                <p style="color: #e53e3e; font-size: 0.9rem;">This action cannot be undone.</p>

                <form method="post" id="deleteForm" style="margin-top: 20px;">
                    <input type="hidden" name="route_id" id="delete_route_id">
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                        <button type="submit" name="delete_route" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Routes data for JavaScript
        const routesData = <?php
            $routesResult = $con->query("SELECT * FROM routes ORDER BY route_id ASC");
            $routes = [];
            while ($route = $routesResult->fetch_assoc()) {
                $routes[] = $route;
            }
            echo json_encode($routes);
        ?>;

        // Edit Route Modal Functions
        function editRoute(routeId) {
            const route = routesData.find(r => r.id == routeId);
            if (!route) return;

            document.getElementById('edit_route_id').value = route.id;
            document.getElementById('edit_route_name').value = route.route_name;
            document.getElementById('edit_source').value = route.source;
            document.getElementById('edit_destination').value = route.destination;
            document.getElementById('edit_distance_km').value = route.distance_km || '';
            document.getElementById('edit_estimated_duration').value = route.estimated_duration || '';
            document.getElementById('edit_is_active').checked = route.is_active == 1;

            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Delete Route Modal Functions
        function deleteRoute(routeId, routeIdDisplay) {
            document.getElementById('delete_route_id').value = routeId;
            document.getElementById('deleteRouteId').textContent = routeIdDisplay;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');

            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }

        // Auto-hide messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => {
                        message.style.display = 'none';
                    }, 300);
                }, 5000);
            });

            // Add search functionality to table
            addTableSearch();
        });

        // Table Search Functionality
        function addTableSearch() {
            const table = document.querySelector('.table');
            if (!table) return;

            // Create search input
            const searchContainer = document.createElement('div');
            searchContainer.style.marginBottom = '20px';
            searchContainer.innerHTML = `
                <input type="text" id="routeSearch" class="form-control"
                       placeholder="🔍 Search routes by ID, name, source, or destination..."
                       style="max-width: 400px;">
            `;

            table.parentNode.insertBefore(searchContainer, table);

            // Add search functionality
            document.getElementById('routeSearch').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.style.borderColor = '#e53e3e';
                            isValid = false;
                        } else {
                            field.style.borderColor = '#e2e8f0';
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            });
        });

        // Auto-complete functionality for source and destination
        function setupAutoComplete() {
            const sourceInput = document.getElementById('source');
            const destinationInput = document.getElementById('destination');

            if (sourceInput && destinationInput) {
                // Get unique sources and destinations from existing routes
                const sources = [...new Set(routesData.map(r => r.source))].sort();
                const destinations = [...new Set(routesData.map(r => r.destination))].sort();

                // Update datalists
                const sourceList = document.getElementById('source-list');
                const destinationList = document.getElementById('destination-list');

                if (sourceList) {
                    sourceList.innerHTML = sources.map(s => `<option value="${s}">`).join('');
                }

                if (destinationList) {
                    destinationList.innerHTML = destinations.map(d => `<option value="${d}">`).join('');
                }
            }
        }

        // Initialize auto-complete on page load
        document.addEventListener('DOMContentLoaded', setupAutoComplete);
    </script>
</body>
</html>
