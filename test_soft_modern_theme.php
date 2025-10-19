<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soft Modern Theme Preview - Bus Pass Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/admin-style.css" rel="stylesheet">
    <style>
        .preview-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .theme-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .theme-header h1 {
            color: #1E3A8A;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .theme-header p {
            color: #6B7280;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .color-palette {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .color-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border: 1px solid #E5E7EB;
            text-align: center;
        }
        
        .color-swatch {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            border: 3px solid #E5E7EB;
        }
        
        .color-name {
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .color-hex {
            font-family: monospace;
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        .components-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .component-demo {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border: 1px solid #E5E7EB;
        }
        
        .component-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 0.5rem;
        }
        
        .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .status-demo {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .demo-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .demo-table th,
        .demo-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .demo-table th {
            background: #F8FAFC;
            font-weight: 600;
            color: #1F2937;
        }
        
        .demo-form {
            display: grid;
            gap: 1rem;
        }
        
        .demo-form-group {
            display: flex;
            flex-direction: column;
        }
        
        .demo-form-group label {
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .demo-form-group input,
        .demo-form-group select {
            padding: 0.75rem;
            border: 2px solid #E5E7EB;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .demo-form-group input:focus,
        .demo-form-group select:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-header">
        <h2>Soft Modern Theme Preview</h2>
        <div class="admin-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
            <a href="admin-login.php"><i class="fas fa-sign-in-alt"></i> Admin Login</a>
        </div>
    </div>

    <div class="preview-container">
        <div class="theme-header">
            <h1>🎨 Soft Modern Color Theme</h1>
            <p>A clean, professional, and modern color palette designed for better user experience and accessibility. This theme replaces the previous cyberpunk/neon styling with soft, calming colors.</p>
        </div>

        <!-- Color Palette -->
        <div class="admin-table-container">
            <div class="admin-table-header">
                <h3><i class="fas fa-palette"></i> Color Palette</h3>
            </div>
            <div style="padding: 2rem;">
                <div class="color-palette">
                    <div class="color-card">
                        <div class="color-swatch" style="background: #1E3A8A;"></div>
                        <div class="color-name">Deep Blue (Header)</div>
                        <div class="color-hex">#1E3A8A</div>
                    </div>
                    <div class="color-card">
                        <div class="color-swatch" style="background: #2563EB;"></div>
                        <div class="color-name">Primary Blue</div>
                        <div class="color-hex">#2563EB</div>
                    </div>
                    <div class="color-card">
                        <div class="color-swatch" style="background: #10B981;"></div>
                        <div class="color-name">Success Green</div>
                        <div class="color-hex">#10B981</div>
                    </div>
                    <div class="color-card">
                        <div class="color-swatch" style="background: #EF4444;"></div>
                        <div class="color-name">Soft Red</div>
                        <div class="color-hex">#EF4444</div>
                    </div>
                    <div class="color-card">
                        <div class="color-swatch" style="background: #F8FAFC;"></div>
                        <div class="color-name">Light Background</div>
                        <div class="color-hex">#F8FAFC</div>
                    </div>
                    <div class="color-card">
                        <div class="color-swatch" style="background: #1F2937;"></div>
                        <div class="color-name">Dark Text</div>
                        <div class="color-hex">#1F2937</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Components Demo -->
        <div class="components-grid">
            <!-- Buttons -->
            <div class="component-demo">
                <div class="component-title">Buttons</div>
                <div class="button-group">
                    <button class="admin-btn admin-btn-primary"><i class="fas fa-check"></i> Primary</button>
                    <button class="admin-btn admin-btn-success"><i class="fas fa-thumbs-up"></i> Success</button>
                    <button class="admin-btn admin-btn-danger"><i class="fas fa-times"></i> Danger</button>
                    <button class="admin-btn admin-btn-warning"><i class="fas fa-exclamation"></i> Warning</button>
                    <button class="admin-btn admin-btn-info"><i class="fas fa-info"></i> Info</button>
                    <button class="admin-btn admin-btn-secondary"><i class="fas fa-cog"></i> Secondary</button>
                </div>
            </div>

            <!-- Status Badges -->
            <div class="component-demo">
                <div class="component-title">Status Badges</div>
                <div class="status-demo">
                    <span class="admin-status-pending">Pending</span>
                    <span class="admin-status-approved">Approved</span>
                    <span class="admin-status-rejected">Rejected</span>
                    <span class="admin-status-paid">Paid</span>
                </div>
            </div>

            <!-- Alerts -->
            <div class="component-demo">
                <div class="component-title">Alerts</div>
                <div class="admin-alert admin-alert-success">
                    <i class="fas fa-check-circle"></i> Success! Your changes have been saved.
                </div>
                <div class="admin-alert admin-alert-error">
                    <i class="fas fa-exclamation-triangle"></i> Error! Please check your input.
                </div>
                <div class="admin-alert admin-alert-warning">
                    <i class="fas fa-exclamation-circle"></i> Warning! This action cannot be undone.
                </div>
                <div class="admin-alert admin-alert-info">
                    <i class="fas fa-info-circle"></i> Info: New features are available.
                </div>
            </div>

            <!-- Forms -->
            <div class="component-demo">
                <div class="component-title">Form Elements</div>
                <div class="demo-form">
                    <div class="demo-form-group">
                        <label for="demo-input">Text Input</label>
                        <input type="text" id="demo-input" placeholder="Enter text here..." class="admin-form-control">
                    </div>
                    <div class="demo-form-group">
                        <label for="demo-select">Select Dropdown</label>
                        <select id="demo-select" class="admin-form-control">
                            <option>Choose an option</option>
                            <option>Option 1</option>
                            <option>Option 2</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sample Table -->
        <div class="admin-table-container">
            <div class="admin-table-header">
                <h3><i class="fas fa-table"></i> Sample Data Table</h3>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>001</td>
                        <td>John Doe</td>
                        <td><span class="admin-status-approved">Approved</span></td>
                        <td><span class="admin-status-paid">Paid</span></td>
                        <td>
                            <button class="admin-btn admin-btn-info admin-btn-sm"><i class="fas fa-eye"></i></button>
                            <button class="admin-btn admin-btn-primary admin-btn-sm"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>002</td>
                        <td>Jane Smith</td>
                        <td><span class="admin-status-pending">Pending</span></td>
                        <td><span class="admin-status-pending">Pending</span></td>
                        <td>
                            <button class="admin-btn admin-btn-success admin-btn-sm"><i class="fas fa-check"></i></button>
                            <button class="admin-btn admin-btn-danger admin-btn-sm"><i class="fas fa-times"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>003</td>
                        <td>Bob Johnson</td>
                        <td><span class="admin-status-rejected">Rejected</span></td>
                        <td><span class="admin-status-pending">Pending</span></td>
                        <td>
                            <button class="admin-btn admin-btn-warning admin-btn-sm"><i class="fas fa-redo"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Statistics Cards -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-number">1,234</div>
                <div class="admin-stat-label">Total Applications</div>
                <div class="admin-stat-icon"><i class="fas fa-file-alt"></i></div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-number pending">456</div>
                <div class="admin-stat-label">Pending Review</div>
                <div class="admin-stat-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-number approved">789</div>
                <div class="admin-stat-label">Approved</div>
                <div class="admin-stat-icon"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-number rejected">123</div>
                <div class="admin-stat-label">Rejected</div>
                <div class="admin-stat-icon"><i class="fas fa-times-circle"></i></div>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 3rem; padding: 2rem; background: white; border-radius: 8px; border: 1px solid #E5E7EB;">
            <h3 style="color: #1E3A8A; margin-bottom: 1rem;">✨ Theme Implementation Complete</h3>
            <p style="color: #6B7280; margin-bottom: 1.5rem;">
                The soft modern theme has been successfully applied to your bus pass management system. 
                This provides a clean, professional appearance that's easy on the eyes and improves user experience.
            </p>
            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="admin-dashboard.php" class="admin-btn admin-btn-primary">
                    <i class="fas fa-tachometer-alt"></i> View Admin Dashboard
                </a>
                <a href="admin-login.php" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Admin Login
                </a>
                <a href="index.php" class="admin-btn admin-btn-info">
                    <i class="fas fa-home"></i> Homepage
                </a>
            </div>
        </div>
    </div>
</body>
</html>
