# Bus Pass Management System - Database Setup Instructions

## Problem
You're getting the error: `Table 'bpmsdb.bus_pass_applications' doesn't exist`

This happens because the database tables haven't been created yet.

## Solution Options

### Option 1: Automatic Setup (Recommended)
1. Open your browser and go to: `http://localhost/buspassmsfull/setup_database.php`
2. This will automatically create all required tables
3. After setup, test with: `http://localhost/buspassmsfull/test_database.php`

### Option 2: Manual Setup via phpMyAdmin
1. Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`
2. Create a new database called `bpmsdb` (if it doesn't exist)
3. Select the `bpmsdb` database
4. Go to the "Import" tab
5. Choose the file `users_table.sql` from your project folder
6. Click "Go" to execute the SQL

### Option 3: Manual Setup via MySQL Command Line
1. Open Command Prompt as Administrator
2. Navigate to your XAMPP MySQL bin folder:
   ```
   cd C:\xampp\mysql\bin
   ```
3. Connect to MySQL:
   ```
   mysql -u root -p
   ```
4. Create and use the database:
   ```sql
   CREATE DATABASE IF NOT EXISTS bpmsdb;
   USE bpmsdb;
   ```
5. Execute the SQL file:
   ```
   source C:\Users\Nandeesha M\Downloads\buspassmsfull\users_table.sql
   ```

## Required Tables
The system needs these tables:
- `users` - User accounts
- `bus_pass_types` - Types of bus passes (Daily, Weekly, Monthly, Annual)
- `bus_pass_applications` - Bus pass applications
- `payments` - Payment records
- `settings` - System settings

## Verification
After setup, verify everything works:
1. Go to: `http://localhost/buspassmsfull/test_database.php`
2. All tables should show as "exists" with green checkmarks
3. Try accessing: `http://localhost/buspassmsfull/user-dashboard.php`

## Default Credentials
- **Admin Login**: admin@buspass.com / admin123
- **User Registration**: Create new account at register.php

## Troubleshooting
If you still get errors:
1. Check XAMPP is running (Apache + MySQL)
2. Verify database name is `bpmsdb` in `includes/dbconnection.php`
3. Make sure MySQL is running on port 3306
4. Check MySQL error logs in XAMPP control panel

## File Structure
```
buspassmsfull/
├── includes/
│   ├── dbconnection.php (database connection)
│   └── config.php (configuration)
├── users_table.sql (database schema)
├── setup_database.php (automatic setup)
├── test_database.php (verification)
└── user-dashboard.php (the file causing the error)
```
