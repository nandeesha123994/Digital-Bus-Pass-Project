<?php
session_start();
include('includes/dbconnection.php');

$message = '';
$messageType = '';

if (isset($_POST['submit'])) {
    echo "<h3>Form submitted! POST data received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    echo "<h3>Processing registration...</h3>";
    echo "Full Name: " . htmlspecialchars($fullname) . "<br>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
    echo "Password Length: " . strlen($password) . "<br>";
    echo "Passwords Match: " . ($password === $confirm_password ? 'Yes' : 'No') . "<br>";

    // Validation
    if (empty($fullname) || strlen($fullname) < 2) {
        $message = "Full name must be at least 2 characters long.";
        $messageType = "error";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } else {
        // Check if email already exists
        $checkQuery = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Email address already registered. Please use a different email.";
            $messageType = "error";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $insertQuery = "INSERT INTO users (full_name, email, password, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $con->prepare($insertQuery);
            $stmt->bind_param("sss", $fullname, $email, $hashedPassword);

            if ($stmt->execute()) {
                echo "<h3 style='color: green;'>Registration successful!</h3>";
                echo "<p>User ID: " . $stmt->insert_id . "</p>";
                echo "<p>Redirecting to login page in 3 seconds...</p>";
                
                // Set session variable for success message
                $_SESSION['registration_success'] = "Registration successful! Please login with your credentials.";
                
                // JavaScript redirect after 3 seconds for testing
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php?registered=1';
                    }, 3000);
                </script>";
                
                // Also provide manual link
                echo "<p><a href='login.php?registered=1'>Click here to go to login page</a></p>";
                
            } else {
                $message = "Registration failed: " . $con->error;
                $messageType = "error";
                echo "<h3 style='color: red;'>Registration failed!</h3>";
                echo "<p>Error: " . $con->error . "</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Registration - Bus Pass Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background: #218838;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .debug {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Registration Form</h1>
        <p>This is a simplified version to test the registration functionality.</p>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="fullname">Full Name:</label>
                <input type="text" id="fullname" name="fullname" required
                       value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password (minimum 6 characters):</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <input type="submit" name="submit" value="Test Register">
        </form>

        <div class="debug">
            <h3>Debug Information:</h3>
            <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
            <p><strong>Database Connection:</strong> <?php echo $con ? 'Connected' : 'Failed'; ?></p>
            <p><strong>Form Method:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></p>
            <p><strong>POST Data:</strong> <?php echo empty($_POST) ? 'No POST data' : 'POST data received'; ?></p>
        </div>

        <p><a href="register.php">← Back to Main Registration Form</a></p>
        <p><a href="login.php">Go to Login Page</a></p>
    </div>
</body>
</html>
