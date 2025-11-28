# GetToKnow - Community Management App
## Project Documentation - Phase 1

---

## 1. Project Overview

**App Name:** GetToKnow
**Domain:** zatana.in
**Type:** Community-driven Management Application
**Phase:** Phase 1 (Testing & MVP)
**Target Users:** Community members aged 40-60 years

### Purpose
A modern, interactive community management platform that enables seamless communication and management within residential communities, apartment complexes, and housing societies.

---

## 2. Phase 1 Scope

### Primary Focus
- **Admin Panel Development**
- **Group Admin Panel Development**
- User-friendly, interactive design for age group 40-60
- Testing with ONE Admin and ONE Group Admin
- Validation of core concept and features

### Out of Scope (Future Phases)
- Mobile Application (will switch in later stage)
- Group Member features
- Multiple groups management
- Advanced reporting and analytics

---

## 3. Technology Stack

### Frontend
- **HTML5** - Structure and semantics
- **CSS3** - Styling and responsive design
- **JavaScript (Vanilla JS)** - Interactivity and dynamic content

### Backend
- **Technology:** (PHP/Node.js - to be decided)
- **Database:** MySQL
- **Server:** Apache/Nginx

### Authentication
- **Primary Method:** Mobile Number + Password
- **Security:** Password encryption (bcrypt/hash)

---

## 4. User Roles & Permissions

### 4.1 Admin (Super Admin)
**Responsibilities:**
- Platform-level management
- Create and manage Group Admins
- Oversee all communities/groups
- System configuration and settings
- View all group activities and reports

**Key Features:**
- Dashboard with overall statistics
- Group Admin management (Create, Edit, Deactivate)
- Community/Group creation and assignment
- System-wide announcements
- Activity logs and audit trail

### 4.2 Group Admin
**Responsibilities:**
- Manage single community/group
- Manage group members
- Handle community-specific operations
- Two key features:
  1. **Lottery System** (details to be finalized)
  2. **Transaction Collection System** (details to be finalized)

**Key Features:**
- Group-specific dashboard
- Member management
- Lottery System interface
- Transaction Collection interface
- Community announcements
- Reports and analytics for their group

### 4.3 Group Members
**Status:** Not included in Phase 1
**Future Implementation:** Phase 2+

---

## 5. Design Guidelines

### Design Principles
- **Theme Inspiration:** MyGate-style community app
- **Modern & Interactive:** Clean, contemporary design with smooth interactions
- **Senior-Friendly:** Optimized for users aged 40-60 years
- **Simple to Use:** Intuitive navigation with minimal learning curve

### UI/UX Requirements

#### Typography
- **Font Size:** Minimum 16px for body text, 20px+ for headings
- **Font Family:** Clear, readable sans-serif fonts (e.g., Open Sans, Roboto, Inter)
- **Line Height:** 1.6-1.8 for better readability
- **Font Weight:** Medium to bold for important elements

#### Color Scheme
- **High Contrast:** Ensure text is easily readable
- **Primary Colors:** Calming, trustworthy colors (blues, greens)
- **Secondary Colors:** Complementary accent colors
- **Error/Success States:** Clear visual feedback (red for errors, green for success)

#### Layout
- **Responsive Design:** Works seamlessly on desktop, tablet, and mobile browsers
- **Grid System:** Clean, organized layout
- **White Space:** Generous spacing for clarity
- **Card-based Design:** Group related information in cards

#### Interactive Elements
- **Large Click Targets:** Minimum 44x44px for buttons/links
- **Clear CTAs:** Prominent call-to-action buttons
- **Hover States:** Visual feedback on interactive elements
- **Loading States:** Clear indicators for processing actions
- **Tooltips:** Helpful hints for complex features

#### Navigation
- **Simple Menu Structure:** Maximum 5-7 main menu items
- **Breadcrumbs:** Show user's current location
- **Back Buttons:** Easy navigation to previous pages
- **Search Functionality:** Quick access to features

---

## 6. Core Features - Phase 1

### 6.1 Authentication System

#### Login Page
- Mobile number input (10 digits)
- Password input (show/hide toggle)
- "Remember Me" checkbox
- "Forgot Password" link
- Clear error messages
- Loading state during authentication

#### Registration (Admin Only)
- Admin creates Group Admin accounts
- Mobile number (unique identifier)
- Temporary password generation
- Force password change on first login

#### Password Recovery
- OTP-based password reset via SMS
- Security questions (optional)

### 6.2 Admin Dashboard

#### Dashboard Overview
- Total number of groups
- Total Group Admins
- Active/Inactive groups
- Recent activities
- Quick action buttons

#### Group Admin Management
- **List View:**
  - Search and filter Group Admins
  - Sort by name, community, status
  - Quick actions (Edit, View, Deactivate)

- **Create Group Admin:**
  - Personal details (Name, Mobile, Email)
  - Community/Group assignment
  - Set permissions
  - Generate temporary password

- **Edit Group Admin:**
  - Update details
  - Change community assignment
  - Reset password
  - Activate/Deactivate account

#### Community/Group Management
- Create new communities
- Edit community details
- Assign Group Admin
- View community statistics

#### Settings
- Profile management
- Change password
- System preferences
- Notification settings

### 6.3 Group Admin Dashboard

#### Dashboard Overview
- Community overview
- Member count (for future)
- Lottery system summary
- Transaction collection summary
- Recent activities
- Quick action buttons

#### Lottery System
Complete 6-part lottery management system for community events
- Create and manage lottery events
- Generate lottery books with auto-numbering
- Configure multi-level distribution settings
- Distribute books to members
- Track collections and payments
- View comprehensive reports and summaries

#### Transaction Collection System
Complete 4-step CSV-based transaction tracking system
- Create campaigns and upload member data via CSV
- Send WhatsApp payment reminders (manual workflow)
- Track payment status (Paid, Partial, Unpaid)
- Monitor collections with comprehensive reports

#### Profile & Settings
- View/Edit profile
- Change password
- Notification preferences
- Community information

---

## 7. Database Schema (Initial)

### Users Table
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    mobile_number VARCHAR(15) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'group_admin', 'member') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    force_password_change BOOLEAN DEFAULT FALSE
);
```

### Communities Table
```sql
CREATE TABLE communities (
    community_id INT PRIMARY KEY AUTO_INCREMENT,
    community_name VARCHAR(100) NOT NULL,
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    total_units INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Group Admin Assignment Table
```sql
CREATE TABLE group_admin_assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    community_id INT NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (community_id) REFERENCES communities(community_id),
    UNIQUE KEY unique_assignment (user_id, community_id)
);
```

### Activity Logs Table
```sql
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

### Lottery Events Table
```sql
CREATE TABLE lottery_events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    community_id INT NOT NULL,
    event_name VARCHAR(150) NOT NULL,
    event_description TEXT,
    first_ticket_number INT NOT NULL,
    tickets_per_book INT NOT NULL,
    total_books INT NOT NULL,
    single_ticket_cost DECIMAL(10,2) NOT NULL,
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

### Lottery Books Table
```sql
CREATE TABLE lottery_books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    book_number INT NOT NULL,
    start_ticket_number INT NOT NULL,
    end_ticket_number INT NOT NULL,
    book_value DECIMAL(10,2) NOT NULL,
    distribution_status ENUM('available', 'distributed', 'collected') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id),
    UNIQUE KEY unique_book (event_id, book_number)
);
```

### Distribution Levels Table
```sql
CREATE TABLE distribution_levels (
    level_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    level_number INT NOT NULL,
    level_heading VARCHAR(100) NOT NULL,
    is_dependent BOOLEAN DEFAULT FALSE,
    parent_level_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id),
    FOREIGN KEY (parent_level_id) REFERENCES distribution_levels(level_id)
);
```

### Distribution Level Values Table
```sql
CREATE TABLE distribution_level_values (
    value_id INT PRIMARY KEY AUTO_INCREMENT,
    level_id INT NOT NULL,
    parent_value_id INT NULL,
    value_text VARCHAR(100) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (level_id) REFERENCES distribution_levels(level_id),
    FOREIGN KEY (parent_value_id) REFERENCES distribution_level_values(value_id)
);
```

### Book Distribution Table
```sql
CREATE TABLE book_distribution (
    distribution_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    member_name VARCHAR(150),
    member_mobile VARCHAR(15),
    level_1_value_id INT NULL,
    level_2_value_id INT NULL,
    level_3_value_id INT NULL,
    distributed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    distributed_by INT NOT NULL,
    notes TEXT,
    FOREIGN KEY (book_id) REFERENCES lottery_books(book_id),
    FOREIGN KEY (level_1_value_id) REFERENCES distribution_level_values(value_id),
    FOREIGN KEY (level_2_value_id) REFERENCES distribution_level_values(value_id),
    FOREIGN KEY (level_3_value_id) REFERENCES distribution_level_values(value_id),
    FOREIGN KEY (distributed_by) REFERENCES users(user_id),
    UNIQUE KEY unique_book_distribution (book_id)
);
```

### Payment Collections Table
```sql
CREATE TABLE payment_collections (
    collection_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    amount_collected DECIMAL(10,2) NOT NULL,
    payment_status ENUM('full', 'partial') NOT NULL,
    payment_method ENUM('cash', 'upi', 'other') NOT NULL,
    collection_date DATE NOT NULL,
    collected_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES lottery_books(book_id),
    FOREIGN KEY (collected_by) REFERENCES users(user_id)
);
```

---

## 8. Lottery System - Detailed Specifications

### Overview
The Lottery System is a comprehensive 6-part module that enables Group Admins to create, manage, distribute, and track lottery events within their community. Each event can be customized for different occasions (Diwali, New Year, etc.) with automated book generation, multi-level distribution, payment collection, and detailed reporting.

---

### Part 1: Introduction / Event Creation

#### Purpose
Create and initialize lottery events for different community occasions.

#### Fields & Inputs

**Event Name** (Text, Required)
- Description: Name of the event for which lottery is being created
- Examples: "Diwali 2025 Lottery", "New Year 2026 Lucky Draw", "Christmas Celebration Lottery"
- Validation: Maximum 150 characters
- UI: Large text input with placeholder

**Group Name/ID** (Auto-populated/Hidden)
- Description: Backend identifier linking event to community
- Source: Automatically retrieved from logged-in Group Admin's community assignment
- Display: Show community name for confirmation only

**Event Description** (Textarea, Optional)
- Description: Additional details about the lottery event
- Validation: Maximum 500 characters
- UI: Multi-line textarea

**Status** (Auto-set)
- Initial status: "Draft"
- Can be changed to: Active, Completed, Cancelled

#### UI/UX Requirements
- Clean, card-based form layout
- Clear section heading: "Create New Lottery Event"
- Save as Draft button
- Proceed to Next Step button
- Cancel/Back button

---

### Part 2: Lottery Creation / Book Generation

#### Purpose
Define lottery parameters and auto-generate numbered lottery books.

#### Fields & Inputs

**First Ticket Number** (Numerical, Required)
- Description: Starting ticket number for the lottery series
- Example: 100
- Validation: Positive integer, min value 1
- UI: Number input with clear label

**Single Ticket Cost** (Numerical, Required)
- Description: Cost per individual lottery ticket
- Example: ₹10, ₹20, ₹50
- Validation: Positive decimal (max 2 decimal places)
- UI: Number input with currency symbol (₹)

**No. of Tickets per Book** (Numerical, Required)
- Description: How many tickets in a single lottery book
- Example: 10 tickets per book
- Validation: Positive integer, min value 1
- UI: Number input

**Total No. of Books** (Numerical, Required)
- Description: Total number of lottery books to generate
- Example: 5 books
- Validation: Positive integer, min value 1
- UI: Number input

#### Auto-Calculation Logic

**Formula:**
```
Book Number = 1, 2, 3... up to Total Books
Start Ticket = First Ticket + (Book Number - 1) × Tickets Per Book
End Ticket = Start Ticket + Tickets Per Book - 1
Book Value = Tickets Per Book × Single Ticket Cost
```

**Example Calculation:**
- First Ticket: 100
- Tickets Per Book: 10
- Total Books: 5
- Single Ticket Cost: ₹20

Generated Books:
1. Book 1: Tickets 100-109, Value: ₹200
2. Book 2: Tickets 110-119, Value: ₹200
3. Book 3: Tickets 120-129, Value: ₹200
4. Book 4: Tickets 130-139, Value: ₹200
5. Book 5: Tickets 140-149, Value: ₹200

Total Tickets: 50
Total Value: ₹1,000

#### Preview Feature
- **Preview Button:** "Preview Lottery Books"
- **Display:** Modal/Accordion showing all generated books
- **Information per book:**
  - Book Number
  - Ticket Range (Start - End)
  - Number of Tickets
  - Book Value
- **Summary Section:**
  - Total Books
  - Total Tickets
  - Total Expected Collection

#### UI/UX Requirements
- Grid layout for inputs (2 columns on desktop)
- Real-time calculation preview
- Large "Generate Books" button
- Visual feedback during generation
- Confirmation message on success
- Edit capability before finalizing

---

### Part 3: Distribution Settings / Level Configuration

#### Purpose
Configure multi-level categorization for organizing lottery book distribution (e.g., by Area > Block > Floor).

#### Configuration Options

**Number of Levels** (Dropdown, Required)
- Options: 1, 2, or 3 levels
- Default: 1 level
- Description: How many hierarchical levels needed for distribution

#### Level Setup (Dynamic based on selected levels)

**For Each Level:**

**Level Heading** (Text, Required)
- Description: Name/label for this distribution category
- Examples: "Area", "Block", "Building", "Floor", "Wing", "Zone"
- Validation: Maximum 100 characters
- UI: Text input

**Level Values** (Multi-entry)
- Description: Dropdown options for this level
- Entry Methods:
  1. **Manual Entry:** Add values one by one
  2. **CSV Upload:** Bulk upload values from CSV file

**Manual Entry UI:**
- Add Value button
- List of added values with delete option
- Drag-and-drop to reorder
- Minimum 1 value required

**CSV Upload UI:**
- File upload button (accepts .csv only)
- Sample CSV download link
- Preview uploaded data
- Validation feedback

#### Dependent Dropdown Logic

**If 2 Levels Selected:**
- Level 1: Independent dropdown
- Level 2: Values depend on Level 1 selection

**If 3 Levels Selected:**
- Level 1: Independent dropdown
- Level 2: Values depend on Level 1 selection
- Level 3: Values depend on Level 2 selection

**Example Configuration:**

**1 Level:**
- Level 1: "Block" → A, B, C, D

**2 Levels (Dependent):**
- Level 1: "Area" → North, South, East, West
- Level 2: "Block" →
  - If North: N1, N2, N3
  - If South: S1, S2, S3
  - If East: E1, E2
  - If West: W1, W2, W3

**3 Levels (Dependent):**
- Level 1: "Wing" → A, B, C
- Level 2: "Floor" →
  - If Wing A: 1, 2, 3, 4
  - If Wing B: 1, 2, 3
  - If Wing C: 1, 2
- Level 3: "Flat" →
  - If Wing A, Floor 1: 101, 102, 103
  - If Wing A, Floor 2: 201, 202, 203
  - (and so on...)

#### UI/UX Requirements
- Step-by-step wizard interface
- Clear visual indication of level hierarchy
- Sample examples shown for guidance
- Preview distribution structure
- Save and Edit capability
- Bulk actions for managing values

---

### Part 4: Book Distribution / Assignment

#### Purpose
Assign lottery books to members/users based on configured distribution levels.

#### Distribution Interface

**Book Selection:**
- List view of all available lottery books
- Filters: Available, Distributed, All
- Multi-select capability for bulk distribution
- Single book assignment option

**Member/User Information:**
- Member Name (Text, Optional initially)
- Member Mobile (Text, Optional initially)
- Note: Member details can be added during or after distribution

**Distribution Level Selection:**
- Dropdown(s) based on configured levels
- Dependent cascading dropdowns
- Required: At least Level 1 selection

**Example Distribution Flow:**

**Scenario:** 3 Levels configured (Wing > Floor > Flat)

1. Select Book(s): Book 1 (Tickets 100-109)
2. Select Wing: A
3. Select Floor: 1 (shows only floors in Wing A)
4. Select Flat: 101 (shows only flats on Floor 1, Wing A)
5. Member Name: John Doe (optional)
6. Member Mobile: 9876543210 (optional)
7. Notes: Any additional information (optional)
8. Click "Assign Book"

#### Business Rules

**Unique Assignment:**
- One lottery book can be assigned to ONLY ONE member/category combination
- System prevents duplicate book assignment
- Warning if trying to assign already distributed book

**Edit Capability:**
- Can modify assignment before collection starts
- Cannot reassign once payment collection has begun
- Can update member details anytime

**Bulk Distribution:**
- Select multiple books
- Assign to same distribution level combination
- Different member names/mobiles per book

#### Distribution Statistics Dashboard

**Summary Cards:**
- Total Books: X
- Distributed Books: Y
- Available Books: Z
- Distribution Percentage: Y/X × 100%

**Visual Representation:**
- Progress bar showing distribution completion
- Pie chart: Distributed vs Available
- Table/List view of all distributions

**Filter & Search:**
- Filter by distribution level
- Search by member name/mobile
- Filter by status (distributed/available)

#### UI/UX Requirements
- Split-screen layout: Book list + Distribution form
- Color coding: Green (available), Blue (distributed)
- Quick assign buttons
- Bulk selection checkboxes
- Confirmation dialog before assignment
- Success/Error notifications
- Print assignment receipt option

---

### Part 5: Payment Collection / Tracking

#### Purpose
Track payment collections for distributed lottery books with flexible payment options and detailed tracking.

#### Collection Interface

**Book/Member Selection:**
- Auto-populated list of distributed books
- Search by: Book number, Member name, Mobile, Distribution level
- Filter by: Payment status (Pending, Partial, Full)

**Payment Information Entry:**

**Book Value** (Auto-displayed, Read-only)
- Shows total value of the book
- Calculated: Tickets Per Book × Single Ticket Cost

**Payment Status** (Dropdown, Required)
- Options:
  - **Full:** Complete payment received
  - **Partial:** Partial payment received
- Default: Based on amount entered

**Amount Collected** (Numerical, Required)
- Description: Amount received from member
- Validation: Must be > 0 and ≤ Book Value
- Auto-suggest Full amount
- If < Book Value, auto-set status to "Partial"

**Payment Method** (Dropdown, Required)
- Options:
  - Cash
  - UPI
  - Other (expandable for future: Bank Transfer, Cheque, etc.)
- Default: Cash

**Collection Date** (Date picker, Required)
- Description: Date when payment was collected
- Default: Today's date
- Can select past dates

**Collected By** (Auto-filled)
- Shows logged-in Group Admin name
- Stored for audit trail

**Notes** (Textarea, Optional)
- Additional information about collection
- Max 500 characters

#### Auto-Calculations

**Amount Pending:**
```
Pending = Book Value - Total Amount Collected
```

**Example:**
- Book Value: ₹200
- First Collection: ₹100 (Partial)
- Pending: ₹100
- Second Collection: ₹100 (Full)
- Pending: ₹0

#### Collection Summary Dashboard

**Overall Statistics:**
- Total Expected Collection: Sum of all book values
- Total Collected: Sum of all collected amounts
- Total Pending: Expected - Collected
- Collection Percentage: (Collected / Expected) × 100%

**Payment Status Breakdown:**
- Books Fully Paid: Count
- Books Partially Paid: Count
- Books Unpaid: Count

**Payment Method Breakdown:**
- Cash: Amount + Count
- UPI: Amount + Count
- Other: Amount + Count

**Distribution Level-wise Collection:**
- Group by configured levels
- Show expected vs collected for each group
- Identify under-performing segments

#### Multiple Payments Support
- Allow multiple payment entries for same book
- Show payment history per book
- Running total calculation
- Date-wise payment tracking

#### UI/UX Requirements
- Card-based layout for each book
- Color indicators:
  - Red: Unpaid
  - Orange: Partial
  - Green: Full
- Quick collection buttons
- Payment history timeline
- Bulk collection entry option
- Export to Excel/PDF
- Print collection receipts

---

### Part 6: Summary & Reports

#### Purpose
Provide comprehensive reporting and analytics during the collection period with real-time insights.

#### Report Types

#### 6.1 Daily Collection Report

**Information:**
- Date-wise collection breakdown
- Number of payments received per day
- Amount collected per day
- Payment method distribution per day

**UI:**
- Calendar view with amount markers
- List view with expandable details
- Filter by date range

#### 6.2 Distribution Level-wise Report

**For Each Configured Level:**
- Level name and values
- Expected collection per value
- Actual collection per value
- Pending amount per value
- Collection percentage per value

**Example (Wing-wise):**
```
Wing A:
  Expected: ₹2,000 (10 books)
  Collected: ₹1,500
  Pending: ₹500
  Percentage: 75%

Wing B:
  Expected: ₹1,800 (9 books)
  Collected: ₹1,800
  Pending: ₹0
  Percentage: 100%
```

**Visual:**
- Bar chart comparison
- Table with drill-down capability
- Export functionality

#### 6.3 Member-wise Report

**Information:**
- Member name
- Mobile number
- Book(s) assigned
- Total book value
- Amount paid
- Amount pending
- Payment status
- Last payment date

**Features:**
- Search member
- Filter by payment status
- Sort by pending amount (highest first)
- Send payment reminders (future feature)

#### 6.4 Payment Method Report

**Breakdown:**
- Cash collections: Amount + Count
- UPI collections: Amount + Count
- Other methods: Amount + Count

**Purpose:**
- Reconciliation
- Cash in hand tracking
- Digital payment tracking

#### 6.5 Overall Summary

**Key Metrics (Dashboard Cards):**

**Total Event Value:**
- Total Books × Book Value
- Example: 50 books × ₹200 = ₹10,000

**Total Collected:**
- Sum of all collections
- Example: ₹8,500

**Total Pending:**
- Expected - Collected
- Example: ₹1,500

**Collection Percentage:**
- (Collected / Expected) × 100%
- Example: 85%

**Books Distributed:**
- Count and percentage
- Example: 45/50 (90%)

**Books Fully Paid:**
- Count and percentage
- Example: 35/45 (77.7%)

**Average Collection per Book:**
- Total Collected / Distributed Books
- Example: ₹8,500 / 45 = ₹188.89

#### 6.6 Predicted vs Actual Report

**Purpose:**
Track expected collections vs actual by time period or distribution group.

**Metrics:**
- Predicted from each group (based on distributed books)
- Actual received from each group
- Variance (positive/negative)
- Achievement percentage

**Example Table:**
```
Group      | Predicted | Actual  | Variance | Achievement
-----------|-----------|---------|----------|-------------
Wing A     | ₹2,000   | ₹1,500  | -₹500    | 75%
Wing B     | ₹1,800   | ₹1,800  | ₹0       | 100%
Wing C     | ₹1,200   | ₹900    | -₹300    | 75%
-----------|-----------|---------|----------|-------------
Total      | ₹5,000   | ₹4,200  | -₹800    | 84%
```

#### 6.7 Commission Calculation (Future Feature)

**Note:** Placeholder for commission calculation logic
- Commission percentage configuration
- Commission calculation rules
- Commission distribution tracking
- Payment to distributors tracking

**To be defined:**
- Commission structure
- Calculation formula
- Payment methods
- Reporting requirements

#### Report Features

**Export Options:**
- PDF: Formatted reports with charts
- Excel: Raw data with formulas
- CSV: Data export for external tools

**Print Options:**
- Print-friendly layouts
- Summary reports
- Detailed reports
- Custom date ranges

**Refresh Options:**
- Real-time data updates
- Manual refresh button
- Last updated timestamp

**Visual Charts:**
- Pie charts: Collection status distribution
- Bar charts: Level-wise comparison
- Line charts: Daily collection trends
- Progress bars: Collection percentage

#### UI/UX Requirements
- Tabbed interface for different report types
- Dashboard-style summary page
- Interactive charts (click to drill-down)
- Date range selectors
- Filter and sort options
- Responsive tables
- High contrast for readability
- Large fonts (senior-friendly)

---

### Lottery System - Navigation Flow

```
1. Create Event (Part 1)
   ↓
2. Generate Books (Part 2)
   ↓
3. Configure Distribution Levels (Part 3)
   ↓
4. Distribute Books (Part 4)
   ↓
5. Collect Payments (Part 5)
   ↓
6. View Reports (Part 6 - Available anytime after Part 4)
```

**Navigation Notes:**
- Can save progress at any step
- Can go back to edit (with validations)
- Part 6 (Reports) accessible during and after collection
- Quick links between all parts
- Breadcrumb navigation

---

### Lottery System - User Permissions

**Group Admin Can:**
- Create lottery events
- Generate books
- Configure distribution settings
- Distribute books
- Collect payments
- View all reports
- Edit distributions (before collection)
- Export data

**Group Admin Cannot:**
- Delete events (can only cancel)
- Modify books once distributed
- Change core settings after distribution starts

---

### Lottery System - Validation Rules

**Event Creation:**
- All required fields must be filled
- Event name must be unique per community

**Book Generation:**
- All numerical fields must be positive
- First ticket number cannot overlap with existing events
- Preview required before final generation

**Distribution Settings:**
- At least 1 level required
- Each level must have at least 1 value
- Dependent dropdowns must have parent values

**Book Distribution:**
- Cannot assign same book twice
- Must select all required distribution levels
- Cannot unassign after collection starts

**Payment Collection:**
- Amount must be positive
- Amount cannot exceed book value
- Collection date cannot be future date
- Payment method required

---

## 9. Transaction Collection System - Detailed Specifications

### Overview
The Transaction Collection System is a simple yet powerful CSV-based transaction tracking tool that enables Group Admins to upload member details, track payment status, send WhatsApp reminders, and monitor collections. Each collection campaign (e.g., November maintenance, January fees) is independent and can be created as needed.

**Key Use Case:**
- November: Create transaction collection for maintenance fees
- January: Create new lottery event
- Group Admin can create multiple collection campaigns and lottery events as per their requirements

---

### System Flow

**4-Step Process:**

1. **Upload CSV** - Import member details with name, mobile, and payment amount
2. **Send Reminders** - WhatsApp notifications to members for pending payments
3. **Track Payments** - Record payment status (Paid, Partial, Unpaid) and method
4. **Monitor Status** - View collection statistics and WhatsApp delivery status

---

### Step 1: CSV Upload & Member Import

#### Purpose
Bulk import member transaction details via CSV file upload.

#### CSV File Format

**Required Columns:**
1. Name (Member name)
2. Mobile (10-digit mobile number)
3. Amount (Payment amount expected)

**Sample CSV:**
```csv
Name,Mobile,Amount
John Doe,9876543210,5000
Jane Smith,9876543211,5000
Robert Wilson,9876543212,3000
Maria Garcia,9876543213,5000
```

#### Upload Interface

**Collection Campaign Details:**
- **Campaign Name** (Text, Required)
  - Example: "November 2025 Maintenance", "Diwali Fund Collection"
  - Validation: Maximum 150 characters

- **Description** (Textarea, Optional)
  - Additional details about the collection
  - Maximum 500 characters

- **Due Date** (Date picker, Optional)
  - Payment due date for this collection
  - Used in reminder messages

**CSV Upload:**
- File upload button (accepts .csv only)
- Maximum file size: 5MB
- Sample CSV download link
- Upload and preview functionality

#### Validation & Preview

**CSV Validation:**
- Check for required columns (Name, Mobile, Amount)
- Validate mobile numbers (10 digits, numeric)
- Validate amounts (positive numbers)
- Check for duplicate mobile numbers
- Show error messages for invalid rows

**Preview Table:**
Display uploaded data before final import:
- Serial number
- Name
- Mobile number
- Amount
- Status (Valid/Invalid with reason)

**Actions:**
- Edit individual entries before import
- Remove invalid rows
- Confirm and import
- Cancel and re-upload

#### Database Storage

After successful validation and confirmation:
- Create new collection campaign
- Import all members with initial status "Unpaid"
- Set payment method as empty initially
- WhatsApp status as "Not Sent"

---

### Step 2: WhatsApp Reminder System

#### Purpose
Send payment reminders to members via WhatsApp.

#### Reminder Interface

**Select Members:**
- View all members in the campaign
- Filter by payment status:
  - All members
  - Unpaid only
  - Partial payment only
  - Paid (for re-confirmation messages)
- Bulk select or individual selection

**Reminder Message:**

**Default Message Template:**
```
Hello [Name],

This is a reminder for [Campaign Name].
Amount Due: ₹[Amount]
Due Date: [Due Date]

Please make the payment at your earliest convenience.

- [Community Name]
```

**Message Customization:**
- Edit message template
- Variables auto-replaced:
  - [Name]
  - [Amount]
  - [Due Date]
  - [Campaign Name]
  - [Community Name]

**Send Options:**
- Send to selected members
- Schedule for later (optional feature for Phase 2)
- Send immediately

#### WhatsApp Integration

**Technical Implementation:**
- WhatsApp Business API integration
- Message queue system
- Delivery status tracking

**Status Tracking:**
- Sent: Message sent successfully
- Delivered: Message delivered to recipient
- Read: Message read by recipient (if available)
- Failed: Message failed to send
- Pending: In queue

**Note:** WhatsApp Business API requires registration and approval. Alternative for Phase 1 can be manual WhatsApp messaging with status update interface.

#### Phase 1 Simplified Approach (Manual Tracking)

Since WhatsApp API integration may require time:

**Manual Send + Track:**
- System generates message text for each member
- Copy message button for each member
- Group Admin manually sends via WhatsApp
- Mark as "Sent" after sending
- Track delivery status manually

**Features:**
- Pre-formatted messages ready to copy
- One-click copy button per member
- Click member's mobile to open WhatsApp directly
- Mark as sent after sending manually
- Track sent date and time

---

### Step 3: Payment Tracking & Status Update

#### Purpose
Record payment status and method for each member.

#### Payment Entry Interface

**Member List View:**
- Search by name or mobile
- Filter by payment status
- Sort by name, amount, status
- Color coding:
  - Red: Unpaid
  - Orange: Partial
  - Green: Paid

**Quick Status Update:**
For each member, display:
- Name
- Mobile number
- Expected amount
- Current status
- Amount paid
- Amount pending

**Update Payment Form:**

**Payment Status** (Dropdown, Required)
- **Paid:** Full payment received
- **Partial:** Partial payment received
- **Unpaid:** No payment received

**Amount Paid** (Numerical, Conditional)
- Required if status is Paid or Partial
- Validation: Must be > 0 and ≤ Expected Amount
- Auto-calculate pending amount

**Payment Method** (Dropdown, Conditional)
- Required if status is Paid or Partial
- Options:
  - Cash
  - UPI
  - Bank Transfer
  - Cheque
  - Other

**Payment Date** (Date picker, Conditional)
- Required if status is Paid or Partial
- Default: Today's date
- Can select past dates

**Notes** (Textarea, Optional)
- Additional information
- Transaction reference number
- Any special notes

**WhatsApp Status Update:**
- Capture if member confirmed via WhatsApp
- Options:
  - Confirmed via WhatsApp
  - Confirmed via Call
  - Confirmed in Person
  - Not Confirmed

#### Multiple Payments Support

For Partial payments, allow adding subsequent payments:
- Show payment history
- Add new payment entry
- Running total calculation
- Auto-update status to "Paid" when total reaches expected amount

**Example:**
```
Expected: ₹5,000
Payment 1: ₹2,000 (Partial) - Cash - 01-Nov-2025
Payment 2: ₹3,000 (Paid) - UPI - 05-Nov-2025
Status: Paid
```

---

### Step 4: Dashboard & Reporting

#### Purpose
Monitor collection progress and view comprehensive statistics.

#### Dashboard Overview

**Summary Cards:**

1. **Total Members:** Count of members in campaign
2. **Total Expected:** Sum of all expected amounts
3. **Total Collected:** Sum of all payments received
4. **Total Pending:** Expected - Collected
5. **Collection Percentage:** (Collected / Expected) × 100%

**Payment Status Breakdown:**
- Members Fully Paid: Count + Amount
- Members Partially Paid: Count + Amount Paid + Amount Pending
- Members Unpaid: Count + Amount

**Payment Method Breakdown:**
- Cash: Amount + Count
- UPI: Amount + Count
- Bank Transfer: Amount + Count
- Cheque: Amount + Count
- Other: Amount + Count

**WhatsApp Status Breakdown:**
- Messages Sent: Count
- Delivered: Count
- Read: Count
- Failed: Count
- Pending: Count

#### Reports

**1. Member-wise Report**

Display for each member:
- Name
- Mobile
- Expected amount
- Amount paid
- Amount pending
- Payment status
- Payment method
- Payment date
- WhatsApp status
- Notes

**Features:**
- Search and filter
- Sort by any column
- Export to Excel/CSV
- Print report

**2. Payment Method Report**

Group by payment method:
- Method name
- Total amount collected
- Number of transactions
- Percentage of total collection

**3. Daily Collection Report**

Date-wise breakdown:
- Collection date
- Number of payments
- Amount collected
- Payment methods used

**4. Outstanding Report**

Focus on pending payments:
- Member name and mobile
- Amount pending
- Days overdue (if due date set)
- Last reminder sent date
- Sort by highest pending first

#### Export Options

- **Excel:** Detailed report with formulas
- **CSV:** Raw data for external tools
- **PDF:** Formatted report with summary

#### Visual Charts

- Pie chart: Payment status distribution
- Bar chart: Payment method comparison
- Line chart: Daily collection trend
- Progress bar: Collection percentage

---

### Database Schema - Transaction Collection

#### Transaction Campaigns Table
```sql
CREATE TABLE transaction_campaigns (
    campaign_id INT PRIMARY KEY AUTO_INCREMENT,
    community_id INT NOT NULL,
    campaign_name VARCHAR(150) NOT NULL,
    description TEXT,
    due_date DATE NULL,
    total_members INT DEFAULT 0,
    total_expected DECIMAL(10,2) DEFAULT 0,
    total_collected DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

#### Campaign Members Table
```sql
CREATE TABLE campaign_members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    member_name VARCHAR(150) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    expected_amount DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    payment_method ENUM('cash', 'upi', 'bank_transfer', 'cheque', 'other') NULL,
    payment_date DATE NULL,
    whatsapp_status ENUM('not_sent', 'sent', 'delivered', 'read', 'failed') DEFAULT 'not_sent',
    whatsapp_sent_date TIMESTAMP NULL,
    confirmation_method ENUM('whatsapp', 'call', 'in_person', 'not_confirmed') NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES transaction_campaigns(campaign_id)
);
```

#### Payment History Table
```sql
CREATE TABLE payment_history (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'upi', 'bank_transfer', 'cheque', 'other') NOT NULL,
    payment_date DATE NOT NULL,
    recorded_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES campaign_members(member_id),
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
);
```

#### WhatsApp Messages Table
```sql
CREATE TABLE whatsapp_messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    message_text TEXT NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed') DEFAULT 'pending',
    sent_date TIMESTAMP NULL,
    delivered_date TIMESTAMP NULL,
    read_date TIMESTAMP NULL,
    error_message TEXT NULL,
    sent_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES campaign_members(member_id),
    FOREIGN KEY (sent_by) REFERENCES users(user_id)
);
```

---

### Transaction Collection - Key Features

#### Independent Campaigns
- Group Admin can create multiple campaigns simultaneously
- Each campaign is independent (e.g., Nov maintenance, Dec event fund)
- No limit on number of active campaigns

#### Flexibility
- Create transaction collection in any month
- Create lottery events in any month
- Mix and match as per community needs

**Example Scenario:**
```
November 2025:
- Transaction Collection: "November Maintenance"
- Transaction Collection: "Diwali Celebration Fund"

December 2025:
- Lottery Event: "Christmas Lucky Draw"

January 2026:
- Transaction Collection: "January Maintenance"
- Lottery Event: "New Year Lottery"
```

#### CSV Re-upload
- Can upload new CSV to add more members to existing campaign
- Duplicate check based on mobile number
- Option to update existing member details or skip

---

### Transaction Collection - Validation Rules

**CSV Upload:**
- File must be .csv format
- Required columns must be present
- Mobile numbers must be 10 digits
- Amounts must be positive numbers
- No duplicate mobile numbers in single upload

**Payment Entry:**
- Amount paid cannot exceed expected amount
- Payment date cannot be future date
- Payment method required when recording payment
- Status must match amount (Paid = Full amount)

**WhatsApp Reminders:**
- Cannot send to members with invalid mobile numbers
- Minimum 1 member must be selected
- Message cannot be empty

---

### Transaction Collection - User Permissions

**Group Admin Can:**
- Create transaction campaigns
- Upload CSV files
- Send WhatsApp reminders
- Update payment status
- View all reports
- Export data
- Mark campaigns as completed

**Group Admin Cannot:**
- Delete campaigns (can only cancel)
- Edit expected amounts after import (only via notes)
- Modify payment history (only add new payments)

---

### Phase 1 Implementation Approach

**Simplified WhatsApp Integration:**
- Manual sending with tracking (copy-paste messages)
- WhatsApp link generation (wa.me links)
- Manual status update
- **Phase 2:** Full WhatsApp Business API integration

**Focus Areas:**
1. CSV upload and validation (robust)
2. Payment tracking (comprehensive)
3. Dashboard and reporting (detailed)
4. Manual WhatsApp workflow (easy to use)

---

## 10. Key Pages Structure

### Admin Pages
1. `/admin/login` - Login page
2. `/admin/dashboard` - Main dashboard
3. `/admin/group-admins` - List all Group Admins
4. `/admin/group-admins/create` - Create new Group Admin
5. `/admin/group-admins/edit/:id` - Edit Group Admin
6. `/admin/communities` - List all communities
7. `/admin/communities/create` - Create new community
8. `/admin/communities/edit/:id` - Edit community
9. `/admin/settings` - Admin settings
10. `/admin/profile` - Admin profile

### Group Admin Pages
1. `/group-admin/login` - Login page
2. `/group-admin/dashboard` - Main dashboard
3. `/group-admin/lottery/events` - List all lottery events
4. `/group-admin/lottery/create/step1` - Create event (Part 1)
5. `/group-admin/lottery/create/step2/:event_id` - Generate books (Part 2)
6. `/group-admin/lottery/create/step3/:event_id` - Distribution settings (Part 3)
7. `/group-admin/lottery/distribute/:event_id` - Distribute books (Part 4)
8. `/group-admin/lottery/collect/:event_id` - Collect payments (Part 5)
9. `/group-admin/lottery/reports/:event_id` - View reports & summary (Part 6)
10. `/group-admin/lottery/preview/:event_id` - Preview lottery books
11. `/group-admin/transactions/campaigns` - List all transaction campaigns
12. `/group-admin/transactions/create` - Create new campaign & upload CSV
13. `/group-admin/transactions/reminders/:campaign_id` - Send WhatsApp reminders
14. `/group-admin/transactions/track/:campaign_id` - Track payments
15. `/group-admin/transactions/reports/:campaign_id` - View reports & dashboard
16. `/group-admin/settings` - Group Admin settings
17. `/group-admin/profile` - Group Admin profile

---

## 10. Development Phases

### Phase 1.1 - Foundation (Week 1-2)
- [ ] Project setup and folder structure
- [ ] Database design and creation
- [ ] Create all database tables
- [ ] Basic authentication system
- [ ] Admin login page
- [ ] Group Admin login page

### Phase 1.2 - Admin Panel (Week 3-4)
- [ ] Admin dashboard
- [ ] Group Admin CRUD operations
- [ ] Community CRUD operations
- [ ] Activity logging

### Phase 1.3 - Lottery System - Part 1 & 2 (Week 5-6)
- [ ] Event creation page (Part 1)
- [ ] Book generation page (Part 2)
- [ ] Auto-calculation logic implementation
- [ ] Preview lottery books feature
- [ ] Database integration for events and books

### Phase 1.4 - Lottery System - Part 3 & 4 (Week 7-8)
- [ ] Distribution settings configuration (Part 3)
- [ ] Multi-level dropdown logic
- [ ] CSV upload functionality
- [ ] Book distribution interface (Part 4)
- [ ] Distribution statistics dashboard

### Phase 1.5 - Lottery System - Part 5 & 6 (Week 9-10)
- [ ] Payment collection interface (Part 5)
- [ ] Multiple payment support
- [ ] Collection dashboard
- [ ] Reports and summary page (Part 6)
- [ ] Export to PDF/Excel functionality
- [ ] Charts and visualizations

### Phase 1.6 - Transaction Collection System (Week 11-12)
- [ ] Campaign creation page
- [ ] CSV upload and validation
- [ ] Preview and import functionality
- [ ] WhatsApp reminder interface (manual workflow)
- [ ] Payment tracking interface
- [ ] Multiple payment support
- [ ] Dashboard and reports

### Phase 1.7 - Integration & Polish (Week 13-14)
- [ ] Group Admin dashboard with lottery & transaction summaries
- [ ] Profile management
- [ ] Navigation and breadcrumbs
- [ ] Overall UI polish
- [ ] Responsive design refinement
- [ ] Performance optimization

### Phase 1.8 - Testing & Refinement (Week 15-16)
- [ ] User acceptance testing with 1 Admin + 1 Group Admin
- [ ] Complete lottery workflow testing
- [ ] Complete transaction collection workflow testing
- [ ] CSV upload/validation testing
- [ ] Payment tracking testing
- [ ] Report generation testing
- [ ] Bug fixes and improvements
- [ ] Security audit
- [ ] Documentation updates
- [ ] User guide creation

---

## 11. Testing Strategy

### Testing Scope
- **Users:** 1 Admin + 1 Group Admin
- **Duration:** 2-4 weeks
- **Objective:** Validate concept and gather feedback

### Testing Checklist

**Authentication:**
- [ ] Login/Logout functionality
- [ ] Password change functionality
- [ ] Session management

**Admin Panel:**
- [ ] Admin can create Group Admin
- [ ] Admin can edit Group Admin details
- [ ] Admin can deactivate Group Admin
- [ ] Admin can create communities
- [ ] Activity logging works correctly

**Lottery System - Part 1:**
- [ ] Create lottery event
- [ ] Event name validation
- [ ] Save as draft functionality

**Lottery System - Part 2:**
- [ ] Auto-calculation of books works correctly
- [ ] Preview lottery books displays accurately
- [ ] Book generation creates correct records
- [ ] Edit and regenerate books

**Lottery System - Part 3:**
- [ ] Configure 1-level distribution
- [ ] Configure 2-level dependent dropdown
- [ ] Configure 3-level dependent dropdown
- [ ] Manual value entry works
- [ ] CSV upload works correctly
- [ ] Preview distribution structure

**Lottery System - Part 4:**
- [ ] Distribute single book
- [ ] Distribute multiple books
- [ ] Dependent dropdowns work correctly
- [ ] Cannot assign same book twice
- [ ] Distribution statistics are accurate
- [ ] Edit distribution before collection

**Lottery System - Part 5:**
- [ ] Collect full payment
- [ ] Collect partial payment
- [ ] Multiple payments for same book
- [ ] Payment calculations are correct
- [ ] Collection statistics update in real-time
- [ ] Filter and search work correctly

**Lottery System - Part 6:**
- [ ] Daily collection report displays correctly
- [ ] Distribution level-wise report works
- [ ] Member-wise report is accurate
- [ ] Payment method report calculates correctly
- [ ] Overall summary shows correct metrics
- [ ] Export to PDF works
- [ ] Export to Excel works
- [ ] Charts render properly

**Transaction Collection:**
- [ ] Create new campaign
- [ ] Upload CSV file
- [ ] CSV validation works correctly
- [ ] Preview imported data
- [ ] Edit entries before import
- [ ] Confirm and import members
- [ ] Generate WhatsApp messages
- [ ] Copy message functionality
- [ ] WhatsApp link generation (wa.me)
- [ ] Mark messages as sent
- [ ] Update payment status (Paid/Partial/Unpaid)
- [ ] Record payment method
- [ ] Multiple payments for same member
- [ ] Payment calculations are correct
- [ ] Dashboard statistics accurate
- [ ] Member-wise report displays correctly
- [ ] Payment method report works
- [ ] Daily collection report works
- [ ] Outstanding report shows pending amounts
- [ ] Export to Excel/CSV works
- [ ] Charts render properly

**General:**
- [ ] Responsive design on different screen sizes
- [ ] Cross-browser compatibility (Chrome, Firefox, Safari, Edge)
- [ ] Data validation and error handling
- [ ] Security testing (SQL injection, XSS prevention)
- [ ] Senior-friendly UI (40-60 age group)
- [ ] Page load performance
- [ ] Print functionality works
- [ ] CSV file upload security

---

## 12. Security Considerations

### Authentication & Authorization
- Strong password requirements (min 8 chars, alphanumeric + special chars)
- Password hashing (bcrypt with salt)
- Session management with timeout
- CSRF token protection
- Role-based access control

### Data Security
- Input validation and sanitization
- SQL injection prevention (prepared statements)
- XSS protection
- Secure database connections
- HTTPS for production
- CSV file upload validation (file type, size, content)
- Secure file storage
- Sanitize CSV data before database insert

### Privacy
- Mobile number encryption
- Activity logging for audit trail
- Data backup and recovery plan

---

## 13. Future Roadmap (Post Phase 1)

### Phase 2
- Group Member features
- Member registration and management
- Enhanced communication features
- Notifications system

### Phase 3
- Mobile application (iOS & Android)
- Push notifications
- Mobile-specific features

### Phase 4
- Transaction Collection System - Full implementation
- Payment gateway integration
- Commission calculation system for lottery

### Phase 5
- Advanced analytics and reporting
- Multi-group management
- API for third-party integrations

---

## 14. Success Metrics - Phase 1

### Technical Metrics
- Page load time < 2 seconds
- Zero critical security vulnerabilities
- 99% uptime during testing period
- Mobile responsive across all pages

### User Experience Metrics
- User can complete login in < 30 seconds
- Admin can create Group Admin in < 2 minutes
- Clear error messages with resolution steps
- Positive feedback from test users (40-60 age group)

### Feature Completion
- 100% of Phase 1 features implemented
- All critical bugs resolved
- Documentation complete and up-to-date

---

## 15. Project Risks & Mitigation

### Risk 1: User Adoption (Age Group 40-60)
**Mitigation:**
- Extra focus on UI/UX simplicity
- Comprehensive user guide with screenshots
- Video tutorials for key features

### Risk 2: Technical Complexity
**Mitigation:**
- Start with MVP features only
- Incremental development approach
- Regular testing cycles

### Risk 3: Security Vulnerabilities
**Mitigation:**
- Follow security best practices
- Regular security audits
- Secure coding standards

---

## 16. Pending Information Required

### Lottery System
- [x] Detailed use case explanation - ✅ COMPLETED
- [x] User flow and workflows - ✅ COMPLETED
- [x] Business rules and logic - ✅ COMPLETED
- [x] UI/UX requirements - ✅ COMPLETED
- [ ] Commission calculation logic (Future feature)

### Transaction Collection System
- [x] Detailed use case explanation - ✅ COMPLETED
- [x] Payment methods to support - ✅ COMPLETED
- [x] Transaction flow - ✅ COMPLETED
- [x] Reporting requirements - ✅ COMPLETED
- [ ] WhatsApp Business API integration (Phase 2)

---

## 17. Project Timeline

**Start Date:** TBD
**Phase 1 Completion Target:** 16 weeks from start
**Testing Period:** 2-4 weeks
**Go-Live Decision:** Based on testing feedback

**Breakdown:**
- Foundation: 2 weeks
- Admin Panel: 2 weeks
- Lottery System (Parts 1-6): 6 weeks
- Transaction Collection System: 2 weeks
- Integration & Polish: 2 weeks
- Testing: 2 weeks

---

## 18. Contact & Collaboration

**Project Status:** Planning & Documentation Phase
**Next Steps:**
1. ✅ Lottery System use case - COMPLETED
2. ✅ Transaction Collection System use case - COMPLETED
3. Review and approve project documentation
4. Finalize technology stack (recommend PHP for backend)
5. Set project start date
6. Begin development

---

## 19. Document Control

**Version:** 3.0
**Last Updated:** 2025-11-28
**Status:** Complete - Ready for Review & Approval
**Next Review:** Before development kickoff

**Change Log:**
- v3.0 (2025-11-28): Added complete Transaction Collection System specifications (4 steps)
- v3.0 (2025-11-28): Added database schema for transaction collection
- v3.0 (2025-11-28): Updated development phases to 16 weeks
- v3.0 (2025-11-28): Added transaction collection testing checklist
- v3.0 (2025-11-28): Updated security considerations for CSV uploads
- v3.0 (2025-11-28): Marked both major features as COMPLETED
- v2.0 (2025-11-28): Added complete Lottery System specifications (6 parts)
- v2.0 (2025-11-28): Added database schema for lottery system
- v1.0 (2025-11-28): Initial project documentation

---

## Appendix

### A. Folder Structure (Proposed)
```
gettoknow/
│
├── assets/
│   ├── css/
│   │   ├── main.css
│   │   ├── admin.css
│   │   └── group-admin.css
│   ├── js/
│   │   ├── common.js
│   │   ├── admin.js
│   │   └── group-admin.js
│   └── images/
│       ├── logo.png
│       └── icons/
│
├── admin/
│   ├── index.php (dashboard)
│   ├── login.php
│   ├── group-admins/
│   │   ├── list.php
│   │   ├── create.php
│   │   └── edit.php
│   ├── communities/
│   │   ├── list.php
│   │   ├── create.php
│   │   └── edit.php
│   └── includes/
│       ├── header.php
│       ├── footer.php
│       └── sidebar.php
│
├── group-admin/
│   ├── index.php (dashboard)
│   ├── login.php
│   ├── lottery/
│   │   ├── events.php (list all events)
│   │   ├── create-step1.php (event creation)
│   │   ├── create-step2.php (book generation)
│   │   ├── create-step3.php (distribution settings)
│   │   ├── distribute.php (book distribution)
│   │   ├── collect.php (payment collection)
│   │   ├── reports.php (summary & reports)
│   │   └── preview.php (preview books)
│   ├── transactions/
│   │   ├── campaigns.php (list all campaigns)
│   │   ├── create.php (create campaign & upload CSV)
│   │   ├── reminders.php (send WhatsApp reminders)
│   │   ├── track.php (track payments)
│   │   └── reports.php (dashboard & reports)
│   └── includes/
│       ├── header.php
│       ├── footer.php
│       └── sidebar.php
│
├── includes/
│   ├── config.php
│   ├── db-connect.php
│   ├── functions.php
│   └── session.php
│
└── index.php (landing page)
```

### B. Design Reference
- **Inspiration:** MyGate app design patterns
- **Color Palette:** TBD based on brand guidelines
- **Component Library:** Custom components with focus on accessibility

---

*End of Document*
