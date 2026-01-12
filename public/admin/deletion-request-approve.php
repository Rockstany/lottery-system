<?php
/**
 * Approve Deletion Request and Delete Item
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
$requestType = Validator::sanitizeString($_POST['request_type'] ?? '');
$itemId = Validator::sanitizeInt($_POST['item_id'] ?? 0);
$notes = Validator::sanitizeString($_POST['notes'] ?? '');

if (!$requestId || !$requestType || !$itemId) {
    header("Location: /public/admin/deletion-requests.php?error=invalid");
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Start transaction
    $db->beginTransaction();

    // Verify request exists and is pending
    $verifyQuery = "SELECT * FROM deletion_requests WHERE request_id = :request_id AND status = 'pending'";
    $verifyStmt = $db->prepare($verifyQuery);
    $verifyStmt->bindParam(':request_id', $requestId);
    $verifyStmt->execute();
    $request = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $db->rollBack();
        header("Location: /public/admin/deletion-requests.php?error=notfound");
        exit;
    }

    // Delete the actual item based on type
    $deleteSuccess = false;

    if ($requestType === 'lottery_event') {
        // Delete lottery event and all related data
        // First delete all related records in correct order

        // Get all book IDs for this event
        $bookIdsQuery = "SELECT book_id FROM lottery_books WHERE event_id = :event_id";
        $bookIdsStmt = $db->prepare($bookIdsQuery);
        $bookIdsStmt->bindParam(':event_id', $itemId);
        $bookIdsStmt->execute();
        $bookIds = $bookIdsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($bookIds) > 0) {
            $bookIdsList = implode(',', $bookIds);

            // Delete payment collections
            $deletePaymentsQuery = "DELETE pc FROM payment_collections pc
                                   JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                                   WHERE bd.book_id IN ($bookIdsList)";
            $db->exec($deletePaymentsQuery);

            // Delete book distributions
            $deleteDistQuery = "DELETE FROM book_distribution WHERE book_id IN ($bookIdsList)";
            $db->exec($deleteDistQuery);
        }

        // Delete lottery books
        $deleteBooksQuery = "DELETE FROM lottery_books WHERE event_id = :event_id";
        $deleteBooksStmt = $db->prepare($deleteBooksQuery);
        $deleteBooksStmt->bindParam(':event_id', $itemId);
        $deleteBooksStmt->execute();

        // Delete lottery event
        $deleteEventQuery = "DELETE FROM lottery_events WHERE event_id = :event_id";
        $deleteEventStmt = $db->prepare($deleteEventQuery);
        $deleteEventStmt->bindParam(':event_id', $itemId);
        $deleteSuccess = $deleteEventStmt->execute();

    } elseif ($requestType === 'transaction') {
        // Delete payment transaction
        $deleteTransQuery = "DELETE FROM payment_collections WHERE payment_id = :payment_id";
        $deleteTransStmt = $db->prepare($deleteTransQuery);
        $deleteTransStmt->bindParam(':payment_id', $itemId);
        $deleteSuccess = $deleteTransStmt->execute();
    }

    if (!$deleteSuccess) {
        $db->rollBack();
        header("Location: /public/admin/deletion-requests.php?error=delete_failed");
        exit;
    }

    // Update deletion request status
    $updateQuery = "UPDATE deletion_requests
                   SET status = 'approved',
                       reviewed_by = :reviewed_by,
                       review_notes = :review_notes,
                       reviewed_at = NOW()
                   WHERE request_id = :request_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':reviewed_by', $adminId);
    $updateStmt->bindParam(':review_notes', $notes);
    $updateStmt->bindParam(':request_id', $requestId);
    $updateStmt->execute();

    // Log the activity
    $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at)
                 VALUES (:user_id, 'deletion_approved', :description, NOW())";
    $logStmt = $db->prepare($logQuery);
    $description = "Approved deletion request #{$requestId}: {$request['item_name']}";
    $logStmt->bindParam(':user_id', $adminId);
    $logStmt->bindParam(':description', $description);
    $logStmt->execute();

    // Commit transaction
    $db->commit();

    header("Location: /public/admin/deletion-requests.php?success=approved");
    exit;

} catch (Exception $e) {
    $db->rollBack();
    error_log("Approve Deletion Error: " . $e->getMessage());
    header("Location: /public/admin/deletion-requests.php?error=delete_failed");
    exit;
}
