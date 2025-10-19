<?php
/**
 * TEST REGISTER BUTTON
 * Quick test to verify register button functionality
 */

session_start();

echo "<!DOCTYPE html>";
echo "<html><head><title>🧪 Register Button Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f8f9fa; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; }
    .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 10px 5px; cursor: pointer; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    .btn-success { background: #28a745; } .btn-success:hover { background: #218838; }
    .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
    .btn-warning { background: #ffc107; color: #212529; } .btn-warning:hover { background: #e0a800; }
    h1 { color: #333; text-align: center; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🧪 Register Button Test</h1>";

// Check current session status
if (isset($_SESSION['uid'])) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Currently Logged In</h3>";
    echo "<p><strong>User ID:</strong> {$_SESSION['uid']}</p>";
    echo "<p><strong>Issue:</strong> When logged in, register button redirects to dashboard</p>";
    echo "<p><strong>Solution:</strong> Fixed to show proper message instead</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>🧪 Test Scenarios:</h4>";
    echo "<ol>";
    echo "<li><strong>While Logged In:</strong> Register page should show 'already logged in' message</li>";
    echo "<li><strong>After Logout:</strong> Register page should show registration form</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='register.php' class='btn btn-warning'>🧪 Test Register Page (Logged In)</a>";
    echo "<a href='logout.php' class='btn btn-danger'>🚪 Logout & Test</a>";
    echo "<a href='user-dashboard.php' class='btn btn-success'>🏠 Go to Dashboard</a>";
    echo "</div>";
    
} else {
    echo "<div class='success'>";
    echo "<h3>✅ Not Logged In</h3>";
    echo "<p><strong>Status:</strong> Perfect for testing registration</p>";
    echo "<p><strong>Expected:</strong> Register page should show registration form</p>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='register.php' class='btn btn-success'>📝 Test Register Page</a>";
    echo "<a href='login.php' class='btn'>🔑 Go to Login</a>";
    echo "<a href='index.php' class='btn'>🏠 Go to Homepage</a>";
    echo "</div>";
}

// Test the register button link from homepage
echo "<div class='info'>";
echo "<h4>🔗 Register Button Test</h4>";
echo "<p><strong>Homepage Register Button URL:</strong> <code>register.php</code></p>";
echo "<p><strong>Expected Behavior:</strong></p>";
echo "<ul>";
echo "<li>If <strong>NOT logged in</strong>: Show registration form</li>";
echo "<li>If <strong>logged in</strong>: Show 'already logged in' message with options</li>";
echo "</ul>";
echo "</div>";

// Show current session info
echo "<div class='info'>";
echo "<h4>📊 Current Session Status</h4>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . (isset($_SESSION['uid']) ? $_SESSION['uid'] : 'Not set') . "\n";
echo "Session Data: " . print_r($_SESSION, true);
echo "</pre>";
echo "</div>";

// Quick fix verification
echo "<div class='success'>";
echo "<h4>✅ Fix Applied</h4>";
echo "<p><strong>Problem:</strong> Register button was redirecting logged-in users to dashboard</p>";
echo "<p><strong>Solution:</strong> Modified register.php to show appropriate message instead of redirecting</p>";
echo "<p><strong>Result:</strong> Users can now see the register page regardless of login status</p>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='index.php' class='btn'>🏠 Back to Homepage</a>";
echo "</div>";

echo "</div></body></html>";
?>
