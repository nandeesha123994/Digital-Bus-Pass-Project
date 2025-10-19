<?php
/**
 * EMERGENCY REDIRECT FIX
 * This will fix the payment redirect issue immediately
 */

session_start();
include('includes/dbconnection.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>🚨 EMERGENCY REDIRECT FIX</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 10px 5px; cursor: pointer; }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
    .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
    .btn-success { background: #28a745; } .btn-success:hover { background: #218838; }
    h1 { color: #333; text-align: center; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🚨 EMERGENCY REDIRECT FIX</h1>";

if (isset($_POST['apply_fix'])) {
    echo "<div class='info'>🔧 Applying emergency redirect fix...</div>";
    
    // Read the current apply-pass.php file
    $applyPassContent = file_get_contents('apply-pass.php');
    
    if ($applyPassContent === false) {
        echo "<div class='error'>❌ Could not read apply-pass.php file</div>";
        exit();
    }
    
    // Create a backup
    $backupFile = 'apply-pass-backup-' . date('Y-m-d-H-i-s') . '.php';
    file_put_contents($backupFile, $applyPassContent);
    echo "<div class='success'>✅ Backup created: $backupFile</div>";
    
    // Find the redirect section and replace it with a more aggressive approach
    $oldRedirectCode = 'error_log(\'DEBUG: Redirecting to payment.php?application_id=\' . $applicationId);

                            // Clear any output buffers and redirect immediately
                            while (ob_get_level()) {
                                ob_end_clean();
                            }
                            
                            // Send redirect header
                            header("Location: payment.php?application_id=" . $applicationId);
                            exit();';
    
    $newRedirectCode = 'error_log(\'DEBUG: Redirecting to payment.php?application_id=\' . $applicationId);

                            // EMERGENCY REDIRECT FIX - Multiple methods to ensure redirect works
                            
                            // Method 1: Clear all output buffers
                            while (ob_get_level()) {
                                ob_end_clean();
                            }
                            
                            // Method 2: Stop any further output
                            ob_start();
                            
                            // Method 3: Send redirect header with exit
                            header("Location: payment.php?application_id=" . $applicationId, true, 302);
                            
                            // Method 4: JavaScript redirect as immediate backup
                            echo "<!DOCTYPE html><html><head>";
                            echo "<script>window.location.href=\'payment.php?application_id=" . $applicationId . "\';</script>";
                            echo "<meta http-equiv=\'refresh\' content=\'0;url=payment.php?application_id=" . $applicationId . "\'>";
                            echo "</head><body>";
                            echo "<div style=\'text-align:center;padding:50px;font-family:Arial;\'>";
                            echo "<h2>✅ Application Submitted Successfully!</h2>";
                            echo "<p>🔄 Redirecting to payment page...</p>";
                            echo "<p>If not redirected: <a href=\'payment.php?application_id=" . $applicationId . "\'>Click Here</a></p>";
                            echo "</div></body></html>";
                            
                            // Method 5: Force exit
                            exit();';
    
    // Replace the redirect code
    $newContent = str_replace($oldRedirectCode, $newRedirectCode, $applyPassContent);
    
    if ($newContent === $applyPassContent) {
        echo "<div class='warning'>⚠️ Redirect code not found in expected format. Trying alternative approach...</div>";
        
        // Alternative approach - find and replace the header redirect line
        $pattern = '/header\("Location: payment\.php\?application_id=" \. \$applicationId\);\s*exit\(\);/';
        $replacement = 'header("Location: payment.php?application_id=" . $applicationId, true, 302);
                            echo "<!DOCTYPE html><html><head>";
                            echo "<script>window.location.href=\'payment.php?application_id=" . $applicationId . "\';</script>";
                            echo "<meta http-equiv=\'refresh\' content=\'0;url=payment.php?application_id=" . $applicationId . "\'>";
                            echo "</head><body>";
                            echo "<div style=\'text-align:center;padding:50px;font-family:Arial;\'>";
                            echo "<h2>✅ Application Submitted Successfully!</h2>";
                            echo "<p>🔄 Redirecting to payment page...</p>";
                            echo "<p>If not redirected: <a href=\'payment.php?application_id=" . $applicationId . "\'>Click Here</a></p>";
                            echo "</div></body></html>";
                            exit();';
        
        $newContent = preg_replace($pattern, $replacement, $applyPassContent);
    }
    
    if ($newContent !== $applyPassContent) {
        // Write the new content
        if (file_put_contents('apply-pass.php', $newContent)) {
            echo "<div class='success'>✅ Emergency redirect fix applied successfully!</div>";
            echo "<div class='info'>";
            echo "<h3>🔧 Fix Applied:</h3>";
            echo "<ul>";
            echo "<li>✅ Multiple redirect methods implemented</li>";
            echo "<li>✅ JavaScript backup redirect added</li>";
            echo "<li>✅ Meta refresh fallback included</li>";
            echo "<li>✅ Manual link provided as final backup</li>";
            echo "<li>✅ Aggressive output buffer clearing</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='error'>❌ Failed to write the fixed file</div>";
        }
    } else {
        echo "<div class='error'>❌ Could not locate redirect code to fix</div>";
    }
    
    echo "<div class='success'>";
    echo "<h3>🧪 Test the Fix Now:</h3>";
    echo "<p>Go to the application form and submit it. It should now redirect to payment page.</p>";
    echo "<a href='apply-pass.php' class='btn btn-success'>📝 Test Application Form</a>";
    echo "</div>";
    
} else {
    echo "<div class='error'>";
    echo "<h2>🚨 CRITICAL ISSUE: Form Not Redirecting to Payment</h2>";
    echo "<p><strong>Problem:</strong> Application form is not redirecting to payment page after submission.</p>";
    echo "<p><strong>Solution:</strong> This emergency fix will implement multiple redirect methods to ensure it works.</p>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<h3>⚠️ What This Fix Will Do:</h3>";
    echo "<ul>";
    echo "<li>🔧 Add multiple redirect methods (PHP header, JavaScript, Meta refresh)</li>";
    echo "<li>🔧 Implement aggressive output buffer clearing</li>";
    echo "<li>🔧 Provide manual link as final fallback</li>";
    echo "<li>🔧 Create backup of current file before changes</li>";
    echo "<li>🔧 Ensure redirect works in all browsers</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<form method='post'>";
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<button type='submit' name='apply_fix' class='btn btn-danger'>🚨 APPLY EMERGENCY FIX NOW</button>";
    echo "</div>";
    echo "</form>";
    
    echo "<div class='info'>";
    echo "<h4>📋 Current Status Check:</h4>";
    
    // Check if apply-pass.php exists
    if (file_exists('apply-pass.php')) {
        echo "<div class='success'>✅ apply-pass.php file exists</div>";
        
        // Check current redirect code
        $content = file_get_contents('apply-pass.php');
        if (strpos($content, 'header("Location: payment.php') !== false) {
            echo "<div class='success'>✅ Redirect code found in file</div>";
        } else {
            echo "<div class='error'>❌ Redirect code not found</div>";
        }
        
        if (strpos($content, 'ob_end_clean') !== false) {
            echo "<div class='success'>✅ Output buffer clearing code present</div>";
        } else {
            echo "<div class='warning'>⚠️ Output buffer clearing may be missing</div>";
        }
    } else {
        echo "<div class='error'>❌ apply-pass.php file not found</div>";
    }
    
    // Check payment.php
    if (file_exists('payment.php')) {
        echo "<div class='success'>✅ payment.php file exists</div>";
    } else {
        echo "<div class='error'>❌ payment.php file missing</div>";
    }
    
    echo "</div>";
}

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='apply-pass.php' class='btn'>📝 Go to Application Form</a>";
echo "<a href='payment.php?application_id=1' class='btn'>💳 Test Payment Page</a>";
echo "<a href='index.php' class='btn'>🏠 Back to Homepage</a>";
echo "</div>";

echo "</div></body></html>";
?>
