<?php
/**
 * Community Building System - Main Dashboard
 * Allows Group Admin to manage sub-communities and members
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

// Require Group Admin role
AuthMiddleware::requireRole('group_admin');

$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

// Check if feature is enabled
$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'community_building')) {
    $_SESSION['error_message'] = "Community Building is not enabled for your community";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get sub-communities count
$querySubComm = "SELECT COUNT(*) as count FROM sub_communities
                 WHERE community_id = :community_id AND status = 'active'";
$stmtSubComm = $db->prepare($querySubComm);
$stmtSubComm->bindParam(':community_id', $communityId);
$stmtSubComm->execute();
$subCommCount = $stmtSubComm->fetch()['count'];

// Get total members in sub-communities
$queryMembers = "SELECT COUNT(DISTINCT scm.user_id) as count
                 FROM sub_community_members scm
                 JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                 WHERE sc.community_id = :community_id AND scm.status = 'active'";
$stmtMembers = $db->prepare($queryMembers);
$stmtMembers->bindParam(':community_id', $communityId);
$stmtMembers->execute();
$memberCount = $stmtMembers->fetch()['count'];

// Get custom fields count
$queryFields = "SELECT COUNT(*) as count FROM custom_field_definitions
                WHERE community_id = :community_id AND status = 'active'";
$stmtFields = $db->prepare($queryFields);
$stmtFields->bindParam(':community_id', $communityId);
$stmtFields->execute();
$fieldCount = $stmtFields->fetch()['count'];

// Get recent sub-communities
$queryRecent = "SELECT sub_community_id, sub_community_name, description, created_at,
                (SELECT COUNT(*) FROM sub_community_members
                 WHERE sub_community_id = sc.sub_community_id AND status = 'active') as member_count
                FROM sub_communities sc
                WHERE community_id = :community_id AND status = 'active'
                ORDER BY created_at DESC
                LIMIT 5";
$stmtRecent = $db->prepare($queryRecent);
$stmtRecent->bindParam(':community_id', $communityId);
$stmtRecent->execute();
$recentSubCommunities = $stmtRecent->fetchAll();

// Breadcrumb
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Building - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
        }
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .action-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .action-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(102, 126, 234, 0.2);
        }
        .action-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .action-card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .action-card p {
            color: #666;
            margin: 0;
        }
        .sub-comm-list {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sub-comm-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .sub-comm-item:last-child {
            border-bottom: none;
        }
        .sub-comm-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .sub-comm-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        .member-badge {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <!-- Header -->
        <div style="margin-bottom: 30px;">
            <h1 style="margin: 0 0 10px 0;">👥 Community Building</h1>
            <p style="color: #666; margin: 0;">Manage sub-communities, custom fields, and members</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $subCommCount; ?></h3>
                <p>Active Sub-Communities</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><?php echo $memberCount; ?></h3>
                <p>Total Members</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><?php echo $fieldCount; ?></h3>
                <p>Custom Fields</p>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="action-grid">
            <a href="/public/group-admin/sub-communities.php" style="text-decoration: none;">
                <div class="action-card">
                    <div class="icon">🏘️</div>
                    <h3>Sub-Communities</h3>
                    <p>Create and manage sub-communities</p>
                </div>
            </a>

            <a href="/public/group-admin/custom-fields.php" style="text-decoration: none;">
                <div class="action-card">
                    <div class="icon">📝</div>
                    <h3>Custom Fields</h3>
                    <p>Define fields for members and sub-communities</p>
                </div>
            </a>

            <a href="/public/group-admin/community-members.php" style="text-decoration: none;">
                <div class="action-card">
                    <div class="icon">👤</div>
                    <h3>Members</h3>
                    <p>Register and manage community members</p>
                </div>
            </a>
        </div>

        <!-- Recent Sub-Communities -->
        <div class="sub-comm-list">
            <h2 style="margin: 0 0 20px 0;">Recent Sub-Communities</h2>

            <?php if (count($recentSubCommunities) > 0): ?>
                <?php foreach ($recentSubCommunities as $subComm): ?>
                    <div class="sub-comm-item">
                        <div class="sub-comm-info">
                            <h4><?php echo htmlspecialchars($subComm['sub_community_name']); ?></h4>
                            <p><?php echo htmlspecialchars($subComm['description'] ?? 'No description'); ?></p>
                            <p style="font-size: 0.8rem; color: #999; margin-top: 5px;">
                                Created on <?php echo date('M d, Y', strtotime($subComm['created_at'])); ?>
                            </p>
                        </div>
                        <div>
                            <span class="member-badge">
                                <?php echo $subComm['member_count']; ?> Members
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="/public/group-admin/sub-communities.php"
                       style="display: inline-block; padding: 10px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">
                        View All Sub-Communities
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">🏘️</div>
                    <h3>No Sub-Communities Yet</h3>
                    <p>Get started by creating your first sub-community</p>
                    <a href="/public/group-admin/sub-communities.php"
                       style="display: inline-block; margin-top: 15px; padding: 10px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">
                        Create Sub-Community
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
