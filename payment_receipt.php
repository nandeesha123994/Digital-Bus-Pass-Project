<?php
session_start();
include('includes/dbconnection.php');
include('includes/config.php');

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit();
}

$paymentId = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if (!$paymentId) {
    header('Location: user-dashboard.php');
    exit();
}

// Get payment details with application info
$query = "SELECT p.*, ba.*, bpt.type_name, bpt.duration_days, u.full_name, u.email
          FROM payments p
          JOIN bus_pass_applications ba ON p.application_id = ba.id
          JOIN bus_pass_types bpt ON ba.pass_type_id = bpt.id
          JOIN users u ON ba.user_id = u.id
          WHERE p.id = ? AND ba.user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("ii", $paymentId, $_SESSION['uid']);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    header('Location: user-dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - Bus Pass Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .receipt-header h1 {
            margin: 0;
            font-size: 2em;
        }
        .receipt-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .receipt-body {
            padding: 30px;
        }
        .receipt-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .receipt-section:last-child {
            border-bottom: none;
        }
        .receipt-section h3 {
            color: #333;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .receipt-row.total {
            font-weight: bold;
            font-size: 1.1em;
            color: #007bff;
            border-top: 2px solid #007bff;
            padding-top: 10px;
            margin-top: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .receipt-actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        @media print {
            body { background: white; }
            .receipt-container { box-shadow: none; }
            .receipt-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1><i class="fas fa-receipt"></i> Payment Receipt</h1>
            <p>Bus Pass Management System</p>
        </div>
        
        <div class="receipt-body">
            <div class="receipt-section">
                <h3><i class="fas fa-info-circle"></i> Transaction Details</h3>
                <div class="receipt-row">
                    <span>Transaction ID:</span>
                    <span><strong><?php echo htmlspecialchars($payment['transaction_id']); ?></strong></span>
                </div>
                <div class="receipt-row">
                    <span>Payment Date:</span>
                    <span><?php echo date('M d, Y H:i', strtotime($payment['payment_date'])); ?></span>
                </div>
                <div class="receipt-row">
                    <span>Payment Method:</span>
                    <span><?php echo ucfirst($payment['payment_method']); ?></span>
                </div>
                <div class="receipt-row">
                    <span>Status:</span>
                    <span><span class="status-badge status-<?php echo strtolower($payment['status']); ?>"><?php echo ucfirst($payment['status']); ?></span></span>
                </div>
            </div>
            
            <div class="receipt-section">
                <h3><i class="fas fa-user"></i> Customer Information</h3>
                <div class="receipt-row">
                    <span>Name:</span>
                    <span><?php echo htmlspecialchars($payment['full_name']); ?></span>
                </div>
                <div class="receipt-row">
                    <span>Email:</span>
                    <span><?php echo htmlspecialchars($payment['email']); ?></span>
                </div>
                <div class="receipt-row">
                    <span>Application ID:</span>
                    <span>#<?php echo $payment['application_id']; ?></span>
                </div>
            </div>
            
            <div class="receipt-section">
                <h3><i class="fas fa-ticket-alt"></i> Pass Details</h3>
                <div class="receipt-row">
                    <span>Pass Type:</span>
                    <span><?php echo htmlspecialchars($payment['type_name']); ?></span>
                </div>
                <div class="receipt-row">
                    <span>Duration:</span>
                    <span><?php echo $payment['duration_days']; ?> days</span>
                </div>
                <div class="receipt-row">
                    <span>Route:</span>
                    <span><?php echo htmlspecialchars($payment['source'] . ' → ' . $payment['destination']); ?></span>
                </div>
                <?php if ($payment['pass_number']): ?>
                <div class="receipt-row">
                    <span>Pass Number:</span>
                    <span><strong><?php echo htmlspecialchars($payment['pass_number']); ?></strong></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="receipt-section">
                <h3><i class="fas fa-calculator"></i> Payment Breakdown</h3>
                <?php
                $baseAmount = $payment['amount'] / 1.1; // Assuming 10% tax
                $tax = $payment['amount'] - $baseAmount;
                ?>
                <div class="receipt-row">
                    <span>Base Amount:</span>
                    <span><?php echo formatCurrency($baseAmount); ?></span>
                </div>
                <div class="receipt-row">
                    <span>Tax (10%):</span>
                    <span><?php echo formatCurrency($tax); ?></span>
                </div>
                <div class="receipt-row total">
                    <span>Total Paid:</span>
                    <span><?php echo formatCurrency($payment['amount']); ?></span>
                </div>
            </div>
            
            <div class="receipt-actions">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <a href="user-dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
