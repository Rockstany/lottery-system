<?php
/**
 * Collect Payment for Lottery Book
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$bookId = Validator::sanitizeInt($_GET['book_id'] ?? 0);

$database = new Database();
$db = $database->getConnection();

// Get book & distribution details
$query = "SELECT lb.*, bd.distribution_id, bd.notes, bd.mobile_number, bd.distribution_path,
          le.event_id, le.event_name, le.price_per_ticket, le.tickets_per_book,
          COALESCE(SUM(pc.amount_paid), 0) as total_paid
          FROM lottery_books lb
          JOIN book_distribution bd ON lb.book_id = bd.book_id
          JOIN lottery_events le ON lb.event_id = le.event_id
          LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
          WHERE lb.book_id = :book_id
          GROUP BY lb.book_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':book_id', $bookId);
$stmt->execute();
$book = $stmt->fetch();

if (!$book) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$expectedAmount = $book['tickets_per_book'] * $book['price_per_ticket'];
$outstanding = $expectedAmount - $book['total_paid'];

// Get distribution levels for dynamic display
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$levelsStmt = $db->prepare($levelsQuery);
$levelsStmt->bindParam(':event_id', $book['event_id']);
$levelsStmt->execute();
$levels = $levelsStmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentType = $_POST['payment_type'] ?? 'partial';
    $amount = Validator::sanitizeFloat($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $returnReason = Validator::sanitizeString($_POST['return_reason'] ?? '');

    // Handle "No Payment - Books Returned" case
    if ($paymentType === 'no_payment') {
        if (empty($returnReason)) {
            $error = 'Please provide a reason for book return';
        } else {
            try {
                $db->beginTransaction();

                // Insert payment record with no_payment status
                $query = "INSERT INTO payment_collections
                         (distribution_id, amount_paid, payment_method, payment_date, collected_by, payment_status, return_reason, is_editable)
                         VALUES (:distribution_id, 0, 'cash', :payment_date, :collected_by, 'no_payment_book_returned', :return_reason, 0)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':distribution_id', $book['distribution_id']);
                $stmt->bindParam(':payment_date', $paymentDate);
                $stmt->bindParam(':collected_by', AuthMiddleware::getUserId());
                $stmt->bindParam(':return_reason', $returnReason);
                $stmt->execute();

                // Update book status back to available
                $updateBookQuery = "UPDATE lottery_books SET book_status = 'available' WHERE book_id = :book_id";
                $updateStmt = $db->prepare($updateBookQuery);
                $updateStmt->bindParam(':book_id', $book['book_id']);
                $updateStmt->execute();

                // Log activity
                $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at)
                            VALUES (:user_id, 'book_returned', :description, NOW())";
                $logStmt = $db->prepare($logQuery);
                $description = "Book #{$book['book_number']} returned - no payment received. Reason: $returnReason";
                $logStmt->bindParam(':user_id', AuthMiddleware::getUserId());
                $logStmt->bindParam(':description', $description);
                $logStmt->execute();

                $db->commit();
                header("Location: /public/group-admin/lottery-books.php?id={$book['event_id']}&success=book_returned");
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Book Return Error: " . $e->getMessage());
                $error = 'Failed to process book return. Please try again.';
            }
        }
    }
    // Normal payment collection
    else {
        // Validate based on payment type
        if ($paymentType === 'full') {
            if ($amount != $outstanding) {
                $error = 'Full payment amount must equal outstanding amount of ₹' . number_format($outstanding);
            }
        } else {
            if ($amount <= 0 || $amount > $outstanding) {
                $error = 'Partial payment must be between ₹1 and ₹' . number_format($outstanding);
            }
        }
    }

    if (!$error) {
        $query = "INSERT INTO payment_collections (distribution_id, amount_paid, payment_method, payment_date, collected_by)
                  VALUES (:distribution_id, :amount, :payment_method, :payment_date, :collected_by)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':distribution_id', $book['distribution_id']);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':payment_method', $paymentMethod);
        $stmt->bindParam(':payment_date', $paymentDate);
        $collectedBy = AuthMiddleware::getUserId();
        $stmt->bindParam(':collected_by', $collectedBy);

        if ($stmt->execute()) {
            // Check if payment is now FULLY PAID (commission only for full payment)
            $newTotalPaid = $book['total_paid'] + $amount;
            $isFullyPaid = ($newTotalPaid >= $expectedAmount);

            // Calculate and save commission if enabled AND payment is full
            if ($isFullyPaid) {
                $commissionQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id AND commission_enabled = 1";
                $commStmt = $db->prepare($commissionQuery);
                $commStmt->bindParam(':event_id', $book['event_id']);
                $commStmt->execute();
                $commSettings = $commStmt->fetch();

                if ($commSettings) {
                    // Get book distribution details including is_extra_book flag
                    $bookDistQuery = "SELECT is_extra_book, distributed_at FROM book_distribution WHERE distribution_id = :dist_id";
                    $bookDistStmt = $db->prepare($bookDistQuery);
                    $bookDistStmt->bindParam(':dist_id', $book['distribution_id']);
                    $bookDistStmt->execute();
                    $bookDist = $bookDistStmt->fetch();

                    // Collect all eligible commission types (can have multiple)
                    $eligibleCommissions = [];

                    // Check if book is marked as extra book
                    if ($bookDist && $bookDist['is_extra_book'] == 1 &&
                        $commSettings['extra_books_commission_enabled'] == 1) {
                        $eligibleCommissions[] = [
                            'type' => 'extra_books',
                            'percent' => $commSettings['extra_books_commission_percent']
                        ];
                    }

                    // Check date-based commission (can be in addition to extra books)
                    if ($commSettings['early_commission_enabled'] == 1 &&
                        !empty($commSettings['early_payment_date']) &&
                        $paymentDate <= $commSettings['early_payment_date']) {
                        $eligibleCommissions[] = [
                            'type' => 'early',
                            'percent' => $commSettings['early_commission_percent']
                        ];
                    }
                    elseif ($commSettings['standard_commission_enabled'] == 1 &&
                            !empty($commSettings['standard_payment_date']) &&
                            $paymentDate <= $commSettings['standard_payment_date']) {
                        $eligibleCommissions[] = [
                            'type' => 'standard',
                            'percent' => $commSettings['standard_commission_percent']
                        ];
                    }

                    // Extract Level 1 value from distribution_path
                    $level1Value = '';
                    if (!empty($book['distribution_path'])) {
                        $pathParts = explode(' > ', $book['distribution_path']);
                        $level1Value = $pathParts[0] ?? '';
                    }

                    // Save each eligible commission
                    if ($level1Value && count($eligibleCommissions) > 0) {
                        foreach ($eligibleCommissions as $commission) {
                            $commissionAmount = ($expectedAmount * $commission['percent']) / 100;

                            $insertCommQuery = "INSERT INTO commission_earned
                                               (event_id, distribution_id, level_1_value, commission_type, commission_percent,
                                                payment_amount, commission_amount, payment_date, book_id)
                                               VALUES (:event_id, :dist_id, :level_1, :comm_type, :comm_percent,
                                                       :payment_amt, :comm_amt, :payment_date, :book_id)";
                            $insertCommStmt = $db->prepare($insertCommQuery);
                            $insertCommStmt->bindParam(':event_id', $book['event_id']);
                            $insertCommStmt->bindParam(':dist_id', $book['distribution_id']);
                            $insertCommStmt->bindParam(':level_1', $level1Value);
                            $insertCommStmt->bindParam(':comm_type', $commission['type']);
                            $insertCommStmt->bindParam(':comm_percent', $commission['percent']);
                            $insertCommStmt->bindParam(':payment_amt', $expectedAmount);
                            $insertCommStmt->bindParam(':comm_amt', $commissionAmount);
                            $insertCommStmt->bindParam(':payment_date', $paymentDate);
                            $insertCommStmt->bindParam(':book_id', $bookId);
                            $insertCommStmt->execute();
                        }
                    }
                }
            }

            $success = 'Payment recorded successfully!';
            // Refresh data
            $refreshQuery = "SELECT lb.*, bd.distribution_id, bd.notes, bd.mobile_number, bd.distribution_path,
                            le.event_id, le.event_name, le.price_per_ticket, le.tickets_per_book,
                            COALESCE(SUM(pc.amount_paid), 0) as total_paid
                            FROM lottery_books lb
                            JOIN book_distribution bd ON lb.book_id = bd.book_id
                            JOIN lottery_events le ON lb.event_id = le.event_id
                            LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
                            WHERE lb.book_id = :book_id
                            GROUP BY lb.book_id";
            $refreshStmt = $db->prepare($refreshQuery);
            $refreshStmt->bindParam(':book_id', $bookId);
            $refreshStmt->execute();
            $book = $refreshStmt->fetch();
            $outstanding = $expectedAmount - $book['total_paid'];
        } else {
            $error = 'Failed to record payment';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Payment - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
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
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Collect Payment - Book <?php echo $book['book_number']; ?></h1>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <a href="/public/group-admin/lottery-payments.php?id=<?php echo $book['event_id']; ?>" style="margin-left: var(--spacing-md);">
                    Back to Payments →
                </a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Outstanding Amount Alert -->
        <div class="alert alert-warning" style="font-size: var(--font-size-lg); text-align: center;">
            <strong>Outstanding Amount:</strong>
            <span style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--danger-color); display: block; margin-top: var(--spacing-sm);">
                ₹<?php echo number_format($outstanding); ?>
            </span>
        </div>

        <div class="responsive-grid-2">
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Book & Member Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Event:</strong> <?php echo htmlspecialchars($book['event_name']); ?></div>
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Book:</strong> #<?php echo $book['book_number']; ?></div>
                            <?php
                            // Display dynamic level values
                            if (!empty($book['distribution_path'])) {
                                $levelValues = explode(' > ', $book['distribution_path']);
                                foreach ($levels as $index => $level) {
                                    $value = $levelValues[$index] ?? '-';
                                    echo '<div style="margin-bottom: var(--spacing-sm);"><strong>' . htmlspecialchars($level['level_name']) . ':</strong> ' . htmlspecialchars($value) . '</div>';
                                }
                            }
                            ?>
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Notes:</strong> <?php echo htmlspecialchars($book['notes'] ?? '-'); ?></div>
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Mobile:</strong> <?php echo htmlspecialchars($book['mobile_number'] ?? '-'); ?></div>
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Expected:</strong> ₹<?php echo number_format($expectedAmount); ?></div>
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Already Paid:</strong> <span style="color: var(--success-color);">₹<?php echo number_format($book['total_paid']); ?></span></div>
                            <div><strong>Outstanding:</strong> <span style="color: var(--danger-color); font-size: var(--font-size-xl); font-weight: 700;">₹<?php echo number_format($outstanding); ?></span></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Record Payment</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="paymentForm">
                            <div class="form-group">
                                <label class="form-label form-label-required">Payment Type</label>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md); margin-top: var(--spacing-sm);">
                                    <label style="display: flex; align-items: center; cursor: pointer; padding: var(--spacing-sm); border: 2px solid var(--gray-300); border-radius: var(--radius-md);">
                                        <input type="radio" name="payment_type" value="full" <?php echo ($outstanding > 0) ? '' : 'checked'; ?> onchange="togglePaymentFields('full')" style="margin-right: var(--spacing-sm);">
                                        <span>💰 Full Payment<br><small style="color: var(--gray-600);">₹<?php echo number_format($outstanding); ?></small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer; padding: var(--spacing-sm); border: 2px solid var(--gray-300); border-radius: var(--radius-md);">
                                        <input type="radio" name="payment_type" value="partial" checked onchange="togglePaymentFields('partial')" style="margin-right: var(--spacing-sm);">
                                        <span>💵 Partial Payment<br><small style="color: var(--gray-600);">Any amount</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer; padding: var(--spacing-sm); border: 2px solid var(--danger-color); border-radius: var(--radius-md); background: var(--danger-light);">
                                        <input type="radio" name="payment_type" value="no_payment" onchange="togglePaymentFields('no_payment')" style="margin-right: var(--spacing-sm);">
                                        <span>📚 No Payment<br><small style="color: var(--danger-color);">Books Returned</small></span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Amount Received</label>
                                <input
                                    type="number"
                                    name="amount"
                                    id="amountInput"
                                    class="form-control"
                                    step="1"
                                    min="1"
                                    max="<?php echo $outstanding; ?>"
                                    value=""
                                    required
                                    autofocus
                                >
                                <small class="form-text">Outstanding: ₹<?php echo number_format($outstanding); ?></small>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Payment Method</label>
                                <div class="payment-method-grid">
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="cash">
                                        <span>💵 Cash</span>
                                    </label>
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="upi" checked>
                                        <span>📱 UPI</span>
                                    </label>
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="bank">
                                        <span>🏦 Bank Transfer</span>
                                    </label>
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="other">
                                        <span>💳 Other</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Payment Date</label>
                                <input
                                    type="date"
                                    name="payment_date"
                                    class="form-control"
                                    value="<?php echo date('Y-m-d'); ?>"
                                    max="<?php echo date('Y-m-d'); ?>"
                                    required
                                >
                            </div>

                            <!-- Return Reason Field (shown only for no_payment type) -->
                            <div class="form-group" id="returnReasonField" style="display: none;">
                                <label class="form-label form-label-required" style="color: var(--danger-color);">Reason for Book Return</label>
                                <textarea
                                    name="return_reason"
                                    id="returnReasonTextarea"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Please provide a clear reason why the books were returned (e.g., member couldn't sell tickets, member relocated, etc.)"
                                ></textarea>
                                <small class="form-text" style="color: var(--danger-color);">This will mark the book as returned and make it available for reassignment</small>
                            </div>

                            <div class="button-group-mobile">
                                <button type="submit" class="btn btn-success btn-lg" id="submitButton">Record Payment</button>
                                <a href="/public/group-admin/lottery-payments.php?id=<?php echo $book['event_id']; ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">💡 Tip</h4>
                    </div>
                    <div class="card-body">
                        <p>You can collect payments in multiple installments. Each payment will be recorded separately.</p>
                        <p>Once the total paid equals or exceeds the expected amount, the book will be marked as "Paid".</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const outstanding = <?php echo $outstanding; ?>;
        const amountInput = document.getElementById('amountInput');
        const paymentMethodGroup = document.querySelector('.payment-method-grid').parentElement;
        const returnReasonField = document.getElementById('returnReasonField');
        const returnReasonTextarea = document.getElementById('returnReasonTextarea');
        const submitButton = document.getElementById('submitButton');
        const amountGroup = amountInput.parentElement;

        function togglePaymentFields(type) {
            if (type === 'no_payment') {
                // Hide payment fields
                amountGroup.style.display = 'none';
                paymentMethodGroup.style.display = 'none';

                // Show return reason field
                returnReasonField.style.display = 'block';
                returnReasonTextarea.required = true;

                // Update button text
                submitButton.textContent = '📚 Record Book Return';
                submitButton.className = 'btn btn-danger btn-lg';
            } else {
                // Show payment fields
                amountGroup.style.display = 'block';
                paymentMethodGroup.style.display = 'block';

                // Hide return reason field
                returnReasonField.style.display = 'none';
                returnReasonTextarea.required = false;

                // Update button text
                submitButton.textContent = '💰 Record Payment';
                submitButton.className = 'btn btn-success btn-lg';

                // Update amount based on type
                updateAmount(type);
            }
        }

        function updateAmount(type) {
            if (type === 'full') {
                amountInput.value = outstanding;
                amountInput.readOnly = true;
                amountInput.style.backgroundColor = '#f3f4f6';
            } else {
                amountInput.value = '';
                amountInput.readOnly = false;
                amountInput.style.backgroundColor = '';
                amountInput.focus();
            }
        }

        // Initialize on page load
        updateAmount();

        // Add visual feedback for selected payment method
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('label:has(input[name="payment_method"])').forEach(label => {
                    label.style.borderColor = '#e5e7eb';
                    label.style.backgroundColor = '';
                });
                this.parentElement.style.borderColor = '#3b82f6';
                this.parentElement.style.backgroundColor = '#eff6ff';
            });
        });

        // Trigger initial selection
        document.querySelector('input[name="payment_method"]:checked').dispatchEvent(new Event('change'));
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
