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

// Create support_tickets table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    application_id VARCHAR(50),
    category VARCHAR(50) NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$con->query($createTableSQL);

// Handle status updates
if (isset($_POST['update_status'])) {
    $ticketId = $_POST['ticket_id'];
    $newStatus = $_POST['new_status'];
    $updateSQL = "UPDATE support_tickets SET status = ? WHERE id = ?";
    $stmt = $con->prepare($updateSQL);
    $stmt->bind_param("si", $newStatus, $ticketId);
    
    if ($stmt->execute()) {
        $message = "Ticket status updated successfully";
        $messageType = "success";
        
        // Log the action with proper parameters
        $adminId = $_SESSION['admin_email'] ?? 'admin';
        $action = "Updated support ticket status";
        $details = "Ticket #" . $ticketId . " status changed to " . $newStatus;
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        AdminLogger::logAction(
            $adminId,
            $action,
            $details,
            $ipAddress,
            $userAgent
        );
    } else {
        $message = "Error updating ticket status";
        $messageType = "error";
    }
}

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$whereConditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $whereConditions[] = "(ticket_number LIKE ? OR subject LIKE ? OR name LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= 'ssss';
}

if (!empty($categoryFilter)) {
    $whereConditions[] = "category = ?";
    $params[] = $categoryFilter;
    $types .= 's';
}

if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

// Get all support tickets with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM support_tickets";
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

$stmt = $con->prepare($query);
if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$tickets = $stmt->get_result();

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM support_tickets";
if (!empty($whereConditions)) {
    $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
}

$countStmt = $con->prepare($countQuery);
if (!empty($params)) {
    // Remove limit and offset parameters
    array_pop($params); // Remove offset
    array_pop($params); // Remove limit
    $types = substr($types, 0, -2); // Remove 'ii' from types
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalTickets = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalTickets / $limit);

// Get statistics
$statsQuery = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed
    FROM support_tickets";
$statsResult = $con->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Get unique categories for filter
$categoriesQuery = "SELECT DISTINCT category FROM support_tickets ORDER BY category";
$categoriesResult = $con->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row['category'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Support Requests - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1024px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
        }

        .breadcrumb a {
            color: #1E3A8A;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1024px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.total { background: #f8f9fa; }
        .stat-card.open { background: #fff3cd; }
        .stat-card.progress { background: #cce5ff; }
        .stat-card.resolved { background: #d4edda; }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin: 2rem 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            min-width: 150px;
        }

        .ticket-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .ticket-number {
            color: #1E3A8A;
            font-weight: 600;
            text-decoration: none;
        }

        .ticket-number:hover {
            text-decoration: underline;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-open { background: #fff3cd; color: #856404; }
        .status-in-progress { background: #cce5ff; color: #004085; }
        .status-resolved { background: #d4edda; color: #155724; }
        .status-closed { background: #f8d7da; color: #721c24; }

        .ticket-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .ticket-subject {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 1rem 0;
        }

        .ticket-message {
            font-size: 0.9rem;
            color: #444;
            margin-bottom: 1rem;
            white-space: pre-line;
        }

        .ticket-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .ticket-date {
            font-size: 0.8rem;
            color: #666;
        }

        .status-select {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            min-width: 150px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-decoration: none;
            color: #1E3A8A;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #1E3A8A;
            color: white;
        }

        .pagination .active {
            background: #1E3A8A;
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                padding: 1rem;
            }

            .header-title {
                font-size: 20px;
            }

            .filters {
                flex-direction: column;
            }

            .search-box, .filter-select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="header-title">
                <i class="fas fa-headset"></i>
                Support Requests
            </div>
            <div class="breadcrumb">
                <a href="admin-dashboard.php">Dashboard</a> &gt; 
                <a href="admin-logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">🗂</div>
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card open">
                <div class="stat-icon">🔓</div>
                <div class="stat-number"><?php echo $stats['open']; ?></div>
                <div class="stat-label">Open</div>
            </div>
            <div class="stat-card progress">
                <div class="stat-icon">🔄</div>
                <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card resolved">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $stats['resolved']; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="filters">
            <form method="GET" class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by ticket ID, subject..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </form>
            <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>" 
                            <?php echo $categoryFilter === $category ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="Open" <?php echo $statusFilter === 'Open' ? 'selected' : ''; ?>>Open</option>
                <option value="In Progress" <?php echo $statusFilter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="Resolved" <?php echo $statusFilter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                <option value="Closed" <?php echo $statusFilter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>

        <!-- Support Tickets List -->
        <div class="section-title">
            <i class="fas fa-ticket-alt"></i>
            Recent Support Tickets
        </div>
        
        <?php if ($tickets->num_rows > 0): ?>
            <?php while ($ticket = $tickets->fetch_assoc()): ?>
                <div class="ticket-card">
                    <div class="ticket-header">
                        <a href="#" class="ticket-number">#<?php echo htmlspecialchars($ticket['ticket_number']); ?></a>
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>">
                            <?php echo htmlspecialchars($ticket['status']); ?>
                        </span>
                    </div>
                    
                    <div class="ticket-meta">
                        <strong>From:</strong> <?php echo htmlspecialchars($ticket['name']); ?> 
                        (<?php echo htmlspecialchars($ticket['email']); ?>)
                    </div>
                    <div class="ticket-meta">
                        <strong>Category:</strong> <?php echo htmlspecialchars($ticket['category']); ?> |
                        <strong>Priority:</strong> <?php echo htmlspecialchars($ticket['priority']); ?>
                    </div>
                    
                    <div class="ticket-subject">
                        <?php echo htmlspecialchars($ticket['subject']); ?>
                    </div>
                    
                    <div class="ticket-message">
                        <?php 
                        $message = htmlspecialchars($ticket['message']);
                        $lines = explode("\n", $message);
                        echo implode("\n", array_slice($lines, 0, 2));
                        if (count($lines) > 2) {
                            echo "\n...";
                        }
                        ?>
                    </div>

                    <div class="ticket-footer">
                        <div class="ticket-date">
                            Created: <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <select name="new_status" onchange="this.form.submit()" class="status-select">
                                <option value="">Update Status</option>
                                <option value="Open" <?php echo $ticket['status'] == 'Open' ? 'selected' : ''; ?>>Open</option>
                                <option value="In Progress" <?php echo $ticket['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Resolved" <?php echo $ticket['status'] == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="Closed" <?php echo $ticket['status'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" 
                           class="<?php echo $page == $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="message info">
                <i class="fas fa-info-circle"></i> No support tickets found.
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 