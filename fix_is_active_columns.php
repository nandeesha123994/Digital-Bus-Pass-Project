<?php
// Database connection
$con = new mysqli("localhost", "root", "", "bpmsdb");

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Function to execute SQL and show result
function executeSQL($con, $sql, $message) {
    if ($con->query($sql) === TRUE) {
        echo "<div style='color: green; margin: 5px 0;'>✅ $message</div>";
    } else {
        echo "<div style='color: red; margin: 5px 0;'>❌ Error: " . $con->error . "</div>";
    }
}

echo "<h2>Adding is_active columns to tables...</h2>";

// Add is_active to bus_pass_types
$sql = "ALTER TABLE bus_pass_types ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER duration_days";
executeSQL($con, $sql, "Added is_active column to bus_pass_types table");

$sql = "UPDATE bus_pass_types SET is_active = TRUE WHERE is_active IS NULL";
executeSQL($con, $sql, "Updated existing records in bus_pass_types to be active");

// Add is_active to categories
$sql = "ALTER TABLE categories ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER description";
executeSQL($con, $sql, "Added is_active column to categories table");

$sql = "UPDATE categories SET is_active = TRUE WHERE is_active IS NULL";
executeSQL($con, $sql, "Updated existing records in categories to be active");

// Add is_active to routes
$sql = "ALTER TABLE routes ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER estimated_duration";
executeSQL($con, $sql, "Added is_active column to routes table");

$sql = "UPDATE routes SET is_active = TRUE WHERE is_active IS NULL";
executeSQL($con, $sql, "Updated existing records in routes to be active");

// Add indexes
$sql = "ALTER TABLE bus_pass_types ADD INDEX IF NOT EXISTS idx_active (is_active)";
executeSQL($con, $sql, "Added index on is_active for bus_pass_types");

$sql = "ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_active (is_active)";
executeSQL($con, $sql, "Added index on is_active for categories");

$sql = "ALTER TABLE routes ADD INDEX IF NOT EXISTS idx_active (is_active)";
executeSQL($con, $sql, "Added index on is_active for routes");

echo "<h3>Verification:</h3>";

// Verify columns exist
$tables = ['bus_pass_types', 'categories', 'routes'];
foreach ($tables as $table) {
    $result = $con->query("SHOW COLUMNS FROM $table LIKE 'is_active'");
    if ($result->num_rows > 0) {
        echo "<div style='color: green; margin: 5px 0;'>✅ is_active column exists in $table</div>";
    } else {
        echo "<div style='color: red; margin: 5px 0;'>❌ is_active column missing in $table</div>";
    }
}

$con->close();
echo "<h3>Process completed!</h3>";
?> 