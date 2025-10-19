<?php
session_start();
include('includes/dbconnection.php');

// Redirect if already logged in
if (isset($_SESSION['uid'])) {
    header('Location: user-dashboard.php');
    exit();
}

$message = '';
$messageType = '';

// Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $message = $_SESSION['registration_success'];
    $messageType = "success";
    unset($_SESSION['registration_success']); // Clear the message after displaying
} elseif (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $message = "Registration successful! Please login with your credentials.";
    $messageType = "success";
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        $message = "Please fill in all fields";
        $messageType = "error";
    } else {
        $query = "SELECT * FROM users WHERE email=?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['uid'] = $user['id'];
            $_SESSION['uname'] = $user['full_name'];
            header("Location: user-dashboard.php");
            exit();
        } else {
            $message = "Invalid email or password";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Bus Pass Management System</title>
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

        .login-container {
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

        .login-container::before {
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

        input[type="email"], input[type="password"] {
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

        input[type="email"]:focus, input[type="password"]:focus {
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

        input[type="email"]::placeholder, input[type="password"]::placeholder {
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <h2>Bus Pass Management System</h2>
            <p>User Login</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <input type="submit" name="login" value="Login">
        </form>

        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p><a href="index.php">← Back to Home</a></p>
        </div>
    </div>
</body>
</html>