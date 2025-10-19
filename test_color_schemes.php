<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Color Schemes - Nrupatunga Digital Bus Pass System</title>
    <link rel="stylesheet" href="assets/css/color-schemes.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .main-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .main-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5rem;
            color: #333;
        }
        .main-header p {
            margin: 0;
            color: #666;
            font-size: 1.2rem;
        }
        .color-scheme-section {
            margin-bottom: 40px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .scheme-demo {
            padding: 30px;
        }
        .scheme-title {
            margin: 0 0 20px 0;
            font-size: 1.8rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .demo-card {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .demo-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin: 20px 0;
        }
        .usage-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .usage-info h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .usage-info ul {
            margin: 0;
            padding-left: 20px;
        }
        .usage-info li {
            color: #666;
            margin: 5px 0;
        }
        .test-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        .test-link {
            text-decoration: none;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .test-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-header">
            <h1><i class="fas fa-palette"></i> Color Schemes Test</h1>
            <p>Testing 4 different color schemes for user interactions</p>
        </div>

        <!-- Ocean Blue Scheme -->
        <div class="color-scheme-section">
            <div class="theme-ocean-blue" style="padding: 30px; color: white;">
                <h2><i class="fas fa-water"></i> Ocean Blue Theme</h2>
                <p>Primary actions like login, registration, and main navigation</p>
            </div>
            <div class="scheme-demo">
                <h3 class="scheme-title"><i class="fas fa-sign-in-alt"></i> Ocean Blue - Primary Actions</h3>
                
                <div class="demo-buttons">
                    <button class="btn btn-ocean-blue"><i class="fas fa-sign-in-alt"></i> User Login</button>
                    <button class="btn btn-ocean-blue"><i class="fas fa-user-plus"></i> Register Now</button>
                    <button class="btn btn-ocean-blue"><i class="fas fa-home"></i> Homepage</button>
                    <button class="btn btn-ocean-blue"><i class="fas fa-tachometer-alt"></i> Dashboard</button>
                </div>

                <div class="demo-grid">
                    <div class="demo-card card-ocean-blue">
                        <h4>Navigation Card</h4>
                        <p>Ocean blue themed card for primary navigation elements</p>
                    </div>
                    <div class="demo-card">
                        <span class="badge-ocean-blue">Ocean Blue Badge</span>
                        <p style="margin-top: 15px;">Status badges and labels</p>
                    </div>
                </div>

                <div class="usage-info">
                    <h4>Usage:</h4>
                    <ul>
                        <li>Login and registration pages</li>
                        <li>Main navigation elements</li>
                        <li>Primary user actions</li>
                        <li>Homepage buttons</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sunset Orange Scheme -->
        <div class="color-scheme-section">
            <div class="theme-sunset-orange" style="padding: 30px; color: white;">
                <h2><i class="fas fa-sun"></i> Sunset Orange Theme</h2>
                <p>Application actions like apply pass, submit forms, and payments</p>
            </div>
            <div class="scheme-demo">
                <h3 class="scheme-title"><i class="fas fa-paper-plane"></i> Sunset Orange - Application Actions</h3>
                
                <div class="demo-buttons">
                    <button class="btn btn-sunset-orange"><i class="fas fa-paper-plane"></i> Submit Application</button>
                    <button class="btn btn-sunset-orange"><i class="fas fa-credit-card"></i> Make Payment</button>
                    <button class="btn btn-sunset-orange"><i class="fas fa-upload"></i> Upload Documents</button>
                    <button class="btn btn-sunset-orange"><i class="fas fa-cog"></i> Admin Panel</button>
                </div>

                <div class="demo-grid">
                    <div class="demo-card card-sunset-orange">
                        <h4>Application Card</h4>
                        <p>Sunset orange themed card for application-related content</p>
                    </div>
                    <div class="demo-card">
                        <span class="badge-sunset-orange">Processing</span>
                        <p style="margin-top: 15px;">Application status badges</p>
                    </div>
                </div>

                <div class="usage-info">
                    <h4>Usage:</h4>
                    <ul>
                        <li>Apply pass form</li>
                        <li>Payment processing</li>
                        <li>Form submissions</li>
                        <li>Admin actions</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sky Blue Scheme -->
        <div class="color-scheme-section">
            <div class="theme-sky-blue" style="padding: 30px; color: white;">
                <h2><i class="fas fa-cloud"></i> Sky Blue Theme</h2>
                <p>Information actions like track application, view status, and help</p>
            </div>
            <div class="scheme-demo">
                <h3 class="scheme-title"><i class="fas fa-search"></i> Sky Blue - Information Actions</h3>
                
                <div class="demo-buttons">
                    <button class="btn btn-sky-blue"><i class="fas fa-search"></i> Track Application</button>
                    <button class="btn btn-sky-blue"><i class="fas fa-info-circle"></i> View Status</button>
                    <button class="btn btn-sky-blue"><i class="fas fa-question-circle"></i> Help & Support</button>
                    <button class="btn btn-sky-blue"><i class="fas fa-chart-bar"></i> View Reports</button>
                </div>

                <div class="demo-grid">
                    <div class="demo-card card-sky-blue">
                        <h4>Information Card</h4>
                        <p>Sky blue themed card for informational content</p>
                    </div>
                    <div class="demo-card">
                        <span class="badge-sky-blue">Tracking</span>
                        <p style="margin-top: 15px;">Information status badges</p>
                    </div>
                </div>

                <div class="usage-info">
                    <h4>Usage:</h4>
                    <ul>
                        <li>Track application page</li>
                        <li>Status viewing</li>
                        <li>Help and support</li>
                        <li>Information displays</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Soft Pink Scheme -->
        <div class="color-scheme-section">
            <div class="theme-soft-pink" style="padding: 30px; color: #333;">
                <h2><i class="fas fa-heart"></i> Soft Pink Theme</h2>
                <p>Secondary actions like reviews, feedback, and support features</p>
            </div>
            <div class="scheme-demo">
                <h3 class="scheme-title"><i class="fas fa-star"></i> Soft Pink - Secondary Actions</h3>
                
                <div class="demo-buttons">
                    <button class="btn btn-soft-pink"><i class="fas fa-star"></i> Write Review</button>
                    <button class="btn btn-soft-pink"><i class="fas fa-comment"></i> Give Feedback</button>
                    <button class="btn btn-soft-pink"><i class="fas fa-headset"></i> Contact Support</button>
                    <button class="btn btn-soft-pink"><i class="fas fa-thumbs-up"></i> Rate Service</button>
                </div>

                <div class="demo-grid">
                    <div class="demo-card card-soft-pink">
                        <h4>Feedback Card</h4>
                        <p>Soft pink themed card for feedback and review content</p>
                    </div>
                    <div class="demo-card">
                        <span class="badge-soft-pink">5 Stars</span>
                        <p style="margin-top: 15px;">Review and rating badges</p>
                    </div>
                </div>

                <div class="usage-info">
                    <h4>Usage:</h4>
                    <ul>
                        <li>Reviews and ratings</li>
                        <li>Feedback forms</li>
                        <li>Support features</li>
                        <li>Secondary actions</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Test Links -->
        <div class="color-scheme-section">
            <div style="padding: 30px;">
                <h3 class="scheme-title"><i class="fas fa-rocket"></i> Test Live Pages</h3>
                <p>Click the links below to see the color schemes in action on actual pages:</p>
                
                <div class="test-links">
                    <a href="index.php" class="test-link btn-ocean-blue">
                        <i class="fas fa-home"></i> Homepage (Ocean Blue)
                    </a>
                    <a href="apply-pass.php" class="test-link btn-sunset-orange">
                        <i class="fas fa-paper-plane"></i> Apply Pass (Sunset Orange)
                    </a>
                    <a href="track-application.php" class="test-link btn-sky-blue">
                        <i class="fas fa-search"></i> Track App (Sky Blue)
                    </a>
                    <a href="instant-reviews-display.php" class="test-link btn-soft-pink">
                        <i class="fas fa-star"></i> Reviews (Soft Pink)
                    </a>
                    <a href="login.php" class="test-link btn-ocean-blue">
                        <i class="fas fa-sign-in-alt"></i> Login (Ocean Blue)
                    </a>
                    <a href="register.php" class="test-link btn-ocean-blue">
                        <i class="fas fa-user-plus"></i> Register (Ocean Blue)
                    </a>
                    <a href="user-dashboard.php" class="test-link btn-ocean-blue">
                        <i class="fas fa-tachometer-alt"></i> User Dashboard
                    </a>
                    <a href="admin-dashboard.php" class="test-link btn-sunset-orange">
                        <i class="fas fa-cog"></i> Admin Dashboard
                    </a>
                </div>

                <div class="usage-info">
                    <h4><i class="fas fa-lightbulb"></i> Color Scheme Benefits:</h4>
                    <ul>
                        <li><strong>Visual Distinction:</strong> Different colors help users quickly identify action types</li>
                        <li><strong>Better UX:</strong> Intuitive color coding improves user experience</li>
                        <li><strong>Modern Design:</strong> Gradient backgrounds and smooth transitions</li>
                        <li><strong>Accessibility:</strong> High contrast and readable color combinations</li>
                        <li><strong>Consistency:</strong> Unified design language across the entire system</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
