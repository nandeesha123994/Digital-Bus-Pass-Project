-- Create Reviews Table for User Review Feature
-- Run this SQL in phpMyAdmin or MySQL command line

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    review_text TEXT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    status ENUM('pending', 'approved', 'hidden') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Verify the table was created
DESCRIBE reviews;

-- Check if table exists
SHOW TABLES LIKE 'reviews';

-- Sample data (optional - remove if you don't want sample data)
-- INSERT INTO reviews (user_id, review_text, rating, status) VALUES
-- (1, 'Great bus pass management system! Very easy to use and efficient.', 5, 'approved'),
-- (2, 'The application process was smooth and the support team was helpful.', 4, 'approved'),
-- (3, 'Good system overall, but could use some improvements in the interface.', 3, 'approved');

-- End of script
