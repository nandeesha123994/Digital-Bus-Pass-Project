<?php
require_once 'includes/dbconnection.php';
require_once 'includes/email_config.php';

// Create support_tickets table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    application_id VARCHAR(20),
    category VARCHAR(50) NOT NULL,
    priority VARCHAR(20) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($con->query($createTableSQL) === FALSE) {
    die("Error creating table: " . $con->error);
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $application_id = trim($_POST['application_id']);
    $category = trim($_POST['category']);
    $priority = trim($_POST['priority']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Generate ticket number
    $ticket_number = 'TICKET-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be 10 digits";
    }
    
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    
    if (empty($priority)) {
        $errors[] = "Priority is required";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    if (empty($errors)) {
        try {
            // Insert into database
            $stmt = $con->prepare("INSERT INTO support_tickets (ticket_number, name, email, phone, application_id, category, priority, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW())");
            
            $stmt->bind_param("sssssssss", $ticket_number, $name, $email, $phone, $application_id, $category, $priority, $subject, $message);
            
            if ($stmt->execute()) {
                // Log the email locally instead of sending it
                $email_subject = "Support Ticket Confirmation - " . $ticket_number;
                $email_body = "
                    <h2>Thank you for contacting our support team!</h2>
                    <p>We have received your support request and will get back to you as soon as possible.</p>
                    
                    <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <h3>Ticket Details:</h3>
                        <p><strong>Ticket Number:</strong> {$ticket_number}</p>
                        <p><strong>Category:</strong> {$category}</p>
                        <p><strong>Priority:</strong> {$priority}</p>
                        <p><strong>Subject:</strong> {$subject}</p>
                    </div>
                    
                    <p>You can track the status of your ticket using your ticket number: <strong>{$ticket_number}</strong></p>
                    
                    <div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <h4>What happens next?</h4>
                        <ul>
                            <li>Our support team will review your request</li>
                            <li>You'll receive updates via email</li>
                            <li>We aim to respond within 24 hours</li>
                        </ul>
                    </div>
                    
                    <p>If you need immediate assistance, please call our support hotline: <strong>+91 1800-123-4567</strong></p>
                ";
                
                // Log the email instead of sending it
                logEmail($email, $email_subject, $email_body);
                
                $success_message = "Your support request has been submitted successfully. Ticket Number: " . $ticket_number . 
                                 "<br><br><strong>Note:</strong> This is a development environment. In production, you would receive a confirmation email.";
                
                // Clear form data
                $_POST = array();
            } else {
                $error_message = "Error submitting support request. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - Bus Pass Management System</title>
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
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        .contact-card:hover {
            transform: translateY(-5px);
        }
        .contact-card i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        .contact-card h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .contact-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .contact-card a {
            background: #667eea;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .contact-card a:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        .support-form {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .support-form h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            outline: none;
        }
        textarea.form-control {
            height: 120px;
            resize: vertical;
        }
        .btn-submit {
            background: #667eea;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
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
        .faq-link {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border-left: 4px solid #17a2b8;
        }
        .faq-link h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .faq-link p {
            color: #666;
            margin-bottom: 15px;
        }
        .faq-link a {
            color: #17a2b8;
            text-decoration: none;
            font-weight: 600;
        }
        .faq-link a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .form-control.error {
            border-color: #dc3545;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-headset"></i> Contact Support</h1>
            <p>We're here to help you with any questions or issues</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="faq-link">
            <h3><i class="fas fa-question-circle"></i> Quick Help</h3>
            <p>Before contacting support, check our FAQ section for instant answers to common questions.</p>
            <a href="faqs.php">
                <i class="fas fa-external-link-alt"></i> Visit FAQ Section
            </a>
        </div>

        <div class="contact-grid">
            <div class="contact-card">
                <i class="fas fa-envelope"></i>
                <h3>Email Support</h3>
                <p>Send us an email and we'll respond within 24 hours. Perfect for detailed inquiries and documentation.</p>
                <a href="mailto:support@buspass.com">
                    <i class="fas fa-paper-plane"></i>
                    support@buspass.com
                </a>
            </div>

            <div class="contact-card">
                <i class="fas fa-phone"></i>
                <h3>Phone Support</h3>
                <p>Call our support hotline for immediate assistance. Available 24/7 for urgent issues and general inquiries.</p>
                <a href="tel:+9118001234567">
                    <i class="fas fa-phone-alt"></i>
                    +91 1800-123-4567
                </a>
            </div>

            <div class="contact-card">
                <i class="fas fa-comments"></i>
                <h3>Live Chat</h3>
                <p>Get instant help through our live chat system. Connect with our support team in real-time.</p>
                <a href="#" onclick="alert('Live chat feature coming soon!')">
                    <i class="fas fa-comment-dots"></i>
                    Start Chat
                </a>
            </div>

            <div class="contact-card">
                <i class="fas fa-search"></i>
                <h3>Track Your Issue</h3>
                <p>Already submitted a support request? Track the status of your ticket using your reference number.</p>
                <a href="track-status.php">
                    <i class="fas fa-ticket-alt"></i>
                    Track Status
                </a>
            </div>
        </div>

        <div class="support-form">
            <h2><i class="fas fa-edit"></i> Submit Support Request</h2>
            <form method="post" action="" id="supportForm">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="application_id">Application ID (if applicable)</label>
                    <input type="text" id="application_id" name="application_id" class="form-control" 
                           placeholder="e.g., BPMS2025123456"
                           value="<?php echo isset($_POST['application_id']) ? htmlspecialchars($_POST['application_id']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="category">Issue Category *</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select a category</option>
                        <option value="application" <?php echo (isset($_POST['category']) && $_POST['category'] === 'application') ? 'selected' : ''; ?>>Application Issues</option>
                        <option value="payment" <?php echo (isset($_POST['category']) && $_POST['category'] === 'payment') ? 'selected' : ''; ?>>Payment Problems</option>
                        <option value="technical" <?php echo (isset($_POST['category']) && $_POST['category'] === 'technical') ? 'selected' : ''; ?>>Technical Support</option>
                        <option value="account" <?php echo (isset($_POST['category']) && $_POST['category'] === 'account') ? 'selected' : ''; ?>>Account Issues</option>
                        <option value="general" <?php echo (isset($_POST['category']) && $_POST['category'] === 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                        <option value="feedback" <?php echo (isset($_POST['category']) && $_POST['category'] === 'feedback') ? 'selected' : ''; ?>>Feedback/Suggestions</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Priority Level *</label>
                    <select id="priority" name="priority" class="form-control" required>
                        <option value="">Select priority</option>
                        <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>Low - General question</option>
                        <option value="medium" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'medium') ? 'selected' : ''; ?>>Medium - Non-urgent issue</option>
                        <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>High - Urgent issue</option>
                        <option value="critical" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'critical') ? 'selected' : ''; ?>>Critical - System down</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" class="form-control" required 
                           placeholder="Brief description of your issue"
                           value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="message">Detailed Description *</label>
                    <textarea id="message" name="message" class="form-control" required 
                              placeholder="Please provide as much detail as possible about your issue, including any error messages you've encountered."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Submit Support Request
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('supportForm').addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            // Phone number validation
            const phone = document.getElementById('phone');
            if (phone.value && !/^[0-9]{10}$/.test(phone.value)) {
                isValid = false;
                phone.classList.add('error');
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });
        
        // Real-time validation
        document.querySelectorAll('.form-control').forEach(field => {
            field.addEventListener('input', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        });
    </script>
</body>
</html>
