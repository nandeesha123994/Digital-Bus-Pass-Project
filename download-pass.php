<?php
/**
 * Download Bus Pass
 * Generates and downloads PDF bus pass for approved applications
 */

session_start();
include('includes/dbconnection.php');

// Check if application ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: track-application.php');
    exit('Invalid application ID');
}

$applicationId = intval($_GET['id']);

try {
    // Get application details with user information
    $query = "SELECT ba.*, bpt.type_name, bpt.duration_days, c.category_name, u.full_name as user_name, u.email as user_email, u.phone as user_phone
              FROM bus_pass_applications ba
              LEFT JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
              LEFT JOIN categories c ON ba.category_id = c.id
              LEFT JOIN users u ON ba.user_id = u.id
              WHERE ba.id = ? AND ba.status = 'Approved' AND ba.pass_number IS NOT NULL";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: track-application.php');
        exit('Pass not found or not approved');
    }
    
    $pass = $result->fetch_assoc();
    
    // Check if user is logged in and owns this application (optional security check)
    if (isset($_SESSION['uid']) && $_SESSION['uid'] != $pass['user_id']) {
        // Allow download even if not the owner for public tracking
        // You can uncomment the lines below for stricter security
        // header('Location: track-application.php');
        // exit('Access denied');
    }
    
    // For now, we'll create an HTML page that can be saved as PDF by the browser
    // Set headers for HTML download that will prompt save as PDF
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: inline; filename="bus_pass_' . $pass['pass_number'] . '.html"');
    
    // For now, we'll create an HTML version that can be printed as PDF
    // In a production environment, you'd use a proper PDF library
    
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bus Pass - <?php echo htmlspecialchars($pass['pass_number']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            color: #333;
        }
        .pass-container {
            max-width: 600px;
            margin: 0 auto;
            border: 3px solid #5A4FCF;
            border-radius: 15px;
            padding: 30px;
            background: linear-gradient(135deg, #F8F9FA 0%, #ECE8FF 100%);
        }
        .pass-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #5A4FCF;
            padding-bottom: 20px;
        }
        .pass-title {
            color: #5A4FCF;
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        .pass-subtitle {
            color: #4B0082;
            font-size: 16px;
            margin: 5px 0 0 0;
        }
        .pass-number {
            background: linear-gradient(90deg, #5A4FCF 0%, #6A67D5 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0;
            display: inline-block;
        }
        .pass-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        .detail-item {
            margin-bottom: 15px;
        }
        .detail-label {
            font-weight: bold;
            color: #4B0082;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 16px;
            color: #333;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            border: 1px solid #ECE8FF;
        }
        .route-section {
            grid-column: 1 / -1;
            text-align: center;
            background: rgba(90, 79, 207, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .route-text {
            font-size: 20px;
            font-weight: bold;
            color: #5A4FCF;
        }
        .validity-section {
            background: rgba(40, 167, 69, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #28a745;
        }
        .validity-text {
            color: #28a745;
            font-weight: bold;
            font-size: 16px;
        }
        .pass-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #5A4FCF;
            font-size: 12px;
            color: #666;
        }
        .qr-placeholder {
            width: 100px;
            height: 100px;
            background: #f0f0f0;
            border: 2px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            border-radius: 10px;
            color: #666;
            font-size: 12px;
        }
        @media print {
            body { margin: 0; }
            .pass-container { border: 2px solid #000; }
        }
    </style>
</head>
<body>
    <div class="pass-container">
        <div class="pass-header">
            <h1 class="pass-title">🚌 DIGITAL BUS PASS</h1>
            <p class="pass-subtitle">Nrupatunga Digital Bus Pass System</p>
            <div class="pass-number">Pass #: <?php echo htmlspecialchars($pass['pass_number']); ?></div>
        </div>

        <div class="pass-details">
            <div class="detail-item">
                <div class="detail-label">👤 Passenger Name</div>
                <div class="detail-value"><?php echo htmlspecialchars($pass['applicant_name']); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">🆔 Application ID</div>
                <div class="detail-value"><?php echo htmlspecialchars($pass['application_id']); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">🎫 Pass Type</div>
                <div class="detail-value"><?php echo htmlspecialchars($pass['type_name'] ?? 'Standard'); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">🚌 Category</div>
                <div class="detail-value"><?php echo htmlspecialchars($pass['category_name'] ?? 'General'); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">📞 Contact</div>
                <div class="detail-value"><?php echo htmlspecialchars($pass['phone']); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">💰 Amount Paid</div>
                <div class="detail-value">₹<?php echo number_format($pass['amount'], 2); ?></div>
            </div>

            <div class="route-section">
                <div class="detail-label">🛣️ ROUTE</div>
                <div class="route-text">
                    <?php echo htmlspecialchars($pass['source']); ?> 
                    ➡️ 
                    <?php echo htmlspecialchars($pass['destination']); ?>
                </div>
            </div>
        </div>

        <div class="validity-section">
            <div class="validity-text">
                ✅ VALID FROM: <?php echo date('d M Y', strtotime($pass['valid_from'])); ?>
                <br>
                📅 VALID UNTIL: <?php echo date('d M Y', strtotime($pass['valid_until'])); ?>
            </div>
        </div>

        <div class="qr-placeholder">
            QR Code
            <br>
            (Scan for verification)
        </div>

        <div class="pass-footer">
            <p><strong>Important Instructions:</strong></p>
            <p>• This pass is non-transferable and valid only for the mentioned route</p>
            <p>• Carry a valid ID proof along with this pass</p>
            <p>• Pass must be shown to the conductor when requested</p>
            <p>• Report loss or theft immediately to the transport authority</p>
            <br>
            <p>Generated on: <?php echo date('d M Y, h:i A'); ?></p>
            <p>© Nrupatunga Digital Bus Pass System</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Show download options
            const userChoice = confirm("Bus Pass loaded successfully!\n\nClick 'OK' to print/save as PDF\nClick 'Cancel' to view only");

            if (userChoice) {
                // Open print dialog which allows saving as PDF
                window.print();
            }

            // Add download button
            const downloadBtn = document.createElement('button');
            downloadBtn.innerHTML = '📄 Save as PDF';
            downloadBtn.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(90deg, #5A4FCF 0%, #6A67D5 100%);
                color: white;
                border: none;
                padding: 15px 25px;
                border-radius: 25px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(90, 79, 207, 0.3);
                z-index: 1000;
            `;

            downloadBtn.onclick = function() {
                window.print();
            };

            document.body.appendChild(downloadBtn);

            // Add back button
            const backBtn = document.createElement('button');
            backBtn.innerHTML = '← Back to Tracking';
            backBtn.style.cssText = `
                position: fixed;
                top: 20px;
                left: 20px;
                background: #6c757d;
                color: white;
                border: none;
                padding: 15px 25px;
                border-radius: 25px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
                z-index: 1000;
            `;

            backBtn.onclick = function() {
                window.history.back();
            };

            document.body.appendChild(backBtn);
        }

        // Hide buttons when printing
        window.addEventListener('beforeprint', function() {
            const buttons = document.querySelectorAll('button');
            buttons.forEach(btn => btn.style.display = 'none');
        });

        window.addEventListener('afterprint', function() {
            const buttons = document.querySelectorAll('button');
            buttons.forEach(btn => btn.style.display = 'block');
        });
    </script>
</body>
</html>

<?php
    
} catch (Exception $e) {
    error_log('Download Pass Error: ' . $e->getMessage());
    header('Location: track-application.php?error=download_failed');
    exit('Download failed');
}

$con->close();
?>
