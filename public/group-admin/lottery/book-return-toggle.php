<?php
/**
 * Toggle Book Return Status
 * Mark book as returned or not returned
 */

require_once __DIR__ . '/../../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$distributionId = Validator::sanitizeInt($_POST['distribution_id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'mark_returned' or 'mark_not_returned'

if (!$distributionId || !in_array($action, ['mark_returned', 'mark_not_returned'])) {
    header("Location: /public/group-admin/lottery/lottery-books.php?error=invalid");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get distribution details
$query = "SELECT bd.*, lb.book_number, lb.event_id
          FROM book_distribution bd
          JOIN lottery_books lb ON bd.book_id = lb.book_id
          WHERE bd.distribution_id = :dist_id";
$stmt = $db->prepare($query);
$stmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
$stmt->execute();
$distribution = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$distribution) {
    header("Location: /public/group-admin/lottery/lottery-books.php?error=notfound");
    exit;
}

try {
    $db->beginTransaction();

    if ($action === 'mark_returned') {
        // Mark as returned
        $updateQuery = "UPDATE book_distribution
                       SET is_returned = 1,
                           returned_by = :returned_by
                       WHERE distribution_id = :dist_id";
        $updateStmt = $db->prepare($updateQuery);
        $userId = AuthMiddleware::getUserId();
        $updateStmt->bindValue(':returned_by', $userId, PDO::PARAM_INT);
        $updateStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
        $updateStmt->execute();

        // Log activity
        $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at)
                    VALUES (:user_id, 'book_returned', :description, NOW())";
        $logStmt = $db->prepare($logQuery);
        $description = "Marked book #{$distribution['book_number']} as RETURNED (Distribution ID: $distributionId)";
        $logStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $logStmt->bindValue(':description', $description, PDO::PARAM_STR);
        $logStmt->execute();

        $successMsg = 'Book marked as returned successfully!';

    } else {
        // Mark as not returned (undo)
        $updateQuery = "UPDATE book_distribution
                       SET is_returned = 0,
                           returned_by = NULL
                       WHERE distribution_id = :dist_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
        $updateStmt->execute();

        // Log activity
        $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at)
                    VALUES (:user_id, 'book_return_undone', :description, NOW())";
        $logStmt = $db->prepare($logQuery);
        $userId = AuthMiddleware::getUserId();
        $description = "Unmarked book #{$distribution['book_number']} return status (Distribution ID: $distributionId)";
        $logStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $logStmt->bindValue(':description', $description, PDO::PARAM_STR);
        $logStmt->execute();

        $successMsg = 'Book return status updated!';
    }

    $db->commit();

    // Redirect back with success message
    $eventId = $distribution['event_id'];

    // Check if coming from payments or books page
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'lottery-payments.php') !== false) {
        header("Location: /public/group-admin/lottery/lottery-payments.php?id=$eventId&success=" . urlencode($successMsg));
    } else {
        header("Location: /public/group-admin/lottery/lottery-books.php?id=$eventId&success=" . urlencode($successMsg));
    }
    exit;

} catch (Exception $e) {
    $db->rollBack();
    error_log("Book Return Toggle Error: " . $e->getMessage());
    header("Location: /public/group-admin/lottery/lottery-books.php?error=failed");
    exit;
}
