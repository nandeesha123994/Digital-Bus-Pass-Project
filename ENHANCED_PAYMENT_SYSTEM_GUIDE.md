# Enhanced Bus Pass Management System - Payment & Email Integration Guide

## 🚀 New Features Added

### 1. **Enhanced Payment System**
- ✅ Real Stripe payment integration
- ✅ Real Razorpay payment integration  
- ✅ Demo payment for testing
- ✅ Payment status tracking
- ✅ Transaction history
- ✅ Payment receipts

### 2. **Email Notification System**
- ✅ Application confirmation emails
- ✅ Payment confirmation emails
- ✅ Status update notifications
- ✅ Pass activation emails
- ✅ Professional HTML email templates

### 3. **Enhanced Admin Dashboard**
- ✅ Payment management
- ✅ Transaction tracking
- ✅ Automatic email notifications
- ✅ Pass validity management
- ✅ Enhanced application overview

### 4. **Improved User Experience**
- ✅ Enhanced user dashboard
- ✅ Payment status tracking
- ✅ Pass validity display
- ✅ Transaction history
- ✅ Better visual design

## 📧 Email Configuration

### Option 1: Using Gmail SMTP (Recommended for Testing)
1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
3. **Update config.php**:
   ```php
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USERNAME', 'your_email@gmail.com');
   define('SMTP_PASSWORD', 'your_16_digit_app_password');
   define('SMTP_ENCRYPTION', 'tls');
   define('ADMIN_EMAIL', 'your_email@gmail.com');
   define('ENABLE_EMAIL_NOTIFICATIONS', true);
   ```

### Option 2: Using Local Mail Server (XAMPP)
1. **Configure php.ini** (in XAMPP/php/php.ini):
   ```ini
   [mail function]
   SMTP = localhost
   smtp_port = 25
   sendmail_from = admin@localhost
   ```
2. **Install local mail server** like hMailServer or Mercury

## 💳 Payment Gateway Setup

### Stripe Integration
1. **Create Stripe Account**: https://stripe.com
2. **Get API Keys**:
   - Dashboard → Developers → API keys
   - Copy Publishable key and Secret key
3. **Update config.php**:
   ```php
   define('STRIPE_PUBLIC_KEY', 'pk_test_your_publishable_key');
   define('STRIPE_SECRET_KEY', 'sk_test_your_secret_key');
   ```

### Razorpay Integration (For Indian Users)
1. **Create Razorpay Account**: https://razorpay.com
2. **Get API Keys**:
   - Dashboard → Settings → API Keys
   - Generate Test/Live keys
3. **Update config.php**:
   ```php
   define('RAZORPAY_KEY_ID', 'rzp_test_your_key_id');
   define('RAZORPAY_KEY_SECRET', 'your_key_secret');
   ```

## 🛠️ Installation Steps

### 1. Database Setup
```bash
# Visit in browser:
http://localhost/buspassmsfull/setup_database.php

# Or manually import:
# - Open phpMyAdmin
# - Import users_table.sql
```

### 2. Configure Email Settings
```php
// Edit includes/config.php
define('ADMIN_EMAIL', 'your_email@gmail.com');
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
define('ENABLE_EMAIL_NOTIFICATIONS', true);
```

### 3. Test Email System
```bash
# Visit in browser:
http://localhost/buspassmsfull/test_email.php
```

### 4. Configure Payment Gateways
- Add your Stripe/Razorpay keys to config.php
- Test with demo payment first

## 📱 Usage Workflow

### For Users:
1. **Register/Login** → Create account
2. **Apply for Pass** → Fill form, upload photo
3. **Receive Email** → Application confirmation
4. **Make Payment** → Choose payment method
5. **Get Confirmation** → Payment success email
6. **Track Status** → View in dashboard
7. **Receive Pass** → Activation email when approved

### For Admins:
1. **Login** → admin@buspass.com / admin123
2. **View Applications** → See all applications with payment status
3. **Process Applications** → Approve/Reject with remarks
4. **Send Notifications** → Automatic emails sent
5. **Manage Payments** → View transaction details

## 🧪 Testing Guide

### Test Email System:
1. Register a new user with your email
2. Apply for a bus pass
3. Check email for confirmation
4. Complete payment (use demo)
5. Check email for payment confirmation
6. Admin: Approve application
7. Check email for approval notification

### Test Payment System:
1. **Demo Payment**: Always works, no real money
2. **Stripe Test**: Use test card 4242424242424242
3. **Razorpay Test**: Use test credentials

## 🔧 Troubleshooting

### Email Issues:
- Check SMTP credentials
- Verify Gmail app password
- Check spam folder
- Enable less secure apps (if needed)

### Payment Issues:
- Verify API keys
- Check test/live mode
- Ensure HTTPS for production
- Check browser console for errors

### Database Issues:
- Run setup_database.php
- Check table existence
- Verify foreign key constraints

## 📁 New Files Added:
- `includes/email.php` - Email service class
- `ENHANCED_PAYMENT_SYSTEM_GUIDE.md` - This guide
- Enhanced `payment.php` - Real payment integration
- Enhanced `admin-dashboard.php` - Payment management
- Enhanced `user-dashboard.php` - Better UX
- Enhanced `apply-pass.php` - Email notifications

## 🔐 Security Notes:
- Never commit real API keys to version control
- Use environment variables in production
- Enable HTTPS for payment processing
- Validate all user inputs
- Use prepared statements (already implemented)

## 📞 Support:
If you encounter issues:
1. Check error logs in `logs/error.log`
2. Enable debug mode in config.php
3. Test individual components
4. Check database connections
5. Verify email/payment configurations

## 🎉 Success!
Your enhanced bus pass management system now includes:
- Professional payment processing
- Automated email notifications
- Better admin management
- Improved user experience
- Real-world ready features
