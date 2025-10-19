<?php
/**
 * Complete Review System Diagnostic Tool
 * Checks all aspects of the review system and provides fixes
 */

include('includes/dbconnection.php');

echo "<h1>🔍 Complete Review System Diagnostic</h1>";

try {
    // Step 1: Database Connection Test
    echo "<h3>🔗 Step 1: Database Connection</h3>";
    if ($con->connect_error) {
        throw new Exception("Database connection failed: " . $con->connect_error);
    }
    echo "<p style='color: green;'>✅ Database connection: OK</p>";
    
    // Step 2: Check All Review-Related Tables
    echo "<h3>📊 Step 2: Review Tables Analysis</h3>";
    
    $allTables = [];
    $tablesQuery = "SHOW TABLES";
    $tablesResult = $con->query($tablesQuery);
    while ($table = $tablesResult->fetch_array()) {
        $allTables[] = $table[0];
    }
    
    $reviewTables = array_filter($allTables, function($table) {
        return strpos(strtolower($table), 'review') !== false;
    });
    
    echo "<p><strong>All tables in database:</strong> " . implode(', ', $allTables) . "</p>";
    echo "<p><strong>Review-related tables found:</strong> " . (empty($reviewTables) ? 'None' : implode(', ', $reviewTables)) . "</p>";
    
    // Step 3: Detailed Table Analysis
    foreach ($reviewTables as $table) {
        echo "<h4>📋 Table: $table</h4>";
        
        // Structure
        $structureQuery = "DESCRIBE $table";
        $structureResult = $con->query($structureQuery);
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($col = $structureResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Data count
        $countQuery = "SELECT COUNT(*) as count FROM $table";
        $countResult = $con->query($countQuery);
        $count = $countResult->fetch_assoc()['count'];
        echo "<p><strong>Records:</strong> $count</p>";
        
        // Sample data
        if ($count > 0) {
            $sampleQuery = "SELECT * FROM $table LIMIT 3";
            $sampleResult = $con->query($sampleQuery);
            echo "<p><strong>Sample data:</strong></p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 0.9rem;'>";
            
            // Headers
            $firstRow = $sampleResult->fetch_assoc();
            if ($firstRow) {
                echo "<tr style='background: #f0f0f0;'>";
                foreach (array_keys($firstRow) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                
                // Data
                $sampleResult->data_seek(0);
                while ($row = $sampleResult->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars(substr($value ?? '', 0, 50)) . "</td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";
        }
    }
    
    // Step 4: Test Homepage Query
    echo "<h3>🏠 Step 4: Homepage Query Test</h3>";
    
    $homepageQueries = [
        'instant_reviews' => "SELECT ir.review_text, ir.rating, ir.created_at, u.full_name as username
                             FROM instant_reviews ir
                             JOIN users u ON ir.user_id = u.id
                             WHERE ir.status = 'active'
                             ORDER BY ir.created_at DESC
                             LIMIT 5",
        'reviews' => "SELECT r.review_text, r.rating, r.submitted_at as created_at, u.full_name as username
                     FROM reviews r
                     JOIN users u ON r.user_id = u.id
                     WHERE r.status = 'approved'
                     ORDER BY r.submitted_at DESC
                     LIMIT 5"
    ];
    
    $workingQuery = null;
    $workingTable = null;
    
    foreach ($homepageQueries as $table => $query) {
        if (in_array($table, $reviewTables)) {
            echo "<p>Testing query for table: <strong>$table</strong></p>";
            $result = $con->query($query);
            
            if ($result && $result->num_rows > 0) {
                echo "<p style='color: green;'>✅ Query successful! Found " . $result->num_rows . " reviews.</p>";
                $workingQuery = $query;
                $workingTable = $table;
                break;
            } else {
                echo "<p style='color: orange;'>⚠️ Query returned no results or failed: " . $con->error . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Table $table not found</p>";
        }
    }
    
    // Step 5: Check Users Table
    echo "<h3>👥 Step 5: Users Table Check</h3>";
    
    if (in_array('users', $allTables)) {
        echo "<p style='color: green;'>✅ Users table exists</p>";
        
        $userCountQuery = "SELECT COUNT(*) as count FROM users";
        $userCountResult = $con->query($userCountQuery);
        $userCount = $userCountResult->fetch_assoc()['count'];
        echo "<p>Users in database: $userCount</p>";
        
        if ($userCount == 0) {
            echo "<p style='color: orange;'>⚠️ No users found. Reviews need users to display properly.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Users table not found. This is required for review display.</p>";
    }
    
    // Step 6: Homepage File Check
    echo "<h3>📄 Step 6: Homepage File Analysis</h3>";
    
    $homepageFile = 'index.php';
    if (file_exists($homepageFile)) {
        echo "<p style='color: green;'>✅ Homepage file exists</p>";
        
        $content = file_get_contents($homepageFile);
        
        // Check for review-related code
        $checks = [
            'instant_reviews table reference' => 'instant_reviews',
            'reviews table reference' => 'reviews',
            'testimonials container' => 'testimonials-container',
            'review display loop' => 'while.*review.*fetch_assoc',
            'review rating display' => 'fa-star',
            'review text display' => 'review_text'
        ];
        
        foreach ($checks as $description => $pattern) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                echo "<p style='color: green;'>✅ $description: Found</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ $description: Not found</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Homepage file not found</p>";
    }
    
    // Step 7: Provide Solutions
    echo "<h3>🔧 Step 7: Recommended Solutions</h3>";
    
    if (empty($reviewTables)) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>🛠️ Solution 1: Create Review Table</h4>";
        echo "<p>No review tables found. Create the instant_reviews table:</p>";
        echo "<p><a href='fix-homepage-reviews.php' style='background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>🔧 Run Auto-Fix Script</a></p>";
        echo "</div>";
    } elseif (!$workingQuery) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>🛠️ Solution 2: Fix Data Issues</h4>";
        echo "<p>Review tables exist but queries are failing. Possible issues:</p>";
        echo "<ul>";
        echo "<li>No approved/active reviews in database</li>";
        echo "<li>Missing user records</li>";
        echo "<li>Incorrect status values</li>";
        echo "</ul>";
        echo "<p><a href='fix-homepage-reviews.php' style='background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>🔧 Run Data Fix Script</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ System Status: Working</h4>";
        echo "<p>Review system appears to be working correctly:</p>";
        echo "<ul>";
        echo "<li>✅ Review table exists: $workingTable</li>";
        echo "<li>✅ Query returns data successfully</li>";
        echo "<li>✅ Homepage file contains review display code</li>";
        echo "</ul>";
        echo "<p><strong>If reviews still don't show on homepage:</strong></p>";
        echo "<ul>";
        echo "<li>Clear browser cache (Ctrl+Shift+R)</li>";
        echo "<li>Check browser console for JavaScript errors</li>";
        echo "<li>Verify review status is 'active' or 'approved'</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    // Step 8: Quick Actions
    echo "<h3>⚡ Step 8: Quick Actions</h3>";
    echo "<div style='display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0;'>";
    echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>🏠 Test Homepage</a>";
    echo "<a href='fix-homepage-reviews.php' style='background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>🔧 Auto-Fix Reviews</a>";
    echo "<a href='instant-reviews-display.php' style='background: #6f42c1; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>📋 View All Reviews</a>";
    echo "<a href='user-dashboard.php' style='background: #fd7e14; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>✍️ Submit Review</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Diagnostic Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review System Diagnostic - Bus Pass Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            line-height: 1.6;
        }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        h3 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        h4 { color: #2c3e50; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:nth-child(even) { background: #f9f9f9; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-top: 30px;">
        <p><a href="admin-dashboard.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">← Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
