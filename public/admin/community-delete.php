<?php
/**
 * Delete Community (Admin Only)
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$communityId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$communityId) {
    header("Location: /public/admin/communities.php?error=invalid");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get community details
$query = "SELECT * FROM communities WHERE community_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $communityId);
$stmt->execute();
$community = $stmt->fetch();

if (!$community) {
    header("Location: /public/admin/communities.php?error=notfound");
    exit;
}

// Delete community (CASCADE will delete all related data)
$deleteQuery = "DELETE FROM communities WHERE community_id = :id";
$stmt = $db->prepare($deleteQuery);
$stmt->bindParam(':id', $communityId);

if ($stmt->execute()) {
    // Log the action
    $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description) VALUES (:user_id, 'community_deleted', :description)";
    $logStmt = $db->prepare($logQuery);
    $adminId = AuthMiddleware::getUserId();
    $description = "Deleted community: " . $community['community_name'];
    $logStmt->bindParam(':user_id', $adminId);
    $logStmt->bindParam(':description', $description);
    $logStmt->execute();

    header("Location: /public/admin/communities.php?success=deleted");
} else {
    header("Location: /public/admin/communities.php?error=deletefailed");
}
exit;
