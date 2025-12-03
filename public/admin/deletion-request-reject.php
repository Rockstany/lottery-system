<?php
/**
 * Reject Deletion Request
 * Super Admin Only
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$adminId = AuthMiddleware::getUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /public/admin/deletion-requests.php?error=invalid");
    exit;
}

$requestId = Validator::sanitizeInt($_POST['request_id'] ?? 0);
$notes = Validator::sanitizeString($_POST['notes'] ?? '');

if (!$requestId || empty($notes)) {
    header("Location: /public/admin/deletion-requests.php?error=invalid");
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verify request exists and is pending
    $verifyQuery = "SELECT * FROM deletion_requests WHERE request_id = :request_id AND status = 'pending'";
    $verifyStmt = $db->prepare($verifyQuery);
    $verifyStmt->bindParam(':request_id', $requestId);
    $verifyStmt->execute();
    $request = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        header("Location: /public/admin/deletion-requests.php?error=notfound");
        exit;
    }

    // Update deletion request status to rejected
    $updateQuery = "UPDATE deletion_requests
                   SET status = 'rejected',
                       reviewed_by = :reviewed_by,
                       review_notes = :review_notes,
                       reviewed_at = NOW()
                   WHERE request_id = :request_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':reviewed_by', $adminId);
    $updateStmt->bindParam(':review_notes', $notes);
    $updateStmt->bindParam(':request_id', $requestId);

    if (!$updateStmt->execute()) {
        header("Location: /public/admin/deletion-requests.php?error=update_failed");
        exit;
    }

    // Log the activity
    $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at)
                 VALUES (:user_id, 'deletion_rejected', :description, NOW())";
    $logStmt = $db->prepare($logQuery);
    $description = "Rejected deletion request #{$requestId}: {$request['item_name']}";
    $logStmt->bindParam(':user_id', $adminId);
    $logStmt->bindParam(':description', $description);
    $logStmt->execute();

    header("Location: /public/admin/deletion-requests.php?success=rejected");
    exit;

} catch (Exception $e) {
    error_log("Reject Deletion Error: " . $e->getMessage());
    header("Location: /public/admin/deletion-requests.php?error=update_failed");
    exit;
}
