-- =====================================================
-- Commission Settings Fix Script
-- Run this to check and fix commission configuration
-- =====================================================

-- Step 1: Check all lottery events
SELECT 'Step 1: Available Events' as step;
SELECT event_id, event_name, created_at
FROM lottery_events
ORDER BY created_at DESC;

-- Step 2: Check commission settings for each event
SELECT 'Step 2: Checking Commission Settings' as step;
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
FROM commission_settings;

-- Step 3: If NO settings found, insert default settings for event ID 1
-- IMPORTANT: Replace '1' with your actual event_id and adjust the date
SELECT 'Step 3: Inserting default commission settings (if missing)' as step;

-- Uncomment and modify this INSERT statement if commission_settings is empty:
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
    1,                      -- CHANGE THIS: Your event ID
    1,                      -- Enable master commission switch
    1,                      -- Enable early commission
    '2025-12-14',          -- CHANGE THIS: Early payment deadline (Y-m-d format)
    10.00,                 -- 10% for early payment
    0,                      -- Disable standard (or change to 1)
    NULL,                  -- Standard date (set if using)
    5.00,                  -- 5% for standard
    0,                      -- Disable extra books (or change to 1)
    NULL,                  -- Extra books date
    15.00                  -- 15% for extra books
);
*/

-- Step 4: Update existing settings to enable early commission
-- IMPORTANT: Replace '1' with your actual event_id
SELECT 'Step 4: Updating existing commission settings to enable early payment' as step;

-- Uncomment to update existing settings:
/*
UPDATE commission_settings
SET
    commission_enabled = 1,
    early_commission_enabled = 1,
    early_payment_date = '2025-12-14',     -- CHANGE THIS: Your deadline date
    early_commission_percent = 10.00        -- CHANGE THIS: Your percentage
WHERE event_id = 1;                         -- CHANGE THIS: Your event ID
*/

-- Step 5: Verify payment status for all books
SELECT 'Step 5: Checking Payment Status' as step;
SELECT
    lb.book_number,
    bd.distribution_path,
    le.tickets_per_book * le.price_per_ticket as expected_amount,
    COALESCE(SUM(pc.amount_paid), 0) as total_paid,
    CASE
        WHEN COALESCE(SUM(pc.amount_paid), 0) >= (le.tickets_per_book * le.price_per_ticket)
        THEN 'FULLY PAID'
        WHEN COALESCE(SUM(pc.amount_paid), 0) > 0
        THEN 'PARTIAL'
        ELSE 'UNPAID'
    END as status,
    MAX(pc.payment_date) as last_payment_date
FROM lottery_books lb
JOIN book_distribution bd ON lb.book_id = bd.book_id
JOIN lottery_events le ON lb.event_id = le.event_id
LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
WHERE le.event_id = 1                       -- CHANGE THIS: Your event ID
GROUP BY lb.book_id
ORDER BY lb.book_number;

-- Step 6: Check existing commission records
SELECT 'Step 6: Checking Commission Records' as step;
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
WHERE ce.event_id = 1                       -- CHANGE THIS: Your event ID
ORDER BY ce.created_at DESC;

-- Step 7: Count commission records by type
SELECT 'Step 7: Commission Summary by Type' as step;
SELECT
    commission_type,
    COUNT(*) as count,
    SUM(payment_amount) as total_payment,
    SUM(commission_amount) as total_commission
FROM commission_earned
WHERE event_id = 1                          -- CHANGE THIS: Your event ID
GROUP BY commission_type;

-- Step 8: Commission by Level 1 (Unit/Building)
SELECT 'Step 8: Commission by Level 1' as step;
SELECT
    level_1_value,
    commission_type,
    COUNT(*) as books_count,
    SUM(commission_amount) as total_commission
FROM commission_earned
WHERE event_id = 1                          -- CHANGE THIS: Your event ID
GROUP BY level_1_value, commission_type
ORDER BY level_1_value, commission_type;

-- =====================================================
-- INSTRUCTIONS:
-- =====================================================
-- 1. Run this script in your MySQL/phpMyAdmin
-- 2. Check the results of each step
-- 3. If Step 2 shows NO RESULTS, uncomment the INSERT in Step 3
-- 4. Replace all event_id = 1 with your actual event ID
-- 5. Replace '2025-12-14' with your actual deadline date
-- 6. If settings exist but are wrong, uncomment the UPDATE in Step 4
-- =====================================================
