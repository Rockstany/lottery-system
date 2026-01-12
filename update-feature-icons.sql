-- Update Feature Icons to Display Emojis
-- Run this SQL in your phpMyAdmin or database management tool

-- Update Lottery System to use emoji icon instead of SVG path
UPDATE features
SET feature_icon = '🎟️'
WHERE feature_key = 'lottery_system';

-- Verify the update
SELECT feature_id, feature_name, feature_key, feature_icon
FROM features;

-- Expected Result:
-- feature_id | feature_name    | feature_key     | feature_icon
-- 1          | Lottery System  | lottery_system  | 🎟️
