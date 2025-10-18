<?php
/**
 * Create Announcements Table
 * This script creates the announcements table for managing important announcements
 */

include('includes/dbconnection.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create Announcements Table - Bus Pass Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; }
        h1 { color: #007bff; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📢 Create Announcements Table</h1>
        <p>This script creates the announcements table and inserts sample data.</p>
        <hr>";

try {
    // Check if announcements table already exists
    $checkQuery = "SHOW TABLES LIKE 'announcements'";
    $result = $con->query($checkQuery);

    if ($result->num_rows == 0) {
        echo "<h2>Creating announcements table...</h2>";

        // Create announcements table
        $createTableQuery = "
        CREATE TABLE announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            type ENUM('urgent', 'new', 'info', 'success', 'warning') DEFAULT 'info',
            icon VARCHAR(50) DEFAULT 'fas fa-info-circle',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by INT,
            expiry_date DATETIME NULL,
            INDEX idx_active (is_active),
            INDEX idx_created (created_at),
            INDEX idx_expiry (expiry_date)
        )";

        if ($con->query($createTableQuery)) {
            echo "<span class='success'>✅ Announcements table created successfully!</span><br><br>";

            // Insert sample announcements
            echo "<h2>Inserting sample announcements...</h2>";

            $sampleAnnouncements = [
                [
                    'title' => 'Service Disruption Notice',
                    'content' => 'Bus services will be unavailable on public holidays (December 25, January 1, and January 26). Please plan your travel accordingly.',
                    'type' => 'urgent',
                    'icon' => 'fas fa-exclamation-triangle',
                    'display_order' => 1
                ],
                [
                    'title' => 'New Pass Format Coming Soon',
                    'content' => 'Starting July 2025, we will introduce a new digital bus pass format with enhanced security features and QR code integration.',
                    'type' => 'new',
                    'icon' => 'fas fa-star',
                    'display_order' => 2
                ],
                [
                    'title' => 'PhonePe Payment Integration',
                    'content' => 'We have successfully integrated PhonePe as our primary payment gateway for faster and more secure transactions.',
                    'type' => 'info',
                    'icon' => 'fas fa-info-circle',
                    'display_order' => 3
                ],
                [
                    'title' => 'Application ID System Upgrade',
                    'content' => 'All new applications now receive a unique Application ID in BPMS format for easier tracking and support.',
                    'type' => 'success',
                    'icon' => 'fas fa-check-circle',
                    'display_order' => 4
                ],
                [
                    'title' => 'Processing Time Update',
                    'content' => 'Due to high demand, bus pass applications may take 3-5 business days to process. We appreciate your patience.',
                    'type' => 'warning',
                    'icon' => 'fas fa-clock',
                    'display_order' => 5
                ],
                [
                    'title' => 'Mobile-Friendly Interface',
                    'content' => 'Our website is now fully optimized for mobile devices. Apply for bus passes easily from your smartphone!',
                    'type' => 'info',
                    'icon' => 'fas fa-mobile-alt',
                    'display_order' => 6
                ]
            ];

            $insertQuery = "INSERT INTO announcements (title, content, type, icon, display_order) VALUES (?, ?, ?, ?, ?)";
            $stmt = $con->prepare($insertQuery);

            foreach ($sampleAnnouncements as $announcement) {
                $stmt->bind_param("ssssi",
                    $announcement['title'],
                    $announcement['content'],
                    $announcement['type'],
                    $announcement['icon'],
                    $announcement['display_order']
                );

                if ($stmt->execute()) {
                    echo "✅ Added: " . htmlspecialchars($announcement['title']) . "<br>";
                } else {
                    echo "❌ Failed to add: " . htmlspecialchars($announcement['title']) . "<br>";
                }
            }

            echo "<br><span class='success'>✅ Sample announcements inserted successfully!</span><br>";

        } else {
            echo "<span class='error'>❌ Error creating announcements table: " . $con->error . "</span><br>";
        }
    } else {
        echo "<span class='info'>ℹ️ Announcements table already exists.</span><br>";
    }

    // Show table structure
    echo "<h2>Table Structure:</h2>";
    $structureQuery = "DESCRIBE announcements";
    $structureResult = $con->query($structureQuery);

    if ($structureResult) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structureResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Show current announcements
    echo "<h2>Current Announcements:</h2>";
    $announcementsQuery = "SELECT * FROM announcements WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC";
    $announcementsResult = $con->query($announcementsQuery);

    if ($announcementsResult && $announcementsResult->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Icon</th><th>Active</th><th>Order</th><th>Created</th></tr>";
        while ($row = $announcementsResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['title']) . "</strong></td>";
            echo "<td><span style='color: " . getTypeColor($row['type']) . ";'>" . htmlspecialchars($row['type']) . "</span></td>";
            echo "<td><i class='" . htmlspecialchars($row['icon']) . "'></i> " . htmlspecialchars($row['icon']) . "</td>";
            echo "<td>" . ($row['is_active'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "<td>" . htmlspecialchars($row['display_order']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No announcements found.</p>";
    }

    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p>The announcements system is now ready. You can:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>View announcements on home page</a></li>";
    echo "<li><a href='admin-login.php'>Login to admin panel</a> to manage announcements</li>";
    echo "<li><a href='manage_announcements.php'>Manage announcements directly</a> (if admin)</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}

function getTypeColor($type) {
    switch ($type) {
        case 'urgent': return '#dc3545';
        case 'new': return '#856404';
        case 'info': return '#0c5460';
        case 'success': return '#155724';
        case 'warning': return '#856404';
        default: return '#333';
    }
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a></p>";
echo "</div></body></html>";
?>
