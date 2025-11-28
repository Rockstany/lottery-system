# GetToKnow - Quick Start Guide

**Ready to deploy in 5 minutes!**

---

## 🚀 Deployment (Hostinger)

### 1. Upload Files
- Upload entire project to `public_html/`
- Or use subdirectory: `public_html/gettoknow/`

### 2. Create Database
```
Name: gettoknow_db
User: your_username
Password: your_password
```

### 3. Import Schema
- Open phpMyAdmin
- Select database
- Import: `database/schema.sql`

### 4. Configure
Edit `config/database.php`:
```php
define('DB_NAME', 'gettoknow_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

Edit `config/config.php`:
```php
define('APP_URL', 'https://zatana.in');
```

### 5. Set Permissions
```
public/uploads/ → 755
```

### 6. Access
```
https://zatana.in/public/login.php

Login: 9999999999
Password: admin123
```

---

## 📁 Important Files

| File | Purpose |
|------|---------|
| `config/database.php` | Database credentials |
| `config/config.php` | App settings |
| `database/schema.sql` | Database structure |
| `public/login.php` | Login page |
| `.htaccess` | Apache config |

---

## 🗄️ Database

**Tables:** 14
**Views:** 2
**Triggers:** 2

**Core:** users, communities, group_admin_assignments, activity_logs
**Lottery:** 6 tables for event management
**Transaction:** 4 tables for payment collection

---

## 🔐 Default Login

```
Mobile: 9999999999
Password: admin123
Role: Admin
```

⚠️ **Change password immediately after first login!**

---

## 📱 Features

### ✅ Complete
- Authentication (mobile + password)
- Session management
- Role-based access (Admin, Group Admin)
- Responsive design (mobile, tablet, desktop)
- Security (CSRF, XSS, SQL injection protection)
- Activity logging

### 🔜 Next to Build
- Admin Dashboard
- User Management
- Community Management
- Lottery System (6 parts)
- Transaction Collection (4 steps)

---

## 🎨 Design

**Optimized for:** 40-60 age group
**Font Size:** 18px (senior-friendly)
**Touch Targets:** 48px minimum
**Colors:**
- 🟢 Green = Paid/Success
- 🔴 Red = Unpaid/Error
- 🟠 Orange = Partial/Warning
- 🔵 Blue = Info

---

## 🛠️ Tech Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 8.0+
- **Frontend:** HTML5, CSS3, JavaScript
- **Server:** Apache/Nginx

---

## 📖 Documentation

1. **README.md** - Complete documentation
2. **SETUP_GUIDE.md** - Detailed setup steps
3. **BUILD_SUMMARY.md** - What we built
4. **PROJECT_STRUCTURE.txt** - File structure

---

## ⚙️ Configuration

### Production Settings
In `config/config.php`:
```php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('session.cookie_secure', 1);
```

### Enable HTTPS
In `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 🐛 Troubleshooting

**Database Error?**
- Check credentials in `config/database.php`
- Verify database exists
- Ensure MySQL is running

**500 Error?**
- Check file permissions
- Verify .htaccess exists
- Check PHP error logs

**Login Not Working?**
- Verify database imported correctly
- Check default user exists
- Clear browser cookies

**Upload Issues?**
- Set `public/uploads/` to 755
- Check PHP upload settings

---

## 📊 Status

```
Foundation: ✅ Complete
Database: ✅ 14 tables ready
Authentication: ✅ Working
Responsive: ✅ Mobile + Desktop
Security: ✅ Production-ready
Deployment: ✅ Ready for Hostinger
```

---

## 🎯 Next Steps

1. **Deploy** to Hostinger (5 minutes)
2. **Test** login functionality
3. **Change** default password
4. **Build** Admin Dashboard (next)
5. **Add** User Management
6. **Implement** Features (Lottery & Transaction)

---

## 📞 Support

- Review README.md for details
- Check SETUP_GUIDE.md for help
- See error logs for issues
- Contact development team

---

**Version:** 1.0
**Status:** Ready for Deployment
**Last Updated:** 2025-11-28

🎉 **You're all set! Deploy and start building features!**
