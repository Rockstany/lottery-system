# Commission Troubleshooting Guide

## Issue: Commission Not Being Calculated

Based on the diagnostics output showing **"NO COMMISSION SETTINGS FOUND"**, here's how to fix the issue.

---

## Root Causes Identified

### 1. **Commission Settings Not in Database** ❌ CRITICAL
The `check_commission.php` script returned:
```
❌ NO COMMISSION SETTINGS FOUND!
This is why commission is not being calculated.
```

**Possible Reasons:**
- Settings were not saved properly when you clicked "Save Early Commission Settings"
- The `event_id = 1` in `check_commission.php` doesn't match your actual event
- The `commission_settings` table doesn't exist or is empty

### 2. **Commission Only on FULL Payment** ⚠️ IMPORTANT
Commission is calculated ONLY when the book payment is **fully paid**, not on partial payments.

Code Location: `public/group-admin/lottery-payment-collect.php:125-130`
```php
$isFullyPaid = ($newTotalPaid >= $expectedAmount);

// Calculate and save commission if enabled AND payment is full
if ($isFullyPaid) {
    // Commission calculation happens here
}
```

### 3. **Date Comparison Issue** ⚠️ CHECK
Early commission requires: `payment_date <= early_payment_date`

From your settings:
- Early payment deadline: `2025-12-14` (14/12/2025)
- Payment dates in Excel: `14-12-2025`

These should match, but verify the exact format in the database.

---

## Step-by-Step Fix

### Step 1: Verify Event ID

1. Find your actual event ID:
   ```sql
   SELECT event_id, event_name FROM lottery_events ORDER BY created_at DESC LIMIT 5;
   ```

2. Update `check_commission.php` line 8:
   ```php
   $eventId = 1; // CHANGE THIS TO YOUR ACTUAL EVENT ID
   ```

### Step 2: Check Commission Settings in Database

Run this query to see if settings exist:
```sql
SELECT * FROM commission_settings WHERE event_id = YOUR_EVENT_ID;
```

**If NO ROWS returned:**
- Your settings were NOT saved to the database
- Go to the commission setup page and save again
- Check for any JavaScript errors in browser console

**If ROWS exist, check these columns:**
```sql
SELECT
    commission_enabled,
    early_commission_enabled,
    early_payment_date,
    early_commission_percent,
    standard_commission_enabled,
    standard_payment_date,
    standard_commission_percent
FROM commission_settings
WHERE event_id = YOUR_EVENT_ID;
```

**Required values for Early Commission:**
- `commission_enabled` = 1 (master switch)
- `early_commission_enabled` = 1 (early commission toggle)
- `early_payment_date` = '2025-12-14' (in Y-m-d format)
- `early_commission_percent` = 10.00

### Step 3: Manually Insert Settings (If Missing)

If the settings don't exist, manually insert them:

```sql
INSERT INTO commission_settings (
    event_id,
    commission_enabled,
    early_commission_enabled,
    early_payment_date,
    early_commission_percent,
    standard_commission_enabled,
    standard_payment_date,
    standard_commission_percent,
    extra_books_commission_enabled,
    extra_books_date,
    extra_books_commission_percent
) VALUES (
    YOUR_EVENT_ID,        -- Replace with your event ID
    1,                    -- Enable commission system
    1,                    -- Enable early commission
    '2025-12-14',        -- Early payment deadline
    10.00,               -- 10% for early payment
    0,                    -- Disable standard commission (or set to 1 if needed)
    NULL,                -- Standard date (set if using)
    5.00,                -- 5% for standard
    0,                    -- Disable extra books (or set to 1 if needed)
    NULL,                -- Extra books date
    15.00                -- 15% for extra books
);
```

### Step 4: Verify Payment Status

Check which payments are marked as "Fully Paid":

```sql
SELECT
    lb.book_number,
    bd.distribution_path,
    le.tickets_per_book * le.price_per_ticket as expected_amount,
    COALESCE(SUM(pc.amount_paid), 0) as total_paid,
    CASE
        WHEN COALESCE(SUM(pc.amount_paid), 0) >= (le.tickets_per_book * le.price_per_ticket)
        THEN 'FULLY PAID'
        ELSE 'PARTIAL/UNPAID'
    END as status
FROM lottery_books lb
JOIN book_distribution bd ON lb.book_id = bd.book_id
JOIN lottery_events le ON lb.event_id = le.event_id
LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
WHERE le.event_id = YOUR_EVENT_ID
GROUP BY lb.book_id
ORDER BY lb.book_number;
```

**Key Point:** Only "FULLY PAID" books will have commission calculated.

### Step 5: Check Existing Commission Records

```sql
SELECT
    ce.commission_id,
    ce.level_1_value,
    ce.commission_type,
    ce.commission_percent,
    ce.payment_amount,
    ce.commission_amount,
    ce.payment_date,
    lb.book_number
FROM commission_earned ce
LEFT JOIN lottery_books lb ON ce.book_id = lb.book_id
WHERE ce.event_id = YOUR_EVENT_ID
ORDER BY ce.created_at DESC;
```

If this returns **0 rows** and you have fully paid books, then commission calculation is failing.

### Step 6: Recalculate All Commissions

If settings are correct but commissions are missing, use the recalculation tool:

**Option A: Via Web Interface**
1. Go to Commission Setup page
2. Click "🔄 Recalculate Commissions"
3. This will delete and regenerate all commission records

**Option B: Via Direct Link**
Navigate to: `public/group-admin/lottery-commission-sync.php?id=YOUR_EVENT_ID`

---

## Common Issues from Your Excel Report

Looking at your Excel screenshot:

### Books with "Fully Paid" Status:
- **B.H.L Convent** (Book 2): ₹2,000 paid on 14-12-2025 ✅
- **Bl. Mariam Thresiamma** (Books 92-98): All ₹2,000 paid on 14-12-2025 ✅
- **DSYM Unit** (Book 165): ₹2,000 paid on 14-12-2025 ✅

These **should have commission** calculated if:
1. Commission settings exist in database
2. Payment date (14-12-2025) ≤ Early deadline (14-12-2025) ✅
3. Books are fully paid ✅

### Books with "Unpaid" Status:
- **DSYM Unit** (Books 166-177): ₹0 payment ❌

These will **NOT have commission** until they are paid.

---

## Date Issue Analysis

**Your Settings (from screenshot):**
- Deadline Date: `14 / 12 / 2025`
- Commission: `10.00%`

**Expected Database Format:**
- `early_payment_date` = `'2025-12-14'` (Y-m-d format)

**Payment Dates (from Excel):**
- `14-12-2025`

**In PHP Code:**
The comparison is:
```php
$paymentDate <= $commSettings['early_payment_date']
```

If `paymentDate = '2025-12-14'` and `early_payment_date = '2025-12-14'`, this evaluates to `TRUE` ✅

So dates **should work** if stored correctly.

---

## Action Plan

1. **Find your Event ID** (check URL when viewing the event)
2. **Update `check_commission.php`** with correct event_id
3. **Run the diagnostic script again** to see actual data
4. **Check database** for commission_settings
5. **If missing, manually insert** settings using SQL above
6. **Verify fully paid books** using the payment status query
7. **Run recalculation tool** if needed
8. **Re-run diagnostics** to confirm commission records exist

---

## Expected Result

After fixing, the diagnostic should show:

```
=== COMMISSION DIAGNOSTICS ===

✅ Commission Settings Found:
   - commission_enabled: 1
   - early_commission_enabled: 1
   - early_commission_percent: 10%
   - early_payment_date: 2025-12-14

📋 Sample Book Distributions:
   Book 2: Path = 'B.H.L Convent', Extra = 0
      Level 1 value: 'B.H.L Convent'

💰 Sample Payments:
   Payment ID 1: ₹2,000 on 2025-12-14
      Distribution path: 'B.H.L Convent'

💵 Commission Records Found: 7
   - Type: early, Amount: ₹200.00, Level 1: 'B.H.L Convent'
   - Type: early, Amount: ₹200.00, Level 1: 'Bl. Mariam Thresiamma'
   ...
```

---

## Need More Help?

1. Run `check_commission.php` with the correct event_id
2. Share the output
3. Share the result of: `SELECT * FROM commission_settings WHERE event_id = YOUR_EVENT_ID;`
4. Then we can pinpoint the exact issue

The issue is almost certainly **missing commission settings** in the database, not a date issue.
