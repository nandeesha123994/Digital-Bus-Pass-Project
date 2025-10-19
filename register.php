<?php
session_start();
include('includes/dbconnection.php');

// Check if already logged in - show message instead of redirecting
$alreadyLoggedIn = isset($_SESSION['uid']);

$message = '';
$messageType = '';

if (isset($_POST['submit'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    $errors = [];

    if (empty($fullname) || strlen($fullname) < 2) {
        $errors[] = "Full name must be at least 2 characters long";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    if (empty($errors)) {
        $checkQuery = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Email address already exists";
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
        $stmt = $con->prepare($query);
        $stmt->bind_param("sss", $fullname, $email, $hashedPassword);

        if ($stmt->execute()) {
            // Set session variable for success message on login page
            $_SESSION['registration_success'] = "Registration successful! Please login with your credentials.";

            // Redirect to login page after successful registration
            header('Location: login.php?registered=1');
            exit();
        } else {
            $message = "Registration failed. Please try again.";
            $messageType = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bus Pass Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Roboto:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(180deg, #2C3E50 0%, #34495E 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Subtle background pattern for professional feel */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(52, 152, 219, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(155, 89, 182, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.02) 0%, transparent 50%);
            animation: subtleGlow 6s ease-in-out infinite alternate;
        }

        @keyframes subtleGlow {
            0% { opacity: 0.3; }
            100% { opacity: 0.7; }
        }

        .register-container {
            background: rgba(52, 73, 94, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px;
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow:
                0 0 30px rgba(255, 255, 255, 0.1),
                0 25px 50px rgba(0, 0, 0, 0.3),
                inset 0 0 30px rgba(255, 255, 255, 0.05);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg,
                rgba(255, 255, 255, 0.1),
                rgba(52, 152, 219, 0.1),
                rgba(255, 255, 255, 0.1),
                rgba(155, 89, 182, 0.1));
            border-radius: 22px;
            z-index: -1;
            animation: borderGlow 4s linear infinite;
        }

        @keyframes borderGlow {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h2 {
            color: #FFFFFF;
            margin: 0 0 15px 0;
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            text-shadow:
                0 0 10px rgba(255, 255, 255, 0.3),
                0 4px 8px rgba(0, 0, 0, 0.3);
            animation: titleGlow 3s ease-in-out infinite alternate;
        }

        .header p {
            color: #BDC3C7;
            margin: 0;
            font-size: 1.1rem;
            font-weight: 400;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        @keyframes titleGlow {
            0% {
                text-shadow:
                    0 0 10px rgba(255, 255, 255, 0.3),
                    0 4px 8px rgba(0, 0, 0, 0.3);
            }
            100% {
                text-shadow:
                    0 0 20px rgba(255, 255, 255, 0.5),
                    0 4px 8px rgba(0, 0, 0, 0.3);
            }
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #FFFFFF;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 16px;
            border: 2px solid #3498DB;
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 16px;
            background: #5D6D7E;
            color: #FFFFFF;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            box-shadow:
                0 0 10px rgba(52, 152, 219, 0.2),
                inset 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #3498DB;
            box-shadow:
                0 0 20px rgba(52, 152, 219, 0.4),
                inset 0 2px 4px rgba(0, 0, 0, 0.1);
            background: #6C7B7F;
            animation: inputGlow 2s ease-in-out infinite alternate;
        }

        @keyframes inputGlow {
            0% {
                box-shadow:
                    0 0 20px rgba(52, 152, 219, 0.4),
                    inset 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            100% {
                box-shadow:
                    0 0 30px rgba(52, 152, 219, 0.6),
                    inset 0 2px 4px rgba(0, 0, 0, 0.1);
            }
        }

        input[type="text"]::placeholder, input[type="email"]::placeholder, input[type="password"]::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        input[type="submit"] {
            width: 100%;
            background: #3498DB;
            color: #FFFFFF;
            padding: 18px;
            border: 2px solid #3498DB;
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow:
                0 0 20px rgba(52, 152, 219, 0.3),
                0 8px 16px rgba(0, 0, 0, 0.2),
                inset 0 0 20px rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            animation: buttonPulse 3s ease-in-out infinite alternate;
        }

        @keyframes buttonPulse {
            0% {
                box-shadow:
                    0 0 20px rgba(52, 152, 219, 0.3),
                    0 8px 16px rgba(0, 0, 0, 0.2),
                    inset 0 0 20px rgba(255, 255, 255, 0.1);
            }
            100% {
                box-shadow:
                    0 0 30px rgba(52, 152, 219, 0.5),
                    0 8px 16px rgba(0, 0, 0, 0.2),
                    inset 0 0 30px rgba(255, 255, 255, 0.15);
            }
        }

        input[type="submit"]:hover {
            background: #2980B9;
            border-color: #2980B9;
            box-shadow:
                0 0 40px rgba(52, 152, 219, 0.6),
                0 12px 24px rgba(0, 0, 0, 0.3),
                inset 0 0 40px rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        input[type="submit"]:active {
            transform: translateY(0);
            box-shadow:
                0 0 15px rgba(52, 152, 219, 0.4),
                0 4px 8px rgba(0, 0, 0, 0.2),
                inset 0 0 15px rgba(255, 255, 255, 0.1);
        }

        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            border: 2px solid;
        }

        .message.success {
            background: rgba(46, 204, 113, 0.1);
            color: #2ECC71;
            border-color: #2ECC71;
            box-shadow:
                0 0 15px rgba(46, 204, 113, 0.2),
                inset 0 0 15px rgba(46, 204, 113, 0.05);
            text-shadow: 0 0 5px rgba(46, 204, 113, 0.3);
        }

        .message.error {
            background: rgba(231, 76, 60, 0.1);
            color: #E74C3C;
            border-color: #E74C3C;
            box-shadow:
                0 0 15px rgba(231, 76, 60, 0.2),
                inset 0 0 15px rgba(231, 76, 60, 0.05);
            text-shadow: 0 0 5px rgba(231, 76, 60, 0.3);
        }

        .register-link {
            text-align: center;
            margin-top: 30px;
        }
        .register-link p {
            color: #BDC3C7;
            margin: 0;
            font-size: 0.95rem;
        }
        .register-link a {
            color: #9B59B6;
            text-decoration: none;
            font-weight: 600;
            text-shadow: 0 0 5px rgba(155, 89, 182, 0.3);
            transition: all 0.3s ease;
            border-bottom: 1px solid transparent;
        }
        .register-link a:hover {
            color: #FFFFFF;
            text-shadow:
                0 0 10px rgba(155, 89, 182, 0.6),
                0 0 20px rgba(155, 89, 182, 0.3);
            border-bottom: 1px solid #9B59B6;
            transform: translateY(-1px);
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        .checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #3498DB;
            filter: drop-shadow(0 0 3px rgba(52, 152, 219, 0.4));
            border: 2px solid #3498DB;
            border-radius: 4px;
        }

        .checkbox-container label {
            margin: 0;
            font-weight: 400;
            color: #FFFFFF;
            font-family: 'Inter', sans-serif;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="header">
            <h2>Bus Pass Management System</h2>
            <p>Create your account</p>
        </div>

        <?php if ($alreadyLoggedIn): ?>
            <div class="message success">
                <strong>You are already logged in!</strong><br>
                You can access your dashboard or logout to create a new account.
            </div>
            <div style="text-align: center; margin: 20px 0;">
                <a href="user-dashboard.php" style="background: #3498DB; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin: 5px; display: inline-block;">Go to Dashboard</a>
                <a href="logout.php" style="background: #E74C3C; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin: 5px; display: inline-block;">Logout</a>
            </div>
        <?php else: ?>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php">
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

            <div class="checkbox-container">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the Terms and Conditions</label>
            </div>

                <input type="submit" name="submit" value="Create Account">
            </form>

            <div class="register-link">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
                <p><a href="index.php">← Back to Home</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
