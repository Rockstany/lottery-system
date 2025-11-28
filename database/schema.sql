-- =====================================================
-- GetToKnow Community App - MySQL Database Schema
-- Version: 1.0
-- Date: 2025-11-28
-- Total Tables: 14
-- =====================================================

-- Drop existing database if exists (CAUTION: Use only in development)
-- DROP DATABASE IF EXISTS gettoknow_db;

-- Create database
CREATE DATABASE IF NOT EXISTS gettoknow_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE gettoknow_db;

-- =====================================================
-- CORE TABLES (4 tables)
-- =====================================================

-- Table 1: Users (Admin & Group Admins)
CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mobile_number VARCHAR(15) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'group_admin') NOT NULL DEFAULT 'group_admin',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_mobile (mobile_number),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: Communities
CREATE TABLE communities (
    community_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_name VARCHAR(150) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(10),
    total_members INT UNSIGNED DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 3: Group Admin Assignments
CREATE TABLE group_admin_assignments (
    assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    community_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_assignment (user_id, community_id),
    INDEX idx_user (user_id),
    INDEX idx_community (community_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 4: Activity Logs
CREATE TABLE activity_logs (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- LOTTERY SYSTEM TABLES (6 tables)
-- =====================================================

-- Table 5: Lottery Events
CREATE TABLE lottery_events (
    event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id INT UNSIGNED NOT NULL,
    event_name VARCHAR(150) NOT NULL,
    event_description TEXT,
    total_books INT UNSIGNED NOT NULL,
    tickets_per_book INT UNSIGNED NOT NULL,
    price_per_ticket DECIMAL(10, 2) NOT NULL,
    first_ticket_number INT UNSIGNED NOT NULL,
    total_tickets INT UNSIGNED GENERATED ALWAYS AS (total_books * tickets_per_book) STORED,
    total_predicted_amount DECIMAL(12, 2) GENERATED ALWAYS AS (total_books * tickets_per_book * price_per_ticket) STORED,
    status ENUM('draft', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_community (community_id),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 6: Lottery Books
CREATE TABLE lottery_books (
    book_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    book_number INT UNSIGNED NOT NULL,
    start_ticket_number INT UNSIGNED NOT NULL,
    end_ticket_number INT UNSIGNED NOT NULL,
    book_status ENUM('available', 'distributed', 'collected') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id) ON DELETE CASCADE,
    UNIQUE KEY unique_book (event_id, book_number),
    INDEX idx_event (event_id),
    INDEX idx_status (book_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 7: Distribution Levels (Wing, Floor, Flat hierarchy)
CREATE TABLE distribution_levels (
    level_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    level_number TINYINT UNSIGNED NOT NULL,
    level_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id) ON DELETE CASCADE,
    UNIQUE KEY unique_level (event_id, level_number),
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 8: Distribution Level Values (A, B, C for Wing)
CREATE TABLE distribution_level_values (
    value_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level_id INT UNSIGNED NOT NULL,
    parent_value_id INT UNSIGNED NULL,
    value_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (level_id) REFERENCES distribution_levels(level_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_value_id) REFERENCES distribution_level_values(value_id) ON DELETE CASCADE,
    INDEX idx_level (level_id),
    INDEX idx_parent (parent_value_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 9: Book Distribution
CREATE TABLE book_distribution (
    distribution_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    member_name VARCHAR(100) NOT NULL,
    mobile_number VARCHAR(15),
    level_1_value_id INT UNSIGNED,
    level_2_value_id INT UNSIGNED,
    level_3_value_id INT UNSIGNED,
    distributed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    distributed_by INT UNSIGNED NOT NULL,
    notes TEXT,
    FOREIGN KEY (book_id) REFERENCES lottery_books(book_id) ON DELETE RESTRICT,
    FOREIGN KEY (level_1_value_id) REFERENCES distribution_level_values(value_id) ON DELETE SET NULL,
    FOREIGN KEY (level_2_value_id) REFERENCES distribution_level_values(value_id) ON DELETE SET NULL,
    FOREIGN KEY (level_3_value_id) REFERENCES distribution_level_values(value_id) ON DELETE SET NULL,
    FOREIGN KEY (distributed_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_book_dist (book_id),
    INDEX idx_book (book_id),
    INDEX idx_member (member_name),
    INDEX idx_mobile (mobile_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 10: Payment Collections (Lottery)
CREATE TABLE payment_collections (
    payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    distribution_id INT UNSIGNED NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'upi', 'other') NOT NULL DEFAULT 'cash',
    payment_date DATE NOT NULL,
    payment_notes TEXT,
    collected_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (distribution_id) REFERENCES book_distribution(distribution_id) ON DELETE RESTRICT,
    FOREIGN KEY (collected_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_distribution (distribution_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_collected_by (collected_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRANSACTION COLLECTION SYSTEM TABLES (4 tables)
-- =====================================================

-- Table 11: Transaction Campaigns
CREATE TABLE transaction_campaigns (
    campaign_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id INT UNSIGNED NOT NULL,
    campaign_name VARCHAR(150) NOT NULL,
    campaign_description TEXT,
    total_members INT UNSIGNED DEFAULT 0,
    total_expected_amount DECIMAL(12, 2) DEFAULT 0.00,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_community (community_id),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 12: Campaign Members
CREATE TABLE campaign_members (
    member_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    member_name VARCHAR(100) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    expected_amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid',
    total_paid DECIMAL(10, 2) DEFAULT 0.00,
    outstanding_amount DECIMAL(10, 2) GENERATED ALWAYS AS (expected_amount - total_paid) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES transaction_campaigns(campaign_id) ON DELETE CASCADE,
    INDEX idx_campaign (campaign_id),
    INDEX idx_mobile (mobile_number),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 13: Payment History (Transaction Collection)
CREATE TABLE payment_history (
    history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'upi', 'other') NOT NULL DEFAULT 'cash',
    confirmation_method ENUM('whatsapp', 'call', 'in_person') NOT NULL DEFAULT 'in_person',
    payment_date DATE NOT NULL,
    payment_notes TEXT,
    recorded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES campaign_members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_member (member_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_recorded_by (recorded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 14: WhatsApp Messages
CREATE TABLE whatsapp_messages (
    message_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    message_content TEXT NOT NULL,
    whatsapp_link VARCHAR(500),
    sent_status ENUM('pending', 'sent') NOT NULL DEFAULT 'pending',
    sent_by INT UNSIGNED NOT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES campaign_members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_member (member_id),
    INDEX idx_sent_status (sent_status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA (For Testing - Remove in Production)
-- =====================================================

-- Insert default admin user
-- Password: admin123 (bcrypt hash - cost 10)
INSERT INTO users (mobile_number, password_hash, full_name, email, role, status)
VALUES
('9999999999', '$2y$10$CZ3qGSfZLNqOd.ZqkqP7V.7p8N5.5a5LR0wKYfOKvqXqQX0rqZ8Li', 'System Admin', 'admin@zatana.in', 'admin', 'active');

-- =====================================================
-- VIEWS (For Easy Data Retrieval)
-- =====================================================

-- View: Lottery Event Summary
CREATE VIEW view_lottery_event_summary AS
SELECT
    le.event_id,
    le.event_name,
    c.community_name,
    le.total_books,
    le.total_tickets,
    le.total_predicted_amount,
    COUNT(DISTINCT bd.distribution_id) as books_distributed,
    COALESCE(SUM(pc.amount_paid), 0) as total_collected,
    le.status,
    le.created_at
FROM lottery_events le
LEFT JOIN communities c ON le.community_id = c.community_id
LEFT JOIN lottery_books lb ON le.event_id = lb.event_id
LEFT JOIN book_distribution bd ON lb.book_id = bd.book_id
LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
GROUP BY le.event_id;

-- View: Transaction Campaign Summary
CREATE VIEW view_transaction_campaign_summary AS
SELECT
    tc.campaign_id,
    tc.campaign_name,
    c.community_name,
    tc.total_members,
    tc.total_expected_amount,
    COUNT(CASE WHEN cm.payment_status = 'paid' THEN 1 END) as paid_members,
    COUNT(CASE WHEN cm.payment_status = 'partial' THEN 1 END) as partial_members,
    COUNT(CASE WHEN cm.payment_status = 'unpaid' THEN 1 END) as unpaid_members,
    COALESCE(SUM(cm.total_paid), 0) as total_collected,
    tc.status,
    tc.created_at
FROM transaction_campaigns tc
LEFT JOIN communities c ON tc.community_id = c.community_id
LEFT JOIN campaign_members cm ON tc.campaign_id = cm.campaign_id
GROUP BY tc.campaign_id;

-- =====================================================
-- TRIGGERS (Auto-update calculations)
-- =====================================================

-- Trigger: Update payment status after payment insertion
DELIMITER //
CREATE TRIGGER after_payment_insert
AFTER INSERT ON payment_history
FOR EACH ROW
BEGIN
    DECLARE expected DECIMAL(10,2);
    DECLARE total DECIMAL(10,2);

    SELECT expected_amount INTO expected
    FROM campaign_members
    WHERE member_id = NEW.member_id;

    SELECT SUM(amount_paid) INTO total
    FROM payment_history
    WHERE member_id = NEW.member_id;

    UPDATE campaign_members
    SET
        total_paid = total,
        payment_status = CASE
            WHEN total >= expected THEN 'paid'
            WHEN total > 0 THEN 'partial'
            ELSE 'unpaid'
        END
    WHERE member_id = NEW.member_id;
END//
DELIMITER ;

-- Trigger: Update book status after distribution
DELIMITER //
CREATE TRIGGER after_book_distribution
AFTER INSERT ON book_distribution
FOR EACH ROW
BEGIN
    UPDATE lottery_books
    SET book_status = 'distributed'
    WHERE book_id = NEW.book_id;
END//
DELIMITER ;

-- =====================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- =====================================================
-- Additional composite indexes for common queries

CREATE INDEX idx_event_status ON lottery_events(community_id, status);
CREATE INDEX idx_campaign_status ON transaction_campaigns(community_id, status);
CREATE INDEX idx_payment_date_range ON payment_collections(payment_date, collected_by);
CREATE INDEX idx_payment_history_date ON payment_history(payment_date, recorded_by);

-- =====================================================
-- DATABASE SCHEMA COMPLETE
-- =====================================================
-- Total Tables: 14
-- Total Views: 2
-- Total Triggers: 2
-- Ready for application integration
-- =====================================================
