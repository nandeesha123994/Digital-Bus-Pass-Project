<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Bus Pass Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }
        .page-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        .page-header p {
            color: #666;
            font-size: 1.2rem;
        }
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .content-section h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .content-section p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .feature-item {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .feature-item i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .feature-item h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .feature-item p {
            color: #666;
            font-size: 0.9rem;
        }
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        .back-link a {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .back-link a:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-info-circle"></i> About Us</h1>
            <p>Learn more about our Bus Pass Management System</p>
        </div>

        <div class="content-section">
            <h2><i class="fas fa-bus"></i> Our Mission</h2>
            <p>
                The Bus Pass Management System is designed to revolutionize public transportation by providing 
                a seamless, digital solution for bus pass applications and management. Our mission is to make 
                public transportation more accessible, efficient, and user-friendly for everyone.
            </p>
            <p>
                We believe that technology should simplify everyday tasks, and that's exactly what our platform 
                does. By digitizing the bus pass application process, we eliminate long queues, reduce paperwork, 
                and provide instant access to transportation services.
            </p>
        </div>

        <div class="content-section">
            <h2><i class="fas fa-star"></i> Key Features</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Mobile-First Design</h3>
                    <p>Optimized for smartphones and tablets, allowing you to apply for bus passes anywhere, anytime.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure Platform</h3>
                    <p>Advanced security measures protect your personal information and payment details.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-clock"></i>
                    <h3>Real-Time Tracking</h3>
                    <p>Track your application status in real-time with our unique Application ID system.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-credit-card"></i>
                    <h3>Multiple Payment Options</h3>
                    <p>Support for various payment methods including PhonePe, Stripe, and other secure gateways.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-headset"></i>
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock customer support to assist you with any questions or issues.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-download"></i>
                    <h3>Digital Passes</h3>
                    <p>Download your bus pass instantly after approval and payment completion.</p>
                </div>
            </div>
        </div>

        <div class="content-section">
            <h2><i class="fas fa-users"></i> Our Team</h2>
            <p>
                Our dedicated team of developers, designers, and transportation experts work tirelessly to 
                improve the public transportation experience. We combine technical expertise with deep 
                understanding of user needs to create solutions that truly make a difference.
            </p>
            <p>
                With years of experience in software development and public service, our team is committed 
                to delivering reliable, efficient, and user-friendly solutions for modern transportation challenges.
            </p>
        </div>

        <div class="content-section">
            <h2><i class="fas fa-chart-line"></i> Our Impact</h2>
            <p>
                Since our launch, we have successfully processed thousands of bus pass applications, 
                significantly reducing processing time and improving user satisfaction. Our platform has:
            </p>
            <ul style="color: #666; line-height: 1.6;">
                <li>Reduced application processing time by 80%</li>
                <li>Eliminated paper-based applications</li>
                <li>Provided 24/7 accessibility to transportation services</li>
                <li>Improved transparency with real-time status tracking</li>
                <li>Enhanced security with digital verification systems</li>
            </ul>
        </div>

        <div class="content-section">
            <h2><i class="fas fa-rocket"></i> Future Vision</h2>
            <p>
                We are continuously working to enhance our platform with new features and improvements. 
                Our roadmap includes integration with smart city initiatives, AI-powered route optimization, 
                and enhanced mobile applications for an even better user experience.
            </p>
            <p>
                Starting July 2025, we will introduce a new digital bus pass format with enhanced security 
                features and QR code integration, making public transportation even more convenient and secure.
            </p>
        </div>

        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
