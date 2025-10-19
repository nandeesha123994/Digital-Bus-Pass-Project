<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - Bus Pass Management System</title>
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
        .search-box {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .search-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .search-input:focus {
            border-color: #667eea;
            outline: none;
        }
        .faq-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .category-btn {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
        }
        .category-btn:hover, .category-btn.active {
            border-color: #667eea;
            background: #f8f9ff;
            color: #667eea;
        }
        .category-btn i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }
        .faq-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .faq-item {
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        .faq-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .faq-question {
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            padding: 15px 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: color 0.3s ease;
        }
        .faq-question:hover {
            color: #667eea;
        }
        .faq-question i {
            transition: transform 0.3s ease;
        }
        .faq-question.active i {
            transform: rotate(180deg);
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            color: #666;
            line-height: 1.6;
        }
        .faq-answer.active {
            max-height: 500px;
            padding-top: 15px;
        }
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        .back-link a {
            background: #6c757d;
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
            background: #5a6268;
            transform: translateY(-2px);
        }
        .contact-cta {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin-top: 30px;
            border-left: 4px solid #28a745;
        }
        .contact-cta h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .contact-cta p {
            color: #666;
            margin-bottom: 15px;
        }
        .contact-cta a {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .contact-cta a:hover {
            background: #218838;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-question-circle"></i> Frequently Asked Questions</h1>
            <p>Find quick answers to common questions about our Bus Pass Management System</p>
        </div>

        <div class="search-box">
            <input type="text" class="search-input" placeholder="🔍 Search FAQs..." onkeyup="searchFAQs(this.value)">
        </div>

        <div class="faq-categories">
            <a href="#" class="category-btn active" onclick="filterFAQs('all')">
                <i class="fas fa-list"></i>
                All Questions
            </a>
            <a href="#" class="category-btn" onclick="filterFAQs('application')">
                <i class="fas fa-file-alt"></i>
                Applications
            </a>
            <a href="#" class="category-btn" onclick="filterFAQs('payment')">
                <i class="fas fa-credit-card"></i>
                Payments
            </a>
            <a href="#" class="category-btn" onclick="filterFAQs('technical')">
                <i class="fas fa-cog"></i>
                Technical
            </a>
            <a href="#" class="category-btn" onclick="filterFAQs('account')">
                <i class="fas fa-user"></i>
                Account
            </a>
        </div>

        <div class="faq-section">
            <!-- Application FAQs -->
            <div class="faq-item" data-category="application">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    How do I apply for a bus pass?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    To apply for a bus pass, simply click the "Register Now" button on our home page, create an account, and fill out the application form. You'll need to provide personal details, upload a photo, and select your pass type. After submission, you'll receive a unique Application ID for tracking.
                </div>
            </div>

            <div class="faq-item" data-category="application">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    What is an Application ID and how do I use it?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    An Application ID is a unique identifier in the format BPMS2025XXXXXX that is generated when you submit your application. You can use this ID to track your application status on our "Track Status" page. Keep this ID safe as you'll need it for any inquiries about your application.
                </div>
            </div>

            <div class="faq-item" data-category="application">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    How long does it take to process my application?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Due to high demand, bus pass applications typically take 3-5 business days to process. You'll receive email notifications at each stage of the process, and you can track your application status in real-time using your Application ID.
                </div>
            </div>

            <!-- Payment FAQs -->
            <div class="faq-item" data-category="payment">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    What payment methods do you accept?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    We accept multiple payment methods including PhonePe (our primary gateway), Stripe for international cards, and demo payments for testing. All payments are processed securely with industry-standard encryption.
                </div>
            </div>

            <div class="faq-item" data-category="payment">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    Is my payment information secure?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Yes, absolutely! We use industry-standard security measures and work with trusted payment gateways like PhonePe and Stripe. Your payment information is encrypted and never stored on our servers. All transactions are processed through secure, PCI-compliant systems.
                </div>
            </div>

            <div class="faq-item" data-category="payment">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    Can I get a refund if my application is rejected?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Yes, if your application is rejected for reasons beyond your control, you are eligible for a full refund. The refund will be processed within 5-7 business days to your original payment method. Contact our support team for assistance with refund requests.
                </div>
            </div>

            <!-- Technical FAQs -->
            <div class="faq-item" data-category="technical">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    Is the website mobile-friendly?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Yes! Our website is fully optimized for mobile devices. You can apply for bus passes, track applications, and manage your account easily from your smartphone or tablet. The interface automatically adapts to your screen size for the best experience.
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    What browsers are supported?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Our platform works on all modern browsers including Chrome, Firefox, Safari, and Edge. For the best experience, we recommend using the latest version of your preferred browser. Internet Explorer is not supported.
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    I'm having trouble uploading my photo. What should I do?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Make sure your photo is in JPG, PNG, or GIF format and is less than 5MB in size. The image should be clear and show your face clearly. If you're still having trouble, try using a different browser or contact our support team for assistance.
                </div>
            </div>

            <!-- Account FAQs -->
            <div class="faq-item" data-category="account">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    How do I reset my password?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Click the "Forgot Password" link on the login page, enter your email address, and we'll send you a password reset link. Follow the instructions in the email to create a new password. If you don't receive the email, check your spam folder or contact support.
                </div>
            </div>

            <div class="faq-item" data-category="account">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    Can I update my personal information after submitting an application?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Once an application is submitted, you cannot modify the information directly. However, you can contact our support team with your Application ID, and they can help you update necessary information before the application is processed.
                </div>
            </div>

            <div class="faq-item" data-category="account">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    How do I download my bus pass?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Once your application is approved and payment is completed, you can download your bus pass from your user dashboard. Login to your account, go to "My Applications," and click the "Download Pass" button next to your approved application.
                </div>
            </div>
        </div>

        <div class="contact-cta">
            <h3><i class="fas fa-headset"></i> Still Need Help?</h3>
            <p>Can't find the answer you're looking for? Our support team is here to help!</p>
            <a href="contact-support.php">
                <i class="fas fa-envelope"></i>
                Contact Support
            </a>
        </div>

        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>

    <script>
        function toggleFAQ(button) {
            const answer = button.nextElementSibling;
            const isActive = answer.classList.contains('active');
            
            // Close all other FAQs
            document.querySelectorAll('.faq-answer.active').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelectorAll('.faq-question.active').forEach(item => {
                item.classList.remove('active');
            });
            
            // Toggle current FAQ
            if (!isActive) {
                answer.classList.add('active');
                button.classList.add('active');
            }
        }

        function filterFAQs(category) {
            // Update active category button
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Show/hide FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function searchFAQs(searchTerm) {
            const items = document.querySelectorAll('.faq-item');
            const term = searchTerm.toLowerCase();
            
            items.forEach(item => {
                const question = item.querySelector('.faq-question').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                
                if (question.includes(term) || answer.includes(term)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
