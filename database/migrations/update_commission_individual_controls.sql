-- =====================================================
-- Update Commission System for Individual Controls
-- Allows each commission type to be enabled/disabled separately
-- Date: 2025-12-03
-- =====================================================

USE gettoknow_db;

-- =====================================================
-- 1. UPDATE COMMISSION SETTINGS TABLE
-- =====================================================

-- Add individual enable/disable columns for each commission type
ALTER TABLE commission_settings
ADD COLUMN early_commission_enabled TINYINT(1) DEFAULT 0 COMMENT 'Enable/disable early payment commission' AFTER commission_enabled,
ADD COLUMN standard_commission_enabled TINYINT(1) DEFAULT 0 COMMENT 'Enable/disable standard payment commission' AFTER early_commission_percent,
ADD COLUMN extra_books_commission_enabled TINYINT(1) DEFAULT 0 COMMENT 'Enable/disable extra books commission' AFTER standard_commission_percent;

-- Keep the old commission_enabled column for backward compatibility
-- It will act as a master switch, but individual switches take priority

-- =====================================================
-- 2. UPDATE BOOK DISTRIBUTION TABLE
-- =====================================================

-- Add is_extra_book flag to book_distribution
ALTER TABLE book_distribution
ADD COLUMN is_extra_book TINYINT(1) DEFAULT 0 COMMENT 'Mark this book as extra book for commission purposes' AFTER distributed_at;

-- =====================================================
-- 3. UPDATE PAYMENT COLLECTIONS TABLE
-- =====================================================

-- Add columns for payment status and book return tracking
ALTER TABLE payment_collections
ADD COLUMN payment_status ENUM('paid', 'no_payment_book_returned') DEFAULT 'paid' COMMENT 'Payment status or book return' AFTER payment_date,
ADD COLUMN return_reason TEXT NULL COMMENT 'Reason for book return if no payment' AFTER payment_status,
ADD COLUMN is_editable TINYINT(1) DEFAULT 1 COMMENT 'Can this payment be edited' AFTER return_reason;

-- =====================================================
-- 4. CREATE PAYMENT EDIT HISTORY TABLE
-- =====================================================

-- Track all payment edits for audit purposes
CREATE TABLE IF NOT EXISTS payment_edit_history (
    edit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT UNSIGNED NOT NULL,

    -- Old values
    old_amount DECIMAL(10,2) NOT NULL,
    old_payment_method ENUM('cash', 'upi', 'bank', 'other') NOT NULL,
    old_payment_date DATE NOT NULL,

    -- New values
    new_amount DECIMAL(10,2) NOT NULL,
    new_payment_method ENUM('cash', 'upi', 'bank', 'other') NOT NULL,
    new_payment_date DATE NOT NULL,

    -- Edit details
    edit_reason TEXT NOT NULL COMMENT 'Reason for editing payment',
    edited_by INT UNSIGNED NOT NULL,
    edited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (payment_id) REFERENCES payment_collections(payment_id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_payment (payment_id),
    INDEX idx_edited_at (edited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. UPDATE EXISTING DATA (Set defaults)
-- =====================================================

-- Set all existing books as regular books (not extra)
UPDATE book_distribution SET is_extra_book = 0 WHERE is_extra_book IS NULL;

-- Set all existing payments as editable
UPDATE payment_collections SET is_editable = 1 WHERE is_editable IS NULL;

-- Set all existing payments as 'paid' status
UPDATE payment_collections SET payment_status = 'paid' WHERE payment_status IS NULL;

-- =====================================================
-- DATABASE MIGRATION COMPLETE
-- =====================================================
-- Updates Made:
-- 1. commission_settings - Added individual enable/disable for each type
-- 2. book_distribution - Added is_extra_book flag
-- 3. payment_collections - Added payment_status, return_reason, is_editable
-- 4. payment_edit_history - New table for tracking edits
-- =====================================================
