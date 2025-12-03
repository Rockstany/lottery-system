# 🚀 System Monitoring - Quick Setup Guide

## Installation Steps (5 Minutes)

### Step 1: Import Database Tables ✅

Run this SQL file in your MySQL database:

```bash
File: database/migrations/add_system_monitoring_tables.sql
```

**Via Command Line:**
```bash
mysql -u your_username -p your_database < database/migrations/add_system_monitoring_tables.sql
```

**Via phpMyAdmin:**
1. Login to phpMyAdmin
2. Select your database
3. Go to "Import" tab
4. Choose file: `database/migrations/add_system_monitoring_tables.sql`
5. Click "Go"

**Expected Result:** 5 new tables created:
- `system_logs`
- `alert_notifications`
- `failed_login_attempts`
- `database_connection_logs`
- `system_health_metrics`

---

### Step 2: Configure Admin Email ✅

Edit `config/config.php` and add:

```php
define('ADMIN_EMAIL', 'your-email@example.com');
```

This email will receive critical alerts.

---

### Step 3: Create Storage Directory ✅

Create a directory for log cleanup tracking:

**Linux/Mac:**
```bash
mkdir -p storage
chmod 755 storage
```

**Windows:**
```bash
mkdir storage
```

---

### Step 4: Set Up Cron Job (Optional but Recommended) ✅

**What it does:** Monitors disk space, database, and cleans old logs every 5 minutes.

**Linux/Mac:**
```bash
crontab -e
```

Add this line:
```
*/5 * * * * /usr/bin/php /full/path/to/your/project/cron/monitor-system-health.php
```

Replace `/full/path/to/your/project/` with actual path.

**To find PHP path:**
```bash
which php
```

**Windows (Task Scheduler):**
1. Open Task Scheduler
2. Create Basic Task
3. Trigger: Every 5 minutes
4. Action: Start a program
5. Program: `C:\php\php.exe`
6. Arguments: `C:\full\path\to\cron\monitor-system-health.php`

---

### Step 5: Access Dashboard ✅

1. Login as **admin** user
2. Navigate to: `https://yoursite.com/public/admin/system-health.php`

You should see:
- ✅ System health cards
- ✅ Recent alerts
- ✅ System logs
- ✅ Last 24 hours statistics

---

## Testing the System

### Test 1: Failed Login Monitoring

1. Go to login page
2. Enter wrong password 6 times
3. Go to System Health Dashboard
4. You should see "Failed Login" alert

### Test 2: Manual Error Logging

Create a test file: `test-monitoring.php`

```php
<?php
require_once 'config/config.php';
require_once 'classes/SystemLogger.php';

$logger = new SystemLogger();

// Test error logging
$logger->logError('Test error message');
$logger->logWarning('Test warning message');
$logger->logCritical('Test critical error - should send email!');

echo "Check System Health Dashboard and your email!";
```

Visit: `https://yoursite.com/test-monitoring.php`

Check:
1. System Health Dashboard → Recent Logs
2. Your email for critical alert

### Test 3: Cron Job (Manual Run)

```bash
php cron/monitor-system-health.php
```

Expected output:
```
[2025-12-03 14:30:00] Starting system health monitoring...
Checking disk space...
  ✅ Disk space OK: 45.2% used
Testing database connection...
  ✅ Database connection OK (12.5ms)
...
```

---

## Verification Checklist

- [ ] 5 database tables created
- [ ] ADMIN_EMAIL configured in config.php
- [ ] Storage directory created
- [ ] Cron job set up (optional)
- [ ] Can access system-health.php as admin
- [ ] Failed login test works
- [ ] Error logging test works
- [ ] Email alerts received for critical issues

---

## Quick Reference

**Dashboard URL:** `/public/admin/system-health.php`

**Cron Schedule:** Every 5 minutes

**Log Retention:** 30 days

**Failed Login Threshold:** 5 attempts in 15 minutes

**Disk Space Alerts:**
- Warning: 80% used
- Critical: 90% used

**Email Alerts Sent For:**
- Critical errors
- Database failures
- Disk space >90%
- 5+ failed logins in 15 minutes

---

## Support

If you encounter issues:

1. Check PHP error logs
2. Verify database connection
3. Ensure SystemLogger.php is loaded
4. Check email server configuration
5. Review MONITORING_DOCUMENTATION.md for details

---

**Setup Complete!** 🎉

Your system is now monitored 24/7. Check the dashboard regularly for alerts and system health.
