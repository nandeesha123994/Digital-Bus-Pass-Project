<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyberpunk Login Design - Nrupatunga Smart Bus Pass Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Exo 2', 'Arial', sans-serif;
            background: linear-gradient(135deg, #1A3C34 0%, #0A1D37 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Cyberpunk background effects */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 255, 234, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(214, 0, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 0, 122, 0.05) 0%, transparent 50%);
            animation: pulse 4s ease-in-out infinite alternate;
        }

        @keyframes pulse {
            0% { opacity: 0.5; }
            100% { opacity: 1; }
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
            background: rgba(10, 29, 55, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 2px solid rgba(0, 255, 234, 0.3);
            box-shadow: 
                0 0 30px rgba(0, 255, 234, 0.2),
                0 0 60px rgba(214, 0, 255, 0.1);
        }

        .main-header h1 {
            font-family: 'Orbitron', monospace;
            color: #FFFFFF;
            margin: 0 0 10px 0;
            font-size: 2.5rem;
            font-weight: 900;
            text-shadow: 
                0 0 10px #D600FF,
                0 0 20px #D600FF,
                0 0 30px #D600FF;
            animation: titleGlow 2s ease-in-out infinite alternate;
        }

        .main-header p {
            color: #FFFFFF;
            margin: 0;
            font-size: 1.2rem;
            text-shadow: 0 0 10px #00FFEA;
        }

        @keyframes titleGlow {
            0% { 
                text-shadow: 
                    0 0 10px #D600FF,
                    0 0 20px #D600FF,
                    0 0 30px #D600FF;
            }
            100% { 
                text-shadow: 
                    0 0 15px #D600FF,
                    0 0 25px #D600FF,
                    0 0 35px #D600FF;
            }
        }

        .demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin: 40px 0;
        }

        .demo-section {
            background: rgba(10, 29, 55, 0.9);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            border: 2px solid rgba(0, 255, 234, 0.3);
            box-shadow: 
                0 0 30px rgba(0, 255, 234, 0.2),
                0 0 60px rgba(214, 0, 255, 0.1);
        }

        .demo-section h3 {
            font-family: 'Orbitron', monospace;
            color: #FFFFFF;
            margin: 0 0 20px 0;
            font-size: 1.5rem;
            text-shadow: 0 0 10px #D600FF;
        }

        .color-demo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .color-item {
            padding: 15px;
            border-radius: 10px;
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

        .neon-cyan {
            background: rgba(0, 255, 234, 0.1);
            color: #00FFEA;
            border-color: #00FFEA;
            box-shadow: 0 0 15px rgba(0, 255, 234, 0.3);
            text-shadow: 0 0 10px #00FFEA;
        }

        .neon-pink {
            background: rgba(255, 0, 122, 0.1);
            color: #FF007A;
            border-color: #FF007A;
            box-shadow: 0 0 15px rgba(255, 0, 122, 0.3);
            text-shadow: 0 0 10px #FF007A;
        }

        .neon-green {
            background: rgba(57, 255, 20, 0.1);
            color: #39FF14;
            border-color: #39FF14;
            box-shadow: 0 0 15px rgba(57, 255, 20, 0.3);
            text-shadow: 0 0 10px #39FF14;
        }

        .neon-purple {
            background: rgba(214, 0, 255, 0.1);
            color: #D600FF;
            border-color: #D600FF;
            box-shadow: 0 0 15px rgba(214, 0, 255, 0.3);
            text-shadow: 0 0 10px #D600FF;
        }

        .feature-list {
            color: #FFFFFF;
            line-height: 1.8;
        }

        .feature-list li {
            margin: 10px 0;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
        }

        .btn {
            background: #00FFEA;
            color: #000000;
            padding: 15px 30px;
            border: 2px solid #00FFEA;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: 'Orbitron', monospace;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            box-shadow: 
                0 0 20px rgba(0, 255, 234, 0.5),
                inset 0 0 20px rgba(0, 255, 234, 0.1);
            transition: all 0.3s ease;
            animation: buttonPulse 2s ease-in-out infinite alternate;
        }

        @keyframes buttonPulse {
            0% { 
                box-shadow: 
                    0 0 20px rgba(0, 255, 234, 0.5),
                    inset 0 0 20px rgba(0, 255, 234, 0.1);
            }
            100% { 
                box-shadow: 
                    0 0 30px rgba(0, 255, 234, 0.8),
                    inset 0 0 30px rgba(0, 255, 234, 0.2);
            }
        }

        .btn:hover {
            background: #FFFFFF;
            color: #00FFEA;
            text-decoration: none;
            box-shadow: 
                0 0 40px rgba(0, 255, 234, 0.8),
                inset 0 0 40px rgba(0, 255, 234, 0.3);
            transform: translateY(-2px);
        }

        .btn-pink {
            background: #FF007A;
            border-color: #FF007A;
            color: #FFFFFF;
            box-shadow: 
                0 0 20px rgba(255, 0, 122, 0.5),
                inset 0 0 20px rgba(255, 0, 122, 0.1);
        }

        .btn-pink:hover {
            background: #FFFFFF;
            color: #FF007A;
            box-shadow: 
                0 0 40px rgba(255, 0, 122, 0.8),
                inset 0 0 40px rgba(255, 0, 122, 0.3);
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
            <h1>🌟 Cyberpunk Login Design</h1>
            <p>Futuristic neon aesthetic for the Bus Pass Management System</p>
        </div>

        <div class="demo-grid">
            <div class="demo-section">
                <h3>🎨 Neon Color Palette</h3>
                <div class="color-demo">
                    <div class="color-item neon-cyan">
                        <h4>Neon Cyan</h4>
                        <p>#00FFEA</p>
                        <small>Login Button</small>
                    </div>
                    <div class="color-item neon-pink">
                        <h4>Neon Pink</h4>
                        <p>#FF007A</p>
                        <small>Links & Back Button</small>
                    </div>
                    <div class="color-item neon-green">
                        <h4>Neon Green</h4>
                        <p>#39FF14</p>
                        <small>Input Borders</small>
                    </div>
                    <div class="color-item neon-purple">
                        <h4>Neon Purple</h4>
                        <p>#D600FF</p>
                        <small>Title Glow</small>
                    </div>
                </div>
            </div>

            <div class="demo-section">
                <h3>✨ Design Features</h3>
                <ul class="feature-list">
                    <li><strong>Gradient Background:</strong> Deep teal to midnight blue</li>
                    <li><strong>Glowing Borders:</strong> Animated neon border effects</li>
                    <li><strong>Pulsing Animations:</strong> Subtle breathing effects</li>
                    <li><strong>Cyberpunk Typography:</strong> Orbitron & Exo 2 fonts</li>
                    <li><strong>Backdrop Blur:</strong> Modern glass morphism</li>
                    <li><strong>Neon Shadows:</strong> Color-matched glowing effects</li>
                    <li><strong>Interactive Elements:</strong> Hover animations</li>
                    <li><strong>Futuristic Theme:</strong> Sci-fi aesthetic</li>
                </ul>
            </div>
        </div>

        <div class="demo-section">
            <h3>🚀 Technical Implementation</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="color: #FFFFFF; line-height: 1.6;">
                    <h4 style="color: #00FFEA; text-shadow: 0 0 10px #00FFEA;">Background Effects:</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Deep teal (#1A3C34) to midnight blue (#0A1D37) gradient</li>
                        <li>Radial gradient overlays for depth</li>
                        <li>Pulsing animation for dynamic background</li>
                        <li>Backdrop blur for glass morphism effect</li>
                    </ul>
                </div>
                <div style="color: #FFFFFF; line-height: 1.6;">
                    <h4 style="color: #FF007A; text-shadow: 0 0 10px #FF007A;">Interactive Elements:</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Neon cyan (#00FFEA) login button with glow</li>
                        <li>Neon green (#39FF14) input field borders</li>
                        <li>Neon pink (#FF007A) links with hover effects</li>
                        <li>Animated border with color rotation</li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4 style="color: #FFFFFF; font-family: 'Orbitron', monospace; text-shadow: 0 0 10px #D600FF;">🎯 Experience the Cyberpunk Login:</h4>
            <a href="login.php" class="btn">
                🚀 View Cyberpunk Login
            </a>
            <a href="index.php" class="btn btn-pink">
                🏠 Back to Homepage
            </a>
        </div>

        <div style="background: rgba(57, 255, 20, 0.1); padding: 25px; border-radius: 15px; border: 2px solid #39FF14; margin: 30px 0; box-shadow: 0 0 20px rgba(57, 255, 20, 0.3);">
            <h4 style="margin: 0 0 15px 0; color: #39FF14; text-shadow: 0 0 10px #39FF14; font-family: 'Orbitron', monospace;">⚡ Cyberpunk Login Successfully Implemented!</h4>
            <p style="margin: 0; color: #FFFFFF; line-height: 1.6; text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);">
                The login page has been transformed with a futuristic cyberpunk aesthetic featuring neon colors, glowing effects, 
                animated borders, and a modern glass morphism design. The interface maintains full functionality while providing 
                an immersive, visually striking user experience with deep teal to midnight blue gradients and vibrant neon accents.
            </p>
        </div>
    </div>
</body>
</html>
