<?php
/**
 * MANUAL PAYMENT REDIRECT
 * Simple page to manually redirect to payment after form submission
 */

session_start();
include('includes/dbconnection.php');

// Get application ID from URL or session
$applicationId = $_GET['application_id'] ?? $_SESSION['last_application_id'] ?? 1;

echo "<!DOCTYPE html>";
echo "<html><head><title>🔄 Redirecting to Payment</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); text-align: center; }
    .container { background: white; padding: 50px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    .success { color: #28a745; font-size: 1.5em; margin: 20px 0; }
    .btn { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; margin: 10px; font-size: 1.1em; font-weight: bold; }
    .btn:hover { background: #218838; color: white; text-decoration: none; transform: translateY(-2px); }
    .btn-primary { background: #007bff; } .btn-primary:hover { background: #0056b3; }
    h1 { color: #333; margin-bottom: 30px; }
    .loading { font-size: 2em; margin: 20px 0; }
</style>";

// Auto-redirect script
echo "<script>";
echo "let countdown = 3;";
echo "function updateCountdown() {";
echo "  document.getElementById('countdown').textContent = countdown;";
echo "  if (countdown <= 0) {";
echo "    window.location.href = 'payment.php?application_id=$applicationId';";
echo "  } else {";
echo "    countdown--;";
echo "    setTimeout(updateCountdown, 1000);";
echo "  }";
echo "}";
echo "window.onload = function() { updateCountdown(); };";
echo "</script>";

echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🎉 Application Submitted Successfully!</h1>";

echo "<div class='success'>";
echo "✅ Your bus pass application has been submitted successfully!<br>";
echo "📋 Application ID: <strong>$applicationId</strong>";
echo "</div>";

echo "<div class='loading'>🔄</div>";

echo "<p><strong>Redirecting to payment page in <span id='countdown'>3</span> seconds...</strong></p>";

echo "<div style='margin: 30px 0;'>";
echo "<a href='payment.php?application_id=$applicationId' class='btn'>💳 Go to Payment Page Now</a>";
echo "<a href='user-dashboard.php' class='btn btn-primary'>🏠 Go to Dashboard</a>";
echo "</div>";

echo "<div style='margin-top: 30px; font-size: 0.9em; color: #666;'>";
echo "<p>If you're not redirected automatically, please click the 'Go to Payment Page Now' button above.</p>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
