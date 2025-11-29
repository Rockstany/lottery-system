-- Add 'bank' to payment_method ENUM in both tables
-- Run this SQL to update the database schema

-- Update payment_collections table (Lottery)
ALTER TABLE payment_collections
MODIFY COLUMN payment_method ENUM('cash', 'upi', 'bank', 'other') NOT NULL DEFAULT 'cash';

-- Update payment_history table (Transaction)
ALTER TABLE payment_history
MODIFY COLUMN payment_method ENUM('cash', 'upi', 'bank', 'other') NOT NULL DEFAULT 'cash';
