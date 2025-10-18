-- Create Instant Reviews Table for User Review Feature
-- Run this SQL in phpMyAdmin or MySQL command line

-- Create instant_reviews table
CREATE TABLE IF NOT EXISTS instant_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comments TEXT NOT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_public_created (is_public, created_at DESC),
    INDEX idx_user_created (user_id, created_at DESC)
);

-- Verify the table was created
DESCRIBE instant_reviews;

-- Check if table exists
SHOW TABLES LIKE 'instant_reviews';

-- Insert sample data for demonstration
INSERT INTO instant_reviews (user_id, username, rating, comments, created_at) VALUES
(1, 'John Doe', 5, 'Excellent bus pass management system! Very user-friendly and efficient. The online application process is smooth and saves a lot of time.', NOW() - INTERVAL 5 DAY),
(2, 'Priya Sharma', 4, 'Great service overall. The digital pass system is convenient and the support team is responsive. Minor improvements needed in mobile interface.', NOW() - INTERVAL 10 DAY),
(3, 'Rajesh Kumar', 5, 'Outstanding experience! Quick approval process and easy to use dashboard. Highly recommend this system for all bus pass needs.', NOW() - INTERVAL 15 DAY),
(4, 'Anita Reddy', 4, 'Good system with modern features. The application tracking is very helpful. Would like to see more payment options in the future.', NOW() - INTERVAL 20 DAY),
(5, 'Vikram Singh', 5, 'Perfect solution for bus pass management. Clean interface, fast processing, and excellent customer service. Very satisfied!', NOW() - INTERVAL 25 DAY);

-- Check the inserted data
SELECT * FROM instant_reviews ORDER BY created_at DESC;

-- Get statistics
SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as average_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_count,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star_count,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star_count,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star_count,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star_count
FROM instant_reviews 
WHERE is_public = 1;

-- End of script
