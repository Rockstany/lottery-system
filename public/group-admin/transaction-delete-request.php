<?php
/**
 * Payment Transaction - Request Deletion
 * Allows group_admin to request deletion (requires super admin approval)
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$userId = AuthMiddleware::getUserId();

if (!$userId) {
    header("Location: /public/group-admin/lottery.php?error=unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /public/group-admin/lottery.php?error=invalid");
    exit;
}

$transactionId = Validator::sanitizeInt($_POST['transaction_id'] ?? 0);
$distId = Validator::sanitizeInt($_POST['dist_id'] ?? 0);
$reason = Validator::sanitizeString($_POST['reason'] ?? '');

if (!$transactionId || !$distId || empty($reason)) {
    header("Location: /public/group-admin/lottery-payment-transactions.php?dist_id=$distId&error=invalid");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Verify transaction exists and get details
$verifyQuery = "SELECT pc.*, lb.book_number, le.event_name
                FROM payment_collections pc
                JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                JOIN lottery_books lb ON bd.book_id = lb.book_id
                JOIN lottery_events le ON lb.event_id = le.event_id
                WHERE pc.payment_id = :payment_id AND pc.distribution_id = :dist_id";
$verifyStmt = $db->prepare($verifyQuery);
$verifyStmt->bindParam(':payment_id', $transactionId);
$verifyStmt->bindParam(':dist_id', $distId);
$verifyStmt->execute();
$transaction = $verifyStmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    header("Location: /public/group-admin/lottery-payment-transactions.php?dist_id=$distId&error=notfound");
    exit;
}

try {
    // Check if there's already a pending request for this transaction
    $checkQuery = "SELECT request_id FROM deletion_requests
                   WHERE request_type = 'transaction'
                   AND item_id = :item_id
                   AND status = 'pending'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':item_id', $transactionId);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        header("Location: /public/group-admin/lottery-payment-transactions.php?dist_id=$distId&error=duplicate_request");
        exit;
    }

    // Create item name/description
    $itemName = "Transaction: ₹" . number_format($transaction['amount_paid']) . " - Book #{$transaction['book_number']} ({$transaction['event_name']})";

    // Insert deletion request
    $insertQuery = "INSERT INTO deletion_requests
                    (request_type, item_id, item_name, requested_by, reason, status, created_at)
                    VALUES
                    ('transaction', :item_id, :item_name, :requested_by, :reason, 'pending', NOW())";

    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':item_id', $transactionId);
    $insertStmt->bindParam(':item_name', $itemName);
    $insertStmt->bindParam(':requested_by', $userId);
    $insertStmt->bindParam(':reason', $reason);
    $insertStmt->execute();

    // Log the activity
    $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at)
                 VALUES (:user_id, 'deletion_requested', :description, NOW())";
    $logStmt = $db->prepare($logQuery);
    $description = "Requested deletion of payment transaction: {$itemName}. Reason: " . substr($reason, 0, 100);
    $logStmt->bindParam(':user_id', $userId);
    $logStmt->bindParam(':description', $description);
    $logStmt->execute();

    // Send email notification to admin
    if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
        $subject = "Deletion Request: Payment Transaction";
        $message = "A deletion request has been submitted:\n\n";
        $message .= "Type: Payment Transaction\n";
        $message .= "Transaction: {$itemName}\n";
        $message .= "Payment Date: " . date('M d, Y', strtotime($transaction['payment_date'])) . "\n";
        $message .= "Requested By: User ID {$userId}\n";
        $message .= "Reason: {$reason}\n\n";
        $message .= "Please review this request at:\n";
        $message .= "https://zatana.in/public/admin/deletion-requests.php\n";

        $headers = "From: noreply@zatana.in\r\n";
        $headers .= "Reply-To: noreply@zatana.in\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        @mail(ADMIN_EMAIL, $subject, $message, $headers);
    }

    header("Location: /public/group-admin/lottery-payment-transactions.php?dist_id=$distId&success=delete_requested");
    exit;

} catch (Exception $e) {
    error_log("Transaction Delete Request Error: " . $e->getMessage());
    header("Location: /public/group-admin/lottery-payment-transactions.php?dist_id=$distId&error=request_failed");
    exit;
}
