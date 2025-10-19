<?php
/**
 * QR Code Verification Page
 * Allows bus conductors and admins to verify digital bus passes
 */

include('includes/dbconnection.php');
include('includes/qr-generator.php');

$verificationResult = null;
$error = null;

// Handle QR code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qrData = trim($_POST['qr_data']);
    
    if (!empty($qrData)) {
        // Decode QR data
        $decodeResult = QRCodeGenerator::decodePassData($qrData);
        
        if ($decodeResult['success']) {
            // Verify against database
            $verificationResult = QRCodeGenerator::verifyPass($decodeResult['data'], $con);
        } else {
            $error = $decodeResult['error'];
        }
    } else {
        $error = 'Please enter QR code data';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Verification - Bus Pass System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .content {
            padding: 2rem;
        }

        .verification-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }

        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ced4da;
            border-radius: 10px;
            font-size: 1rem;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #1565c0;
            box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
        }

        .verify-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .verify-btn:hover {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }

        .result-card {
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .result-valid {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .result-invalid {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .result-error {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        .result-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .result-header i {
            font-size: 2rem;
        }

        .result-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }

        .pass-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #212529;
        }

        .instructions {
            background: #e3f2fd;
            padding: 1.5rem;
            border-radius: 15px;
            border-left: 4px solid #1565c0;
        }

        .instructions h4 {
            color: #0d47a1;
            margin-bottom: 1rem;
        }

        .instructions ul {
            color: #1565c0;
            padding-left: 1.5rem;
        }

        .instructions li {
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header {
                padding: 1.5rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .content {
                padding: 1.5rem;
            }

            .verification-form {
                padding: 1.5rem;
            }

            .pass-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-qrcode"></i> QR Code Verification</h1>
            <p>Verify Digital Bus Pass QR Codes</p>
        </div>

        <div class="content">
            <div class="verification-form">
                <form method="POST">
                    <div class="form-group">
                        <label for="qr_data">
                            <i class="fas fa-qrcode"></i> QR Code Data
                        </label>
                        <textarea 
                            id="qr_data" 
                            name="qr_data" 
                            placeholder="Paste the QR code data here or scan the QR code and paste the decoded text..."
                            required><?php echo htmlspecialchars($_POST['qr_data'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="verify-btn">
                        <i class="fas fa-search"></i> Verify Pass
                    </button>
                </form>
            </div>

            <?php if ($error): ?>
                <div class="result-card result-error">
                    <div class="result-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Verification Error</h3>
                    </div>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($verificationResult): ?>
                <?php if ($verificationResult['success']): ?>
                    <?php if ($verificationResult['valid']): ?>
                        <div class="result-card result-valid">
                            <div class="result-header">
                                <i class="fas fa-check-circle"></i>
                                <h3>✅ Valid Bus Pass</h3>
                            </div>
                            <p><strong>This bus pass is valid and active.</strong></p>
                            
                            <div class="pass-details">
                                <div class="detail-item">
                                    <div class="detail-label">Pass Number</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($verificationResult['pass']['pass_number']); ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Passenger Name</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($verificationResult['pass']['full_name']); ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Route</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($verificationResult['pass']['source'] . ' → ' . $verificationResult['pass']['destination']); ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Pass Type</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($verificationResult['pass']['type_name'] ?? 'Standard'); ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Valid Until</div>
                                    <div class="detail-value"><?php echo date('M d, Y', strtotime($verificationResult['pass']['valid_until'])); ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Application ID</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($verificationResult['pass']['application_id']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="result-card result-invalid">
                            <div class="result-header">
                                <i class="fas fa-times-circle"></i>
                                <h3>❌ Invalid Bus Pass</h3>
                            </div>
                            <p><strong>Reason:</strong> <?php echo htmlspecialchars($verificationResult['reason']); ?></p>
                            
                            <?php if (isset($verificationResult['pass'])): ?>
                                <div class="pass-details">
                                    <div class="detail-item">
                                        <div class="detail-label">Pass Number</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($verificationResult['pass']['pass_number']); ?></div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">Passenger Name</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($verificationResult['pass']['full_name']); ?></div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">Expired On</div>
                                        <div class="detail-value"><?php echo date('M d, Y', strtotime($verificationResult['pass']['valid_until'])); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="result-card result-error">
                        <div class="result-header">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Verification Failed</h3>
                        </div>
                        <p><?php echo htmlspecialchars($verificationResult['error']); ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="instructions">
                <h4><i class="fas fa-info-circle"></i> How to Use</h4>
                <ul>
                    <li>Ask the passenger to show their digital bus pass QR code</li>
                    <li>Use a QR code scanner app to scan the code</li>
                    <li>Copy the decoded text and paste it in the field above</li>
                    <li>Click "Verify Pass" to check if the pass is valid</li>
                    <li>Green result means the pass is valid and active</li>
                    <li>Red result means the pass is invalid or expired</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus on the textarea
        document.getElementById('qr_data').focus();
        
        // Add some basic validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const qrData = document.getElementById('qr_data').value.trim();
            if (!qrData) {
                e.preventDefault();
                alert('Please enter QR code data');
                return false;
            }
        });
    </script>
</body>
</html>

<?php $con->close(); ?>
