<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional User Login - Nrupatunga Smart Bus Pass Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(180deg, #2C3E50 0%, #34495E 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .main-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(52, 73, 94, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 0 30px rgba(255, 255, 255, 0.1),
                0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .main-header h1 {
            color: #FFFFFF;
            margin: 0 0 10px 0;
            font-size: 2.5rem;
            font-weight: 800;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }

        .main-header p {
            color: #BDC3C7;
            margin: 0;
            font-size: 1.2rem;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin: 40px 0;
        }

        .demo-section {
            background: rgba(52, 73, 94, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 0 30px rgba(255, 255, 255, 0.1),
                0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .demo-section h3 {
            color: #FFFFFF;
            margin: 0 0 20px 0;
            font-size: 1.5rem;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }

        .color-demo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .color-item {
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            border: 2px solid;
            position: relative;
            overflow: hidden;
        }

        .color-item::before {
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

        .color-item:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }

        .dark-slate {
            background: rgba(44, 62, 80, 0.8);
            color: #2C3E50;
            border-color: #2C3E50;
            box-shadow: 0 0 15px rgba(44, 62, 80, 0.3);
        }

        .blue-gray {
            background: rgba(52, 73, 94, 0.8);
            color: #34495E;
            border-color: #34495E;
            box-shadow: 0 0 15px rgba(52, 73, 94, 0.3);
        }

        .steel-blue {
            background: rgba(93, 109, 126, 0.8);
            color: #5D6D7E;
            border-color: #5D6D7E;
            box-shadow: 0 0 15px rgba(93, 109, 126, 0.3);
        }

        .bright-blue {
            background: rgba(52, 152, 219, 0.2);
            color: #3498DB;
            border-color: #3498DB;
            box-shadow: 0 0 15px rgba(52, 152, 219, 0.3);
        }

        .pure-white {
            background: rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
            border-color: #FFFFFF;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
        }

        .amethyst {
            background: rgba(155, 89, 182, 0.2);
            color: #9B59B6;
            border-color: #9B59B6;
            box-shadow: 0 0 15px rgba(155, 89, 182, 0.3);
        }

        .feature-list {
            color: #FFFFFF;
            line-height: 1.8;
        }

        .feature-list li {
            margin: 10px 0;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);
        }

        .btn {
            background: #3498DB;
            color: #FFFFFF;
            padding: 15px 30px;
            border: 2px solid #3498DB;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            box-shadow: 
                0 0 20px rgba(52, 152, 219, 0.3),
                0 8px 16px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #2980B9;
            color: #FFFFFF;
            text-decoration: none;
            box-shadow: 
                0 0 40px rgba(52, 152, 219, 0.6),
                0 12px 24px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }

        .btn-purple {
            background: #9B59B6;
            border-color: #9B59B6;
            box-shadow: 
                0 0 20px rgba(155, 89, 182, 0.3),
                0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .btn-purple:hover {
            background: #8E44AD;
            box-shadow: 
                0 0 40px rgba(155, 89, 182, 0.6),
                0 12px 24px rgba(0, 0, 0, 0.3);
        }

        .info-section {
            background: rgba(52, 73, 94, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 0 30px rgba(255, 255, 255, 0.1),
                0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .info-section h3 {
            color: #3498DB;
            text-shadow: 0 0 10px rgba(52, 152, 219, 0.5);
            margin-top: 0;
        }

        @media (max-width: 768px) {
            .demo-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .main-header h1 {
                font-size: 2rem;
            }
            
            .color-demo {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-header">
            <h1>🏢 Professional User Login</h1>
            <p>Sophisticated, business-appropriate design for user authentication</p>
        </div>

        <div class="demo-grid">
            <div class="demo-section">
                <h3>🎨 Professional Color Palette</h3>
                <div class="color-demo">
                    <div class="color-item dark-slate">
                        <h4>Dark Slate</h4>
                        <p>#2C3E50</p>
                        <small>Background Top</small>
                    </div>
                    <div class="color-item blue-gray">
                        <h4>Blue Gray</h4>
                        <p>#34495E</p>
                        <small>Background Bottom</small>
                    </div>
                    <div class="color-item steel-blue">
                        <h4>Steel Blue</h4>
                        <p>#5D6D7E</p>
                        <small>Input Background</small>
                    </div>
                    <div class="color-item bright-blue">
                        <h4>Bright Blue</h4>
                        <p>#3498DB</p>
                        <small>Buttons & Borders</small>
                    </div>
                    <div class="color-item pure-white">
                        <h4>Pure White</h4>
                        <p>#FFFFFF</p>
                        <small>Title & Text</small>
                    </div>
                    <div class="color-item amethyst">
                        <h4>Amethyst</h4>
                        <p>#9B59B6</p>
                        <small>Navigation Links</small>
                    </div>
                </div>
            </div>

            <div class="demo-section">
                <h3>✨ Design Features</h3>
                <ul class="feature-list">
                    <li><strong>Professional Gradient:</strong> Dark slate to blue gray</li>
                    <li><strong>Glass Morphism:</strong> Semi-transparent form container</li>
                    <li><strong>Subtle Glow Effects:</strong> Professional white borders</li>
                    <li><strong>Bright Blue Accents:</strong> Modern button styling</li>
                    <li><strong>Clean Typography:</strong> Inter & Roboto fonts</li>
                    <li><strong>Amethyst Links:</strong> Elegant navigation contrast</li>
                    <li><strong>Business Appropriate:</strong> Professional color scheme</li>
                    <li><strong>Responsive Design:</strong> Mobile-friendly layout</li>
                </ul>
            </div>
        </div>

        <div class="info-section">
            <h3>🏢 Professional User Experience</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="color: #FFFFFF; line-height: 1.6;">
                    <h4 style="color: #3498DB; text-shadow: 0 0 10px rgba(52, 152, 219, 0.5);">Sophisticated Design:</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Professional dark slate to blue gray gradient</li>
                        <li>Semi-transparent form with glass morphism</li>
                        <li>Subtle white glow border effects</li>
                        <li>Modern Inter & Roboto typography</li>
                    </ul>
                </div>
                <div style="color: #FFFFFF; line-height: 1.6;">
                    <h4 style="color: #9B59B6; text-shadow: 0 0 10px rgba(155, 89, 182, 0.5);">Enhanced Elements:</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Bright blue input borders with focus glow</li>
                        <li>Professional login button with pulse</li>
                        <li>Amethyst navigation links</li>
                        <li>Business-appropriate color scheme</li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4 style="color: #FFFFFF; text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);">🎯 Experience the Professional User Login:</h4>
            <a href="login.php" class="btn">
                🏢 View User Login
            </a>
            <a href="admin-login.php" class="btn-purple btn">
                🏛️ Admin Login
            </a>
            <a href="index.php" class="btn" style="background: #5D6D7E; border-color: #5D6D7E;">
                🏠 Back to Homepage
            </a>
        </div>

        <div style="background: rgba(52, 152, 219, 0.1); padding: 25px; border-radius: 15px; border: 2px solid #3498DB; margin: 30px 0; box-shadow: 0 0 20px rgba(52, 152, 219, 0.3);">
            <h4 style="margin: 0 0 15px 0; color: #3498DB; text-shadow: 0 0 10px rgba(52, 152, 219, 0.5);">🏢 Professional User Login Successfully Implemented!</h4>
            <p style="margin: 0; color: #FFFFFF; line-height: 1.6; text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);">
                The user login page has been redesigned with a sophisticated, professional aesthetic featuring a dark slate to blue gray gradient background, 
                semi-transparent form with glass morphism effects, bright blue input fields and buttons, amethyst navigation links, 
                and clean Inter/Roboto typography. The design maintains business appropriateness while providing a modern, 
                elegant user experience that distinguishes it from the admin interface.
            </p>
        </div>
    </div>
</body>
</html>
