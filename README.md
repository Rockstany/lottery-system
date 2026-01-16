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

### Community Building (3 tables)
11. `sub_communities` - Areas/groups within communities
12. `sub_community_members` - Member assignments to sub-communities
13. `custom_fields` - Dynamic member fields

### CSF (Community Social Funds) (2 tables)
14. `csf_payments` - Monthly contribution records
15. `csf_reminders` - WhatsApp reminder tracking

### Feature Management (2 tables)
16. `features` - Available system features
17. `community_features` - Feature enablement per community

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

## CSF (Community Social Funds) Feature

### Overview
Complete monthly contribution tracking system designed for community administrators to manage social fund collections efficiently.

### Key Features

#### 1. **Manage Members**
- **Single Member Add**: Add members one at a time with full details
  - Name, mobile number, email
  - Area/sub-community assignment
  - Auto-creates user accounts with default credentials
- **Bulk Import**: Import hundreds of members via CSV paste
  - Format: `Name, Mobile, Email`
  - Automatic duplicate detection
  - Skip existing members
  - Batch processing with error reporting
- **View All Members**: List of all CSF members with area filter

#### 2. **Smart Member Search** (Record Payment)
- **Live Autocomplete**: Type-as-you-search with instant results
- **Smart Filtering with @ symbol**:
  - `@Area1 @John` - Find John in Area 1
  - `@Sector5 @Akshit` - Find Akshit in Sector 5
  - Regular search: `9876543210` or `Akshit` - Search all members
- **Performance**: Handles 1000+ members efficiently (20 results limit)
- **Rich Display**: Shows name, mobile, and area for each result

#### 3. **5-Step Payment Recording** (50+ Age Optimized)
- **Step 1**: Select Member (smart search)
- **Step 2**: Enter Amount (default monthly amount)
- **Step 3**: Select Date (current month default)
- **Step 4**: Payment Method (Cash, UPI, Bank Transfer, Cheque)
- **Step 5**: Confirm & Record
- **Features**:
  - Large fonts (18-36px)
  - Touch-friendly buttons (48px minimum)
  - Visual progress indicator
  - Transaction ID capture (optional)
  - Notes field

#### 4. **Payment History** (Enhanced)
- **Month Column**: Shows which month the payment is for (not just payment date)
- **Searchable Records**: Filter by member, month, year, payment method, or search text
- **Sortable Columns**: Click headers to sort by Month, Date, Member, or Amount (asc/desc)
- **Pagination**: 15 records per page with smart page navigation
- **Quick Stats**: When filtering by specific month, shows:
  - Members paid count
  - Total transactions
  - Payment method breakdown (Cash, UPI, Bank, Cheque with amounts)
- **Edit Payment**: Modify amount, payment method, transaction ID, and notes
- **Delete Payment**: Remove records with confirmation dialog
- **Displays**:
  - Payment month (what payment is for)
  - Payment date (when paid)
  - Member details with phone
  - Amount paid
  - Payment method badge
  - Transaction reference and notes
  - Recorded by (admin name and timestamp)

#### 5. **Reports & Analytics**
- **Monthly Overview**:
  - Total members
  - Paid members count
  - Unpaid members count
  - Total amount collected
  - Collection rate percentage
- **Member Status**:
  - Paid members (full payment)
  - Partial payment members
  - Unpaid members
- **Yearly Statistics**:
  - Month-by-month collection trends
  - Top contributors
  - Payment method distribution

#### 6. **WhatsApp Reminders**
- **Unpaid Members**: Send reminders to members who haven't paid
- **Partial Payment**: Remind members with pending balance
- **Customizable Templates**:
  - Dynamic placeholders: `{name}`, `{amount}`, `{month}`, `{community_name}`
  - Pre-filled WhatsApp links (`https://wa.me/`)
- **One-Click Send**: Open WhatsApp with pre-filled message

### Technical Architecture

#### Database Integration
```
communities → sub_communities (areas) → sub_community_members → csf_payments
```

#### Authentication & Security
- AuthMiddleware-based role checking (`group_admin`)
- Feature-level access control
- Community data isolation (by `community_id`)
- CSRF protection on all forms
- SQL injection prevention (prepared statements)

#### Smart Search Algorithm
- Regex pattern matching for `@` tags
- Area name fuzzy matching (LIKE queries)
- Falls back to name/mobile search
- Optimized with query limits (20 results)

### File Structure
```
public/group-admin/
├── csf-funds.php              # Main dashboard
├── csf-manage-members.php     # Member management (single/bulk)
├── csf-record-payment.php     # 5-step payment form
├── csf-payment-history.php    # Payment records
├── csf-reports.php            # Analytics dashboard
├── csf-send-reminders.php     # WhatsApp reminders
├── csf-api-search-member.php  # Smart search API
├── csf-api-check-duplicate.php # Duplicate payment check
└── csf-upload-proof.php       # Payment proof upload
```

### Configuration Requirements
- `csf_funds` feature must be enabled for the community
- Sub-communities (areas) must be created first
- Members added through CSF are also accessible in Community Building

### Default Settings
- Monthly contribution: ₹100 (customizable per community)
- Payment methods: Cash, UPI, Bank Transfer, Cheque
- Default password for new members: `Welcome@123`
- New member role: `group_admin` (required by schema)

### CSV Import Format
```csv
John Doe, 9876543210, john@example.com
Jane Smith, 9876543211
Akshit Kumar, 9876543212, akshit@example.com
```

### Usage Workflow
1. **Setup**: Create sub-communities (areas) in Community Building
2. **Add Members**: Use Manage Members (single or bulk)
3. **Record Payments**: Use smart search to find and record payments
4. **Track**: View payment history and reports
5. **Remind**: Send WhatsApp reminders to unpaid members

---

## Changelog

### Version 1.3 (2026-01-17)
- ✅ **Payment History Enhancements**
  - Added "Month" column showing payment period (not just payment date)
  - Edit Payment feature with modal form
  - Sortable columns (Month, Date, Member, Amount) with asc/desc toggle
  - Pagination with 15 records per page and smart navigation
  - Quick Stats panel when filtering by specific month
  - Payment method breakdown in Quick Stats
  - Improved UI with sort indicators and active column highlighting

### Version 1.2 (2026-01-13)
- ✅ **CSF (Community Social Funds) Feature Complete**
  - Member management (single add + bulk CSV import)
  - Smart member search with `@Area @Name` filtering
  - 5-step payment recording workflow (50+ age optimized)
  - Payment history with advanced filters
  - Reports & analytics dashboard
  - WhatsApp reminder system
  - Feature-level access control
  - Community data isolation
  - 9 CSF files created and integrated

### Version 1.1 (2025-12-15)
- ✅ **Community Building Feature**
  - Hierarchical community structure
  - Sub-communities (areas/groups)
  - Member management with custom fields
  - Feature enablement system

### Version 1.0 (2025-11-28)
- ✅ Initial project structure
- ✅ Database schema (17 tables)
- ✅ Authentication system (AuthMiddleware)
- ✅ Responsive CSS framework
- ✅ Login/logout functionality
- ✅ Security implementations

---

**Status:** CSF Feature Complete with Enhanced Payment History - Production Ready
**Version:** 1.3
**Last Updated:** 2026-01-17
**Next Step:** Testing & User Acceptance (UAT)
#   l o t t e r y - s y s t e m  
 