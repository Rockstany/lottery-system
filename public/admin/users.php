<?php
/**
 * User Management - List All Users
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get all users
$query = "SELECT u.user_id, u.mobile_number, u.full_name, u.email, u.role, u.status, u.created_at, u.last_login,
          (SELECT community_name FROM communities c
           JOIN group_admin_assignments ga ON c.community_id = ga.community_id
           WHERE ga.user_id = u.user_id LIMIT 1) as community_name
          FROM users u
          ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo APP_NAME; ?></title>
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
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo APP_NAME; ?> - User Management</h1>
        </div>
    </div>

    <div class="container main-content">
        <nav class="nav-menu">
            <ul>
                <li><a href="/public/admin/dashboard.php">Dashboard</a></li>
                <li><a href="/public/admin/users.php" style="font-weight: 600;">Manage Users</a></li>
                <li><a href="/public/admin/communities.php">Manage Communities</a></li>
                <li><a href="/public/logout.php">Logout</a></li>
            </ul>
        </nav>

        <?php if ($success === 'created'): ?>
            <div class="alert alert-success">User created successfully!</div>
        <?php elseif ($success === 'updated'): ?>
            <div class="alert alert-success">User updated successfully!</div>
        <?php elseif ($success === 'deleted'): ?>
            <div class="alert alert-success">User deactivated successfully!</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title" style="margin: 0;">All Users (<?php echo count($users); ?>)</h3>
                <a href="/public/admin/user-add.php" class="btn btn-primary">
                    + Create Group Admin
                </a>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Community</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['mobile_number']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge badge-danger">Admin</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">Group Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['community_name'] ?? 'Not Assigned'); ?></td>
                                        <td>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($user['last_login']) {
                                                echo date('M d, Y H:i', strtotime($user['last_login']));
                                            } else {
                                                echo 'Never';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="/public/admin/user-edit.php?id=<?php echo $user['user_id']; ?>"
                                                   class="btn btn-sm btn-primary">Edit</a>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <a href="/public/admin/user-toggle.php?id=<?php echo $user['user_id']; ?>&action=deactivate"
                                                       class="btn btn-sm btn-warning"
                                                       onclick="return confirm('Deactivate this user?')">Deactivate</a>
                                                <?php else: ?>
                                                    <a href="/public/admin/user-toggle.php?id=<?php echo $user['user_id']; ?>&action=activate"
                                                       class="btn btn-sm btn-success">Activate</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: var(--spacing-xl); color: var(--gray-500);">
                                        No users found
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
