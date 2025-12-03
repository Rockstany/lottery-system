-- System Monitoring Tables Migration
-- Phase 1 & 2: Error Logging, Health Monitoring, Alerts
-- Created: December 2025
-- FIXED: Removed foreign key constraints to avoid dependency issues

-- Table 1: System Logs (Error Logging)
CREATE TABLE IF NOT EXISTS `system_logs` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `log_type` ENUM('error', 'warning', 'info', 'security') NOT NULL,
  `severity` ENUM('critical', 'high', 'medium', 'low') NOT NULL DEFAULT 'low',
  `message` TEXT NOT NULL,
  `details` TEXT NULL,
  `file_path` VARCHAR(255) NULL,
  `line_number` INT NULL,
  `user_id` INT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `url` VARCHAR(500) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_type (`log_type`),
  INDEX idx_severity (`severity`),
  INDEX idx_created_at (`created_at`),
  INDEX idx_user_id (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: System Health Metrics
CREATE TABLE IF NOT EXISTS `system_health_metrics` (
  `metric_id` INT AUTO_INCREMENT PRIMARY KEY,
  `metric_name` VARCHAR(100) NOT NULL,
  `metric_value` DECIMAL(10, 2) NOT NULL,
  `metric_unit` VARCHAR(20) NULL COMMENT 'percentage, MB, seconds, etc.',
  `status` ENUM('healthy', 'warning', 'critical') NOT NULL DEFAULT 'healthy',
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_metric_name (`metric_name`),
  INDEX idx_recorded_at (`recorded_at`),
  INDEX idx_status (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 3: Alert Notifications
CREATE TABLE IF NOT EXISTS `alert_notifications` (
  `alert_id` INT AUTO_INCREMENT PRIMARY KEY,
  `alert_type` ENUM('critical_error', 'security_threat', 'system_down', 'disk_space', 'failed_login', 'performance') NOT NULL,
  `alert_title` VARCHAR(255) NOT NULL,
  `alert_message` TEXT NOT NULL,
  `severity` ENUM('critical', 'high', 'medium', 'low') NOT NULL DEFAULT 'medium',
  `is_read` TINYINT(1) DEFAULT 0,
  `is_resolved` TINYINT(1) DEFAULT 0,
  `notified_via_email` TINYINT(1) DEFAULT 0,
  `notified_at` TIMESTAMP NULL,
  `acknowledged_by` INT NULL,
  `acknowledged_at` TIMESTAMP NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_alert_type (`alert_type`),
  INDEX idx_is_read (`is_read`),
  INDEX idx_is_resolved (`is_resolved`),
  INDEX idx_severity (`severity`),
  INDEX idx_created_at (`created_at`),
  INDEX idx_acknowledged_by (`acknowledged_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 4: Failed Login Attempts (Security Monitoring)
CREATE TABLE IF NOT EXISTS `failed_login_attempts` (
  `attempt_id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (`email`),
  INDEX idx_ip_address (`ip_address`),
  INDEX idx_attempted_at (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 5: Database Connection Status
CREATE TABLE IF NOT EXISTS `database_connection_logs` (
  `connection_id` INT AUTO_INCREMENT PRIMARY KEY,
  `connection_status` ENUM('success', 'failed', 'timeout') NOT NULL,
  `response_time` DECIMAL(8, 3) NULL COMMENT 'in milliseconds',
  `error_message` TEXT NULL,
  `checked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_connection_status (`connection_status`),
  INDEX idx_checked_at (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial health metrics
INSERT INTO `system_health_metrics` (`metric_name`, `metric_value`, `metric_unit`, `status`) VALUES
('disk_space_used', 0, 'percentage', 'healthy'),
('database_status', 1, 'boolean', 'healthy'),
('avg_response_time', 0, 'seconds', 'healthy')
ON DUPLICATE KEY UPDATE metric_value = metric_value;
