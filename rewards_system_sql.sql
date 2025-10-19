-- Rewards System Database Setup SQL
-- Run this in phpMyAdmin or MySQL command line

-- 1. Add reward_points column to users table
ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0 AFTER phone;

-- 2. Update existing users to have 0 points
UPDATE users SET reward_points = 0 WHERE reward_points IS NULL;

-- 3. Create rewards_rules table
CREATE TABLE IF NOT EXISTS rewards_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action_type VARCHAR(50) NOT NULL UNIQUE,
    points_awarded INT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Create rewards_transactions table
CREATE TABLE IF NOT EXISTS rewards_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    points_earned INT NOT NULL,
    points_redeemed INT DEFAULT 0,
    reference_id INT NULL,
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Create rewards_redemptions table
CREATE TABLE IF NOT EXISTS rewards_redemptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    redemption_type VARCHAR(50) NOT NULL,
    points_used INT NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    application_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    admin_remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES bus_pass_applications(id) ON DELETE SET NULL
);

-- 6. Insert default reward rules
INSERT IGNORE INTO rewards_rules (action_type, points_awarded, description) VALUES
('pass_application', 50, 'Points earned for applying for a new bus pass'),
('pass_renewal', 30, 'Points earned for renewing an existing bus pass'),
('referral_signup', 100, 'Points earned when a referred user signs up'),
('referral_first_pass', 150, 'Points earned when a referred user applies for their first pass'),
('payment_completion', 25, 'Points earned for completing payment on time'),
('profile_completion', 20, 'Points earned for completing profile information');

-- 7. Verify the setup
SELECT 'Users table structure:' as info;
DESCRIBE users;

SELECT 'Reward rules count:' as info;
SELECT COUNT(*) as total_rules FROM rewards_rules;

SELECT 'Active reward rules:' as info;
SELECT action_type, points_awarded, description FROM rewards_rules WHERE is_active = 1;

SELECT 'Users with reward points:' as info;
SELECT COUNT(*) as users_with_points FROM users WHERE reward_points IS NOT NULL;

-- End of setup
