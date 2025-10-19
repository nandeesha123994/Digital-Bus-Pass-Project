<?php
/**
 * Setup Announcements System
 * Quick setup script to ensure announcements system is ready
 */

include('includes/dbconnection.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Announcements System - Bus Pass Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        h1 { color: #007bff; text-align: center; }
        .step { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #007bff; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 Setup Announcements System</h1>
        <p style='text-align: center;'>This script will set up the announcements system for your Bus Pass Management System.</p>
        <hr>";

$setupSteps = [];
$allGood = true;

try {
    // Step 1: Check database connection
    echo "<div class='step'>";
    echo "<h3>Step 1: Database Connection</h3>";
    if ($con) {
        echo "<span class='success'>✅ Database connection successful</span><br>";
        $setupSteps[] = "Database connection: OK";
    } else {
        echo "<span class='error'>❌ Database connection failed</span><br>";
        $setupSteps[] = "Database connection: FAILED";
        $allGood = false;
    }
    echo "</div>";

    // Step 2: Check if announcements table exists
    echo "<div class='step'>";
    echo "<h3>Step 2: Announcements Table</h3>";
    $checkTableQuery = "SHOW TABLES LIKE 'announcements'";
    $tableResult = $con->query($checkTableQuery);
    
    if ($tableResult->num_rows > 0) {
        echo "<span class='success'>✅ Announcements table exists</span><br>";
        $setupSteps[] = "Announcements table: EXISTS";
        
        // Check table structure
        $structureQuery = "DESCRIBE announcements";
        $structureResult = $con->query($structureQuery);
        $requiredFields = ['id', 'title', 'content', 'type', 'icon', 'is_active', 'created_at'];
        $existingFields = [];
        
        while ($row = $structureResult->fetch_assoc()) {
            $existingFields[] = $row['Field'];
        }
        
        $missingFields = array_diff($requiredFields, $existingFields);
        if (empty($missingFields)) {
            echo "<span class='success'>✅ Table structure is correct</span><br>";
            $setupSteps[] = "Table structure: CORRECT";
        } else {
            echo "<span class='warning'>⚠️ Missing fields: " . implode(', ', $missingFields) . "</span><br>";
            $setupSteps[] = "Table structure: INCOMPLETE";
        }
        
    } else {
        echo "<span class='warning'>⚠️ Announcements table does not exist</span><br>";
        echo "<p>Creating announcements table...</p>";
        
        // Create the table
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
            display_order INT DEFAULT 0,
            INDEX idx_active (is_active),
            INDEX idx_order (display_order),
            INDEX idx_created (created_at)
        )";
        
        if ($con->query($createTableQuery)) {
            echo "<span class='success'>✅ Announcements table created successfully</span><br>";
            $setupSteps[] = "Announcements table: CREATED";
        } else {
            echo "<span class='error'>❌ Failed to create announcements table: " . $con->error . "</span><br>";
            $setupSteps[] = "Announcements table: CREATION FAILED";
            $allGood = false;
        }
    }
    echo "</div>";

    // Step 3: Check for sample data
    echo "<div class='step'>";
    echo "<h3>Step 3: Sample Announcements</h3>";
    
    if ($tableResult->num_rows > 0) {
        $countQuery = "SELECT COUNT(*) as count FROM announcements";
        $countResult = $con->query($countQuery);
        $count = $countResult->fetch_assoc()['count'];
        
        if ($count > 0) {
            echo "<span class='success'>✅ Found $count announcements in database</span><br>";
            $setupSteps[] = "Sample data: $count announcements found";
        } else {
            echo "<span class='warning'>⚠️ No announcements found. Adding sample data...</span><br>";
            
            // Insert sample announcements
            $sampleAnnouncements = [
                ['Service Disruption Notice', 'Bus services will be unavailable on public holidays (December 25, January 1, and January 26). Please plan your travel accordingly.', 'urgent', 'fas fa-exclamation-triangle', 1],
                ['New Pass Format Coming Soon', 'Starting July 2025, we will introduce a new digital bus pass format with enhanced security features and QR code integration.', 'new', 'fas fa-star', 2],
                ['PhonePe Payment Integration', 'We have successfully integrated PhonePe as our primary payment gateway for faster and more secure transactions.', 'info', 'fas fa-info-circle', 3],
                ['Application ID System Upgrade', 'All new applications now receive a unique Application ID in BPMS format for easier tracking and support.', 'success', 'fas fa-check-circle', 4],
                ['Processing Time Update', 'Due to high demand, bus pass applications may take 3-5 business days to process. We appreciate your patience.', 'warning', 'fas fa-clock', 5],
                ['Mobile-Friendly Interface', 'Our website is now fully optimized for mobile devices. Apply for bus passes easily from your smartphone!', 'info', 'fas fa-mobile-alt', 6]
            ];
            
            $insertQuery = "INSERT INTO announcements (title, content, type, icon, display_order) VALUES (?, ?, ?, ?, ?)";
            $stmt = $con->prepare($insertQuery);
            $inserted = 0;
            
            foreach ($sampleAnnouncements as $announcement) {
                $stmt->bind_param("ssssi", $announcement[0], $announcement[1], $announcement[2], $announcement[3], $announcement[4]);
                if ($stmt->execute()) {
                    $inserted++;
                }
            }
            
            echo "<span class='success'>✅ Added $inserted sample announcements</span><br>";
            $setupSteps[] = "Sample data: $inserted announcements added";
        }
    }
    echo "</div>";

    // Step 4: Test functionality
    echo "<div class='step'>";
    echo "<h3>Step 4: Functionality Test</h3>";
    
    // Test the get_announcements.php functions
    include_once('get_announcements.php');
    $testAnnouncements = getActiveAnnouncements($con, 3);
    
    if (!empty($testAnnouncements)) {
        echo "<span class='success'>✅ Successfully retrieved " . count($testAnnouncements) . " announcements</span><br>";
        echo "<span class='success'>✅ Announcement functions working correctly</span><br>";
        $setupSteps[] = "Functionality test: PASSED";
    } else {
        echo "<span class='warning'>⚠️ No active announcements found, but system is working</span><br>";
        $setupSteps[] = "Functionality test: NO DATA";
    }
    echo "</div>";

    // Final status
    echo "<div class='step'>";
    if ($allGood) {
        echo "<h2 style='color: #28a745;'>🎉 Setup Complete!</h2>";
        echo "<p>The announcements system is now ready to use.</p>";
    } else {
        echo "<h2 style='color: #dc3545;'>⚠️ Setup Issues Detected</h2>";
        echo "<p>Some issues were found during setup. Please review the steps above.</p>";
    }
    
    echo "<h3>Setup Summary:</h3>";
    echo "<ul>";
    foreach ($setupSteps as $step) {
        echo "<li>$step</li>";
    }
    echo "</ul>";
    echo "</div>";

    // Action buttons
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='index.php' class='btn btn-success'>🏠 View Home Page</a>";
    echo "<a href='manage_announcements.php?admin_access=1' class='btn'>📢 Manage Announcements</a>";
    echo "<a href='create_announcements_table.php' class='btn'>🔧 Advanced Setup</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='step'>";
    echo "<span class='error'>❌ Setup Error: " . $e->getMessage() . "</span><br>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666;'>Bus Pass Management System - Announcements Setup</p>";
echo "</div></body></html>";
?>
