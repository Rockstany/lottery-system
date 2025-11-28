<?php
/**
 * Edit User
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$userId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$userId) {
    header("Location: /public/admin/users.php?error=Invalid user");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$userModel = new User();

// Get user details
$user = $userModel->getById($userId);
if (!$user) {
    header("Location: /public/admin/users.php?error=User not found");
    exit;
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $fullName = Validator::sanitizeString($_POST['full_name'] ?? '');
        $email = Validator::sanitizeString($_POST['email'] ?? '');
        $status = $_POST['status'] ?? 'active';

        // Validate
        $validator = new Validator();
        $validator->required('full_name', $fullName, 'Full Name')
                  ->email('email', $email)
                  ->enum('status', $status, ['active', 'inactive']);

        if ($validator->fails()) {
            $errors = $validator->getErrors();
        } else {
            // Update user
            $updated = $userModel->update($userId, [
                'full_name' => $fullName,
                'email' => $email,
                'status' => $status
            ]);

            if ($updated) {
                header("Location: /public/admin/users.php?success=updated");
                exit;
            } else {
                $error = 'Failed to update user';
            }
        }
    }
} else {
    // Populate form with existing data
    $_POST['full_name'] = $user['full_name'];
    $_POST['email'] = $user['email'];
    $_POST['status'] = $user['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
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
        .info-box {
            background: var(--gray-50);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .info-row {
            display: flex;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--gray-200);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            width: 150px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo APP_NAME; ?> - Edit User</h1>
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

        <div class="row">
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit User: <?php echo htmlspecialchars($user['full_name']); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <div class="info-row">
                                <div class="info-label">User ID:</div>
                                <div><?php echo $user['user_id']; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Mobile Number:</div>
                                <div><?php echo htmlspecialchars($user['mobile_number']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Role:</div>
                                <div>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge badge-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Group Admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Created:</div>
                                <div><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Last Login:</div>
                                <div>
                                    <?php
                                    if ($user['last_login']) {
                                        echo date('M d, Y H:i', strtotime($user['last_login']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label form-label-required">Full Name</label>
                                <input
                                    type="text"
                                    name="full_name"
                                    class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                                    value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                    required
                                >
                                <?php if (isset($errors['full_name'])): ?>
                                    <span class="form-error"><?php echo $errors['full_name']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                >
                                <?php if (isset($errors['email'])): ?>
                                    <span class="form-error"><?php echo $errors['email']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="active" <?php echo ($_POST['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div style="display: flex; gap: var(--spacing-md);">
                                <button type="submit" class="btn btn-primary">Update User</button>
                                <a href="/public/admin/users.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Change Password</h4>
                    </div>
                    <div class="card-body">
                        <p>Password changes coming soon.</p>
                        <p>For now, contact system administrator to reset password.</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Note</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Cannot Edit:</strong></p>
                        <ul>
                            <li>Mobile Number (Login ID)</li>
                            <li>User Role</li>
                            <li>User ID</li>
                        </ul>
                        <p>These fields are locked for security.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
