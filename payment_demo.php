<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment System Demo - Bus Pass Management</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 2.5em;
        }
        .header p {
            color: #666;
            font-size: 1.2em;
            margin: 10px 0;
        }
        .demo-section {
            margin: 30px 0;
            padding: 25px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #f9f9f9;
        }
        .demo-section h2 {
            color: #007bff;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .payment-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .payment-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.2);
        }
        .payment-card h3 {
            margin-top: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .test-cards {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .test-cards h4 {
            margin-top: 0;
            color: #0066cc;
        }
        .card-number {
            font-family: monospace;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin: 5px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .feature-list li i {
            color: #28a745;
            width: 20px;
        }
        .workflow {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .workflow-step {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e0e0e0;
        }
        .workflow-step .step-number {
            background: #007bff;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-weight: bold;
            font-size: 1.2em;
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
            <h1><i class="fas fa-bus"></i> Enhanced Payment System Demo</h1>
            <p>Complete Bus Pass Management with Real Payment Processing & Email Notifications</p>
        </div>

        <div class="demo-section">
            <h2><i class="fas fa-credit-card"></i> Payment Methods Available</h2>

            <!-- Payment Service Update Notice -->
            <div class="alert alert-info" style="margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i>
                <strong>Payment Service Update:</strong> We have upgraded our payment infrastructure.
                PhonePe is now our primary payment partner for Indian users, offering secure and convenient transactions.
                Razorpay is temporarily under maintenance.
            </div>

            <div class="payment-methods">
                <div class="payment-card">
                    <h3><i class="fab fa-stripe"></i> Stripe Payment</h3>
                    <p>Credit/Debit card processing with real-time validation</p>
                    <div class="test-cards">
                        <h4>Test Card Numbers:</h4>
                        <div class="card-number">4242 4242 4242 4242</div> - Visa (Success)<br>
                        <div class="card-number">4000 0000 0000 0002</div> - Visa (Declined)<br>
                        <div class="card-number">5555 5555 5555 4444</div> - Mastercard<br>
                        <strong>Expiry:</strong> Any future date | <strong>CVV:</strong> Any 3 digits
                    </div>
                </div>

                <div class="payment-card">
                    <h3><i class="fas fa-mobile-alt" style="color: #5f259f;"></i> PhonePe Payment</h3>
                    <p>UPI, Cards, Wallets, Net Banking (India)</p>
                    <div class="test-cards">
                        <h4>Available Features:</h4>
                        • UPI payments<br>
                        • Credit/Debit cards<br>
                        • Digital wallets<br>
                        • Net banking<br>
                        • Instant transfers
                    </div>
                    <div class="alert alert-info" style="margin-top: 10px; font-size: 0.9em;">
                        <strong>Status:</strong> Active and ready for payments
                    </div>
                </div>

                <div class="payment-card">
                    <h3><i class="fas fa-play-circle"></i> Demo Payment</h3>
                    <p>Instant simulation for testing purposes</p>
                    <div class="test-cards">
                        <h4>Perfect for:</h4>
                        • System testing<br>
                        • Demo presentations<br>
                        • Development phase<br>
                        • No real money involved
                    </div>
                </div>
            </div>
        </div>

        <div class="demo-section">
            <h2><i class="fas fa-envelope"></i> Email Notification System</h2>
            <ul class="feature-list">
                <li><i class="fas fa-check"></i> Application Confirmation Email</li>
                <li><i class="fas fa-check"></i> Payment Success Notification</li>
                <li><i class="fas fa-check"></i> Status Update Alerts</li>
                <li><i class="fas fa-check"></i> Pass Activation Email</li>
                <li><i class="fas fa-check"></i> Professional HTML Templates</li>
                <li><i class="fas fa-check"></i> Automatic Delivery</li>
            </ul>
            <a href="test_email.php" class="btn">Test Email System</a>
        </div>

        <div class="demo-section">
            <h2><i class="fas fa-route"></i> Complete User Workflow</h2>
            <div class="workflow">
                <div class="workflow-step">
                    <div class="step-number">1</div>
                    <h4>Register/Login</h4>
                    <p>Create account or sign in to existing account</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">2</div>
                    <h4>Apply for Pass</h4>
                    <p>Fill application form and upload photo</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">3</div>
                    <h4>Receive Email</h4>
                    <p>Get application confirmation email</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">4</div>
                    <h4>Make Payment</h4>
                    <p>Choose payment method and complete transaction</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">5</div>
                    <h4>Payment Confirmation</h4>
                    <p>Receive payment success email with details</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">6</div>
                    <h4>Admin Processing</h4>
                    <p>Admin reviews and approves application</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">7</div>
                    <h4>Pass Activation</h4>
                    <p>Get pass activation email with validity details</p>
                </div>
                <div class="workflow-step">
                    <div class="step-number">8</div>
                    <h4>Track Status</h4>
                    <p>Monitor application status in user dashboard</p>
                </div>
            </div>
        </div>

        <div class="demo-section">
            <h2><i class="fas fa-cogs"></i> System Features</h2>
            <div class="payment-methods">
                <div class="payment-card">
                    <h3><i class="fas fa-shield-alt"></i> Security</h3>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Secure payment processing</li>
                        <li><i class="fas fa-check"></i> Data encryption</li>
                        <li><i class="fas fa-check"></i> Input validation</li>
                        <li><i class="fas fa-check"></i> SQL injection protection</li>
                    </ul>
                </div>
                <div class="payment-card">
                    <h3><i class="fas fa-users-cog"></i> Admin Features</h3>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Payment management</li>
                        <li><i class="fas fa-check"></i> Transaction tracking</li>
                        <li><i class="fas fa-check"></i> Application processing</li>
                        <li><i class="fas fa-check"></i> Email notifications</li>
                    </ul>
                </div>
                <div class="payment-card">
                    <h3><i class="fas fa-mobile-alt"></i> User Experience</h3>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Responsive design</li>
                        <li><i class="fas fa-check"></i> Real-time validation</li>
                        <li><i class="fas fa-check"></i> Status tracking</li>
                        <li><i class="fas fa-check"></i> Payment history</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong><i class="fas fa-info-circle"></i> Demo Instructions:</strong><br>
            1. Start by setting up the database: <a href="setup_database.php">Database Setup</a><br>
            2. Test email functionality: <a href="test_email.php">Email Test</a><br>
            3. Try the complete workflow: <a href="register.php">Register</a> → <a href="apply-pass.php">Apply</a> → Payment<br>
            4. Admin access: <a href="admin-login.php">Admin Login</a> (admin@buspass.com / admin123)
        </div>

        <div class="alert alert-warning">
            <strong><i class="fas fa-exclamation-triangle"></i> Note:</strong>
            This is a demo system. For production use, configure real payment gateway credentials and SMTP settings in the config file.
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn btn-success">
                <i class="fas fa-home"></i> Go to Main System
            </a>
        </div>
    </div>
</body>
</html>
