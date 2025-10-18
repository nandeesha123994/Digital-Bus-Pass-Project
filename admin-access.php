<?php
/**
 * Temporary Admin Access - For Testing Purposes
 * This creates a temporary admin session for testing the announcements feature
 */

session_start();

// Set admin session variables
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Access - Bus Pass Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; text-align: center; }
        h1 { color: #28a745; }
        .success { color: #28a745; font-weight: bold; margin: 20px 0; }
        .btn { padding: 12px 25px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>✅ Admin Access Granted</h1>
        <div class='success'>
            You now have temporary admin access for testing the announcements feature.
        </div>
        
        <p>You can now access:</p>
        
        <div style='margin: 30px 0;'>
            <a href='admin-dashboard.php' class='btn'>
                <i class='fas fa-tachometer-alt'></i> Admin Dashboard
            </a>
            <br>
            <a href='manage-announcements.php' class='btn btn-success'>
                <i class='fas fa-bullhorn'></i> Manage Announcements
            </a>
            <br>
            <a href='index.php' class='btn btn-warning'>
                <i class='fas fa-home'></i> View Home Page
            </a>
        </div>
        
        <hr>
        <p style='color: #666; font-size: 0.9rem;'>
            <strong>Note:</strong> This is a temporary session for testing purposes. 
            In production, proper authentication should be implemented.
        </p>
    </div>
</body>
</html>";
?>
