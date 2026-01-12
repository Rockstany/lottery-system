-- ============================================
-- FIX ALL FEATURES - Complete Database Update
-- ============================================
-- Run this in phpMyAdmin to fix feature display issues
-- Database: u717011923_gettoknow_db
-- ============================================

-- Step 1: Update Lottery System feature with correct icon and description
UPDATE features
SET
    feature_icon = '🎟️',
    feature_description = 'Complete 6-part lottery event management with auto-generation, distribution, and payment tracking',
    feature_name = 'Lottery System',
    display_order = 1,
    is_active = 1
WHERE feature_key = 'lottery_system';

-- Step 2: If feature doesn't exist, insert it
INSERT INTO features (feature_name, feature_key, feature_description, feature_icon, display_order, is_active)
SELECT 'Lottery System', 'lottery_system',
       'Complete 6-part lottery event management with auto-generation, distribution, and payment tracking',
       '🎟️', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM features WHERE feature_key = 'lottery_system');

-- Step 3: Verify the update
SELECT
    feature_id,
    feature_name,
    feature_key,
    feature_icon,
    LEFT(feature_description, 50) as description_preview,
    display_order,
    is_active
FROM features
ORDER BY display_order;

-- ============================================
-- Expected Result:
-- ============================================
-- feature_id | feature_name    | feature_key     | feature_icon | description_preview                                | display_order | is_active
-- 1          | Lottery System  | lottery_system  | 🎟️           | Complete 6-part lottery event management with...  | 1             | 1
-- ============================================

-- Optional: Add more features for future use
-- Uncomment the lines below if you want to add these features

/*
INSERT INTO features (feature_name, feature_key, feature_description, feature_icon, display_order, is_active)
VALUES
('Event Management', 'event_management', 'Organize and manage community events, activities, and gatherings', '🎯', 2, 1),
('Member Directory', 'member_directory', 'Browse and search community member profiles and contact information', '👥', 3, 1),
('Announcements', 'announcements', 'Post and manage important community announcements and updates', '📢', 4, 1),
('Payment Tracking', 'payment_tracking', 'Track member payments, dues, and financial contributions', '💰', 5, 1),
('Reports & Analytics', 'reports_analytics', 'View detailed reports and analytics about your community', '📊', 6, 1);
*/

-- ============================================
-- After running this SQL:
-- ============================================
-- 1. Go to Admin Dashboard
-- 2. Click "Manage Features"
-- 3. You should see a beautiful feature card with:
--    - 🎟️ emoji icon (not /images/features/lottery.svg)
--    - "Lottery System" as the name
--    - "lottery_system" as the key
--    - Full description (not "- -")
--    - Green border if enabled
--    - Enable/Disable button
-- ============================================
