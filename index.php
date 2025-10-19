<?php
/**
 * Home Page - Nrupatunga Digital Bus Pass System
 * Welcome page with navigation options
 */

// Include database connection and optimized functions
include('includes/dbconnection.php');
include('get_announcements.php');

// Get active announcements (optimized)
$announcements = getActiveAnnouncements($con);

// Get instant reviews for homepage display (optimized)
$reviewsResult = null;
$reviewStats = ['total_reviews' => 0, 'average_rating' => 4.8, 'five_star_count' => 0];

// Check if instant_reviews table exists and fetch reviews
try {
    $tableCheck = $con->query("SHOW TABLES LIKE 'instant_reviews'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Get latest 5 active reviews for homepage display
        $reviewsQuery = "SELECT ir.review_text, ir.rating, ir.created_at, u.full_name as username
                         FROM instant_reviews ir
                         JOIN users u ON ir.user_id = u.id
                         WHERE ir.status = 'active'
                         ORDER BY ir.created_at DESC
                         LIMIT 5";
        $reviewsResult = $con->query($reviewsQuery);

        // Get review statistics
        $statsQuery = "SELECT COUNT(*) as total_reviews, AVG(rating) as average_rating,
                       SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_count
                       FROM instant_reviews WHERE status = 'active'";
        $statsResult = $con->query($statsQuery);
        if ($statsResult && $statsResult->num_rows > 0) {
            $reviewStats = $statsResult->fetch_assoc();
            $reviewStats['average_rating'] = $reviewStats['average_rating'] ? round($reviewStats['average_rating'], 1) : 4.8;
        }
    }
} catch (Exception $e) {
    // Fallback to default values if table doesn't exist
    $reviewsResult = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nrupatunga Smart Bus Pass Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        body {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            display: flex;
            flex-direction: column;
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            min-height: 0; /* Allow content to determine height */
        }
        .title-section {
            background: white;
            padding: 3.5rem 3rem;
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        /* Floating Text Banner Styles */
        #floatingText {
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            position: relative;
            font-size: 22px;
            font-weight: bold;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 20px;
            margin-top: 25px;
            border-radius: 50px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
        }

        #floatingText::before {
            content: attr(data-text);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            animation: scrollText 12s linear infinite;
            white-space: nowrap;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #floatingText::after {
            content: "🚌 ⭐ 🇮🇳 ✨";
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            animation: scrollText 12s linear infinite;
            animation-delay: -6s;
            white-space: nowrap;
            margin-left: 50px;
            font-size: 20px;
        }

        @keyframes scrollText {
            0% {
                left: 100%;
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            45% {
                left: 50%;
                transform: translateX(-50%) translateY(-50%);
            }
            55% {
                left: 50%;
                transform: translateX(-50%) translateY(-50%);
            }
            90% {
                opacity: 1;
            }
            100% {
                left: -100%;
                opacity: 0;
            }
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Responsive design for floating text */
        @media (max-width: 768px) {
            #floatingText {
                font-size: 18px;
                padding: 12px 15px;
                margin-top: 20px;
            }

            #floatingText::before,
            #floatingText::after {
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            #floatingText {
                font-size: 16px;
                padding: 10px 12px;
                border-radius: 25px;
            }

            #floatingText::before,
            #floatingText::after {
                font-size: 16px;
            }
        }

        /* FAQ Section Styles */
        .faq-section {
            width: 100%;
            max-width: 800px;
            margin: 40px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .faq-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .faq-header h3 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .faq-subtitle {
            margin: 0;
            opacity: 0.9;
            font-size: 1rem;
        }

        .faq-item {
            border-bottom: 1px solid #e2e8f0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-toggle {
            width: 100%;
            background: white;
            color: #2d3748;
            font-size: 16px;
            font-weight: 600;
            padding: 20px 30px;
            border: none;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .faq-toggle:hover {
            background: #f7fafc;
            color: #667eea;
        }

        .faq-toggle.active {
            background: #edf2f7;
            color: #667eea;
        }

        .faq-question {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .faq-icon {
            color: #667eea;
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .faq-toggle.active .faq-icon {
            transform: rotate(45deg);
        }

        .faq-content {
            padding: 0 30px 30px 30px;
            background: #f8fafc;
            display: none;
            animation: fadeIn 0.3s ease;
            border-top: 1px solid #e2e8f0;
        }

        .faq-content.show {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .faq-content p {
            margin: 15px 0;
            line-height: 1.6;
            color: #4a5568;
        }

        .faq-content ul,
        .faq-content ol {
            margin: 15px 0;
            padding-left: 20px;
        }

        .faq-content li {
            margin: 8px 0;
            color: #4a5568;
            line-height: 1.5;
        }

        .payment-steps {
            display: grid;
            gap: 15px;
            margin: 20px 0;
        }

        .step {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .step strong {
            color: #2d3748;
            display: block;
            margin-bottom: 5px;
        }

        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .doc-category {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .doc-category h4 {
            margin: 0 0 15px 0;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .timeline {
            margin: 20px 0;
        }

        .timeline-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #48bb78;
            position: relative;
        }

        .timeline-item strong {
            color: #2d3748;
            display: block;
            margin-bottom: 5px;
        }

        .faq-footer {
            background: #f7fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .faq-footer p {
            margin: 0 0 20px 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
        }

        .contact-options {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .contact-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
        }

        /* FAQ Responsive Design */
        @media (max-width: 768px) {
            .faq-section {
                margin: 20px 10px;
                border-radius: 15px;
            }

            .faq-header {
                padding: 20px;
            }

            .faq-header h3 {
                font-size: 1.5rem;
            }

            .faq-toggle {
                padding: 15px 20px;
                font-size: 15px;
            }

            .faq-content {
                padding: 0 20px 20px 20px;
            }

            .document-grid {
                grid-template-columns: 1fr;
            }

            .contact-options {
                flex-direction: column;
                align-items: center;
            }

            .contact-btn {
                width: 200px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .faq-toggle {
                padding: 12px 15px;
                font-size: 14px;
            }

            .faq-content {
                padding: 0 15px 15px 15px;
            }

            .faq-header {
                padding: 15px;
            }

            .faq-header h3 {
                font-size: 1.3rem;
            }
        }

        .welcome-container {
            background: white;
            padding: 3.5rem 3rem 4rem 3rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 800px;
            width: 100%;
            position: relative;
            overflow: hidden;
            margin-bottom: 3rem;
        }
        .welcome-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
        }
        .logo {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .welcome-title {
            color: #111827;
            margin-bottom: 1rem;
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }
        .subtitle {
            color: #6B7280;
            margin-bottom: 2rem;
            font-size: 1.25rem;
            font-weight: 400;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
            padding: 0 1rem;
        }
        .action-btn {
            padding: 1.5rem 2rem;
            text-decoration: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1.15rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            color: white;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border: none;
            cursor: pointer;
            min-height: 65px;
        }
        .action-btn i {
            font-size: 1.4rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        .action-btn::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.12), transparent);
            transform: rotate(45deg);
            transition: all 0.6s ease;
            opacity: 0;
        }
        .action-btn:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }
        .action-btn:hover {
            transform: translateY(-4px) scale(1.03);
            text-decoration: none;
            color: white;
        }

        /* User Login - Electric Blue Theme */
        .btn-user-login {
            background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 100%);
            box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .btn-user-login:hover {
            box-shadow: 0 12px 30px rgba(30, 64, 175, 0.4);
            background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 100%);
        }

        /* Track Status - Emerald Green Theme */
        .btn-track-status {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.3);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .btn-track-status:hover {
            box-shadow: 0 12px 30px rgba(5, 150, 105, 0.4);
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
        }

        /* Register Now - Green Gradient Theme */
        .btn-register-now {
            background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .btn-register-now:hover {
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
        }

        /* Admin Panel - Fiery Red Theme */
        .btn-admin-panel {
            background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .btn-admin-panel:hover {
            box-shadow: 0 12px 30px rgba(220, 38, 38, 0.4);
            background: linear-gradient(135deg, #B91C1C 0%, #DC2626 100%);
        }

        /* Mobile Responsiveness for Action Buttons */
        @media (max-width: 768px) {
            .action-buttons {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
                padding: 0 0.5rem;
            }
            .action-btn {
                padding: 1.3rem 1.8rem;
                font-size: 1.1rem;
                border-radius: 14px;
                min-height: 60px;
            }
            .action-btn i {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .action-buttons {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .action-btn {
                padding: 1.2rem 1.6rem;
                font-size: 1rem;
                min-height: 55px;
            }
            .action-btn i {
                font-size: 1.2rem;
            }
        }


        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0 3rem 0;
        }
        .feature-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: left;
            border-left: 4px solid #667eea;
        }
        .feature-card h4 {
            color: #333;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .feature-card p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0 3rem 0;
        }
        .stat-item {
            text-align: center;
            padding: 1.5rem;
            color: white;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        .stat-item.service:hover {
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.5);
        }
        .stat-item.digital:hover {
            box-shadow: 0 8px 25px rgba(78, 205, 196, 0.5);
        }
        .stat-item.payments:hover {
            box-shadow: 0 8px 25px rgba(168, 230, 207, 0.5);
        }
        .stat-item.service {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }
        .stat-item.digital {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            box-shadow: 0 4px 15px rgba(78, 205, 196, 0.3);
        }
        .stat-item.payments {
            background: linear-gradient(135deg, #a8e6cf 0%, #88d8a3 100%);
            box-shadow: 0 4px 15px rgba(168, 230, 207, 0.3);
        }
        .stat-item .stat-icon {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            display: block;
            opacity: 0.9;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        /* Announcements Section */
        .announcements-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin: 3rem 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .announcements-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .announcements-header h2 {
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toggle-announcements {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-announcements:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        .announcements-container {
            max-height: 400px;
            overflow: hidden;
            transition: max-height 0.5s ease;
        }

        .announcements-container.collapsed {
            max-height: 0;
        }

        .announcements-scroll {
            max-height: 400px;
            overflow-y: auto;
            padding: 0;
        }

        .announcement-item {
            display: flex;
            align-items: flex-start;
            padding: 20px 30px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            position: relative;
        }

        .announcement-item:hover {
            background: #f8f9ff;
            transform: translateX(5px);
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .announcement-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .announcement-item.urgent .announcement-icon {
            background: #fee;
            color: #dc3545;
        }

        .announcement-item.new .announcement-icon {
            background: #fff3cd;
            color: #856404;
        }

        .announcement-item.info .announcement-icon {
            background: #d1ecf1;
            color: #0c5460;
        }

        .announcement-item.success .announcement-icon {
            background: #d4edda;
            color: #155724;
        }

        .announcement-item.warning .announcement-icon {
            background: #fff3cd;
            color: #856404;
        }

        .announcement-content {
            flex: 1;
        }

        .announcement-content h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .announcement-content p {
            margin: 0 0 10px 0;
            color: #666;
            line-height: 1.5;
        }

        .announcement-date {
            font-size: 0.85rem;
            color: #999;
            font-style: italic;
        }

        /* Scrollbar Styling */
        .announcements-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .announcements-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .announcements-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .announcements-scroll::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Animation for new announcements */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .announcement-item.new {
            animation: slideIn 0.5s ease;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .announcements-header {
                padding: 15px 20px;
            }

            .announcements-header h2 {
                font-size: 1.2rem;
            }

            .announcement-item {
                padding: 15px 20px;
                flex-direction: column;
                text-align: center;
            }

            .announcement-icon {
                margin: 0 auto 15px auto;
            }

            .announcements-container {
                max-height: 300px;
            }

            .announcements-scroll {
                max-height: 300px;
            }
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

        /* Footer Mobile Responsiveness */
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

        @media (max-width: 480px) {
            .footer {
                padding: 30px 0 0 0;
            }

            .footer-container {
                padding: 0 15px;
            }

            .footer-content {
                gap: 25px;
            }

            .footer-section h3 {
                font-size: 1.1rem;
            }

            .footer-logo {
                font-size: 1.1rem;
            }

            .footer-logo i {
                font-size: 1.3rem;
            }
        }

        /* Testimonials and Statistics Section */
        .testimonials-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 25px;
            margin-top: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .testimonials-section h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 2.2rem;
            font-weight: 700;
        }

        .testimonials-section h2 i {
            color: #667eea;
            margin-right: 10px;
        }

        /* Statistics Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            color: #FFFFFF;
            padding: 30px 20px;
            border-radius: 24px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        /* Unique modern gradient backgrounds for each stat card */
        .stat-card:nth-child(1) {
            background: linear-gradient(135deg, #3B82F6 0%, #06B6D4 100%);
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.3);
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.3);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #F97316 0%, #FDE047 100%);
            box-shadow: 0 15px 35px rgba(249, 115, 22, 0.3);
        }

        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #10B981 0%, #14B8A6 100%);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.3);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.15), transparent);
            transform: rotate(45deg);
            transition: all 0.6s ease;
            opacity: 0;
        }

        .stat-card:hover::before {
            opacity: 1;
            transform: rotate(45deg) translate(50%, 50%);
        }

        .stat-card:hover {
            /* Individual hover effects defined below */
        }

        /* Subtle enhanced hover effects for each card */
        .stat-card:nth-child(1):hover {
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.4);
            transform: translateY(-6px) scale(1.01);
        }

        .stat-card:nth-child(2):hover {
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.4);
            transform: translateY(-6px) scale(1.01);
        }

        .stat-card:nth-child(3):hover {
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.4);
            transform: translateY(-6px) scale(1.01);
        }

        .stat-card:nth-child(4):hover {
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.4);
            transform: translateY(-6px) scale(1.01);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.95;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.3));
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 900;
            margin-bottom: 8px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.95;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 0.5px;
        }

        /* Testimonials */
        .testimonials-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
            position: relative;
        }

        .testimonial-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        .testimonial-content {
            margin-bottom: 15px;
            position: relative;
        }

        .quote-icon {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 10px;
            opacity: 0.7;
        }

        .testimonial-content p {
            color: #555;
            line-height: 1.6;
            font-style: italic;
            margin: 0;
            font-size: 1rem;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .author-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }

        .author-role {
            color: #666;
            font-size: 0.9rem;
        }

        /* System Impact */
        .impact-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-top: 10px;
        }

        .impact-section h3 {
            text-align: center;
            color: #333;
            margin-bottom: 15px;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .impact-section h3 i {
            color: #28a745;
            margin-right: 10px;
        }

        .impact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px;
        }

        .impact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .impact-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }

        .impact-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .impact-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .impact-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Mobile Responsiveness for Testimonials */
        @media (max-width: 768px) {
            .testimonials-section {
                padding: 25px;
                margin-top: 20px;
            }

            .testimonials-section h2 {
                font-size: 1.8rem;
            }

            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }

            .stat-card {
                padding: 20px 15px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .testimonials-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .impact-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .impact-item {
                padding: 15px;
            }

            .impact-section {
                padding: 20px;
            }

            .impact-section h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Title Section -->
    <div class="title-section">
        <div class="logo"><i class="fas fa-bus"></i></div>
        <h1 class="welcome-title">Nrupatunga Smart Bus Pass Portal</h1>
        <p class="subtitle">A Smart Digital Platform for Effortless Bus Pass Applications, Renewals & Management</p>

        <!-- Floating Banner -->
        <div id="floatingText">India's Best Digital Bus Pass System</div>
    </div>

    <div class="main-content">
        <div class="welcome-container">

        <div class="stats-row">
            <div class="stat-item service">
                <i class="fas fa-clock stat-icon"></i>
                <span class="stat-number">24/7</span>
                <span class="stat-label">Service</span>
            </div>
            <div class="stat-item digital">
                <i class="fas fa-laptop stat-icon"></i>
                <span class="stat-number">100%</span>
                <span class="stat-label">Digital</span>
            </div>
            <div class="stat-item payments">
                <i class="fas fa-shield-alt stat-icon"></i>
                <span class="stat-number">Secure</span>
                <span class="stat-label">Payments</span>
            </div>
        </div>

        <div class="action-buttons">
            <a href="login.php" class="action-btn btn-user-login">
                <i class="fas fa-sign-in-alt"></i>
                User Login
            </a>
            <a href="track-application.php" class="action-btn btn-track-status">
                <i class="fas fa-search"></i>
                Track Status
            </a>
            <a href="register.php" class="action-btn btn-register-now">
                <i class="fas fa-user-plus"></i>
                Register Now
            </a>
            <a href="admin-login.php" class="action-btn btn-admin-panel">
                <i class="fas fa-cog"></i>
                Admin Panel
            </a>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <div class="faq-header">
                <h3><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
                <p class="faq-subtitle">Quick answers to common questions about our bus pass system</p>
            </div>

            <div class="faq-item">
                <button class="faq-toggle">
                    <span class="faq-question">
                        <i class="fas fa-plus faq-icon"></i>
                        How do I apply for a new bus pass?
                    </span>
                </button>
                <div class="faq-content">
                    <p><strong>Step-by-step process:</strong></p>
                    <ol>
                        <li>Click on "Apply Pass" button above</li>
                        <li>Register/Login to your account</li>
                        <li>Fill in your personal details and select route</li>
                        <li>Upload required documents (ID proof, photo)</li>
                        <li>Choose pass type and make payment</li>
                        <li>Wait for admin approval (usually 24-48 hours)</li>
                    </ol>
                    <p><em>💡 Tip: Keep your documents ready before starting the application.</em></p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-toggle">
                    <span class="faq-question">
                        <i class="fas fa-plus faq-icon"></i>
                        How can I renew my existing pass?
                    </span>
                </button>
                <div class="faq-content">
                    <p><strong>Renewal process:</strong></p>
                    <ul>
                        <li>Login to your user dashboard</li>
                        <li>Click on "Renew Pass" option</li>
                        <li>Select new validity period</li>
                        <li>Make payment for renewal</li>
                        <li>Your pass will be automatically extended</li>
                    </ul>
                    <p><em>🔄 Note: You can renew up to 30 days before expiry.</em></p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-toggle">
                    <span class="faq-question">
                        <i class="fas fa-plus faq-icon"></i>
                        Can I change my selected route after approval?
                    </span>
                </button>
                <div class="faq-content">
                    <p><strong>Route change policy:</strong></p>
                    <p>Yes, you can request a route change through your dashboard. However:</p>
                    <ul>
                        <li>Route changes require admin approval</li>
                        <li>Processing time: 2-3 business days</li>
                        <li>Additional charges may apply for premium routes</li>
                        <li>Only one route change allowed per month</li>
                    </ul>
                    <p><em>📍 Make sure to select the correct route during initial application.</em></p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-toggle">
                    <span class="faq-question">
                        <i class="fas fa-plus faq-icon"></i>
                        What should I do if my payment fails?
                    </span>
                </button>
                <div class="faq-content">
                    <p><strong>Payment troubleshooting:</strong></p>
                    <div class="payment-steps">
                        <div class="step">
                            <strong>1. Wait and Retry:</strong>
                            <p>Wait 5-10 minutes and try the payment again</p>
                        </div>
                        <div class="step">
                            <strong>2. Check Bank Account:</strong>
                            <p>If money is debited but pass not updated, don't panic</p>
                        </div>
                        <div class="step">
                            <strong>3. Contact Support:</strong>
                            <p>Email us with transaction ID and screenshot</p>
                        </div>
                        <div class="step">
                            <strong>4. Refund Policy:</strong>
                            <p>Failed payments are automatically refunded within 3-5 business days</p>
                        </div>
                    </div>
                    <p><em>💳 Supported: Credit/Debit Cards, UPI, Net Banking, Wallets</em></p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-toggle">
                    <span class="faq-question">
                        <i class="fas fa-plus faq-icon"></i>
                        Is account registration required to apply?
                    </span>
                </button>
                <div class="faq-content">
                    <p><strong>Account requirement:</strong></p>
                    <p>Yes, creating an account is mandatory for:</p>
                    <ul>
                        <li><strong>Security:</strong> Protect your personal information</li>
                        <li><strong>Tracking:</strong> Monitor application status</li>
                        <li><strong>Management:</strong> Renew, modify, or cancel passes</li>
                        <li><strong>History:</strong> View past transactions and passes</li>
                        <li><strong>Support:</strong> Get personalized assistance</li>
                    </ul>
                    <p><em>🔐 Registration is free and takes less than 2 minutes!</em></p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-toggle">
                    <span class="faq-question">
                        <i class="fas fa-plus faq-icon"></i>
                        What documents do I need for application?
                    </span>
                </button>
                <div class="faq-content">
                    <p><strong>Required documents:</strong></p>
                    <div class="document-grid">
                        <div class="doc-category">
                            <h4>📄 Identity Proof (any one):</h4>
                            <ul>
                                <li>Aadhaar Card</li>
                                <li>PAN Card</li>
                                <li>Voter ID</li>
                                <li>Driving License</li>
                            </ul>
                        </div>
                        <div class="doc-category">
                            <h4>📸 Additional Requirements:</h4>
                            <ul>
                                <li>Recent passport-size photo</li>
                                <li>Address proof (if different from ID)</li>
                                <li>Student ID (for student passes)</li>
                                <li>Senior citizen proof (for senior passes)</li>
                            </ul>
                        </div>
                    </div>
                    <p><em>📱 All documents can be uploaded as JPG, PNG, or PDF files (max 2MB each).</em></p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-toggle">
                    <span class="faq-question">
                        <i class="fas fa-plus faq-icon"></i>
                        How long does approval take?
                    </span>
                </button>
                <div class="faq-content">
                    <p><strong>Processing timeline:</strong></p>
                    <div class="timeline">
                        <div class="timeline-item">
                            <strong>Application Submitted:</strong> Instant confirmation
                        </div>
                        <div class="timeline-item">
                            <strong>Document Verification:</strong> 6-12 hours
                        </div>
                        <div class="timeline-item">
                            <strong>Admin Review:</strong> 12-24 hours
                        </div>
                        <div class="timeline-item">
                            <strong>Final Approval:</strong> 24-48 hours total
                        </div>
                    </div>
                    <p><em>⚡ Express processing available for urgent cases (additional charges apply).</em></p>
                </div>
            </div>

            <div class="faq-footer">
                <p><strong>Still have questions?</strong></p>
                <div class="contact-options">
                    <a href="mailto:support@buspass.com" class="contact-btn">
                        <i class="fas fa-envelope"></i> Email Support
                    </a>
                    <a href="tel:+91-1234567890" class="contact-btn">
                        <i class="fas fa-phone"></i> Call Us
                    </a>
                    <a href="#" class="contact-btn">
                        <i class="fas fa-comments"></i> Live Chat
                    </a>
                </div>
            </div>
        </div>

        <!-- Important Announcements Section -->
        <div class="announcements-section">
            <div class="announcements-header">
                <h2><i class="fas fa-bullhorn"></i> Important Announcements</h2>
                <button class="toggle-announcements" onclick="toggleAnnouncements()">
                    <i class="fas fa-chevron-up" id="toggle-icon"></i>
                </button>
            </div>
            <div class="announcements-container" id="announcements-container">
                <div class="announcements-scroll">
                    <?php echo renderAnnouncements($announcements); ?>
                </div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <h4><i class="fas fa-shield-alt"></i> Secure Platform</h4>
                <p>Advanced security with encrypted data and secure payment processing</p>
            </div>
            <div class="feature-card">
                <h4><i class="fas fa-mobile-alt"></i> Mobile Friendly</h4>
                <p>Responsive design that works perfectly on all devices</p>
            </div>
            <div class="feature-card">
                <h4><i class="fas fa-clock"></i> Real-time Updates</h4>
                <p>Track your application status and get instant notifications</p>
            </div>
            <div class="feature-card">
                <h4><i class="fas fa-credit-card"></i> Multiple Payment Options</h4>
                <p>Pay securely with Stripe, Razorpay, or other payment methods</p>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            <strong><i class="fas fa-info-circle"></i> Demo Credentials:</strong><br>
            Admin Email: admin@buspass.com | Password: admin123<br><br>
            <a href="payment_demo.php" style="color: #007bff; text-decoration: none; margin-right: 15px;">
                <i class="fas fa-credit-card"></i> Payment Demo
            </a>
            <a href="test_redirections.php" style="color: #007bff; text-decoration: none; margin-right: 15px;">
                <i class="fas fa-route"></i> Test Redirections
            </a>
            <a href="configure_xampp_email.php" style="color: #007bff; text-decoration: none; margin-right: 15px;">
                <i class="fas fa-envelope-open"></i> Email Setup
            </a>
            <a href="test_razorpay.php" style="color: #007bff; text-decoration: none; margin-right: 15px;">
                <i class="fas fa-credit-card"></i> Razorpay Test
            </a>
            <a href="debug_razorpay.php" style="color: #dc3545; text-decoration: none;">
                <i class="fas fa-bug"></i> Debug Razorpay
            </a>
        </div>

            <!-- What Users Say Section -->
            <div class="testimonials-section">
                <h2><i class="fas fa-users"></i> What Users Say</h2>

                <!-- Statistics Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="stat-number">10,000+</div>
                        <div class="stat-label">Passes Issued</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Institutions Served</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-number"><?php echo $reviewStats['total_reviews'] > 0 ? number_format($reviewStats['average_rating'], 1) : '4.8'; ?>/5</div>
                        <div class="stat-label">User Rating</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Support Available</div>
                    </div>
                </div>

                <!-- User Reviews -->
                <div class="testimonials-container">
                    <?php if ($reviewsResult && $reviewsResult->num_rows > 0): ?>
                        <?php while ($review = $reviewsResult->fetch_assoc()): ?>
                        <div class="testimonial-card">
                            <div class="testimonial-content">
                                <i class="fas fa-quote-left quote-icon"></i>
                                <div class="review-rating" style="margin-bottom: 10px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? '#ffc107' : '#ddd'; ?>; font-size: 0.9rem;"></i>
                                    <?php endfor; ?>
                                    <span style="margin-left: 8px; font-size: 0.9rem; color: #666;">(<?php echo $review['rating']; ?>/5)</span>
                                </div>
                                <p>"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                            </div>
                            <div class="testimonial-author">
                                <div class="author-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="author-info">
                                    <div class="author-name"><?php echo htmlspecialchars($review['username']); ?></div>
                                    <div class="author-role">Verified User • <?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Default testimonials when no reviews exist -->
                        <div class="testimonial-card">
                            <div class="testimonial-content">
                                <i class="fas fa-quote-left quote-icon"></i>
                                <div class="review-rating" style="margin-bottom: 10px;">
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <span style="margin-left: 8px; font-size: 0.9rem; color: #666;">(5/5)</span>
                                </div>
                                <p>"The digital bus pass system has revolutionized our daily commute. Quick, easy, and hassle-free!"</p>
                            </div>
                            <div class="testimonial-author">
                                <div class="author-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="author-info">
                                    <div class="author-name">Priya Sharma</div>
                                    <div class="author-role">College Student</div>
                                </div>
                            </div>
                        </div>

                        <div class="testimonial-card">
                            <div class="testimonial-content">
                                <i class="fas fa-quote-left quote-icon"></i>
                                <div class="review-rating" style="margin-bottom: 10px;">
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ddd; font-size: 0.9rem;"></i>
                                    <span style="margin-left: 8px; font-size: 0.9rem; color: #666;">(4/5)</span>
                                </div>
                                <p>"Excellent service! The application process is smooth and the support team is very responsive."</p>
                            </div>
                            <div class="testimonial-author">
                                <div class="author-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="author-info">
                                    <div class="author-name">Rajesh Kumar</div>
                                    <div class="author-role">Working Professional</div>
                                </div>
                            </div>
                        </div>

                        <div class="testimonial-card">
                            <div class="testimonial-content">
                                <i class="fas fa-quote-left quote-icon"></i>
                                <div class="review-rating" style="margin-bottom: 10px;">
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 0.9rem;"></i>
                                    <span style="margin-left: 8px; font-size: 0.9rem; color: #666;">(5/5)</span>
                                </div>
                                <p>"As an admin, managing bus passes has never been easier. The system is intuitive and efficient."</p>
                            </div>
                            <div class="testimonial-author">
                                <div class="author-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="author-info">
                                    <div class="author-name">Dr. Anita Reddy</div>
                                    <div class="author-role">Institution Administrator</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- View All Reviews Link -->
                <?php if ($reviewsResult && $reviewsResult->num_rows > 0): ?>
                <div style="text-align: center; margin: 15px 0;">
                    <a href="instant-reviews-display.php" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
                        <i class="fas fa-comments"></i>
                        View All <?php echo $reviewStats['total_reviews']; ?> Instant Reviews
                    </a>
                </div>
                <?php endif; ?>

                <!-- System Impact -->
                <div class="impact-section">
                    <h3><i class="fas fa-chart-line"></i> System Impact</h3>
                    <div class="impact-grid">
                        <div class="impact-item">
                            <div class="impact-icon">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <div class="impact-content">
                                <div class="impact-title">80% Faster Processing</div>
                                <div class="impact-description">Reduced application processing time from days to hours</div>
                            </div>
                        </div>

                        <div class="impact-item">
                            <div class="impact-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <div class="impact-content">
                                <div class="impact-title">100% Paperless</div>
                                <div class="impact-description">Eliminated paper forms, contributing to environmental sustainability</div>
                            </div>
                        </div>

                        <div class="impact-item">
                            <div class="impact-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="impact-content">
                                <div class="impact-title">Mobile-First Design</div>
                                <div class="impact-description">95% of users access the system via mobile devices</div>
                            </div>
                        </div>

                        <div class="impact-item">
                            <div class="impact-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="impact-content">
                                <div class="impact-title">99.9% Uptime</div>
                                <div class="impact-description">Reliable service with minimal downtime and robust security</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-bus"></i>
                        <span>Nrupatunga Digital Bus Pass System</span>
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
                    <p>&copy; <?php echo date('Y'); ?> Nrupatunga Digital Bus Pass System. All rights reserved.</p>
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
        function toggleAnnouncements() {
            const container = document.getElementById('announcements-container');
            const icon = document.getElementById('toggle-icon');

            if (container.classList.contains('collapsed')) {
                container.classList.remove('collapsed');
                icon.className = 'fas fa-chevron-up';
            } else {
                container.classList.add('collapsed');
                icon.className = 'fas fa-chevron-down';
            }
        }

        // Auto-scroll announcements every 5 seconds
        function autoScrollAnnouncements() {
            const scrollContainer = document.querySelector('.announcements-scroll');
            if (scrollContainer && !document.getElementById('announcements-container').classList.contains('collapsed')) {
                const scrollHeight = scrollContainer.scrollHeight;
                const clientHeight = scrollContainer.clientHeight;
                const currentScroll = scrollContainer.scrollTop;

                if (currentScroll + clientHeight >= scrollHeight) {
                    // Reset to top when reached bottom
                    scrollContainer.scrollTop = 0;
                } else {
                    // Scroll down by 100px
                    scrollContainer.scrollTop += 100;
                }
            }
        }

        // Start auto-scroll when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-scroll every 5 seconds
            setInterval(autoScrollAnnouncements, 5000);

            // Add click handler to header for toggle
            document.querySelector('.announcements-header').addEventListener('click', toggleAnnouncements);

            // Initialize floating text banner
            initializeFloatingText();
        });

        // Initialize floating text functionality
        function initializeFloatingText() {
            const floatingText = document.getElementById('floatingText');
            if (floatingText) {
                floatingText.setAttribute('data-text', floatingText.innerText);

                // Add hover effects
                floatingText.addEventListener('mouseenter', function() {
                    this.style.animationPlayState = 'paused';
                });

                floatingText.addEventListener('mouseleave', function() {
                    this.style.animationPlayState = 'running';
                });
            }
        }

        // FAQ Toggle Functionality
        function initializeFAQ() {
            const faqToggles = document.querySelectorAll(".faq-toggle");

            faqToggles.forEach(button => {
                button.addEventListener("click", function() {
                    const content = this.nextElementSibling;
                    const isVisible = content.classList.contains('show');

                    // Close all other open FAQs
                    document.querySelectorAll(".faq-content").forEach(el => {
                        el.classList.remove('show');
                        el.style.display = 'none';
                    });

                    // Remove active class from all buttons
                    document.querySelectorAll(".faq-toggle").forEach(btn => {
                        btn.classList.remove('active');
                    });

                    // Toggle current FAQ
                    if (!isVisible) {
                        content.style.display = 'block';
                        setTimeout(() => {
                            content.classList.add('show');
                        }, 10);
                        this.classList.add('active');
                    }
                });
            });
        }

        // Initialize FAQ on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-scroll every 5 seconds
            setInterval(autoScrollAnnouncements, 5000);

            // Add click handler to header for toggle
            document.querySelector('.announcements-header').addEventListener('click', toggleAnnouncements);

            // Initialize floating text banner
            initializeFloatingText();

            // Initialize FAQ functionality
            initializeFAQ();
        });

        // Pause auto-scroll when user hovers over announcements
        document.addEventListener('DOMContentLoaded', function() {
            const announcementsSection = document.querySelector('.announcements-section');
            let autoScrollInterval;

            function startAutoScroll() {
                autoScrollInterval = setInterval(autoScrollAnnouncements, 5000);
            }

            function stopAutoScroll() {
                clearInterval(autoScrollInterval);
            }

            announcementsSection.addEventListener('mouseenter', stopAutoScroll);
            announcementsSection.addEventListener('mouseleave', startAutoScroll);

            // Start auto-scroll initially
            startAutoScroll();
        });
    </script>
</body>
</html>
