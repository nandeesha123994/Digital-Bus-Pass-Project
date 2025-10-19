<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics Cards Comparison - Before vs After</title>
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
            max-width: 1400px;
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
        .comparison-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .comparison-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
            color: #333;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        /* OLD STYLE - Before */
        .stat-card-old {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        .stat-card-old:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        .stat-icon-old {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        .stat-number-old {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .stat-label-old {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* NEW STYLE - After */
        .stat-card-new {
            color: white;
            padding: 30px 20px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        .stat-card-new:nth-child(1) {
            background: linear-gradient(135deg, #007bff 0%, #00d4ff 100%);
            box-shadow: 0 15px 35px rgba(0, 123, 255, 0.3);
        }
        .stat-card-new:nth-child(2) {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            box-shadow: 0 15px 35px rgba(111, 66, 193, 0.3);
        }
        .stat-card-new:nth-child(3) {
            background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
            box-shadow: 0 15px 35px rgba(253, 126, 20, 0.3);
        }
        .stat-card-new:nth-child(4) {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 15px 35px rgba(40, 167, 69, 0.3);
        }
        .stat-card-new::before {
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
        .stat-card-new:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }
        .stat-card-new:hover {
            transform: translateY(-8px) scale(1.02);
        }
        .stat-card-new:nth-child(1):hover {
            box-shadow: 0 20px 40px rgba(0, 123, 255, 0.4);
        }
        .stat-card-new:nth-child(2):hover {
            box-shadow: 0 20px 40px rgba(111, 66, 193, 0.4);
        }
        .stat-card-new:nth-child(3):hover {
            box-shadow: 0 20px 40px rgba(253, 126, 20, 0.4);
        }
        .stat-card-new:nth-child(4):hover {
            box-shadow: 0 20px 40px rgba(40, 167, 69, 0.4);
        }
        .stat-icon-new {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.95;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
            transition: all 0.3s ease;
        }
        .stat-card-new:hover .stat-icon-new {
            transform: scale(1.1);
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.3));
        }
        .stat-number-new {
            font-size: 2.8rem;
            font-weight: 900;
            margin-bottom: 8px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            letter-spacing: -1px;
        }
        .stat-label-new {
            font-size: 1.1rem;
            opacity: 0.95;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 0.5px;
        }

        .vs-divider {
            text-align: center;
            margin: 40px 0;
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }

        .improvement-list {
            background: #e8f5e8;
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid #28a745;
            margin: 30px 0;
        }

        .improvement-list h4 {
            color: #155724;
            margin: 0 0 15px 0;
        }

        .improvement-list ul {
            margin: 0;
            padding-left: 20px;
        }

        .improvement-list li {
            color: #155724;
            margin: 8px 0;
            font-weight: 500;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="main-header">
            <h1><i class="fas fa-chart-line"></i> Statistics Cards Comparison</h1>
            <p>Before vs After: Modern Gradient Implementation</p>
        </div>

        <!-- BEFORE Section -->
        <div class="comparison-section">
            <h2 class="comparison-title">❌ BEFORE: Single Blue Gradient</h2>
            <div class="stats-container">
                <div class="stat-card-old">
                    <div class="stat-icon-old">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="stat-number-old">10,000+</div>
                    <div class="stat-label-old">Passes Issued</div>
                </div>

                <div class="stat-card-old">
                    <div class="stat-icon-old">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="stat-number-old">50+</div>
                    <div class="stat-label-old">Institutions Served</div>
                </div>

                <div class="stat-card-old">
                    <div class="stat-icon-old">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number-old">4.8/5</div>
                    <div class="stat-label-old">User Rating</div>
                </div>

                <div class="stat-card-old">
                    <div class="stat-icon-old">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number-old">24/7</div>
                    <div class="stat-label-old">Support Available</div>
                </div>
            </div>
            
            <div style="background: #f8d7da; padding: 20px; border-radius: 10px; border-left: 4px solid #dc3545;">
                <h4 style="color: #721c24; margin: 0 0 10px 0;"><i class="fas fa-times"></i> Issues with Old Design:</h4>
                <ul style="margin: 0; color: #721c24;">
                    <li>All cards look identical - no visual distinction</li>
                    <li>Monotonous single blue gradient</li>
                    <li>Basic hover effects</li>
                    <li>Smaller icons and text</li>
                    <li>Less engaging user experience</li>
                </ul>
            </div>
        </div>

        <div class="vs-divider">
            <i class="fas fa-arrow-down"></i> UPGRADED TO <i class="fas fa-arrow-down"></i>
        </div>

        <!-- AFTER Section -->
        <div class="comparison-section">
            <h2 class="comparison-title">✅ AFTER: Unique Modern Gradients</h2>
            <div class="stats-container">
                <div class="stat-card-new">
                    <div class="stat-icon-new">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="stat-number-new">10,000+</div>
                    <div class="stat-label-new">Passes Issued</div>
                </div>

                <div class="stat-card-new">
                    <div class="stat-icon-new">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="stat-number-new">50+</div>
                    <div class="stat-label-new">Institutions Served</div>
                </div>

                <div class="stat-card-new">
                    <div class="stat-icon-new">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number-new">4.8/5</div>
                    <div class="stat-label-new">User Rating</div>
                </div>

                <div class="stat-card-new">
                    <div class="stat-icon-new">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number-new">24/7</div>
                    <div class="stat-label-new">Support Available</div>
                </div>
            </div>

            <div class="improvement-list">
                <h4><i class="fas fa-check"></i> Improvements in New Design:</h4>
                <ul>
                    <li><strong>Unique Gradients:</strong> Blue→Cyan, Purple→Pink, Orange→Yellow, Green→Teal</li>
                    <li><strong>Enhanced Hover Effects:</strong> Lift, scale, and shimmer animations</li>
                    <li><strong>Larger Icons:</strong> 3rem size with drop-shadow effects</li>
                    <li><strong>Better Typography:</strong> Increased font weights and letter spacing</li>
                    <li><strong>Soft Corners:</strong> 20px border-radius for modern look</li>
                    <li><strong>Color-Matched Shadows:</strong> Each card has gradient-specific shadows</li>
                    <li><strong>Backdrop Blur:</strong> Modern glass-morphism effect</li>
                    <li><strong>Visual Distinction:</strong> Each metric has its own color identity</li>
                </ul>
            </div>
        </div>

        <!-- Color Breakdown -->
        <div class="comparison-section">
            <h2 class="comparison-title"><i class="fas fa-palette"></i> Color Gradient Breakdown</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="background: linear-gradient(135deg, #007bff 0%, #00d4ff 100%); color: white; padding: 20px; border-radius: 15px; text-align: center;">
                    <i class="fas fa-id-card" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h4 style="margin: 0;">Passes Issued</h4>
                    <p style="margin: 5px 0 0 0; opacity: 0.9;">Blue to Cyan</p>
                    <small style="opacity: 0.8;">#007bff → #00d4ff</small>
                </div>
                <div style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); color: white; padding: 20px; border-radius: 15px; text-align: center;">
                    <i class="fas fa-university" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h4 style="margin: 0;">Institutions Served</h4>
                    <p style="margin: 5px 0 0 0; opacity: 0.9;">Purple to Pink</p>
                    <small style="opacity: 0.8;">#6f42c1 → #e83e8c</small>
                </div>
                <div style="background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); color: white; padding: 20px; border-radius: 15px; text-align: center;">
                    <i class="fas fa-star" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h4 style="margin: 0;">User Rating</h4>
                    <p style="margin: 5px 0 0 0; opacity: 0.9;">Orange to Yellow</p>
                    <small style="opacity: 0.8;">#fd7e14 → #ffc107</small>
                </div>
                <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 15px; text-align: center;">
                    <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h4 style="margin: 0;">Support Available</h4>
                    <p style="margin: 5px 0 0 0; opacity: 0.9;">Green to Teal</p>
                    <small style="opacity: 0.8;">#28a745 → #20c997</small>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4>🚀 Experience the New Design:</h4>
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> View Updated Homepage
            </a>
            <a href="test_gradient_stats_cards.php" class="btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                <i class="fas fa-chart-bar"></i> Interactive Demo
            </a>
        </div>

        <div style="background: #d1ecf1; padding: 25px; border-radius: 15px; border-left: 4px solid #17a2b8; margin: 30px 0;">
            <h4 style="color: #0c5460; margin: 0 0 15px 0;"><i class="fas fa-info-circle"></i> Implementation Summary:</h4>
            <p style="color: #0c5460; margin: 0; line-height: 1.6;">
                The statistics cards have been completely redesigned with modern gradient backgrounds, enhanced animations, and improved visual hierarchy. Each card now has a unique color identity that helps users quickly distinguish between different metrics while maintaining excellent readability with white text and icons.
            </p>
        </div>
    </div>
</body>
</html>
