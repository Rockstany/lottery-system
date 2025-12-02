-- Add plain_password column to users table for password viewing
-- Note: This stores passwords in plain text for admin viewing purposes

ALTER TABLE users
ADD COLUMN plain_password VARCHAR(100) NULL AFTER password_hash;

-- Update existing users with a default password (you'll need to reset these)
UPDATE users
SET plain_password = '123456'
WHERE plain_password IS NULL;
