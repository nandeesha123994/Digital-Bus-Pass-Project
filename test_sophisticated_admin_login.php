<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sophisticated Admin Login - Nrupatunga Smart Bus Pass Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(180deg, #1C2526 0%, #333333 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle background pattern for premium feel */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 183, 235, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 215, 0, 0.03) 0%, transparent 50%),
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
            background: rgba(74, 74, 74, 0.95);
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
            color: #C0C0C0;
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
            background: rgba(74, 74, 74, 0.95);
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

        .midnight-blue {
            background: rgba(28, 37, 38, 0.8);
            color: #1C2526;
            border-color: #1C2526;
            box-shadow: 0 0 15px rgba(28, 37, 38, 0.3);
        }

        .charcoal-gray {
            background: rgba(51, 51, 51, 0.8);
            color: #333333;
            border-color: #333333;
            box-shadow: 0 0 15px rgba(51, 51, 51, 0.3);
        }

        .slate-gray {
            background: rgba(74, 74, 74, 0.8);
            color: #4A4A4A;
            border-color: #4A4A4A;
            box-shadow: 0 0 15px rgba(74, 74, 74, 0.3);
        }

        .electric-blue {
            background: rgba(0, 183, 235, 0.2);
            color: #00B7EB;
            border-color: #00B7EB;
            box-shadow: 0 0 15px rgba(0, 183, 235, 0.3);
        }

        .pure-white {
            background: rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
            border-color: #FFFFFF;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
        }

        .soft-gold {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
            border-color: #FFD700;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
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
            background: #00B7EB;
            color: #FFFFFF;
            padding: 15px 30px;
            border: 2px solid #00B7EB;
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
                0 0 20px rgba(0, 183, 235, 0.3),
                0 8px 16px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #0099CC;
            color: #FFFFFF;
            text-decoration: none;
            box-shadow: 
                0 0 40px rgba(0, 183, 235, 0.6),
                0 12px 24px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }

        .btn-gold {
            background: #FFD700;
            border-color: #FFD700;
            color: #000000;
            box-shadow: 
                0 0 20px rgba(255, 215, 0, 0.3),
                0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .btn-gold:hover {
            background: #FFC700;
            color: #000000;
            box-shadow: 
                0 0 40px rgba(255, 215, 0, 0.6),
                0 12px 24px rgba(0, 0, 0, 0.3);
        }

        .info-section {
            background: rgba(74, 74, 74, 0.95);
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
            color: #00B7EB;
            text-shadow: 0 0 10px rgba(0, 183, 235, 0.5);
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
            <h1>🏛️ Sophisticated Admin Login</h1>
            <p>Authoritative, modern aesthetic for administrative access</p>
        </div>

        <div class="demo-grid">
            <div class="demo-section">
                <h3>🎨 Professional Color Palette</h3>
                <div class="color-demo">
                    <div class="color-item midnight-blue">
                        <h4>Midnight Blue</h4>
                        <p>#1C2526</p>
                        <small>Background Top</small>
                    </div>
                    <div class="color-item charcoal-gray">
                        <h4>Charcoal Gray</h4>
                        <p>#333333</p>
                        <small>Background Bottom</small>
                    </div>
                    <div class="color-item slate-gray">
                        <h4>Slate Gray</h4>
                        <p>#4A4A4A</p>
                        <small>Form Background</small>
                    </div>
                    <div class="color-item electric-blue">
                        <h4>Electric Blue</h4>
                        <p>#00B7EB</p>
                        <small>Buttons & Borders</small>
                    </div>
                    <div class="color-item pure-white">
                        <h4>Pure White</h4>
                        <p>#FFFFFF</p>
                        <small>Title & Text</small>
                    </div>
                    <div class="color-item soft-gold">
                        <h4>Soft Gold</h4>
                        <p>#FFD700</p>
                        <small>Navigation Links</small>
                    </div>
                </div>
            </div>

            <div class="demo-section">
                <h3>✨ Design Features</h3>
                <ul class="feature-list">
                    <li><strong>Sophisticated Gradient:</strong> Midnight blue to charcoal gray</li>
                    <li><strong>Premium Glass Effect:</strong> Semi-transparent slate gray form</li>
                    <li><strong>White Glow Borders:</strong> Subtle premium feel</li>
                    <li><strong>Electric Blue Accents:</strong> Professional button styling</li>
                    <li><strong>Pure White Typography:</strong> Bold, authoritative titles</li>
                    <li><strong>Soft Gold Links:</strong> Elegant navigation contrast</li>
                    <li><strong>Dark Gray Demo Section:</strong> Enhanced readability</li>
                    <li><strong>Modern Typography:</strong> Inter & Roboto fonts</li>
                </ul>
            </div>
        </div>

        <div class="info-section">
            <h3>🏛️ Administrative Authority</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="color: #FFFFFF; line-height: 1.6;">
                    <h4 style="color: #00B7EB; text-shadow: 0 0 10px rgba(0, 183, 235, 0.5);">Distinguished Design:</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Sophisticated midnight blue to charcoal gradient</li>
                        <li>Semi-transparent slate gray form container</li>
                        <li>Premium white glow border effects</li>
                        <li>Professional Inter & Roboto typography</li>
                    </ul>
                </div>
                <div style="color: #FFFFFF; line-height: 1.6;">
                    <h4 style="color: #FFD700; text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);">Enhanced Elements:</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Electric blue input borders with glow</li>
                        <li>Pulsing admin login button</li>
                        <li>Soft gold navigation links</li>
                        <li>Dark gray demo credentials section</li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4 style="color: #FFFFFF; text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);">🎯 Experience the Sophisticated Admin Login:</h4>
            <a href="admin-login.php" class="btn">
                🏛️ View Admin Login
            </a>
            <a href="login.php" class="btn-gold btn">
                👤 User Login
            </a>
            <a href="index.php" class="btn" style="background: #666666; border-color: #666666;">
                🏠 Back to Homepage
            </a>
        </div>

        <div style="background: rgba(0, 183, 235, 0.1); padding: 25px; border-radius: 15px; border: 2px solid #00B7EB; margin: 30px 0; box-shadow: 0 0 20px rgba(0, 183, 235, 0.3);">
            <h4 style="margin: 0 0 15px 0; color: #00B7EB; text-shadow: 0 0 10px rgba(0, 183, 235, 0.5);">🏛️ Sophisticated Admin Login Successfully Implemented!</h4>
            <p style="margin: 0; color: #FFFFFF; line-height: 1.6; text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);">
                The admin login page has been redesigned with a sleek, authoritative aesthetic featuring a midnight blue to charcoal gray gradient background, 
                semi-transparent slate gray form with white glow borders, pure white bold typography, electric blue input fields and buttons, 
                soft gold navigation links, and a dark gray demo credentials section. The design emphasizes professional authority while 
                maintaining modern sophistication and clear distinction from the user login experience.
            </p>
        </div>
    </div>
</body>
</html>
