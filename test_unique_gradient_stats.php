<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unique Gradient Statistics Cards - Nrupatunga Smart Bus Pass Portal</title>
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

        /* Statistics Cards Styles */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .stat-card {
            color: #FFFFFF;
            padding: 35px 25px;
            border-radius: 24px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            cursor: pointer;
        }

        /* Unique modern gradient backgrounds for each stat card */
        .stat-card:nth-child(1) {
            background: linear-gradient(135deg, #3B82F6 0%, #06B6D4 100%);
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.3);
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.3);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #F97316 0%, #FDE047 100%);
            box-shadow: 0 15px 35px rgba(249, 115, 22, 0.3);
        }

        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #10B981 0%, #14B8A6 100%);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.3);
        }

        .stat-card::before {
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

        .stat-card:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }

        /* Subtle enhanced hover effects for each card */
        .stat-card:nth-child(1):hover {
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.4);
            transform: translateY(-6px) scale(1.01);
        }

        .stat-card:nth-child(2):hover {
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.4);
            transform: translateY(-6px) scale(1.01);
        }

        .stat-card:nth-child(3):hover {
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.4);
            transform: translateY(-6px) scale(1.01);
        }

        .stat-card:nth-child(4):hover {
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.4);
            transform: translateY(-6px) scale(1.01);
        }

        .stat-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            opacity: 0.95;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.3));
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 10px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: 1.2rem;
            opacity: 0.95;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 0.5px;
        }

        .gradient-info {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .gradient-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .gradient-demo {
            padding: 25px;
            border-radius: 20px;
            color: white;
            text-align: center;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .gradient-demo::before {
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

        .gradient-demo:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }

        .demo-blue {
            background: linear-gradient(135deg, #3B82F6 0%, #06B6D4 100%);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .demo-purple {
            background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
        }

        .demo-orange {
            background: linear-gradient(135deg, #F97316 0%, #FDE047 100%);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);
        }

        .demo-green {
            background: linear-gradient(135deg, #10B981 0%, #14B8A6 100%);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
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

        .info-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
            border-left: 4px solid #6366F1;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 20px;
            }
            
            .stat-card {
                padding: 25px 20px;
            }
            
            .stat-icon {
                font-size: 2.5rem;
            }
            
            .stat-number {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-header">
            <h1><i class="fas fa-chart-bar"></i> Unique Gradient Statistics Cards</h1>
            <p>Modern, distinct gradients for each dashboard statistic</p>
        </div>

        <!-- Statistics Cards Demo -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="stat-number">10,000+</div>
                <div class="stat-label">Passes Issued</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-university"></i>
                </div>
                <div class="stat-number">50+</div>
                <div class="stat-label">Institutions Served</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number">4.8/5</div>
                <div class="stat-label">User Rating</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number">24/7</div>
                <div class="stat-label">Support Available</div>
            </div>
        </div>

        <!-- Gradient Information -->
        <div class="gradient-info">
            <h3><i class="fas fa-palette"></i> Unique Gradient Color Schemes</h3>
            <p>Each statistics card features a distinct modern gradient for visual differentiation:</p>
            
            <div class="gradient-grid">
                <div class="gradient-demo demo-blue">
                    <h4><i class="fas fa-id-card"></i> Blue to Cyan</h4>
                    <p><strong>Passes Issued</strong></p>
                    <small>#3B82F6 → #06B6D4</small>
                </div>
                <div class="gradient-demo demo-purple">
                    <h4><i class="fas fa-university"></i> Purple to Pink</h4>
                    <p><strong>Institutions Served</strong></p>
                    <small>#8B5CF6 → #EC4899</small>
                </div>
                <div class="gradient-demo demo-orange">
                    <h4><i class="fas fa-star"></i> Orange to Yellow</h4>
                    <p><strong>User Rating</strong></p>
                    <small>#F97316 → #FDE047</small>
                </div>
                <div class="gradient-demo demo-green">
                    <h4><i class="fas fa-clock"></i> Green to Teal</h4>
                    <p><strong>Support Available</strong></p>
                    <small>#10B981 → #14B8A6</small>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h4><i class="fas fa-sparkles"></i> Design Features:</h4>
            <ul>
                <li><strong>Unique Gradients:</strong> Each card has its own distinct color identity</li>
                <li><strong>Smooth Corners:</strong> 24px border-radius for elegant rounded corners</li>
                <li><strong>Soft Shadows:</strong> Color-matched shadows with appropriate opacity</li>
                <li><strong>Subtle Hover Effects:</strong> Gentle lift (6px) and minimal scale (1.01)</li>
                <li><strong>White Icons & Text:</strong> Excellent visibility and contrast</li>
                <li><strong>Shimmer Animation:</strong> Light sweep effect on hover</li>
                <li><strong>Responsive Design:</strong> Adapts beautifully to all screen sizes</li>
            </ul>
        </div>

        <div class="info-section">
            <h4><i class="fas fa-code"></i> Technical Implementation:</h4>
            <ul>
                <li><strong>Blue to Cyan:</strong> linear-gradient(135deg, #3B82F6 0%, #06B6D4 100%)</li>
                <li><strong>Purple to Pink:</strong> linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%)</li>
                <li><strong>Orange to Yellow:</strong> linear-gradient(135deg, #F97316 0%, #FDE047 100%)</li>
                <li><strong>Green to Teal:</strong> linear-gradient(135deg, #10B981 0%, #14B8A6 100%)</li>
                <li><strong>Hover Transform:</strong> translateY(-6px) scale(1.01)</li>
                <li><strong>Transition:</strong> all 0.3s ease for smooth animations</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4>🚀 Experience the New Gradient Cards:</h4>
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> View Updated Homepage
            </a>
            <a href="test_color_schemes.php" class="btn" style="background: linear-gradient(135deg, #8B5CF6, #EC4899);">
                <i class="fas fa-palette"></i> All Color Schemes
            </a>
        </div>

        <div class="gradient-info">
            <h3><i class="fas fa-check-circle"></i> Implementation Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #3B82F6, #06B6D4); border-radius: 15px; color: white;">
                    <i class="fas fa-id-card" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h5 style="margin: 0; color: white;">Passes Issued</h5>
                    <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Blue to Cyan gradient</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #8B5CF6, #EC4899); border-radius: 15px; color: white;">
                    <i class="fas fa-university" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h5 style="margin: 0; color: white;">Institutions Served</h5>
                    <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Purple to Pink gradient</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #F97316, #FDE047); border-radius: 15px; color: white;">
                    <i class="fas fa-star" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h5 style="margin: 0; color: white;">User Rating</h5>
                    <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Orange to Yellow gradient</p>
                </div>
                <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #10B981, #14B8A6); border-radius: 15px; color: white;">
                    <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h5 style="margin: 0; color: white;">Support Available</h5>
                    <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Green to Teal gradient</p>
                </div>
            </div>
            
            <div style="background: #d4edda; padding: 20px; border-radius: 10px; border-left: 4px solid #28a745; margin-top: 20px;">
                <h4 style="margin: 0 0 10px 0; color: #155724;"><i class="fas fa-check"></i> Successfully Implemented!</h4>
                <p style="margin: 0; color: #155724;">Each statistics card now features a unique modern gradient with subtle hover effects, smooth corners, and elegant shadows for enhanced visual appeal and user experience.</p>
            </div>
        </div>
    </div>
</body>
</html>
