<?php
/**
 * Bulk Payment Collection
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);
$communityId = AuthMiddleware::getCommunityId();

if (!$eventId || !$communityId) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get event
$query = "SELECT * FROM lottery_events WHERE event_id = :id AND community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $eventId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Get distribution levels
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($levelsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$levels = $stmt->fetchAll();

$error = '';
$previewData = [];

// Handle filter and preview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview'])) {
    $level1Filter = Validator::sanitizeInt($_POST['level_1_filter'] ?? 0);
    $level2Filter = Validator::sanitizeInt($_POST['level_2_filter'] ?? 0);
    $level3Filter = Validator::sanitizeInt($_POST['level_3_filter'] ?? 0);
    $paymentMethod = Validator::sanitizeString($_POST['payment_method'] ?? 'cash');
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');

    // Build query to find unpaid/partially paid books
    $whereClause = "bd.event_id = :event_id AND bd.book_id IS NOT NULL";
    $params = ['event_id' => $eventId];

    if ($level1Filter > 0) {
        $whereClause .= " AND bd.level_1_value = :level1";
        $params['level1'] = $level1Filter;
    }
    if ($level2Filter > 0) {
        $whereClause .= " AND bd.level_2_value = :level2";
        $params['level2'] = $level2Filter;
    }
    if ($level3Filter > 0) {
        $whereClause .= " AND bd.level_3_value = :level3";
        $params['level3'] = $level3Filter;
    }

    // Get books matching filters
    $booksQuery = "SELECT
                    bd.distribution_id,
                    bd.book_id,
                    lb.book_number,
                    lb.start_ticket_number,
                    lb.end_ticket_number,
                    bd.distribution_path,
                    (SELECT COALESCE(SUM(pc.amount_paid), 0)
                     FROM payment_collections pc
                     WHERE pc.distribution_id = bd.distribution_id) as paid_amount,
                    (lb.end_ticket_number - lb.start_ticket_number + 1) * :price_per_ticket as book_value
                   FROM book_distribution bd
                   JOIN lottery_books lb ON bd.book_id = lb.book_id
                   WHERE $whereClause
                   ORDER BY lb.book_number";

    $stmt = $db->prepare($booksQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindParam(':price_per_ticket', $event['price_per_ticket']);
    $stmt->execute();
    $allBooks = $stmt->fetchAll();

    // Filter only unpaid or partially paid books
    $unpaidBooks = array_filter($allBooks, function($book) {
        return $book['paid_amount'] < $book['book_value'];
    });

    if (empty($unpaidBooks)) {
        $error = 'No unpaid books found matching the selected filters.';
    } else {
        $previewData = [
            'books' => $unpaidBooks,
            'payment_method' => $paymentMethod,
            'payment_date' => $paymentDate,
            'level_1_filter' => $level1Filter,
            'level_2_filter' => $level2Filter,
            'level_3_filter' => $level3Filter,
            'total_books' => count($unpaidBooks),
            'total_amount' => array_sum(array_map(function($b) {
                return $b['book_value'] - $b['paid_amount'];
            }, $unpaidBooks))
        ];
    }
}

// Handle bulk payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $bookIds = json_decode($_POST['book_ids'] ?? '[]', true);
    $paymentMethod = Validator::sanitizeString($_POST['payment_method'] ?? 'cash');
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');

    if (empty($bookIds)) {
        $error = 'No books selected for payment';
    } else {
        $db->beginTransaction();
        try {
            $successCount = 0;

            foreach ($bookIds as $bookData) {
                $distributionId = $bookData['distribution_id'];
                $amount = $bookData['amount'];

                // Insert payment record
                $insertQuery = "INSERT INTO payment_collections
                               (distribution_id, amount_paid, payment_method, payment_date, collected_by)
                               VALUES (:distribution_id, :amount_paid, :payment_method, :payment_date, :collected_by)";
                $stmt = $db->prepare($insertQuery);
                $stmt->bindParam(':distribution_id', $distributionId);
                $stmt->bindParam(':amount_paid', $amount);
                $stmt->bindParam(':payment_method', $paymentMethod);
                $stmt->bindParam(':payment_date', $paymentDate);
                $collectedBy = AuthMiddleware::getUserId();
                $stmt->bindParam(':collected_by', $collectedBy);
                $stmt->execute();

                $successCount++;
            }

            $db->commit();
            header("Location: /public/group-admin/lottery-payments.php?id=$eventId&success=bulk_collected&count=$successCount");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to process bulk payments: ' . $e->getMessage();
        }
    }
}

// Get level values for dropdowns
$levelValues = [];
foreach ($levels as $level) {
    $valuesQuery = "SELECT * FROM distribution_level_values WHERE level_id = :level_id ORDER BY value_name";
    $stmt = $db->prepare($valuesQuery);
    $stmt->bindParam(':level_id', $level['level_id']);
    $stmt->execute();
    $levelValues[$level['level_id']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Payment Collection - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
    <style>
        .header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }

        .instructions {
            background: var(--info-light);
            border-left: 4px solid var(--info-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }

        .calc-display {
            background: var(--success-light);
            border: 2px solid var(--success-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin: var(--spacing-lg) 0;
        }

        .calc-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-sm) 0;
            font-size: var(--font-size-lg);
        }

        .book-item {
            padding: var(--spacing-md);
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-sm);
        }

        .book-item:hover {
            background: var(--gray-50);
        }

        .book-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            margin-bottom: var(--spacing-xs);
        }

        .book-details {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }

        .badge-unpaid {
            background: var(--warning-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1>💰 <?php echo htmlspecialchars($event['event_name']); ?> - Bulk Payment Collection</h1>
            <p style="margin: 0; opacity: 0.9;">Collect full payments for multiple books at once</p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Back Button -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery-payments.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">← Back to Payments</a>
        </div>

        <div class="instructions">
            <h3 style="margin-top: 0;">📋 How Bulk Payment Collection Works</h3>
            <p><strong>Step 1:</strong> Select filters to find unpaid books (by Unit, Family, Member, etc.)</p>
            <p><strong>Step 2:</strong> Choose payment method and date</p>
            <p><strong>Step 3:</strong> Review the list of books that will be marked as paid</p>
            <p><strong>Step 4:</strong> Confirm to collect full payment for all books at once</p>
            <p style="margin-bottom: 0;"><strong>⚠️ Note:</strong> This feature only supports <strong>full payments</strong>. For partial payments, use the regular payment collection page.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($previewData)): ?>
            <!-- Filter Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Step 1: Select Filters & Payment Details</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <h4>Filter Books</h4>
                        <div class="row">
                            <?php foreach ($levels as $index => $level): ?>
                                <div class="col-4">
                                    <div class="form-group">
                                        <label class="form-label"><?php echo htmlspecialchars($level['level_name']); ?></label>
                                        <select name="level_<?php echo $level['level_number']; ?>_filter" class="form-control">
                                            <option value="0">All <?php echo htmlspecialchars($level['level_name']); ?></option>
                                            <?php foreach ($levelValues[$level['level_id']] ?? [] as $value): ?>
                                                <option value="<?php echo $value['value_id']; ?>">
                                                    <?php echo htmlspecialchars($value['value_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr style="margin: var(--spacing-lg) 0;">

                        <h4>Payment Details</h4>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label form-label-required">Payment Method</label>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="cash">💵 Cash</option>
                                        <option value="upi">📱 UPI</option>
                                        <option value="bank_transfer">🏦 Bank Transfer</option>
                                        <option value="cheque">📝 Cheque</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label form-label-required">Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control"
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="preview" class="btn btn-success btn-lg">
                            Preview Unpaid Books →
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Preview Section -->
            <div class="calc-display">
                <h3 style="margin-top: 0; color: var(--success-color);">✓ Payment Summary</h3>
                <div class="calc-row">
                    <span>Total Books Found:</span>
                    <strong><?php echo $previewData['total_books']; ?></strong>
                </div>
                <div class="calc-row">
                    <span>Payment Method:</span>
                    <strong><?php
                        $methods = [
                            'cash' => '💵 Cash',
                            'upi' => '📱 UPI',
                            'bank_transfer' => '🏦 Bank Transfer',
                            'cheque' => '📝 Cheque'
                        ];
                        echo $methods[$previewData['payment_method']] ?? $previewData['payment_method'];
                    ?></strong>
                </div>
                <div class="calc-row">
                    <span>Payment Date:</span>
                    <strong><?php echo date('d M Y', strtotime($previewData['payment_date'])); ?></strong>
                </div>
                <div class="calc-row" style="border-top: 2px solid var(--success-color); margin-top: var(--spacing-md); padding-top: var(--spacing-md);">
                    <span style="font-size: var(--font-size-xl);">Total Amount to Collect:</span>
                    <strong style="font-size: var(--font-size-2xl); color: var(--success-color);">
                        ₹<?php echo number_format($previewData['total_amount'], 2); ?>
                    </strong>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Step 2: Review Books (<?php echo $previewData['total_books']; ?>)</h3>
                </div>
                <div class="card-body">
                    <div style="max-height: 400px; overflow-y: auto; padding: var(--spacing-sm);">
                        <?php foreach ($previewData['books'] as $book): ?>
                            <div class="book-item">
                                <div class="book-header">
                                    <span>📖 Book #<?php echo $book['book_number']; ?>
                                        (Tickets: <?php echo $book['start_ticket_number']; ?>-<?php echo $book['end_ticket_number']; ?>)
                                    </span>
                                    <span class="badge-unpaid">
                                        ₹<?php echo number_format($book['book_value'] - $book['paid_amount'], 2); ?> Due
                                    </span>
                                </div>
                                <div class="book-details">
                                    <strong>Assigned to:</strong> <?php echo htmlspecialchars($book['distribution_path'] ?? 'Unassigned'); ?>
                                    <br>
                                    <strong>Book Value:</strong> ₹<?php echo number_format($book['book_value'], 2); ?>
                                    | <strong>Paid:</strong> ₹<?php echo number_format($book['paid_amount'], 2); ?>
                                    | <strong>Balance:</strong> ₹<?php echo number_format($book['book_value'] - $book['paid_amount'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="book_ids" value="<?php
                    echo htmlspecialchars(json_encode(array_map(function($b) {
                        return [
                            'distribution_id' => $b['distribution_id'],
                            'amount' => $b['book_value'] - $b['paid_amount']
                        ];
                    }, $previewData['books'])));
                ?>">
                <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($previewData['payment_method']); ?>">
                <input type="hidden" name="payment_date" value="<?php echo htmlspecialchars($previewData['payment_date']); ?>">

                <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                    <button type="submit" name="confirm" class="btn btn-success btn-lg">
                        ✓ Confirm & Collect ₹<?php echo number_format($previewData['total_amount'], 2); ?> →
                    </button>
                    <a href="/public/group-admin/lottery-payment-bulk.php?id=<?php echo $eventId; ?>" class="btn btn-secondary btn-lg">
                        ← Change Filters
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
