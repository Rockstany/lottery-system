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
$query = "SELECT lb.*, bd.distribution_id, bd.member_name, bd.mobile_number,
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = Validator::sanitizeFloat($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');

    if ($amount <= 0) {
        $error = 'Please enter a valid amount';
    } else {
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
            $success = 'Payment recorded successfully!';
            // Refresh data
            $stmt = $db->prepare($query);
            $stmt->bindParam(':book_id', $bookId);
            $stmt->execute();
            $book = $stmt->fetch();
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

        <div class="row">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Book & Member Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Event:</strong> <?php echo htmlspecialchars($book['event_name']); ?></div>
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Book:</strong> #<?php echo $book['book_number']; ?></div>
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Member:</strong> <?php echo htmlspecialchars($book['member_name']); ?></div>
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
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label form-label-required">Amount Received</label>
                                <input
                                    type="number"
                                    name="amount"
                                    class="form-control"
                                    step="1"
                                    min="1"
                                    max="<?php echo $outstanding; ?>"
                                    value="<?php echo $outstanding; ?>"
                                    required
                                    autofocus
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Payment Method</label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="upi" selected>UPI</option>
                                    <option value="other">Other</option>
                                </select>
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

                            <div style="display: flex; gap: var(--spacing-md);">
                                <button type="submit" class="btn btn-success btn-lg">Record Payment</button>
                                <a href="/public/group-admin/lottery-payments.php?id=<?php echo $book['event_id']; ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-6">
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
</body>
</html>
