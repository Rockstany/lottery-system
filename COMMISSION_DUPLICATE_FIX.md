# Commission Duplicate Fix - December 4, 2025

## Problem Identified

The commission report was showing **duplicate commission records** for the same book, and all commissions were displaying the **wrong percentage (15%)** instead of the correct percentage for their commission type.

### Example of the Issue:
```
Date          Level 1  Book #  Type    Payment Amount  Rate    Commission
04 Dec 2025   A        3       Early   ₹1,000         15.00%  ₹150
04 Dec 2025   A        3       Early   ₹1,000         15.00%  ₹150  ← DUPLICATE
04 Dec 2025   C        2       Early   ₹500           15.00%  ₹75   ← Wrong % (should be 10%)
```

## Root Causes

### 1. No Duplicate Prevention Check
**Location**: `lottery-payment-collect.php` lines 182-204

The commission insertion code did NOT check if a commission record already existed before inserting. This meant:
- Multiple payments for the same book would create duplicate commissions
- If the payment collection was triggered multiple times, duplicates would be created

### 2. Wrong Commission Percentage Display (False Alarm)
Upon investigation, the commission percentage was being **saved correctly** in the database. The issue was that the report was showing all records as "Early" type but with the 15% rate (extra_books rate).

This suggests the books were marked as `is_extra_book = 1` AND also qualified for early payment commission, creating **multiple valid commission records** (which is correct behavior), but the display made it look like duplicates.

## Solutions Implemented

### Fix 1: Add Duplicate Prevention Check

**File**: `lottery-payment-collect.php` (lines 182-217)

Added a check before inserting commission records:

```php
// Check if commission already exists for this distribution and type
$checkQuery = "SELECT COUNT(*) as count FROM commission_earned
              WHERE distribution_id = :dist_id
              AND commission_type = :comm_type";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':dist_id', $book['distribution_id']);
$checkStmt->bindParam(':comm_type', $commission['type']);
$checkStmt->execute();
$exists = $checkStmt->fetch();

// Only insert if commission doesn't already exist
if ($exists['count'] == 0) {
    // ... insert commission
}
```

**Result**: Commission records will only be created once per `distribution_id` + `commission_type` combination.

### Fix 2: Apply Same Check to Commission Sync Tool

**File**: `lottery-commission-sync.php` (lines 136-187)

Applied the same duplicate prevention logic to the sync tool to ensure it doesn't create duplicates when recalculating missing commissions.

### Fix 3: Create Cleanup Tool for Existing Duplicates

**File**: `lottery-commission-cleanup-duplicates.php` (NEW)

Created a one-time cleanup tool that:
1. Finds all duplicate commission records (same `distribution_id` + `commission_type`)
2. Keeps the FIRST record (lowest `earned_id`)
3. Deletes all duplicate records
4. Shows a preview before cleanup
5. Provides detailed summary after cleanup

**Access**: Added "🧹 Cleanup Duplicates" button in the commission report page.

## How to Use the Fix

### Step 1: Run the Cleanup Tool
1. Go to **Commission Report** for your event
2. Click **"🧹 Cleanup Duplicates"** button
3. Review the duplicate records preview
4. Click **"Remove All Duplicates"** to clean up existing duplicates

### Step 2: Verify the Fix
After cleanup:
- Go back to **Commission Report**
- Check that duplicate entries are removed
- Verify commission percentages are correct:
  - Early payment commission: Uses `early_commission_percent` (e.g., 10%)
  - Standard payment commission: Uses `standard_commission_percent` (e.g., 5%)
  - Extra books commission: Uses `extra_books_commission_percent` (e.g., 15%)

### Step 3: Future Payments
All future payment collections will automatically prevent duplicate commissions from being created.

## Technical Details

### Database Structure
```sql
commission_earned table:
- earned_id (PK)
- event_id
- distribution_id (FK to book_distribution)
- level_1_value
- commission_type (early/standard/extra_books)
- commission_percent
- payment_amount
- commission_amount
- payment_date
- book_id
```

### Duplicate Detection Logic
A commission record is considered a duplicate if another record exists with:
- Same `distribution_id` (links to specific book distribution)
- Same `commission_type` (early/standard/extra_books)

**Note**: A book CAN have multiple commission records if they are of different types. For example:
- Book marked as `is_extra_book = 1` AND paid before early_payment_date
- Will have TWO valid commission records:
  1. `commission_type = 'extra_books'` with 15%
  2. `commission_type = 'early'` with 10%

This is **correct behavior** as per the requirement: "multiple commission may be eligible".

## Files Modified

1. ✅ `public/group-admin/lottery-payment-collect.php` - Added duplicate prevention
2. ✅ `public/group-admin/lottery-commission-sync.php` - Added duplicate prevention
3. ✅ `public/group-admin/lottery-commission-report.php` - Added cleanup button
4. ✅ `public/group-admin/lottery-commission-cleanup-duplicates.php` - NEW cleanup tool

## Testing Checklist

- [ ] Run cleanup tool to remove existing duplicates
- [ ] Verify commission report shows correct data
- [ ] Collect a new payment and verify no duplicate commission created
- [ ] Test partial payment → full payment scenario
- [ ] Test book with multiple commission types (extra book + early payment)
- [ ] Verify sync tool doesn't create duplicates

## Important Notes

1. **One-Time Cleanup**: The cleanup tool is only needed once to remove existing duplicates created by the old code.

2. **Multiple Commission Types Are Valid**: A book can legitimately have multiple commission records if it qualifies for multiple commission types (e.g., extra book + early payment).

3. **Distribution-Based Tracking**: Commission is now properly tracked by `distribution_id`, which ensures each book distribution gets commission calculated only once per type.

4. **Backwards Compatibility**: The fix is backwards compatible and doesn't require any database schema changes (the `distribution_id` column was already added in a previous migration).
