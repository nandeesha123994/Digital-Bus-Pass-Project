<?php
// Simple TCPDF installation checker and downloader
echo "<h2>📄 TCPDF Library Setup</h2>";

$tcpdfPath = 'tcpdf/';
$tcpdfUrl = 'https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip';

if (!file_exists($tcpdfPath)) {
    echo "<p>⚠️ TCPDF library not found. For production use, please install TCPDF:</p>";
    echo "<ol>";
    echo "<li>Download TCPDF from: <a href='https://tcpdf.org/' target='_blank'>https://tcpdf.org/</a></li>";
    echo "<li>Extract to the 'tcpdf/' directory</li>";
    echo "<li>Or use Composer: <code>composer require tecnickcom/tcpdf</code></li>";
    echo "</ol>";
    echo "<p>📝 <strong>Note:</strong> The current implementation uses HTML-to-PDF conversion which works well for most browsers.</p>";
} else {
    echo "<p>✅ TCPDF library found!</p>";
}

echo "<h3>🔧 Current PDF Generation Methods:</h3>";
echo "<ul>";
echo "<li><strong>HTML View:</strong> <code>generate-bus-pass.php</code> - Browser-based printing</li>";
echo "<li><strong>PDF Download:</strong> <code>download-bus-pass-pdf.php</code> - Direct PDF download</li>";
echo "</ul>";

echo "<h3>🚀 Features Available:</h3>";
echo "<ul>";
echo "<li>✅ Professional bus pass design</li>";
echo "<li>✅ User photo integration</li>";
echo "<li>✅ QR code for verification</li>";
echo "<li>✅ Category information (KSRTC, MSRTC, etc.)</li>";
echo "<li>✅ Validity period display</li>";
echo "<li>✅ Print-optimized layout</li>";
echo "<li>✅ Mobile-responsive design</li>";
echo "</ul>";

echo "<p><a href='user-dashboard.php'>← Back to Dashboard</a></p>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>TCPDF Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2 { color: #1565c0; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        a { color: #1565c0; }
    </style>
</head>
<body>
</body>
</html>
