-- Add distribution_path column to book_distribution for easier display
ALTER TABLE book_distribution
ADD COLUMN distribution_path VARCHAR(255) AFTER mobile_number;
