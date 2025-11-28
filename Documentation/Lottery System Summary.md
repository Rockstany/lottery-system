# Lottery System - Quick Reference Guide

## Overview
The Lottery System enables Group Admins to create, manage, and track community lottery events for occasions like Diwali, New Year, etc.

---

## 6-Part System Flow

### Part 1: Introduction / Event Creation
**Purpose:** Create new lottery events

**Key Fields:**
- Event Name (e.g., "Diwali 2025 Lottery")
- Event Description
- Group Name/ID (auto-populated)

**Actions:**
- Save as Draft
- Proceed to Next Step

---

### Part 2: Lottery Creation / Book Generation
**Purpose:** Auto-generate lottery books with ticket numbers

**Key Fields:**
- First Ticket Number (e.g., 100)
- Single Ticket Cost (e.g., ₹20)
- No. of Tickets per Book (e.g., 10)
- Total No. of Books (e.g., 5)

**Auto-Calculation:**
```
Book 1: Tickets 100-109, Value: ₹200
Book 2: Tickets 110-119, Value: ₹200
Book 3: Tickets 120-129, Value: ₹200
...
Total: 50 tickets, ₹1,000
```

**Features:**
- Preview Books
- Real-time calculation
- Edit before finalizing

---

### Part 3: Distribution Settings
**Purpose:** Configure multi-level categorization (1-3 levels)

**Configuration:**
- Select number of levels (1, 2, or 3)
- Define level headings (e.g., Wing, Floor, Flat)
- Add values manually or via CSV
- Create dependent dropdowns

**Example (3 Levels):**
- Level 1: Wing → A, B, C
- Level 2: Floor → 1, 2, 3, 4 (depends on Wing)
- Level 3: Flat → 101, 102, 103 (depends on Floor)

---

### Part 4: Book Distribution
**Purpose:** Assign lottery books to members

**Process:**
1. Select book(s)
2. Choose distribution levels (Wing → Floor → Flat)
3. Enter member details (optional)
4. Assign book

**Business Rules:**
- One book = One member only
- Cannot reassign after collection starts
- Can edit before collection

**Dashboard:**
- Total Books
- Distributed Books
- Available Books
- Distribution Percentage

---

### Part 5: Payment Collection
**Purpose:** Track payments for distributed books

**Payment Entry:**
- Book Value (auto-calculated)
- Amount Collected
- Payment Status (Full/Partial)
- Payment Method (Cash/UPI/Other)
- Collection Date
- Notes

**Features:**
- Multiple payments per book
- Auto-calculate pending amount
- Filter by payment status
- Collection statistics

**Example:**
```
Book Value: ₹200
Payment 1: ₹100 (Partial) → Pending: ₹100
Payment 2: ₹100 (Full) → Pending: ₹0
```

---

### Part 6: Summary & Reports
**Purpose:** Comprehensive reporting during collection

**Report Types:**

1. **Daily Collection Report**
   - Date-wise breakdown
   - Amount per day
   - Payment method distribution

2. **Distribution Level-wise Report**
   - Expected vs Actual by Wing/Floor/etc.
   - Collection percentage per level

3. **Member-wise Report**
   - Payment status per member
   - Pending amounts

4. **Payment Method Report**
   - Cash vs UPI vs Other
   - Reconciliation data

5. **Overall Summary**
   - Total Expected Collection
   - Total Collected
   - Total Pending
   - Collection Percentage

6. **Predicted vs Actual Report**
   - Group-wise variance
   - Achievement percentage

**Export Options:**
- PDF (with charts)
- Excel (with formulas)
- CSV (raw data)

---

## Key Statistics Tracked

### Event Level
- Total Books
- Total Tickets
- Total Value
- Books Distributed
- Books Available

### Collection Level
- Total Expected: ₹10,000
- Total Collected: ₹8,500
- Total Pending: ₹1,500
- Collection %: 85%

### Payment Status
- Fully Paid Books
- Partially Paid Books
- Unpaid Books

### Distribution Level
- Wing A: 75% collected
- Wing B: 100% collected
- Wing C: 60% collected

---

## Navigation Flow

```
Create Event → Generate Books → Configure Levels → Distribute Books → Collect Payments → View Reports
   (Part 1)      (Part 2)         (Part 3)          (Part 4)         (Part 5)        (Part 6)
```

**Note:** Reports (Part 6) accessible anytime after Part 4

---

## Database Tables

1. **lottery_events** - Event information
2. **lottery_books** - Generated lottery books
3. **distribution_levels** - Level configuration
4. **distribution_level_values** - Dropdown values
5. **book_distribution** - Book assignments
6. **payment_collections** - Payment tracking

---

## User Permissions

**Group Admin Can:**
- Create/edit lottery events
- Generate books
- Configure distribution
- Distribute books
- Collect payments
- View all reports
- Export data

**Group Admin Cannot:**
- Delete events (can only cancel)
- Modify books after distribution
- Reassign books after collection starts

---

## Validation Rules

**Event Creation:**
- Event name required and unique
- All fields validated

**Book Generation:**
- Positive numbers only
- No ticket overlap with existing events
- Preview required before generation

**Distribution:**
- Cannot assign same book twice
- All levels must be selected
- Cannot unassign after collection

**Payment:**
- Amount > 0 and ≤ Book Value
- Collection date cannot be future
- Payment method required

---

## Commission Calculation
**Status:** Future Feature (Phase 4)
- Placeholder in reports
- To be defined later

---

## Senior-Friendly Design (Age 40-60)

**Typography:**
- Large fonts (16px+ body, 20px+ headings)
- Clear, readable fonts
- High contrast

**Layout:**
- Card-based design
- Generous white space
- Simple navigation
- Large buttons (44x44px minimum)

**Interactions:**
- Clear CTAs
- Visual feedback
- Loading states
- Helpful tooltips
- Confirmation dialogs

---

## Quick Reference: Example Scenario

**Event:** Diwali 2025 Lottery

**Setup:**
- First Ticket: 1001
- Tickets per Book: 10
- Total Books: 20
- Ticket Cost: ₹50
- **Total Value: ₹10,000**

**Distribution Levels:**
- Wing: A, B, C
- Floor: 1, 2, 3, 4
- Flat: 101-104 per floor

**Assignment Example:**
- Book 1 (1001-1010) → Wing A, Floor 1, Flat 101, John Doe
- Book 2 (1011-1020) → Wing A, Floor 1, Flat 102, Jane Smith

**Collection Example:**
- Book 1: ₹500 collected (Full) via UPI on 01-Dec-2025
- Book 2: ₹300 collected (Partial) via Cash on 02-Dec-2025

**Report:**
- Wing A: Expected ₹5,000, Collected ₹4,000 (80%)
- Wing B: Expected ₹3,000, Collected ₹3,000 (100%)
- Wing C: Expected ₹2,000, Collected ₹1,200 (60%)
- **Overall: 82% collection**

---

## Next Steps

1. ✅ Lottery System specification - COMPLETED
2. ⏳ Transaction Collection System - PENDING
3. Approve documentation
4. Begin development (14 weeks)

---

*Last Updated: 2025-11-28*
*Version: 2.0*
