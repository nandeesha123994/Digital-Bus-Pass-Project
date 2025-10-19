<?php
/**
 * Database Update Script - Add Application ID field
 * Adds the application_id field to bus_pass_applications table
 */

include('includes/dbconnection.php');

echo "<h1>Database Update - Application ID Field</h1>";
echo "<p>This script adds the application_id field to the bus_pass_applications table.</p>";
echo "<hr>";

try {
    echo "<h2>Step 1: Checking if application_id column exists...</h2>";

    // Check if application_id column already exists
    $checkQuery = "SHOW COLUMNS FROM bus_pass_applications LIKE 'application_id'";
    $result = $con->query($checkQuery);

    if ($result->num_rows == 0) {
        echo "❌ application_id column does not exist. Adding it now...<br><br>";

        // Add application_id column
        echo "<h2>Step 2: Adding application_id column...</h2>";
        $alterQuery = "ALTER TABLE bus_pass_applications ADD COLUMN application_id VARCHAR(20) UNIQUE AFTER id";

        if ($con->query($alterQuery)) {
            echo "✅ Successfully added application_id column to bus_pass_applications table.<br><br>";

            echo "<h2>Step 3: Updating existing records...</h2>";
            // Update existing records with generated Application IDs
            $selectQuery = "SELECT id FROM bus_pass_applications WHERE application_id IS NULL OR application_id = ''";
            $existingRecords = $con->query($selectQuery);

            if ($existingRecords && $existingRecords->num_rows > 0) {
                echo "📝 Found {$existingRecords->num_rows} records to update...<br>";

                while ($row = $existingRecords->fetch_assoc()) {
                    $applicationId = generateApplicationId($con);
                    $updateQuery = "UPDATE bus_pass_applications SET application_id = ? WHERE id = ?";
                    $stmt = $con->prepare($updateQuery);
                    $stmt->bind_param("si", $applicationId, $row['id']);

                    if ($stmt->execute()) {
                        echo "✅ Updated record ID {$row['id']} with Application ID: <strong>$applicationId</strong><br>";
                    } else {
                        echo "❌ Failed to update record ID {$row['id']}: " . $con->error . "<br>";
                    }
                }
            } else {
                echo "ℹ️ No existing records need updating.<br>";
            }

            echo "<br><h2>✅ Database update completed successfully!</h2>";
            echo "<p>The application_id field has been added and all existing records have been updated.</p>";

        } else {
            echo "❌ Error adding application_id column: " . $con->error . "<br>";
            echo "<p>Please check your database connection and permissions.</p>";
        }
    } else {
        echo "✅ application_id column already exists in bus_pass_applications table.<br>";

        // Check if any records need updating
        echo "<h2>Step 2: Checking for records without Application IDs...</h2>";
        $selectQuery = "SELECT id FROM bus_pass_applications WHERE application_id IS NULL OR application_id = ''";
        $existingRecords = $con->query($selectQuery);

        if ($existingRecords && $existingRecords->num_rows > 0) {
            echo "📝 Found {$existingRecords->num_rows} records without Application IDs. Updating...<br>";

            while ($row = $existingRecords->fetch_assoc()) {
                $applicationId = generateApplicationId($con);
                $updateQuery = "UPDATE bus_pass_applications SET application_id = ? WHERE id = ?";
                $stmt = $con->prepare($updateQuery);
                $stmt->bind_param("si", $applicationId, $row['id']);

                if ($stmt->execute()) {
                    echo "✅ Updated record ID {$row['id']} with Application ID: <strong>$applicationId</strong><br>";
                } else {
                    echo "❌ Failed to update record ID {$row['id']}: " . $con->error . "<br>";
                }
            }
            echo "<br>✅ All records updated successfully!<br>";
        } else {
            echo "✅ All records already have Application IDs.<br>";
        }
    }

    // Show current table structure
    echo "<h2>Current Table Structure:</h2>";
    $structureQuery = "DESCRIBE bus_pass_applications";
    $structureResult = $con->query($structureQuery);

    if ($structureResult) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
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

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<p>Please check your database connection and try again.</p>";
}

function generateApplicationId() {
    global $con;

    do {
        $year = date('Y');
        $randomNumber = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $applicationId = "BPMS{$year}{$randomNumber}";

        // Check if this ID already exists
        $checkQuery = "SELECT id FROM bus_pass_applications WHERE application_id = ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->bind_param("s", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();

    } while ($result->num_rows > 0); // Keep generating until we get a unique ID

    return $applicationId;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Update - Application ID Field</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Update - Application ID Field</h1>
        <p>This script adds the application_id field to the bus_pass_applications table and updates existing records.</p>
        <hr>
    </div>
</body>
</html>
