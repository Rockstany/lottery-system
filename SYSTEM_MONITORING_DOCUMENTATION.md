# 🖥️ System Monitoring & Alerting - Documentation

## Overview

This system monitoring infrastructure provides real-time health monitoring, error logging, and automated alerts for the Church Community Management System.

---

## 📋 Table of Contents

1. [Features](#features)
2. [Installation](#installation)
3. [Components](#components)
4. [Usage Guide](#usage-guide)
5. [Alert Types](#alert-types)
6. [Configuration](#configuration)
7. [Cron Jobs](#cron-jobs)

---

## Features

### Phase 1 & 2 (Implemented):

✅ **Error Logging System**
- Categorized error logging (Error, Warning, Info, Security)
- Severity levels (Critical, High, Medium, Low)
- Automatic logging of file path, line number, user, IP, URL
- Database storage with 30-day retention

✅ **Database Connection Monitoring**
- Real-time connection testing
- Response time tracking
- Automatic alerts on connection failures
- Connection history logging

✅ **Email Alerts for Critical Errors**
- Automatic email notifications for critical issues
- Alert categorization and prioritization
- Email sent to admin on critical events

✅ **Failed Login Monitoring**
- Tracks all failed login attempts
- IP address and user agent logging
- Automatic security alerts after 5 failed attempts in 15 minutes
- Brute force attack detection

✅ **Disk Space Monitoring**
- Automatic disk space usage tracking
- Warning at 80% usage
- Critical alert at 90% usage
- Real-time percentage and free space display

✅ **System Health Dashboard**
- Real-time status indicators
- Recent alerts display
- System logs viewer
- Auto-refresh every 60 seconds

---

## Installation

### Step 1: Run Database Migration

Execute the SQL migration file to create monitoring tables:

```bash
mysql -u your_username -p your_database < database/migrations/add_system_monitoring_tables.sql
```

Or import via phpMyAdmin or your preferred MySQL client.

### Step 2: Set Up Admin Email

Add the admin email to your `config/config.php`:

```php
define('ADMIN_EMAIL', 'admin@yourchurch.com');
```

### Step 3: Create Storage Directory

Create a directory for log cleanup tracking:

```bash
mkdir -p storage
chmod 755 storage
```

### Step 4: Set Up Cron Job

Add the monitoring cron job to run every 5 minutes:

```bash
crontab -e
```

Add this line:

```
*/5 * * * * /usr/bin/php /path/to/your/project/cron/monitor-system-health.php
```

### Step 5: Access the Dashboard

Navigate to:
```
https://yoursite.com/public/admin/system-health.php
```

(Admin role required)

---

## Components

### 1. Database Tables

#### `system_logs`
Stores all system errors, warnings, and info messages.

| Column | Type | Description |
|--------|------|-------------|
| log_id | INT | Primary key |
| log_type | ENUM | error, warning, info, security |
| severity | ENUM | critical, high, medium, low |
| message | TEXT | Error message |
| details | TEXT | Additional JSON details |
| file_path | VARCHAR | PHP file where error occurred |
| line_number | INT | Line number in file |
| user_id | INT | User who triggered error |
| ip_address | VARCHAR | IP address |
| user_agent | VARCHAR | Browser user agent |
| url | VARCHAR | Request URL |
| created_at | TIMESTAMP | When logged |

#### `alert_notifications`
Stores system alerts and notifications.

| Column | Type | Description |
|--------|------|-------------|
| alert_id | INT | Primary key |
| alert_type | ENUM | critical_error, security_threat, system_down, disk_space, failed_login, performance |
| alert_title | VARCHAR | Alert title |
| alert_message | TEXT | Full alert message |
| severity | ENUM | critical, high, medium, low |
| is_read | TINYINT | Read status (0/1) |
| is_resolved | TINYINT | Resolution status (0/1) |
| notified_via_email | TINYINT | Email sent (0/1) |
| created_at | TIMESTAMP | When created |

#### `failed_login_attempts`
Tracks failed login attempts for security monitoring.

| Column | Type | Description |
|--------|------|-------------|
| attempt_id | INT | Primary key |
| email | VARCHAR | Email/mobile attempted |
| ip_address | VARCHAR | Source IP |
| user_agent | VARCHAR | Browser info |
| attempted_at | TIMESTAMP | When attempted |

#### `database_connection_logs`
Monitors database connection health.

| Column | Type | Description |
|--------|------|-------------|
| connection_id | INT | Primary key |
| connection_status | ENUM | success, failed, timeout |
| response_time | DECIMAL | Response time in ms |
| error_message | TEXT | Error if failed |
| checked_at | TIMESTAMP | When checked |

#### `system_health_metrics`
Stores periodic health metrics (disk, memory, etc.)

| Column | Type | Description |
|--------|------|-------------|
| metric_id | INT | Primary key |
| metric_name | VARCHAR | Metric identifier |
| metric_value | DECIMAL | Metric value |
| metric_unit | VARCHAR | Unit (%, MB, seconds) |
| status | ENUM | healthy, warning, critical |
| recorded_at | TIMESTAMP | When recorded |

---

### 2. PHP Classes

#### `SystemLogger` (`classes/SystemLogger.php`)

Main logging and monitoring class.

**Key Methods:**

```php
// Log messages
$logger->log($type, $severity, $message, $details);
$logger->logError($message, $details);
$logger->logCritical($message, $details);
$logger->logWarning($message, $details);
$logger->logInfo($message, $details);
$logger->logSecurity($message, $details);

// Create alerts
$logger->createAlert($type, $message, $severity);

// Failed login tracking
$logger->logFailedLogin($email);

// Health metrics
$logger->recordHealthMetric($metricName, $metricValue, $metricUnit);

// Database connection test
$logger->testDatabaseConnection();

// Get alerts
$logger->getRecentAlerts($limit, $onlyUnread);
$logger->getUnreadAlertCount();
$logger->markAlertAsRead($alertId);

// Cleanup
$logger->cleanOldLogs();
```

---

### 3. Dashboard

**File:** `public/admin/system-health.php`

**Features:**
- Real-time system status cards
- Website status (always online)
- Database connection status with response time
- Disk space usage with percentage
- Unread alert count
- Last 24 hours statistics (errors, critical issues, failed logins)
- Recent alerts list with mark-as-read functionality
- Recent system logs table
- Auto-refresh every 60 seconds

---

### 4. Cron Job

**File:** `cron/monitor-system-health.php`

**Runs every 5 minutes** and checks:
- Disk space usage (alerts at 80% and 90%)
- Database connectivity
- Memory usage
- Recent critical errors
- Cleans old logs daily

---

## Usage Guide

### How to Log Errors in Your Code

```php
require_once __DIR__ . '/classes/SystemLogger.php';

$logger = new SystemLogger();

// Log a simple error
$logger->logError('Payment processing failed');

// Log with details
$logger->logError('Database query failed', [
    'file' => __FILE__,
    'line' => __LINE__,
    'query' => $sql,
    'error' => $e->getMessage()
]);

// Log critical issue (sends email)
$logger->logCritical('System crashed during lottery book generation');

// Log security event
$logger->logSecurity('Unauthorized access attempt to admin panel');

// Log warning
$logger->logWarning('Disk space running low');

// Log info
$logger->logInfo('Cron job completed successfully');
```

### How to Track Custom Metrics

```php
$logger = new SystemLogger();

// Record disk space
$logger->recordHealthMetric('disk_space_used', 75.5, 'percentage');

// Record response time
$logger->recordHealthMetric('avg_response_time', 1.2, 'seconds');

// Record memory usage
$logger->recordHealthMetric('memory_usage', 65, 'percentage');
```

### How to Create Manual Alerts

```php
$logger = new SystemLogger();

// Create alert
$logger->createAlert(
    'performance',
    'Website response time exceeded 5 seconds',
    'high'
);

// Create critical alert (sends email)
$logger->createAlert(
    'critical_error',
    'Payment gateway integration failed',
    'critical'
);
```

---

## Alert Types

### Critical Errors (`critical_error`)
- System crashes
- Database failures
- Payment processing errors
- **Action:** Email sent immediately

### Security Threats (`security_threat`)
- 5+ failed logins in 15 minutes
- Unauthorized access attempts
- SQL injection attempts
- **Action:** Email sent immediately

### System Down (`system_down`)
- Database connection lost
- Website unreachable
- Critical service failures
- **Action:** Email sent immediately

### Disk Space (`disk_space`)
- 80%+ usage: Warning alert
- 90%+ usage: Critical alert
- **Action:** Email sent for critical (90%+)

### Failed Login (`failed_login`)
- Multiple failed login attempts
- IP-based attack detection
- **Action:** Email sent after threshold

### Performance (`performance`)
- Slow response times (>3s warning, >5s critical)
- High memory usage (>75% warning, >90% critical)
- **Action:** Email sent for critical issues

---

## Configuration

### Email Settings

Edit `classes/SystemLogger.php`:

```php
private function sendCriticalAlertEmail($title, $message) {
    $adminEmail = ADMIN_EMAIL ?? 'admin@example.com';
    // ... email configuration
}
```

### Alert Thresholds

Edit `classes/SystemLogger.php` in `determineMetricStatus()`:

```php
case 'disk_space_used':
    if ($value >= 90) return 'critical'; // Change threshold
    if ($value >= 80) return 'warning';  // Change threshold
    return 'healthy';
```

### Failed Login Threshold

Edit `classes/SystemLogger.php` in `checkFailedLoginThreshold()`:

```php
// Change from 5 attempts to 10 attempts
if ($result['attempt_count'] >= 10) {
    // Alert
}

// Change from 15 minutes to 30 minutes
AND attempted_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
```

### Log Retention Period

Edit `classes/SystemLogger.php` in `cleanOldLogs()`:

```php
// Change from 30 days to 60 days
WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)
```

---

## Cron Jobs

### System Health Monitor

**File:** `cron/monitor-system-health.php`

**Schedule:** Every 5 minutes

```bash
*/5 * * * * /usr/bin/php /path/to/cron/monitor-system-health.php
```

**What it does:**
1. Checks disk space every 5 minutes
2. Tests database connection
3. Records memory usage
4. Checks for recent critical errors
5. Cleans old logs once daily

### Manual Run (for testing)

```bash
php cron/monitor-system-health.php
```

**Output:**
```
[2025-12-03 14:30:00] Starting system health monitoring...
Checking disk space...
  ✅ Disk space OK: 45.2% used
Testing database connection...
  ✅ Database connection OK (12.5ms)
Checking memory usage...
  ✅ Memory usage OK: 32.1%
Checking recent errors...
  ✅ No critical errors
[2025-12-03 14:30:02] System health monitoring completed
------------------------------------------------------------
```

---

## Dashboard Screenshots

### Health Status Cards
Shows:
- Website Status (Online/Offline)
- Database Status (Connected/Failed) with response time
- Disk Space (percentage and free GB)
- Pending Alerts count

### Last 24 Hours Stats
Shows:
- Total Errors
- Critical Issues
- Failed Logins

### Recent Alerts
Lists:
- Alert severity badge
- Timestamp
- Alert title and message
- Mark as read button

### Recent System Logs
Table showing:
- Time
- Type (Error/Warning/Info/Security)
- Severity
- Message
- User
- IP Address

---

## Troubleshooting

### Emails Not Sending

1. Check `ADMIN_EMAIL` is set in `config/config.php`
2. Verify PHP mail() function is configured
3. Check server mail logs: `/var/log/mail.log`
4. Test with simple PHP mail script

### Cron Job Not Running

1. Verify cron is installed: `crontab -l`
2. Check PHP path: `which php`
3. Update cron command with correct path
4. Check cron logs: `grep CRON /var/log/syslog`

### Database Tables Missing

1. Re-run migration: `mysql -u user -p db < database/migrations/add_system_monitoring_tables.sql`
2. Verify tables exist: `SHOW TABLES LIKE 'system_%';`

### Dashboard Not Loading

1. Check you're logged in as admin
2. Verify `AuthMiddleware::requireRole('admin')` check
3. Check PHP error logs
4. Clear browser cache

---

## Best Practices

1. **Check Dashboard Daily:** Review alerts and errors every morning
2. **Address Critical Alerts Immediately:** Don't ignore critical alerts
3. **Monitor Trends:** Watch for increasing error rates or disk usage
4. **Clean Logs Regularly:** Cron job handles this automatically
5. **Test Email Alerts:** Trigger a test critical alert monthly
6. **Review Failed Logins:** Investigate unusual patterns
7. **Update Thresholds:** Adjust based on your system's normal behavior

---

## Future Enhancements (Phase 3)

Not yet implemented:
- SMS/WhatsApp notifications
- Performance monitoring (page load times)
- Daily digest emails
- Uptime percentage tracking
- Custom alert rules
- Integration with external monitoring services

---

## Summary

You now have a comprehensive monitoring system that:
- ✅ Logs all errors with full context
- ✅ Monitors database connectivity
- ✅ Sends email alerts for critical issues
- ✅ Tracks failed login attempts
- ✅ Monitors disk space usage
- ✅ Provides real-time health dashboard
- ✅ Automatically cleans old logs

**Access:** `https://yoursite.com/public/admin/system-health.php` (Admin only)

**Last Updated:** December 2025
