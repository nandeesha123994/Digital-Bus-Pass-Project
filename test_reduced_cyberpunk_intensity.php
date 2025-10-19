<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reduced Cyberpunk Intensity - Nrupatunga Smart Bus Pass Portal</title>
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

        /* Reduced cyberpunk background effects */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 255, 234, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(214, 0, 255, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 0, 122, 0.03) 0%, transparent 50%);
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
                0 0 25px rgba(0, 255, 234, 0.15),
                0 0 50px rgba(214, 0, 255, 0.08);
        }

        .main-header h1 {
            font-family: 'Orbitron', monospace;
            color: #FFFFFF;
            margin: 0 0 10px 0;
            font-size: 2.5rem;
            font-weight: 900;
            text-shadow: 
                0 0 8px #D600FF,
                0 0 16px #D600FF,
                0 0 24px #D600FF;
        }

        .main-header p {
            color: #FFFFFF;
            margin: 0;
            font-size: 1.2rem;
            text-shadow: 0 0 8px #00FFEA;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin: 40px 0;
        }

        .comparison-section {
            background: rgba(10, 29, 55, 0.9);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            border: 2px solid rgba(0, 255, 234, 0.3);
            box-shadow: 
                0 0 25px rgba(0, 255, 234, 0.15),
                0 0 50px rgba(214, 0, 255, 0.08);
        }

        .comparison-section h3 {
            font-family: 'Orbitron', monospace;
            color: #FFFFFF;
            margin: 0 0 20px 0;
            font-size: 1.5rem;
            text-shadow: 0 0 8px #D600FF;
        }

        /* Reduced intensity form elements */
        .demo-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #39FF14;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 16px;
            background: rgba(0, 0, 0, 0.7);
            color: #FFFFFF;
            font-family: 'Exo 2', sans-serif;
            box-shadow: 
                0 0 8px rgba(57, 255, 20, 0.25),
                inset 0 0 8px rgba(57, 255, 20, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .demo-input:focus {
            outline: none;
            box-shadow: 
                0 0 15px rgba(57, 255, 20, 0.4),
                inset 0 0 12px rgba(57, 255, 20, 0.15);
        }

        .demo-button {
            width: 100%;
            background: #00FFEA;
            color: #000000;
            padding: 15px;
            border: 2px solid #00FFEA;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            font-family: 'Orbitron', monospace;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 
                0 0 15px rgba(0, 255, 234, 0.4),
                inset 0 0 15px rgba(0, 255, 234, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .demo-button:hover {
            background: #FFFFFF;
            color: #00FFEA;
            box-shadow: 
                0 0 25px rgba(0, 255, 234, 0.6),
                inset 0 0 25px rgba(0, 255, 234, 0.15);
        }

        .demo-link {
            color: #FF007A;
            text-decoration: none;
            font-weight: 600;
            text-shadow: 
                0 0 4px #FF007A,
                0 0 8px #FF007A;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 10px 0;
        }

        .demo-link:hover {
            color: #FFFFFF;
            text-shadow: 
                0 0 10px #FF007A,
                0 0 20px #FF007A;
            transform: scale(1.02);
        }

        .intensity-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .intensity-item {
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            color: white;
            font-weight: 600;
        }

        .before-intensity {
            background: rgba(10, 29, 55, 0.9);
            border: 2px solid rgba(0, 255, 234, 0.5);
            box-shadow: 
                0 0 30px rgba(0, 255, 234, 0.3),
                0 0 60px rgba(214, 0, 255, 0.2);
        }

        .after-intensity {
            background: rgba(10, 29, 55, 0.9);
            border: 2px solid rgba(0, 255, 234, 0.3);
            box-shadow: 
                0 0 25px rgba(0, 255, 234, 0.15),
                0 0 50px rgba(214, 0, 255, 0.08);
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
                0 0 15px rgba(0, 255, 234, 0.4),
                inset 0 0 15px rgba(0, 255, 234, 0.08);
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #FFFFFF;
            color: #00FFEA;
            text-decoration: none;
            box-shadow: 
                0 0 25px rgba(0, 255, 234, 0.6),
                inset 0 0 25px rgba(0, 255, 234, 0.15);
            transform: translateY(-2px);
        }

        .btn-pink {
            background: #FF007A;
            border-color: #FF007A;
            color: #FFFFFF;
            box-shadow: 
                0 0 12px rgba(255, 0, 122, 0.4),
                inset 0 0 12px rgba(255, 0, 122, 0.08);
        }

        .btn-pink:hover {
            background: #FFFFFF;
            color: #FF007A;
            box-shadow: 
                0 0 20px rgba(255, 0, 122, 0.6),
                inset 0 0 20px rgba(255, 0, 122, 0.15);
        }

        @media (max-width: 768px) {
            .comparison-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .main-header h1 {
                font-size: 2rem;
            }
            
            .intensity-comparison {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-header">
            <h1>🎨 Reduced Cyberpunk Intensity</h1>
            <p>Subtle, refined neon aesthetic for better visual balance</p>
        </div>

        <div class="comparison-grid">
            <div class="comparison-section">
                <h3>🔧 Intensity Adjustments</h3>
                <ul class="feature-list">
                    <li><strong>Background Radials:</strong> Reduced from 0.1 to 0.06 opacity</li>
                    <li><strong>Container Glow:</strong> Decreased from 30px to 25px blur</li>
                    <li><strong>Input Shadows:</strong> Lowered from 10px to 8px blur</li>
                    <li><strong>Button Glow:</strong> Reduced from 20px to 15px blur</li>
                    <li><strong>Text Shadows:</strong> Decreased from 10px to 8px blur</li>
                    <li><strong>Link Glow:</strong> Reduced from 5px/10px to 4px/8px</li>
                    <li><strong>Overall Opacity:</strong> 20-30% reduction across elements</li>
                </ul>
            </div>

            <div class="comparison-section">
                <h3>✨ Enhanced Elements Demo</h3>
                <input type="text" class="demo-input" placeholder="Reduced Glow Input Field" readonly>
                <button class="demo-button">Subtle Neon Button</button>
                <a href="#" class="demo-link">Refined Glow Link</a>
                <p style="color: #FFFFFF; margin: 15px 0; text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);">
                    All elements maintain the cyberpunk aesthetic while providing better visual balance and reduced eye strain.
                </p>
            </div>
        </div>

        <div class="comparison-section">
            <h3>📊 Before vs After Intensity</h3>
            <div class="intensity-comparison">
                <div class="intensity-item before-intensity">
                    <h4>Before: High Intensity</h4>
                    <p>Strong glows, bright shadows</p>
                    <small>0.1-0.5 opacity values</small>
                </div>
                <div class="intensity-item after-intensity">
                    <h4>After: Refined Intensity</h4>
                    <p>Subtle glows, balanced shadows</p>
                    <small>0.06-0.4 opacity values</small>
                </div>
            </div>
        </div>

        <div class="comparison-section">
            <h3>🎯 Benefits of Reduced Intensity</h3>
            <ul class="feature-list">
                <li><strong>Better Readability:</strong> Less visual noise, easier text reading</li>
                <li><strong>Reduced Eye Strain:</strong> Softer glows for comfortable viewing</li>
                <li><strong>Professional Appeal:</strong> More business-appropriate aesthetic</li>
                <li><strong>Maintained Style:</strong> Cyberpunk feel without overwhelming effects</li>
                <li><strong>Enhanced Focus:</strong> Users can focus on content over effects</li>
                <li><strong>Accessibility:</strong> Better for users sensitive to bright effects</li>
                <li><strong>Battery Friendly:</strong> Reduced power consumption on OLED displays</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4 style="color: #FFFFFF; font-family: 'Orbitron', monospace; text-shadow: 0 0 8px #D600FF;">🎯 Experience the Refined Cyberpunk Design:</h4>
            <a href="login.php" class="btn">
                🔐 Refined Login
            </a>
            <a href="register.php" class="btn btn-pink">
                📝 Refined Register
            </a>
            <a href="index.php" class="btn" style="background: #39FF14; border-color: #39FF14; color: #000000;">
                🏠 Back to Homepage
            </a>
        </div>

        <div style="background: rgba(57, 255, 20, 0.08); padding: 25px; border-radius: 15px; border: 2px solid rgba(57, 255, 20, 0.3); margin: 30px 0; box-shadow: 0 0 15px rgba(57, 255, 20, 0.15);">
            <h4 style="margin: 0 0 15px 0; color: #39FF14; text-shadow: 0 0 8px rgba(57, 255, 20, 0.5); font-family: 'Orbitron', monospace;">✨ Color Intensity Successfully Reduced!</h4>
            <p style="margin: 0; color: #FFFFFF; line-height: 1.6; text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);">
                The cyberpunk login and registration forms now feature refined color intensity with 20-30% reduced glow effects, 
                softer shadows, and more subtle neon accents. The aesthetic maintains its futuristic appeal while providing 
                better visual balance, improved readability, and reduced eye strain for a more professional and comfortable user experience.
            </p>
        </div>
    </div>
</body>
</html>
