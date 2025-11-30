-- Complete migration for book_distribution table
-- This updates the schema to use 'notes' instead of 'member_name' and adds 'distribution_path'

-- Step 1: Add distribution_path column if it doesn't exist
ALTER TABLE book_distribution
ADD COLUMN IF NOT EXISTS distribution_path VARCHAR(255) AFTER mobile_number;

-- Step 2: Add notes column if it doesn't exist (in case it's missing)
ALTER TABLE book_distribution
ADD COLUMN IF NOT EXISTS notes VARCHAR(255) AFTER distributed_by;

-- Step 3: Copy data from member_name to notes if member_name exists
UPDATE book_distribution
SET notes = COALESCE(notes, member_name)
WHERE member_name IS NOT NULL AND (notes IS NULL OR notes = '');

-- Step 4: Drop member_name column (if it exists)
ALTER TABLE book_distribution
DROP COLUMN IF EXISTS member_name;

-- Step 5: Remove the old level columns that are no longer used (optional - uncomment if needed)
-- ALTER TABLE book_distribution DROP COLUMN IF EXISTS level_1_value_id;
-- ALTER TABLE book_distribution DROP COLUMN IF EXISTS level_2_value_id;
-- ALTER TABLE book_distribution DROP COLUMN IF EXISTS level_3_value_id;
