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

// Include TCPDF library (we'll create a simple version first)
class SimplePDF {
    private $content = '';
    private $title = '';
    
    public function __construct() {
        $this->content = '';
    }
    
    public function SetTitle($title) {
        $this->title = $title;
    }
    
    public function AddPage() {
        // Start HTML content
        $this->content .= '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($this->title) . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .pass-container {
            background: white;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .pass-header {
            background: linear-gradient(135deg, #1565c0, #0d47a1);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .pass-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .pass-header .category {
            font-size: 18px;
            margin-top: 5px;
            opacity: 0.9;
        }
        .pass-body {
            padding: 30px;
        }
        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .user-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #1565c0;
            margin-right: 20px;
            object-fit: cover;
        }
        .user-details h2 {
            margin: 0 0 10px 0;
            color: #1565c0;
            font-size: 22px;
        }
        .user-details p {
            margin: 5px 0;
            color: #666;
        }
        .pass-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .detail-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #1565c0;
        }
        .detail-label {
            font-weight: bold;
            color: #333;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .detail-value {
            color: #1565c0;
            font-size: 16px;
            font-weight: 600;
        }
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .qr-code {
            width: 120px;
            height: 120px;
            margin: 0 auto 10px;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        .validity-notice {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            color: #2e7d32;
        }
        .print-button {
            text-align: center;
            margin: 20px 0;
        }
        .btn-print {
            background: #1565c0;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-print:hover {
            background: #0d47a1;
        }
        @media print {
            body { background: white; }
            .print-button { display: none; }
            .pass-container { box-shadow: none; }
        }
    </style>
</head>
<body>';
    }
    
    public function WriteHTML($html) {
        $this->content .= $html;
    }
    
    public function Output($filename = '', $dest = 'I') {
        $this->content .= '</body></html>';
        
        if ($dest === 'D') {
            // Force download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        } else {
            // Display in browser
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        echo $this->content;
    }
}

// Create PDF
$pdf = new SimplePDF();
$pdf->SetTitle('Bus Pass - ' . $application['full_name']);
$pdf->AddPage();

// Generate QR code data
$qrData = "BUSPASS:" . $application['application_id'] . ":" . $application['full_name'] . ":" . $application['valid_until'];
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrData);

// Format dates
$validFrom = date('M d, Y', strtotime($application['valid_from']));
$validUntil = date('M d, Y', strtotime($application['valid_until']));
$issueDate = date('M d, Y', strtotime($application['processed_date']));

// Generate HTML content
$html = '
<div class="pass-container">
    <div class="pass-header">
        <h1>🚌 BUS PASS</h1>
        <div class="category">' . htmlspecialchars($application['category_name'] ?? 'Public Transport') . '</div>
    </div>
    
    <div class="pass-body">
        <div class="user-info">
            <img src="' . (!empty($application['photo_path']) ? htmlspecialchars($application['photo_path']) : 'https://via.placeholder.com/100x100/1565c0/white?text=USER') . '" 
                 alt="User Photo" class="user-photo">
            <div class="user-details">
                <h2>' . htmlspecialchars($application['full_name']) . '</h2>
                <p><strong>Application ID:</strong> ' . htmlspecialchars($application['application_id']) . '</p>
                <p><strong>Pass Number:</strong> ' . htmlspecialchars($application['pass_number']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($application['email']) . '</p>
            </div>
        </div>
        
        <div class="pass-details">
            <div class="detail-item">
                <div class="detail-label">Pass Type</div>
                <div class="detail-value">' . htmlspecialchars($application['type_name']) . '</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Transport Category</div>
                <div class="detail-value">' . htmlspecialchars($application['category_name'] ?? 'N/A') . '</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Valid From</div>
                <div class="detail-value">' . $validFrom . '</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Valid Until</div>
                <div class="detail-value">' . $validUntil . '</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Route</div>
                <div class="detail-value">' . htmlspecialchars($application['source'] . ' → ' . $application['destination']) . '</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Issue Date</div>
                <div class="detail-value">' . $issueDate . '</div>
            </div>
        </div>
        
        <div class="qr-section">
            <div class="qr-code">
                <img src="' . $qrCodeUrl . '" alt="QR Code" style="width: 100%; height: 100%; border-radius: 8px;">
            </div>
            <p><strong>Scan for Verification</strong></p>
            <small>Application ID: ' . htmlspecialchars($application['application_id']) . '</small>
        </div>
        
        <div class="validity-notice">
            <strong>✅ This pass is valid from ' . $validFrom . ' to ' . $validUntil . '</strong><br>
            <small>Please carry this pass during travel. Valid for ' . $application['duration_days'] . ' days from issue date.</small>
        </div>
    </div>
</div>

<div class="print-button">
    <button onclick="window.print()" class="btn-print">🖨️ Print Bus Pass</button>
    <a href="user-dashboard.php" class="btn-print" style="background: #666; margin-left: 10px;">← Back to Dashboard</a>
</div>';

$pdf->WriteHTML($html);

// Check if download is requested
if (isset($_GET['download']) && $_GET['download'] === '1') {
    $filename = 'BusPass_' . $application['application_id'] . '.pdf';
    $pdf->Output($filename, 'D');
} else {
    $pdf->Output();
}
?>
