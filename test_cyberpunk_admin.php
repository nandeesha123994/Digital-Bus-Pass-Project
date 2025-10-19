<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyberpunk Admin Dashboard - Nrupatunga Smart Bus Pass Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(180deg, #8B00FF 0%, #1A1A40 100%);
            min-height: 100vh;
            color: #E6E6E6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .main-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(45, 45, 45, 0.9);
            border-radius: 15px;
            border: 2px solid rgba(255, 26, 26, 0.3);
            box-shadow: 0 0 30px rgba(255, 26, 26, 0.2);
        }

        .main-header h1 {
            color: #FFFFFF;
            margin: 0 0 10px 0;
            font-size: 2.5rem;
            font-weight: 900;
            text-shadow: 0 0 20px #FF1A1A;
        }

        .main-header p {
            color: #E6E6E6;
            margin: 0;
            font-size: 1.2rem;
            text-shadow: 0 0 10px rgba(230, 230, 230, 0.5);
        }

        /* Header Demo */
        .header-demo {
            background: #FF1A1A;
            color: #E6E6E6;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(255, 26, 26, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-demo h2 {
            margin: 0;
            text-shadow: 0 0 10px rgba(230, 230, 230, 0.5);
        }

        .nav-demo {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-item {
            color: #E6E6E6;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(230, 230, 230, 0.1);
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s ease;
            text-shadow: 0 0 5px rgba(230, 230, 230, 0.5);
        }

        .nav-item:hover {
            background: rgba(230, 230, 230, 0.2);
            text-shadow: 0 0 10px rgba(230, 230, 230, 0.8);
            box-shadow: 0 0 10px rgba(230, 230, 230, 0.3);
            text-decoration: none;
            color: #E6E6E6;
        }

        /* Stats Cards Demo */
        .stats-demo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card-demo {
            background: #2D2D2D;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #FF9500;
            box-shadow: 0 0 15px rgba(255, 149, 0, 0.3);
            text-align: center;
        }

        .stat-number-demo {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
            color: #FFFF00;
            text-shadow: 0 0 10px rgba(255, 255, 0, 0.5);
        }

        .stat-label-demo {
            color: #E6E6E6;
            text-shadow: 0 0 5px rgba(230, 230, 230, 0.3);
        }

        /* Table Demo */
        .table-demo {
            background: rgba(45, 45, 45, 0.9);
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 247, 255, 0.2);
            overflow: hidden;
            border: 1px solid rgba(0, 247, 255, 0.3);
            margin-bottom: 30px;
        }

        .table-header {
            padding: 20px;
            background: rgba(0, 247, 255, 0.1);
            border-bottom: 1px solid rgba(0, 247, 255, 0.3);
            color: #E6E6E6;
            text-shadow: 0 0 10px rgba(230, 230, 230, 0.5);
        }

        .table-demo table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-demo th {
            background: rgba(58, 58, 58, 0.8);
            color: #00F7FF;
            text-shadow: 0 0 10px rgba(0, 247, 255, 0.5);
            padding: 12px;
            text-align: left;
        }

        .table-demo td {
            padding: 12px;
            color: #E6E6E6;
            border-bottom: 1px solid rgba(74, 74, 74, 0.5);
        }

        .table-demo tr:nth-child(even) { background: rgba(58, 58, 58, 0.3); }
        .table-demo tr:nth-child(odd) { background: rgba(74, 74, 74, 0.3); }

        /* Action Buttons Demo */
        .actions-demo {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .btn-demo {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-green {
            background: #00FF00;
            color: #000000;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.3);
        }

        .btn-green:hover {
            background: #FFFFFF;
            color: #00FF00;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }

        .btn-red {
            background: #FF1A1A;
            color: #FFFFFF;
            box-shadow: 0 0 10px rgba(255, 26, 26, 0.3);
        }

        .btn-red:hover {
            background: #FFFFFF;
            color: #FF1A1A;
            box-shadow: 0 0 15px rgba(255, 26, 26, 0.5);
        }

        .btn-pink {
            background: #FF00FF;
            color: #FFFFFF;
            box-shadow: 0 0 10px rgba(255, 0, 255, 0.3);
        }

        .btn-pink:hover {
            background: #FFFFFF;
            color: #FF00FF;
            box-shadow: 0 0 15px rgba(255, 0, 255, 0.5);
        }

        .color-palette {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .color-item {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            border: 2px solid;
        }

        .electric-violet {
            background: rgba(139, 0, 255, 0.2);
            color: #8B00FF;
            border-color: #8B00FF;
            box-shadow: 0 0 15px rgba(139, 0, 255, 0.3);
        }

        .neon-red {
            background: rgba(255, 26, 26, 0.2);
            color: #FF1A1A;
            border-color: #FF1A1A;
            box-shadow: 0 0 15px rgba(255, 26, 26, 0.3);
        }

        .neon-orange {
            background: rgba(255, 149, 0, 0.2);
            color: #FF9500;
            border-color: #FF9500;
            box-shadow: 0 0 15px rgba(255, 149, 0, 0.3);
        }

        .neon-yellow {
            background: rgba(255, 255, 0, 0.2);
            color: #FFFF00;
            border-color: #FFFF00;
            box-shadow: 0 0 15px rgba(255, 255, 0, 0.3);
        }

        .electric-cyan {
            background: rgba(0, 247, 255, 0.2);
            color: #00F7FF;
            border-color: #00F7FF;
            box-shadow: 0 0 15px rgba(0, 247, 255, 0.3);
        }

        .neon-green {
            background: rgba(0, 255, 0, 0.2);
            color: #00FF00;
            border-color: #00FF00;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.3);
        }

        .info-section {
            background: rgba(45, 45, 45, 0.9);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border: 2px solid rgba(0, 247, 255, 0.3);
            box-shadow: 0 0 20px rgba(0, 247, 255, 0.2);
        }

        .info-section h3 {
            color: #00F7FF;
            text-shadow: 0 0 10px rgba(0, 247, 255, 0.5);
            margin-top: 0;
        }

        @media (max-width: 768px) {
            .header-demo {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-demo {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .actions-demo {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-header">
            <h1>⚡ Cyberpunk Admin Dashboard</h1>
            <p>Bold, futuristic, high-energy aesthetic for admin interface</p>
        </div>

        <!-- Header Demo -->
        <div class="header-demo">
            <h2>Admin Dashboard</h2>
            <div class="nav-demo">
                <a href="#" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="#" class="nav-item"><i class="fas fa-history"></i> Activity Log</a>
                <a href="#" class="nav-item"><i class="fas fa-home"></i> Home</a>
                <a href="#" class="nav-item"><i class="fas fa-tags"></i> Category</a>
                <a href="#" class="nav-item"><i class="fas fa-bullhorn"></i> Announcements</a>
                <a href="#" class="nav-item"><i class="fas fa-star"></i> Reviews</a>
                <a href="#" class="nav-item"><i class="fas fa-flask"></i> Demo</a>
                <a href="#" class="nav-item"><i class="fas fa-envelope"></i> Email Test</a>
                <a href="#" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Stats Cards Demo -->
        <div class="stats-demo">
            <div class="stat-card-demo">
                <div class="stat-number-demo">7</div>
                <div class="stat-label-demo">Total Applications</div>
            </div>
            <div class="stat-card-demo">
                <div class="stat-number-demo">0</div>
                <div class="stat-label-demo">Pending</div>
            </div>
            <div class="stat-card-demo">
                <div class="stat-number-demo">5</div>
                <div class="stat-label-demo">Approved</div>
            </div>
            <div class="stat-card-demo">
                <div class="stat-number-demo">2</div>
                <div class="stat-label-demo">Rejected</div>
            </div>
        </div>

        <!-- Table Demo -->
        <div class="table-demo">
            <div class="table-header">
                <h3>Bus Pass Applications</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Applicant Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>001</td>
                        <td>John Doe</td>
                        <td>Pending</td>
                        <td>
                            <div class="actions-demo">
                                <button class="btn-demo btn-green">View Details</button>
                                <button class="btn-demo btn-red">Reject</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>002</td>
                        <td>Jane Smith</td>
                        <td>Approved</td>
                        <td>
                            <div class="actions-demo">
                                <button class="btn-demo btn-green">View Details</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Color Palette -->
        <div class="info-section">
            <h3>🎨 Cyberpunk Color Palette</h3>
            <div class="color-palette">
                <div class="color-item electric-violet">
                    <h4>Electric Violet</h4>
                    <p>#8B00FF</p>
                    <small>Background Gradient Top</small>
                </div>
                <div class="color-item neon-red">
                    <h4>Neon Red</h4>
                    <p>#FF1A1A</p>
                    <small>Header & Reject Buttons</small>
                </div>
                <div class="color-item neon-orange">
                    <h4>Neon Orange</h4>
                    <p>#FF9500</p>
                    <small>Stat Card Borders</small>
                </div>
                <div class="color-item neon-yellow">
                    <h4>Neon Yellow</h4>
                    <p>#FFFF00</p>
                    <small>Stat Numbers</small>
                </div>
                <div class="color-item electric-cyan">
                    <h4>Electric Cyan</h4>
                    <p>#00F7FF</p>
                    <small>Table Headers</small>
                </div>
                <div class="color-item neon-green">
                    <h4>Neon Green</h4>
                    <p>#00FF00</p>
                    <small>Action Buttons</small>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h3>⚡ Enhanced Features</h3>
            <ul style="line-height: 1.8;">
                <li><strong>Electric Violet to Deep Space Blue:</strong> Cosmic gradient background</li>
                <li><strong>Neon Red Header:</strong> Glowing navigation bar with soft white text</li>
                <li><strong>Charcoal Gray Stat Cards:</strong> Neon orange borders with yellow numbers</li>
                <li><strong>Electric Cyan Table Headers:</strong> Glowing table headers</li>
                <li><strong>Alternating Row Colors:</strong> Dark gray and lighter gray rows</li>
                <li><strong>Neon Action Buttons:</strong> Green for approve, red for reject, pink for filters</li>
                <li><strong>Glowing Effects:</strong> Subtle neon glow on all interactive elements</li>
                <li><strong>Professional Layout:</strong> Maintains functionality while adding visual impact</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <h4 style="color: #00F7FF; text-shadow: 0 0 10px rgba(0, 247, 255, 0.5);">🚀 Experience the Cyberpunk Admin Dashboard:</h4>
            <a href="admin-dashboard.php" class="btn-demo btn-green" style="margin: 10px;">
                ⚡ View Cyberpunk Dashboard
            </a>
            <a href="admin-login.php" class="btn-demo btn-red" style="margin: 10px;">
                🔐 Admin Login
            </a>
            <a href="index.php" class="btn-demo btn-pink" style="margin: 10px;">
                🏠 Back to Homepage
            </a>
        </div>

        <div style="background: rgba(0, 255, 0, 0.1); padding: 25px; border-radius: 15px; border: 2px solid #00FF00; margin: 30px 0; box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);">
            <h4 style="margin: 0 0 15px 0; color: #00FF00; text-shadow: 0 0 10px #00FF00;">⚡ Cyberpunk Admin Dashboard Successfully Implemented!</h4>
            <p style="margin: 0; color: #E6E6E6; line-height: 1.6;">
                The admin dashboard has been transformed with a bold, futuristic cyberpunk aesthetic featuring Electric Violet to Deep Space Blue gradient background, 
                Neon Red header with glowing navigation, Charcoal Gray stat cards with Neon Orange borders and Neon Yellow numbers, Electric Cyan table headers, 
                and Neon Green/Red action buttons. All interactive elements feature subtle neon glows while maintaining professional functionality.
            </p>
        </div>
    </div>
</body>
</html>
