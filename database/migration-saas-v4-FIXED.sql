-- ============================================================================
-- GetToKnow SAAS Platform Migration Script v4.0 - FIXED v2
-- Migration to Feature-based SAAS Architecture
-- Date: 2026-01-11
-- ============================================================================
--
-- IMPORTANT: This script is designed to work with your existing database
-- It will NOT drop the existing 'features' table
-- Fixed to match your UNSIGNED INT column types
-- ============================================================================

-- STEP 1: Create New SAAS Platform Tables (if not exists)
-- ============================================================================

-- Community Features (Enable/Disable per community)
CREATE TABLE IF NOT EXISTS `community_features` (
    `id` INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `community_id` INT(10) UNSIGNED NOT NULL,
    `feature_id` INT(11) NOT NULL,
    `is_enabled` TINYINT(1) DEFAULT 0,
    `enabled_date` TIMESTAMP NULL,
    `disabled_date` TIMESTAMP NULL,
    `enabled_by` INT(10) UNSIGNED NULL,
    `disabled_by` INT(10) UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`community_id`) REFERENCES `communities`(`community_id`) ON DELETE CASCADE,
    FOREIGN KEY (`feature_id`) REFERENCES `features`(`feature_id`) ON DELETE CASCADE,
    FOREIGN KEY (`enabled_by`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`disabled_by`) REFERENCES `users`(`user_id`),
    UNIQUE KEY `unique_community_feature` (`community_id`, `feature_id`),
    INDEX `idx_community` (`community_id`),
    INDEX `idx_feature` (`feature_id`),
    INDEX `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_id` INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `description` TEXT,
    `is_editable` TINYINT(1) DEFAULT 1,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(10) UNSIGNED NULL,

    FOREIGN KEY (`updated_by`) REFERENCES `users`(`user_id`),
    INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Community Settings Table
CREATE TABLE IF NOT EXISTS `community_settings` (
    `id` INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `community_id` INT(10) UNSIGNED NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT(10) UNSIGNED NULL,

    FOREIGN KEY (`community_id`) REFERENCES `communities`(`community_id`) ON DELETE CASCADE,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`user_id`),
    UNIQUE KEY `unique_community_setting` (`community_id`, `setting_key`),
    INDEX `idx_community` (`community_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STEP 2: Update Existing Tables (Add columns if needed)
-- ============================================================================

-- Add feature_key to activity_logs for better tracking (if not exists)
SET @column_exists = (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'activity_logs'
    AND COLUMN_NAME = 'feature_key');

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `activity_logs` ADD COLUMN `feature_key` VARCHAR(50) NULL AFTER `action_description`, ADD INDEX `idx_feature_key` (`feature_key`)',
    'SELECT "Column feature_key already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- STEP 3: Insert Default Platform Features (if not already inserted)
-- ============================================================================

-- Check if lottery_system feature already exists
INSERT INTO `features` (`feature_name`, `feature_key`, `feature_description`, `feature_icon`, `display_order`, `is_active`)
SELECT 'Lottery System', 'lottery_system', 'Complete 6-part lottery event management with auto-generation, distribution, and payment tracking', '/public/images/features/lottery.svg', 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `features` WHERE `feature_key` = 'lottery_system'
);

-- STEP 4: Insert Default System Settings
-- ============================================================================

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`)
VALUES
('platform_name', 'GetToKnow', 'string', 'Platform display name', 1),
('platform_version', '4.0', 'string', 'Current platform version', 0),
('max_communities_per_admin', '1', 'number', 'Maximum communities per Group Admin in Phase 1', 1),
('session_timeout', '30', 'number', 'Session timeout in minutes', 1),
('enable_activity_logs', 'true', 'boolean', 'Enable activity logging', 1)
ON DUPLICATE KEY UPDATE
    `setting_value` = VALUES(`setting_value`),
    `description` = VALUES(`description`);

-- STEP 5: Auto-Enable Lottery System for All Existing Communities
-- ============================================================================

-- Enable Lottery System for all existing communities by default
INSERT INTO `community_features` (`community_id`, `feature_id`, `is_enabled`, `enabled_date`, `enabled_by`)
SELECT
    c.`community_id`,
    f.`feature_id`,
    1,
    NOW(),
    (SELECT `user_id` FROM `users` WHERE `role` = 'admin' LIMIT 1)
FROM `communities` c
CROSS JOIN `features` f
WHERE f.`feature_key` = 'lottery_system'
AND NOT EXISTS (
    SELECT 1 FROM `community_features` cf
    WHERE cf.`community_id` = c.`community_id`
    AND cf.`feature_id` = f.`feature_id`
);

-- STEP 6: Verify Migration
-- ============================================================================

-- Show summary of migration
SELECT 'Migration Summary' as Info;
SELECT COUNT(*) as 'Total Features' FROM `features`;
SELECT COUNT(*) as 'Total Communities' FROM `communities`;
SELECT COUNT(*) as 'Total Community Features Enabled' FROM `community_features` WHERE `is_enabled` = 1;
SELECT COUNT(*) as 'Total System Settings' FROM `system_settings`;

-- ============================================================================
-- Migration Complete!
-- ============================================================================
--
-- CHANGES MADE:
-- 1. ✅ Created community_features table (if not exists)
-- 2. ✅ Created system_settings table (if not exists)
-- 3. ✅ Created community_settings table (if not exists)
-- 4. ✅ Added feature_key column to activity_logs (if not exists)
-- 5. ✅ Inserted Lottery System feature (if not exists)
-- 6. ✅ Auto-enabled Lottery System for all existing communities
-- 7. ✅ Added default system settings
--
-- NEXT STEPS:
-- 1. Test Group Admin dashboard - should show feature cards
-- 2. Test Admin feature management page
-- 3. Test breadcrumb navigation
--
-- ============================================================================
