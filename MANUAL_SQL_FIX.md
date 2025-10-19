# 🔧 Manual SQL Fix for Reward Points Column

## 🎯 **If the automated scripts don't work, follow these manual steps:**

### **Method 1: phpMyAdmin (Recommended)**

#### **Step 1: Open phpMyAdmin**
1. Go to `http://localhost/phpmyadmin`
2. Login with your MySQL credentials
3. Select your database (usually `bpmsdb`)

#### **Step 2: Add the Column**
1. Click on the **"users"** table in the left sidebar
2. Click on the **"Structure"** tab
3. Scroll down and click **"Add"** (or "Add column")
4. Fill in the details:
   - **Name**: `reward_points`
   - **Type**: `INT`
   - **Length/Values**: `11`
   - **Default**: `0`
   - **Null**: Uncheck (NOT NULL)
5. Click **"Save"**

#### **Step 3: Initialize Existing Users**
1. Click on the **"SQL"** tab
2. Run this command:
```sql
UPDATE users SET reward_points = 0 WHERE reward_points IS NULL;
```

### **Method 2: Direct SQL Commands**

#### **Copy and paste these commands one by one in phpMyAdmin SQL tab:**

```sql
-- Step 1: Add the reward_points column
ALTER TABLE users ADD COLUMN reward_points INT(11) DEFAULT 0;

-- Step 2: Update existing users to have 0 points
UPDATE users SET reward_points = 0 WHERE reward_points IS NULL;

-- Step 3: Verify the column was added
DESCRIBE users;

-- Step 4: Check sample data
SELECT id, full_name, reward_points FROM users LIMIT 5;
```

### **Method 3: Complete Rewards System Setup**

#### **If you want to set up everything at once:**

```sql
-- 1. Add reward_points column to users table
ALTER TABLE users ADD COLUMN reward_points INT(11) DEFAULT 0;

-- 2. Update existing users
UPDATE users SET reward_points = 0 WHERE reward_points IS NULL;

-- 3. Create rewards_rules table
CREATE TABLE IF NOT EXISTS rewards_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action_type VARCHAR(50) NOT NULL UNIQUE,
    points_awarded INT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Create rewards_transactions table
CREATE TABLE IF NOT EXISTS rewards_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    points_earned INT NOT NULL,
    points_redeemed INT DEFAULT 0,
    reference_id INT NULL,
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Create rewards_redemptions table
CREATE TABLE IF NOT EXISTS rewards_redemptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    redemption_type VARCHAR(50) NOT NULL,
    points_used INT NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    application_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    admin_remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES bus_pass_applications(id) ON DELETE SET NULL
);

-- 6. Insert default reward rules
INSERT IGNORE INTO rewards_rules (action_type, points_awarded, description) VALUES
('pass_application', 50, 'Points earned for applying for a new bus pass'),
('pass_renewal', 30, 'Points earned for renewing an existing bus pass'),
('referral_signup', 100, 'Points earned when a referred user signs up'),
('referral_first_pass', 150, 'Points earned when a referred user applies for their first pass'),
('payment_completion', 25, 'Points earned for completing payment on time'),
('profile_completion', 20, 'Points earned for completing profile information');
```

---

## 🔍 **Verification Steps**

### **After running the SQL commands:**

#### **1. Check if column exists:**
```sql
SHOW COLUMNS FROM users LIKE 'reward_points';
```
**Expected result**: Should show one row with the reward_points column

#### **2. Check users have points:**
```sql
SELECT id, full_name, reward_points FROM users LIMIT 5;
```
**Expected result**: All users should have reward_points = 0

#### **3. Check rewards tables:**
```sql
SHOW TABLES LIKE 'rewards_%';
```
**Expected result**: Should show 3 tables (rewards_rules, rewards_transactions, rewards_redemptions)

#### **4. Check reward rules:**
```sql
SELECT COUNT(*) as rule_count FROM rewards_rules;
```
**Expected result**: Should show 6 rules

---

## 🚨 **Troubleshooting**

### **If you get "Table doesn't exist" error:**
1. Make sure you're in the correct database
2. Check if the `users` table exists: `SHOW TABLES LIKE 'users';`
3. If users table doesn't exist, you need to set up the basic bus pass system first

### **If you get "Column already exists" error:**
1. The column might already exist but with NULL values
2. Run: `UPDATE users SET reward_points = 0 WHERE reward_points IS NULL;`
3. Check: `SELECT COUNT(*) FROM users WHERE reward_points IS NULL;`

### **If you get permission errors:**
1. Make sure you're logged in as root or a user with ALTER privileges
2. Try logging in with admin credentials
3. Contact your hosting provider if on shared hosting

---

## ✅ **Success Indicators**

### **When everything is working:**
1. **Column exists**: `DESCRIBE users;` shows reward_points column
2. **No NULL values**: All users have reward_points = 0 or higher
3. **Tables created**: All 4 rewards tables exist
4. **Rules inserted**: 6 default reward rules exist
5. **Pages load**: My Rewards and Manage Rewards pages work without errors

---

## 🎯 **After Manual Fix**

### **Test these URLs:**
1. **My Rewards**: `http://localhost/buspassmsfull/my-rewards.php`
2. **User Dashboard**: `http://localhost/buspassmsfull/user-dashboard.php`
3. **Admin Rewards**: `http://localhost/buspassmsfull/manage-rewards.php`
4. **Verification**: `http://localhost/buspassmsfull/verify-rewards-setup.php`

### **Expected behavior:**
- ✅ No "Unknown column" errors
- ✅ My Rewards page shows 0 points
- ✅ User dashboard has rewards section
- ✅ Apply pass awards points automatically
- ✅ Admin panel shows statistics

---

## 📞 **If Manual Fix Doesn't Work**

### **Last resort options:**

#### **1. Database Reset**
If you're comfortable losing existing data:
```sql
DROP DATABASE bpmsdb;
CREATE DATABASE bpmsdb;
-- Then re-import your database structure
```

#### **2. Alternative Column Addition**
Try different syntax:
```sql
ALTER TABLE users ADD reward_points INT DEFAULT 0;
-- OR
ALTER TABLE users ADD COLUMN reward_points INTEGER DEFAULT 0;
-- OR
ALTER TABLE users ADD reward_points SMALLINT DEFAULT 0;
```

#### **3. Check Database Engine**
```sql
SHOW CREATE TABLE users;
```
Make sure the table engine supports ALTER operations.

---

## 🎉 **Final Result**

After successfully running the manual SQL commands, the Rewards System will be fully operational with:

1. ✅ **reward_points column** in users table
2. ✅ **All rewards tables** created and configured
3. ✅ **Default reward rules** inserted and active
4. ✅ **User interface** working without errors
5. ✅ **Admin panel** fully functional

**The manual SQL fix will resolve the "Unknown column 'reward_points'" error permanently!** 🎁✨
