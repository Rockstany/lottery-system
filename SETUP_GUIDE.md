# GetToKnow - Quick Setup Guide

**For Hosting Environment (Desktop & Mobile Responsive)**

---

## Prerequisites

- PHP 7.4 or higher
- MySQL 8.0 or higher
- Apache web server with mod_rewrite enabled
- Basic knowledge of MySQL and phpMyAdmin

---

## Step-by-Step Setup (Hostinger or Similar)

### Step 1: Upload Files

1. **Compress project files** (exclude Documentation folder if needed)
2. **Upload to hosting** via FTP or File Manager
3. **Extract** in public_html or desired directory
4. **Set permissions:**
   - `public/uploads/` → 755
   - `.htaccess` → 644

### Step 2: Create Database

#### Using phpMyAdmin:

1. **Login to phpMyAdmin**
2. **Create new database:**
   - Name: `gettoknow_db`
   - Collation: `utf8mb4_unicode_ci`
3. **Import schema:**
   - Click on `gettoknow_db`
   - Go to "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go"
4. **Verify tables:** You should see 14 tables

#### Using MySQL Command Line:

```bash
mysql -u your_username -p
CREATE DATABASE gettoknow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gettoknow_db;
SOURCE database/schema.sql;
EXIT;
```

### Step 3: Configure Database Connection

1. **Open:** `config/database.php`
2. **Update credentials:**

```php
define('DB_HOST', 'localhost');          // Usually 'localhost'
define('DB_NAME', 'gettoknow_db');       // Your database name
define('DB_USER', 'your_db_username');   // Your database username
define('DB_PASS', 'your_db_password');   // Your database password
```

### Step 4: Configure Application

1. **Open:** `config/config.php`
2. **Update APP_URL:**

```php
// For Hostinger with domain zatana.in
define('APP_URL', 'https://zatana.in');

// For subdirectory
define('APP_URL', 'https://zatana.in/app');

// For testing subdomain
define('APP_URL', 'https://test.zatana.in');
```

3. **For Production, set:**

```php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('session.cookie_secure', 1);  // If using HTTPS
```

### Step 5: Test Installation

1. **Open browser:** `https://zatana.in/public/login.php`
2. **Default admin login:**
   - Mobile: `9999999999`
   - Password: `admin123`
3. **If successful:** You'll see the login page
4. **After login:** You'll be redirected to dashboard (to be built)

### Step 6: Secure Your Installation

#### Change Default Admin Password:

1. Login with default credentials
2. Go to profile/settings (once built)
3. Change password immediately

#### Update Database Password:

```sql
-- Using phpMyAdmin or MySQL command line
UPDATE users
SET password_hash = '$2y$10$NEW_HASH_HERE'
WHERE mobile_number = '9999999999';
```

Generate new hash using PHP:
```php
<?php
echo password_hash('your_new_password', PASSWORD_BCRYPT, ['cost' => 10]);
?>
```

---

## Hostinger-Specific Configuration

### File Manager Setup

1. **Extract uploaded zip** directly in File Manager
2. **Set folder permissions:**
   - Right-click `public/uploads` → Permissions → 755
3. **Check .htaccess** is present in root directory

### Database Setup via Hostinger

1. **Go to:** hPanel → Databases → MySQL Databases
2. **Create Database:**
   - Database name: `u123456789_gettoknow`
   - Username: `u123456789_admin`
   - Password: Generate strong password
3. **Note credentials** for config/database.php
4. **Open phpMyAdmin** (link in hPanel)
5. **Import schema.sql**

### SSL Certificate (HTTPS)

1. **Go to:** hPanel → Security → SSL
2. **Install Free SSL** (Let's Encrypt)
3. **Force HTTPS:**
   - Add to .htaccess:

```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### PHP Version

1. **Go to:** hPanel → Advanced → PHP Configuration
2. **Select:** PHP 7.4 or higher
3. **Enable extensions:**
   - PDO
   - PDO_MySQL
   - mbstring
   - session

---

## Common Issues & Solutions

### Issue 1: "Database Connection Error"

**Solution:**
- Verify database credentials in `config/database.php`
- Check if database exists
- Verify database user has permissions

### Issue 2: "500 Internal Server Error"

**Solution:**
- Check `.htaccess` file exists
- Verify mod_rewrite is enabled
- Check file permissions (644 for files, 755 for directories)
- Check PHP error logs

### Issue 3: "404 Not Found"

**Solution:**
- Verify .htaccess rewrite rules
- Check file paths in config.php
- Access via: `https://zatana.in/public/login.php`

### Issue 4: "Session Timeout"

**Solution:**
- Increase timeout in `config/config.php`:
```php
define('SESSION_TIMEOUT', 7200); // 2 hours
```

### Issue 5: "Upload Directory Not Writable"

**Solution:**
```bash
chmod 755 public/uploads
```

Or via File Manager: Right-click → Permissions → 755

---

## Directory Structure (After Upload)

```
public_html/                  (or your hosting root)
├── .htaccess                ✅ Must be present
├── config/
│   ├── config.php          ✅ Update APP_URL
│   └── database.php        ✅ Update DB credentials
├── database/
│   └── schema.sql          ✅ Import to MySQL
├── public/
│   ├── uploads/            ✅ Set 755 permissions
│   ├── login.php           ✅ Entry point
│   └── ...
└── src/
    └── ...
```

---

## Testing Checklist

- [ ] Can access login page: `https://zatana.in/public/login.php`
- [ ] Database has 14 tables
- [ ] Can login with default credentials (9999999999 / admin123)
- [ ] No PHP errors displayed
- [ ] Mobile responsive (test on phone)
- [ ] HTTPS working (green padlock)
- [ ] File uploads directory writable

---

## Performance Optimization (Production)

### Enable Caching

Add to `.htaccess`:
```apache
# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Enable Compression

Already in `.htaccess` - verify mod_deflate is enabled

### Optimize Database

```sql
-- Run periodically
OPTIMIZE TABLE users, communities, lottery_events;

-- Analyze tables
ANALYZE TABLE users, communities, lottery_events;
```

---

## Backup Strategy

### Database Backup

**Via phpMyAdmin:**
1. Select database
2. Export tab
3. Quick export → Go
4. Save .sql file

**Via Command Line:**
```bash
mysqldump -u username -p gettoknow_db > backup_$(date +%Y%m%d).sql
```

### Files Backup

**Important files:**
- `config/` - Configuration
- `public/uploads/` - User uploads
- Database backup (.sql)

---

## Next Steps After Setup

1. **Login** with default admin
2. **Change password** immediately
3. **Create test Group Admin** (once user management is built)
4. **Create test Community** (once community management is built)
5. **Test all features** as they're developed

---

## Support

### Error Logs Location

**Hostinger:**
- hPanel → Files → Error Logs

**Apache:**
- `/var/log/apache2/error.log`

**PHP:**
- Check hosting control panel

### Getting Help

1. Check error logs first
2. Review this guide
3. Check README.md
4. Contact development team

---

## Production Checklist

Before going live:

- [ ] Changed default admin password
- [ ] Updated all credentials in config files
- [ ] Disabled error display (set to 0 in config.php)
- [ ] Enabled HTTPS (SSL certificate)
- [ ] Set secure session cookies (cookie_secure = 1)
- [ ] Tested on multiple devices (desktop, tablet, mobile)
- [ ] Tested on multiple browsers
- [ ] Database backed up
- [ ] Set up automated backups
- [ ] Reviewed security settings
- [ ] Tested all user flows

---

**Last Updated:** 2025-11-28
**Version:** 1.0
**Status:** Foundation Complete
