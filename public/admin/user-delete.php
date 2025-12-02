<?php
/**
 * Delete User (Admin Only)
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$userId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$userId) {
    header("Location: /public/admin/users.php?error=invalid");
    exit;
}

// Prevent deleting yourself
if ($userId == AuthMiddleware::getUserId()) {
    header("Location: /public/admin/users.php?error=cannotdeleteyourself");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get user details
$query = "SELECT * FROM users WHERE user_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $userId);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    header("Location: /public/admin/users.php?error=notfound");
    exit;
}

// Check if this is the last admin
if ($user['role'] === 'admin') {
    $adminCountQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'";
    $stmt = $db->prepare($adminCountQuery);
    $stmt->execute();
    $adminCount = $stmt->fetch()['count'];

    if ($adminCount <= 1) {
        header("Location: /public/admin/users.php?error=lastadmin");
        exit;
    }
}

// Delete user (CASCADE will delete assignments)
$deleteQuery = "DELETE FROM users WHERE user_id = :id";
$stmt = $db->prepare($deleteQuery);
$stmt->bindParam(':id', $userId);

if ($stmt->execute()) {
    // Log the action
    $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description) VALUES (:user_id, 'user_deleted', :description)";
    $logStmt = $db->prepare($logQuery);
    $adminId = AuthMiddleware::getUserId();
    $description = "Deleted user: " . $user['full_name'] . " (" . $user['mobile_number'] . ")";
    $logStmt->bindParam(':user_id', $adminId);
    $logStmt->bindParam(':description', $description);
    $logStmt->execute();

    header("Location: /public/admin/users.php?success=deleted");
} else {
    header("Location: /public/admin/users.php?error=deletefailed");
}
exit;
