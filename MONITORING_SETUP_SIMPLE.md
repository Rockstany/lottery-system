# 🖥️ System Monitoring - Simple Setup (Weekly Digest)

## Overview

This monitoring system is configured for **low-traffic websites** with:
- ✅ **Weekly email digest** (every Monday)
- ✅ **Critical alerts only** (immediate email for emergencies)
- ✅ **Minimal overhead** (no frequent checking)

---

## Quick Setup (3 Steps)

### Step 1: Import Database Tables ✅

Run this SQL file in phpMyAdmin:
```
database/migrations/add_system_monitoring_tables.sql
```

This creates 5 monitoring tables.

---

### Step 2: Configure Admin Email ✅

**Already done!** Your admin email is set to: `admin@zatana.in`

To change it, edit `config/config.php`:
```php
define('ADMIN_EMAIL', 'your-email@example.com');
```

---

### Step 3: Set Up Weekly Cron Job ✅

**Option A: cPanel (Recommended for shared hosting)**

1. Login to cPanel
2. Go to "Cron Jobs"
3. Add new cron job:
   - **Minute:** 0
   - **Hour:** 9
   - **Day:** *
   - **Month:** *
   - **Weekday:** 1 (Monday)
   - **Command:** `/usr/bin/php /home/u717011923/domains/zatana.in/public_html/cron/weekly-system-digest.php`

**Option B: Command Line (Linux/Mac)**

```bash
crontab -e
```

Add this line:
```
0 9 * * 1 /usr/bin/php /full/path/to/cron/weekly-system-digest.php
```

**What it does:**
- Runs every Monday at 9:00 AM
- Sends weekly email with system status
- Cleans old logs automatically

---

## What You Get

### 📧 Weekly Email (Every Monday)

You'll receive an email with:

```
=== WEEKLY SYSTEM HEALTH DIGEST ===
Period: Nov 25, 2025 to Dec 02, 2025

--- DISK SPACE ---
Usage: 45.2%
Free: 54.8 GB
Status: OK ✓

--- DATABASE ---
Status: CONNECTED ✓
Response Time: 12.5ms

--- SYSTEM LOGS (Last 7 Days) ---
Error (high): 2
Warning (medium): 5

--- SECURITY ---
Failed Login Attempts: 3
Unique IPs: 2
Status: OK ✓

--- ALERTS ---
Unresolved Alerts: 0

--- RECOMMENDATIONS ---
✓ All systems running smoothly

Dashboard: https://zatana.in/public/admin/system-health.php
```

---

### 🚨 Critical Alerts (Immediate Email)

You'll get **immediate email** for:
- ❌ Database connection failures
- ❌ Disk space >90% (critical)
- ❌ System crashes
- ❌ 5+ failed logins in 15 minutes

These are sent as soon as they happen (not waiting for weekly digest).

---

## Admin Dashboard

**URL:** `https://zatana.in/public/admin/system-health.php`

**Features:**
- Real-time system status
- Recent errors and alerts
- Failed login attempts
- Database health
- Auto-refreshes every 60 seconds

**Access:** Admin role only

---

## How Logging Works

### Automatic Logging

The system automatically logs:
- ✅ All errors in your PHP code
- ✅ Database connection issues
- ✅ Failed login attempts
- ✅ Security events

No action needed - it's automatic!

### Manual Logging (Optional)

You can add logging to your code:

```php
require_once 'classes/SystemLogger.php';
$logger = new SystemLogger();

// Log critical issue (sends immediate email)
$logger->logCritical('Payment gateway failed');

// Log regular error
$logger->logError('User profile update failed');

// Log security event
$logger->logSecurity('Unauthorized access attempt');
```

---

## Monitoring Schedule

| What | When | Purpose |
|------|------|---------|
| **Weekly Digest** | Every Monday 9 AM | System summary email |
| **Critical Alerts** | Immediate | Emergency notifications |
| **Log Cleanup** | Weekly (Monday) | Delete logs older than 30 days |
| **Dashboard** | Always available | Real-time monitoring |

---

## Important Notes

✅ **No Daily Cron Needed:** Weekly digest is enough for low-traffic sites

✅ **Critical Alerts Work Automatically:** No cron needed - alerts send when errors occur

✅ **Low Overhead:** Minimal impact on server performance

✅ **30-Day Log Retention:** Old logs auto-deleted to save space

---

## Testing the System

### Test 1: Check Dashboard
1. Login as admin
2. Go to: `https://zatana.in/public/admin/system-health.php`
3. You should see system status cards

### Test 2: Test Critical Alert (Optional)

Create file: `test-alert.php`

```php
<?php
require_once 'config/config.php';
require_once 'classes/SystemLogger.php';

$logger = new SystemLogger();
$logger->logCritical('TEST: Critical alert - please ignore');

echo "Check your email at: " . ADMIN_EMAIL;
```

Visit the file, check your email.

### Test 3: Run Weekly Digest Manually

```bash
php cron/weekly-system-digest.php
```

Check your email.

---

## Configuration

### Change Admin Email

Edit `config/config.php`:
```php
define('ADMIN_EMAIL', 'newemail@example.com');
```

### Change Weekly Digest Day

Edit `config/config.php`:
```php
define('WEEKLY_DIGEST_DAY', 'Friday'); // Monday to Sunday
```

Then update cron to run on that day.

### Disable Monitoring

Edit `config/config.php`:
```php
define('MONITORING_ENABLED', false);
```

---

## Support

**Email not working?**
1. Check `ADMIN_EMAIL` in config.php
2. Verify server can send emails: `php -r "mail('test@example.com', 'Test', 'Test');"`
3. Check spam folder

**Dashboard not loading?**
1. Verify you're logged in as admin
2. Check PHP error logs
3. Ensure database tables were imported

**Weekly digest not sending?**
1. Check cron is running: `crontab -l`
2. Run manually to test: `php cron/weekly-system-digest.php`
3. Check email server logs

---

## Summary

✅ **Setup:** 3 steps (5 minutes)
✅ **Cron:** Weekly only (Monday 9 AM)
✅ **Emails:** Weekly digest + critical alerts
✅ **Dashboard:** Always available
✅ **Overhead:** Minimal

**You're all set!** Your system is monitored with minimal configuration.
