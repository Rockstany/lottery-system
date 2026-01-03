# Commission Partial Payment Update

**Date:** 2026-01-03
**Status:** ✅ COMPLETE

---

## Summary of Changes

Two major updates to the commission system:

1. **Commission on Partial Payments** - Calculate commission on actual payment amount (not just full payments)
2. **Commission Reset Tool** - Admin page to reset all commission records for an event

---

## Change 1: Commission on Partial Payments

### Previous Behavior ❌

Commission was **ONLY** calculated when a book was **fully paid**:

```php
// OLD LOGIC
$isFullyPaid = ($totalPaid >= $expectedAmount);
if ($isFullyPaid) {
    // Calculate commission on EXPECTED amount
    $commissionAmount = ($expectedAmount * $percent) / 100;
}
```

**Example:**
- Book price: ₹2000
- Payment 1: ₹500 → **NO commission**
- Payment 2: ₹500 → **NO commission**
- Payment 3: ₹500 → **NO commission**
- Payment 4: ₹500 → **Commission: ₹2000 × 5% = ₹100** (only on last payment)

### New Behavior ✅

Commission is calculated on **EVERY payment** based on the **ACTUAL amount paid**:

```php
// NEW LOGIC
if ($paymentAmount > 0) {
    // Calculate commission on ACTUAL payment amount
    $commissionAmount = ($paymentAmount * $percent) / 100;
}
```

**Example:**
- Book price: ₹2000
- Payment 1: ₹500 → **Commission: ₹500 × 5% = ₹25**
- Payment 2: ₹500 → **Commission: ₹500 × 5% = ₹25**
- Payment 3: ₹500 → **Commission: ₹500 × 5% = ₹25**
- Payment 4: ₹500 → **Commission: ₹500 × 5% = ₹25**
- **Total commission: ₹100** (same total, but distributed across payments)

---

## Benefits of Partial Payment Commission

### 1. Fair Commission Distribution

✅ **Members earn commission immediately** when they collect any payment
✅ **No waiting** until book is fully paid
✅ **Instant reward** for partial collections
✅ **Encourages multiple payment installments**

### 2. Better Cash Flow

✅ Members see commission rewards faster
✅ Motivates continued payment collection
✅ More accurate real-time commission tracking

### 3. Flexibility

✅ Works with installment payments
✅ Handles any payment amount (₹100, ₹500, ₹1000, etc.)
✅ Commission grows with each payment

---

## How It Works Now

### Example Scenario 1: Single Full Payment

**Setup:**
- Book price: ₹2000
- Commission: 5%
- Payment: ₹2000 on 2025-12-14

**Result:**
```
✅ Payment: ₹2000
💰 Commission: ₹2000 × 5% = ₹100
```

---

### Example Scenario 2: Two Installments

**Setup:**
- Book price: ₹2000
- Commission: 5%

**Upload 1:** ₹1000 on 2025-12-14
```
✅ Payment: ₹1000
💰 Commission: ₹1000 × 5% = ₹50
```

**Upload 2:** ₹1000 on 2025-12-21
```
✅ Payment: ₹1000
💰 Commission: ₹1000 × 5% = ₹50
```

**Total Commission:** ₹50 + ₹50 = ₹100

---

### Example Scenario 3: Multiple Small Payments

**Setup:**
- Book price: ₹2000
- Commission: 5%

**Payments:**
- Dec 10: ₹200 → Commission: ₹10
- Dec 15: ₹300 → Commission: ₹15
- Dec 20: ₹500 → Commission: ₹25
- Dec 25: ₹1000 → Commission: ₹50

**Total Commission:** ₹100

---

## Commission Tracking by Date

Commission records now track **payment date** to prevent duplicates:

```php
// Check if commission exists for this distribution, type, AND DATE
$checkCommQuery = "SELECT commission_id, commission_amount FROM commission_earned
                  WHERE distribution_id = :dist_id
                  AND commission_type = :comm_type
                  AND DATE(payment_date) = :payment_date";
```

**This means:**
- Each payment date gets its own commission record
- Same book can have multiple commission entries (different dates)
- No duplicates for same date

**Example:**
```
Book #2:
- Commission ID 1: ₹50 on 2025-12-14 (early)
- Commission ID 2: ₹50 on 2025-12-21 (early)
Total: ₹100 across 2 records
```

---

## Commission Update Logic

When re-uploading data with corrected amounts, commission automatically updates:

```php
if ($existingComm) {
    // UPDATE existing commission if amount changed
    if ($existingComm['commission_amount'] != $commissionAmount) {
        UPDATE commission_earned
        SET commission_amount = :new_amount,
            payment_amount = :new_payment_amount
        WHERE commission_id = :id
    }
} else {
    // INSERT new commission
    INSERT INTO commission_earned ...
}
```

**Example:**

**First Upload:** ₹500 payment
- Commission created: ₹25

**Second Upload:** Corrected to ₹600 (same date)
- Commission updated: ₹30
- No duplicate created

---

## Change 2: Commission Reset Tool

### New Admin Page

**File:** [lottery-commission-reset.php](public/group-admin/lottery-commission-reset.php)

**Purpose:** Allow admins to delete ALL commission records for an event and start fresh

### Features

✅ **Statistics Dashboard**
- Shows total commission records
- Displays total commission amount
- Breaks down by commission type (early, standard, extra_books)

✅ **Safe Delete Mechanism**
- Requires typing "RESET" to confirm
- Shows warning with count of records to be deleted
- Double confirmation via JavaScript and PHP
- Cannot be undone

✅ **Transaction Safety**
- Uses database transactions
- Rolls back on error
- Logs deletion count

### When to Use Reset Tool

**Use this tool when:**
1. Re-uploading Excel data with major corrections
2. Commission settings changed (want to recalculate all)
3. Testing commission calculations
4. Fixing incorrect commission data

**Example Flow:**
1. Realize commission was calculated wrong
2. Go to Commission Reset page
3. Review current statistics
4. Type "RESET" and confirm
5. All commission records deleted
6. Re-upload Excel file
7. Commission recalculated with new logic

---

## Files Modified

### 1. [lottery-reports-excel-upload.php](public/group-admin/lottery-reports-excel-upload.php)

**Lines 262-389:** Main Data Sheet commission calculation
- Changed from "fully paid" check to "any payment" check
- Calculate commission on actual payment amount
- Check by distribution + type + date (prevents duplicates)
- UPDATE existing commission if amount changed

**Lines 549-676:** Multiple Payments Sheet commission calculation
- Same logic as main sheet
- Handles installment payments
- Commission per payment date

**Key Changes:**
```php
// BEFORE
if ($isFullyPaid) {
    $commissionAmount = ($expectedAmount * $percent) / 100;
}

// AFTER
if ($paymentAmount > 0) {
    $commissionAmount = ($paymentAmount * $percent) / 100;
}
```

### 2. [lottery-commission-reset.php](public/group-admin/lottery-commission-reset.php) ✨ NEW

**Purpose:** Admin tool to reset all commission records

**Features:**
- Statistics dashboard
- Commission breakdown by type
- Safe delete with confirmation
- Transaction safety

---

## Database Impact

### commission_earned Table

**Before:**
- 1 record per fully-paid book
- Commission based on expected amount
- No duplicate prevention by date

**After:**
- Multiple records per book (one per payment date)
- Commission based on actual payment amount
- Duplicate prevention: distribution + type + date

**Example Data:**

**OLD:**
```sql
distribution_id | commission_type | payment_amount | commission_amount | payment_date
1              | early           | 2000           | 100              | 2025-12-14
```

**NEW:**
```sql
distribution_id | commission_type | payment_amount | commission_amount | payment_date
1              | early           | 500            | 25               | 2025-12-10
1              | early           | 500            | 25               | 2025-12-14
1              | early           | 500            | 25               | 2025-12-18
1              | early           | 500            | 25               | 2025-12-21
```

---

## Testing Scenarios

### Test 1: Partial Payment Commission

**Setup:**
- Book price: ₹2000, Commission: 5%

**Step 1:** Upload ₹500 payment
```
Expected:
✅ Payment inserted: ₹500
💰 Commission earned: early (5%) = ₹25
```

**Step 2:** Upload ₹500 payment (different date)
```
Expected:
✅ Payment inserted: ₹500
💰 Commission earned: early (5%) = ₹25
```

**Database Check:**
```sql
SELECT * FROM commission_earned WHERE distribution_id = X
```
Should show 2 records, ₹25 each

**Result:** ✅ PASS

---

### Test 2: Commission Update on Correction

**Setup:**
- Upload ₹500 payment on Dec 14

**Step 1:** First upload
```
Commission created: ₹25
```

**Step 2:** Re-upload with ₹600 (same date)
```
Expected:
💰 Commission updated: early (5%) = ₹30
```

**Database Check:**
Should have 1 record with ₹30 (not 2 records)

**Result:** ✅ PASS

---

### Test 3: Commission Reset

**Setup:**
- Event has 50 commission records

**Steps:**
1. Go to lottery-commission-reset.php?id=EVENT_ID
2. View statistics (50 records shown)
3. Type "RESET" in confirmation box
4. Click submit
5. Confirm in popup

**Expected:**
```
✅ Successfully reset! Deleted 50 commission records
```

**Database Check:**
```sql
SELECT COUNT(*) FROM commission_earned WHERE event_id = X
```
Should return 0

**Result:** ✅ PASS

---

## Migration Guide

### For Existing Data

If you have existing commission records calculated with the OLD logic:

**Option 1: Keep Existing Data**
- Existing commission records remain
- New uploads use new logic
- May have mixed calculation methods

**Option 2: Reset and Recalculate (Recommended)**
1. Go to [lottery-commission-reset.php](public/group-admin/lottery-commission-reset.php)
2. Review current commission totals
3. Reset all commission records
4. Download Excel template with current data
5. Re-upload Excel file
6. Commission recalculated with new logic

---

## Commission Calculation Comparison

### Scenario: ₹2000 book, paid in 4 installments of ₹500 each, 5% commission

**OLD System:**
```
Payment 1 (₹500): No commission
Payment 2 (₹500): No commission
Payment 3 (₹500): No commission
Payment 4 (₹500): Commission = ₹2000 × 5% = ₹100

Total: ₹100 (single record)
```

**NEW System:**
```
Payment 1 (₹500): Commission = ₹500 × 5% = ₹25
Payment 2 (₹500): Commission = ₹500 × 5% = ₹25
Payment 3 (₹500): Commission = ₹500 × 5% = ₹25
Payment 4 (₹500): Commission = ₹500 × 5% = ₹25

Total: ₹100 (4 records, ₹25 each)
```

**Result:** Same total commission, but distributed across payments

---

## Commission Reports Impact

### Before

Commission reports showed:
- 1 commission entry when book fully paid
- Large commission amounts
- Fewer records

### After

Commission reports now show:
- Multiple commission entries per book
- Smaller commission amounts per entry
- More records (one per payment)
- Commission accumulates over time

**Example Report:**

**OLD:**
```
Book #2 | Dec 21 | ₹100 | early
```

**NEW:**
```
Book #2 | Dec 10 | ₹25  | early
Book #2 | Dec 14 | ₹25  | early
Book #2 | Dec 18 | ₹25  | early
Book #2 | Dec 21 | ₹25  | early
Total:           | ₹100
```

---

## API/Integration Impact

If you have external systems querying commission data:

**⚠️ BREAKING CHANGE:**
- Commission records are now **per payment date**
- Need to **SUM** commission records to get total per book
- Cannot assume 1 record = 1 book

**Updated Query:**
```sql
-- Get total commission per book
SELECT
    distribution_id,
    book_id,
    level_1_value,
    commission_type,
    SUM(commission_amount) as total_commission,
    COUNT(*) as payment_count
FROM commission_earned
WHERE event_id = ?
GROUP BY distribution_id, commission_type
```

---

## Performance Considerations

### Database Writes

**Before:** 1 INSERT per fully-paid book
**After:** 1 INSERT per payment (more records)

**Impact:** More database rows, but negligible performance impact

### Query Performance

Queries need to GROUP BY to sum commission amounts:

```sql
-- Add indexes for better performance
CREATE INDEX idx_dist_type_date ON commission_earned(distribution_id, commission_type, payment_date);
```

---

## Deployment Checklist

### For Development:
- [x] Code updated in lottery-reports-excel-upload.php
- [x] Commission reset page created
- [x] Tested with sample data
- [x] Documentation created

### For Production:

1. **Backup Database:**
   ```sql
   CREATE TABLE commission_earned_backup_20260103 AS SELECT * FROM commission_earned;
   ```

2. **Upload Files:**
   - Upload `lottery-reports-excel-upload.php`
   - Upload `lottery-commission-reset.php`

3. **Add Database Index:**
   ```sql
   CREATE INDEX idx_dist_type_date ON commission_earned(distribution_id, commission_type, payment_date);
   ```

4. **Decide on Existing Data:**
   - Keep old commission records (mixed calculation)
   - OR reset and recalculate (recommended)

5. **Test:**
   - Upload sample Excel with partial payments
   - Verify commission calculated correctly
   - Check commission reports

---

## Support

### Common Questions

**Q: Will this change my existing commission totals?**
A: No, existing commission records are unchanged. The new logic only applies to new Excel uploads.

**Q: How do I recalculate all commissions with the new logic?**
A: Use the Commission Reset tool to delete all records, then re-upload your Excel data.

**Q: What if I pay ₹100 installments 20 times?**
A: You'll get 20 commission records of ₹5 each (if 5% commission), totaling ₹100.

**Q: Can I undo a commission reset?**
A: No, it's permanent. But you can re-upload Excel data to regenerate commission records.

**Q: Does this affect manual payment collection?**
A: Not yet. Manual payment collection still uses the OLD logic (full payment only). This will be updated separately if needed.

---

## Next Steps

### Potential Future Enhancements

1. **Update Manual Payment Collection**
   - Apply same partial payment logic to lottery-payment-collect.php
   - Currently only Excel uploads have new logic

2. **Commission Summary Dashboard**
   - Show total commission by date range
   - Filter by commission type
   - Export commission reports

3. **Commission Preview**
   - Show estimated commission before confirming payment
   - Real-time commission calculation

---

**Status:** ✅ COMPLETE and ready for production deployment

**Last Updated:** 2026-01-03
