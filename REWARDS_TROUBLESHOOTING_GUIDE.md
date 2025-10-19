# 🔧 Rewards System Troubleshooting Guide

## 🎯 **Common Issues & Solutions**

### ❌ **Issue 1: "Unknown column 'reward_points' in 'field list'"**

#### **Problem**
The `reward_points` column is missing from the users table.

#### **Solutions**

##### **Method 1: Use Fix Script (Recommended)**
1. **Go to**: `http://localhost/buspassmsfull/fix-reward-points-column.php`
2. **Run the script** - It will automatically detect and fix the issue
3. **Verify** the column is added and users are initialized

##### **Method 2: Manual SQL (phpMyAdmin)**
1. **Open phpMyAdmin**
2. **Select your database** (usually `bpmsdb`)
3. **Run this SQL**:
```sql
ALTER TABLE users ADD COLUMN reward_points INT DEFAULT 0 AFTER phone;
UPDATE users SET reward_points = 0 WHERE reward_points IS NULL;
```

##### **Method 3: Complete SQL Setup**
1. **Use the SQL file**: `rewards_system_sql.sql`
2. **Import in phpMyAdmin** or run each command manually
3. **Creates all tables** and adds the missing column

---

### ❌ **Issue 2: "Table 'rewards_rules' doesn't exist"**

#### **Problem**
The rewards system database tables haven't been created.

#### **Solutions**

##### **Quick Fix**
1. **Go to**: `http://localhost/buspassmsfull/quick-setup-rewards.php`
2. **Run the setup** - Creates all required tables
3. **Verify** with the verification script

##### **Manual Setup**
1. **Run the complete SQL** from `rewards_system_sql.sql`
2. **Check tables exist**:
   - `rewards_rules`
   - `rewards_transactions` 
   - `rewards_redemptions`

---

### ❌ **Issue 3: Rewards Pages Not Loading**

#### **Problem**
My Rewards or Manage Rewards pages show errors.

#### **Solutions**

##### **Check Database Connection**
1. **Verify** `includes/dbconnection.php` is working
2. **Test** database connection with other pages
3. **Check** MySQL service is running

##### **Verify File Permissions**
1. **Check** `includes/rewards.php` exists and is readable
2. **Verify** all rewards files are uploaded correctly
3. **Test** file access permissions

##### **Run Verification**
1. **Go to**: `http://localhost/buspassmsfull/verify-rewards-setup.php`
2. **Check** all components are working
3. **Fix** any issues reported

---

## 🔍 **Diagnostic Tools**

### **1. Verification Script**
- **URL**: `http://localhost/buspassmsfull/verify-rewards-setup.php`
- **Purpose**: Comprehensive system check
- **Shows**: Database status, file existence, functionality tests

### **2. Column Fix Script**
- **URL**: `http://localhost/buspassmsfull/fix-reward-points-column.php`
- **Purpose**: Fix missing reward_points column
- **Shows**: Current table structure, fixes issues

### **3. Quick Setup Script**
- **URL**: `http://localhost/buspassmsfull/quick-setup-rewards.php`
- **Purpose**: Complete database setup
- **Creates**: All tables and default data

---

## 📋 **Step-by-Step Fix Process**

### **Complete Fix Procedure**

#### **Step 1: Database Setup**
1. **Run**: `http://localhost/buspassmsfull/fix-reward-points-column.php`
2. **Verify**: reward_points column is added
3. **Check**: All users have 0 points initially

#### **Step 2: Create Tables**
1. **Run**: `http://localhost/buspassmsfull/quick-setup-rewards.php`
2. **Verify**: All 4 tables are created
3. **Check**: Default rules are inserted

#### **Step 3: Verify Setup**
1. **Run**: `http://localhost/buspassmsfull/verify-rewards-setup.php`
2. **Check**: All components show green checkmarks
3. **Test**: RewardsSystem class loads correctly

#### **Step 4: Test Functionality**
1. **Visit**: `http://localhost/buspassmsfull/my-rewards.php`
2. **Check**: Page loads without errors
3. **Verify**: Points display correctly

#### **Step 5: Test Integration**
1. **Visit**: `http://localhost/buspassmsfull/user-dashboard.php`
2. **Check**: Rewards section appears
3. **Verify**: Navigation shows points

---

## 🗄️ **Required Database Structure**

### **Users Table (Modified)**
```sql
-- Must have this column
reward_points INT DEFAULT 0
```

### **Required Tables**
1. **rewards_rules** - Point values for actions
2. **rewards_transactions** - Point earning/spending history  
3. **rewards_redemptions** - Discount redemptions

### **Verification Queries**
```sql
-- Check if reward_points column exists
SHOW COLUMNS FROM users LIKE 'reward_points';

-- Check if rewards tables exist
SHOW TABLES LIKE 'rewards_%';

-- Count reward rules
SELECT COUNT(*) FROM rewards_rules;

-- Check user points
SELECT id, full_name, reward_points FROM users LIMIT 5;
```

---

## 🔗 **File Dependencies**

### **Required Files**
1. **includes/rewards.php** - Core rewards system class
2. **my-rewards.php** - User rewards dashboard
3. **manage-rewards.php** - Admin management panel
4. **Setup scripts** - Database initialization

### **Integration Files**
1. **user-dashboard.php** - Modified for rewards display
2. **apply-pass.php** - Modified for automatic point awarding

### **File Check**
```bash
# Verify files exist
ls -la includes/rewards.php
ls -la my-rewards.php
ls -la manage-rewards.php
```

---

## 🎯 **Testing Checklist**

### **Database Tests**
- [ ] reward_points column exists in users table
- [ ] rewards_rules table exists with 6 default rules
- [ ] rewards_transactions table exists
- [ ] rewards_redemptions table exists
- [ ] All users have reward_points = 0 initially

### **Functionality Tests**
- [ ] My Rewards page loads without errors
- [ ] Manage Rewards page loads without errors
- [ ] User dashboard shows rewards section
- [ ] Apply pass awards points automatically
- [ ] Point redemption works correctly

### **Integration Tests**
- [ ] Navigation shows current points
- [ ] Sidebar rewards widget displays
- [ ] Success messages include points earned
- [ ] Admin panel shows rewards statistics

---

## 🚀 **Quick Recovery Commands**

### **If Everything Fails**
```sql
-- Complete reset and setup
DROP TABLE IF EXISTS rewards_redemptions;
DROP TABLE IF EXISTS rewards_transactions;
DROP TABLE IF EXISTS rewards_rules;
ALTER TABLE users DROP COLUMN IF EXISTS reward_points;

-- Then run the complete setup again
```

### **Emergency Fix**
1. **Delete** all rewards-related files
2. **Re-upload** all rewards system files
3. **Run** `quick-setup-rewards.php`
4. **Verify** with verification script

---

## 📞 **Support Checklist**

### **Before Asking for Help**
1. **Run** all diagnostic scripts
2. **Check** database connection
3. **Verify** file uploads are complete
4. **Test** with fresh browser session
5. **Clear** browser cache

### **Information to Provide**
- **Error messages** (exact text)
- **Browser** and version
- **PHP version** and MySQL version
- **Results** from verification script
- **Database structure** (DESCRIBE users)

---

## ✅ **Success Indicators**

### **When Everything Works**
- ✅ **My Rewards page** loads with point balance
- ✅ **User dashboard** shows rewards in navigation
- ✅ **Apply pass** automatically awards points
- ✅ **Admin panel** shows statistics and rules
- ✅ **Point redemption** processes correctly

### **Expected Behavior**
1. **New user** starts with 0 points
2. **Apply pass** → Earn 50 points automatically
3. **Navigation** shows "My Rewards (50)"
4. **Redemption** works for discounts
5. **Admin** can configure rules and award bulk points

---

## 🎉 **Final Verification**

### **Complete System Test**
1. **Create new user** or use existing
2. **Apply for bus pass** → Should earn 50 points
3. **Check user dashboard** → Should show rewards
4. **Visit My Rewards** → Should show 50 points
5. **Try redemption** → Should work for 100+ points
6. **Admin panel** → Should show statistics

### **Success Confirmation**
When all tests pass, the Rewards System is fully operational and ready for production use!

---

## 🔧 **Emergency Contacts**

If you continue to have issues:
1. **Check** MySQL error logs
2. **Verify** PHP error logs  
3. **Test** database connectivity
4. **Ensure** all files are uploaded correctly
5. **Run** diagnostic scripts in order

**The Rewards System should work perfectly after following this troubleshooting guide!** 🎁✨
