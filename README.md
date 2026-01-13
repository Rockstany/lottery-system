# GetToKnow Community App

**Version:** 1.0
**Domain:** zatana.in
**Status:** Development - Phase 1 Foundation Complete

---

## Project Overview

GetToKnow is a modern, senior-friendly community management platform focused on three core features:

1. **Lottery System** - Complete 6-part workflow for managing community lottery events
2. **Community Building** - Hierarchical community structure with sub-communities and member management
3. **CSF (Community Social Funds)** - Monthly contribution tracking with smart member search and bulk import

**Target Users:** Community administrators aged 40-60 years
**Design Philosophy:** MyGate-inspired, simple, interactive, and accessible

---

## Technology Stack

### Frontend
- HTML5, CSS3, JavaScript (Vanilla)
- Responsive design (mobile-first approach)
- Senior-friendly UI (large fonts, high contrast, 44x44px touch targets)

### Backend
- PHP 7.4+
- MySQL 8.0+ database
- Apache/Nginx server
- Session-based authentication

### Security
- Bcrypt password hashing
- CSRF protection
- SQL injection prevention
- XSS protection
- Input validation and sanitization

---

## Project Structure

```
Church Project/
├── config/                 # Configuration files
│   ├── config.php         # Application settings
│   └── database.php       # Database connection
│
├── database/              # Database files
│   └── schema.sql        # MySQL schema (14 tables)
│
├── src/                   # Backend source code
│   ├── controllers/      # Request handlers
│   ├── models/           # Database models
│   │   └── User.php     # User authentication model
│   ├── middleware/       # Authentication & security
│   │   └── AuthMiddleware.php
│   ├── utils/            # Utility classes
│   │   ├── Response.php # JSON response handler
│   │   └── Validator.php # Input validation
│   ├── routes/           # API routes
│   └── views/            # View templates
│
├── public/                # Public web files
│   ├── css/
│   │   └── main.css     # Responsive senior-friendly CSS
│   ├── js/              # JavaScript files
│   ├── images/          # Image assets
│   ├── uploads/         # User uploads (CSV, etc.)
│   ├── index.php        # Application entry point
│   ├── login.php        # Login page
│   └── logout.php       # Logout handler
│
├── assets/               # Additional assets
└── Documentation/        # Project documentation
    ├── Executive Summary.md
    ├── Project Documentation.md
    ├── Lottery System Summary.md
    └── Transaction Collection Summary.md
```

---

## Database Schema

**Total Tables:** 14

### Core Tables (4)
1. `users` - Admin and Group Admin users
2. `communities` - Community information
3. `group_admin_assignments` - User-community mappings
4. `activity_logs` - System activity tracking

### Lottery System (6 tables)
5. `lottery_events` - Lottery event management
6. `lottery_books` - Auto-generated lottery books
7. `distribution_levels` - Hierarchical distribution (Wing/Floor/Flat)
8. `distribution_level_values` - Level values (A, B, C, etc.)
9. `book_distribution` - Book assignments to members
10. `payment_collections` - Lottery payment tracking

### Transaction Collection (4 tables)
11. `transaction_campaigns` - Payment collection campaigns
12. `campaign_members` - Members in each campaign
13. `payment_history` - Payment records
14. `whatsapp_messages` - WhatsApp reminder tracking

---

## Installation Instructions

### Prerequisites
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer (optional, for future dependencies)

### Step 1: Database Setup

1. Create MySQL database:
```sql
CREATE DATABASE gettoknow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import schema:
```bash
mysql -u root -p gettoknow_db < database/schema.sql
```

3. Default admin credentials:
   - **Mobile:** 9999999999
   - **Password:** admin123

### Step 2: Configuration

1. Update database credentials in `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gettoknow_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

2. Update application URL in `config/config.php`:
```php
define('APP_URL', 'http://your-domain.com');
```

3. For production, set:
```php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('session.cookie_secure', 1); // Enable HTTPS
```

### Step 3: File Permissions

```bash
# Make uploads directory writable
chmod 755 public/uploads

# Ensure proper ownership (Linux/Mac)
chown -R www-data:www-data public/uploads
```

### Step 4: Apache Configuration

Create `.htaccess` in project root:
```apache
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/$1 [L]
```

### Step 5: Access Application

1. Open browser: `http://localhost/Church%20Project/public/login.php`
2. Login with default admin credentials
3. **IMPORTANT:** Change default password immediately!

---

## Features Implemented (Foundation)

### ✅ Complete
- [x] Scalable folder structure (MVC-inspired)
- [x] MySQL database schema (14 tables with indexes, views, triggers)
- [x] Backend configuration (database, security, sessions)
- [x] Authentication system (mobile + password)
- [x] Input validation and sanitization
- [x] CSRF protection
- [x] Responsive CSS framework (mobile-first)
- [x] Senior-friendly UI design (large fonts, high contrast)
- [x] Login/logout functionality
- [x] Activity logging
- [x] Session management with timeout

### 🔄 In Progress
- [ ] Admin dashboard
- [ ] Group Admin dashboard
- [ ] User management (CRUD)
- [ ] Community management

### 📋 Pending (Phase 1)
- [ ] Lottery System (6 parts)
  - [ ] Part 1: Event Creation
  - [ ] Part 2: Book Generation
  - [ ] Part 3: Distribution Settings
  - [ ] Part 4: Book Distribution
  - [ ] Part 5: Payment Collection
  - [ ] Part 6: Reports & Analytics
- [ ] Transaction Collection System (4 steps)
  - [ ] Step 1: CSV Upload
  - [ ] Step 2: WhatsApp Reminders
  - [ ] Step 3: Payment Tracking
  - [ ] Step 4: Dashboard & Reports

---

## Design Guidelines

### Senior-Friendly (40-60 Age Group)

**Typography:**
- Minimum 16px body text (18px preferred)
- 20px+ headings
- Clear sans-serif fonts (system fonts)

**Layout:**
- Card-based design with generous white space
- High contrast colors (WCAG AA compliant)
- Simple, intuitive navigation

**Interactions:**
- Large buttons (minimum 48x48px touch targets)
- One-click actions where possible
- Clear visual feedback
- Helpful tooltips and instructions
- Confirmation dialogs for destructive actions

**Status Colors:**
- 🟢 Green: Paid/Available/Success
- 🔴 Red: Unpaid/Error/Critical
- 🟠 Orange: Partial/Warning
- 🔵 Blue: Distributed/Info

---

## Security Features

### Authentication
- ✅ Password hashing (bcrypt, cost 10)
- ✅ Session-based authentication
- ✅ Session timeout (1 hour default)
- ✅ CSRF token protection
- ✅ Role-based access control

### Data Security
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (htmlspecialchars)
- ✅ Input validation and sanitization
- ✅ Mobile number format validation
- ⏳ HTTPS enforcement (production)
- ⏳ Rate limiting (future)

### Privacy
- ✅ Activity logging with IP tracking
- ✅ Audit trails for all actions
- ⏳ Data backup plan (to be implemented)

---

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Success message",
  "data": { ... },
  "timestamp": "2025-11-28 10:30:00"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... },
  "timestamp": "2025-11-28 10:30:00"
}
```

---

## User Roles

### 1. Admin (Super Admin)
- Create and manage Group Admins
- Create and manage Communities
- System-wide oversight
- Activity monitoring
- Full access to all features

### 2. Group Admin
- Manage assigned community
- Create unlimited lottery events
- Create unlimited transaction campaigns
- Track collections and payments
- Generate reports
- Cannot access other communities

---

## Development Roadmap

### Phase 1.1: Foundation ✅ (Current)
- Project structure setup
- Database schema
- Authentication system
- Responsive CSS framework

### Phase 1.2: Admin Panel (Next)
- Admin dashboard
- User management (CRUD)
- Community management
- Activity logs viewer

### Phase 1.3: Lottery System - Parts 1-2
- Event creation
- Auto book generation

### Phase 1.4: Lottery System - Parts 3-4
- Distribution settings (multi-level)
- Book distribution

### Phase 1.5: Lottery System - Parts 5-6
- Payment collection
- Reports and analytics

### Phase 1.6: Transaction Collection
- CSV upload and validation
- WhatsApp reminders (manual)
- Payment tracking
- Reports

### Phase 1.7: Integration & Polish
- Dashboard statistics
- Navigation improvements
- UI/UX refinements

### Phase 1.8: Testing & Deployment
- Unit testing
- UAT (1 Admin + 1 Group Admin)
- Bug fixes
- Security audit
- Production deployment

---

## Testing

### Test Accounts
- **Admin:** 9999999999 / admin123 (default)
- **Group Admin:** Create via admin panel

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

### Responsive Breakpoints
- Desktop: 1200px+
- Tablet: 768px - 1199px
- Mobile: < 768px
- Small Mobile: < 480px

---

## Troubleshooting

### Database Connection Error
```
Check config/database.php credentials
Ensure MySQL service is running
Verify database exists
```

### Session Timeout
```
Increase SESSION_TIMEOUT in config/config.php
Check server session configuration
```

### File Upload Issues
```
Check public/uploads/ permissions (755)
Verify MAX_FILE_SIZE in config.php
Ensure php.ini upload_max_filesize is adequate
```

---

## Performance Optimization

### Database
- ✅ Indexed columns for fast queries
- ✅ Composite indexes for common queries
- ✅ Generated columns for calculations
- ✅ Views for complex reports

### Frontend
- CSS minification (production)
- JS minification (production)
- Image optimization
- Lazy loading (future)

---

## Support & Documentation

### Documentation Files
- [Executive Summary](Documentation/Executive Summary.md) - Project overview
- [Project Documentation](Documentation/Project Documentation.md) - Complete specifications
- [Lottery System Summary](Documentation/Lottery System Summary.md) - Lottery feature details
- [Transaction Collection Summary](Documentation/Transaction Collection Summary.md) - Transaction feature details

### For Help
- Check documentation files
- Review code comments
- Contact development team

---

## License

Proprietary - All Rights Reserved
© 2025 GetToKnow Community App

---

## Changelog

### Version 1.0 (2025-11-28)
- ✅ Initial project structure
- ✅ Database schema (14 tables)
- ✅ Authentication system
- ✅ Responsive CSS framework
- ✅ Login/logout functionality
- ✅ Security implementations

---

**Status:** Foundation Complete - Ready for Feature Development
**Next Step:** Build Admin Dashboard and User Management
#   l o t t e r y - s y s t e m  
 