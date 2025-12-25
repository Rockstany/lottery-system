# Commission Calculation Logic - Complete Explanation

**File:** `lottery-payment-collect.php` (Lines 125-219)
**Date:** 2025-12-25

---

## 📋 Overview

Commission is calculated **automatically** when a payment is collected. The system supports **multiple commission types** that can apply **simultaneously**.

---

## 🔑 Critical Conditions & Limitations

### **Condition #1: ONLY on FULL Payment** ⭐ MOST IMPORTANT

```php
// Lines 125-127
$newTotalPaid = $book['total_paid'] + $amount;
$isFullyPaid = ($newTotalPaid >= $expectedAmount);

if ($isFullyPaid) {  // Commission ONLY if fully paid
    // Calculate commission
}
```

**What This Means:**
- ❌ **No commission on partial payments**
- ✅ **Commission calculated ONLY when book is fully paid**
- ✅ Even if paid in 10 installments, commission calculated when LAST payment completes the full amount

**Example:**
```
Book Expected: ₹1000

Scenario 1: Single payment
- Payment: ₹1000 → ✅ Commission calculated

Scenario 2: Multiple payments
- Payment 1: ₹300 → ❌ No commission (partial)
- Payment 2: ₹400 → ❌ No commission (partial)
- Payment 3: ₹300 → ✅ Commission calculated (now fully paid)

Scenario 3: Over-payment
- Payment: ₹1100 → ✅ Commission calculated (≥ expected amount)
```

---

### **Condition #2: Commission Must Be Enabled**

```php
// Lines 131-135
$commissionQuery = "SELECT * FROM commission_settings
                    WHERE event_id = :event_id
                    AND commission_enabled = 1";  // Global toggle
```

**Settings Required:**
1. **Global Toggle:** `commission_enabled = 1` in `commission_settings` table
2. **Individual Toggles:** At least one commission type must be enabled:
   - `early_commission_enabled = 1`, OR
   - `standard_commission_enabled = 1`, OR
   - `extra_books_commission_enabled = 1`

**Where to Enable:**
- Group Admin → Event → "Commission Setup" page
- Toggle: "Enable Commission System" = ON
- Enable specific types: Early/Standard/Extra Books

---

### **Condition #3: Distribution Must Have Level 1 Value**

```php
// Lines 175-180
$level1Value = '';
if (!empty($book['distribution_path'])) {
    $pathParts = explode(' > ', $book['distribution_path']);
    $level1Value = $pathParts[0] ?? '';
}

if ($level1Value && count($eligibleCommissions) > 0) {
    // Save commission
}
```

**Requirements:**
- ❌ If `distribution_path` is empty → No commission
- ❌ If Level 1 is missing → No commission
- ✅ Level 1 extracted from path: "Wing A > Floor 2 > Flat 101" → Level 1 = "Wing A"

**Why Level 1?**
- Commission tracked by Level 1 (Wing/Building/Unit)
- Reports show commission breakdown by Level 1
- Each Level 1 manager gets commission summary

---

### **Condition #4: No Duplicate Commission**

```php
// Lines 186-196
$checkQuery = "SELECT COUNT(*) as count FROM commission_earned
              WHERE distribution_id = :dist_id
              AND commission_type = :comm_type";

if ($exists['count'] == 0) {
    // Insert commission
}
```

**Protection:**
- ❌ Cannot create duplicate commission for same book + type
- ✅ Each commission type can only be awarded ONCE per book
- ✅ Prevents double-commission if payment is edited/updated

**Example:**
```
Book #1 paid fully on Dec 15:
- Early commission (10%) → Created ✅
- Try to pay again → Early commission already exists → Skipped ✅

Book #1 is also extra book:
- Extra books commission (15%) → Created ✅ (different type, allowed)
```

---

## 🎯 Commission Types & Logic

### **Type 1: Early Payment Commission**

```php
// Lines 158-165
if ($commSettings['early_commission_enabled'] == 1 &&
    !empty($commSettings['early_payment_date']) &&
    $paymentDate <= $commSettings['early_payment_date']) {

    $eligibleCommissions[] = [
        'type' => 'early',
        'percent' => $commSettings['early_commission_percent']  // Default: 10%
    ];
}
```

**Conditions:**
1. ✅ `early_commission_enabled = 1`
2. ✅ `early_payment_date` is set (not empty)
3. ✅ `payment_date <= early_payment_date`

**Example:**
```
Early Payment Date: December 20, 2025
Early Commission: 10%

Payment on Dec 15 → ✅ Gets 10% commission (before deadline)
Payment on Dec 20 → ✅ Gets 10% commission (on deadline)
Payment on Dec 21 → ❌ No early commission (after deadline)
```

---

### **Type 2: Standard Payment Commission**

```php
// Lines 166-173
elseif ($commSettings['standard_commission_enabled'] == 1 &&
        !empty($commSettings['standard_payment_date']) &&
        $paymentDate <= $commSettings['standard_payment_date']) {

    $eligibleCommissions[] = [
        'type' => 'standard',
        'percent' => $commSettings['standard_commission_percent']  // Default: 5%
    ];
}
```

**Conditions:**
1. ✅ `standard_commission_enabled = 1`
2. ✅ `standard_payment_date` is set (not empty)
3. ✅ `payment_date <= standard_payment_date`
4. ❌ **Must NOT qualify for early commission** (elseif logic)

**Example:**
```
Early Payment Date: December 20, 2025 (10%)
Standard Payment Date: December 31, 2025 (5%)

Payment on Dec 15 → ✅ Early commission 10% (NOT standard)
Payment on Dec 25 → ✅ Standard commission 5% (missed early)
Payment on Jan 1  → ❌ No date-based commission (missed both)
```

**Important:** You get **EITHER** early **OR** standard, **NOT BOTH**!

---

### **Type 3: Extra Books Commission**

```php
// Lines 148-155
if ($bookDist && $bookDist['is_extra_book'] == 1 &&
    $commSettings['extra_books_commission_enabled'] == 1) {

    $eligibleCommissions[] = [
        'type' => 'extra_books',
        'percent' => $commSettings['extra_books_commission_percent']  // Default: 15%
    ];
}
```

**Conditions:**
1. ✅ `extra_books_commission_enabled = 1`
2. ✅ Book marked as `is_extra_book = 1` during distribution
3. ✅ **Independent of payment date** (can combine with early/standard)

**Example:**
```
Extra Books Commission: 15%
Early Payment Commission: 10%

Book marked as "Extra Book" + paid early:
- Extra books commission: 15% ✅
- Early payment commission: 10% ✅
- TOTAL: 25% commission!
```

**How to Mark as Extra Book:**
- During book assignment: Check "Mark as Extra Book" checkbox
- During bulk assignment: Check the extra book option
- Cannot change after assignment (requires re-assignment)

---

## 🔢 Commission Calculation Formula

```php
// Line 197
$commissionAmount = ($expectedAmount * $commission['percent']) / 100;
```

**Calculation:**
- Commission calculated on **EXPECTED amount** (not paid amount)
- Even if customer overpays, commission is on expected amount

**Example:**
```
Expected Amount: ₹1000
Commission Percent: 10%

Scenario 1: Paid exactly
- Payment: ₹1000
- Commission: (₹1000 × 10%) / 100 = ₹100 ✅

Scenario 2: Overpaid
- Payment: ₹1200
- Commission: Still (₹1000 × 10%) / 100 = ₹100 ✅

Scenario 3: Multiple commissions
- Extra Book (15%) + Early Payment (10%)
- Commission 1: (₹1000 × 15%) / 100 = ₹150
- Commission 2: (₹1000 × 10%) / 100 = ₹100
- TOTAL: ₹250 (two separate records)
```

---

## 📊 Data Stored in `commission_earned` Table

```php
// Lines 199-214
INSERT INTO commission_earned
(event_id, distribution_id, level_1_value, commission_type, commission_percent,
 payment_amount, commission_amount, payment_date, book_id)
VALUES (...)
```

**Fields:**
| Field | Value | Purpose |
|-------|-------|---------|
| `event_id` | Event ID | Link to lottery event |
| `distribution_id` | Distribution ID | Link to book distribution |
| `level_1_value` | "Wing A" | Level 1 from distribution path |
| `commission_type` | 'early'/'standard'/'extra_books' | Type of commission |
| `commission_percent` | 10.00 | Percentage used |
| `payment_amount` | 1000.00 | Expected amount (base for calculation) |
| `commission_amount` | 100.00 | Calculated commission |
| `payment_date` | 2025-12-15 | Date of FULL payment |
| `book_id` | Book ID | Link to lottery book |

---

## 🔄 Multiple Commission Scenario

**Can a single book get multiple commissions?**

✅ **YES!** If it qualifies for different types:

```
Example Book:
- Expected: ₹1000
- Marked as "Extra Book"
- Paid on Dec 15 (before early deadline of Dec 20)

Commission Calculation:
1. Extra Books Commission:
   - Type: extra_books
   - Percent: 15%
   - Amount: ₹150

2. Early Payment Commission:
   - Type: early
   - Percent: 10%
   - Amount: ₹100

TOTAL: ₹250 (two separate records in commission_earned table)
```

**Database Records:**
```sql
commission_earned table:
commission_id | distribution_id | commission_type | commission_percent | commission_amount
-----------------------------------------------------------------------------
1             | 123            | extra_books     | 15.00             | 150.00
2             | 123            | early          | 10.00             | 100.00
```

---

## ⚠️ Important Limitations & Edge Cases

### **Limitation #1: Partial Payments**

**Scenario:**
```
Book Expected: ₹1000
Early Payment Deadline: Dec 20

Timeline:
- Dec 10: Pay ₹300 (partial) → ❌ No commission
- Dec 15: Pay ₹400 (partial) → ❌ No commission
- Dec 25: Pay ₹300 (FULL) → ❌ No early commission (date is Dec 25, after deadline)
```

**Issue:** Even though payments started early, commission uses FINAL payment date!

**Solution:** Use Commission Sync Tool to recalculate based on FIRST payment date (if needed)

---

### **Limitation #2: Payment Date Matters**

Commission uses the **payment_date** when collecting payment, NOT the actual transaction date.

**Example:**
```
Actual payment received: Dec 15
Admin enters payment in system: Dec 26
Payment date selected in form: Dec 26

Result: Commission calculated using Dec 26 (misses early deadline) ❌
```

**Best Practice:** Always enter correct payment date in the form!

---

### **Limitation #3: Cannot Remove Commission**

Once commission is created:
- ❌ Cannot delete from UI
- ❌ Cannot reduce commission percent
- ✅ Can only add NEW commission types (if not already exist)

**To Fix:** Must delete from database directly:
```sql
DELETE FROM commission_earned WHERE distribution_id = 123;
```

---

### **Limitation #4: Commission on Payment Amount, Not Book Value**

```php
// Line 197
$commissionAmount = ($expectedAmount * $commission['percent']) / 100;
```

**Always uses expected amount**, even if:
- Book has different ticket price
- Book has special discount
- Customer pays more/less

**Example:**
```
Expected Amount (tickets × price): ₹1000
But special discount given, customer pays: ₹800

Commission still calculated on ₹1000 (not ₹800)
```

---

## 📈 Commission Workflow

```
┌─────────────────────────────────────────────────────────┐
│ 1. Payment Collected (Partial or Full)                  │
└───────────────┬─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────┐
│ 2. Check: Is Total Paid >= Expected Amount?             │
│    - If NO  → Stop (No commission)                      │
│    - If YES → Continue                                   │
└───────────────┬─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────┐
│ 3. Check: Is Commission Enabled for Event?              │
│    - If NO  → Stop (No commission)                      │
│    - If YES → Continue                                   │
└───────────────┬─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────┐
│ 4. Check: Is Book Marked as Extra Book?                 │
│    - If YES + Extra Commission Enabled                   │
│      → Add Extra Books Commission (15%)                  │
└───────────────┬─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────┐
│ 5. Check: Payment Date vs Early Deadline                │
│    - If payment_date <= early_date + Early Enabled       │
│      → Add Early Commission (10%)                        │
│    - ELSE: Check Standard Deadline                       │
│      - If payment_date <= standard_date + Standard Enabled│
│        → Add Standard Commission (5%)                    │
└───────────────┬─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────┐
│ 6. Extract Level 1 from Distribution Path               │
│    "Wing A > Floor 2 > Flat 101" → Level 1 = "Wing A"   │
└───────────────┬─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────┐
│ 7. For Each Eligible Commission Type:                   │
│    - Check if already exists (prevent duplicates)        │
│    - Calculate: (expected_amount × percent) / 100       │
│    - Insert into commission_earned table                 │
└─────────────────────────────────────────────────────────┘
```

---

## 📝 Summary Table

| Condition | Requirement | Impact if Not Met |
|-----------|------------|-------------------|
| **Full Payment** | total_paid >= expected_amount | ❌ No commission at all |
| **Commission Enabled** | commission_enabled = 1 | ❌ No commission at all |
| **Individual Toggle** | At least one type enabled | ❌ No commission for that type |
| **Level 1 Value** | distribution_path has Level 1 | ❌ No commission at all |
| **Early Date Set** | early_payment_date not empty | ❌ No early commission |
| **Payment Before Early** | payment_date <= early_date | ❌ No early commission |
| **Standard Date Set** | standard_payment_date not empty | ❌ No standard commission |
| **Payment Before Standard** | payment_date <= standard_date | ❌ No standard commission |
| **Extra Book Flag** | is_extra_book = 1 | ❌ No extra books commission |
| **No Duplicate** | Commission type not exists | ⚠️ Skips duplicate, no error |

---

## 🎯 Commission Settings Configuration

**Database:** `commission_settings` table

```sql
Example Record:
setting_id: 1
event_id: 5
commission_enabled: 1

early_commission_enabled: 1
early_payment_date: 2025-12-20
early_commission_percent: 10.00

standard_commission_enabled: 1
standard_payment_date: 2025-12-31
standard_commission_percent: 5.00

extra_books_commission_enabled: 1
extra_books_date: NULL  (Not used in logic!)
extra_books_commission_percent: 15.00
```

**UI Location:** Group Admin → Event → "Commission Setup" button

---

**That's the complete commission logic!** 🎉
