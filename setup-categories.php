<?php
// Database setup script for Category Management feature
include('includes/dbconnection.php');

echo "<h2>Setting up Category Management Feature...</h2>";

// Create categories table
$createTableQuery = "
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($con->query($createTableQuery)) {
    echo "<p>✅ Categories table created successfully!</p>";
} else {
    echo "<p>❌ Error creating categories table: " . $con->error . "</p>";
}

// Insert default categories
$defaultCategories = [
    ['KSRTC', 'Karnataka State Road Transport Corporation - State government buses'],
    ['MSRTC', 'Maharashtra State Road Transport Corporation - State government buses'],
    ['BMTC', 'Bangalore Metropolitan Transport Corporation - City buses'],
    ['TSRTC', 'Telangana State Road Transport Corporation - State government buses'],
    ['APSRTC', 'Andhra Pradesh State Road Transport Corporation - State government buses'],
    ['Private', 'Private bus operators and services']
];

foreach ($defaultCategories as $category) {
    $insertQuery = "INSERT IGNORE INTO categories (category_name, description) VALUES (?, ?)";
    $stmt = $con->prepare($insertQuery);
    $stmt->bind_param("ss", $category[0], $category[1]);
    
    if ($stmt->execute()) {
        echo "<p>✅ Added category: " . $category[0] . "</p>";
    } else {
        echo "<p>⚠️ Category " . $category[0] . " already exists or error: " . $con->error . "</p>";
    }
}

// Check if category_id column exists in bus_pass_applications table
$checkColumnQuery = "SHOW COLUMNS FROM bus_pass_applications LIKE 'category_id'";
$result = $con->query($checkColumnQuery);

if ($result->num_rows == 0) {
    // Add category_id column to bus_pass_applications table
    $addColumnQuery = "ALTER TABLE bus_pass_applications ADD COLUMN category_id INT DEFAULT NULL";
    if ($con->query($addColumnQuery)) {
        echo "<p>✅ Added category_id column to bus_pass_applications table!</p>";
        
        // Add foreign key constraint
        $addForeignKeyQuery = "ALTER TABLE bus_pass_applications ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
        if ($con->query($addForeignKeyQuery)) {
            echo "<p>✅ Added foreign key constraint!</p>";
        } else {
            echo "<p>⚠️ Warning: Could not add foreign key constraint: " . $con->error . "</p>";
        }
        
        // Update existing applications to have a default category (BMTC)
        $updateQuery = "UPDATE bus_pass_applications SET category_id = (SELECT id FROM categories WHERE category_name = 'BMTC' LIMIT 1) WHERE category_id IS NULL";
        if ($con->query($updateQuery)) {
            echo "<p>✅ Updated existing applications with default category!</p>";
        } else {
            echo "<p>⚠️ Warning: Could not update existing applications: " . $con->error . "</p>";
        }
    } else {
        echo "<p>❌ Error adding category_id column: " . $con->error . "</p>";
    }
} else {
    echo "<p>✅ category_id column already exists in bus_pass_applications table!</p>";
}

echo "<h3>Setup Complete!</h3>";
echo "<p><strong>Category Management feature has been successfully set up!</strong></p>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li>Access <a href='manage-categories.php'>Manage Categories</a> from the admin dashboard</li>";
echo "<li>Add, edit, and delete transport categories</li>";
echo "<li>Users can select categories when applying for bus passes</li>";
echo "<li>View category information in the admin dashboard</li>";
echo "</ul>";
echo "<p><a href='admin-dashboard.php'>← Back to Admin Dashboard</a></p>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Category Management Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        h3 { color: #667eea; }
        p { margin: 10px 0; }
        ul { margin: 10px 0; padding-left: 20px; }
        a { color: #667eea; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
</body>
</html>
