<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Gradient Statistics Cards - Nrupatunga Digital Bus Pass System</title>
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

        /* Statistics Cards Styles */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            color: white;
            padding: 35px 25px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            cursor: pointer;
        }

        /* Individual gradient backgrounds for each stat card */
        .stat-card:nth-child(1) {
            background: linear-gradient(135deg, #007bff 0%, #00d4ff 100%);
            box-shadow: 0 15px 35px rgba(0, 123, 255, 0.3);
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            box-shadow: 0 15px 35px rgba(111, 66, 193, 0.3);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
            box-shadow: 0 15px 35px rgba(253, 126, 20, 0.3);
        }

        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 15px 35px rgba(40, 167, 69, 0.3);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.15), transparent);
            transform: rotate(45deg);
            transition: all 0.6s ease;
            opacity: 0;
        }

        .stat-card:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
        }

        /* Enhanced hover effects for each card */
        .stat-card:nth-child(1):hover {
            box-shadow: 0 20px 40px rgba(0, 123, 255, 0.4);
        }

        .stat-card:nth-child(2):hover {
            box-shadow: 0 20px 40px rgba(111, 66, 193, 0.4);
        }

        .stat-card:nth-child(3):hover {
            box-shadow: 0 20px 40px rgba(253, 126, 20, 0.4);
        }

        .stat-card:nth-child(4):hover {
            box-shadow: 0 20px 40px rgba(40, 167, 69, 0.4);
        }

        .stat-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            opacity: 0.95;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.15);
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

        .color-info {
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
            padding: 20px;
            border-radius: 15px;
            color: white;
            text-align: center;
            font-weight: 600;
        }

        .demo-blue {
            background: linear-gradient(135deg, #007bff 0%, #00d4ff 100%);
        }

        .demo-purple {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
        }

        .demo-orange {
            background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
        }

        .demo-green {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
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
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }

        .info-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            <h1><i class="fas fa-chart-bar"></i> Gradient Statistics Cards</h1>
            <p>Modern gradient backgrounds for dashboard statistics</p>
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

        <!-- Color Information -->
        <div class="color-info">
            <h3><i class="fas fa-palette"></i> Gradient Color Schemes</h3>
            <p>Each statistics card uses a unique modern gradient background:</p>
            
            <div class="color-grid">
                <div class="color-demo demo-blue">
                    <h4><i class="fas fa-id-card"></i> Blue to Cyan</h4>
                    <p>Passes Issued</p>
                    <small>#007bff → #00d4ff</small>
                </div>
                <div class="color-demo demo-purple">
                    <h4><i class="fas fa-university"></i> Purple to Pink</h4>
                    <p>Institutions Served</p>
                    <small>#6f42c1 → #e83e8c</small>
                </div>
                <div class="color-demo demo-orange">
                    <h4><i class="fas fa-star"></i> Orange to Yellow</h4>
                    <p>User Rating</p>
                    <small>#fd7e14 → #ffc107</small>
                </div>
                <div class="color-demo demo-green">
                    <h4><i class="fas fa-clock"></i> Green to Teal</h4>
                    <p>Support Available</p>
                    <small>#28a745 → #20c997</small>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h4><i class="fas fa-sparkles"></i> Design Features:</h4>
            <ul>
                <li><strong>Modern Gradients:</strong> Smooth color transitions from primary to secondary colors</li>
                <li><strong>Soft Corners:</strong> 20px border-radius for elegant rounded corners</li>
                <li><strong>Elegant Shadows:</strong> Depth-creating shadows with matching gradient colors</li>
                <li><strong>Subtle Hover Effects:</strong> Gentle lift and scale animations on hover</li>
                <li><strong>White Icons & Text:</strong> High contrast for excellent visibility</li>
                <li><strong>Backdrop Blur:</strong> Modern glass-morphism effect</li>
                <li><strong>Responsive Design:</strong> Adapts beautifully to all screen sizes</li>
            </ul>
        </div>

        <div class="info-section">
            <h4><i class="fas fa-code"></i> Technical Implementation:</h4>
            <ul>
                <li><strong>CSS Gradients:</strong> Linear gradients at 135-degree angle</li>
                <li><strong>Box Shadows:</strong> Color-matched shadows for depth</li>
                <li><strong>Transitions:</strong> Smooth 0.4s ease animations</li>
                <li><strong>Transform Effects:</strong> translateY and scale on hover</li>
                <li><strong>Text Shadows:</strong> Enhanced readability with subtle shadows</li>
                <li><strong>Filter Effects:</strong> Drop-shadow for icons</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4>🚀 Test Live Implementation:</h4>
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> View Homepage with New Cards
            </a>
            <a href="test_color_schemes.php" class="btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                <i class="fas fa-palette"></i> View All Color Schemes
            </a>
        </div>

        <div class="color-info">
            <h3><i class="fas fa-check-circle"></i> Implementation Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                <div style="text-align: center; padding: 15px; background: #e3f2fd; border-radius: 10px;">
                    <i class="fas fa-id-card" style="font-size: 2rem; color: #007bff; margin-bottom: 10px;"></i>
                    <h5 style="margin: 0; color: #333;">Passes Issued</h5>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Blue to Cyan gradient</p>
                </div>
                <div style="text-align: center; padding: 15px; background: #f3e5f5; border-radius: 10px;">
                    <i class="fas fa-university" style="font-size: 2rem; color: #6f42c1; margin-bottom: 10px;"></i>
                    <h5 style="margin: 0; color: #333;">Institutions Served</h5>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Purple to Pink gradient</p>
                </div>
                <div style="text-align: center; padding: 15px; background: #fff3e0; border-radius: 10px;">
                    <i class="fas fa-star" style="font-size: 2rem; color: #fd7e14; margin-bottom: 10px;"></i>
                    <h5 style="margin: 0; color: #333;">User Rating</h5>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Orange to Yellow gradient</p>
                </div>
                <div style="text-align: center; padding: 15px; background: #e8f5e8; border-radius: 10px;">
                    <i class="fas fa-clock" style="font-size: 2rem; color: #28a745; margin-bottom: 10px;"></i>
                    <h5 style="margin: 0; color: #333;">Support Available</h5>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Green to Teal gradient</p>
                </div>
            </div>
            
            <div style="background: #d4edda; padding: 20px; border-radius: 10px; border-left: 4px solid #28a745; margin-top: 20px;">
                <h4 style="margin: 0 0 10px 0; color: #155724;"><i class="fas fa-check"></i> Successfully Implemented!</h4>
                <p style="margin: 0; color: #155724;">The homepage statistics cards now feature beautiful modern gradients with unique colors for each metric, enhanced hover effects, and improved visual hierarchy.</p>
            </div>
        </div>
    </div>
</body>
</html>
