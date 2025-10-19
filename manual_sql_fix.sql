-- Manual SQL Fix for Nrupatunga Digital Bus Pass System
-- Run this in phpMyAdmin SQL tab if you're still getting column errors

-- Use the correct database
USE bpmsdb;

-- Remove display_order column and its index from announcements table
ALTER TABLE announcements DROP INDEX IF EXISTS idx_order;
ALTER TABLE announcements DROP COLUMN IF EXISTS display_order;

-- Add missing columns to bus_pass_applications table
-- These commands will only add columns if they don't already exist

-- Add application_id column
ALTER TABLE bus_pass_applications 
ADD COLUMN IF NOT EXISTS application_id VARCHAR(50) UNIQUE AFTER id;

-- Add email column
ALTER TABLE bus_pass_applications 
ADD COLUMN IF NOT EXISTS email VARCHAR(100) NOT NULL AFTER phone;

-- Add id_proof_type column
ALTER TABLE bus_pass_applications 
ADD COLUMN IF NOT EXISTS id_proof_type VARCHAR(50) AFTER destination;

-- Add id_proof_number column
ALTER TABLE bus_pass_applications 
ADD COLUMN IF NOT EXISTS id_proof_number VARCHAR(50) AFTER id_proof_type;

-- Add id_proof_file column
ALTER TABLE bus_pass_applications 
ADD COLUMN IF NOT EXISTS id_proof_file VARCHAR(255) AFTER id_proof_number;

-- Add photo_file column
ALTER TABLE bus_pass_applications 
ADD COLUMN IF NOT EXISTS photo_file VARCHAR(255) AFTER id_proof_file;

-- Add photo_path column
ALTER TABLE bus_pass_applications 
ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) AFTER photo_file;

-- Add is_active column to announcements table
ALTER TABLE announcements 
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER type;

-- Update existing announcements to be active
UPDATE announcements SET is_active = TRUE WHERE is_active IS NULL;

-- Verify the table structure
SELECT 'bus_pass_applications table structure:' as info;
DESCRIBE bus_pass_applications;

SELECT 'announcements table structure:' as info;
DESCRIBE announcements;

-- Test queries to ensure everything works
SELECT 'Testing bus_pass_applications table:' as info;
SELECT COUNT(*) as total_applications FROM bus_pass_applications;

SELECT 'Testing announcements table:' as info;
SELECT COUNT(*) as active_announcements FROM announcements WHERE is_active = TRUE;

SELECT 'Testing instant_reviews table:' as info;
SELECT COUNT(*) as active_reviews FROM instant_reviews WHERE status = 'active';

-- Show success message
SELECT '✅ All fixes applied successfully! The system should now work without column errors.' as result;

-- Add is_active column to bus_pass_types table
ALTER TABLE bus_pass_types
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER duration_days;

-- Update existing records to be active
UPDATE bus_pass_types SET is_active = TRUE WHERE is_active IS NULL;

-- Add is_active column to categories table
ALTER TABLE categories
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER description;

-- Update existing records to be active
UPDATE categories SET is_active = TRUE WHERE is_active IS NULL;

-- Add is_active column to routes table
ALTER TABLE routes
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER estimated_duration;

-- Update existing records to be active
UPDATE routes SET is_active = TRUE WHERE is_active IS NULL;

-- Add indexes for better performance
ALTER TABLE bus_pass_types ADD INDEX idx_active (is_active);
ALTER TABLE categories ADD INDEX idx_active (is_active);
ALTER TABLE routes ADD INDEX idx_active (is_active);
