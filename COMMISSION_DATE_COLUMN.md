# Commission Date Column Feature

**Date:** 2026-01-03
**Status:** ✅ COMPLETE

---

## Summary

Added a new **"Commission Date"** column to Excel templates that allows users to specify which date should be used for commission eligibility calculations, separate from the actual payment date.

---

## Why This Feature?

### Problem
Previously, commission eligibility was always determined by the **payment date**:
- Payment made on Dec 25 → Check if Dec 25 <= early_payment_date
- If payment date missed early deadline, no early commission even if user wanted to backdate it

### Solution
Now users can specify a separate **commission date** for each payment:
- **Payment Date**: When the money was actually collected (e.g., Dec 25)
- **Commission Date**: What date to use for commission calculation (e.g., Dec 10)

This allows:
- Backdating commission for payments collected late but agreed upon earlier
- Flexible commission policies for special cases
- Correcting commission dates without changing payment records

---

## How It Works

### Excel Template Structure

#### Main Data Sheet (Sheet 1)
Columns:
```
Sr No | Level 1 | Level 2 | ... | Member Name | Mobile | Book Number |
Payment Amount | Payment Date | Commission Date | Payment Status | Payment Method | Return Status
```

#### Multiple Payments Sheet (Sheet 4)
Columns:
```
Book Number | Payment Amount | Payment Date | Commission Date | Payment Method | Notes | Collected By
```

### Default Behavior
- If **Commission Date is empty or invalid**, it automatically uses the **Payment Date**
- Templates pre-fill commission date = payment date by default

### Commission Calculation Logic

**Before:**
```php
if ($paymentDate <= $commSettings['early_payment_date']) {
    // Qualify for early commission
}
```

**After:**
```php
if ($commissionDate <= $commSettings['early_payment_date']) {
    // Qualify for early commission (using commission date, not payment date)
}
```

---

## Use Cases

### Use Case 1: Backdated Commission

**Scenario:**
- Early payment deadline: Dec 15
- Payment collected: Dec 20 (late)
- But agreement was made on Dec 12 (before deadline)

**Solution:**
```
Payment Date: 20-12-2025
Commission Date: 12-12-2025
```

**Result:**
- Payment recorded as collected on Dec 20
- Commission calculated as "early" because commission date (Dec 12) is before deadline

---

### Use Case 2: Grace Period

**Scenario:**
- Standard payment deadline: Dec 31
- Payment collected: Jan 5 (after deadline)
- Admin wants to give grace period commission

**Solution:**
```
Payment Date: 05-01-2026
Commission Date: 30-12-2025
```

**Result:**
- Payment recorded as collected on Jan 5
- Commission calculated as "standard" because commission date (Dec 30) is before deadline

---

### Use Case 3: Correction

**Scenario:**
- Payment was entered with wrong date
- Commission already calculated incorrectly
- Need to fix without losing payment history

**Solution:**
1. Reset commission records
2. Re-upload with corrected commission dates
3. Commission recalculated based on new dates

---

## Files Modified

### 1. lottery-reports-excel-template.php

**Main Data Sheet (Line 265):**
```php
$headers = array_merge($headers, [
    'Member Name',
    'Mobile Number',
    'Book Number',
    'Payment Amount (₹)',
    'Payment Date',
    'Commission Date',  // NEW COLUMN
    'Payment Status',
    'Payment Method',
    'Book Returned Status'
]);
```

**Multiple Payments Sheet (Line 388):**
```php
$multiPayHeaders = [
    'Book Number',
    'Payment Amount (₹)',
    'Payment Date',
    'Commission Date',  // NEW COLUMN
    'Payment Method',
    'Notes',
    'Collected By'
];
```

**Sample Data (Lines 313, 342, 406-409):**
- Pre-filled with commission_date = payment_date by default

---

### 2. lottery-reports-excel-upload.php

#### Main Data Sheet Processing

**Column Mapping (Lines 90-101):**
```php
$memberNameCol = $levelCount + 1;
$mobileCol = $levelCount + 2;
$bookNumberCol = $levelCount + 3;
$paymentAmountCol = $levelCount + 4;
$paymentDateCol = $levelCount + 5;
$commissionDateCol = $levelCount + 6;  // NEW
$paymentStatusCol = $levelCount + 7;   // +1
$paymentMethodCol = $levelCount + 8;   // +1
$returnStatusCol = $levelCount + 9;    // +1
```

**Commission Date Parsing (Lines 195-229):**
```php
// Parse commission date (same logic as payment date)
$commissionDateRaw = $rowData[$commissionDateCol] ?? '';
$commissionDate = null;

if (!empty($commissionDateRaw)) {
    // Try Excel date serial number
    if (is_numeric($commissionDateRaw) && $commissionDateRaw > 25569) {
        try {
            $dateTime = Date::excelToDateTimeObject($commissionDateRaw);
            $commissionDate = $dateTime->format('Y-m-d');
        } catch (Exception $e) {
            // Try text parsing
        }
    }

    // Try text date (DD-MM-YYYY or DD/MM/YYYY)
    if (!$commissionDate) {
        $commissionDateRaw = trim($commissionDateRaw);
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $commissionDateRaw, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            $commissionDate = "$year-$month-$day";
        } else {
            $timestamp = strtotime($commissionDateRaw);
            if ($timestamp) {
                $commissionDate = date('Y-m-d', $timestamp);
            }
        }
    }
}

// If commission date is empty or invalid, use payment date
if (!$commissionDate && $paymentDate) {
    $commissionDate = $paymentDate;
}
```

**Commission Eligibility Check (Lines 357-373):**
```php
// Check date-based commission
// Uses commission date (not payment date) for commission eligibility
if ($commSettings['early_commission_enabled'] == 1 &&
    !empty($commSettings['early_payment_date']) &&
    $commissionDate <= $commSettings['early_payment_date']) {
    $eligibleCommissions[] = [
        'type' => 'early',
        'percent' => $commSettings['early_commission_percent']
    ];
}
elseif ($commSettings['standard_commission_enabled'] == 1 &&
        !empty($commSettings['standard_payment_date']) &&
        $commissionDate <= $commSettings['standard_payment_date']) {
    $eligibleCommissions[] = [
        'type' => 'standard',
        'percent' => $commSettings['standard_commission_percent']
    ];
}
```

#### Multiple Payments Sheet Processing

**Column Reading (Lines 489-495):**
```php
// Columns: Book Number (A/1), Payment Amount (B/2), Payment Date (C/3),
//          Commission Date (D/4), Payment Method (E/5), Notes (F/6)
$bookNumber = trim($multiPaymentSheet->getCellByColumnAndRow(1, $row)->getValue() ?? '');
$paymentAmount = trim($multiPaymentSheet->getCellByColumnAndRow(2, $row)->getValue() ?? '');
$paymentDateRaw = trim($multiPaymentSheet->getCellByColumnAndRow(3, $row)->getValue() ?? '');
$commissionDateRaw = trim($multiPaymentSheet->getCellByColumnAndRow(4, $row)->getValue() ?? '');  // NEW
$paymentMethod = strtolower(trim($multiPaymentSheet->getCellByColumnAndRow(5, $row)->getValue() ?? ''));
$notes = trim($multiPaymentSheet->getCellByColumnAndRow(6, $row)->getValue() ?? '');
```

**Commission Date Parsing (Lines 541-571):**
- Same parsing logic as main sheet
- Falls back to payment date if empty/invalid

**Commission Eligibility Check (Lines 683-698):**
- Uses `$commissionDate` instead of `$paymentDate`
- Same logic as main sheet

---

## Database Impact

### commission_earned Table

The `payment_date` field in the commission_earned table **still stores the actual payment date**, NOT the commission date.

**Why?**
- Commission date is only used to **determine eligibility**
- The actual payment date is important for payment history tracking
- Commission reports show when payment was actually made

**Example Record:**
```sql
commission_id: 1
distribution_id: 5
payment_amount: 500
payment_date: 2025-12-25  -- When payment was actually made
commission_type: early     -- Determined using commission_date (e.g., 2025-12-10)
commission_amount: 25
```

---

## Examples

### Example 1: Same Date for Both

**Excel Entry:**
```
Book Number: 2
Payment Amount: 2000
Payment Date: 14-12-2025
Commission Date: 14-12-2025
```

**Result:**
- Payment recorded: Dec 14
- Commission eligibility checked: Dec 14
- If early deadline is Dec 20 → Qualifies for early commission

---

### Example 2: Backdated Commission

**Excel Entry:**
```
Book Number: 5
Payment Amount: 1000
Payment Date: 22-12-2025
Commission Date: 12-12-2025
```

**Result:**
- Payment recorded: Dec 22 (actual collection date)
- Commission eligibility checked: Dec 12 (backdated)
- If early deadline is Dec 15 → Qualifies for early commission (using commission date)
- Payment history shows actual collection date (Dec 22)

---

### Example 3: Empty Commission Date

**Excel Entry:**
```
Book Number: 10
Payment Amount: 500
Payment Date: 18-12-2025
Commission Date: [empty]
```

**Result:**
- Payment recorded: Dec 18
- Commission date defaults to: Dec 18 (same as payment date)
- Commission eligibility checked: Dec 18

---

## Testing

### Test 1: Commission Date Before Deadline

**Setup:**
- Early payment deadline: 2025-12-15
- Early commission: 10%

**Upload:**
```
Payment Date: 22-12-2025
Commission Date: 10-12-2025
Payment Amount: 1000
```

**Expected:**
- ✅ Payment inserted with date Dec 22
- ✅ Commission calculated: early (10%) = ₹100
- ✅ Commission earned because commission_date (Dec 10) <= deadline (Dec 15)

---

### Test 2: Commission Date After Deadline

**Setup:**
- Early payment deadline: 2025-12-15
- Standard deadline: 2025-12-31
- Standard commission: 5%

**Upload:**
```
Payment Date: 14-12-2025
Commission Date: 20-12-2025
Payment Amount: 1000
```

**Expected:**
- ✅ Payment inserted with date Dec 14
- ✅ Commission calculated: standard (5%) = ₹50
- ✅ Does NOT get early commission (commission_date Dec 20 > early deadline Dec 15)
- ✅ Gets standard commission (commission_date Dec 20 <= standard deadline Dec 31)

---

### Test 3: Empty Commission Date

**Upload:**
```
Payment Date: 12-12-2025
Commission Date: [empty]
Payment Amount: 1000
```

**Expected:**
- ✅ Payment inserted with date Dec 12
- ✅ Commission date defaults to Dec 12
- ✅ Commission eligibility checked using Dec 12

---

## User Guide

### How to Use Commission Date

1. **Download Excel Template**
   - Template includes "Commission Date" column
   - Pre-filled with payment dates by default

2. **Modify Commission Dates (if needed)**
   - Leave empty to use payment date
   - Enter specific date in DD-MM-YYYY format (e.g., 15-12-2025)
   - Or use Excel date picker

3. **Upload Excel File**
   - Commission automatically calculated based on commission dates
   - Upload results show commission earned per payment

4. **View Commission Records**
   - Commission reports show payment_date (when payment was made)
   - Commission type (early/standard) determined by commission_date

---

## Migration from Previous Version

**Previous versions** did NOT have commission date column.

**For old Excel files:**
- Upload will still work (backwards compatible)
- Missing commission date will default to payment date
- No changes to existing functionality

**For new Excel files:**
- Download fresh template (includes commission date column)
- Commission dates pre-filled with payment dates
- Can modify as needed

---

## Benefits

✅ **Flexibility**: Backdate commission for special cases without changing payment records

✅ **Accuracy**: Separate payment collection date from commission eligibility date

✅ **Correction Tool**: Fix commission calculations without losing payment history

✅ **Backwards Compatible**: Empty commission dates default to payment dates

✅ **Audit Trail**: Payment date preserved in database for accurate payment history

---

## Important Notes

1. **Commission Date is ONLY for eligibility**
   - Determines if payment qualifies for early/standard commission
   - Does NOT change the payment_date in database

2. **Default Behavior**
   - Empty commission date = uses payment date
   - Invalid commission date = uses payment date
   - Template pre-fills commission_date = payment_date

3. **Database Storage**
   - `payment_date` field stores actual payment collection date
   - Commission type reflects commission_date eligibility
   - Commission reports show actual payment_date

4. **Reset Tool Compatible**
   - Reset commission tool works with new column
   - Re-uploading recalculates using commission dates
   - Can fix commission issues by resetting and re-uploading

---

**Status:** ✅ COMPLETE and ready for use

**Last Updated:** 2026-01-03
