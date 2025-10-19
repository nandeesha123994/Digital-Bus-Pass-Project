<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Test - Bus Pass Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            min-height: 0;
        }

        .test-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .test-container h1 {
            color: #333;
            margin-bottom: 1rem;
        }

        .test-container p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 0 0 0;
            margin-top: auto;
            flex-shrink: 0;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .footer-section h3 {
            color: #ecf0f1;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
            display: inline-block;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 1.3rem;
            font-weight: bold;
            color: #3498db;
        }

        .footer-logo i {
            font-size: 1.5rem;
        }

        .footer-description {
            color: #bdc3c7;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .footer-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 8px 0;
        }

        .footer-link:hover {
            color: #3498db;
            transform: translateX(5px);
        }

        .footer-link i {
            width: 16px;
            text-align: center;
            font-size: 0.9rem;
        }

        .footer-bottom {
            border-top: 1px solid #34495e;
            padding: 20px 0;
            background: rgba(0,0,0,0.2);
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-bottom p {
            margin: 0;
            color: #bdc3c7;
            font-size: 0.9rem;
        }

        .footer-social {
            display: flex;
            gap: 15px;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            color: #bdc3c7;
            text-decoration: none;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #3498db;
            color: white;
            transform: translateY(-3px);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
                align-items: flex-start;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
                text-align: center;
            }

            .footer-section:first-child {
                text-align: center;
            }

            .footer-logo {
                justify-content: center;
            }

            .footer-links {
                align-items: center;
            }

            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .footer-social {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="test-container">
            <h1><i class="fas fa-vial"></i> Footer Position Test</h1>
            <p>This is a test page with minimal content to demonstrate how the footer behaves on shorter pages.</p>
            <p><strong>Expected Behavior:</strong></p>
            <ul style="text-align: left; color: #666;">
                <li>Footer should stick to the bottom of the viewport</li>
                <li>Content should be centered in available space</li>
                <li>Footer should not overlap content</li>
                <li>On mobile, layout should remain responsive</li>
            </ul>
            
            <div style="margin-top: 2rem;">
                <a href="index.php" class="btn">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="#" class="btn" onclick="addContent()">
                    <i class="fas fa-plus"></i> Add Content
                </a>
            </div>
            
            <div id="dynamic-content" style="margin-top: 1rem;"></div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-bus"></i>
                        <span>Bus Pass Management System</span>
                    </div>
                    <p class="footer-description">
                        Digital solution for seamless bus pass applications and management. 
                        Secure, efficient, and user-friendly platform for all your transportation needs.
                    </p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <div class="footer-links">
                        <a href="about-us.php" class="footer-link">
                            <i class="fas fa-info-circle"></i>
                            <span>About Us</span>
                        </a>
                        <a href="contact-support.php" class="footer-link">
                            <i class="fas fa-headset"></i>
                            <span>Contact Support</span>
                        </a>
                        <a href="faqs.php" class="footer-link">
                            <i class="fas fa-question-circle"></i>
                            <span>FAQs</span>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Legal</h3>
                    <div class="footer-links">
                        <a href="privacy-policy.php" class="footer-link">
                            <i class="fas fa-shield-alt"></i>
                            <span>Privacy Policy</span>
                        </a>
                        <a href="terms-conditions.php" class="footer-link">
                            <i class="fas fa-file-contract"></i>
                            <span>Terms & Conditions</span>
                        </a>
                        <a href="track-status.php" class="footer-link">
                            <i class="fas fa-search"></i>
                            <span>Track Status</span>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Connect</h3>
                    <div class="footer-links">
                        <a href="mailto:support@buspass.com" class="footer-link">
                            <i class="fas fa-envelope"></i>
                            <span>support@buspass.com</span>
                        </a>
                        <a href="tel:+1234567890" class="footer-link">
                            <i class="fas fa-phone"></i>
                            <span>+1 (234) 567-890</span>
                        </a>
                        <a href="#" class="footer-link">
                            <i class="fas fa-clock"></i>
                            <span>24/7 Support</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; <?php echo date('Y'); ?> Bus Pass Management System. All rights reserved.</p>
                    <div class="footer-social">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        let contentCount = 0;
        
        function addContent() {
            contentCount++;
            const dynamicContent = document.getElementById('dynamic-content');
            const newParagraph = document.createElement('p');
            newParagraph.style.color = '#666';
            newParagraph.style.marginTop = '1rem';
            newParagraph.innerHTML = `<strong>Dynamic Content ${contentCount}:</strong> This is additional content to test how the footer behaves when the page content grows. Notice how the footer moves down naturally as content is added.`;
            dynamicContent.appendChild(newParagraph);
        }
    </script>
</body>
</html>
