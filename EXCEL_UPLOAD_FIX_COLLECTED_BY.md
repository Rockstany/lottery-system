# Excel Upload Fix - Missing collected_by Field

**Date:** 2025-12-25
**Issue:** Foreign key constraint violation on payment_collections.collected_by
**Status:** ✅ FIXED

---

## Error Message

```
❌ Error processing Excel file: SQLSTATE[23000]: Integrity constraint violation: 1452
Cannot add or update a child row: a foreign key constraint fails
(`u717011923_gettoknow_db`.`payment_collections`,
CONSTRAINT `payment_collections_ibfk_2` FOREIGN KEY (`collected_by`)
REFERENCES `users` (`user_id`))
```

---

## Root Cause

The `payment_collections` table has a **required foreign key** constraint:

```sql
CONSTRAINT `payment_collections_ibfk_2`
FOREIGN KEY (`collected_by`) REFERENCES `users` (`user_id`)
```

This means:
- Every payment record **must** have a `collected_by` value
- The `collected_by` value **must** reference a valid user_id from the users table
- The field cannot be NULL

**The Excel upload INSERT statement was missing this required field!**

---

## The Fix

### Before (Broken):

```php
// Insert new payment
$insertPaymentQuery = "INSERT INTO payment_collections
                      (distribution_id, amount_paid, payment_date, payment_method)
                      VALUES (:dist_id, :amount, :payment_date, :method)";
$insertStmt = $db->prepare($insertPaymentQuery);
$insertStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
$insertStmt->bindValue(':amount', $paymentAmount, PDO::PARAM_STR);
$insertStmt->bindValue(':payment_date', $paymentDate, PDO::PARAM_STR);
$insertStmt->bindValue(':method', $paymentMethod, PDO::PARAM_STR);
// ❌ Missing: collected_by
$insertStmt->execute();
```

### After (Fixed):

```php
// Insert new payment
$insertPaymentQuery = "INSERT INTO payment_collections
                      (distribution_id, amount_paid, payment_date, payment_method, collected_by)
                      VALUES (:dist_id, :amount, :payment_date, :method, :collected_by)";
$insertStmt = $db->prepare($insertPaymentQuery);
$insertStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
$insertStmt->bindValue(':amount', $paymentAmount, PDO::PARAM_STR);
$insertStmt->bindValue(':payment_date', $paymentDate, PDO::PARAM_STR);
$insertStmt->bindValue(':method', $paymentMethod, PDO::PARAM_STR);
$insertStmt->bindValue(':collected_by', $_SESSION['user_id'], PDO::PARAM_INT); // ✅ ADDED
$insertStmt->execute();
```

---

## What Changed

**File:** [lottery-reports-excel-upload.php:247-260](public/group-admin/lottery-reports-excel-upload.php#L247-L260)

**Changes:**
1. Added `collected_by` to the INSERT column list
2. Added `:collected_by` to the VALUES list
3. Bound `$_SESSION['user_id']` to the `:collected_by` parameter

**Logic:**
- Uses the currently logged-in user's ID (`$_SESSION['user_id']`)
- This user is the one who uploaded the Excel file
- Makes sense: the uploader is recorded as the payment collector

---

## Why UPDATE Doesn't Need This

The UPDATE statement only changes `amount_paid` and `payment_method`:

```php
$updatePaymentQuery = "UPDATE payment_collections
                      SET amount_paid = :amount,
                          payment_method = :method
                      WHERE payment_id = :payment_id";
```

**Why no collected_by?**
- The payment already exists (it's an UPDATE, not INSERT)
- The original `collected_by` value is preserved
- We're just correcting the amount/method, not changing who collected it

This is **correct behavior** - when re-uploading to fix payment amounts, the original collector stays the same.

---

## Testing

### Test Case 1: New Payment Insert

**Scenario:** Upload Excel with payment for a book that has no payments yet

**Expected:**
- INSERT new payment record
- `collected_by` = current user's ID
- No foreign key error
- Success message shown

**Result:** ✅ PASS

### Test Case 2: Existing Payment Update

**Scenario:** Upload Excel with corrected payment amount for existing payment

**Expected:**
- UPDATE existing payment record
- Amount/method updated
- `collected_by` unchanged (stays original collector)
- No foreign key error
- Success message: "Updated payment from ₹X to ₹Y"

**Result:** ✅ PASS

### Test Case 3: Multiple Payments Same Date

**Scenario:** Upload Excel multiple times with same date

**Expected:**
- First upload: INSERT
- Second upload: UPDATE (same date, same distribution)
- No duplicates
- `collected_by` preserved from first insert

**Result:** ✅ PASS

---

## Related Database Schema

### payment_collections Table Structure:

```sql
CREATE TABLE payment_collections (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    distribution_id INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash','upi','bank_transfer','cheque') DEFAULT 'cash',
    collected_by INT NOT NULL,  -- ⚠️ REQUIRED FIELD
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (distribution_id) REFERENCES book_distribution(distribution_id),
    FOREIGN KEY (collected_by) REFERENCES users(user_id)  -- ⚠️ MUST BE VALID USER
);
```

**Key Points:**
- `collected_by` is **NOT NULL** (required)
- Must reference a valid `user_id` from users table
- Ensures data integrity: every payment has a known collector

---

## Impact

### Before Fix:
- ❌ Excel upload always failed with foreign key error
- ❌ No payments could be added via Excel
- ❌ Manual payment entry was the only option

### After Fix:
- ✅ Excel upload works correctly
- ✅ Payments inserted with current user as collector
- ✅ Bulk payment updates now possible
- ✅ Audit trail: know who uploaded each payment batch

---

## Best Practices Applied

1. **Audit Trail:** Recording who collected/uploaded payments
2. **Data Integrity:** Foreign key ensures collector is a real user
3. **Session Security:** Using authenticated session user_id
4. **Preserve History:** UPDATE doesn't change original collector
5. **Error Handling:** Clear error messages for troubleshooting

---

## Additional Notes

### Session User ID

The fix uses `$_SESSION['user_id']` which is:
- Set during login (in login.php)
- Validated by AuthMiddleware
- Guaranteed to exist for authenticated users
- Safe to use in this context

### Manual Payment Collection

When payments are collected manually (not via Excel upload), the `collected_by` is set in:
- [lottery-payment-collect.php](public/group-admin/lottery-payment-collect.php)
- Uses the same `$_SESSION['user_id']`
- Consistent behavior across both methods

---

## Deployment

### For Development:
✅ Already applied - file updated

### For Production:
1. Upload updated `lottery-reports-excel-upload.php`
2. Test with sample Excel file
3. Verify payments insert correctly
4. Check `collected_by` field in database

### Verification Query:

```sql
-- Check recent payments from Excel upload
SELECT
    pc.payment_id,
    lb.book_number,
    pc.amount_paid,
    pc.payment_date,
    pc.payment_method,
    u.full_name AS collected_by_user,
    pc.created_at
FROM payment_collections pc
JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
JOIN lottery_books lb ON bd.book_id = lb.book_id
JOIN users u ON pc.collected_by = u.user_id
ORDER BY pc.created_at DESC
LIMIT 20;
```

---

## Status

✅ **FIXED and TESTED**

The Excel upload now works correctly:
- Inserts payments with `collected_by` field
- No foreign key constraint violations
- Full audit trail maintained
- Success messages display properly

**Ready for production deployment!**
