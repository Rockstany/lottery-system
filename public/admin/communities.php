<?php
/**
 * Community Management - List All Communities
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

require_once __DIR__ . '/../../src/models/Community.php';

$communityModel = new Community();
$communities = $communityModel->getAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Communities - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .nav-menu {
            background: var(--white);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        .nav-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }
        .nav-menu a {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
        }
        .nav-menu a:hover {
            background: var(--gray-100);
            text-decoration: none;
        }
        .actions {
            display: flex;
            gap: var(--spacing-sm);
        }
        .btn-sm {
            padding: 8px 16px;
            font-size: var(--font-size-sm);
            min-height: auto;
        }
        .community-address {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo APP_NAME; ?> - Community Management</h1>
        </div>
    </div>

    <div class="container main-content">
        <nav class="nav-menu">
            <ul>
                <li><a href="/public/admin/dashboard.php">Dashboard</a></li>
                <li><a href="/public/admin/users.php">Manage Users</a></li>
                <li><a href="/public/admin/communities.php" style="font-weight: 600;">Manage Communities</a></li>
                <li><a href="/public/logout.php">Logout</a></li>
            </ul>
        </nav>

        <?php if ($success === 'created'): ?>
            <div class="alert alert-success">Community created successfully!</div>
        <?php elseif ($success === 'updated'): ?>
            <div class="alert alert-success">Community updated successfully!</div>
        <?php elseif ($success === 'status_changed'): ?>
            <div class="alert alert-success">Community status updated successfully!</div>
        <?php elseif ($success === 'admin_assigned'): ?>
            <div class="alert alert-success">Group Admin assigned successfully!</div>
        <?php elseif ($success === 'admin_unassigned'): ?>
            <div class="alert alert-success">Group Admin unassigned successfully!</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title" style="margin: 0;">All Communities (<?php echo count($communities); ?>)</h3>
                <a href="/public/admin/community-add.php" class="btn btn-primary">
                    + Create Community
                </a>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Community Name</th>
                                <th>Location</th>
                                <th>Group Admin</th>
                                <th>Members</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($communities) > 0): ?>
                                <?php foreach ($communities as $community): ?>
                                    <?php
                                    $groupAdmin = $communityModel->getGroupAdmin($community['community_id']);
                                    ?>
                                    <tr>
                                        <td><?php echo $community['community_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($community['community_name']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="community-address">
                                                <?php
                                                $location = [];
                                                if ($community['city']) $location[] = $community['city'];
                                                if ($community['state']) $location[] = $community['state'];
                                                if ($community['pincode']) $location[] = $community['pincode'];
                                                echo htmlspecialchars(implode(', ', $location)) ?: '-';
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($groupAdmin): ?>
                                                <strong><?php echo htmlspecialchars($groupAdmin['full_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($groupAdmin['mobile_number']); ?></small>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $community['total_members']; ?></td>
                                        <td>
                                            <?php if ($community['status'] === 'active'): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($community['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="/public/admin/community-edit.php?id=<?php echo $community['community_id']; ?>"
                                                   class="btn btn-sm btn-primary">Edit</a>
                                                <?php if ($community['status'] === 'active'): ?>
                                                    <a href="/public/admin/community-toggle.php?id=<?php echo $community['community_id']; ?>&action=deactivate"
                                                       class="btn btn-sm btn-warning"
                                                       onclick="return confirm('Deactivate this community?')">Deactivate</a>
                                                <?php else: ?>
                                                    <a href="/public/admin/community-toggle.php?id=<?php echo $community['community_id']; ?>&action=activate"
                                                       class="btn btn-sm btn-success">Activate</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: var(--spacing-xl); color: var(--gray-500);">
                                        No communities found. <a href="/public/admin/community-add.php">Create one now</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
