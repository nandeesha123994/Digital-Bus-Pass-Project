<!DOCTYPE html>
<html>
<head>
    <title>Simple Razorpay Test</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin: 10px 0;
        }
        .btn:hover { background: #0056b3; }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: none;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Simple Razorpay Test</h1>
        <p>This is a simplified test to check if Razorpay integration works without session dependencies.</p>

        <div id="status" class="status"></div>

        <h3>Configuration Check</h3>
        <div class="info status" style="display: block;">
            <strong>Razorpay Script:</strong> <span id="scriptStatus">⏳ Checking...</span><br>
            <strong>Demo Key:</strong> rzp_test_1234567890<br>
            <strong>Test Amount:</strong> ₹100
        </div>

        <h3>Test Options</h3>

        <button class="btn" onclick="testRazorpayDirect()">
            🔥 Test Razorpay Direct (No Backend)
        </button>

        <button class="btn" onclick="testRazorpayWithOrder()">
            📦 Test Razorpay with Order Creation
        </button>

        <button class="btn" onclick="checkRazorpayScript()">
            🔍 Check Razorpay Script Loading
        </button>

        <h3>Debug Information</h3>
        <pre id="debugInfo">Click a test button to see debug information...</pre>
    </div>

    <script>
        function showStatus(message, type = 'info') {
            const status = document.getElementById('status');
            status.className = `status ${type}`;
            status.innerHTML = message;
            status.style.display = 'block';
        }

        function updateDebug(info) {
            document.getElementById('debugInfo').textContent = JSON.stringify(info, null, 2);
        }

        function checkRazorpayScript() {
            const info = {
                razorpayLoaded: typeof Razorpay !== 'undefined',
                razorpayVersion: typeof Razorpay !== 'undefined' ? 'Available' : 'Not Available',
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            };

            updateDebug(info);

            if (typeof Razorpay !== 'undefined') {
                showStatus('✅ Razorpay script loaded successfully!', 'success');
            } else {
                showStatus('❌ Razorpay script failed to load. Check internet connection.', 'error');
            }
        }

        function testRazorpayDirect() {
            showStatus('🔄 Testing Razorpay direct integration...', 'info');

            if (typeof Razorpay === 'undefined') {
                showStatus('❌ Razorpay script not loaded!', 'error');
                return;
            }

            const options = {
                key: 'rzp_test_1234567890', // Demo key
                amount: 10000, // ₹100 in paise
                currency: 'INR',
                name: 'Bus Pass Management',
                description: 'Direct Test Payment',
                order_id: 'order_demo_' + Date.now(), // Demo order ID
                handler: function (response) {
                    // For demo mode, generate a proper payment ID format
                    const demoPaymentId = response.razorpay_payment_id || ('pay_demo_' + Date.now() + '_' + Math.floor(Math.random() * 9999));

                    const info = {
                        success: true,
                        payment_id: demoPaymentId,
                        order_id: response.razorpay_order_id,
                        signature: response.razorpay_signature,
                        timestamp: new Date().toISOString()
                    };

                    updateDebug(info);
                    showStatus('✅ Payment successful! Payment ID: ' + demoPaymentId, 'success');
                },
                prefill: {
                    name: 'Test User',
                    email: 'test@example.com',
                    contact: '9999999999'
                },
                theme: {
                    color: '#007bff'
                },
                modal: {
                    ondismiss: function() {
                        showStatus('⚠️ Payment cancelled by user', 'error');
                        updateDebug({ cancelled: true, timestamp: new Date().toISOString() });
                    }
                }
            };

            try {
                const rzp = new Razorpay(options);

                rzp.on('payment.failed', function (response) {
                    const info = {
                        success: false,
                        error: response.error,
                        timestamp: new Date().toISOString()
                    };

                    updateDebug(info);
                    showStatus('❌ Payment failed: ' + response.error.description, 'error');
                });

                rzp.open();
                showStatus('🚀 Razorpay modal opened successfully!', 'success');

            } catch (error) {
                updateDebug({ error: error.message, stack: error.stack });
                showStatus('❌ Error opening Razorpay: ' + error.message, 'error');
            }
        }

        function testRazorpayWithOrder() {
            showStatus('🔄 Testing Razorpay with order creation...', 'info');

            // Test the order creation endpoint
            fetch('create_razorpay_order_test.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: 1,
                    amount: 100
                })
            })
            .then(response => {
                updateDebug({
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries())
                });

                return response.json();
            })
            .then(data => {
                updateDebug(data);

                if (data.success) {
                    showStatus('✅ Order created successfully! Order ID: ' + data.order_id, 'success');

                    // Now test payment with the order
                    const options = {
                        key: data.key,
                        amount: data.amount,
                        currency: data.currency,
                        name: data.name,
                        description: data.description,
                        order_id: data.order_id,
                        handler: function (response) {
                            showStatus('✅ Payment with order successful! Payment ID: ' + response.razorpay_payment_id, 'success');
                        },
                        prefill: data.prefill || {
                            name: 'Test User',
                            email: 'test@example.com'
                        },
                        theme: {
                            color: '#007bff'
                        }
                    };

                    const rzp = new Razorpay(options);
                    rzp.open();

                } else {
                    showStatus('❌ Order creation failed: ' + data.error, 'error');
                }
            })
            .catch(error => {
                updateDebug({ fetchError: error.message });
                showStatus('❌ Network error: ' + error.message, 'error');
            });
        }

        // Check Razorpay on page load
        window.addEventListener('load', function() {
            setTimeout(function() {
                checkRazorpayScript();
                // Update script status indicator
                const scriptStatus = document.getElementById('scriptStatus');
                if (typeof Razorpay !== 'undefined') {
                    scriptStatus.innerHTML = '✅ Loaded';
                    scriptStatus.style.color = '#28a745';
                } else {
                    scriptStatus.innerHTML = '❌ Failed';
                    scriptStatus.style.color = '#dc3545';
                }
            }, 1000);
        });
    </script>
</body>
</html>
