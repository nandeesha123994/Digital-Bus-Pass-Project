-- SQL Script to Add Application ID Column
-- Run this script in phpMyAdmin or MySQL command line

-- Use the correct database
USE bpmsdb;

-- Add the application_id column to bus_pass_applications table
ALTER TABLE bus_pass_applications 
ADD COLUMN application_id VARCHAR(20) UNIQUE AFTER id;

-- Show the updated table structure
DESCRIBE bus_pass_applications;

-- Optional: Update existing records with generated Application IDs
-- Note: You may need to run the PHP script to generate proper Application IDs
-- or manually update records as needed

SELECT 'Application ID column added successfully!' as Status;
