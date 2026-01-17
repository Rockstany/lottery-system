<?php
/**
 * Edit Payment Transaction
 * Allows updating payment amount, method, and date with reason
 */

require_once __DIR__ . '/../../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$paymentId = Validator::sanitizeInt($_GET['payment_id'] ?? 0);
$distId = Validator::sanitizeInt($_GET['dist_id'] ?? 0);

if (!$paymentId || !$distId) {
    header("Location: /public/group-admin/lottery/lottery.php?error=invalid");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get payment details
$query = "SELECT pc.*, lb.book_number, le.event_name, le.event_id,
                 le.price_per_ticket, le.tickets_per_book
          FROM payment_collections pc
          JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
          JOIN lottery_books lb ON bd.book_id = lb.book_id
          JOIN lottery_events le ON lb.event_id = le.event_id
          WHERE pc.payment_id = :payment_id AND pc.distribution_id = :dist_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':payment_id', $paymentId);
$stmt->bindParam(':dist_id', $distId);
$stmt->execute();
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    header("Location: /public/group-admin/lottery/lottery.php?error=notfound");
    exit;
}

// Check if payment is editable
if (!$payment['is_editable']) {
    header("Location: /public/group-admin/lottery/lottery-payment-transactions.php?dist_id=$distId&error=not_editable");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newAmount = Validator::sanitizeFloat($_POST['amount'] ?? 0);
    $newMethod = Validator::sanitizeString($_POST['payment_method'] ?? 'cash');
    $newDate = $_POST['payment_date'] ?? '';
    $editReason = Validator::sanitizeString($_POST['edit_reason'] ?? '');

    // Validate
    if ($newAmount <= 0) {
        $error = 'Amount must be greater than zero';
    } elseif (empty($newDate)) {
        $error = 'Payment date is required';
    } elseif (empty($editReason)) {
        $error = 'Please provide a reason for editing this payment';
    } else {
        try {
            $db->beginTransaction();

            // Save edit history
            $historyQuery = "INSERT INTO payment_edit_history
                            (payment_id, old_amount, old_payment_method, old_payment_date,
                             new_amount, new_payment_method, new_payment_date,
                             edit_reason, edited_by, edited_at)
                            VALUES
                            (:payment_id, :old_amount, :old_method, :old_date,
                             :new_amount, :new_method, :new_date,
                             :reason, :edited_by, NOW())";
            $historyStmt = $db->prepare($historyQuery);
            $historyStmt->bindParam(':payment_id', $paymentId);
            $historyStmt->bindParam(':old_amount', $payment['amount_paid']);
            $historyStmt->bindParam(':old_method', $payment['payment_method']);
            $historyStmt->bindParam(':old_date', $payment['payment_date']);
            $historyStmt->bindParam(':new_amount', $newAmount);
            $historyStmt->bindParam(':new_method', $newMethod);
            $historyStmt->bindParam(':new_date', $newDate);
            $historyStmt->bindParam(':reason', $editReason);
            $editedBy = AuthMiddleware::getUserId();
            $historyStmt->bindParam(':edited_by', $editedBy);
            $historyStmt->execute();

            // Update payment
            $updateQuery = "UPDATE payment_collections
                           SET amount_paid = :amount,
                               payment_method = :method,
                               payment_date = :date
                           WHERE payment_id = :payment_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':amount', $newAmount);
            $updateStmt->bindParam(':method', $newMethod);
            $updateStmt->bindParam(':date', $newDate);
            $updateStmt->bindParam(':payment_id', $paymentId);
            $updateStmt->execute();

            // Log activity
            $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at)
                        VALUES (:user_id, 'payment_edited', :description, NOW())";
            $logStmt = $db->prepare($logQuery);
            $description = "Edited payment #$paymentId: Book #{$payment['book_number']} ({$payment['event_name']}). Old: ₹{$payment['amount_paid']}, New: ₹$newAmount. Reason: $editReason";
            $logStmt->bindParam(':user_id', $editedBy);
            $logStmt->bindParam(':description', $description);
            $logStmt->execute();

            $db->commit();

            header("Location: /public/group-admin/lottery/lottery-payment-transactions.php?dist_id=$distId&success=payment_edited");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Payment Edit Error: " . $e->getMessage());
            $error = 'Failed to update payment. Please try again.';
        }
    }
}

$expectedAmount = $payment['tickets_per_book'] * $payment['price_per_ticket'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payment - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }

        .comparison-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-lg);
            margin: var(--spacing-lg) 0;
        }

        .comparison-col {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
        }

        .comparison-col.old {
            background: #fee;
            border: 2px solid #fca5a5;
        }

        .comparison-col.new {
            background: #eff6ff;
            border: 2px solid #93c5fd;
        }

        @media (max-width: 768px) {
            .comparison-box {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1>✏️ Edit Payment Transaction</h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($payment['event_name']); ?> - Book #<?php echo $payment['book_number']; ?></p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Back Button -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery/lottery-payment-transactions.php?dist_id=<?php echo $distId; ?>" class="btn btn-secondary">← Back to Transactions</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Current Payment Info -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <div class="card-header">
                <h3 class="card-title">Current Payment Details</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                    <div>
                        <strong>Amount Paid:</strong> ₹<?php echo number_format($payment['amount_paid'], 2); ?>
                    </div>
                    <div>
                        <strong>Payment Method:</strong> <?php echo strtoupper($payment['payment_method']); ?>
                    </div>
                    <div>
                        <strong>Payment Date:</strong> <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                    </div>
                    <div>
                        <strong>Collected At:</strong> <?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?>
                    </div>
                </div>
                <div style="margin-top: var(--spacing-md); padding: var(--spacing-sm); background: var(--info-light); border-radius: var(--radius-sm);">
                    <strong>ℹ️ Book Value:</strong> ₹<?php echo number_format($expectedAmount, 2); ?>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Update Payment Information</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">New Amount (₹) *</label>
                            <input
                                type="number"
                                name="amount"
                                class="form-control"
                                step="0.01"
                                min="1"
                                max="<?php echo $expectedAmount; ?>"
                                value="<?php echo $payment['amount_paid']; ?>"
                                required
                            >
                            <small class="form-text">Maximum: ₹<?php echo number_format($expectedAmount); ?></small>
                        </div>

                        <div class="form-col">
                            <label class="form-label">Payment Method *</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="cash" <?php echo $payment['payment_method'] === 'cash' ? 'selected' : ''; ?>>💵 Cash</option>
                                <option value="upi" <?php echo $payment['payment_method'] === 'upi' ? 'selected' : ''; ?>>📱 UPI</option>
                                <option value="bank" <?php echo $payment['payment_method'] === 'bank' ? 'selected' : ''; ?>>🏦 Bank Transfer</option>
                                <option value="other" <?php echo $payment['payment_method'] === 'other' ? 'selected' : ''; ?>>🔄 Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Date *</label>
                        <input
                            type="date"
                            name="payment_date"
                            class="form-control"
                            value="<?php echo $payment['payment_date']; ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reason for Editing * <span style="color: var(--danger-color);">(Required)</span></label>
                        <textarea
                            name="edit_reason"
                            class="form-control"
                            rows="3"
                            required
                            placeholder="Please provide a clear reason for editing this payment (e.g., wrong amount entered, payment method correction, date error, etc.)"
                        ></textarea>
                        <small class="form-text">This will be saved in the edit history for audit purposes</small>
                    </div>

                    <div style="background: var(--warning-light); padding: var(--spacing-md); border-radius: var(--radius-md); border-left: 4px solid var(--warning-color); margin: var(--spacing-lg) 0;">
                        <strong>⚠️ Important:</strong>
                        <ul style="margin: var(--spacing-sm) 0 0 var(--spacing-lg); padding: 0;">
                            <li>All changes are recorded in edit history</li>
                            <li>Commission calculations may be affected if payment date changes</li>
                            <li>This action cannot be undone</li>
                        </ul>
                    </div>

                    <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                        <button type="submit" class="btn btn-warning" style="flex: 1;">💾 Save Changes</button>
                        <a href="/public/group-admin/lottery/lottery-payment-transactions.php?dist_id=<?php echo $distId; ?>" class="btn btn-secondary" style="flex: 1;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
