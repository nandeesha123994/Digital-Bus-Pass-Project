<?php
session_start();
include('includes/dbconnection.php');
include('includes/config.php');
include('includes/admin-logger.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Initialize admin logger
AdminLogger::init($con);

// Handle pagination and filtering
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 25; // Records per page
$offset = ($page - 1) * $limit;

// Build filters
$filters = [];
if (!empty($_GET['admin_filter'])) {
    $filters['admin_id'] = $_GET['admin_filter'];
}
if (!empty($_GET['application_filter'])) {
    $filters['application_id'] = $_GET['application_filter'];
}
if (!empty($_GET['action_filter'])) {
    $filters['action'] = $_GET['action_filter'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Get activity logs
$logData = AdminLogger::getActivityLogs($limit, $offset, $filters);
$logs = $logData['logs'];
$totalLogs = $logData['total'];
$totalPages = ceil($totalLogs / $limit);

// Get activity statistics
$stats = AdminLogger::getActivityStats();

// Check if filters are active
$hasFilters = !empty($filters);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Log - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .header .nav-links {
            display: flex;
            gap: 15px;
        }

        .header .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .header .nav-links a:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        .content {
            padding: 30px;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid #007bff;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.today {
            border-left-color: #28a745;
        }

        .stat-card.week {
            border-left-color: #ffc107;
        }

        .stat-card.month {
            border-left-color: #dc3545;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filters */
        .filters-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .filters-toggle {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .filters-toggle:hover {
            background: #0056b3;
        }

        .filters-content {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .filters-content.active {
            display: grid;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-filter {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        /* Activity Log Table */
        .log-table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .log-table {
            width: 100%;
            border-collapse: collapse;
        }

        .log-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .log-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        .log-table tr:hover {
            background: #f8f9fa;
        }

        .log-table tr:last-child td {
            border-bottom: none;
        }

        /* Action badges */
        .action-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-status-update {
            background: #e3f2fd;
            color: #1976d2;
        }

        .action-bulk {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .action-delete {
            background: #ffebee;
            color: #d32f2f;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 25px;
            gap: 10px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #007bff;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #007bff;
            color: white;
        }

        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination .disabled {
            color: #6c757d;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }

            .header {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
            }

            .content {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters-content {
                grid-template-columns: 1fr;
            }

            .log-table-container {
                overflow-x: auto;
            }

            .log-table {
                min-width: 800px;
            }
        }

        .no-logs {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-logs i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }

        .results-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }

        .results-info i {
            color: #007bff;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-history"></i> Activity Log</h1>
            <div class="nav-links">
                <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage-announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
                <a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="content">
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card today">
                    <div class="stat-number"><?php echo $stats['today'] ?? 0; ?></div>
                    <div class="stat-label">Actions Today</div>
                </div>
                <div class="stat-card week">
                    <div class="stat-number"><?php echo $stats['week'] ?? 0; ?></div>
                    <div class="stat-label">Actions This Week</div>
                </div>
                <div class="stat-card month">
                    <div class="stat-number"><?php echo $stats['month'] ?? 0; ?></div>
                    <div class="stat-label">Actions This Month</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalLogs; ?></div>
                    <div class="stat-label">Total Actions</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <div class="filters-header">
                    <h3><i class="fas fa-filter"></i> Filter Activity Log</h3>
                    <button class="filters-toggle" onclick="toggleFilters()">
                        <i class="fas fa-chevron-down" id="filter-icon"></i> Show Filters
                    </button>
                </div>

                <form method="GET" id="filter-form">
                    <div class="filters-content" id="filters-content">
                        <div class="filter-group">
                            <label for="admin_filter">Admin</label>
                            <input type="text" name="admin_filter" id="admin_filter"
                                   placeholder="Search by admin ID or name..."
                                   value="<?php echo isset($_GET['admin_filter']) ? htmlspecialchars($_GET['admin_filter']) : ''; ?>">
                        </div>

                        <div class="filter-group">
                            <label for="application_filter">Application ID</label>
                            <input type="number" name="application_filter" id="application_filter"
                                   placeholder="Enter application ID..."
                                   value="<?php echo isset($_GET['application_filter']) ? htmlspecialchars($_GET['application_filter']) : ''; ?>">
                        </div>

                        <div class="filter-group">
                            <label for="action_filter">Action Type</label>
                            <select name="action_filter" id="action_filter">
                                <option value="">All Actions</option>
                                <option value="Status Update" <?php echo (isset($_GET['action_filter']) && $_GET['action_filter'] === 'Status Update') ? 'selected' : ''; ?>>Status Update</option>
                                <option value="Bulk Action" <?php echo (isset($_GET['action_filter']) && $_GET['action_filter'] === 'Bulk Action') ? 'selected' : ''; ?>>Bulk Action</option>
                                <option value="Application Deleted" <?php echo (isset($_GET['action_filter']) && $_GET['action_filter'] === 'Application Deleted') ? 'selected' : ''; ?>>Application Deleted</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="date_from">Date From</label>
                            <input type="date" name="date_from" id="date_from"
                                   value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                        </div>

                        <div class="filter-group">
                            <label for="date_to">Date To</label>
                            <input type="date" name="date_to" id="date_to"
                                   value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="button" class="btn-filter btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear All
                        </button>
                        <button type="submit" class="btn-filter btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results Info -->
            <?php if ($hasFilters): ?>
                <div class="results-info">
                    <i class="fas fa-info-circle"></i>
                    Showing <strong><?php echo count($logs); ?></strong> of <strong><?php echo $totalLogs; ?></strong> activity log entries based on your filters.
                    <a href="admin-activity-log.php" style="color: #007bff; text-decoration: none; margin-left: 10px;">
                        <i class="fas fa-times"></i> Clear all filters
                    </a>
                </div>
            <?php endif; ?>

            <!-- Activity Log Table -->
            <div class="log-table-container">
                <?php if (!empty($logs)): ?>
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Admin</th>
                                <th>Application</th>
                                <th>Action</th>
                                <th>Status Change</th>
                                <th>Remarks</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M d, Y', strtotime($log['timestamp'])); ?></strong><br>
                                    <small style="color: #666;"><?php echo date('g:i A', strtotime($log['timestamp'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['admin_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($log['admin_id'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td>
                                    <strong>#<?php echo $log['application_id']; ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($log['applicant_name'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $actionClass = 'action-status-update';
                                    if (strpos($log['action'], 'Bulk') !== false) {
                                        $actionClass = 'action-bulk';
                                    } elseif (strpos($log['action'], 'Deleted') !== false) {
                                        $actionClass = 'action-delete';
                                    }
                                    ?>
                                    <span class="action-badge <?php echo $actionClass; ?>">
                                        <?php echo htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['old_status'] && $log['new_status']): ?>
                                        <span class="status-badge status-<?php echo strtolower($log['old_status']); ?>">
                                            <?php echo $log['old_status']; ?>
                                        </span>
                                        <i class="fas fa-arrow-right" style="margin: 0 8px; color: #666;"></i>
                                        <span class="status-badge status-<?php echo strtolower($log['new_status']); ?>">
                                            <?php echo $log['new_status']; ?>
                                        </span>
                                    <?php elseif ($log['new_status']): ?>
                                        <span class="status-badge status-<?php echo strtolower($log['new_status']); ?>">
                                            <?php echo $log['new_status']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #666;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['remarks'])): ?>
                                        <div style="max-width: 200px; word-wrap: break-word;">
                                            <?php echo htmlspecialchars($log['remarks'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #666;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small style="color: #666;"><?php echo htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-logs">
                        <i class="fas fa-history"></i>
                        <h3>No Activity Logs Found</h3>
                        <p>No administrative actions match your current filters.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $currentUrl = 'admin-activity-log.php';
                    $queryParams = $_GET;

                    // Previous page
                    if ($page > 1):
                        $queryParams['page'] = $page - 1;
                        $prevUrl = $currentUrl . '?' . http_build_query($queryParams);
                    ?>
                        <a href="<?php echo $prevUrl; ?>"><i class="fas fa-chevron-left"></i> Previous</a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-left"></i> Previous</span>
                    <?php endif; ?>

                    <?php
                    // Page numbers
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    for ($i = $startPage; $i <= $endPage; $i++):
                        $queryParams['page'] = $i;
                        $pageUrl = $currentUrl . '?' . http_build_query($queryParams);

                        if ($i == $page):
                    ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo $pageUrl; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php
                    // Next page
                    if ($page < $totalPages):
                        $queryParams['page'] = $page + 1;
                        $nextUrl = $currentUrl . '?' . http_build_query($queryParams);
                    ?>
                        <a href="<?php echo $nextUrl; ?>">Next <i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="disabled">Next <i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle filters visibility
        function toggleFilters() {
            const filtersContent = document.getElementById('filters-content');
            const filterIcon = document.getElementById('filter-icon');
            const toggleButton = document.querySelector('.filters-toggle');

            if (filtersContent.classList.contains('active')) {
                filtersContent.classList.remove('active');
                filterIcon.className = 'fas fa-chevron-down';
                toggleButton.innerHTML = '<i class="fas fa-chevron-down" id="filter-icon"></i> Show Filters';
            } else {
                filtersContent.classList.add('active');
                filterIcon.className = 'fas fa-chevron-up';
                toggleButton.innerHTML = '<i class="fas fa-chevron-up" id="filter-icon"></i> Hide Filters';
            }
        }

        // Clear all filters
        function clearFilters() {
            window.location.href = 'admin-activity-log.php';
        }

        // Show filters if any filter is active
        document.addEventListener('DOMContentLoaded', function() {
            const hasActiveFilters = <?php echo $hasFilters ? 'true' : 'false'; ?>;
            if (hasActiveFilters) {
                toggleFilters();
            }
        });
    </script>
</body>
</html>
