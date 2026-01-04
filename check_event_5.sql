-- =====================================================
-- COMMISSION CHECK FOR EVENT 5 (Christmas 25)
-- Run this in phpMyAdmin or MySQL Workbench
-- =====================================================

-- Step 1: Verify Event 5 exists
SELECT '=== STEP 1: Event Information ===' as step;
SELECT event_id, event_name, tickets_per_book, price_per_ticket, created_at
FROM lottery_events
WHERE event_id = 5;

-- Step 2: Check commission settings for Event 5
SELECT '=== STEP 2: Commission Settings ===' as step;
SELECT
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
FROM commission_settings
WHERE event_id = 5;

-- If the above returns NO ROWS, that's your problem!
-- Uncomment and run this INSERT to create settings:

/*
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
    5,              -- Event ID
    1,              -- Enable commission system
    1,              -- Enable early commission
    '2025-12-14',  -- Early payment deadline (CHANGE THIS to your deadline)
    10.00,         -- 10% for early payment (CHANGE THIS to your percentage)
    0,              -- Disable standard commission
    NULL,           -- Standard date
    5.00,           -- 5% for standard
    0,              -- Disable extra books commission
    NULL,           -- Extra books date
    15.00           -- 15% for extra books
);
*/

-- Step 3: Check payment status for all books in Event 5
SELECT '=== STEP 3: Payment Status ===' as step;
SELECT
    lb.book_number,
    bd.distribution_path,
    bd.is_extra_book,
    le.tickets_per_book * le.price_per_ticket as expected_amount,
    COALESCE(SUM(pc.amount_paid), 0) as total_paid,
    CASE
        WHEN COALESCE(SUM(pc.amount_paid), 0) >= (le.tickets_per_book * le.price_per_ticket)
        THEN 'FULLY_PAID'
        WHEN COALESCE(SUM(pc.amount_paid), 0) > 0
        THEN 'PARTIAL'
        ELSE 'UNPAID'
    END as status,
    MAX(pc.payment_date) as last_payment_date
FROM lottery_books lb
JOIN book_distribution bd ON lb.book_id = bd.book_id
JOIN lottery_events le ON lb.event_id = le.event_id
LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
WHERE le.event_id = 5
GROUP BY lb.book_id
ORDER BY lb.book_number;

-- Step 4: Count payment statuses
SELECT '=== STEP 4: Payment Summary ===' as step;
SELECT
    CASE
        WHEN COALESCE(SUM(pc.amount_paid), 0) >= (le.tickets_per_book * le.price_per_ticket)
        THEN 'FULLY_PAID'
        WHEN COALESCE(SUM(pc.amount_paid), 0) > 0
        THEN 'PARTIAL'
        ELSE 'UNPAID'
    END as status,
    COUNT(*) as count
FROM lottery_books lb
JOIN book_distribution bd ON lb.book_id = bd.book_id
JOIN lottery_events le ON lb.event_id = le.event_id
LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
WHERE le.event_id = 5
GROUP BY status;

-- Step 5: Check commission records for Event 5
SELECT '=== STEP 5: Commission Records ===' as step;
SELECT
    ce.commission_id,
    lb.book_number,
    ce.level_1_value,
    ce.commission_type,
    ce.commission_percent,
    ce.payment_amount,
    ce.commission_amount,
    ce.payment_date
FROM commission_earned ce
LEFT JOIN lottery_books lb ON ce.book_id = lb.book_id
WHERE ce.event_id = 5
ORDER BY ce.created_at DESC;

-- Step 6: Total commission earned for Event 5
SELECT '=== STEP 6: Total Commission ===' as step;
SELECT
    COUNT(*) as total_records,
    SUM(commission_amount) as total_commission_earned
FROM commission_earned
WHERE event_id = 5;

-- Step 7: Commission by Level 1 (Unit/Building)
SELECT '=== STEP 7: Commission by Unit ===' as step;
SELECT
    level_1_value,
    commission_type,
    COUNT(*) as book_count,
    SUM(commission_amount) as total_commission
FROM commission_earned
WHERE event_id = 5
GROUP BY level_1_value, commission_type
ORDER BY level_1_value, commission_type;

-- =====================================================
-- INTERPRETATION:
-- =====================================================
-- STEP 2: If this returns NO ROWS → Commission settings don't exist
--         → Uncomment and run the INSERT statement above
--
-- STEP 3: Look for 'FULLY_PAID' books
--         → Only these books should have commission
--
-- STEP 5: If this returns NO ROWS but you have FULLY_PAID books
--         → Commission calculation failed
--         → Either settings missing OR need to recalculate
--
-- STEP 6: Should show total commission earned
--         → If 0 but you have fully paid books = PROBLEM
-- =====================================================
