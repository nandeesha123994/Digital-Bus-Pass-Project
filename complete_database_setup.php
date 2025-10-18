<?php
// Complete Database Setup Script for Nrupatunga Digital Bus Pass System
// This script creates the database and all required tables with sample data

$servername = "localhost";
$username = "root";
$password = "";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - Nrupatunga Digital Bus Pass System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
        }
        .content {
            padding: 30px;
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            color: #0c5460;
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        .btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Database Setup</h1>
            <p>Nrupatunga Digital Bus Pass Management System</p>
        </div>
        <div class="content">
            <?php
            try {
                // Create connection without database
                $con = new mysqli($servername, $username, $password);

                // Check connection
                if ($con->connect_error) {
                    throw new Exception("Connection failed: " . $con->connect_error);
                }

                echo "<div class='success'>✅ Connected to MySQL server successfully</div>";

                // Create database
                $sql = "CREATE DATABASE IF NOT EXISTS bpmsdb";
                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Database 'bpmsdb' created successfully</div>";
                } else {
                    throw new Exception("Error creating database: " . $con->error);
                }

                // Select the database
                $con->select_db("bpmsdb");

                // Create users table
                $sql = "CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    full_name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    phone VARCHAR(15),
                    address TEXT,
                    date_of_birth DATE,
                    gender ENUM('Male', 'Female', 'Other'),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    status ENUM('active', 'inactive') DEFAULT 'active'
                )";

                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Users table created successfully</div>";
                } else {
                    throw new Exception("Error creating users table: " . $con->error);
                }

                // Create categories table
                $sql = "CREATE TABLE IF NOT EXISTS categories (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    category_name VARCHAR(50) NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";

                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Categories table created successfully</div>";
                } else {
                    throw new Exception("Error creating categories table: " . $con->error);
                }

                // Insert default categories
                $categories = [
                    ['KSRTC', 'Karnataka State Road Transport Corporation'],
                    ['BMTC', 'Bangalore Metropolitan Transport Corporation'],
                    ['MSRTC', 'Maharashtra State Road Transport Corporation']
                ];

                foreach ($categories as $category) {
                    $sql = "INSERT IGNORE INTO categories (category_name, description) VALUES (?, ?)";
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("ss", $category[0], $category[1]);
                    $stmt->execute();
                }
                echo "<div class='success'>✅ Default categories inserted</div>";

                // Create bus_pass_types table
                $sql = "CREATE TABLE IF NOT EXISTS bus_pass_types (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    type_name VARCHAR(50) NOT NULL,
                    description TEXT,
                    amount DECIMAL(10,2) NOT NULL,
                    duration_days INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";

                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Bus pass types table created successfully</div>";
                } else {
                    throw new Exception("Error creating bus_pass_types table: " . $con->error);
                }

                // Insert default pass types
                $passTypes = [
                    ['Student Monthly', 'Monthly pass for students', 150.00, 30],
                    ['General Monthly', 'Monthly pass for general public', 300.00, 30],
                    ['Senior Citizen Monthly', 'Monthly pass for senior citizens', 100.00, 30],
                    ['Student Quarterly', 'Quarterly pass for students', 400.00, 90],
                    ['General Quarterly', 'Quarterly pass for general public', 800.00, 90],
                    ['Student Annual', 'Annual pass for students', 1500.00, 365],
                    ['General Annual', 'Annual pass for general public', 3000.00, 365]
                ];

                foreach ($passTypes as $passType) {
                    $sql = "INSERT IGNORE INTO bus_pass_types (type_name, description, amount, duration_days) VALUES (?, ?, ?, ?)";
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("ssdi", $passType[0], $passType[1], $passType[2], $passType[3]);
                    $stmt->execute();
                }
                echo "<div class='success'>✅ Default pass types inserted</div>";

                // Create bus_pass_applications table
                $sql = "CREATE TABLE IF NOT EXISTS bus_pass_applications (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    applicant_name VARCHAR(100) NOT NULL,
                    date_of_birth DATE NOT NULL,
                    gender ENUM('Male', 'Female', 'Other') NOT NULL,
                    address TEXT NOT NULL,
                    phone VARCHAR(15) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    pass_type_id INT NOT NULL,
                    category_id INT,
                    source VARCHAR(100) NOT NULL,
                    destination VARCHAR(100) NOT NULL,
                    id_proof_type VARCHAR(50) NOT NULL,
                    id_proof_number VARCHAR(50) NOT NULL,
                    id_proof_file VARCHAR(255),
                    photo_file VARCHAR(255),
                    amount DECIMAL(10,2) NOT NULL,
                    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
                    payment_status ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
                    admin_remarks TEXT,
                    pass_number VARCHAR(50),
                    valid_from DATE,
                    valid_until DATE,
                    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    processed_date TIMESTAMP NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (pass_type_id) REFERENCES bus_pass_types(id),
                    FOREIGN KEY (category_id) REFERENCES categories(id)
                )";

                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Bus pass applications table created successfully</div>";
                } else {
                    throw new Exception("Error creating applications table: " . $con->error);
                }

                // Create payments table
                $sql = "CREATE TABLE IF NOT EXISTS payments (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    application_id INT NOT NULL,
                    user_id INT,
                    amount DECIMAL(10,2) NOT NULL,
                    payment_method VARCHAR(50),
                    transaction_id VARCHAR(100),
                    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                    gateway_response TEXT,
                    FOREIGN KEY (application_id) REFERENCES bus_pass_applications(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )";

                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Payments table created successfully</div>";
                } else {
                    throw new Exception("Error creating payments table: " . $con->error);
                }

                // Create admin_actions table for logging
                $sql = "CREATE TABLE IF NOT EXISTS admin_actions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    admin_id VARCHAR(50) NOT NULL,
                    admin_name VARCHAR(100) NOT NULL,
                    application_id INT,
                    applicant_name VARCHAR(100),
                    action VARCHAR(50) NOT NULL,
                    old_status VARCHAR(50),
                    new_status VARCHAR(50),
                    remarks TEXT,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    INDEX idx_admin_timestamp (admin_id, timestamp),
                    INDEX idx_application (application_id),
                    INDEX idx_timestamp (timestamp)
                )";

                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Admin actions table created successfully</div>";
                } else {
                    throw new Exception("Error creating admin_actions table: " . $con->error);
                }

                // Create announcements table
                $sql = "CREATE TABLE IF NOT EXISTS announcements (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    title VARCHAR(200) NOT NULL,
                    content TEXT NOT NULL,
                    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
                    is_active BOOLEAN DEFAULT TRUE,
                    expiry_date DATE,
                    created_by VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";

                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Announcements table created successfully</div>";
                } else {
                    throw new Exception("Error creating announcements table: " . $con->error);
                }

                // Insert sample announcement
                $sql = "INSERT IGNORE INTO announcements (title, content, type, expiry_date, created_by) VALUES
                        ('Welcome to Nrupatunga Digital Bus Pass System',
                         'Apply for your bus pass online quickly and easily. No more long queues!',
                         'success',
                         DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                         'System Administrator')";

                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Sample announcement inserted</div>";
                }

                // Create instant_reviews table
                $sql = "CREATE TABLE IF NOT EXISTS instant_reviews (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    review_text TEXT NOT NULL,
                    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('active', 'hidden') DEFAULT 'active',
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_status_created (status, created_at DESC),
                    INDEX idx_user_created (user_id, created_at DESC)
                )";

                if ($con->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Instant reviews table created successfully</div>";
                } else {
                    throw new Exception("Error creating instant_reviews table: " . $con->error);
                }

                // Create sample users
                $sampleUsers = [
                    ['John Doe', 'john@example.com', password_hash('password123', PASSWORD_DEFAULT), '9876543210', '123 Main St, Bangalore', '1990-05-15', 'Male'],
                    ['Jane Smith', 'jane@example.com', password_hash('password123', PASSWORD_DEFAULT), '9876543211', '456 Park Ave, Bangalore', '1992-08-20', 'Female'],
                    ['Raj Kumar', 'raj@example.com', password_hash('password123', PASSWORD_DEFAULT), '9876543212', '789 MG Road, Bangalore', '1988-12-10', 'Male'],
                    ['Priya Sharma', 'priya@example.com', password_hash('password123', PASSWORD_DEFAULT), '9876543213', '321 Brigade Road, Bangalore', '1995-03-25', 'Female'],
                    ['Admin User', 'admin@example.com', password_hash('admin123', PASSWORD_DEFAULT), '9876543214', 'Admin Office, Bangalore', '1985-01-01', 'Male']
                ];

                foreach ($sampleUsers as $user) {
                    $sql = "INSERT IGNORE INTO users (full_name, email, password, phone, address, date_of_birth, gender) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("sssssss", $user[0], $user[1], $user[2], $user[3], $user[4], $user[5], $user[6]);
                    $stmt->execute();
                }
                echo "<div class='success'>✅ Sample users created (Password: password123 for users, admin123 for admin)</div>";

                // Insert sample reviews
                $sampleReviews = [
                    [1, 'Excellent bus pass management system! Very user-friendly and efficient. The online application process is smooth and saves a lot of time.', 5],
                    [2, 'Great service overall. The digital pass system is convenient and the support team is responsive. Minor improvements needed in mobile interface.', 4],
                    [3, 'Outstanding experience! Quick approval process and easy to use dashboard. Highly recommend this system for all bus pass needs.', 5],
                    [4, 'Good system with modern features. The application tracking is very helpful. Would like to see more payment options in the future.', 4],
                    [1, 'Perfect solution for bus pass management. Clean interface, fast processing, and excellent customer service. Very satisfied!', 5]
                ];

                foreach ($sampleReviews as $review) {
                    $sql = "INSERT IGNORE INTO instant_reviews (user_id, review_text, rating) VALUES (?, ?, ?)";
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("isi", $review[0], $review[1], $review[2]);
                    $stmt->execute();
                }
                echo "<div class='success'>✅ Sample reviews inserted</div>";

                echo "<div class='info'>";
                echo "<h3>🎉 Complete Database Setup Finished!</h3>";
                echo "<p><strong>✅ All tables created successfully:</strong></p>";
                echo "<ul>";
                echo "<li>✅ users - User accounts and profiles</li>";
                echo "<li>✅ categories - Transport categories (KSRTC, BMTC, MSRTC)</li>";
                echo "<li>✅ bus_pass_types - Pass types and pricing</li>";
                echo "<li>✅ bus_pass_applications - Application management</li>";
                echo "<li>✅ payments - Payment processing</li>";
                echo "<li>✅ admin_actions - Admin activity logging</li>";
                echo "<li>✅ announcements - System announcements</li>";
                echo "<li>✅ instant_reviews - User feedback system</li>";
                echo "</ul>";
                echo "<p><strong>🔑 Sample Login Credentials:</strong></p>";
                echo "<p>👤 <strong>User:</strong> john@example.com / password123</p>";
                echo "<p>🔐 <strong>Admin:</strong> admin@example.com / admin123</p>";
                echo "</div>";

                $con->close();

            } catch (Exception $e) {
                echo "<div class='error'>❌ Setup Failed: " . $e->getMessage() . "</div>";
                echo "<div class='info'>";
                echo "<h4>Troubleshooting:</h4>";
                echo "<ul>";
                echo "<li>Make sure XAMPP/MySQL is running</li>";
                echo "<li>Check if MySQL service is started</li>";
                echo "<li>Verify database connection settings</li>";
                echo "</ul>";
                echo "</div>";
            }
            ?>

            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" class="btn">🏠 Go to Homepage</a>
                <a href="user-registration.php" class="btn btn-primary">👤 Register User</a>
                <a href="admin-login.php" class="btn btn-primary">🔐 Admin Login</a>
            </div>
        </div>
    </div>
</body>
</html>
