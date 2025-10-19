<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Homepage UI Test - Nrupatunga Smart Bus Pass Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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

        /* Title Section Demo */
        .title-section-demo {
            background: white;
            padding: 2.5rem 3rem;
            text-align: center;
            margin-bottom: 2rem;
            border-radius: 0;
        }

        .logo-demo {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-title-demo {
            color: #111827;
            margin-bottom: 1rem;
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }

        .subtitle-demo {
            color: #6B7280;
            margin-bottom: 2.5rem;
            font-size: 1.25rem;
            font-weight: 400;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Stats Cards Demo */
        .stats-container-demo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }

        .stat-card-demo {
            background: linear-gradient(135deg, #93C5FD 0%, #A5B4FC 100%);
            color: #FFFFFF;
            padding: 30px 20px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(147, 197, 253, 0.4);
        }

        .stat-card-demo:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(147, 197, 253, 0.5);
        }

        .stat-icon-demo {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.95;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
            transition: all 0.3s ease;
        }

        .stat-card-demo:hover .stat-icon-demo {
            transform: scale(1.1);
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.3));
        }

        .stat-number-demo {
            font-size: 2.8rem;
            font-weight: 900;
            margin-bottom: 8px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            letter-spacing: -1px;
        }

        .stat-label-demo {
            font-size: 1.1rem;
            opacity: 0.95;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 0.5px;
        }

        .comparison-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .color-palette {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .color-item {
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            color: white;
            font-weight: 600;
        }

        .color-primary {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
        }

        .color-stats {
            background: linear-gradient(135deg, #93C5FD 0%, #A5B4FC 100%);
        }

        .color-text-dark {
            background: #111827;
        }

        .color-text-gray {
            background: #6B7280;
        }

        .btn {
            background: linear-gradient(135deg, #6366F1, #4F46E5);
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
            .welcome-title-demo {
                font-size: 2rem;
            }
            
            .subtitle-demo {
                font-size: 1rem;
            }
            
            .stats-container-demo {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-header">
            <h1><i class="fas fa-palette"></i> Modern Homepage UI Test</h1>
            <p>Fresh and modern UI color scheme for Nrupatunga Smart Bus Pass Portal</p>
        </div>

        <!-- Title Section Demo -->
        <div class="title-section-demo">
            <div class="logo-demo"><i class="fas fa-bus"></i></div>
            <h1 class="welcome-title-demo">Nrupatunga Smart Bus Pass Portal</h1>
            <p class="subtitle-demo">A Smart Digital Platform for Effortless Bus Pass Applications, Renewals & Management</p>
        </div>

        <!-- Statistics Cards Demo -->
        <div class="stats-container-demo">
            <div class="stat-card-demo">
                <div class="stat-icon-demo">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="stat-number-demo">10,000+</div>
                <div class="stat-label-demo">Passes Issued</div>
            </div>

            <div class="stat-card-demo">
                <div class="stat-icon-demo">
                    <i class="fas fa-university"></i>
                </div>
                <div class="stat-number-demo">50+</div>
                <div class="stat-label-demo">Institutions Served</div>
            </div>

            <div class="stat-card-demo">
                <div class="stat-icon-demo">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number-demo">4.8/5</div>
                <div class="stat-label-demo">User Rating</div>
            </div>

            <div class="stat-card-demo">
                <div class="stat-icon-demo">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number-demo">24/7</div>
                <div class="stat-label-demo">Support Available</div>
            </div>
        </div>

        <!-- Color Palette -->
        <div class="comparison-section">
            <h3><i class="fas fa-palette"></i> Modern Color Palette</h3>
            <div class="color-palette">
                <div class="color-item color-primary">
                    <h4>Primary Background</h4>
                    <p>#6366F1 → #4F46E5</p>
                    <small>Blue-violet to Indigo</small>
                </div>
                <div class="color-item color-stats">
                    <h4>Stats Cards</h4>
                    <p>#93C5FD → #A5B4FC</p>
                    <small>Light blue-violet gradient</small>
                </div>
                <div class="color-item color-text-dark">
                    <h4>Main Heading</h4>
                    <p>#111827</p>
                    <small>Almost black</small>
                </div>
                <div class="color-item color-text-gray">
                    <h4>Subheading</h4>
                    <p>#6B7280</p>
                    <small>Cool gray</small>
                </div>
            </div>
        </div>

        <!-- Typography Features -->
        <div class="comparison-section">
            <h3><i class="fas fa-font"></i> Typography Features</h3>
            <div class="feature-grid">
                <div class="feature-item">
                    <h4><i class="fas fa-text-height"></i> Font Family</h4>
                    <p>Inter & Poppins for modern, clean readability</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-heading"></i> Main Title</h4>
                    <p>3xl size, #111827 color, 800 weight, -0.025em spacing</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-paragraph"></i> Subtitle</h4>
                    <p>xl size, #6B7280 color, 400 weight, 1.6 line height</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-mobile-alt"></i> Responsive</h4>
                    <p>Mobile-optimized with proper spacing and contrast</p>
                </div>
            </div>
        </div>

        <!-- Design Features -->
        <div class="comparison-section">
            <h3><i class="fas fa-sparkles"></i> Design Enhancements</h3>
            <div class="info-section">
                <h4>✨ Modern UI Improvements:</h4>
                <ul>
                    <li><strong>Clean White Background:</strong> Title section with clean white background and center alignment</li>
                    <li><strong>Modern Gradients:</strong> Blue-violet to indigo primary background</li>
                    <li><strong>Consistent Stats Cards:</strong> Light blue-violet gradient for all statistics cards</li>
                    <li><strong>Enhanced Typography:</strong> Inter/Poppins fonts with proper weights and spacing</li>
                    <li><strong>Accessible Colors:</strong> High contrast ratios for excellent readability</li>
                    <li><strong>Responsive Design:</strong> Mobile-first approach with proper breakpoints</li>
                    <li><strong>Professional Appearance:</strong> Modern, clean, and business-appropriate design</li>
                </ul>
            </div>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4>🚀 Experience the New Design:</h4>
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> View Updated Homepage
            </a>
            <a href="test_color_schemes.php" class="btn" style="background: linear-gradient(135deg, #93C5FD, #A5B4FC); color: #333;">
                <i class="fas fa-palette"></i> All Color Schemes
            </a>
        </div>

        <div style="background: #d1ecf1; padding: 25px; border-radius: 15px; border-left: 4px solid #17a2b8; margin: 30px 0;">
            <h4 style="color: #0c5460; margin: 0 0 15px 0;"><i class="fas fa-check-circle"></i> Implementation Summary:</h4>
            <p style="color: #0c5460; margin: 0; line-height: 1.6;">
                The homepage has been completely redesigned with a fresh, modern UI featuring the new "Nrupatunga Smart Bus Pass Portal" branding, 
                accessible color scheme with blue-violet gradients, enhanced typography using Inter/Poppins fonts, and improved mobile responsiveness. 
                The design maintains excellent contrast and readability while providing a professional, contemporary appearance.
            </p>
        </div>
    </div>
</body>
</html>
