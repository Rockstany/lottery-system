<?php
/**
 * User Management - List All Users
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get all users with password
$query = "SELECT u.user_id, u.mobile_number, u.password_hash, u.full_name, u.email, u.role, u.status, u.created_at, u.last_login,
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
            <div class="alert alert-success">User deleted successfully!</div>
        <?php elseif ($success === 'password_reset'): ?>
            <div class="alert alert-success">Password reset successfully!</div>
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
                                <th>Password</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Community</th>
                                <th>Status</th>
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
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <input type="password" id="pwd_<?php echo $user['user_id']; ?>"
                                                       value="••••••••" readonly
                                                       style="width: 80px; border: none; background: transparent; font-size: 14px;">
                                                <button type="button" onclick="togglePassword(<?php echo $user['user_id']; ?>)"
                                                        class="btn btn-sm btn-secondary" style="padding: 4px 8px;">
                                                    <span id="eye_<?php echo $user['user_id']; ?>">👁️</span>
                                                </button>
                                            </div>
                                        </td>
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
                                            <div class="actions" style="flex-wrap: wrap; gap: 4px;">
                                                <a href="/public/admin/user-edit.php?id=<?php echo $user['user_id']; ?>"
                                                   class="btn btn-sm btn-primary">Edit</a>
                                                <a href="/public/admin/user-reset-password.php?id=<?php echo $user['user_id']; ?>"
                                                   class="btn btn-sm btn-warning">Reset Pwd</a>
                                                <?php
                                                // Don't allow deleting the last admin or yourself
                                                $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
                                                $canDelete = !($user['role'] === 'admin' && $adminCount <= 1) && $user['user_id'] != AuthMiddleware::getUserId();
                                                if ($canDelete):
                                                ?>
                                                    <button onclick="confirmDeleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>')"
                                                            class="btn btn-sm btn-danger">Delete</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <script>
                                    window['pwdData_<?php echo $user['user_id']; ?>'] = <?php echo json_encode(substr($user['password_hash'], 0, 20) . '...'); ?>;
                                    </script>
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

    <!-- Delete User Modal -->
    <div id="deleteUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: var(--spacing-xl); border-radius: var(--radius-lg); max-width: 500px; margin: var(--spacing-md);">
            <h3 style="margin-top: 0; color: var(--danger-color);">⚠️ Delete User</h3>
            <p>Are you sure you want to delete <strong id="userName"></strong>?</p>
            <p style="color: var(--danger-color); font-weight: 600;">This will permanently delete:</p>
            <ul style="color: var(--gray-700);">
                <li>User account and login access</li>
                <li>All community assignments</li>
                <li>Activity logs will be preserved</li>
            </ul>
            <p style="color: var(--danger-color); font-weight: 700;">This action cannot be undone!</p>
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                <button onclick="closeDeleteUserModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                <button onclick="deleteUser()" class="btn btn-danger" style="flex: 1;">Delete User</button>
            </div>
        </div>
    </div>

    <script>
        let deleteUserId = null;
        const passwordVisible = {};

        function togglePassword(userId) {
            const input = document.getElementById('pwd_' + userId);
            const eye = document.getElementById('eye_' + userId);

            if (passwordVisible[userId]) {
                input.type = 'password';
                input.value = '••••••••';
                eye.textContent = '👁️';
                passwordVisible[userId] = false;
            } else {
                input.type = 'text';
                input.value = window['pwdData_' + userId];
                eye.textContent = '🙈';
                passwordVisible[userId] = true;
            }
        }

        function confirmDeleteUser(userId, userName) {
            deleteUserId = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('deleteUserModal').style.display = 'flex';
        }

        function closeDeleteUserModal() {
            document.getElementById('deleteUserModal').style.display = 'none';
            deleteUserId = null;
        }

        function deleteUser() {
            if (!deleteUserId) return;
            const modal = document.getElementById('deleteUserModal');
            modal.innerHTML = '<div style="background: white; padding: var(--spacing-xl); border-radius: var(--radius-lg); text-align: center;"><h3>Deleting...</h3><p>Please wait...</p></div>';
            window.location.href = '/public/admin/user-delete.php?id=' + deleteUserId;
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteUserModal();
            }
        });
    </script>
</body>
</html>
