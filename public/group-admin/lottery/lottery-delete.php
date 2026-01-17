<?php
/**
 * Delete Lottery Event (Admin Only)
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../../config/config.php';
AuthMiddleware::requireRole('admin'); // Only admins can delete

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$eventId) {
    header("Location: /public/group-admin/lottery/lottery.php?error=invalid");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get event details for logging
$query = "SELECT event_name FROM lottery_events WHERE event_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $eventId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery/lottery.php?error=notfound");
    exit;
}

// Delete the event (CASCADE will delete all related records)
$deleteQuery = "DELETE FROM lottery_events WHERE event_id = :id";
$stmt = $db->prepare($deleteQuery);
$stmt->bindParam(':id', $eventId);

if ($stmt->execute()) {
    // Log the action
    $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description) VALUES (:user_id, 'lottery_event_deleted', :description)";
    $logStmt = $db->prepare($logQuery);
    $userId = AuthMiddleware::getUserId();
    $description = "Deleted lottery event: " . $event['event_name'];
    $logStmt->bindParam(':user_id', $userId);
    $logStmt->bindParam(':description', $description);
    $logStmt->execute();

    header("Location: /public/group-admin/lottery/lottery.php?success=deleted");
} else {
    header("Location: /public/group-admin/lottery/lottery.php?error=deletefailed");
}
exit;
