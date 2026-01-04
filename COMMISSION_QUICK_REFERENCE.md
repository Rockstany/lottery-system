# Commission System - Quick Reference Guide

## 🎯 Quick Summary

**Commission is now calculated on EVERY payment** (partial or full) based on **actual payment amount**.

---

## ✅ What Works Now

### All Payment Methods
- ✅ Manual payment collection
- ✅ Excel upload
- ✅ Commission sync tool

### All Payment Types
- ✅ Partial payments (e.g., ₹300 of ₹500)
- ✅ Full payments (e.g., ₹500 all at once)
- ✅ Multiple payments for same book

### All Commission Types
- ✅ Early payment commission
- ✅ Standard payment commission
- ✅ Extra books commission

---

## 📊 Quick Examples

### Example 1: Partial Payments
**Book value:** ₹1,000
**Payments:**
- Dec 5: ₹600 → Commission: ₹60 (10%)
- Dec 15: ₹400 → Commission: ₹20 (5%)
**Total:** ₹80

### Example 2: Full Payment
**Book value:** ₹1,000
**Payment:**
- Dec 5: ₹1,000 → Commission: ₹100 (10%)
**Total:** ₹100

---

## 🔧 Master Commission Toggle

**Location:** Commission Setup Page
**What it does:** Enables/disables entire commission system
**When disabled:** NO commissions calculated (regardless of individual settings)

---

## 🧮 How Commission is Calculated

```
Commission = Payment Amount × Commission Percentage
```

**NOT based on:**
- ❌ Expected book value
- ❌ Total amount paid
- ❌ Remaining balance

**Based on:**
- ✅ Actual payment amount received
- ✅ Payment date (determines commission type)
- ✅ Book type (extra book flag)

---

## 🛠️ Commission Sync Tool

**What it does:** Recalculates ALL commissions from scratch

**When to use:**
- Commission data is incorrect
- Settings were changed
- Database cleanup needed

**What happens:**
1. Deletes all commission records
2. Finds ALL payments
3. Recalculates commission on each payment
4. Creates new commission records

---

## 📁 Files Modified

| File | Status | Purpose |
|------|--------|---------|
| lottery-payment-collect.php | ✅ Updated | Manual payment collection |
| lottery-commission-sync.php | ✅ Updated | Commission sync/recalculation |
| lottery-reports-excel-upload.php | ✅ Already correct | Excel upload |
| lottery-commission-setup.php | ✅ Updated | Master toggle added |
| lottery.php | ✅ Updated | Commission status indicators |

---

## 📝 Testing Checklist

### Quick Test
- [ ] Enable master commission toggle
- [ ] Collect partial payment (e.g., ₹300 of ₹500)
- [ ] Check database - commission should exist for ₹300
- [ ] Collect remaining payment (₹200)
- [ ] Check database - second commission should exist for ₹200

### Full Test
- [ ] Test manual payment collection
- [ ] Test Excel upload
- [ ] Test commission sync
- [ ] Verify no duplicates
- [ ] Verify correct amounts

---

## 🚨 Important Notes

1. **Commission enabled check:** Master toggle MUST be enabled
2. **Individual types:** Each commission type can be enabled separately
3. **Duplicate prevention:** Same payment + same date = no duplicate
4. **Multiple commissions:** Same book can have many commission records (one per payment)

---

## 📖 Documentation

- **Full Analysis:** [COMMISSION_CALCULATION_ANALYSIS.md](COMMISSION_CALCULATION_ANALYSIS.md)
- **Complete Summary:** [COMMISSION_STANDARDIZATION_COMPLETE.md](COMMISSION_STANDARDIZATION_COMPLETE.md)
- **Master Toggle:** [MASTER_COMMISSION_TOGGLE_IMPLEMENTATION.md](MASTER_COMMISSION_TOGGLE_IMPLEMENTATION.md)

---

## 🆘 Troubleshooting

**Commission not calculated?**
1. Check master commission toggle (must be enabled)
2. Check individual commission type (early/standard/extra)
3. Check payment date vs commission deadlines
4. Check `commission_enabled = 1` in database

**Duplicate commissions?**
- Run commission sync tool to clean up

**Wrong amounts?**
- Run commission sync tool to recalculate

---

**Last Updated:** January 4, 2026
