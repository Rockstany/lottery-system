<?php
/**
 * View Sub-Community Details
 * Display sub-community information and members
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

AuthMiddleware::requireRole('group_admin');

$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'community_building')) {
    $_SESSION['error_message'] = "Community Building is not enabled for your community";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$subCommId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get sub-community details
$query = "SELECT * FROM sub_communities
          WHERE sub_community_id = :id AND community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $subCommId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$subCommunity = $stmt->fetch();

if (!$subCommunity) {
    $_SESSION['error_message'] = "Sub-community not found";
    header('Location: /public/group-admin/sub-communities.php');
    exit();
}

// Get members
$membersQuery = "SELECT u.user_id, u.full_name, u.email, u.phone_number,
                 scm.joined_date, scm.status
                 FROM users u
                 JOIN sub_community_members scm ON u.user_id = scm.user_id
                 WHERE scm.sub_community_id = :sub_comm_id
                 ORDER BY u.full_name ASC";
$membersStmt = $db->prepare($membersQuery);
$membersStmt->bindParam(':sub_comm_id', $subCommId);
$membersStmt->execute();
$members = $membersStmt->fetchAll();

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => '/public/group-admin/community-building.php'],
    ['label' => 'Sub-Communities', 'url' => '/public/group-admin/sub-communities.php'],
    ['label' => 'View', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subCommunity['sub_community_name']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: start;
        }
        .header-info h1 {
            margin: 0 0 10px 0;
        }
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .actions-bar {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .members-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="content-wrapper">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-info">
                    <h1>🏘️ <?php echo htmlspecialchars($subCommunity['sub_community_name']); ?></h1>
                    <p style="color: #666; margin: 10px 0;">
                        <?php echo htmlspecialchars($subCommunity['description'] ?? 'No description provided'); ?>
                    </p>
                    <p style="color: #999; font-size: 0.9rem; margin: 10px 0 0 0;">
                        Created on <?php echo date('M d, Y', strtotime($subCommunity['created_at'])); ?>
                    </p>
                </div>
                <div>
                    <span class="status-badge status-<?php echo $subCommunity['status']; ?>">
                        <?php echo ucfirst($subCommunity['status']); ?>
                    </span>
                </div>
            </div>

            <div class="actions-bar">
                <a href="/public/group-admin/sub-community-edit.php?id=<?php echo $subCommId; ?>"
                   class="btn btn-primary">Edit Sub-Community</a>
                <a href="/public/group-admin/sub-communities.php" class="btn btn-secondary">Back to List</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($members); ?></div>
                <div class="stat-label">Total Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php echo count(array_filter($members, fn($m) => $m['status'] === 'active')); ?>
                </div>
                <div class="stat-label">Active Members</div>
            </div>
        </div>

        <!-- Members List -->
        <div class="members-section">
            <h2 style="margin: 0 0 20px 0;">Members</h2>

            <?php if (count($members) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><?php echo htmlspecialchars($member['phone_number'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($member['joined_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $member['status']; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No members in this sub-community yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
