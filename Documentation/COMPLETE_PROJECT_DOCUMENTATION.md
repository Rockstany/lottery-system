# 🎯 GetToKnow - Complete Project Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Database Structure](#database-structure)
4. [File Structure](#file-structure)
5. [Core Features](#core-features)
6. [User Roles & Permissions](#user-roles--permissions)
7. [SAAS Multi-Tenant Architecture](#saas-multi-tenant-architecture)
8. [Feature Management System](#feature-management-system)
9. [Adding New Features](#adding-new-features)
10. [Code Standards & Conventions](#code-standards--conventions)
11. [Security Guidelines](#security-guidelines)
12. [Deployment Guide](#deployment-guide)
13. [Troubleshooting](#troubleshooting)
14. [Future Roadmap](#future-roadmap)
15. [Rules & Regulations](#rules--regulations)

---

# Project Overview

## What is GetToKnow?

**GetToKnow** is a **SAAS (Software as a Service) multi-tenant community management platform** that enables communities to manage various features through a modular, scalable architecture.

### Key Characteristics:
- **Multi-Tenant**: One platform serves multiple communities
- **Feature-Based Access Control**: Admin enables/disables features per community
- **Dashboard-Centric UI**: Features appear as cards, not navbar links
- **Modular Architecture**: Easy to add new features without affecting existing ones
- **Role-Based Access**: Admin, Group Admin, and Member roles

### Current Version: 4.0 (SAAS Architecture)

---

# System Architecture

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    GetToKnow Platform                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │    ADMIN     │  │ GROUP ADMIN  │  │   MEMBERS    │    │
│  │              │  │              │  │              │    │
│  │ - Manage     │  │ - See only   │  │ - Access     │    │
│  │   Users      │  │   enabled    │  │   features   │    │
│  │ - Enable/    │  │   features   │  │   through    │    │
│  │   Disable    │  │ - Manage     │  │   Group      │    │
│  │   Features   │  │   their      │  │   Admin      │    │
│  │ - Manage     │  │   community  │  │              │    │
│  │   Commun.    │  │              │  │              │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                 Feature Access Control Layer                │
│              (config/feature-access.php)                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │   Lottery    │  │    Event     │  │    Member    │    │
│  │   System     │  │  Management  │  │   Directory  │    │
│  │  (Enabled)   │  │  (Future)    │  │   (Future)   │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                      Database Layer                         │
│                   (MySQL/MariaDB)                           │
└─────────────────────────────────────────────────────────────┘
```

## Technology Stack

| Layer | Technology | Version | Purpose |
|-------|------------|---------|---------|
| **Backend** | PHP | 7.4+ | Server-side logic |
| **Database** | MySQL/MariaDB | 5.7+ | Data storage |
| **Frontend** | HTML5, CSS3, JavaScript | - | User interface |
| **Session** | PHP Sessions | - | Authentication |
| **Architecture** | MVC Pattern | - | Code organization |

## Key Design Principles

1. **Separation of Concerns**: Database, Business Logic, Presentation
2. **DRY (Don't Repeat Yourself)**: Reusable components and classes
3. **SOLID Principles**: Single responsibility, modular code
4. **Security First**: Prepared statements, input validation, role checks
5. **Scalability**: Modular feature system allows infinite features

---

# Database Structure

## Core Tables Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    DATABASE SCHEMA                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  USER MANAGEMENT                                            │
│  ┌──────────┐     ┌──────────────┐                        │
│  │  users   │────▶│ communities  │                        │
│  └──────────┘     └──────────────┘                        │
│                                                             │
│  FEATURE MANAGEMENT (SAAS Core)                            │
│  ┌──────────┐     ┌──────────────────┐                    │
│  │ features │────▶│community_features│                    │
│  └──────────┘     └──────────────────┘                    │
│                                                             │
│  LOTTERY SYSTEM                                             │
│  ┌────────────────┐     ┌─────────────┐                   │
│  │ lottery_events │────▶│   tickets   │                   │
│  └────────────────┘     └─────────────┘                   │
│                                                             │
│  SETTINGS                                                   │
│  ┌────────────────┐     ┌──────────────────┐              │
│  │system_settings │     │community_settings│              │
│  └────────────────┘     └──────────────────┘              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Critical Tables (SAAS Architecture)

### 1. `users` - User Management
```sql
CREATE TABLE users (
    user_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_id INT(10) UNSIGNED DEFAULT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'group_admin', 'member') DEFAULT 'member',
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id)
);
```

**Roles:**
- `admin` - Super Admin (manages entire platform)
- `group_admin` - Community Admin (manages one community)
- `member` - Community Member (uses features)

### 2. `communities` - Multi-Tenant Communities
```sql
CREATE TABLE communities (
    community_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_name VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT(10) UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

### 3. `features` - Master Feature List
```sql
CREATE TABLE features (
    feature_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    feature_name VARCHAR(100) NOT NULL,
    feature_key VARCHAR(50) UNIQUE NOT NULL,
    feature_description TEXT,
    feature_icon VARCHAR(255),  -- Use EMOJI not file paths
    display_order INT(11) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Current Features:**
```sql
INSERT INTO features (feature_name, feature_key, feature_description, feature_icon, display_order)
VALUES ('Lottery System', 'lottery_system',
        'Complete 6-part lottery event management with auto-generation, distribution, and payment tracking',
        '🎟️', 1);
```

### 4. `community_features` - Feature Access Control (MOST IMPORTANT)
```sql
CREATE TABLE community_features (
    id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_id INT(10) UNSIGNED NOT NULL,
    feature_id INT(11) NOT NULL,
    is_enabled TINYINT(1) DEFAULT 0,
    enabled_date TIMESTAMP NULL,
    disabled_date TIMESTAMP NULL,
    enabled_by INT(10) UNSIGNED NULL,
    disabled_by INT(10) UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES features(feature_id) ON DELETE CASCADE,
    FOREIGN KEY (enabled_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (disabled_by) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY unique_community_feature (community_id, feature_id)
);
```

**This is the HEART of the SAAS system!**
- Each row represents a feature's status for a specific community
- `is_enabled = 1` → Feature appears on Group Admin dashboard
- `is_enabled = 0` → Feature hidden from Group Admin

### 5. `system_settings` - Global Platform Settings
```sql
CREATE TABLE system_settings (
    setting_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 6. `community_settings` - Per-Community Settings
```sql
CREATE TABLE community_settings (
    setting_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_id INT(10) UNSIGNED NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE,
    UNIQUE KEY unique_community_setting (community_id, setting_key)
);
```

## Lottery System Tables (Feature-Specific)

### 7. `lottery_events` - Lottery Events
```sql
CREATE TABLE lottery_events (
    event_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_id INT(10) UNSIGNED NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE,
    total_tickets INT(11),
    ticket_price DECIMAL(10,2),
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT(10) UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE
);
```

### 8. `tickets` - Lottery Tickets
```sql
CREATE TABLE tickets (
    ticket_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    event_id INT(10) UNSIGNED NOT NULL,
    member_id INT(10) UNSIGNED,
    ticket_number VARCHAR(50),
    part_number INT(11),
    is_allocated TINYINT(1) DEFAULT 0,
    is_winner TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES lottery_events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES users(user_id) ON DELETE SET NULL
);
```

## Database Relationships

```
users
  ├─ community_id → communities
  └─ role (admin, group_admin, member)

communities
  └─ created_by → users

features (Master list of all available features)
  └─ feature_key (unique identifier)

community_features (Junction table - CONTROLS FEATURE ACCESS)
  ├─ community_id → communities
  ├─ feature_id → features
  ├─ is_enabled (1 = enabled, 0 = disabled)
  ├─ enabled_by → users
  └─ disabled_by → users

lottery_events
  ├─ community_id → communities
  └─ created_by → users

tickets
  ├─ event_id → lottery_events
  └─ member_id → users
```

---

# File Structure

## Complete Directory Tree

```
lottery-system/
│
├── config/                          # Configuration files
│   ├── config.php                   # Main config (DB, constants, autoload)
│   ├── database.php                 # Database connection class
│   ├── auth-middleware.php          # Authentication & role checking
│   └── feature-access.php           # ⭐ Feature access control system
│
├── database/                        # Database files
│   ├── Current Scheme.sql           # Current database schema
│   └── [migration files]            # Database migrations
│
├── public/                          # Publicly accessible files
│   │
│   ├── index.php                    # Landing page / Login redirect
│   ├── login.php                    # Login page
│   ├── logout.php                   # Logout handler
│   │
│   ├── admin/                       # Admin panel
│   │   ├── dashboard.php            # ⭐ Admin dashboard
│   │   ├── community-features.php   # ⭐ Feature management UI
│   │   ├── users.php                # User management
│   │   ├── user-add.php             # Add new user
│   │   ├── user-edit.php            # Edit user
│   │   ├── communities.php          # Community management
│   │   ├── community-add.php        # Add community
│   │   ├── community-edit.php       # Edit community
│   │   ├── system-health.php        # System health check
│   │   ├── deletion-requests.php    # Handle deletion requests
│   │   └── change-password.php      # Change password
│   │
│   ├── group-admin/                 # Group Admin panel
│   │   ├── dashboard.php            # ⭐ Group Admin dashboard (feature cards)
│   │   ├── lottery.php              # Lottery system main page
│   │   ├── lottery-create.php       # Create lottery event
│   │   ├── lottery-edit.php         # Edit lottery event
│   │   ├── lottery-view.php         # View lottery details
│   │   ├── lottery-distribute.php   # Distribute tickets
│   │   ├── lottery-draw.php         # Conduct lottery draw
│   │   ├── change-password.php      # Change password
│   │   └── includes/
│   │       ├── navigation.php       # Navigation menu
│   │       └── footer.php           # Footer
│   │
│   ├── member/                      # Member portal (future)
│   │   └── dashboard.php
│   │
│   ├── includes/                    # Shared components
│   │   └── breadcrumb.php           # ⭐ Breadcrumb navigation
│   │
│   ├── css/                         # Stylesheets
│   │   ├── main.css                 # Main stylesheet
│   │   └── enhancements.css         # UI enhancements
│   │
│   ├── js/                          # JavaScript files
│   │   └── main.js
│   │
│   └── images/                      # Image assets
│
├── Documentation/                   # ⭐ Project documentation
│   ├── COMPLETE_PROJECT_DOCUMENTATION.md  # ⭐ THIS FILE
│   ├── HOW_TO_MANAGE_FEATURES.md          # Feature management guide
│   ├── FEATURE_MODULE_TEMPLATE.md         # Template for new features
│   ├── SAAS_PLATFORM_ARCHITECTURE.md      # Architecture details
│   ├── DATABASE_SCHEMA_SAAS.md            # Database documentation
│   └── [other docs]
│
├── FIX_ALL_FEATURES.sql             # SQL fix for feature icons
└── README.md                        # Project README

```

## Key File Descriptions

### Configuration Files

**`config/config.php`** - Main Configuration
```php
<?php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'u717011923_gettoknow_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application constants
define('APP_NAME', 'GetToKnow');
define('APP_VERSION', '4.0');
define('BASE_URL', 'http://localhost');

// Session configuration
session_start();

// Autoload classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('_', '-', strtolower($class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
```

**`config/database.php`** - Database Connection
```php
<?php
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        return $this->conn;
    }
}
```

**`config/auth-middleware.php`** - Authentication
```php
<?php
class AuthMiddleware {
    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /public/login.php');
            exit();
        }
    }

    public static function requireRole($role) {
        self::requireAuth();
        if ($_SESSION['role'] !== $role) {
            header('Location: /public/login.php');
            exit();
        }
    }

    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function getCommunityId() {
        return $_SESSION['community_id'] ?? null;
    }

    public static function getRole() {
        return $_SESSION['role'] ?? null;
    }
}
```

**`config/feature-access.php`** - ⭐ MOST IMPORTANT FILE
```php
<?php
/**
 * Feature Access Control System
 * This is the HEART of the SAAS architecture
 */
class FeatureAccess {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Check if a feature is enabled for a community
     * @param int $community_id
     * @param string $feature_key
     * @return bool
     */
    public function isFeatureEnabled($community_id, $feature_key) {
        $query = "SELECT COUNT(*) as count
                  FROM community_features cf
                  JOIN features f ON cf.feature_id = f.feature_id
                  WHERE cf.community_id = :community_id
                  AND f.feature_key = :feature_key
                  AND cf.is_enabled = TRUE
                  AND f.is_active = TRUE";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':community_id', $community_id);
        $stmt->bindParam(':feature_key', $feature_key);
        $stmt->execute();

        return $stmt->fetch()['count'] > 0;
    }

    /**
     * Get all enabled features for a community
     * @param int $community_id
     * @return array
     */
    public function getEnabledFeatures($community_id) {
        $query = "SELECT f.feature_id, f.feature_name, f.feature_key,
                         f.feature_description, f.feature_icon, f.display_order
                  FROM features f
                  JOIN community_features cf ON f.feature_id = cf.feature_id
                  WHERE cf.community_id = :community_id
                  AND cf.is_enabled = TRUE
                  AND f.is_active = TRUE
                  ORDER BY f.display_order ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':community_id', $community_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Enable a feature for a community
     * @param int $community_id
     * @param int $feature_id
     * @param int $enabled_by
     * @return bool
     */
    public function enableFeature($community_id, $feature_id, $enabled_by) {
        $query = "INSERT INTO community_features
                  (community_id, feature_id, is_enabled, enabled_by, enabled_date)
                  VALUES (:community_id, :feature_id, TRUE, :enabled_by, NOW())
                  ON DUPLICATE KEY UPDATE
                  is_enabled = TRUE,
                  enabled_by = :enabled_by,
                  enabled_date = NOW()";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':community_id', $community_id);
        $stmt->bindParam(':feature_id', $feature_id);
        $stmt->bindParam(':enabled_by', $enabled_by);

        return $stmt->execute();
    }

    /**
     * Disable a feature for a community
     * @param int $community_id
     * @param int $feature_id
     * @param int $disabled_by
     * @return bool
     */
    public function disableFeature($community_id, $feature_id, $disabled_by) {
        $query = "UPDATE community_features
                  SET is_enabled = FALSE,
                      disabled_by = :disabled_by,
                      disabled_date = NOW()
                  WHERE community_id = :community_id
                  AND feature_id = :feature_id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':community_id', $community_id);
        $stmt->bindParam(':feature_id', $feature_id);
        $stmt->bindParam(':disabled_by', $disabled_by);

        return $stmt->execute();
    }
}
```

---

# Core Features

## Current Features (v4.0)

### 1. Lottery System
**Feature Key:** `lottery_system`
**Status:** ✅ Implemented
**Description:** Complete 6-part lottery event management

**Capabilities:**
1. Create lottery events
2. Generate tickets (6-part system)
3. Distribute tickets to members
4. Track payments
5. Conduct draws
6. Declare winners

**Files:**
- `public/group-admin/lottery.php` - Main page
- `public/group-admin/lottery-create.php` - Create event
- `public/group-admin/lottery-edit.php` - Edit event
- `public/group-admin/lottery-view.php` - View details
- `public/group-admin/lottery-distribute.php` - Distribute tickets
- `public/group-admin/lottery-draw.php` - Conduct draw

**Database Tables:**
- `lottery_events`
- `tickets`
- `payments` (if implemented)

---

## Removed Features

### Transaction Collection Feature
**Status:** ❌ Removed in v4.0
**Reason:** Feature was incomplete and didn't fit SAAS architecture
**Files Deleted:** All transaction-related PHP files

---

# User Roles & Permissions

## Role Hierarchy

```
┌─────────────────────────────────────────┐
│            ADMIN (Super Admin)          │
│  - Full system access                   │
│  - Manage all communities               │
│  - Enable/disable features              │
│  - Create/edit/delete users             │
│  - System-wide settings                 │
└─────────────────────────────────────────┘
              │
              ├─────────────────────────────┐
              │                             │
┌─────────────▼──────────┐   ┌──────────────▼─────────┐
│   GROUP ADMIN          │   │   GROUP ADMIN          │
│   (Community 1)        │   │   (Community 2)        │
│                        │   │                        │
│  - See only enabled    │   │  - See only enabled    │
│    features            │   │    features            │
│  - Manage their        │   │  - Manage their        │
│    community only      │   │    community only      │
│  - Cannot see other    │   │  - Cannot see other    │
│    communities         │   │    communities         │
└────────────────────────┘   └────────────────────────┘
              │                             │
    ┌─────────┴─────────┐       ┌──────────┴──────────┐
    │                   │       │                     │
┌───▼────┐   ┌─────────▼──┐ ┌──▼─────┐   ┌──────────▼──┐
│ MEMBER │   │   MEMBER   │ │ MEMBER │   │   MEMBER    │
│        │   │            │ │        │   │             │
└────────┘   └────────────┘ └────────┘   └─────────────┘
```

## Permission Matrix

| Action | Admin | Group Admin | Member |
|--------|-------|-------------|--------|
| **User Management** |
| Create users | ✅ | ❌ | ❌ |
| Edit users | ✅ | ❌ | ❌ |
| Delete users | ✅ | ❌ | ❌ |
| View all users | ✅ | ❌ | ❌ |
| **Community Management** |
| Create communities | ✅ | ❌ | ❌ |
| Edit communities | ✅ | ❌ | ❌ |
| Delete communities | ✅ | ❌ | ❌ |
| View all communities | ✅ | ❌ | ❌ |
| **Feature Management** |
| Enable/disable features | ✅ | ❌ | ❌ |
| View all features | ✅ | ❌ | ❌ |
| Access enabled features | ❌ | ✅ | ✅ |
| **Lottery System** (if enabled) |
| Create lottery events | ❌ | ✅ | ❌ |
| Edit lottery events | ❌ | ✅ | ❌ |
| Distribute tickets | ❌ | ✅ | ❌ |
| Conduct draws | ❌ | ✅ | ❌ |
| View own tickets | ❌ | ✅ | ✅ |
| **System Settings** |
| Modify system settings | ✅ | ❌ | ❌ |
| View system health | ✅ | ❌ | ❌ |

---

# SAAS Multi-Tenant Architecture

## How It Works

### 1. Feature Registration
Every feature must be registered in the `features` table:

```sql
INSERT INTO features (feature_name, feature_key, feature_description, feature_icon, display_order, is_active)
VALUES ('Lottery System', 'lottery_system',
        'Complete lottery management system',
        '🎟️', 1, 1);
```

### 2. Feature Enablement
Admin enables feature for a specific community in `community_features`:

```sql
INSERT INTO community_features (community_id, feature_id, is_enabled, enabled_by, enabled_date)
VALUES (1, 1, 1, 1, NOW());
```

### 3. Feature Access Check
Group Admin dashboard checks enabled features:

```php
// In public/group-admin/dashboard.php
$featureAccess = new FeatureAccess();
$enabledFeatures = $featureAccess->getEnabledFeatures($communityId);

// Display features as cards
foreach ($enabledFeatures as $feature) {
    // Show feature card with icon, name, description
    // Link to feature page
}
```

### 4. Feature Page Protection
Each feature page checks if it's enabled:

```php
// In public/group-admin/lottery.php
$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'lottery_system')) {
    $_SESSION['error_message'] = "Lottery System is not enabled for your community";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}
```

## Data Isolation

Each community's data is completely isolated:

```php
// Always filter by community_id
$query = "SELECT * FROM lottery_events
          WHERE community_id = :community_id";
```

**CRITICAL RULE:** Every query must filter by `community_id` to ensure data isolation!

## Workflow Diagram

```
┌────────────────────────────────────────────────────────────┐
│  ADMIN: Enable Feature for Community                       │
└───────────────────────┬────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│  Database: community_features table updated                │
│  community_id=1, feature_id=1, is_enabled=1                │
└───────────────────────┬────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│  GROUP ADMIN: Logs in, sees dashboard                      │
└───────────────────────┬────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│  FeatureAccess::getEnabledFeatures($communityId)           │
│  Returns: [Lottery System, ...]                            │
└───────────────────────┬────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│  Dashboard displays feature cards                          │
│  ┌──────────────┐                                          │
│  │   🎟️         │                                          │
│  │ Lottery      │                                          │
│  │ System       │                                          │
│  └──────────────┘                                          │
└───────────────────────┬────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│  GROUP ADMIN: Clicks "Access Feature"                      │
└───────────────────────┬────────────────────────────────────┘
                        │
                        ▼
┌────────────────────────────────────────────────────────────┐
│  Feature page checks: isFeatureEnabled()                   │
│  If enabled: Show feature                                  │
│  If disabled: Redirect to dashboard with error             │
└────────────────────────────────────────────────────────────┘
```

---

# Feature Management System

## For Admin: How to Manage Features

### Step 1: Access Feature Management
1. Login as Admin
2. Go to Admin Dashboard
3. Click **"⚙️ Manage Features"** button (purple gradient)
4. OR scroll to **"Feature Management by Community"** section

### Step 2: Select Community
- Use dropdown at top to select community
- Page shows all features with current status

### Step 3: Enable/Disable Features
- Each feature displays as a card:
  - **Green border** = Enabled
  - **Gray border** = Disabled
- Click **"✓ Enable Feature"** or **"🚫 Disable Feature"**
- Confirm action in dialog

### Step 4: Verify
- Success message appears
- Feature status updates immediately
- Group Admin will see change on their dashboard

## Feature Management UI

**File:** `public/admin/community-features.php`

**Features:**
- Beautiful gradient header
- Community selector dropdown
- Feature cards with icons
- Color-coded status (green/gray)
- Enable/Disable buttons
- Success/error alerts
- Responsive design

---

# Adding New Features

## Complete Step-by-Step Guide

### Step 1: Register Feature in Database

```sql
-- Add feature to master list
INSERT INTO features (feature_name, feature_key, feature_description, feature_icon, display_order, is_active)
VALUES ('Event Management', 'event_management',
        'Organize and manage community events and activities',
        '🎯', 2, 1);
```

**Important:**
- `feature_key` must be unique (use snake_case)
- `feature_icon` must be an emoji (not a file path!)
- `display_order` determines order on dashboard
- `is_active = 1` makes it available to enable

### Step 2: Create Database Tables (if needed)

```sql
-- Example: Events table
CREATE TABLE events (
    event_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_id INT(10) UNSIGNED NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE,
    event_description TEXT,
    created_by INT(10) UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

**CRITICAL:** Always include `community_id` foreign key for data isolation!

### Step 3: Create Feature Files

Create main feature file: `public/group-admin/event-management.php`

```php
<?php
/**
 * Event Management Feature
 * Main page for event management system
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

// Require Group Admin role
AuthMiddleware::requireRole('group_admin');

$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

// ⭐ CRITICAL: Check if feature is enabled
$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'event_management')) {
    $_SESSION['error_message'] = "Event Management is not enabled for your community";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get events for this community ONLY
$query = "SELECT * FROM events
          WHERE community_id = :community_id
          ORDER BY event_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$events = $stmt->fetchAll();

// Breadcrumb
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Event Management', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
</head>
<body>
    <!-- Breadcrumb -->
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <!-- Your feature UI here -->
    <div class="container">
        <h1>Event Management</h1>

        <!-- List events, create button, etc. -->
    </div>
</body>
</html>
```

### Step 4: Update Dashboard Feature Mapping

In `public/group-admin/dashboard.php`, add feature URL mapping:

```php
// Around line 395-402
foreach ($enabledFeatures as $feature):
    $featureUrl = '/public/group-admin/dashboard.php'; // Default
    $featureIcon = '🎯'; // Default

    if ($feature['feature_key'] === 'lottery_system') {
        $featureUrl = '/public/group-admin/lottery.php';
        $featureIcon = '🎟️';
    } elseif ($feature['feature_key'] === 'event_management') {
        $featureUrl = '/public/group-admin/event-management.php';
        $featureIcon = '🎯';
    }
    // Add more features here
?>
```

### Step 5: Enable Feature for Testing

Run SQL to enable for your test community:

```sql
INSERT INTO community_features (community_id, feature_id, is_enabled, enabled_by, enabled_date)
VALUES (1, 2, 1, 1, NOW());
```

Or use the Feature Management UI.

### Step 6: Test

1. Login as Group Admin
2. Check dashboard - new feature card should appear
3. Click feature card
4. Verify feature page loads
5. Test all functionality
6. Verify data isolation (can't see other communities' data)

## Feature Checklist

✅ Feature registered in `features` table
✅ Database tables created with `community_id` foreign key
✅ Main feature file created in `public/group-admin/`
✅ Feature access check implemented (`isFeatureEnabled()`)
✅ All queries filter by `community_id`
✅ Feature URL mapped in dashboard
✅ Breadcrumb navigation added
✅ Feature enabled for test community
✅ Tested as Group Admin
✅ Data isolation verified

---

# Code Standards & Conventions

## Naming Conventions

### Files
- Use kebab-case: `lottery-create.php`, `community-features.php`
- Feature files: `{feature-name}.php`
- Action files: `{feature-name}-{action}.php` (e.g., `lottery-create.php`)

### Database
- **Tables:** snake_case, plural: `lottery_events`, `community_features`
- **Columns:** snake_case: `community_id`, `created_at`
- **Foreign keys:** `{table_singular}_id` (e.g., `community_id`, `user_id`)

### PHP
- **Classes:** PascalCase: `FeatureAccess`, `AuthMiddleware`
- **Functions:** camelCase: `getEnabledFeatures()`, `isFeatureEnabled()`
- **Variables:** camelCase: `$communityId`, `$featureAccess`
- **Constants:** SCREAMING_SNAKE_CASE: `APP_NAME`, `DB_HOST`

### Feature Keys
- Use snake_case: `lottery_system`, `event_management`
- Must be unique
- Used in code to reference features

## Code Structure

### PHP File Template

```php
<?php
/**
 * File Description
 * Purpose of this file
 */

// 1. Require dependencies
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

// 2. Authentication & authorization
AuthMiddleware::requireRole('group_admin');

// 3. Get session variables
$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

// 4. Feature access check (if feature-specific)
$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'feature_key')) {
    $_SESSION['error_message'] = "Feature not enabled";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}

// 5. Database connection
$database = new Database();
$db = $database->getConnection();

// 6. Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    // Process data
    // Redirect with message
}

// 7. Get data for display
$query = "SELECT ... WHERE community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$data = $stmt->fetchAll();

// 8. Breadcrumb setup
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Current Page', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Title - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="container">
        <!-- Page content -->
    </div>
</body>
</html>
```

## Database Query Standards

### Always Use Prepared Statements

```php
// ✅ CORRECT - Prepared statement
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$user = $stmt->fetch();

// ❌ WRONG - SQL injection risk!
$query = "SELECT * FROM users WHERE user_id = $userId";
$result = $db->query($query);
```

### Always Filter by community_id

```php
// ✅ CORRECT - Data isolation
$query = "SELECT * FROM lottery_events
          WHERE community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();

// ❌ WRONG - Can see all communities' data!
$query = "SELECT * FROM lottery_events";
$stmt = $db->query($query);
```

### Use Transactions for Multi-Step Operations

```php
try {
    $db->beginTransaction();

    // Step 1: Insert event
    $query = "INSERT INTO lottery_events (community_id, event_name)
              VALUES (:cid, :name)";
    $stmt = $db->prepare($query);
    $stmt->execute([':cid' => $communityId, ':name' => $eventName]);
    $eventId = $db->lastInsertId();

    // Step 2: Generate tickets
    $query = "INSERT INTO tickets (event_id, ticket_number)
              VALUES (:eid, :num)";
    $stmt = $db->prepare($query);
    for ($i = 1; $i <= 1000; $i++) {
        $stmt->execute([':eid' => $eventId, ':num' => $i]);
    }

    $db->commit();
    $_SESSION['success_message'] = "Event created successfully";
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error_message'] = "Failed to create event: " . $e->getMessage();
}
```

## HTML/CSS Standards

### Use CSS Variables

```css
:root {
    --primary-color: #667eea;
    --success-color: #2ecc71;
    --danger-color: #e74c3c;
    --gray-100: #f8f9fa;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --radius-md: 8px;
}
```

### Responsive Design

```css
/* Mobile-first approach */
.container {
    padding: var(--spacing-md);
}

/* Tablet */
@media (min-width: 768px) {
    .container {
        padding: var(--spacing-lg);
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
}
```

---

# Security Guidelines

## Authentication & Authorization

### 1. Always Check Authentication

```php
// At the top of every protected page
AuthMiddleware::requireAuth();
```

### 2. Always Check Role

```php
// For admin pages
AuthMiddleware::requireRole('admin');

// For group admin pages
AuthMiddleware::requireRole('group_admin');
```

### 3. Always Check Feature Access

```php
// For feature pages
$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'feature_key')) {
    header('Location: /public/group-admin/dashboard.php');
    exit();
}
```

## Input Validation

### 1. Validate All User Input

```php
// Integer validation
$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
if ($userId <= 0) {
    $_SESSION['error_message'] = "Invalid user ID";
    header('Location: previous-page.php');
    exit();
}

// String validation
$eventName = isset($_POST['event_name']) ? trim($_POST['event_name']) : '';
if (empty($eventName)) {
    $_SESSION['error_message'] = "Event name is required";
    header('Location: previous-page.php');
    exit();
}

// Email validation
$email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) : false;
if (!$email) {
    $_SESSION['error_message'] = "Invalid email address";
    header('Location: previous-page.php');
    exit();
}
```

### 2. Sanitize Output

```php
// Always escape HTML
<h1><?php echo htmlspecialchars($eventName); ?></h1>

// For attributes
<input type="text" value="<?php echo htmlspecialchars($value, ENT_QUOTES); ?>">
```

## Password Security

### Hashing Passwords

```php
// When creating user
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$query = "INSERT INTO users (email, password_hash) VALUES (:email, :hash)";
$stmt = $db->prepare($query);
$stmt->execute([':email' => $email, ':hash' => $passwordHash]);
```

### Verifying Passwords

```php
// When logging in
$query = "SELECT user_id, password_hash, role FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    header('Location: /public/admin/dashboard.php');
} else {
    $_SESSION['error_message'] = "Invalid credentials";
}
```

## SQL Injection Prevention

### ALWAYS Use Prepared Statements

```php
// ✅ CORRECT
$query = "SELECT * FROM users WHERE email = :email AND status = :status";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':status', $status);
$stmt->execute();

// ❌ WRONG - SQL injection vulnerability!
$query = "SELECT * FROM users WHERE email = '$email' AND status = '$status'";
$result = $db->query($query);
```

## XSS Prevention

```php
// ✅ CORRECT - Escape output
echo htmlspecialchars($userInput);

// ❌ WRONG - XSS vulnerability!
echo $userInput;
```

## CSRF Protection (Future Enhancement)

```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In form
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Verify token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

---

# Deployment Guide

## Server Requirements

- **PHP:** 7.4 or higher
- **MySQL/MariaDB:** 5.7 or higher
- **Web Server:** Apache with mod_rewrite OR Nginx
- **PHP Extensions:** PDO, PDO_MySQL, mbstring, openssl

## Deployment Steps

### 1. Upload Files

Upload entire project to server:
```
/home/u717011923/domains/zatana.in/public_html/
```

### 2. Configure Database

Edit `config/config.php`:

```php
define('DB_HOST', 'your-db-host');
define('DB_NAME', 'u717011923_gettoknow_db');
define('DB_USER', 'your-db-user');
define('DB_PASS', 'your-db-password');
define('BASE_URL', 'https://zatana.in');
```

### 3. Import Database

1. Login to phpMyAdmin
2. Select database
3. Import `database/Current Scheme.sql`
4. Run `FIX_ALL_FEATURES.sql` to fix feature icons

### 4. Set File Permissions

```bash
chmod 755 public/
chmod 644 config/config.php
```

### 5. Create Admin User

```sql
INSERT INTO users (full_name, email, password_hash, role, status)
VALUES ('Admin', 'admin@zatana.in',
        '$2y$10$hashedpasswordhere',
        'admin', 'active');
```

Generate password hash:
```php
php -r "echo password_hash('your-password', PASSWORD_BCRYPT);"
```

### 6. Test

1. Navigate to `https://zatana.in/public/login.php`
2. Login as admin
3. Create test community
4. Enable features
5. Test as Group Admin

## Apache .htaccess (Optional)

Create `.htaccess` in root:

```apache
RewriteEngine On
RewriteBase /

# Redirect to public folder
RewriteRule ^$ public/ [L]
RewriteRule (.*) public/$1 [L]
```

---

# Troubleshooting

## Common Issues

### 1. Database Connection Error

**Error:** `Connection Error: SQLSTATE[HY000] [2002]`

**Solution:**
- Check `config/config.php` database credentials
- Verify database server is running
- Check if database exists
- Verify user has permissions

### 2. Feature Icon Shows File Path

**Error:** Feature card shows `/images/features/lottery.svg` instead of emoji

**Solution:**
Run this SQL:
```sql
UPDATE features
SET feature_icon = '🎟️'
WHERE feature_key = 'lottery_system';
```

See: `FIX_ALL_FEATURES.sql`

### 3. Feature Description Shows "- -"

**Error:** Feature description is empty

**Solution:**
```sql
UPDATE features
SET feature_description = 'Complete 6-part lottery event management...'
WHERE feature_key = 'lottery_system';
```

### 4. Can't Enable/Disable Features

**Problem:** Toggle doesn't work in Feature Management

**Solutions:**
1. Check JavaScript console for errors
2. Verify database permissions
3. Check if `community_features` table exists
4. Try direct URL: `/public/admin/community-features.php?community_id=1`

### 5. Group Admin Sees No Features

**Problem:** Dashboard shows "No Features Enabled"

**Solutions:**
1. Login as Admin
2. Go to Feature Management
3. Enable features for that community
4. Verify `community_features` table has entry with `is_enabled = 1`
5. Check Group Admin is assigned to correct community

### 6. Foreign Key Constraint Error

**Error:** `errno: 150 "Foreign key constraint is incorrectly formed"`

**Solution:**
Ensure data types match:
```sql
-- Parent table
community_id INT(10) UNSIGNED

-- Child table (must match exactly!)
community_id INT(10) UNSIGNED
```

### 7. Session Issues

**Problem:** User keeps getting logged out

**Solutions:**
1. Check `session_start()` is called in `config.php`
2. Verify PHP session directory is writable
3. Check session cookie settings
4. Increase `session.gc_maxlifetime` in php.ini

---

# Future Roadmap

## Planned Features (v5.0+)

### 1. Event Management System
**Priority:** High
**Description:** Manage community events, RSVPs, calendars

**Database:**
```sql
CREATE TABLE events (
    event_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_id INT(10) UNSIGNED NOT NULL,
    event_name VARCHAR(255),
    event_date DATETIME,
    location VARCHAR(255),
    description TEXT,
    max_attendees INT(11),
    created_by INT(10) UNSIGNED,
    FOREIGN KEY (community_id) REFERENCES communities(community_id)
);

CREATE TABLE event_registrations (
    registration_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    event_id INT(10) UNSIGNED NOT NULL,
    member_id INT(10) UNSIGNED NOT NULL,
    status ENUM('registered', 'attended', 'cancelled'),
    FOREIGN KEY (event_id) REFERENCES events(event_id),
    FOREIGN KEY (member_id) REFERENCES users(user_id)
);
```

### 2. Member Directory
**Priority:** Medium
**Description:** Searchable member directory with profiles

**Database:**
```sql
CREATE TABLE member_profiles (
    profile_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT(10) UNSIGNED NOT NULL,
    bio TEXT,
    interests TEXT,
    profile_picture VARCHAR(255),
    is_public TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

### 3. Announcements System
**Priority:** High
**Description:** Post announcements to community members

**Database:**
```sql
CREATE TABLE announcements (
    announcement_id INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    community_id INT(10) UNSIGNED NOT NULL,
    title VARCHAR(255),
    content TEXT,
    is_urgent TINYINT(1) DEFAULT 0,
    created_by INT(10) UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(community_id)
);
```

### 4. Payment Tracking
**Priority:** Medium
**Description:** Track member payments and dues

### 5. Reports & Analytics
**Priority:** Low
**Description:** Dashboard with charts and statistics

### 6. Mobile App
**Priority:** Future
**Description:** Native mobile app for iOS and Android

## Technical Improvements

### 1. API Layer
Create RESTful API for mobile/third-party integrations

```
GET  /api/communities/{id}
POST /api/features/{id}/enable
GET  /api/lottery-events
```

### 2. Email Notifications
Integrate email service for notifications

### 3. File Uploads
Member profile pictures, event photos

### 4. Multi-Language Support
Internationalization (i18n)

### 5. Advanced Permissions
Granular permission system beyond role-based

### 6. Audit Log
Track all admin actions for compliance

---

# Rules & Regulations

## Development Rules

### CRITICAL - Must Follow

1. **NEVER skip feature access checks**
   ```php
   // ALWAYS include this in feature pages
   if (!$featureAccess->isFeatureEnabled($communityId, 'feature_key')) {
       header('Location: /public/group-admin/dashboard.php');
       exit();
   }
   ```

2. **ALWAYS filter by community_id**
   ```php
   // Every query MUST include community_id filter
   WHERE community_id = :community_id
   ```

3. **ALWAYS use prepared statements**
   - NEVER concatenate user input into SQL queries
   - ALWAYS use `bindParam()` or execute with array

4. **ALWAYS validate and sanitize input**
   - Validate on server-side (never trust client)
   - Use `htmlspecialchars()` for output
   - Use `intval()`, `filter_var()`, etc.

5. **ALWAYS check authentication**
   ```php
   AuthMiddleware::requireRole('group_admin');
   ```

6. **Feature icons MUST be emojis**
   - NOT file paths like `/images/features/icon.svg`
   - Use emoji characters: 🎟️ 🎯 👥 📢

7. **Foreign keys MUST match data types exactly**
   ```sql
   -- Parent
   community_id INT(10) UNSIGNED

   -- Child (MUST be identical!)
   community_id INT(10) UNSIGNED
   ```

## Architecture Rules

### Feature Development

1. **Every feature MUST be registered in `features` table**
   - No hardcoded features allowed

2. **Every feature MUST have a unique `feature_key`**
   - Use snake_case
   - Never change after deployment

3. **Every feature MUST check if enabled**
   - Use `FeatureAccess::isFeatureEnabled()`

4. **Every feature MUST have its own file(s)**
   - Don't mix features in one file

5. **Every feature's data MUST be isolated by community**
   - Include `community_id` in all tables
   - Filter all queries by `community_id`

### Database Rules

1. **Every table MUST have a primary key**
   - Use `AUTO_INCREMENT`

2. **Every table with community data MUST have `community_id`**
   - With FOREIGN KEY constraint
   - ON DELETE CASCADE or SET NULL

3. **Every table MUST have timestamps**
   ```sql
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   ```

4. **Every foreign key MUST have index**
   - MySQL creates index automatically on FK

5. **Use ENUM for fixed options**
   ```sql
   status ENUM('active', 'inactive', 'pending')
   ```

### Security Rules

1. **NEVER store passwords in plain text**
   - Use `password_hash()` and `password_verify()`

2. **NEVER trust user input**
   - Validate everything
   - Sanitize output

3. **NEVER expose sensitive data in errors**
   - Log errors server-side
   - Show generic message to user

4. **ALWAYS use HTTPS in production**
   - No exceptions

5. **ALWAYS implement CSRF protection**
   - For all forms (future enhancement)

### UI/UX Rules

1. **Features MUST appear as cards on dashboard**
   - NOT as navbar links

2. **Every feature page MUST have breadcrumb**
   ```php
   include __DIR__ . '/../includes/breadcrumb.php';
   ```

3. **Every action MUST show feedback**
   - Success message (green)
   - Error message (red)

4. **Mobile-first responsive design**
   - Test on mobile, tablet, desktop

5. **Consistent color scheme**
   - Use CSS variables
   - Follow existing design

## Testing Rules

### Before Committing

1. **Test all user roles**
   - Admin
   - Group Admin
   - Member (future)

2. **Test data isolation**
   - Create 2 communities
   - Verify Community 1 can't see Community 2 data

3. **Test feature enable/disable**
   - Enable feature → appears on dashboard
   - Disable feature → disappears from dashboard
   - Direct URL access → redirects if disabled

4. **Test on multiple communities**
   - Feature works for Community A
   - Feature works for Community B
   - Data doesn't leak between communities

5. **Check for SQL injection**
   - Try `' OR '1'='1` in inputs
   - Should be blocked by prepared statements

## Documentation Rules

1. **Document every new feature**
   - Update this file
   - Add to FEATURE_MODULE_TEMPLATE.md

2. **Document every database change**
   - Update DATABASE_SCHEMA_SAAS.md
   - Create migration SQL file

3. **Document every API change**
   - If API added in future

4. **Keep README.md updated**
   - Version numbers
   - Feature list

## Git/Version Control Rules

1. **Commit messages format:**
   ```
   feat: Add event management feature
   fix: Fix feature icon display
   docs: Update project documentation
   refactor: Restructure feature access code
   ```

2. **NEVER commit sensitive data**
   - No passwords
   - No API keys
   - No database credentials

3. **Create feature branches**
   ```
   git checkout -b feature/event-management
   ```

4. **Test before merging to main**

## Performance Rules

1. **Use pagination for large datasets**
   ```sql
   LIMIT 50 OFFSET 0
   ```

2. **Index frequently queried columns**
   ```sql
   CREATE INDEX idx_community_id ON events(community_id);
   ```

3. **Cache static data**
   - Feature list
   - System settings

4. **Optimize images**
   - Compress before upload
   - Use appropriate formats

## Deployment Rules

1. **NEVER deploy directly to production**
   - Test on staging first

2. **ALWAYS backup database before changes**
   ```bash
   mysqldump database > backup.sql
   ```

3. **ALWAYS test migrations**
   - On copy of production database

4. **Document deployment steps**
   - In DEPLOYMENT.md

5. **Monitor after deployment**
   - Check error logs
   - Monitor performance

---

# Quick Reference

## Important File Locations

```
config/feature-access.php          - Feature access control (CRITICAL)
config/auth-middleware.php         - Authentication system
public/admin/community-features.php - Feature management UI
public/group-admin/dashboard.php   - Group Admin dashboard (feature cards)
Documentation/COMPLETE_PROJECT_DOCUMENTATION.md - THIS FILE
```

## Important Database Tables

```
features               - Master feature list
community_features     - Feature enable/disable status (HEART OF SAAS)
users                  - User accounts
communities            - Multi-tenant communities
```

## Key Classes

```php
FeatureAccess          - Check feature access
AuthMiddleware         - Check authentication/role
Database               - Database connection
```

## Important URLs

```
/public/login.php                              - Login
/public/admin/dashboard.php                    - Admin dashboard
/public/admin/community-features.php?community_id=1  - Feature management
/public/group-admin/dashboard.php              - Group Admin dashboard
```

## Common Commands

```bash
# Generate password hash
php -r "echo password_hash('password', PASSWORD_BCRYPT);"

# Check PHP version
php -v

# Test database connection
php -r "new PDO('mysql:host=localhost;dbname=db', 'user', 'pass');"
```

## SQL Snippets

```sql
-- Add feature
INSERT INTO features (feature_name, feature_key, feature_description, feature_icon, display_order)
VALUES ('Feature Name', 'feature_key', 'Description', '🎯', 1);

-- Enable feature for community
INSERT INTO community_features (community_id, feature_id, is_enabled, enabled_by, enabled_date)
VALUES (1, 1, 1, 1, NOW());

-- Check enabled features
SELECT f.feature_name, cf.is_enabled
FROM features f
LEFT JOIN community_features cf ON f.feature_id = cf.feature_id AND cf.community_id = 1;
```

---

# Summary

**GetToKnow v4.0** is a SAAS multi-tenant community management platform with:

✅ Feature-based access control
✅ Multi-tenant architecture
✅ Dashboard-centric UI
✅ Modular, scalable design
✅ Role-based permissions
✅ Complete data isolation
✅ Easy feature management

**Core Principle:** Admin enables features per community → Group Admin sees enabled features as cards → Modular system allows unlimited features

**Next Steps:**
1. Fix feature icons (run FIX_ALL_FEATURES.sql)
2. Add your 2 new features
3. Test with multiple communities
4. Deploy to production

**Remember:** Always filter by `community_id`, always check feature access, always use prepared statements!

---

**Document Version:** 1.0
**Last Updated:** January 12, 2026
**Maintained By:** Development Team

When you read this document in future, you will have complete understanding of:
- What GetToKnow is and how it works
- Database structure and relationships
- How to add new features
- All rules and standards
- Security best practices
- Deployment procedures
- Troubleshooting common issues

This is the SINGLE SOURCE OF TRUTH for the GetToKnow platform.
