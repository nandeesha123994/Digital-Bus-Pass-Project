<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Action Buttons - Nrupatunga Smart Bus Pass Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
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

        /* Action Buttons Demo */
        .action-buttons-demo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
            padding: 0 1rem;
        }
        .action-btn-demo {
            padding: 1.5rem 2.5rem;
            text-decoration: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: white;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border: none;
            cursor: pointer;
        }
        .action-btn-demo i {
            font-size: 1.3rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        .action-btn-demo::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            transition: all 0.6s ease;
            opacity: 0;
        }
        .action-btn-demo:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }
        .action-btn-demo:hover {
            transform: translateY(-4px) scale(1.02);
            text-decoration: none;
            color: white;
        }

        /* Ocean Blue Theme - Primary Actions (Login, Register) */
        .btn-ocean-blue {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        .btn-ocean-blue:hover {
            box-shadow: 0 12px 35px rgba(99, 102, 241, 0.4);
        }

        /* Sky Blue Theme - Information Actions (Track Status) */
        .btn-sky-blue {
            background: linear-gradient(135deg, #3B82F6 0%, #06B6D4 100%);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        .btn-sky-blue:hover {
            box-shadow: 0 12px 35px rgba(59, 130, 246, 0.4);
        }

        /* Sunset Orange Theme - Admin Actions (Admin Panel) */
        .btn-sunset-orange {
            background: linear-gradient(135deg, #F97316 0%, #EF4444 100%);
            box-shadow: 0 8px 25px rgba(249, 115, 22, 0.3);
        }
        .btn-sunset-orange:hover {
            box-shadow: 0 12px 35px rgba(249, 115, 22, 0.4);
        }

        .info-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .color-demo {
            padding: 25px;
            border-radius: 20px;
            color: white;
            text-align: center;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .color-demo::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            transition: all 0.6s ease;
            opacity: 0;
        }

        .color-demo:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }

        .demo-ocean-blue {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        .demo-sky-blue {
            background: linear-gradient(135deg, #3B82F6 0%, #06B6D4 100%);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .demo-sunset-orange {
            background: linear-gradient(135deg, #F97316 0%, #EF4444 100%);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);
        }

        .btn {
            background: linear-gradient(135deg, #6366F1, #4F46E5);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            color: white;
            text-decoration: none;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .feature-item {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #6366F1;
        }

        @media (max-width: 768px) {
            .action-buttons-demo {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 1.5rem;
                padding: 0 0.5rem;
            }
            .action-btn-demo {
                padding: 1.2rem 1.8rem;
                font-size: 1rem;
                border-radius: 14px;
            }
            .action-btn-demo i {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .action-buttons-demo {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .action-btn-demo {
                padding: 1rem 1.5rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-header">
            <h1><i class="fas fa-mouse-pointer"></i> Modern Action Buttons</h1>
            <p>Enhanced navigation buttons with color-coded themes</p>
        </div>

        <!-- Action Buttons Demo -->
        <div class="action-buttons-demo">
            <a href="login.php" class="action-btn-demo btn-ocean-blue">
                <i class="fas fa-sign-in-alt"></i>
                User Login
            </a>
            <a href="register.php" class="action-btn-demo btn-ocean-blue">
                <i class="fas fa-user-plus"></i>
                Register Now
            </a>
            <a href="track-application.php" class="action-btn-demo btn-sky-blue">
                <i class="fas fa-search"></i>
                Track Status
            </a>
            <a href="admin-login.php" class="action-btn-demo btn-sunset-orange">
                <i class="fas fa-cog"></i>
                Admin Panel
            </a>
        </div>

        <!-- Color Scheme Information -->
        <div class="info-section">
            <h3><i class="fas fa-palette"></i> Button Color Schemes</h3>
            <p>Each button type uses a distinct color theme for better user experience:</p>
            
            <div class="color-grid">
                <div class="color-demo demo-ocean-blue">
                    <h4><i class="fas fa-sign-in-alt"></i> Ocean Blue</h4>
                    <p><strong>Primary Actions</strong></p>
                    <small>Login & Registration</small><br>
                    <small>#6366F1 → #4F46E5</small>
                </div>
                <div class="color-demo demo-sky-blue">
                    <h4><i class="fas fa-search"></i> Sky Blue</h4>
                    <p><strong>Information Actions</strong></p>
                    <small>Track Status & Help</small><br>
                    <small>#3B82F6 → #06B6D4</small>
                </div>
                <div class="color-demo demo-sunset-orange">
                    <h4><i class="fas fa-cog"></i> Sunset Orange</h4>
                    <p><strong>Admin Actions</strong></p>
                    <small>Admin Panel & Management</small><br>
                    <small>#F97316 → #EF4444</small>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h4><i class="fas fa-sparkles"></i> Enhanced Button Features:</h4>
            <div class="feature-grid">
                <div class="feature-item">
                    <h4><i class="fas fa-paint-brush"></i> Modern Gradients</h4>
                    <p>Beautiful gradient backgrounds with color-coded themes</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-mouse-pointer"></i> Hover Effects</h4>
                    <p>Subtle lift, scale, and shimmer animations on interaction</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-mobile-alt"></i> Responsive Design</h4>
                    <p>Adapts perfectly to all screen sizes and devices</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-eye"></i> Visual Hierarchy</h4>
                    <p>Color-coded buttons help users identify action types</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-hand-pointer"></i> Touch Friendly</h4>
                    <p>Optimized button sizes for mobile touch interaction</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-bolt"></i> Smooth Animations</h4>
                    <p>0.3s ease transitions for polished user experience</p>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h4><i class="fas fa-code"></i> Technical Implementation:</h4>
            <ul style="line-height: 1.8;">
                <li><strong>Enhanced Padding:</strong> 1.5rem vertical, 2.5rem horizontal for better click area</li>
                <li><strong>Rounded Corners:</strong> 16px border-radius for modern appearance</li>
                <li><strong>Drop Shadows:</strong> Color-matched shadows with 0.3 opacity</li>
                <li><strong>Shimmer Effect:</strong> Light sweep animation on hover</li>
                <li><strong>Icon Enhancement:</strong> 1.3rem size with drop-shadow filter</li>
                <li><strong>Responsive Grid:</strong> Auto-fit layout with minimum 220px width</li>
                <li><strong>Mobile Optimization:</strong> Adjusted sizes and spacing for smaller screens</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4>🚀 Experience the Enhanced Buttons:</h4>
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> View Updated Homepage
            </a>
            <a href="test_color_schemes.php" class="btn" style="background: linear-gradient(135deg, #3B82F6, #06B6D4);">
                <i class="fas fa-palette"></i> All Color Schemes
            </a>
        </div>

        <div style="background: #d4edda; padding: 25px; border-radius: 15px; border-left: 4px solid #28a745; margin: 30px 0;">
            <h4 style="margin: 0 0 15px 0; color: #155724;"><i class="fas fa-check"></i> Action Buttons Successfully Enhanced!</h4>
            <p style="margin: 0; color: #155724; line-height: 1.6;">
                The homepage navigation buttons have been transformed into modern, color-coded action buttons with enhanced hover effects, 
                improved accessibility, and responsive design. Each button type now has its own visual identity while maintaining 
                consistent styling and smooth animations throughout the user interface.
            </p>
        </div>
    </div>
</body>
</html>
