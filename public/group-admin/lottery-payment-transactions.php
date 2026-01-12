<?php
/**
 * Payment Transactions Management
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$distributionId = Validator::sanitizeInt($_GET['dist_id'] ?? 0);

if (!$distributionId) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get distribution details
$query = "SELECT lb.*, bd.notes, bd.mobile_number, bd.distribution_path, bd.is_extra_book, le.event_name, le.price_per_ticket, le.tickets_per_book, le.event_id
          FROM book_distribution bd
          JOIN lottery_books lb ON bd.book_id = lb.book_id
          JOIN lottery_events le ON lb.event_id = le.event_id
          WHERE bd.distribution_id = :dist_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':dist_id', $distributionId);
$stmt->execute();
$distribution = $stmt->fetch();

if (!$distribution) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$error = '';
$success = '';

// Handle delete transaction
if (isset($_GET['delete_payment'])) {
    $paymentId = Validator::sanitizeInt($_GET['delete_payment']);

    // Check if user is admin or the one who collected the payment
    $checkQuery = "SELECT collected_by FROM payment_collections WHERE payment_id = :payment_id AND distribution_id = :dist_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':payment_id', $paymentId);
    $checkStmt->bindParam(':dist_id', $distributionId);
    $checkStmt->execute();
    $payment = $checkStmt->fetch();

    if ($payment && ($_SESSION['role'] === 'admin' || $payment['collected_by'] == AuthMiddleware::getUserId())) {
        $deleteQuery = "DELETE FROM payment_collections WHERE payment_id = :payment_id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':payment_id', $paymentId);

        if ($deleteStmt->execute()) {
            header("Location: ?dist_id=$distributionId&success=deleted");
            exit;
        } else {
            $error = 'Failed to delete transaction';
        }
    } else {
        $error = 'You do not have permission to delete this transaction';
    }
}

if (isset($_GET['success'])) {
    $success = match($_GET['success']) {
        'deleted' => 'Transaction deleted successfully',
        'delete_requested' => 'Deletion request submitted successfully! Super Admin will review your request.',
        'payment_edited' => 'Payment updated successfully!',
        default => ''
    };
}

if (isset($_GET['error'])) {
    $error = match($_GET['error']) {
        'duplicate_request' => 'A deletion request for this transaction is already pending review.',
        'request_failed' => 'Failed to submit deletion request. Please try again.',
        'notfound' => 'Transaction not found.',
        'invalid' => 'Invalid request.',
        'not_editable' => 'This payment cannot be edited.',
        default => $error
    };
}

// Get all payment transactions for this distribution
$transQuery = "SELECT pc.*, u.full_name as collector_name
               FROM payment_collections pc
               LEFT JOIN users u ON pc.collected_by = u.user_id
               WHERE pc.distribution_id = :dist_id
               ORDER BY pc.payment_date DESC, pc.created_at DESC";
$stmt = $db->prepare($transQuery);
$stmt->bindParam(':dist_id', $distributionId);
$stmt->execute();
$transactions = $stmt->fetchAll();

$expectedAmount = $distribution['price_per_ticket'] * $distribution['tickets_per_book'];
$totalPaid = array_sum(array_column($transactions, 'amount_paid'));
$outstanding = $expectedAmount - $totalPaid;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Transactions - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <script src="/public/js/toast.js"></script>
    <style>
        .header {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .info-box {
            background: var(--gray-50);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1>Payment Transactions - Book #<?php echo $distribution['book_number']; ?></h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($distribution['event_name']); ?></p>
        </div>
    </div>

    <div class="container main-content">
        <?php include __DIR__ . '/includes/toast-handler.php'; ?>

        <!-- Back Button -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery-payments.php?id=<?php echo $distribution['event_id']; ?>" class="btn btn-secondary">← Back to Payments</a>
        </div>

        <!-- Distribution Info -->
        <div class="info-box">
            <h3 style="margin-top: 0;">Book Details</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                <div>
                    <strong>Book Number:</strong> #<?php echo $distribution['book_number']; ?>
                    <?php if ($distribution['is_extra_book'] == 1): ?>
                        <span class="badge badge-warning" style="margin-left: 8px;" title="This book qualifies for extra book commission">📚 Extra Book</span>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>Ticket Range:</strong> <?php echo $distribution['start_ticket_number']; ?> - <?php echo $distribution['end_ticket_number']; ?>
                </div>
                <div>
                    <strong>Location:</strong> <?php echo htmlspecialchars($distribution['distribution_path'] ?? 'N/A'); ?>
                </div>
                <div>
                    <strong>Notes:</strong> <?php echo htmlspecialchars($distribution['notes'] ?? 'N/A'); ?>
                </div>
            </div>
            <div style="margin-top: var(--spacing-md); padding-top: var(--spacing-md); border-top: 1px solid var(--gray-300); display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--spacing-md);">
                <div>
                    <strong>Expected:</strong> <span style="font-size: var(--font-size-lg);">₹<?php echo number_format($expectedAmount); ?></span>
                </div>
                <div>
                    <strong>Paid:</strong> <span style="font-size: var(--font-size-lg); color: var(--success-color);">₹<?php echo number_format($totalPaid); ?></span>
                </div>
                <div>
                    <strong>Outstanding:</strong> <span style="font-size: var(--font-size-lg); color: <?php echo $outstanding > 0 ? 'var(--danger-color)' : 'var(--success-color)'; ?>;">₹<?php echo number_format($outstanding); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Transactions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">💰 Payment Transactions (<?php echo count($transactions); ?>)</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($transactions) === 0): ?>
                    <div style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                        <p>No payments collected yet.</p>
                        <a href="/public/group-admin/lottery-payment-collect.php?book_id=<?php echo $distribution['book_id']; ?>" class="btn btn-success">
                            Collect Payment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Collected By</th>
                                    <th>Collected At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $index => $trans): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($trans['payment_date'])); ?></td>
                                        <td><strong style="color: var(--success-color);">₹<?php echo number_format($trans['amount_paid']); ?></strong></td>
                                        <td>
                                            <?php
                                            $icon = match($trans['payment_method']) {
                                                'cash' => '💵',
                                                'upi' => '📱',
                                                'bank' => '🏦',
                                                default => '💳'
                                            };
                                            echo $icon . ' ' . strtoupper($trans['payment_method']);
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($trans['collector_name'] ?? 'Unknown'); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($trans['created_at'])); ?></td>
                                        <td>
                                            <div style="display: flex; gap: var(--spacing-xs); flex-wrap: wrap;">
                                                <?php if ($trans['is_editable']): ?>
                                                    <a href="/public/group-admin/lottery-payment-edit.php?payment_id=<?php echo $trans['payment_id']; ?>&dist_id=<?php echo $distributionId; ?>"
                                                       class="btn btn-sm btn-warning"
                                                       title="Edit payment">
                                                        ✏️ Edit
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                                    <a href="?dist_id=<?php echo $distributionId; ?>&delete_payment=<?php echo $trans['payment_id']; ?>"
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Delete this payment transaction? This cannot be undone.')">
                                                        🗑️ Delete
                                                    </a>
                                                <?php elseif ($_SESSION['role'] === 'group_admin'): ?>
                                                    <button
                                                        onclick="requestDeleteTransaction(<?php echo $trans['payment_id']; ?>, '₹<?php echo number_format($trans['amount_paid']); ?>', '<?php echo date('M d, Y', strtotime($trans['payment_date'])); ?>')"
                                                        class="btn btn-sm btn-danger">
                                                        🗑️ Request Delete
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-400);">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: 700; background: var(--gray-50);">
                                    <td colspan="2" style="text-align: right;">TOTAL:</td>
                                    <td colspan="5"><strong style="color: var(--success-color);">₹<?php echo number_format($totalPaid); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if ($outstanding > 0): ?>
                        <div style="padding: var(--spacing-lg); text-align: center; border-top: 1px solid var(--gray-200);">
                            <a href="/public/group-admin/lottery-payment-collect.php?book_id=<?php echo $distribution['book_id']; ?>" class="btn btn-success">
                                💰 Collect More Payment (Outstanding: ₹<?php echo number_format($outstanding); ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Request Delete Transaction Modal (Group Admin) -->
    <div id="requestDeleteTransactionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: var(--spacing-xl); border-radius: var(--radius-lg); max-width: 500px; margin: var(--spacing-md);">
            <h3 style="margin-top: 0; color: var(--warning-color);">🗑️ Request Transaction Deletion</h3>
            <p>You are requesting to delete payment transaction:</p>
            <div style="background: var(--gray-50); padding: var(--spacing-md); border-radius: var(--radius-md); margin: var(--spacing-md) 0;">
                <div><strong>Amount:</strong> <span id="transactionAmount"></span></div>
                <div><strong>Date:</strong> <span id="transactionDate"></span></div>
            </div>
            <p style="color: var(--gray-600); font-size: var(--font-size-sm);">
                This request will be sent to the Super Admin for approval. The transaction will not be deleted until approved.
            </p>
            <form id="deleteTransactionRequestForm">
                <div class="form-group">
                    <label for="transactionDeleteReason" class="form-label" style="font-weight: 600;">
                        Reason for deletion <span style="color: var(--danger-color);">*</span>
                    </label>
                    <textarea
                        id="transactionDeleteReason"
                        name="reason"
                        class="form-control"
                        rows="4"
                        required
                        placeholder="Please provide a detailed reason for requesting this deletion..."
                        style="resize: vertical;"
                    ></textarea>
                    <small style="color: var(--gray-600); font-size: var(--font-size-xs); display: block; margin-top: var(--spacing-xs);">
                        Be specific about why this transaction needs to be deleted (e.g., entered by mistake, duplicate, wrong amount, etc.)
                    </small>
                </div>
                <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                    <button type="button" onclick="closeRequestDeleteTransactionModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-warning" style="flex: 1;">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let requestDeleteTransactionId = null;

        // Group Admin - Request Delete Transaction
        function requestDeleteTransaction(transactionId, amount, date) {
            requestDeleteTransactionId = transactionId;
            document.getElementById('transactionAmount').textContent = amount;
            document.getElementById('transactionDate').textContent = date;
            const modal = document.getElementById('requestDeleteTransactionModal');
            modal.style.display = 'flex';
        }

        function closeRequestDeleteTransactionModal() {
            document.getElementById('requestDeleteTransactionModal').style.display = 'none';
            requestDeleteTransactionId = null;
            document.getElementById('transactionDeleteReason').value = '';
        }

        // Handle transaction deletion request form submission
        let isSubmitting = false;
        document.getElementById('deleteTransactionRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (isSubmitting) return; // Prevent double submission
            if (!requestDeleteTransactionId) return;

            const reason = document.getElementById('transactionDeleteReason').value.trim();
            if (!reason) {
                alert('Please provide a reason for deletion');
                return;
            }

            isSubmitting = true;

            // Show loading state
            const modal = document.getElementById('requestDeleteTransactionModal');
            modal.innerHTML = '<div style="background: white; padding: var(--spacing-xl); border-radius: var(--radius-lg); text-align: center;"><h3>Submitting Request...</h3><p>Please wait...</p></div>';

            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/public/group-admin/transaction-delete-request.php';

            const transactionIdInput = document.createElement('input');
            transactionIdInput.type = 'hidden';
            transactionIdInput.name = 'transaction_id';
            transactionIdInput.value = requestDeleteTransactionId;

            const distIdInput = document.createElement('input');
            distIdInput.type = 'hidden';
            distIdInput.name = 'dist_id';
            distIdInput.value = '<?php echo $distributionId; ?>';

            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = reason;

            form.appendChild(transactionIdInput);
            form.appendChild(distIdInput);
            form.appendChild(reasonInput);
            document.body.appendChild(form);
            form.submit();
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRequestDeleteTransactionModal();
            }
        });
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
