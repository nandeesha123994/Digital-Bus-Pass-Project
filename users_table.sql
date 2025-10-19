-- Database setup for Bus Pass Management System
-- Make sure to create the database first: CREATE DATABASE IF NOT EXISTS bpmsdb;
-- Then use the database: USE bpmsdb;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bus pass types table
CREATE TABLE bus_pass_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    duration_days INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default pass types
INSERT INTO bus_pass_types (type_name, duration_days, price, description) VALUES
('Daily Pass', 1, 5.00, 'Valid for one day'),
('Weekly Pass', 7, 25.00, 'Valid for one week'),
('Monthly Pass', 30, 80.00, 'Valid for one month'),
('Annual Pass', 365, 800.00, 'Valid for one year');

-- Bus pass applications table
CREATE TABLE bus_pass_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pass_type_id INT NOT NULL,
    applicant_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    source VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    photo_path VARCHAR(255),
    status ENUM('Pending', 'Approved', 'Rejected', 'Payment_Required') DEFAULT 'Pending',
    payment_status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    amount DECIMAL(10,2) NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL,
    admin_remarks TEXT,
    pass_number VARCHAR(20) UNIQUE,
    valid_from DATE,
    valid_until DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pass_type_id) REFERENCES bus_pass_types(id)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method ENUM('stripe', 'razorpay', 'paypal') NOT NULL,
    payment_intent_id VARCHAR(255),
    transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metadata JSON,
    FOREIGN KEY (application_id) REFERENCES bus_pass_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Settings table for system configuration
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('stripe_public_key', 'pk_test_your_stripe_public_key', 'Stripe public key'),
('stripe_secret_key', 'sk_test_your_stripe_secret_key', 'Stripe secret key'),
('razorpay_key_id', 'rzp_test_your_razorpay_key', 'Razorpay key ID'),
('razorpay_key_secret', 'your_razorpay_secret', 'Razorpay key secret'),
('currency', 'USD', 'Default currency'),
('tax_rate', '0.10', 'Tax rate (10%)'),
('admin_email', 'admin@buspass.com', 'Admin email address');