-- ============================================================================
-- CSF Funds Feature Database Schema
-- ============================================================================
-- This migration adds tables for CSF (Community Social Funds) management
-- Feature: Track member contributions, send reminders, generate reports
-- Integration: Uses Community Building members as backbone (no duplicate data)
-- ============================================================================

-- CSF Payments Table
-- Stores all payment records with flexible month tracking (JSON array)
CREATE TABLE IF NOT EXISTS csf_payments (
    payment_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_id INT(10) UNSIGNED NOT NULL,
    user_id INT(10) UNSIGNED NOT NULL COMMENT 'Member user_id from sub_community_members',
    sub_community_id INT(10) UNSIGNED NOT NULL,

    -- Payment details
    amount DECIMAL(10,2) NOT NULL CHECK (amount > 0),
    payment_date DATE NOT NULL,
    payment_for_months JSON NOT NULL COMMENT 'Array of months: ["2025-09", "2025-10", "2025-11"]',

    -- Payment method
    payment_method ENUM('cash', 'cheque', 'upi', 'bank_transfer') NOT NULL,
    transaction_id VARCHAR(255) NULL COMMENT 'For UPI/Bank Transfer',
    cheque_number VARCHAR(100) NULL COMMENT 'For Cheque payments',
    bank_details TEXT NULL COMMENT 'For Bank Transfer',
    payment_proof_image VARCHAR(255) NULL COMMENT 'Upload path for payment screenshot/photo',

    -- Tracking
    collected_by INT(10) UNSIGNED NULL COMMENT 'Group Admin who recorded this payment',
    notes TEXT NULL COMMENT 'Additional notes about the payment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys (Backbone Architecture)
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sub_community_id) REFERENCES sub_communities(sub_community_id) ON DELETE CASCADE,
    FOREIGN KEY (collected_by) REFERENCES users(user_id) ON DELETE SET NULL,

    -- Indexes for performance
    INDEX idx_payment_date (payment_date),
    INDEX idx_member (user_id),
    INDEX idx_sub_community (sub_community_id),
    INDEX idx_payment_method (payment_method),
    INDEX idx_community_date (community_id, payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='CSF payment records - integrates with Community Building members';

-- WhatsApp Reminders Log
-- Tracks all payment reminders sent via WhatsApp
CREATE TABLE IF NOT EXISTS csf_reminders (
    reminder_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_id INT(10) UNSIGNED NOT NULL,
    user_id INT(10) UNSIGNED NOT NULL COMMENT 'Member user_id from sub_community_members',
    reminder_for_month VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM (e.g., 2026-01)',
    reminder_sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_by INT(10) UNSIGNED NULL COMMENT 'Group Admin who sent reminder',
    status ENUM('sent', 'failed') DEFAULT 'sent',

    -- Foreign Keys
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(user_id) ON DELETE SET NULL,

    -- Indexes
    INDEX idx_reminder_month (reminder_for_month),
    INDEX idx_sent_at (reminder_sent_at),
    INDEX idx_community_month (community_id, reminder_for_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log of WhatsApp payment reminders sent to members';

-- Register CSF Funds Feature
-- Adds feature to features table for dashboard display
INSERT INTO features (feature_name, feature_key, feature_description, feature_icon, display_order, is_active)
VALUES ('CSF Funds', 'csf_funds', 'Track and manage Community Social Fund contributions with payment reminders', '💰', 3, 1)
ON DUPLICATE KEY UPDATE
    feature_description = 'Track and manage Community Social Fund contributions with payment reminders',
    feature_icon = '💰';

-- ============================================================================
-- Migration Complete
-- ============================================================================
-- Next Steps:
-- 1. Enable feature for community: Admin → Manage Features → Enable CSF Funds
-- 2. Access feature: Group Admin Dashboard → CSF Funds card
-- 3. Start recording payments from Community Building members
-- ============================================================================
