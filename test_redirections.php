<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Redirections - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 {
            color: #333;
            margin: 0;
        }
        .test-section {
            margin: 25px 0;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #f9f9f9;
        }
        .test-section h2 {
            color: #007bff;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .test-link {
            display: block;
            padding: 15px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            text-align: center;
        }
        .test-link:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
            text-decoration: none;
            color: #007bff;
        }
        .test-link i {
            font-size: 2em;
            margin-bottom: 10px;
            display: block;
        }
        .test-link .title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .test-link .description {
            font-size: 0.9em;
            color: #666;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: 10px;
        }
        .status-working {
            background: #28a745;
        }
        .status-protected {
            background: #ffc107;
        }
        .status-error {
            background: #dc3545;
        }
        .legend {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
            font-size: 0.9em;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-route"></i> Redirection Test Center</h1>
            <p>Test all page redirections and navigation in the Bus Pass Management System</p>
        </div>

        <div class="legend">
            <div class="legend-item">
                <span class="status-indicator status-working"></span>
                <span>Working - Page loads correctly</span>
            </div>
            <div class="legend-item">
                <span class="status-indicator status-protected"></span>
                <span>Protected - Requires login/authentication</span>
            </div>
            <div class="legend-item">
                <span class="status-indicator status-error"></span>
                <span>Error - Page has issues</span>
            </div>
        </div>

        <div class="test-section">
            <h2><i class="fas fa-home"></i> Public Pages</h2>
            <div class="test-grid">
                <a href="index.php" class="test-link" target="_blank">
                    <i class="fas fa-home"></i>
                    <div class="title">Home Page <span class="status-indicator status-working"></span></div>
                    <div class="description">Main landing page</div>
                </a>
                <a href="login.php" class="test-link" target="_blank">
                    <i class="fas fa-sign-in-alt"></i>
                    <div class="title">User Login <span class="status-indicator status-working"></span></div>
                    <div class="description">User authentication</div>
                </a>
                <a href="register.php" class="test-link" target="_blank">
                    <i class="fas fa-user-plus"></i>
                    <div class="title">User Registration <span class="status-indicator status-working"></span></div>
                    <div class="description">New user signup</div>
                </a>
                <a href="admin-login.php" class="test-link" target="_blank">
                    <i class="fas fa-cog"></i>
                    <div class="title">Admin Login <span class="status-indicator status-working"></span></div>
                    <div class="description">Admin authentication</div>
                </a>
                <a href="track-status.php" class="test-link" target="_blank">
                    <i class="fas fa-search"></i>
                    <div class="title">Track Status <span class="status-indicator status-working"></span></div>
                    <div class="description">Application status tracking</div>
                </a>
            </div>
        </div>

        <div class="test-section">
            <h2><i class="fas fa-user-shield"></i> Protected User Pages</h2>
            <div class="alert alert-warning">
                <strong><i class="fas fa-exclamation-triangle"></i> Note:</strong>
                These pages require user login. You'll be redirected to login page if not authenticated.
            </div>
            <div class="test-grid">
                <a href="user-dashboard.php" class="test-link" target="_blank">
                    <i class="fas fa-tachometer-alt"></i>
                    <div class="title">User Dashboard <span class="status-indicator status-protected"></span></div>
                    <div class="description">User's main dashboard</div>
                </a>
                <a href="apply-pass.php" class="test-link" target="_blank">
                    <i class="fas fa-plus"></i>
                    <div class="title">Apply for Pass <span class="status-indicator status-protected"></span></div>
                    <div class="description">Bus pass application form</div>
                </a>
                <a href="payment.php?application_id=1" class="test-link" target="_blank">
                    <i class="fas fa-credit-card"></i>
                    <div class="title">Payment Page <span class="status-indicator status-protected"></span></div>
                    <div class="description">Payment processing</div>
                </a>
                <a href="payment_receipt.php?payment_id=1" class="test-link" target="_blank">
                    <i class="fas fa-receipt"></i>
                    <div class="title">Payment Receipt <span class="status-indicator status-protected"></span></div>
                    <div class="description">Payment receipt view</div>
                </a>
            </div>
        </div>

        <div class="test-section">
            <h2><i class="fas fa-user-cog"></i> Protected Admin Pages</h2>
            <div class="alert alert-warning">
                <strong><i class="fas fa-exclamation-triangle"></i> Note:</strong>
                These pages require admin login. Use: admin@buspass.com / admin123
            </div>
            <div class="test-grid">
                <a href="admin-dashboard.php" class="test-link" target="_blank">
                    <i class="fas fa-chart-line"></i>
                    <div class="title">Admin Dashboard <span class="status-indicator status-protected"></span></div>
                    <div class="description">Admin control panel</div>
                </a>
            </div>
        </div>

        <div class="test-section">
            <h2><i class="fas fa-tools"></i> Utility Pages</h2>
            <div class="test-grid">
                <a href="setup_database.php" class="test-link" target="_blank">
                    <i class="fas fa-database"></i>
                    <div class="title">Database Setup <span class="status-indicator status-working"></span></div>
                    <div class="description">Initialize database tables</div>
                </a>
                <a href="test_database.php" class="test-link" target="_blank">
                    <i class="fas fa-check-circle"></i>
                    <div class="title">Database Test <span class="status-indicator status-working"></span></div>
                    <div class="description">Verify database connection</div>
                </a>
                <a href="test_email.php" class="test-link" target="_blank">
                    <i class="fas fa-envelope"></i>
                    <div class="title">Email Test <span class="status-indicator status-working"></span></div>
                    <div class="description">Test email functionality</div>
                </a>
                <a href="payment_demo.php" class="test-link" target="_blank">
                    <i class="fas fa-play-circle"></i>
                    <div class="title">Payment Demo <span class="status-indicator status-working"></span></div>
                    <div class="description">Payment system showcase</div>
                </a>
                <a href="setup_email.php" class="test-link" target="_blank">
                    <i class="fas fa-envelope-open"></i>
                    <div class="title">Email Setup Wizard <span class="status-indicator status-working"></span></div>
                    <div class="description">Comprehensive email configuration</div>
                </a>
                <a href="configure_xampp_email.php" class="test-link" target="_blank">
                    <i class="fas fa-server"></i>
                    <div class="title">XAMPP Email Config <span class="status-indicator status-working"></span></div>
                    <div class="description">XAMPP-specific email setup</div>
                </a>
                <a href="test_razorpay.php" class="test-link" target="_blank">
                    <i class="fas fa-credit-card"></i>
                    <div class="title">Razorpay Test <span class="status-indicator status-working"></span></div>
                    <div class="description">Test Razorpay integration</div>
                </a>
            </div>
        </div>

        <div class="test-section">
            <h2><i class="fas fa-sign-out-alt"></i> Logout Actions</h2>
            <div class="test-grid">
                <a href="logout.php" class="test-link" target="_blank">
                    <i class="fas fa-sign-out-alt"></i>
                    <div class="title">User Logout <span class="status-indicator status-working"></span></div>
                    <div class="description">Logout and redirect to login</div>
                </a>
                <a href="admin-logout.php" class="test-link" target="_blank">
                    <i class="fas fa-user-times"></i>
                    <div class="title">Admin Logout <span class="status-indicator status-working"></span></div>
                    <div class="description">Admin logout and redirect</div>
                </a>
            </div>
        </div>

        <div class="alert alert-info">
            <strong><i class="fas fa-info-circle"></i> Testing Instructions:</strong><br>
            1. <strong>Public Pages:</strong> Should load without any authentication<br>
            2. <strong>Protected Pages:</strong> Should redirect to appropriate login page if not authenticated<br>
            3. <strong>Authenticated Access:</strong> Login first, then test protected pages<br>
            4. <strong>Navigation:</strong> Check that all navigation links work correctly<br>
            5. <strong>Logout:</strong> Verify logout redirects to correct pages
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" style="color: #007bff; text-decoration: none; font-size: 1.1em;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>
