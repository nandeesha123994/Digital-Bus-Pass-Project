<?php
// Simple script to create categories table and set up the feature
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Create Categories Table</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo "h2{color:#333;border-bottom:2px solid #667eea;padding-bottom:10px;}";
echo "p{margin:10px 0;} .success{color:#28a745;} .error{color:#dc3545;} .warning{color:#ffc107;}";
echo "</style></head><body>";

echo "<h2>🏷️ Creating Categories Table for Bus Pass Management System</h2>";

// Step 1: Create categories table
echo "<h3>Step 1: Creating Categories Table</h3>";
$createTableSQL = "
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($con->query($createTableSQL)) {
    echo "<p class='success'>✅ Categories table created successfully!</p>";
} else {
    echo "<p class='error'>❌ Error creating categories table: " . $con->error . "</p>";
    exit();
}

// Step 2: Insert default categories
echo "<h3>Step 2: Adding Default Categories</h3>";
$categories = [
    ['KSRTC', 'Karnataka State Road Transport Corporation - State government buses'],
    ['MSRTC', 'Maharashtra State Road Transport Corporation - State government buses'],
    ['BMTC', 'Bangalore Metropolitan Transport Corporation - City buses'],
    ['TSRTC', 'Telangana State Road Transport Corporation - State government buses'],
    ['APSRTC', 'Andhra Pradesh State Road Transport Corporation - State government buses'],
    ['Private', 'Private bus operators and services']
];

foreach ($categories as $category) {
    $insertSQL = "INSERT IGNORE INTO categories (category_name, description) VALUES (?, ?)";
    $stmt = $con->prepare($insertSQL);
    $stmt->bind_param("ss", $category[0], $category[1]);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<p class='success'>✅ Added category: " . htmlspecialchars($category[0]) . "</p>";
        } else {
            echo "<p class='warning'>⚠️ Category " . htmlspecialchars($category[0]) . " already exists</p>";
        }
    } else {
        echo "<p class='error'>❌ Error adding category " . htmlspecialchars($category[0]) . ": " . $con->error . "</p>";
    }
}

// Step 3: Check and add category_id column to bus_pass_applications
echo "<h3>Step 3: Updating Bus Pass Applications Table</h3>";
$checkColumnSQL = "SHOW COLUMNS FROM bus_pass_applications LIKE 'category_id'";
$result = $con->query($checkColumnSQL);

if ($result->num_rows == 0) {
    // Add category_id column
    $addColumnSQL = "ALTER TABLE bus_pass_applications ADD COLUMN category_id INT DEFAULT NULL";
    if ($con->query($addColumnSQL)) {
        echo "<p class='success'>✅ Added category_id column to bus_pass_applications table</p>";
        
        // Try to add foreign key (may fail if there are existing records)
        $addFKSQL = "ALTER TABLE bus_pass_applications ADD CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
        if ($con->query($addFKSQL)) {
            echo "<p class='success'>✅ Added foreign key constraint</p>";
        } else {
            echo "<p class='warning'>⚠️ Could not add foreign key constraint (this is okay): " . $con->error . "</p>";
        }
        
        // Update existing applications with default category
        $updateSQL = "UPDATE bus_pass_applications SET category_id = (SELECT id FROM categories WHERE category_name = 'BMTC' LIMIT 1) WHERE category_id IS NULL";
        if ($con->query($updateSQL)) {
            $affected = $con->affected_rows;
            echo "<p class='success'>✅ Updated $affected existing applications with default category (BMTC)</p>";
        } else {
            echo "<p class='warning'>⚠️ Could not update existing applications: " . $con->error . "</p>";
        }
    } else {
        echo "<p class='error'>❌ Error adding category_id column: " . $con->error . "</p>";
    }
} else {
    echo "<p class='success'>✅ category_id column already exists in bus_pass_applications table</p>";
}

// Step 4: Verify setup
echo "<h3>Step 4: Verification</h3>";
$verifySQL = "SELECT COUNT(*) as count FROM categories";
$result = $con->query($verifySQL);
$count = $result->fetch_assoc()['count'];
echo "<p class='success'>✅ Categories table has $count categories</p>";

$verifyAppsSQL = "SELECT COUNT(*) as count FROM bus_pass_applications WHERE category_id IS NOT NULL";
$result = $con->query($verifyAppsSQL);
if ($result) {
    $appCount = $result->fetch_assoc()['count'];
    echo "<p class='success'>✅ $appCount applications have category assignments</p>";
}

echo "<h3>🎉 Setup Complete!</h3>";
echo "<p><strong>Category Management feature is now ready to use!</strong></p>";
echo "<div style='background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0;'>";
echo "<h4>What you can do now:</h4>";
echo "<ul>";
echo "<li><a href='admin-dashboard.php' style='color:#667eea;'>Go to Admin Dashboard</a> - View applications with categories</li>";
echo "<li><a href='manage-categories.php' style='color:#667eea;'>Manage Categories</a> - Add, edit, delete categories</li>";
echo "<li><a href='apply-pass.php' style='color:#667eea;'>Apply for Pass</a> - Test category selection in user form</li>";
echo "</ul>";
echo "</div>";

echo "<h4>Next Steps:</h4>";
echo "<ol>";
echo "<li><strong>Test Admin Interface:</strong> Go to Admin Dashboard → Categories</li>";
echo "<li><strong>Test User Interface:</strong> Try applying for a bus pass</li>";
echo "<li><strong>Add More Categories:</strong> Use the category management page to add more transport operators</li>";
echo "</ol>";

echo "</body></html>";
?>
