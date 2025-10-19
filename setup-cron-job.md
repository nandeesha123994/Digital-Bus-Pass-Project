# 🔔 Notification System - Cron Job Setup Guide

## Overview
This guide explains how to set up automated daily notifications for bus pass expiry warnings.

## 📋 Cron Job Configuration

### Linux/Unix Systems

1. **Open crontab editor:**
   ```bash
   crontab -e
   ```

2. **Add daily notification check (runs at 8:00 AM every day):**
   ```bash
   0 8 * * * /usr/bin/php /path/to/your/buspassmsfull/daily-notification-checker.php
   ```

3. **Alternative: Run every 6 hours:**
   ```bash
   0 */6 * * * /usr/bin/php /path/to/your/buspassmsfull/daily-notification-checker.php
   ```

### Windows Task Scheduler

1. **Open Task Scheduler**
2. **Create Basic Task**
3. **Set trigger:** Daily at 8:00 AM
4. **Set action:** Start a program
5. **Program:** `C:\xampp\php\php.exe`
6. **Arguments:** `C:\xampp\htdocs\buspassmsfull\daily-notification-checker.php`

### Web Hosting (cPanel)

1. **Go to cPanel → Cron Jobs**
2. **Add new cron job:**
   - **Minute:** 0
   - **Hour:** 8
   - **Day:** *
   - **Month:** *
   - **Weekday:** *
   - **Command:** `/usr/bin/php /home/username/public_html/buspassmsfull/daily-notification-checker.php`

## 🔧 Configuration Options

### Email Settings
Update `includes/email-functions.php` with your SMTP settings:

```php
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-password');
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'Bus Pass System');
```

### Notification Timing
Default settings:
- **Warning notifications:** 7 days before expiry
- **Urgent notifications:** 3 days before expiry
- **Expired notifications:** After expiry date

To modify these settings, update the `notification_settings` table or modify the checker script.

## 📊 Monitoring & Logs

### Log Files
- **Notification logs:** `logs/notification-checker.log`
- **Email debug logs:** `logs/email-debug.log`

### Database Tables
- **notifications:** In-app notifications
- **notification_log:** Delivery tracking
- **notification_settings:** User preferences

## 🧪 Testing

### Manual Testing
1. Run the checker manually:
   ```bash
   php daily-notification-checker.php
   ```

2. Check logs for results:
   ```bash
   tail -f logs/notification-checker.log
   ```

### Web Testing
Visit: `http://yourdomain.com/buspassmsfull/daily-notification-checker.php`

## 🔒 Security Considerations

1. **Restrict web access** to the checker script in production
2. **Use environment variables** for sensitive configuration
3. **Implement rate limiting** for email sending
4. **Monitor log files** for errors and abuse

## 📧 Email Templates

Email templates are stored in the `email_templates` table and can be customized:

- **expiry_warning_7_days:** 7-day warning template
- **expiry_urgent_3_days:** 3-day urgent warning template

## 🎯 Production Deployment

### Recommended Schedule
```bash
# Check for notifications every day at 8 AM
0 8 * * * /usr/bin/php /path/to/daily-notification-checker.php

# Optional: Additional check at 6 PM for urgent notifications
0 18 * * * /usr/bin/php /path/to/daily-notification-checker.php
```

### Performance Optimization
- **Batch processing:** Process notifications in batches
- **Email throttling:** Limit emails per hour
- **Database indexing:** Ensure proper indexes on date columns

## 🆘 Troubleshooting

### Common Issues

1. **Cron job not running:**
   - Check cron service status
   - Verify file permissions
   - Check PHP path

2. **Emails not sending:**
   - Verify SMTP settings
   - Check email logs
   - Test email configuration

3. **Database errors:**
   - Check database connection
   - Verify table structure
   - Review error logs

### Debug Mode
Enable debug mode in `includes/email-functions.php`:
```php
define('EMAIL_DEBUG_MODE', true);
```

This will log emails instead of sending them for testing purposes.

## 📞 Support

For issues or questions:
1. Check log files first
2. Review database tables
3. Test manually before automation
4. Monitor system resources

---

**Note:** Always test the notification system thoroughly before deploying to production!
