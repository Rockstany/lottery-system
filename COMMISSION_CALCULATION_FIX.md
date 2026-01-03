# Commission Calculation Fix - Excel Upload

**Date:** 2026-01-03
**Issue:** Commission not being calculated during Excel uploads
**Status:** ✅ FIXED

---

## Problem

When payments were uploaded via Excel, commissions were **not being calculated at all**, even when the payment made a book fully paid.

### Impact Before Fix:
- ❌ Excel uploads processed payments but skipped commission calculation
- ❌ Commissions only calculated when payments entered manually
- ❌ Inconsistent commission records between manual and bulk uploads
- ❌ Lost commission tracking for bulk payment updates

---

## Root Cause

The Excel upload processor ([lottery-reports-excel-upload.php](public/group-admin/lottery-reports-excel-upload.php)) had **NO commission calculation logic**.

**Evidence:**
```bash
$ grep -n "commission" lottery-reports-excel-upload.php
No matches found
```

Meanwhile, manual payment collection ([lottery-payment-collect.php](public/group-admin/lottery-payment-collect.php)) has comprehensive commission logic (lines 125-219).

**Result:** Commission calculation was completely missing from Excel upload flow.

---

## The Fix

### Changes Made

Added commission calculation logic to **lottery-reports-excel-upload.php** in TWO places:

1. **Main Data Sheet Processing** (after line 260)
2. **Multiple Payments Sheet Processing** (after line 532)

Both locations now calculate commission immediately after a payment INSERT or UPDATE.

---

## Commission Calculation Logic

### When Commission is Calculated:

Commission is **ONLY** calculated when ALL these conditions are met:

1. ✅ Payment has been recorded (INSERT or UPDATE)
2. ✅ Total payments for the book >= Expected amount (Fully Paid)
3. ✅ Commission is enabled for the event (`commission_enabled = 1`)
4. ✅ Book has distribution_path with Level 1 value

### Commission Types Supported:

The system supports **3 commission types** (can earn multiple simultaneously):

#### 1. Extra Books Commission
- **Trigger:** Book has `is_extra_book = 1` flag
- **Percentage:** From `extra_books_commission_percent` in commission_settings
- **Purpose:** Reward for taking additional books beyond initial allocation

#### 2. Early Payment Commission
- **Trigger:** Payment date <= `early_payment_date` from commission_settings
- **Percentage:** From `early_commission_percent`
- **Purpose:** Incentive for early payments

#### 3. Standard Payment Commission
- **Trigger:** Payment date <= `standard_payment_date` (after early date)
- **Percentage:** From `standard_commission_percent`
- **Purpose:** Regular commission for on-time payments

### Multiple Commissions:

A single payment can earn **MULTIPLE commission types**:

**Example:**
- Book is marked as extra book → Earns extra_books commission
- Payment date is before early deadline → ALSO earns early commission
- **Result:** 2 commission records for same distribution_id

---

## Code Implementation

### Step 1: Check if Book is Fully Paid

```php
$fullPaymentCheckQuery = "SELECT
                            lb.event_id,
                            lb.book_id,
                            le.price_per_ticket,
                            le.tickets_per_book,
                            bd.distribution_path,
                            bd.is_extra_book,
                            bd.distributed_at,
                            COALESCE(SUM(pc.amount_paid), 0) as total_paid
                          FROM lottery_books lb
                          JOIN lottery_events le ON lb.event_id = le.event_id
                          JOIN book_distribution bd ON lb.book_id = bd.book_id
                          LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
                          WHERE bd.distribution_id = :dist_id
                          GROUP BY lb.book_id";

$expectedAmount = $bookFullData['price_per_ticket'] * $bookFullData['tickets_per_book'];
$isFullyPaid = ($bookFullData['total_paid'] >= $expectedAmount);
```

### Step 2: Get Commission Settings

```php
if ($isFullyPaid) {
    $commissionQuery = "SELECT * FROM commission_settings
                        WHERE event_id = :event_id AND commission_enabled = 1";
    $commStmt = $db->prepare($commissionQuery);
    $commStmt->bindParam(':event_id', $bookFullData['event_id']);
    $commStmt->execute();
    $commSettings = $commStmt->fetch();
}
```

### Step 3: Determine Eligible Commissions

```php
$eligibleCommissions = [];

// Extra books commission
if ($bookFullData['is_extra_book'] == 1 &&
    $commSettings['extra_books_commission_enabled'] == 1) {
    $eligibleCommissions[] = [
        'type' => 'extra_books',
        'percent' => $commSettings['extra_books_commission_percent']
    ];
}

// Date-based commission (early OR standard, not both)
if ($commSettings['early_commission_enabled'] == 1 &&
    !empty($commSettings['early_payment_date']) &&
    $paymentDate <= $commSettings['early_payment_date']) {
    $eligibleCommissions[] = [
        'type' => 'early',
        'percent' => $commSettings['early_commission_percent']
    ];
}
elseif ($commSettings['standard_commission_enabled'] == 1 &&
        !empty($commSettings['standard_payment_date']) &&
        $paymentDate <= $commSettings['standard_payment_date']) {
    $eligibleCommissions[] = [
        'type' => 'standard',
        'percent' => $commSettings['standard_commission_percent']
    ];
}
```

### Step 4: Extract Level 1 Value

```php
$level1Value = '';
if (!empty($bookFullData['distribution_path'])) {
    $pathParts = explode(' > ', $bookFullData['distribution_path']);
    $level1Value = $pathParts[0] ?? '';
}
```

**Example:**
- `distribution_path = "Diocese A > Parish B > Member Name"`
- `level1Value = "Diocese A"`

### Step 5: Insert Commission Records

```php
foreach ($eligibleCommissions as $commission) {
    // Check if commission already exists (prevent duplicates)
    $checkCommQuery = "SELECT COUNT(*) as count FROM commission_earned
                      WHERE distribution_id = :dist_id
                      AND commission_type = :comm_type";

    if ($commExists['count'] == 0) {
        $commissionAmount = ($expectedAmount * $commission['percent']) / 100;

        $insertCommQuery = "INSERT INTO commission_earned
                           (event_id, distribution_id, level_1_value, commission_type,
                            commission_percent, payment_amount, commission_amount,
                            payment_date, book_id)
                           VALUES (:event_id, :dist_id, :level_1, :comm_type,
                                   :comm_percent, :payment_amt, :comm_amt,
                                   :payment_date, :book_id)";

        // Log success message
        $updates[] = "💰 Row $row (Book $bookNumber): Commission earned -
                      {$commission['type']} ({$commission['percent']}%) = ₹$commissionAmount";
    }
}
```

---

## User Feedback

The upload results now show commission calculation messages:

**Example Output:**
```
✅ Row 5 (Book 2): Added new payment of ₹2000 on 2025-12-14
💰 Row 5 (Book 2): Commission earned - early (10%) = ₹200

✅ Row 6 (Book 92): Added new payment of ₹2000 on 2025-12-14
💰 Row 6 (Book 92): Commission earned - extra_books (5%) = ₹100
💰 Row 6 (Book 92): Commission earned - standard (8%) = ₹160
```

---

## Database Schema

### commission_settings Table

```sql
CREATE TABLE commission_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    commission_enabled TINYINT(1) DEFAULT 0,

    -- Early commission
    early_commission_enabled TINYINT(1) DEFAULT 0,
    early_commission_percent DECIMAL(5,2) DEFAULT 0,
    early_payment_date DATE NULL,

    -- Standard commission
    standard_commission_enabled TINYINT(1) DEFAULT 0,
    standard_commission_percent DECIMAL(5,2) DEFAULT 0,
    standard_payment_date DATE NULL,

    -- Extra books commission
    extra_books_commission_enabled TINYINT(1) DEFAULT 0,
    extra_books_commission_percent DECIMAL(5,2) DEFAULT 0,

    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id)
);
```

### commission_earned Table

```sql
CREATE TABLE commission_earned (
    commission_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    distribution_id INT NOT NULL,
    book_id INT NOT NULL,
    level_1_value VARCHAR(255) NOT NULL,
    commission_type ENUM('early', 'standard', 'extra_books') NOT NULL,
    commission_percent DECIMAL(5,2) NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id),
    FOREIGN KEY (distribution_id) REFERENCES book_distribution(distribution_id),
    FOREIGN KEY (book_id) REFERENCES lottery_books(book_id)
);
```

---

## Testing Scenarios

### Scenario 1: New Full Payment (Early)

**Setup:**
- Event has early commission enabled: 10% before 2025-12-20
- Book price: ₹2000
- Upload: Payment of ₹2000 on 2025-12-14

**Expected:**
- ✅ Payment inserted
- ✅ Book marked as fully paid
- ✅ Commission earned: early (10%) = ₹200
- ✅ Record in commission_earned table

**Result:** ✅ PASS

---

### Scenario 2: Multiple Installments

**Setup:**
- Event has standard commission: 8% before 2025-12-31
- Book price: ₹2000
- First upload: ₹1000 on 2025-12-14
- Second upload: ₹1000 on 2025-12-21

**Expected:**
- ✅ First upload: Payment inserted, NO commission (not fully paid yet)
- ✅ Second upload: Payment inserted, Commission earned: standard (8%) = ₹160

**Result:** ✅ PASS

---

### Scenario 3: Extra Book + Early Commission

**Setup:**
- Book marked as `is_extra_book = 1`
- Early commission: 10% before 2025-12-20
- Extra books commission: 5%
- Upload: ₹2000 on 2025-12-15

**Expected:**
- ✅ Payment inserted
- ✅ Commission earned: extra_books (5%) = ₹100
- ✅ Commission earned: early (10%) = ₹200
- ✅ Total 2 commission records created

**Result:** ✅ PASS

---

### Scenario 4: Payment Already Fully Paid

**Setup:**
- Book already has ₹2000 paid (commission already earned)
- Upload: Update payment date (no amount change)

**Expected:**
- ✅ Payment updated
- ✅ NO new commission (duplicate check prevents it)

**Result:** ✅ PASS

---

### Scenario 5: Commission Not Enabled

**Setup:**
- `commission_enabled = 0` in commission_settings
- Upload: ₹2000 full payment

**Expected:**
- ✅ Payment inserted
- ✅ NO commission calculated

**Result:** ✅ PASS

---

## Duplicate Prevention

The system prevents duplicate commissions using this check:

```php
$checkCommQuery = "SELECT COUNT(*) as count FROM commission_earned
                  WHERE distribution_id = :dist_id
                  AND commission_type = :comm_type";

if ($exists['count'] == 0) {
    // Insert commission
}
```

**Why This Works:**
- Each distribution can have MULTIPLE commission records (different types)
- But can only have ONE record per commission type
- Prevents duplicate early/standard/extra_books commission for same book

---

## Files Modified

### [lottery-reports-excel-upload.php](public/group-admin/lottery-reports-excel-upload.php)

**Line 262-374:** Added commission calculation for main Data sheet processing

**Line 534-646:** Added commission calculation for Multiple Payments sheet processing

**Total Lines Added:** ~224 lines

---

## Performance Considerations

### Query Optimization

Each payment that makes a book fully paid triggers:
1. 1 SELECT (check if fully paid) - includes JOINs
2. 1 SELECT (get commission settings)
3. 1-3 SELECTs (check existing commissions per type)
4. 1-3 INSERTs (insert commission records per type)

**Impact:** For 185 rows with ~50 fully paid books = ~400-500 queries

**Optimization:** Queries use indexed columns (distribution_id, event_id, commission_type)

### Database Indexes Recommended:

```sql
-- commission_earned table
CREATE INDEX idx_dist_type ON commission_earned(distribution_id, commission_type);
CREATE INDEX idx_event ON commission_earned(event_id);

-- commission_settings table
CREATE INDEX idx_event_enabled ON commission_settings(event_id, commission_enabled);
```

---

## Comparison: Manual vs Excel Upload

| Feature | Manual Payment | Excel Upload (Before) | Excel Upload (After) |
|---------|---------------|----------------------|---------------------|
| **Payment Recording** | ✅ Yes | ✅ Yes | ✅ Yes |
| **Commission Calculation** | ✅ Yes | ❌ No | ✅ Yes |
| **Early Commission** | ✅ Yes | ❌ No | ✅ Yes |
| **Standard Commission** | ✅ Yes | ❌ No | ✅ Yes |
| **Extra Books Commission** | ✅ Yes | ❌ No | ✅ Yes |
| **Multiple Commission Types** | ✅ Yes | ❌ No | ✅ Yes |
| **Duplicate Prevention** | ✅ Yes | ❌ No | ✅ Yes |
| **User Feedback** | ✅ Success message | ⚠️ Basic | ✅ Detailed with commission |

---

## Next Steps

### For Development:
✅ Code updated and tested locally

### For Production Deployment:

1. **Backup Database:**
   ```sql
   -- Backup commission tables before deployment
   CREATE TABLE commission_earned_backup AS SELECT * FROM commission_earned;
   CREATE TABLE commission_settings_backup AS SELECT * FROM commission_settings;
   ```

2. **Upload Updated File:**
   - Upload `lottery-reports-excel-upload.php` to production server
   - Path: `/public_html/public/group-admin/lottery-reports-excel-upload.php`

3. **Test with Sample Data:**
   - Download template with existing data
   - Upload file with 1-2 fully paid books
   - Verify commission records appear in database
   - Check upload results show commission messages

4. **Verification Query:**
   ```sql
   -- Check recently earned commissions
   SELECT
       ce.commission_id,
       lb.book_number,
       ce.level_1_value,
       ce.commission_type,
       ce.commission_percent,
       ce.commission_amount,
       ce.payment_date,
       ce.created_at
   FROM commission_earned ce
   JOIN lottery_books lb ON ce.book_id = lb.book_id
   ORDER BY ce.created_at DESC
   LIMIT 20;
   ```

---

## Benefits Summary

### Before Fix:
- ❌ Excel uploads skipped commission calculation entirely
- ❌ Manual data entry required for commission tracking
- ❌ Inconsistent commission records
- ❌ No bulk commission processing

### After Fix:
- ✅ Excel uploads calculate commission automatically
- ✅ Consistent with manual payment commission logic
- ✅ Supports all commission types (early, standard, extra_books)
- ✅ Prevents duplicate commission records
- ✅ Detailed user feedback with commission amounts
- ✅ Bulk commission processing for hundreds of payments
- ✅ Full audit trail in commission_earned table

---

## Support

**For Commission Issues:**

1. Check commission_settings table for event
2. Verify commission_enabled = 1
3. Check payment_date vs early_payment_date / standard_payment_date
4. Verify book has distribution_path with Level 1 value
5. Check upload results for commission messages
6. Query commission_earned table for records

**Common Issues:**

**Q: Commission not calculated for my payment?**
- Check if book is fully paid (total_paid >= expected_amount)
- Check if commission is enabled for the event
- Check if payment date is within commission deadline
- Check upload results for commission messages

**Q: Commission calculated twice?**
- This is normal if book qualifies for multiple commission types
- Example: Extra book + Early commission = 2 records

**Q: Commission not showing in reports?**
- Commission records are in commission_earned table
- Check Commission Reports section on reports page
- Verify records exist in database

---

**Status:** ✅ FIXED and ready for production deployment

**Last Updated:** 2026-01-03
