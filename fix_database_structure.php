<?php
// Database connection
$con = new mysqli("localhost", "root", "", "bpmsdb");

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Function to check if table exists
function tableExists($con, $tableName) {
    $result = $con->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to check if column exists
function columnExists($con, $tableName, $columnName) {
    $result = $con->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
    return $result->num_rows > 0;
}

// Function to execute SQL and show result
function executeSQL($con, $sql, $message) {
    if ($con->query($sql) === TRUE) {
        echo "<div style='color: green; margin: 5px 0;'>✅ $message</div>";
    } else {
        echo "<div style='color: red; margin: 5px 0;'>❌ Error: " . $con->error . "</div>";
    }
}

echo "<h2>Database Structure Fix</h2>";

// Check and fix bus_pass_types table
if (tableExists($con, 'bus_pass_types')) {
    if (!columnExists($con, 'bus_pass_types', 'is_active')) {
        $sql = "ALTER TABLE bus_pass_types ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER duration_days";
        executeSQL($con, $sql, "Added is_active column to bus_pass_types table");
    } else {
        echo "<div style='color: blue; margin: 5px 0;'>ℹ️ is_active column already exists in bus_pass_types</div>";
    }
} else {
    echo "<div style='color: red; margin: 5px 0;'>❌ bus_pass_types table does not exist!</div>";
}

// Check and fix categories table
if (tableExists($con, 'categories')) {
    if (!columnExists($con, 'categories', 'is_active')) {
        $sql = "ALTER TABLE categories ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER description";
        executeSQL($con, $sql, "Added is_active column to categories table");
    } else {
        echo "<div style='color: blue; margin: 5px 0;'>ℹ️ is_active column already exists in categories</div>";
    }
} else {
    echo "<div style='color: red; margin: 5px 0;'>❌ categories table does not exist!</div>";
}

// Check and fix routes table
if (tableExists($con, 'routes')) {
    if (!columnExists($con, 'routes', 'is_active')) {
        $sql = "ALTER TABLE routes ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER estimated_duration";
        executeSQL($con, $sql, "Added is_active column to routes table");
    } else {
        echo "<div style='color: blue; margin: 5px 0;'>ℹ️ is_active column already exists in routes</div>";
    }
} else {
    echo "<div style='color: red; margin: 5px 0;'>❌ routes table does not exist!</div>";
}

// Update existing records to be active
$tables = ['bus_pass_types', 'categories', 'routes'];
foreach ($tables as $table) {
    if (tableExists($con, $table) && columnExists($con, $table, 'is_active')) {
        $sql = "UPDATE $table SET is_active = TRUE WHERE is_active IS NULL";
        executeSQL($con, $sql, "Updated existing records in $table to be active");
    }
}

// Add indexes if they don't exist
foreach ($tables as $table) {
    if (tableExists($con, $table) && columnExists($con, $table, 'is_active')) {
        $sql = "CREATE INDEX IF NOT EXISTS idx_{$table}_active ON $table(is_active)";
        executeSQL($con, $sql, "Added index on is_active for $table");
    }
}

echo "<h3>Verification:</h3>";
foreach ($tables as $table) {
    if (tableExists($con, $table)) {
        if (columnExists($con, $table, 'is_active')) {
            echo "<div style='color: green; margin: 5px 0;'>✅ $table table has is_active column</div>";
        } else {
            echo "<div style='color: red; margin: 5px 0;'>❌ $table table is missing is_active column</div>";
        }
    } else {
        echo "<div style='color: red; margin: 5px 0;'>❌ $table table does not exist!</div>";
    }
}

$con->close();
echo "<h3>Process completed!</h3>";
?> 