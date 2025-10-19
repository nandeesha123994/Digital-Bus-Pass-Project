<?php
// Update User Interface Colors - Apply 4 different color schemes
$servername = "localhost";
$username = "root";
$password = "";
$database = "bpmsdb";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Update User Interface Colors - Nrupatunga Digital Bus Pass System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
        }
        .content {
            padding: 30px;
        }
        .color-scheme {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .color-preview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .color-card {
            padding: 20px;
            border-radius: 8px;
            color: white;
            text-align: center;
            font-weight: 600;
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .info {
            color: #0c5460;
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        .btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
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
        
        /* Color Scheme 1: Ocean Blue */
        .scheme-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        
        /* Color Scheme 2: Sunset Orange */
        .scheme-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        
        /* Color Scheme 3: Forest Green */
        .scheme-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        /* Color Scheme 4: Royal Purple */
        .scheme-4 { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎨 Update User Interface Colors</h1>
            <p>Implementing 4 different color schemes for user interactions</p>
        </div>
        <div class="content">
            <div class="success">✅ Color scheme update system ready</div>
            
            <div class="color-scheme">
                <h3>🎨 New Color Schemes for User Interface</h3>
                <p>We'll implement 4 distinct color schemes for different user interaction types:</p>
                
                <div class="color-preview">
                    <div class="color-card scheme-1">
                        <h4>Scheme 1: Ocean Blue</h4>
                        <p>Primary Actions</p>
                        <small>Login, Registration, Main Navigation</small>
                    </div>
                    <div class="color-card scheme-2">
                        <h4>Scheme 2: Sunset Orange</h4>
                        <p>Application Actions</p>
                        <small>Apply Pass, Submit Forms, Payment</small>
                    </div>
                    <div class="color-card scheme-3">
                        <h4>Scheme 3: Sky Blue</h4>
                        <p>Information Actions</p>
                        <small>Track Application, View Status, Help</small>
                    </div>
                    <div class="color-card scheme-4">
                        <h4>Scheme 4: Soft Pink</h4>
                        <p>Secondary Actions</p>
                        <small>Reviews, Feedback, Support</small>
                    </div>
                </div>
            </div>
            
            <div class="info">
                <h4>🔧 Files to be updated with new color schemes:</h4>
                <ul>
                    <li><strong>index.php</strong> - Homepage with Ocean Blue theme</li>
                    <li><strong>apply-pass.php</strong> - Application form with Sunset Orange theme</li>
                    <li><strong>track-application.php</strong> - Tracking with Sky Blue theme</li>
                    <li><strong>instant-reviews-display.php</strong> - Reviews with Soft Pink theme</li>
                    <li><strong>user-dashboard.php</strong> - Mixed color scheme for different sections</li>
                    <li><strong>login.php & register.php</strong> - Ocean Blue theme</li>
                </ul>
            </div>
            
            <?php
            // Define the 4 color schemes
            $colorSchemes = [
                'ocean_blue' => [
                    'name' => 'Ocean Blue',
                    'primary' => '#667eea',
                    'secondary' => '#764ba2',
                    'accent' => '#4facfe',
                    'text' => '#ffffff',
                    'description' => 'Primary actions like login, registration, main navigation'
                ],
                'sunset_orange' => [
                    'name' => 'Sunset Orange',
                    'primary' => '#f093fb',
                    'secondary' => '#f5576c',
                    'accent' => '#ff6b6b',
                    'text' => '#ffffff',
                    'description' => 'Application actions like apply pass, submit forms, payment'
                ],
                'sky_blue' => [
                    'name' => 'Sky Blue',
                    'primary' => '#4facfe',
                    'secondary' => '#00f2fe',
                    'accent' => '#74b9ff',
                    'text' => '#ffffff',
                    'description' => 'Information actions like track application, view status'
                ],
                'soft_pink' => [
                    'name' => 'Soft Pink',
                    'primary' => '#a8edea',
                    'secondary' => '#fed6e3',
                    'accent' => '#fd79a8',
                    'text' => '#333333',
                    'description' => 'Secondary actions like reviews, feedback, support'
                ]
            ];
            
            echo "<div class='color-scheme'>";
            echo "<h3>📋 Color Scheme Details</h3>";
            echo "<table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>";
            echo "<tr style='background: #f8f9fa;'>";
            echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Scheme</th>";
            echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Primary</th>";
            echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Secondary</th>";
            echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Accent</th>";
            echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Usage</th>";
            echo "</tr>";
            
            foreach ($colorSchemes as $key => $scheme) {
                echo "<tr>";
                echo "<td style='border: 1px solid #ddd; padding: 12px;'><strong>" . $scheme['name'] . "</strong></td>";
                echo "<td style='border: 1px solid #ddd; padding: 12px; background: " . $scheme['primary'] . "; color: " . $scheme['text'] . ";'>" . $scheme['primary'] . "</td>";
                echo "<td style='border: 1px solid #ddd; padding: 12px; background: " . $scheme['secondary'] . "; color: " . $scheme['text'] . ";'>" . $scheme['secondary'] . "</td>";
                echo "<td style='border: 1px solid #ddd; padding: 12px; background: " . $scheme['accent'] . "; color: " . $scheme['text'] . ";'>" . $scheme['accent'] . "</td>";
                echo "<td style='border: 1px solid #ddd; padding: 12px;'>" . $scheme['description'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
            
            // Create CSS file with new color schemes
            $cssContent = "/* Updated Color Schemes for Nrupatunga Digital Bus Pass System */

/* Color Scheme 1: Ocean Blue - Primary Actions */
.theme-ocean-blue {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
}

.btn-ocean-blue {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #ffffff;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-ocean-blue:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: #ffffff;
}

/* Color Scheme 2: Sunset Orange - Application Actions */
.theme-sunset-orange {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: #ffffff;
}

.btn-sunset-orange {
    background: linear-gradient(135deg, #f093fb, #f5576c);
    color: #ffffff;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-sunset-orange:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
    color: #ffffff;
}

/* Color Scheme 3: Sky Blue - Information Actions */
.theme-sky-blue {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: #ffffff;
}

.btn-sky-blue {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color: #ffffff;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-sky-blue:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
    color: #ffffff;
}

/* Color Scheme 4: Soft Pink - Secondary Actions */
.theme-soft-pink {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #333333;
}

.btn-soft-pink {
    background: linear-gradient(135deg, #a8edea, #fed6e3);
    color: #333333;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-soft-pink:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(168, 237, 234, 0.4);
    color: #333333;
}

/* Navigation Color Schemes */
.nav-ocean-blue {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.nav-sunset-orange {
    background: linear-gradient(135deg, #f093fb, #f5576c);
}

.nav-sky-blue {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
}

.nav-soft-pink {
    background: linear-gradient(135deg, #a8edea, #fed6e3);
    color: #333333;
}

/* Card Color Schemes */
.card-ocean-blue {
    border-left: 4px solid #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
}

.card-sunset-orange {
    border-left: 4px solid #f093fb;
    background: linear-gradient(135deg, rgba(240, 147, 251, 0.1), rgba(245, 87, 108, 0.1));
}

.card-sky-blue {
    border-left: 4px solid #4facfe;
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
}

.card-soft-pink {
    border-left: 4px solid #a8edea;
    background: linear-gradient(135deg, rgba(168, 237, 234, 0.1), rgba(254, 214, 227, 0.1));
}

/* Status Badge Color Schemes */
.badge-ocean-blue {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-sunset-orange {
    background: linear-gradient(135deg, #f093fb, #f5576c);
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-sky-blue {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-soft-pink {
    background: linear-gradient(135deg, #a8edea, #fed6e3);
    color: #333333;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}";
            
            // Save CSS file
            file_put_contents('assets/css/color-schemes.css', $cssContent);
            
            echo "<div class='success'>";
            echo "<h3>🎉 Color Schemes Created Successfully!</h3>";
            echo "<p>New CSS file created: <code>assets/css/color-schemes.css</code></p>";
            echo "</div>";
            ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <h4>🚀 Test Updated Color Schemes:</h4>
                <a href="index.php" class="btn" style="background: linear-gradient(135deg, #667eea, #764ba2);">🏠 Homepage (Ocean Blue)</a>
                <a href="apply-pass.php" class="btn" style="background: linear-gradient(135deg, #f093fb, #f5576c);">📝 Apply Pass (Sunset Orange)</a>
                <a href="track-application.php" class="btn" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">🔍 Track App (Sky Blue)</a>
                <a href="instant-reviews-display.php" class="btn" style="background: linear-gradient(135deg, #a8edea, #fed6e3); color: #333;">⭐ Reviews (Soft Pink)</a>
            </div>
            
            <div class="info">
                <h4>🎨 Color Scheme Implementation:</h4>
                <ul>
                    <li>✅ <strong>Ocean Blue:</strong> Login, Registration, Main Navigation</li>
                    <li>✅ <strong>Sunset Orange:</strong> Apply Pass, Submit Forms, Payment Actions</li>
                    <li>✅ <strong>Sky Blue:</strong> Track Application, View Status, Information</li>
                    <li>✅ <strong>Soft Pink:</strong> Reviews, Feedback, Support Features</li>
                </ul>
                
                <h4>📁 Files Updated:</h4>
                <ul>
                    <li>✅ Created <code>assets/css/color-schemes.css</code> with 4 color themes</li>
                    <li>🔄 Next: Update individual PHP files to use new color schemes</li>
                    <li>🔄 Next: Apply themes to buttons, headers, and navigation</li>
                    <li>🔄 Next: Update user dashboard with mixed color scheme</li>
                </ul>
                
                <h4>🚀 Benefits:</h4>
                <ul>
                    <li><strong>Visual Distinction:</strong> Different colors for different action types</li>
                    <li><strong>Better UX:</strong> Users can quickly identify action categories</li>
                    <li><strong>Modern Design:</strong> Gradient backgrounds and smooth transitions</li>
                    <li><strong>Accessibility:</strong> High contrast and readable color combinations</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
