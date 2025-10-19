-- Add reward columns to users table
ALTER TABLE users 
ADD COLUMN reward_points INT DEFAULT 0,
ADD COLUMN pass_count INT DEFAULT 0;

-- Create rewards_rules table
CREATE TABLE IF NOT EXISTS rewards_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pass_type VARCHAR(50) NOT NULL,
    points_awarded INT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create rewards_transactions table
CREATE TABLE IF NOT EXISTS rewards_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pass_type VARCHAR(50) NOT NULL,
    points_earned INT NOT NULL,
    application_id INT,
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES bus_pass_applications(id) ON DELETE SET NULL
);

-- Insert default reward rules
INSERT INTO rewards_rules (pass_type, points_awarded, description) VALUES
('Daily Pass', 1, 'Earn 1 point for daily pass'),
('Weekly Pass', 7, 'Earn 7 points for weekly pass'),
('Monthly Pass', 35, 'Earn 35 points for monthly pass'),
('Annual Pass', 350, 'Earn 350 points for annual pass'); 