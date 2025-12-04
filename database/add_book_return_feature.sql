-- Add Book Return Tracking Feature
-- This allows tracking whether lottery books have been physically returned

-- 1. Add columns to book_distribution table
ALTER TABLE book_distribution
ADD COLUMN is_returned TINYINT(1) DEFAULT 0 COMMENT '0=Not Returned, 1=Returned',
ADD COLUMN returned_by INT UNSIGNED NULL COMMENT 'User who marked as returned',
ADD CONSTRAINT fk_returned_by FOREIGN KEY (returned_by) REFERENCES users(user_id) ON DELETE SET NULL;

-- 2. Add book return deadline to lottery_events table
ALTER TABLE lottery_events
ADD COLUMN book_return_deadline DATE NULL COMMENT 'Date after which non-returned books are flagged';

-- 3. Add index for faster queries
ALTER TABLE book_distribution
ADD INDEX idx_is_returned (is_returned);
