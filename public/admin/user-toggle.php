<?php
/**
 * Toggle User Status (Activate/Deactivate)
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$userId = Validator::sanitizeInt($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$userId || !in_array($action, ['activate', 'deactivate'])) {
    header("Location: /public/admin/users.php?error=Invalid request");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get user
$query = "SELECT user_id, full_name, status FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    header("Location: /public/admin/users.php?error=User not found");
    exit;
}

// Update status
$newStatus = ($action === 'activate') ? 'active' : 'inactive';

$query = "UPDATE users SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':status', $newStatus);
$stmt->bindParam(':user_id', $userId);

if ($stmt->execute()) {
    // Log activity
    $actionDesc = $action === 'activate' ? 'activated' : 'deactivated';
    $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description, ip_address)
                 VALUES (:admin_id, 'user_status_change', :description, :ip)";
    $logStmt = $db->prepare($logQuery);
    $adminId = AuthMiddleware::getUserId();
    $description = "User '{$user['full_name']}' (ID: {$userId}) {$actionDesc}";
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $logStmt->bindParam(':admin_id', $adminId);
    $logStmt->bindParam(':description', $description);
    $logStmt->bindParam(':ip', $ip);
    $logStmt->execute();

    header("Location: /public/admin/users.php?success=updated");
} else {
    header("Location: /public/admin/users.php?error=Failed to update status");
}
exit;
?>
