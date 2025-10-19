<?php
// Test script to check if categories table exists and show current status
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Categories Table</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo "h2{color:#333;} .success{color:#28a745;} .error{color:#dc3545;} .info{color:#007bff;}";
echo "table{border-collapse:collapse;width:100%;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}";
echo "</style></head><body>";

echo "<h2>🔍 Categories Table Status Check</h2>";

// Check if categories table exists
$checkTableSQL = "SHOW TABLES LIKE 'categories'";
$result = $con->query($checkTableSQL);

if ($result->num_rows > 0) {
    echo "<p class='success'>✅ Categories table exists!</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $structureSQL = "DESCRIBE categories";
    $result = $con->query($structureSQL);
    
    if ($result) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show current categories
    echo "<h3>Current Categories:</h3>";
    $categoriesSQL = "SELECT * FROM categories ORDER BY id";
    $result = $con->query($categoriesSQL);
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Category Name</th><th>Description</th><th>Active</th><th>Created</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['category_name']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . ($row['is_active'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>ℹ️ No categories found in the table.</p>";
    }
    
    // Check if category_id column exists in bus_pass_applications
    echo "<h3>Bus Pass Applications Table Check:</h3>";
    $checkColumnSQL = "SHOW COLUMNS FROM bus_pass_applications LIKE 'category_id'";
    $result = $con->query($checkColumnSQL);
    
    if ($result->num_rows > 0) {
        echo "<p class='success'>✅ category_id column exists in bus_pass_applications table</p>";
        
        // Count applications with categories
        $countSQL = "SELECT COUNT(*) as total, COUNT(category_id) as with_category FROM bus_pass_applications";
        $result = $con->query($countSQL);
        if ($result) {
            $counts = $result->fetch_assoc();
            echo "<p class='info'>ℹ️ Total applications: " . $counts['total'] . "</p>";
            echo "<p class='info'>ℹ️ Applications with categories: " . $counts['with_category'] . "</p>";
        }
    } else {
        echo "<p class='error'>❌ category_id column does NOT exist in bus_pass_applications table</p>";
        echo "<p class='info'>ℹ️ You need to run the setup script to add this column.</p>";
    }
    
} else {
    echo "<p class='error'>❌ Categories table does NOT exist!</p>";
    echo "<p class='info'>ℹ️ You need to create the categories table first.</p>";
}

echo "<h3>🔧 Available Actions:</h3>";
echo "<ul>";
echo "<li><a href='create-categories-table.php' style='color:#007bff;'>Run Setup Script</a> - Create table and add default categories</li>";
echo "<li><a href='manage-categories.php' style='color:#007bff;'>Manage Categories</a> - Admin category management (if table exists)</li>";
echo "<li><a href='admin-dashboard.php' style='color:#007bff;'>Admin Dashboard</a> - View applications</li>";
echo "</ul>";

echo "</body></html>";
?>
