<?php
/**
 * Reset User Password
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$userId = Validator::sanitizeInt($_GET['id'] ?? 0);

$database = new Database();
$db = $database->getConnection();

// Get user
$query = "SELECT * FROM users WHERE user_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $userId);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    header("Location: /public/admin/users.php?error=usernotfound");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Update password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE users SET password_hash = :password, plain_password = :plain_password WHERE user_id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':password', $passwordHash);
        $updateStmt->bindParam(':plain_password', $newPassword);
        $updateStmt->bindParam(':id', $userId);

        if ($updateStmt->execute()) {
            // Log activity
            $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description) VALUES (:user_id, 'password_reset', :description)";
            $logStmt = $db->prepare($logQuery);
            $adminId = AuthMiddleware::getUserId();
            $description = "Password reset for user: " . $user['full_name'];
            $logStmt->bindParam(':user_id', $adminId);
            $logStmt->bindParam(':description', $description);
            $logStmt->execute();

            header("Location: /public/admin/users.php?success=password_reset");
            exit;
        } else {
            $error = 'Failed to reset password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
</head>
<body>
    <div class="container main-content" style="max-width: 600px; margin-top: var(--spacing-2xl);">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Reset Password for <?php echo htmlspecialchars($user['full_name']); ?></h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">User</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['mobile_number']); ?>)" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6" autofocus>
                        <small class="form-text">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>

                    <div class="button-group-mobile">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                        <a href="/public/admin/users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
