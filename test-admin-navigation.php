<?php
session_start();
include('includes/dbconnection.php');

// Simple test page to verify admin navigation
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Admin Navigation - Nrupatunga Digital Bus Pass System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f8f9fa; }
        
        .header {
            background: #dc3545;
            color: white;
            padding: 15px 20px;
            position: relative;
            z-index: 100;
            overflow: hidden;
        }
        .header h2 {
            margin: 0;
            display: inline-block;
            float: left;
        }
        .logout {
            float: right;
            margin-top: 5px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .logout a, .logout button {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .logout a:hover, .logout button:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
            text-decoration: none;
        }
        .header::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .container { padding: 40px; max-width: 1200px; margin: 0 auto; }
        .test-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin: 20px 0; }
        .test-result { padding: 15px; border-radius: 8px; margin: 10px 0; }
        .test-success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .test-info { background: #e7f3ff; color: #004085; border: 2px solid #b3d7ff; }
        .nav-test { display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0; }
        .nav-test a, .nav-test button { 
            background: #007bff; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            text-decoration: none; 
            cursor: pointer; 
            transition: all 0.3s ease;
        }
        .nav-test a:hover, .nav-test button:hover { 
            background: #0056b3; 
            transform: translateY(-2px); 
            text-decoration: none; 
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Admin Navigation Test</h2>
        <div class="logout">
            <button onclick="testFunction()" type="button">
                <i class="fas fa-chart-bar"></i> Test Button
            </button>
            <a href="admin-activity-log.php">
                <i class="fas fa-history"></i> Activity Log
            </a>
            <a href="index.php">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="manage-categories.php">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="manage-announcements.php">
                <i class="fas fa-bullhorn"></i> Announcements
            </a>
            <a href="manage-reviews.php">
                <i class="fas fa-star"></i> Reviews
            </a>
            <a href="admin-dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="admin-logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="container">
        <div class="test-section">
            <h3><i class="fas fa-vial"></i> Admin Navigation Test Page</h3>
            <p>This page tests the admin navigation functionality to ensure all buttons and links work properly.</p>
            
            <div class="test-result test-success">
                <h4><i class="fas fa-check-circle"></i> Navigation Status: Working</h4>
                <p>If you can see this page and the navigation buttons above are clickable, the navigation is working correctly.</p>
            </div>
            
            <div class="test-result test-info">
                <h4><i class="fas fa-info-circle"></i> Test Instructions</h4>
                <ol>
                    <li><strong>Header Navigation</strong>: Click each button/link in the header to test navigation</li>
                    <li><strong>Button Responsiveness</strong>: Hover over buttons to see hover effects</li>
                    <li><strong>JavaScript Function</strong>: Click "Test Button" to test JavaScript functionality</li>
                    <li><strong>Link Navigation</strong>: Click other links to navigate to different pages</li>
                </ol>
            </div>
            
            <h4>Additional Navigation Tests</h4>
            <div class="nav-test">
                <a href="admin-dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Back to Admin Dashboard
                </a>
                <a href="user-dashboard.php">
                    <i class="fas fa-user"></i> User Dashboard
                </a>
                <a href="index.php">
                    <i class="fas fa-home"></i> Homepage
                </a>
                <button onclick="showAlert()">
                    <i class="fas fa-bell"></i> Test Alert
                </button>
                <button onclick="window.location.reload()">
                    <i class="fas fa-sync"></i> Reload Page
                </button>
            </div>
            
            <div id="test-output" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; display: none;">
                <h5>Test Output:</h5>
                <p id="test-message"></p>
            </div>
        </div>
        
        <div class="test-section">
            <h4><i class="fas fa-tools"></i> Troubleshooting</h4>
            <p><strong>If navigation buttons are not working:</strong></p>
            <ul>
                <li>Check browser console for JavaScript errors (F12 → Console)</li>
                <li>Ensure all CSS and JavaScript files are loading properly</li>
                <li>Try refreshing the page (Ctrl+F5 for hard refresh)</li>
                <li>Test in a different browser</li>
                <li>Clear browser cache and cookies</li>
            </ul>
            
            <p><strong>Common Issues:</strong></p>
            <ul>
                <li><strong>Buttons not clickable</strong>: CSS pointer-events or z-index issues</li>
                <li><strong>Links not working</strong>: JavaScript preventing default behavior</li>
                <li><strong>Hover effects not working</strong>: CSS transition or hover state issues</li>
                <li><strong>Layout broken</strong>: CSS flexbox or float issues</li>
            </ul>
        </div>
    </div>

    <script>
        function testFunction() {
            showTestOutput('Test Button clicked successfully! JavaScript is working.');
        }
        
        function showAlert() {
            alert('Alert function working! Navigation JavaScript is functional.');
            showTestOutput('Alert function executed successfully.');
        }
        
        function showTestOutput(message) {
            const output = document.getElementById('test-output');
            const messageEl = document.getElementById('test-message');
            messageEl.textContent = message + ' (Time: ' + new Date().toLocaleTimeString() + ')';
            output.style.display = 'block';
        }
        
        // Test navigation on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin Navigation Test Page loaded successfully');
            
            // Test all navigation links
            const navLinks = document.querySelectorAll('.logout a, .nav-test a');
            const navButtons = document.querySelectorAll('.logout button, .nav-test button');
            
            console.log('Found ' + navLinks.length + ' navigation links');
            console.log('Found ' + navButtons.length + ' navigation buttons');
            
            // Ensure all links are clickable
            navLinks.forEach((link, index) => {
                if (link.style.pointerEvents === 'none') {
                    console.warn('Link ' + index + ' has pointer-events disabled');
                    link.style.pointerEvents = 'auto';
                }
            });
            
            // Ensure all buttons are clickable
            navButtons.forEach((button, index) => {
                if (button.style.pointerEvents === 'none') {
                    console.warn('Button ' + index + ' has pointer-events disabled');
                    button.style.pointerEvents = 'auto';
                }
                button.style.cursor = 'pointer';
            });
            
            showTestOutput('Page loaded successfully. Found ' + navLinks.length + ' links and ' + navButtons.length + ' buttons.');
        });
    </script>
</body>
</html>
