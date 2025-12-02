-- =====================================================
-- Add Commission & Winners Management Features
-- GetToKnow Community App
-- Date: 2025-12-02
-- =====================================================

USE gettoknow_db;

-- =====================================================
-- 1. UPDATE EXISTING TABLES
-- =====================================================

-- Add 'bank' payment method to payment_collections if not exists
ALTER TABLE payment_collections
MODIFY COLUMN payment_method ENUM('cash', 'upi', 'bank', 'other') NOT NULL DEFAULT 'cash';

-- =====================================================
-- 2. COMMISSION SYSTEM TABLES
-- =====================================================

-- Table: Commission Settings (Per Event Configuration)
CREATE TABLE IF NOT EXISTS commission_settings (
    setting_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    commission_enabled TINYINT(1) DEFAULT 0 COMMENT 'Group admin can toggle commission on/off',

    -- Early Payment Commission (10%)
    early_payment_date DATE COMMENT 'Date 1: Before this date = 10% commission',
    early_commission_percent DECIMAL(5,2) DEFAULT 10.00,

    -- Standard Payment Commission (5%)
    standard_payment_date DATE COMMENT 'Date 2: Before this date = 5% commission',
    standard_commission_percent DECIMAL(5,2) DEFAULT 5.00,

    -- Extra Books Commission (15%)
    extra_books_date DATE COMMENT 'After this date, new books get 15% commission',
    extra_books_commission_percent DECIMAL(5,2) DEFAULT 15.00,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_commission (event_id),
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: Commission Earned (Track commissions per Level 1)
CREATE TABLE IF NOT EXISTS commission_earned (
    commission_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    level_1_value VARCHAR(100) NOT NULL COMMENT 'Level 1 name (Wing A, Building B, etc.)',
    payment_collection_id BIGINT UNSIGNED COMMENT 'Link to specific payment',
    book_id INT UNSIGNED COMMENT 'Link to book',

    -- Commission details
    commission_type ENUM('early', 'standard', 'extra_books') NOT NULL,
    commission_percent DECIMAL(5,2) NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL COMMENT 'Amount on which commission is calculated',
    commission_amount DECIMAL(10,2) NOT NULL COMMENT 'Calculated commission amount',
    payment_date DATE NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (payment_collection_id) REFERENCES payment_collections(payment_id) ON DELETE SET NULL,
    FOREIGN KEY (book_id) REFERENCES lottery_books(book_id) ON DELETE SET NULL,
    INDEX idx_event (event_id),
    INDEX idx_level_1 (level_1_value),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. WINNERS MANAGEMENT TABLES
-- =====================================================

-- Table: Lottery Winners
CREATE TABLE IF NOT EXISTS lottery_winners (
    winner_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    ticket_number INT UNSIGNED NOT NULL,
    prize_position ENUM('1st', '2nd', '3rd', 'consolation') NOT NULL,

    -- Auto-populated from book/distribution data
    book_number INT UNSIGNED,
    distribution_path VARCHAR(255) COMMENT 'Full location path (Level 1 > Level 2 > Level 3)',

    -- Optional fields (can be added later)
    winner_name VARCHAR(100) COMMENT 'Winner name from lottery or manual entry',
    winner_contact VARCHAR(15) COMMENT 'Winner mobile number',

    -- Tracking
    added_by INT UNSIGNED NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_event_ticket (event_id, ticket_number),
    INDEX idx_event (event_id),
    INDEX idx_prize_position (prize_position),
    INDEX idx_ticket_number (ticket_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. CREATE VIEWS FOR REPORTING
-- =====================================================

-- View: Commission Summary Per Level 1
CREATE OR REPLACE VIEW view_commission_summary AS
SELECT
    ce.event_id,
    le.event_name,
    ce.level_1_value,
    ce.commission_type,
    COUNT(*) as payment_count,
    SUM(ce.payment_amount) as total_payment_amount,
    SUM(ce.commission_amount) as total_commission_earned,
    AVG(ce.commission_percent) as avg_commission_percent
FROM commission_earned ce
JOIN lottery_events le ON ce.event_id = le.event_id
GROUP BY ce.event_id, ce.level_1_value, ce.commission_type;

-- View: Winners Summary
CREATE OR REPLACE VIEW view_winners_summary AS
SELECT
    lw.event_id,
    le.event_name,
    lw.prize_position,
    lw.ticket_number,
    lw.book_number,
    lw.distribution_path,
    lw.winner_name,
    lw.winner_contact,
    lw.added_at
FROM lottery_winners lw
JOIN lottery_events le ON lw.event_id = le.event_id
ORDER BY
    CASE lw.prize_position
        WHEN '1st' THEN 1
        WHEN '2nd' THEN 2
        WHEN '3rd' THEN 3
        WHEN 'consolation' THEN 4
    END,
    lw.added_at;

-- =====================================================
-- 5. ADD INDEXES FOR PERFORMANCE
-- =====================================================

-- Composite indexes for common queries
CREATE INDEX idx_commission_event_level ON commission_earned(event_id, level_1_value);
CREATE INDEX idx_winners_event_position ON lottery_winners(event_id, prize_position);

-- =====================================================
-- 6. INSERT DEFAULT COMMISSION SETTINGS FOR EXISTING EVENTS
-- =====================================================

-- Insert default commission settings for all existing lottery events
-- (disabled by default, group admin can enable later)
INSERT INTO commission_settings (event_id, commission_enabled)
SELECT event_id, 0
FROM lottery_events
WHERE event_id NOT IN (SELECT event_id FROM commission_settings);

-- =====================================================
-- DATABASE MIGRATION COMPLETE
-- =====================================================
-- New Tables Added: 3
-- - commission_settings
-- - commission_earned
-- - lottery_winners
--
-- New Views Added: 2
-- - view_commission_summary
-- - view_winners_summary
--
-- Ready for Commission & Winners Features
-- =====================================================
