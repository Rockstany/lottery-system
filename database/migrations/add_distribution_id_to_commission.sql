-- =====================================================
-- Add distribution_id to commission_earned table
-- This allows us to track which specific distribution earned commission
-- and prevent duplicate commission calculations
-- Date: 2025-12-04
-- =====================================================

USE gettoknow_db;

-- Add distribution_id column to commission_earned
ALTER TABLE commission_earned
ADD COLUMN distribution_id INT UNSIGNED NULL COMMENT 'Link to specific book distribution' AFTER event_id,
ADD INDEX idx_distribution_id (distribution_id);

-- Add foreign key constraint
ALTER TABLE commission_earned
ADD CONSTRAINT fk_commission_distribution
FOREIGN KEY (distribution_id) REFERENCES book_distribution(distribution_id) ON DELETE CASCADE;

-- =====================================================
-- DATABASE MIGRATION COMPLETE
-- =====================================================
