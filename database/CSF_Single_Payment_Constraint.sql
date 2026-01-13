-- ===============================================================
-- CSF Single Payment Per Member Per Month Constraint
-- ===============================================================
-- Migration Date: 2026-01-13
-- Purpose: Enforce business rule that each member can only make
--          ONE payment per month in CSF (Community Social Funds)
--
-- Business Rule:
-- - Each member can pay ANY amount (not fixed)
-- - Only ONE payment allowed per member per month
-- - No partial payments - either PAID (any amount > 0) or UNPAID (₹0)
-- ===============================================================

-- Step 1: Check for existing duplicates before adding constraint
-- This query will show any existing duplicate payments that violate the rule

SELECT
    community_id,
    user_id,
    YEAR(payment_date) as year,
    MONTH(payment_date) as month,
    COUNT(*) as payment_count,
    GROUP_CONCAT(payment_id ORDER BY payment_id) as duplicate_payment_ids
FROM csf_payments
GROUP BY community_id, user_id, YEAR(payment_date), MONTH(payment_date)
HAVING payment_count > 1;

-- ===============================================================
-- IMPORTANT: If the above query returns any rows, you have duplicate
-- payments that need to be resolved before applying the constraint.
--
-- Options to resolve duplicates:
-- 1. Keep the latest payment and delete older ones
-- 2. Merge amounts into single payment
-- 3. Manual review and decision
-- ===============================================================

-- Step 2: (OPTIONAL) Remove duplicate payments - EXAMPLE ONLY
-- Uncomment and modify the following query ONLY if you want to
-- automatically keep the latest payment and delete older ones:

/*
DELETE FROM csf_payments
WHERE payment_id NOT IN (
    SELECT * FROM (
        SELECT MAX(payment_id) as payment_id
        FROM csf_payments
        GROUP BY community_id, user_id, YEAR(payment_date), MONTH(payment_date)
    ) AS keep_payments
);
*/

-- ===============================================================
-- Step 3: Add UNIQUE constraint to enforce single payment rule
-- ===============================================================

-- Drop the constraint if it already exists (for re-running migration)
ALTER TABLE csf_payments
DROP INDEX IF EXISTS unique_member_month_payment;

-- Add the UNIQUE constraint
-- This creates a composite unique index on:
-- - community_id: Ensures isolation between communities
-- - user_id: Identifies the member
-- - YEAR(payment_date): Payment year
-- - MONTH(payment_date): Payment month
--
-- NOTE: MySQL doesn't support functional indexes in UNIQUE constraints directly,
-- so we'll use a workaround with VIRTUAL generated columns

-- Add generated columns for year and month (if they don't exist)
ALTER TABLE csf_payments
ADD COLUMN IF NOT EXISTS payment_year INT AS (YEAR(payment_date)) STORED,
ADD COLUMN IF NOT EXISTS payment_month INT AS (MONTH(payment_date)) STORED;

-- Create the UNIQUE constraint using the generated columns
ALTER TABLE csf_payments
ADD UNIQUE KEY unique_member_month_payment (community_id, user_id, payment_year, payment_month);

-- ===============================================================
-- Step 4: Test the constraint
-- ===============================================================

-- This query should succeed (first payment for a member in a month)
-- Test it by inserting a payment for a user who hasn't paid this month

-- This query should FAIL with "Duplicate entry" error
-- because it tries to insert a second payment for the same member in the same month
/*
INSERT INTO csf_payments
(community_id, sub_community_id, user_id, amount, payment_date, payment_method, collected_by, payment_for_months, created_at)
VALUES
(1, 1, 1, 100, '2026-01-15', 'cash', 1, '["2026-01"]', NOW());

-- This should fail:
INSERT INTO csf_payments
(community_id, sub_community_id, user_id, amount, payment_date, payment_method, collected_by, payment_for_months, created_at)
VALUES
(1, 1, 1, 50, '2026-01-20', 'upi', 1, '["2026-01"]', NOW());
*/

-- ===============================================================
-- Step 5: Verification Query
-- ===============================================================

-- Check that the constraint exists
SHOW INDEX FROM csf_payments WHERE Key_name = 'unique_member_month_payment';

-- ===============================================================
-- ROLLBACK (if needed)
-- ===============================================================
-- If you need to remove this constraint for any reason:

/*
ALTER TABLE csf_payments DROP INDEX unique_member_month_payment;
ALTER TABLE csf_payments DROP COLUMN payment_year;
ALTER TABLE csf_payments DROP COLUMN payment_month;
*/

-- ===============================================================
-- NOTES:
-- ===============================================================
-- 1. This constraint ensures database-level enforcement of the business rule
-- 2. Application code should handle the error gracefully and show user-friendly message
-- 3. The constraint works across all payment methods (cash, UPI, bank transfer, cheque)
-- 4. Each month is treated independently (member can pay once per month)
-- 5. The generated columns (payment_year, payment_month) add minimal storage overhead
-- 6. Queries filtering by year/month can use these indexed columns for better performance

-- ===============================================================
-- ERROR HANDLING IN APPLICATION CODE:
-- ===============================================================
-- When inserting a payment, catch the duplicate entry error:
/*
try {
    $stmt->execute([...]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {  // Integrity constraint violation
        if (strpos($e->getMessage(), 'unique_member_month_payment') !== false) {
            throw new Exception("Member has already made a payment for this month. Each member can only make ONE payment per month.");
        }
    }
    throw $e;
}
*/

-- ===============================================================
-- END OF MIGRATION
-- ===============================================================
