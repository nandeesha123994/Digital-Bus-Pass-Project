<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation - Nrupatunga Digital Bus Pass System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
        }
        .content {
            padding: 30px;
        }
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .nav-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .nav-card.main {
            border-left-color: #28a745;
        }
        .nav-card.admin {
            border-left-color: #dc3545;
        }
        .nav-card.test {
            border-left-color: #ffc107;
        }
        .nav-card.setup {
            border-left-color: #17a2b8;
        }
        .nav-card h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        .nav-card p {
            margin: 0 0 15px 0;
            color: #666;
        }
        .btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px 5px 5px 0;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .btn-success:hover {
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        .btn-danger:hover {
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #333;
        }
        .btn-warning:hover {
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
            color: #333;
        }
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }
        .btn-info:hover {
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.3);
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-online {
            background: #28a745;
        }
        .status-setup {
            background: #ffc107;
        }
        .status-test {
            background: #17a2b8;
        }
        .info {
            color: #0c5460;
            background: #d1ecf1;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚌 Nrupatunga Digital Bus Pass System</h1>
            <p>Complete Navigation & System Access</p>
        </div>
        <div class="content">
            <div class="info">
                <h3>🎯 System Status: Fully Operational</h3>
                <p><strong>✅ Currency:</strong> Indian Rupees (₹) with 18% GST</p>
                <p><strong>✅ Application ID:</strong> Generation working (BPMS format)</p>
                <p><strong>✅ Database:</strong> All tables and columns configured</p>
                <p><strong>✅ Features:</strong> All core functionality implemented</p>
            </div>

            <div class="nav-grid">
                <!-- Main User Features -->
                <div class="nav-card main">
                    <h3><span class="status-indicator status-online"></span>🏠 Main User Features</h3>
                    <p>Core functionality for end users</p>
                    <a href="index.php" class="btn btn-success">🏠 Homepage</a>
                    <a href="register.php" class="btn btn-success">👤 User Registration</a>
                    <a href="login.php" class="btn btn-success">🔐 User Login</a>
                    <a href="apply-pass.php" class="btn btn-success">📝 Apply for Pass</a>
                    <a href="user-dashboard.php" class="btn btn-success">📊 User Dashboard</a>
                    <a href="track-application.php" class="btn btn-success">🔍 Track Application</a>
                </div>

                <!-- Admin Features -->
                <div class="nav-card admin">
                    <h3><span class="status-indicator status-online"></span>🔐 Admin Features</h3>
                    <p>Administrative controls and management</p>
                    <a href="admin-login.php" class="btn btn-primary">🔐 Admin Login</a>
                    <a href="admin-dashboard.php" class="btn btn-primary">📊 Admin Dashboard</a>
                    <a href="manage-announcements.php" class="btn btn-primary">📢 Manage Announcements</a>
                    <a href="manage-categories.php" class="btn btn-primary">🏷️ Manage Categories</a>
                    <a href="admin-activity-log.php" class="btn btn-primary">📋 Activity Log</a>
                </div>

                <!-- Payment & Reviews -->
                <div class="nav-card main">
                    <h3><span class="status-indicator status-online"></span>💳 Payment & Reviews</h3>
                    <p>Payment processing and user feedback</p>
                    <a href="payment.php" class="btn btn-success">💳 Payment Gateway</a>
                    <a href="instant-reviews-display.php" class="btn btn-success">⭐ User Reviews</a>
                    <a href="generate-bus-pass.php" class="btn btn-success">🎫 Generate Pass</a>
                    <a href="download-bus-pass-pdf.php" class="btn btn-success">📄 Download PDF</a>
                </div>

                <!-- System Setup -->
                <div class="nav-card setup">
                    <h3><span class="status-indicator status-setup"></span>🔧 System Setup</h3>
                    <p>Database and system configuration</p>
                    <a href="complete_database_setup.php" class="btn btn-info">🗄️ Complete Database Setup</a>
                    <a href="fix_all_database_issues.php" class="btn btn-info">🔧 Fix All Issues</a>
                    <a href="update_currency_to_rupees.php" class="btn btn-info">💰 Currency Update</a>
                    <a href="setup_email.php" class="btn btn-info">📧 Email Setup</a>
                </div>

                <!-- Testing Tools -->
                <div class="nav-card test">
                    <h3><span class="status-indicator status-test"></span>🧪 Testing Tools</h3>
                    <p>System testing and verification</p>
                    <a href="test_system_functionality.php" class="btn btn-warning">🧪 System Test</a>
                    <a href="verify_currency_update.php" class="btn btn-warning">💰 Currency Test</a>
                    <a href="test_application_id_generation.php" class="btn btn-warning">🆔 ID Generation Test</a>
                    <a href="final_system_check.php" class="btn btn-warning">✅ Final Check</a>
                </div>

                <!-- Debug & Fix Tools -->
                <div class="nav-card test">
                    <h3><span class="status-indicator status-test"></span>🔍 Debug Tools</h3>
                    <p>Troubleshooting and debugging</p>
                    <a href="debug_apply_pass.php" class="btn btn-warning">🐛 Debug Apply Pass</a>
                    <a href="test_application_submission.php" class="btn btn-warning">📝 Test Submission</a>
                    <a href="direct_fix_photo_path.php" class="btn btn-warning">📸 Fix Photo Path</a>
                    <a href="fix_application_id_system.php" class="btn btn-warning">🆔 Fix App ID</a>
                </div>

                <!-- Information Pages -->
                <div class="nav-card main">
                    <h3><span class="status-indicator status-online"></span>📄 Information Pages</h3>
                    <p>Static content and support</p>
                    <a href="about-us.php" class="btn btn-success">ℹ️ About Us</a>
                    <a href="contact-support.php" class="btn btn-success">📞 Contact Support</a>
                    <a href="faqs.php" class="btn btn-success">❓ FAQs</a>
                    <a href="privacy-policy.php" class="btn btn-success">🔒 Privacy Policy</a>
                    <a href="terms-conditions.php" class="btn btn-success">📋 Terms & Conditions</a>
                </div>

                <!-- Quick Actions -->
                <div class="nav-card setup">
                    <h3><span class="status-indicator status-setup"></span>⚡ Quick Actions</h3>
                    <p>Common administrative tasks</p>
                    <a href="create_instant_reviews_sql.php" class="btn btn-info">⭐ Setup Reviews</a>
                    <a href="setup_announcements.php" class="btn btn-info">📢 Setup Announcements</a>
                    <a href="create-categories-table.php" class="btn btn-info">🏷️ Setup Categories</a>
                    <a href="configure_razorpay.php" class="btn btn-info">💳 Configure Payment</a>
                </div>
            </div>

            <div class="info">
                <h4>🔗 Quick Access URLs:</h4>
                <p><strong>Main System:</strong> <code>http://localhost/buspassmsfull/index.php</code></p>
                <p><strong>User Registration:</strong> <code>http://localhost/buspassmsfull/register.php</code></p>
                <p><strong>Apply Pass:</strong> <code>http://localhost/buspassmsfull/apply-pass.php</code></p>
                <p><strong>Admin Login:</strong> <code>http://localhost/buspassmsfull/admin-login.php</code></p>
                <p><strong>Navigation:</strong> <code>http://localhost/buspassmsfull/navigation.php</code></p>
                
                <h4>🔑 Login Credentials:</h4>
                <p><strong>👤 User:</strong> john@example.com / password123</p>
                <p><strong>🔐 Admin:</strong> admin@example.com / admin123</p>
                
                <h4>💡 Troubleshooting:</h4>
                <ul>
                    <li>Make sure XAMPP is running (Apache + MySQL)</li>
                    <li>Check that you're accessing: <code>http://localhost/buspassmsfull/</code></li>
                    <li>Verify the file exists in the correct directory</li>
                    <li>Clear browser cache if needed</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
