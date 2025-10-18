<?php
session_start();
include('includes/dbconnection.php');

// Redirect if already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin-dashboard.php');
    exit();
}

$message = '';
$messageType = '';

// Default admin credentials (in production, this should be in database)
$adminEmail = 'admin@buspass.com';
$adminPassword = 'admin123';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Please fill in all fields";
        $messageType = "error";
    } else if ($email === $adminEmail && $password === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $email;
        header("Location: admin-dashboard.php");
        exit();
    } else {
        $message = "Invalid admin credentials";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bus Pass Management System</title>
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
            letter-spacing: 0.5px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #FFFFFF;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(52, 152, 219, 0.5);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        input[type="email"]::placeholder,
        input[type="password"]::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        input[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(45deg, #3498db, #2980b9);
            border: none;
            border-radius: 10px;
            color: #FFFFFF;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        input[type="submit"]:hover {
            background: linear-gradient(45deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        input[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
            font-size: 0.95rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.error {
            background: rgba(231, 76, 60, 0.2);
            color: #E74C3C;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .user-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-link p {
            color: #BDC3C7;
            margin: 0;
            font-size: 0.9rem;
        }

        .user-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .user-link a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .demo-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .demo-info h3 {
            color: #FFFFFF;
            margin: 0 0 10px 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .demo-info p {
            color: #BDC3C7;
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .demo-info strong {
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <h2>Admin Login</h2>
            <p>Enter your credentials to access the admin panel</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <input type="submit" name="login" value="Login">
        </form>

        <div class="user-link">
            <p>Are you a user? <a href="login.php">Login here</a></p>
        </div>

        <div class="demo-info">
            <h3>Demo Credentials</h3>
            <p>Email: <strong>admin@buspass.com</strong><br>
            Password: <strong>admin123</strong></p>
        </div>
    </div>
</body>
</html>
