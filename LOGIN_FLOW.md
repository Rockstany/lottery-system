# Login Flow - GetToKnow Community App

## Complete Authentication Flow

### 1. User Access Points

```
https://zatana.in/
    ↓
Redirects to: https://zatana.in/public/login.php
```

**OR**

```
https://zatana.in/public/login.php (Direct access)
```

---

### 2. Login Page Flow

#### A. Already Logged In?

```
User visits: /public/login.php
    ↓
Check: Is user authenticated? (session exists)
    ↓
YES → Redirect based on role:
    • Admin → /public/admin/dashboard.php
    • Group Admin → /public/group-admin/dashboard.php
```

#### B. Not Logged In

```
User sees Login Form
    ↓
Enter: Mobile (10 digits) + Password
    ↓
Submit Form
    ↓
Backend Validation:
    1. Mobile format (6-9 start, 10 digits)
    2. Password length (min 6 chars)
    3. CSRF token check
    ↓
Database Authentication:
    • Hash password match (bcrypt)
    • User status = 'active'
    • Role check
    ↓
SUCCESS → Create Session:
    • user_id
    • mobile_number
    • full_name
    • role (admin/group_admin)
    • last_activity
    • community_id (if group_admin)
    ↓
Log Activity:
    • Action: 'login'
    • IP address
    • User agent
    ↓
Redirect based on role:
    • Admin → /public/admin/dashboard.php
    • Group Admin → /public/group-admin/dashboard.php
```

---

### 3. Admin Dashboard

```
URL: https://zatana.in/public/admin/dashboard.php
    ↓
Check Authentication:
    • Session exists?
    • Role = 'admin'?
    ↓
YES → Display Dashboard:
    ┌─────────────────────────────────────┐
    │ Admin Dashboard                      │
    ├─────────────────────────────────────┤
    │ Statistics:                          │
    │  • Total Users                       │
    │  • Total Communities                 │
    │  • Total Group Admins                │
    │  • Lottery Events                    │
    │  • Transaction Campaigns             │
    ├─────────────────────────────────────┤
    │ Quick Actions:                       │
    │  • Create Group Admin                │
    │  • Create Community                  │
    │  • View All Users                    │
    │  • View Activity Logs                │
    ├─────────────────────────────────────┤
    │ Recent Activity (Last 10)            │
    │ System Information                   │
    └─────────────────────────────────────┘
    ↓
NO → Redirect to /public/login.php
```

---

### 4. Group Admin Dashboard

```
URL: https://zatana.in/public/group-admin/dashboard.php
    ↓
Check Authentication:
    • Session exists?
    • Role = 'group_admin'?
    ↓
YES → Get Community Assignment
    ↓
Display Dashboard:
    ┌─────────────────────────────────────┐
    │ Group Admin Dashboard                │
    │ Community: [Community Name]          │
    ├─────────────────────────────────────┤
    │ Statistics:                          │
    │  • Lottery Events (this community)   │
    │  • Transaction Campaigns             │
    │  • Total Collections                 │
    │  • Active Members                    │
    ├─────────────────────────────────────┤
    │ Quick Actions:                       │
    │  • Create Lottery Event              │
    │  • Create Transaction Campaign       │
    │  • View Reports                      │
    │  • Manage Members                    │
    ├─────────────────────────────────────┤
    │ Recent Activity                      │
    │ Coming Soon Features                 │
    └─────────────────────────────────────┘
    ↓
Alert if NOT assigned to community
    ↓
NO → Redirect to /public/login.php
```

---

### 5. Logout Flow

```
User clicks: Logout
    ↓
URL: https://zatana.in/public/logout.php
    ↓
Log Activity:
    • Action: 'logout'
    • User ID
    • IP address
    ↓
Clear Session:
    • Unset all session variables
    • Destroy session cookie
    • Destroy session
    ↓
Redirect: /public/login.php
```

---

### 6. Session Timeout

```
User inactive for > 1 hour (SESSION_TIMEOUT)
    ↓
Next page request:
    ↓
Check: last_activity timestamp
    ↓
Timeout exceeded?
    ↓
YES → Logout automatically
    ↓
Redirect: /public/login.php?timeout=1
    ↓
Show message: "Your session has expired. Please login again."
```

---

## File Structure

```
public/
├── index.php                   (Entry point - redirects based on auth)
├── login.php                   (Login form + authentication)
├── logout.php                  (Logout handler)
│
├── admin/
│   └── dashboard.php          (Admin dashboard)
│
└── group-admin/
    └── dashboard.php          (Group Admin dashboard)
```

---

## Default Credentials

### Admin Account
```
Mobile: 9999999999
Password: admin123
Role: admin
```

**⚠️ CHANGE PASSWORD IMMEDIATELY AFTER FIRST LOGIN!**

---

## Testing Checklist

### 1. Login Page
- [ ] Access `https://zatana.in/public/login.php`
- [ ] Page displays correctly
- [ ] Mobile responsive
- [ ] Form validation works

### 2. Admin Login
- [ ] Login with `9999999999` / `admin123`
- [ ] Redirects to `/public/admin/dashboard.php`
- [ ] Dashboard displays statistics
- [ ] Navigation menu works
- [ ] Logout works

### 3. Group Admin Login
- [ ] Create Group Admin via admin panel (when built)
- [ ] Login with Group Admin credentials
- [ ] Redirects to `/public/group-admin/dashboard.php`
- [ ] Dashboard displays community statistics
- [ ] Shows alert if not assigned to community
- [ ] Logout works

### 4. Session Management
- [ ] Session persists across pages
- [ ] Session timeout works (1 hour)
- [ ] Can't access dashboard without login
- [ ] Can't access admin page as group admin
- [ ] Can't access group admin page as admin

### 5. Security
- [ ] CSRF token validation works
- [ ] SQL injection prevented (prepared statements)
- [ ] XSS protection works (htmlspecialchars)
- [ ] Password hashing works (bcrypt)
- [ ] Activity logging works

---

## Redirect Map

| From | Role | To |
|------|------|-----|
| / | Any | /public/login.php |
| /public/login.php | Already logged (admin) | /public/admin/dashboard.php |
| /public/login.php | Already logged (group_admin) | /public/group-admin/dashboard.php |
| /public/login.php | Login success (admin) | /public/admin/dashboard.php |
| /public/login.php | Login success (group_admin) | /public/group-admin/dashboard.php |
| /public/index.php | Authenticated (admin) | /public/admin/dashboard.php |
| /public/index.php | Authenticated (group_admin) | /public/group-admin/dashboard.php |
| /public/logout.php | Any | /public/login.php |

---

## URL Structure

### Production (Hostinger with domain)
```
https://zatana.in/public/login.php
https://zatana.in/public/admin/dashboard.php
https://zatana.in/public/group-admin/dashboard.php
https://zatana.in/public/logout.php
```

### Development (Localhost)
```
http://localhost/Church%20Project/public/login.php
http://localhost/Church%20Project/public/admin/dashboard.php
http://localhost/Church%20Project/public/group-admin/dashboard.php
http://localhost/Church%20Project/public/logout.php
```

---

## Troubleshooting

### Issue: Redirect Loop
**Cause:** Session not being created
**Fix:** Check PHP session configuration, ensure cookies enabled

### Issue: 404 on Dashboard
**Cause:** Incorrect path or file not exists
**Fix:** Verify files exist in `/public/admin/` and `/public/group-admin/`

### Issue: "Unauthorized" Error
**Cause:** Session expired or wrong role
**Fix:** Login again, check user role in database

### Issue: Can't Login
**Cause:** Database connection error or wrong credentials
**Fix:** Check database credentials in `config/database.php`

---

**Status:** ✅ Complete Login Flow Working
**Last Updated:** 2025-11-29
**Version:** 1.0.1
