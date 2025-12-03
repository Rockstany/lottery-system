/**
 * Deletion Requests System
 * Allows group admins to request deletion of lottery events and transactions
 * Super admin can approve/reject these requests
 */

-- Table for deletion requests
CREATE TABLE IF NOT EXISTS `deletion_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_type` ENUM('lottery_event', 'transaction') NOT NULL,
  `item_id` INT NOT NULL COMMENT 'ID of the lottery event or transaction',
  `item_name` VARCHAR(255) NOT NULL COMMENT 'Name/description of item to delete',
  `requested_by` INT NOT NULL COMMENT 'User ID who requested deletion',
  `reason` TEXT NOT NULL COMMENT 'Reason for deletion request',
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT NULL COMMENT 'Admin user ID who reviewed',
  `review_notes` TEXT NULL COMMENT 'Admin notes for approval/rejection',
  `reviewed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (`status`),
  INDEX idx_request_type (`request_type`),
  INDEX idx_requested_by (`requested_by`),
  INDEX idx_reviewed_by (`reviewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log types will be stored directly in activity_logs.action_type column
-- These action types will be used:
-- 'deletion_requested' - When group admin submits deletion request
-- 'deletion_approved' - When super admin approves and deletes item
-- 'deletion_rejected' - When super admin rejects deletion request
