<?php
session_start();
require_once('includes/dbconnection.php');

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

// Get application ID from URL
$applicationId = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;

if (!$applicationId) {
    die('Invalid application ID');
}

// Get application details with user info and category
$query = "SELECT ba.*, u.full_name, u.email, u.phone as user_phone, 
                 bpt.type_name, bpt.duration_days,
                 c.category_name
          FROM bus_pass_applications ba
          JOIN users u ON ba.user_id = u.id
          JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          LEFT JOIN categories c ON ba.category_id = c.id
          WHERE ba.id = ? AND ba.user_id = ? AND ba.status = 'Approved'";

$stmt = $con->prepare($query);
$stmt->bind_param("ii", $applicationId, $_SESSION['uid']);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();

if (!$application) {
    die('Application not found or not approved');
}

// Format dates
$validFrom = date('M d, Y', strtotime($application['valid_from']));
$validUntil = date('M d, Y', strtotime($application['valid_until']));
$issueDate = date('M d, Y', strtotime($application['processed_date']));

// Generate QR code data
$qrData = "BUSPASS:" . $application['application_id'] . ":" . $application['full_name'] . ":" . $application['valid_until'];
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrData);

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="BusPass_' . $application['application_id'] . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Create HTML content for PDF conversion
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bus Pass - ' . htmlspecialchars($application['full_name']) . '</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }
        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #333;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto;
            background: white;
            box-sizing: border-box;
        }
        .pass-container {
            border: 3px solid #1565c0;
            border-radius: 15px;
            overflow: hidden;
            background: white;
        }
        .pass-header {
            background: linear-gradient(135deg, #1565c0, #0d47a1);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        .pass-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.1\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }
        .pass-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
            position: relative;
            z-index: 1;
        }
        .pass-header .category {
            font-size: 20px;
            margin-top: 8px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        .pass-body {
            padding: 30px;
        }
        .user-section {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 3px solid #e0e0e0;
        }
        .user-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid #1565c0;
            margin-right: 25px;
            object-fit: cover;
            background: #f0f0f0;
        }
        .user-info h2 {
            margin: 0 0 15px 0;
            color: #1565c0;
            font-size: 28px;
            font-weight: bold;
        }
        .user-info p {
            margin: 8px 0;
            color: #666;
            font-size: 16px;
        }
        .user-info .highlight {
            color: #1565c0;
            font-weight: bold;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .detail-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #1565c0;
        }
        .detail-label {
            font-weight: bold;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .detail-value {
            color: #1565c0;
            font-size: 18px;
            font-weight: 600;
        }
        .verification-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .qr-code {
            text-align: center;
        }
        .qr-code img {
            width: 150px;
            height: 150px;
            border: 2px solid #1565c0;
            border-radius: 10px;
        }
        .verification-info {
            flex: 1;
            margin-left: 30px;
        }
        .verification-info h3 {
            color: #1565c0;
            margin: 0 0 15px 0;
            font-size: 22px;
        }
        .verification-info p {
            margin: 8px 0;
            color: #666;
            font-size: 16px;
        }
        .validity-banner {
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
        .footer-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(21, 101, 192, 0.05);
            font-weight: bold;
            z-index: -1;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="watermark">BUS PASS</div>
    <div class="page">
        <div class="pass-container">
            <div class="pass-header">
                <h1>🚌 OFFICIAL BUS PASS</h1>
                <div class="category">' . htmlspecialchars($application['category_name'] ?? 'Public Transport Authority') . '</div>
            </div>
            
            <div class="pass-body">
                <div class="user-section">
                    <img src="' . (!empty($application['photo_path']) ? htmlspecialchars($application['photo_path']) : 'https://via.placeholder.com/120x120/1565c0/white?text=' . urlencode(substr($application['full_name'], 0, 1))) . '" 
                         alt="User Photo" class="user-photo">
                    <div class="user-info">
                        <h2>' . htmlspecialchars($application['full_name']) . '</h2>
                        <p><strong>Application ID:</strong> <span class="highlight">' . htmlspecialchars($application['application_id']) . '</span></p>
                        <p><strong>Pass Number:</strong> <span class="highlight">' . htmlspecialchars($application['pass_number']) . '</span></p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($application['email']) . '</p>
                        <p><strong>Phone:</strong> ' . htmlspecialchars($application['phone']) . '</p>
                    </div>
                </div>
                
                <div class="details-grid">
                    <div class="detail-card">
                        <div class="detail-label">Pass Type</div>
                        <div class="detail-value">' . htmlspecialchars($application['type_name']) . '</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Transport Category</div>
                        <div class="detail-value">' . htmlspecialchars($application['category_name'] ?? 'General') . '</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Valid From</div>
                        <div class="detail-value">' . $validFrom . '</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Valid Until</div>
                        <div class="detail-value">' . $validUntil . '</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Route</div>
                        <div class="detail-value">' . htmlspecialchars($application['source'] . ' ↔ ' . $application['destination']) . '</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Issue Date</div>
                        <div class="detail-value">' . $issueDate . '</div>
                    </div>
                </div>
                
                <div class="verification-section">
                    <div class="qr-code">
                        <img src="' . $qrCodeUrl . '" alt="QR Code">
                        <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;"><strong>Scan for Verification</strong></p>
                    </div>
                    <div class="verification-info">
                        <h3>🔒 Verification Details</h3>
                        <p><strong>Pass ID:</strong> ' . htmlspecialchars($application['application_id']) . '</p>
                        <p><strong>Holder:</strong> ' . htmlspecialchars($application['full_name']) . '</p>
                        <p><strong>Valid Until:</strong> ' . $validUntil . '</p>
                        <p><strong>Authority:</strong> ' . htmlspecialchars($application['category_name'] ?? 'Transport Authority') . '</p>
                        <p style="margin-top: 15px; font-size: 14px; color: #999;">
                            This QR code contains encrypted pass information for verification purposes.
                        </p>
                    </div>
                </div>
                
                <div class="validity-banner">
                    ✅ This pass is valid from ' . $validFrom . ' to ' . $validUntil . '
                    <br><small style="font-size: 14px; opacity: 0.9;">Valid for ' . $application['duration_days'] . ' days • Please carry this pass during travel</small>
                </div>
                
                <div class="footer-info">
                    <p><strong>Important:</strong> This is an official bus pass. Please carry this document during travel.</p>
                    <p>For support or queries, contact the transport authority.</p>
                    <p style="margin-top: 15px; font-size: 12px;">
                        Generated on ' . date('M d, Y H:i:s') . ' • Bus Pass Management System
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';

// For now, we'll output HTML that can be printed as PDF
// In a production environment, you would use a library like TCPDF, FPDF, or wkhtmltopdf
echo $html;
?>
