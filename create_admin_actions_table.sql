-- Create admin_actions table for Activity Log
CREATE TABLE IF NOT EXISTS admin_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id VARCHAR(100) NOT NULL,
    admin_name VARCHAR(255) NOT NULL,
    application_id INT NOT NULL,
    applicant_name VARCHAR(255) NOT NULL,
    action VARCHAR(100) NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    remarks TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_admin_id (admin_id),
    INDEX idx_application_id (application_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action)
);

-- Insert sample data for testing (optional)
INSERT INTO admin_actions (admin_id, admin_name, application_id, applicant_name, action, old_status, new_status, remarks, timestamp, ip_address) VALUES
('admin', 'System Administrator', 1, 'John Doe', 'Status Update', 'Pending', 'Approved', 'Application meets all requirements', '2024-12-15 10:30:00', '127.0.0.1'),
('admin', 'System Administrator', 2, 'Jane Smith', 'Status Update', 'Pending', 'Rejected', 'ID proof document is not clear', '2024-12-15 11:15:00', '127.0.0.1'),
('admin', 'System Administrator', 3, 'Bob Johnson', 'Bulk Action', 'Pending', 'Approved', 'Bulk approved by admin', '2024-12-15 14:20:00', '127.0.0.1'),
('admin', 'System Administrator', 4, 'Alice Brown', 'Bulk Action', 'Pending', 'Approved', 'Bulk approved by admin', '2024-12-15 14:20:00', '127.0.0.1'),
('admin', 'System Administrator', 5, 'Charlie Wilson', 'Status Update', 'Approved', 'Rejected', 'Payment verification failed', '2024-12-15 16:45:00', '127.0.0.1');
