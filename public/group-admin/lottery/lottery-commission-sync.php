<?php
/**
 * Commission Sync - Recalculate Missing Commissions
 * GetToKnow Community App
 *
 * This tool finds fully paid books without commission records
 * and calculates commission based on the FIRST payment date
 */

require_once __DIR__ . '/../../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);
$communityId = AuthMiddleware::getCommunityId();

if (!$eventId || !$communityId) {
    header("Location: /public/group-admin/lottery/lottery.php");
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
    header("Location: /public/group-admin/lottery/lottery.php");
    exit;
}

// Get commission settings
$settingsQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id AND commission_enabled = 1";
$stmt = $db->prepare($settingsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$settings = $stmt->fetch();

if (!$settings) {
    header("Location: /public/group-admin/lottery/lottery-reports.php?id=$eventId");
    exit;
}

$success = '';
$error = '';
$syncResults = null;

// Handle sync request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync') {
    try {
        $db->beginTransaction();

        // STEP 1: Delete ALL existing commission records for this event
        $deleteQuery = "DELETE FROM commission_earned WHERE event_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute([$eventId]);
        $deletedCount = $deleteStmt->rowCount();

        // STEP 2: Find ALL payments and recalculate commissions from scratch
        // Now supports partial payments - calculates commission on each payment individually
        $findQuery = "SELECT
                        pc.payment_id,
                        pc.distribution_id,
                        pc.amount_paid,
                        pc.payment_date,
                        bd.distribution_path,
                        bd.is_extra_book,
                        lb.book_id,
                        le.event_id
                      FROM payment_collections pc
                      JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                      JOIN lottery_books lb ON bd.book_id = lb.book_id
                      JOIN lottery_events le ON lb.event_id = le.event_id
                      WHERE le.event_id = ? AND pc.amount_paid > 0
                      ORDER BY pc.payment_date ASC";

        $findStmt = $db->prepare($findQuery);
        $findStmt->execute([$eventId]);
        $allPayments = $findStmt->fetchAll();

        $synced = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($allPayments as $payment) {
            // Extract Level 1 value
            $level1Value = '';
            if (!empty($payment['distribution_path'])) {
                $pathParts = explode(' > ', $payment['distribution_path']);
                $level1Value = $pathParts[0] ?? '';
            }

            if (empty($level1Value)) {
                $skipped++;
                continue;
            }

            // Determine eligible commission types
            $eligibleCommissions = [];

            // Check extra book commission
            if ($payment['is_extra_book'] == 1 && $settings['extra_books_commission_enabled'] == 1) {
                $eligibleCommissions[] = [
                    'type' => 'extra_books',
                    'percent' => $settings['extra_books_commission_percent']
                ];
            }

            // Check date-based commission using payment date
            if ($settings['early_commission_enabled'] == 1 &&
                !empty($settings['early_payment_date']) &&
                $payment['payment_date'] <= $settings['early_payment_date']) {
                $eligibleCommissions[] = [
                    'type' => 'early',
                    'percent' => $settings['early_commission_percent']
                ];
            }
            elseif ($settings['standard_commission_enabled'] == 1 &&
                    !empty($settings['standard_payment_date']) &&
                    $payment['payment_date'] <= $settings['standard_payment_date']) {
                $eligibleCommissions[] = [
                    'type' => 'standard',
                    'percent' => $settings['standard_commission_percent']
                ];
            }

            // Insert commission records for each eligible type
            if (count($eligibleCommissions) > 0) {
                foreach ($eligibleCommissions as $commission) {
                    // Calculate commission on ACTUAL payment amount (not expected amount)
                    $commissionAmount = ($payment['amount_paid'] * $commission['percent']) / 100;

                    $insertQuery = "INSERT INTO commission_earned
                                   (event_id, distribution_id, level_1_value, commission_type,
                                    commission_percent, payment_amount, commission_amount,
                                    payment_date, book_id)
                                   VALUES (:event_id, :dist_id, :level_1, :comm_type,
                                           :comm_percent, :payment_amt, :comm_amt,
                                           :payment_date, :book_id)";
                    $insertStmt = $db->prepare($insertQuery);
                    $insertStmt->bindParam(':event_id', $payment['event_id']);
                    $insertStmt->bindParam(':dist_id', $payment['distribution_id']);
                    $insertStmt->bindParam(':level_1', $level1Value);
                    $insertStmt->bindParam(':comm_type', $commission['type']);
                    $insertStmt->bindParam(':comm_percent', $commission['percent']);
                    $insertStmt->bindParam(':payment_amt', $payment['amount_paid']);
                    $insertStmt->bindParam(':comm_amt', $commissionAmount);
                    $insertStmt->bindParam(':payment_date', $payment['payment_date']);
                    $insertStmt->bindParam(':book_id', $payment['book_id']);

                    if (!$insertStmt->execute()) {
                        $errors++;
                    }
                }
                $synced++;
            } else {
                $skipped++;
            }
        }

        $db->commit();

        $syncResults = [
            'deleted' => $deletedCount,
            'total' => count($allPayments),
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors
        ];

        $success = "Commission sync completed! Deleted {$deletedCount} old records, recalculated {$synced} commissions from {$syncResults['total']} payments (including partial payments).";

    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Sync failed: ' . $e->getMessage();
        error_log("Commission Sync Error: " . $e->getMessage());
    }
}

// Get preview of ALL payments (will be synced)
$previewQuery = "SELECT
                    pc.payment_id,
                    pc.amount_paid,
                    pc.payment_date,
                    bd.distribution_path,
                    bd.is_extra_book,
                    lb.book_number,
                    (le.tickets_per_book * le.price_per_ticket) as expected_amount
                  FROM payment_collections pc
                  JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                  JOIN lottery_books lb ON bd.book_id = lb.book_id
                  JOIN lottery_events le ON lb.event_id = le.event_id
                  WHERE le.event_id = ? AND pc.amount_paid > 0
                  ORDER BY pc.payment_date ASC";

$previewStmt = $db->prepare($previewQuery);
$previewStmt->execute([$eventId]);
$allPayments = $previewStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Sync - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <div class="header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: var(--spacing-xl) 0; margin-bottom: var(--spacing-xl);">
        <div class="container">
            <h1>🔄 Commission Sync Tool</h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($event['event_name']); ?></p>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <?php if ($syncResults): ?>
                    <ul style="margin: var(--spacing-sm) 0 0 var(--spacing-lg);">
                        <li>Old commission records deleted: <?php echo $syncResults['deleted']; ?></li>
                        <li>Total payments found: <?php echo $syncResults['total']; ?></li>
                        <li>Commissions calculated: <?php echo $syncResults['synced']; ?></li>
                        <li>Skipped (no Level 1 or no eligible commission): <?php echo $syncResults['skipped']; ?></li>
                        <?php if ($syncResults['errors'] > 0): ?>
                            <li style="color: var(--error-color);">Errors: <?php echo $syncResults['errors']; ?></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Explanation Card -->
        <div class="card" style="margin-bottom: var(--spacing-lg); border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <h3 style="margin-top: 0;">📖 What is Commission Sync?</h3>
                <p>This tool recalculates commissions for <strong>ALL payments</strong> (both partial and full) from scratch.</p>

                <h4>When should you use this?</h4>
                <ul>
                    <li><strong>Data cleanup:</strong> Commission records are incorrect or duplicated</li>
                    <li><strong>Settings changed:</strong> Commission percentages or dates were updated</li>
                    <li><strong>System migration:</strong> Commission system was enabled after books were already paid</li>
                    <li><strong>Database issues:</strong> Commission calculation failed during payment collection</li>
                </ul>

                <h4>How does it work?</h4>
                <ul>
                    <li><strong>Step 1:</strong> DELETES all existing commission records for this event</li>
                    <li><strong>Step 2:</strong> Finds ALL payments (partial and full) from <code>payment_collections</code> table</li>
                    <li><strong>Step 3:</strong> Calculates commission on <strong>each payment amount</strong> individually</li>
                    <li><strong>Step 4:</strong> Creates new commission records with correct amounts</li>
                </ul>

                <div style="background: var(--gray-50); padding: var(--spacing-md); border-radius: var(--radius-md); margin-top: var(--spacing-md);">
                    <strong>Example:</strong> Book value ₹500, paid in 2 installments:<br>
                    - Payment 1: Jan 10 (₹300, Early commission 10%) → Commission: ₹30<br>
                    - Payment 2: Jan 20 (₹200, Standard commission 5%) → Commission: ₹10<br>
                    <strong>✅ Total commission: ₹40 (calculated on actual payment amounts)</strong>
                </div>
            </div>
        </div>

        <!-- All Payments Preview -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <div class="card-header">
                <h3 class="card-title">📋 All Payments (<?php echo count($allPayments); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (count($allPayments) === 0): ?>
                    <div style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                        <div style="font-size: 64px; margin-bottom: var(--spacing-md);">💰</div>
                        <h3>No Payments Found</h3>
                        <p>Commission records will be calculated when payments are collected.</p>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: var(--spacing-md);">
                        <p><strong><?php echo count($allPayments); ?> payment(s)</strong> found and will have commissions recalculated.</p>
                        <p style="color: var(--warning-color); margin: var(--spacing-sm) 0;"><strong>⚠️ Warning:</strong> This will DELETE all existing commission records and recalculate them fresh based on actual payment amounts.</p>
                        <form method="POST" onsubmit="return confirm('This will DELETE all existing commission records and recalculate them from ALL payments. Are you sure?');">
                            <input type="hidden" name="action" value="sync">
                            <button type="submit" class="btn btn-primary">🔄 Reset & Recalculate All Commissions</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book #</th>
                                    <th>Location</th>
                                    <th>Extra Book</th>
                                    <th>Payment Amount</th>
                                    <th>Payment Date</th>
                                    <th>Payment Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allPayments as $payment): ?>
                                    <?php
                                    $paymentType = 'Full Payment';
                                    if ($payment['amount_paid'] < $payment['expected_amount']) {
                                        $paymentType = 'Partial Payment';
                                    }
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $payment['book_number']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($payment['distribution_path']); ?></td>
                                        <td>
                                            <?php if ($payment['is_extra_book'] == 1): ?>
                                                <span class="badge badge-info">✓ Extra</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Regular</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong>₹<?php echo number_format($payment['amount_paid']); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <?php if ($paymentType === 'Partial Payment'): ?>
                                                <span class="badge badge-warning">Partial</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Full</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="button-group-mobile">
            <a href="/public/group-admin/lottery/lottery-reports.php?id=<?php echo $eventId; ?>#commission" class="btn btn-secondary" onclick="setTimeout(() => document.querySelector('.tab[onclick*=commission]')?.click(), 100)">← Back to Commission Report</a>
            <a href="/public/group-admin/lottery/lottery.php" class="btn btn-secondary">All Events</a>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
