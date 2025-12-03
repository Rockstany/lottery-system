<?php
/**
 * Lottery Event - Request Deletion
 * Allows group_admin to request deletion (requires super admin approval)
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$communityId = AuthMiddleware::getCommunityId();
$userId = AuthMiddleware::getUserId();

if (!$communityId || !$userId) {
    header("Location: /public/group-admin/lottery.php?error=unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /public/group-admin/lottery.php?error=invalid");
    exit;
}

$eventId = Validator::sanitizeInt($_POST['event_id'] ?? 0);
$reason = Validator::sanitizeString($_POST['reason'] ?? '');

if (!$eventId || empty($reason)) {
    header("Location: /public/group-admin/lottery.php?error=invalid");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Verify event exists and belongs to this community
$verifyQuery = "SELECT event_name FROM lottery_events WHERE event_id = :event_id AND community_id = :community_id";
$verifyStmt = $db->prepare($verifyQuery);
$verifyStmt->bindParam(':event_id', $eventId);
$verifyStmt->bindParam(':community_id', $communityId);
$verifyStmt->execute();
$event = $verifyStmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header("Location: /public/group-admin/lottery.php?error=notfound");
    exit;
}

try {
    // Check if there's already a pending request for this event
    $checkQuery = "SELECT request_id FROM deletion_requests
                   WHERE request_type = 'lottery_event'
                   AND item_id = :item_id
                   AND status = 'pending'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':item_id', $eventId);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        header("Location: /public/group-admin/lottery.php?error=duplicate_request");
        exit;
    }

    // Insert deletion request
    $insertQuery = "INSERT INTO deletion_requests
                    (request_type, item_id, item_name, requested_by, reason, status, created_at)
                    VALUES
                    ('lottery_event', :item_id, :item_name, :requested_by, :reason, 'pending', NOW())";

    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':item_id', $eventId);
    $insertStmt->bindParam(':item_name', $event['event_name']);
    $insertStmt->bindParam(':requested_by', $userId);
    $insertStmt->bindParam(':reason', $reason);
    $insertStmt->execute();

    // Log the activity
    $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at)
                 VALUES (:user_id, 'deletion_requested', :description, NOW())";
    $logStmt = $db->prepare($logQuery);
    $description = "Requested deletion of lottery event: {$event['event_name']}. Reason: " . substr($reason, 0, 100);
    $logStmt->bindParam(':user_id', $userId);
    $logStmt->bindParam(':description', $description);
    $logStmt->execute();

    // Send email notification to admin
    if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
        $subject = "Deletion Request: Lottery Event - " . $event['event_name'];
        $message = "A deletion request has been submitted:\n\n";
        $message .= "Type: Lottery Event\n";
        $message .= "Event Name: {$event['event_name']}\n";
        $message .= "Requested By: User ID {$userId}\n";
        $message .= "Reason: {$reason}\n\n";
        $message .= "Please review this request at:\n";
        $message .= "https://zatana.in/public/admin/deletion-requests.php\n";

        $headers = "From: noreply@zatana.in\r\n";
        $headers .= "Reply-To: noreply@zatana.in\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        @mail(ADMIN_EMAIL, $subject, $message, $headers);
    }

    header("Location: /public/group-admin/lottery.php?success=delete_requested");
    exit;

} catch (Exception $e) {
    error_log("Delete Request Error: " . $e->getMessage());
    header("Location: /public/group-admin/lottery.php?error=request_failed");
    exit;
}
