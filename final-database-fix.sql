-- FINAL DATABASE FIX SCRIPT
-- Complete database structure fix for Bus Pass Management System
-- Run this to ensure all tables and columns are properly configured

-- 1. Fix bus_pass_applications table
ALTER TABLE bus_pass_applications 
ADD COLUMN IF NOT EXISTS application_id VARCHAR(20) UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255),
ADD COLUMN IF NOT EXISTS id_proof_type VARCHAR(50),
ADD COLUMN IF NOT EXISTS id_proof_number VARCHAR(50),
ADD COLUMN IF NOT EXISTS email VARCHAR(100);

-- 2. Fix bus_pass_types table
ALTER TABLE bus_pass_types 
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS amount DECIMAL(10,2) DEFAULT 0.00;

-- 3. Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
);

-- 4. Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. Create routes table
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id VARCHAR(20) NOT NULL UNIQUE,
    route_name VARCHAR(100) NOT NULL,
    source VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    distance_km DECIMAL(6,2) DEFAULT NULL,
    estimated_duration VARCHAR(20) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 6. Create payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'razorpay',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100) UNIQUE,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_application_id (application_id),
    INDEX idx_user_id (user_id)
);

-- 7. Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'super_admin') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 9. Insert default categories
INSERT IGNORE INTO categories (category_name, description) VALUES
('Student', 'For students with valid student ID'),
('Senior Citizen', 'For citizens above 60 years'),
('General', 'For general public'),
('Disabled', 'For physically challenged individuals');

-- 10. Insert default routes
INSERT IGNORE INTO routes (route_id, route_name, source, destination, distance_km, estimated_duration) VALUES
('RT001', 'City Center Route', 'Central Bus Station', 'City Mall', 5.2, '25 minutes'),
('RT002', 'Airport Express', 'Central Bus Station', 'Airport', 12.8, '45 minutes'),
('RT003', 'University Route', 'Central Bus Station', 'University Campus', 8.5, '35 minutes'),
('RT004', 'Hospital Route', 'Central Bus Station', 'General Hospital', 6.3, '30 minutes');

-- 11. Update pass types with amounts
UPDATE bus_pass_types SET amount = 50.00 WHERE type_name = 'Daily Pass' AND (amount IS NULL OR amount = 0);
UPDATE bus_pass_types SET amount = 300.00 WHERE type_name = 'Weekly Pass' AND (amount IS NULL OR amount = 0);
UPDATE bus_pass_types SET amount = 1200.00 WHERE type_name = 'Monthly Pass' AND (amount IS NULL OR amount = 0);
UPDATE bus_pass_types SET amount = 12000.00 WHERE type_name = 'Annual Pass' AND (amount IS NULL OR amount = 0);

-- 12. Generate application IDs for existing records
UPDATE bus_pass_applications 
SET application_id = CONCAT('BPMS', YEAR(CURDATE()), LPAD(id, 6, '0'))
WHERE application_id IS NULL OR application_id = '';

-- 13. Insert default admin user (password: admin123)
INSERT IGNORE INTO admin_users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@buspass.com', 'System Administrator', 'super_admin');

-- 14. Insert default settings
INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Bus Pass Management System', 'Name of the website'),
('admin_email', 'admin@buspass.com', 'Administrator email address'),
('currency_symbol', '₹', 'Currency symbol to display'),
('tax_rate', '0.18', 'Tax rate (18% GST)'),
('pass_validity_days', '30', 'Default pass validity in days');

-- 15. Add indexes for better performance
ALTER TABLE bus_pass_applications ADD INDEX IF NOT EXISTS idx_user_id (user_id);
ALTER TABLE bus_pass_applications ADD INDEX IF NOT EXISTS idx_application_id (application_id);
ALTER TABLE bus_pass_applications ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE bus_pass_applications ADD INDEX IF NOT EXISTS idx_payment_status (payment_status);

-- 16. Ensure all required columns have proper defaults
ALTER TABLE bus_pass_applications MODIFY COLUMN id_proof_type VARCHAR(50) DEFAULT '';
ALTER TABLE bus_pass_applications MODIFY COLUMN id_proof_number VARCHAR(50) DEFAULT '';
ALTER TABLE bus_pass_applications MODIFY COLUMN photo_path VARCHAR(255) DEFAULT '';
ALTER TABLE bus_pass_applications MODIFY COLUMN email VARCHAR(100) DEFAULT '';

-- 17. Fix any data type issues
ALTER TABLE bus_pass_types MODIFY COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE bus_pass_types MODIFY COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
