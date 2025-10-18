-- Create categories table for Bus Pass Management System
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default categories (use INSERT IGNORE to avoid duplicates)
INSERT IGNORE INTO categories (category_name, description) VALUES
('KSRTC', 'Karnataka State Road Transport Corporation - State government buses'),
('MSRTC', 'Maharashtra State Road Transport Corporation - State government buses'),
('BMTC', 'Bangalore Metropolitan Transport Corporation - City buses'),
('TSRTC', 'Telangana State Road Transport Corporation - State government buses'),
('APSRTC', 'Andhra Pradesh State Road Transport Corporation - State government buses'),
('Private', 'Private bus operators and services');

-- Check if category_id column exists before adding it
-- (Run this only if the column doesn't exist)
-- ALTER TABLE bus_pass_applications ADD COLUMN category_id INT DEFAULT NULL;

-- Add foreign key constraint (run after adding column)
-- ALTER TABLE bus_pass_applications ADD CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

-- Update existing applications to have a default category (BMTC)
-- UPDATE bus_pass_applications SET category_id = (SELECT id FROM categories WHERE category_name = 'BMTC' LIMIT 1) WHERE category_id IS NULL;
