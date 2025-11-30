-- Rename member_name to notes in book_distribution table
-- This makes the field more flexible for storing any information about the assignment

ALTER TABLE book_distribution
CHANGE COLUMN member_name notes VARCHAR(255);
