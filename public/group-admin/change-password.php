<?php
/**
 * Change Password - Group Admin
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$userId = AuthMiddleware::getUserId();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // Verify current password
        $query = "SELECT password_hash FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $error = 'Current password is incorrect';
        } else {
            // Update password
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
            $query = "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password_hash', $newPasswordHash);
            $stmt->bindParam(':user_id', $userId);

            if ($stmt->execute()) {
                $success = 'Password changed successfully!';

                // Log the activity
                $activityQuery = "INSERT INTO activity_log (user_id, action, details) VALUES (:user_id, 'password_change', 'Group Admin changed their password')";
                $activityStmt = $db->prepare($activityQuery);
                $activityStmt->bindParam(':user_id', $userId);
                $activityStmt->execute();
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .password-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .security-tips {
            background: var(--info-light);
            border-left: 4px solid var(--info-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .password-input-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: var(--font-size-lg);
            padding: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🔐 Change Password</h1>
            <p style="margin: 0; opacity: 0.9;">Update your account password</p>
        </div>
    </div>

    <div class="container main-content">
        <div class="password-container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <a href="/public/group-admin/dashboard.php" style="margin-left: var(--spacing-md);">
                        Back to Dashboard →
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Security Tips -->
            <div class="security-tips">
                <h4 style="margin-top: 0;">🛡️ Password Security Tips</h4>
                <ul style="margin: 0; padding-left: var(--spacing-lg);">
                    <li>Use at least 6 characters (longer is better)</li>
                    <li>Mix uppercase, lowercase, numbers, and symbols</li>
                    <li>Avoid common words or personal information</li>
                    <li>Don't reuse passwords from other accounts</li>
                </ul>
            </div>

            <!-- Change Password Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Change Your Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="form-group">
                            <label class="form-label form-label-required">Current Password</label>
                            <div class="password-input-wrapper">
                                <input
                                    type="password"
                                    name="current_password"
                                    id="currentPassword"
                                    class="form-control"
                                    placeholder="Enter your current password"
                                    required
                                    autofocus
                                >
                                <button type="button" class="toggle-password" onclick="togglePassword('currentPassword', this)">
                                    👁️
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label form-label-required">New Password</label>
                            <div class="password-input-wrapper">
                                <input
                                    type="password"
                                    name="new_password"
                                    id="newPassword"
                                    class="form-control"
                                    placeholder="Enter new password (min 6 characters)"
                                    minlength="6"
                                    required
                                >
                                <button type="button" class="toggle-password" onclick="togglePassword('newPassword', this)">
                                    👁️
                                </button>
                            </div>
                            <span class="form-help">Minimum 6 characters</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label form-label-required">Confirm New Password</label>
                            <div class="password-input-wrapper">
                                <input
                                    type="password"
                                    name="confirm_password"
                                    id="confirmPassword"
                                    class="form-control"
                                    placeholder="Re-enter new password"
                                    minlength="6"
                                    required
                                >
                                <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)">
                                    👁️
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Change Password
                            </button>
                            <a href="/public/group-admin/dashboard.php" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, button) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = '🙈';
            } else {
                field.type = 'password';
                button.textContent = '👁️';
            }
        }

        // Password match validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>
