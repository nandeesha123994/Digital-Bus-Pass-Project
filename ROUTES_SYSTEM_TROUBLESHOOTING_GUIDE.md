# 🔧 Routes Management System - Troubleshooting Guide

## 🚨 **Common Issues & Solutions**

### **Issue 1: "Table 'bpmsdb.routes' doesn't exist"**

**Problem:** The routes table hasn't been created in the database.

**Solution:**
1. **Run Setup Script**: Go to `http://localhost/buspassmsfull/setup-routes-table.php`
2. **Alternative**: Use `http://localhost/buspassmsfull/create-routes-table.php`
3. **Manual Setup**: Run the SQL commands directly in phpMyAdmin

**Quick Fix:**
```sql
-- Run this in phpMyAdmin SQL tab
CREATE TABLE routes (
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
```

---

### **Issue 2: Admin Login Required**

**Problem:** Getting redirected to admin login page.

**Solution:**
1. **Login Credentials:**
   - Email: `admin@buspass.com`
   - Password: `admin123`
2. **Access**: Go to `http://localhost/buspassmsfull/admin-login.php`

---

### **Issue 3: XAMPP Not Running**

**Problem:** Database connection errors.

**Solution:**
1. **Start XAMPP**: Open XAMPP Control Panel
2. **Start Services**: 
   - ✅ Apache (should be green/running)
   - ✅ MySQL (should be green/running)
3. **Check Ports**: Ensure no conflicts on ports 80 and 3306

---

### **Issue 4: Database 'bpmsdb' Doesn't Exist**

**Problem:** Database not found error.

**Solution:**
1. **Open phpMyAdmin**: `http://localhost/phpmyadmin`
2. **Create Database**: 
   - Click "New" in left sidebar
   - Enter database name: `bpmsdb`
   - Click "Create"
3. **Run Setup**: Go to `http://localhost/buspassmsfull/setup_database.php`

---

### **Issue 5: Dropdowns Not Loading**

**Problem:** Source/destination dropdowns are empty.

**Solution:**
1. **Check Routes Data**: Ensure routes table has data
2. **Test API**: Go to `http://localhost/buspassmsfull/get-route-info.php?action=get_sources`
3. **Browser Console**: Check for JavaScript errors (F12 → Console)

---

### **Issue 6: Route Information Not Displaying**

**Problem:** Route details don't show when source/destination selected.

**Solution:**
1. **Check JavaScript**: Ensure no console errors
2. **Test API**: Try `http://localhost/buspassmsfull/get-route-info.php?action=find_route&source=Bangalore Central&destination=Electronic City`
3. **Clear Cache**: Refresh page with Ctrl+F5

---

## 🛠️ **Diagnostic Tools**

### **1. System Status Checker**
**URL:** `http://localhost/buspassmsfull/check-routes-status.php`
**Purpose:** Comprehensive system health check

### **2. Database Test**
**URL:** `http://localhost/buspassmsfull/test_database.php`
**Purpose:** Check database connectivity and tables

### **3. API Test**
**URLs:**
- `http://localhost/buspassmsfull/get-route-info.php?action=get_sources`
- `http://localhost/buspassmsfull/get-route-info.php?action=get_destinations`

---

## 📋 **Setup Checklist**

### **Prerequisites:**
- [ ] XAMPP installed and running
- [ ] Apache service started (green in XAMPP)
- [ ] MySQL service started (green in XAMPP)
- [ ] Database `bpmsdb` exists

### **Routes System Setup:**
- [ ] Routes table created
- [ ] Sample data inserted
- [ ] Database indexes added
- [ ] Admin login working
- [ ] API endpoints responding

### **Testing Steps:**
1. [ ] Run `setup-routes-table.php`
2. [ ] Login to admin panel
3. [ ] Access `manage-routes.php`
4. [ ] Test `apply-pass.php` dropdowns
5. [ ] Verify route information display

---

## 🔍 **Manual Verification**

### **Check Database in phpMyAdmin:**
1. Open `http://localhost/phpmyadmin`
2. Select `bpmsdb` database
3. Look for `routes` table
4. Check if table has data (should show 15+ routes)

### **Check File Permissions:**
Ensure these files exist and are readable:
- `manage-routes.php`
- `get-route-info.php`
- `setup-routes-table.php`
- `includes/dbconnection.php`

---

## 🚀 **Quick Recovery Steps**

### **If Everything Fails:**
1. **Reset Database:**
   ```sql
   DROP TABLE IF EXISTS routes;
   ```
2. **Run Setup:** `http://localhost/buspassmsfull/setup-routes-table.php`
3. **Clear Browser Cache:** Ctrl+Shift+Delete
4. **Restart XAMPP:** Stop and start Apache/MySQL

### **Emergency Backup:**
If you need to start fresh:
1. Backup existing database
2. Drop and recreate `bpmsdb`
3. Run `setup_database.php`
4. Run `setup-routes-table.php`

---

## 📞 **Support Resources**

### **Log Files to Check:**
- XAMPP Error Logs: `xampp/apache/logs/error.log`
- PHP Error Logs: Check `error_reporting` in PHP
- Browser Console: F12 → Console tab

### **Common File Paths:**
- **XAMPP Directory:** `C:\xampp\` (Windows) or `/opt/lampp/` (Linux)
- **Web Root:** `C:\xampp\htdocs\buspassmsfull\`
- **Database Config:** `includes/dbconnection.php`

### **Useful Commands:**
```sql
-- Check if routes table exists
SHOW TABLES LIKE 'routes';

-- Count routes
SELECT COUNT(*) FROM routes;

-- Show table structure
DESCRIBE routes;

-- Show sample data
SELECT * FROM routes LIMIT 5;
```

---

## ✅ **Success Indicators**

### **System Working Correctly When:**
- ✅ Admin can access `manage-routes.php` without errors
- ✅ Routes table shows in phpMyAdmin with data
- ✅ Application form dropdowns populate with locations
- ✅ Route information displays when source/destination selected
- ✅ API endpoints return JSON responses
- ✅ No JavaScript console errors

### **Performance Benchmarks:**
- **Page Load:** < 2 seconds
- **Dropdown Population:** < 1 second
- **Route Matching:** < 500ms
- **Database Queries:** < 100ms

---

## 🎯 **Final Verification**

### **Complete Test Sequence:**
1. **Admin Test:**
   - Login to admin panel
   - Add a new route
   - Edit existing route
   - Delete a route

2. **User Test:**
   - Open application form
   - Select source from dropdown
   - Select destination from dropdown
   - Verify route information appears

3. **API Test:**
   - Test all API endpoints
   - Verify JSON responses
   - Check error handling

**If all tests pass, the Routes Management System is fully operational!** 🎉
