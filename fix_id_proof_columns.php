<?php
/**
 * Database Fix Script - Add ID Proof Columns
 * Fixes the "Field 'id_proof_type' doesn't have a default value" error
 */

include('includes/dbconnection.php');

echo "<h1>Database Fix - ID Proof Columns</h1>";
echo "<p>This script adds the missing id_proof_type and id_proof_number columns to fix the database error.</p>";
echo "<hr>";

try {
    echo "<h2>Step 1: Checking current table structure...</h2>";
    
    // Get current table structure
    $columnsResult = $con->query("DESCRIBE bus_pass_applications");
    $existingColumns = [];
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Current columns in bus_pass_applications table:</strong><br>";
    while ($column = $columnsResult->fetch_assoc()) {
        $existingColumns[] = $column['Field'];
        echo "• " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }
    echo "</div>";
    
    echo "<h2>Step 2: Adding missing ID proof columns...</h2>";
    
    $hasIdProofType = in_array('id_proof_type', $existingColumns);
    $hasIdProofNumber = in_array('id_proof_number', $existingColumns);
    
    // Add id_proof_type column if it doesn't exist
    if (!$hasIdProofType) {
        echo "<p>Adding id_proof_type column...</p>";
        $sql = "ALTER TABLE bus_pass_applications ADD COLUMN id_proof_type VARCHAR(50) NOT NULL DEFAULT 'Aadhaar Card' AFTER destination";
        
        if ($con->query($sql) === TRUE) {
            echo "<div style='color: green; font-weight: bold;'>✅ Successfully added id_proof_type column</div>";
        } else {
            echo "<div style='color: red; font-weight: bold;'>❌ Error adding id_proof_type column: " . $con->error . "</div>";
        }
    } else {
        echo "<div style='color: blue;'>ℹ️ id_proof_type column already exists</div>";
    }
    
    // Add id_proof_number column if it doesn't exist
    if (!$hasIdProofNumber) {
        echo "<p>Adding id_proof_number column...</p>";
        $sql = "ALTER TABLE bus_pass_applications ADD COLUMN id_proof_number VARCHAR(50) NOT NULL DEFAULT '' AFTER id_proof_type";
        
        if ($con->query($sql) === TRUE) {
            echo "<div style='color: green; font-weight: bold;'>✅ Successfully added id_proof_number column</div>";
        } else {
            echo "<div style='color: red; font-weight: bold;'>❌ Error adding id_proof_number column: " . $con->error . "</div>";
        }
    } else {
        echo "<div style='color: blue;'>ℹ️ id_proof_number column already exists</div>";
    }
    
    echo "<h2>Step 3: Verification - Updated table structure</h2>";
    
    // Show updated table structure
    $columnsResult = $con->query("DESCRIBE bus_pass_applications");
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Updated columns in bus_pass_applications table:</strong><br>";
    while ($column = $columnsResult->fetch_assoc()) {
        $isNew = in_array($column['Field'], ['id_proof_type', 'id_proof_number']);
        $style = $isNew ? "color: green; font-weight: bold;" : "";
        echo "<span style='$style'>• " . $column['Field'] . " (" . $column['Type'] . ")</span><br>";
    }
    echo "</div>";
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Success!</strong> The ID proof columns have been added to the database.<br>";
    echo "The 'Field id_proof_type doesn't have a default value' error should now be resolved.<br><br>";
    echo "<strong>Next steps:</strong><br>";
    echo "• Try submitting a bus pass application again<br>";
    echo "• The form now includes ID proof type and number fields<br>";
    echo "• All new applications will include ID proof information";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</div>";
}

$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Fix - ID Proof Columns</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
        }
        h1 { color: #007bff; }
        h2 { color: #333; margin-top: 30px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <div style="text-align: center; margin-top: 30px;">
            <a href="apply-pass.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                Test Apply Pass Form
            </a>
            <a href="index.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
                Back to Homepage
            </a>
        </div>
    </div>
</body>
</html>
