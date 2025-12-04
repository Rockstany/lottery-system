<?php
/**
 * Commission Sync - Recalculate Missing Commissions
 * GetToKnow Community App
 *
 * This tool finds fully paid books without commission records
 * and calculates commission based on the FIRST payment date
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

// Get commission settings
$settingsQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id AND commission_enabled = 1";
$stmt = $db->prepare($settingsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$settings = $stmt->fetch();

if (!$settings) {
    header("Location: /public/group-admin/lottery-commission-report.php?id=$eventId&error=not_enabled");
    exit;
}

$success = '';
$error = '';
$syncResults = null;

// Handle sync request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync') {
    try {
        $db->beginTransaction();

        // Find all fully paid distributions that don't have commission records
        // Use MAX(payment_date) to get the date when payment became fully paid
        $findQuery = "SELECT
                        bd.distribution_id,
                        bd.distribution_path,
                        bd.is_extra_book,
                        lb.book_id,
                        le.tickets_per_book,
                        le.price_per_ticket,
                        (le.tickets_per_book * le.price_per_ticket) as expected_amount,
                        COALESCE(SUM(pc.amount_paid), 0) as total_paid,
                        MAX(pc.payment_date) as full_payment_date
                      FROM book_distribution bd
                      JOIN lottery_books lb ON bd.book_id = lb.book_id
                      JOIN lottery_events le ON lb.event_id = le.event_id
                      LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
                      WHERE le.event_id = :event_id
                      GROUP BY bd.distribution_id
                      HAVING total_paid >= expected_amount
                      AND bd.distribution_id NOT IN (
                          SELECT DISTINCT ce.distribution_id
                          FROM commission_earned ce
                          WHERE ce.event_id = :event_id
                          AND ce.distribution_id IS NOT NULL
                      )";

        $findStmt = $db->prepare($findQuery);
        $findStmt->bindParam(':event_id', $eventId);
        $findStmt->execute();
        $missingCommissions = $findStmt->fetchAll();

        $synced = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($missingCommissions as $dist) {
            // Extract Level 1 value
            $level1Value = '';
            if (!empty($dist['distribution_path'])) {
                $pathParts = explode(' > ', $dist['distribution_path']);
                $level1Value = $pathParts[0] ?? '';
            }

            if (empty($level1Value)) {
                $skipped++;
                continue;
            }

            // Determine eligible commission types
            $eligibleCommissions = [];

            // Check extra book commission
            if ($dist['is_extra_book'] == 1 && $settings['extra_books_commission_enabled'] == 1) {
                $eligibleCommissions[] = [
                    'type' => 'extra_books',
                    'percent' => $settings['extra_books_commission_percent']
                ];
            }

            // Check date-based commission using LAST payment date (when fully paid)
            $fullPaymentDate = $dist['full_payment_date'];

            if ($settings['early_commission_enabled'] == 1 &&
                !empty($settings['early_payment_date']) &&
                $fullPaymentDate <= $settings['early_payment_date']) {
                $eligibleCommissions[] = [
                    'type' => 'early',
                    'percent' => $settings['early_commission_percent']
                ];
            }
            elseif ($settings['standard_commission_enabled'] == 1 &&
                    !empty($settings['standard_payment_date']) &&
                    $fullPaymentDate <= $settings['standard_payment_date']) {
                $eligibleCommissions[] = [
                    'type' => 'standard',
                    'percent' => $settings['standard_commission_percent']
                ];
            }

            // Insert commission records
            if (count($eligibleCommissions) > 0) {
                foreach ($eligibleCommissions as $commission) {
                    $commissionAmount = ($dist['expected_amount'] * $commission['percent']) / 100;

                    $insertQuery = "INSERT INTO commission_earned
                                   (event_id, distribution_id, level_1_value, commission_type,
                                    commission_percent, payment_amount, commission_amount,
                                    payment_date, book_id)
                                   VALUES (:event_id, :dist_id, :level_1, :comm_type,
                                           :comm_percent, :payment_amt, :comm_amt,
                                           :payment_date, :book_id)";
                    $insertStmt = $db->prepare($insertQuery);
                    $insertStmt->bindParam(':event_id', $eventId);
                    $insertStmt->bindParam(':dist_id', $dist['distribution_id']);
                    $insertStmt->bindParam(':level_1', $level1Value);
                    $insertStmt->bindParam(':comm_type', $commission['type']);
                    $insertStmt->bindParam(':comm_percent', $commission['percent']);
                    $insertStmt->bindParam(':payment_amt', $dist['expected_amount']);
                    $insertStmt->bindParam(':comm_amt', $commissionAmount);
                    $insertStmt->bindParam(':payment_date', $fullPaymentDate);
                    $insertStmt->bindParam(':book_id', $dist['book_id']);

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
            'total' => count($missingCommissions),
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors
        ];

        $success = "Commission sync completed! Processed {$syncResults['total']} books, synced {$synced} commissions.";

    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Sync failed: ' . $e->getMessage();
        error_log("Commission Sync Error: " . $e->getMessage());
    }
}

// Get preview of missing commissions
$previewQuery = "SELECT
                    bd.distribution_id,
                    bd.distribution_path,
                    bd.is_extra_book,
                    lb.book_number,
                    (le.tickets_per_book * le.price_per_ticket) as expected_amount,
                    COALESCE(SUM(pc.amount_paid), 0) as total_paid,
                    MIN(pc.payment_date) as first_payment_date,
                    MAX(pc.payment_date) as full_payment_date
                  FROM book_distribution bd
                  JOIN lottery_books lb ON bd.book_id = lb.book_id
                  JOIN lottery_events le ON lb.event_id = le.event_id
                  LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
                  WHERE le.event_id = :event_id
                  GROUP BY bd.distribution_id
                  HAVING total_paid >= expected_amount
                  AND bd.distribution_id NOT IN (
                      SELECT DISTINCT ce.distribution_id
                      FROM commission_earned ce
                      WHERE ce.event_id = :event_id
                      AND ce.distribution_id IS NOT NULL
                  )
                  ORDER BY full_payment_date ASC";

$previewStmt = $db->prepare($previewQuery);
$previewStmt->bindParam(':event_id', $eventId);
$previewStmt->execute();
$missingRecords = $previewStmt->fetchAll();
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
    <?php include __DIR__ . '/includes/navigation.php'; ?>

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
                        <li>Total fully paid books found: <?php echo $syncResults['total']; ?></li>
                        <li>Commissions synced: <?php echo $syncResults['synced']; ?></li>
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
                <p>This tool finds fully paid books that are <strong>missing commission records</strong> and calculates their commissions.</p>

                <h4>Why would commissions be missing?</h4>
                <ul>
                    <li><strong>Partial payments:</strong> Book paid in multiple installments (commission only calculated when fully paid)</li>
                    <li><strong>System updates:</strong> Commission system was enabled after books were already paid</li>
                    <li><strong>Database issues:</strong> Commission record creation failed during payment</li>
                </ul>

                <h4>How does it work?</h4>
                <ul>
                    <li>Finds all books where <code>total_paid >= expected_amount</code></li>
                    <li>Uses the <strong>LAST payment date</strong> (when book became fully paid) to determine commission eligibility</li>
                    <li>Checks <code>is_extra_book</code> flag for extra books commission</li>
                    <li>Creates missing commission records automatically</li>
                </ul>

                <div style="background: var(--gray-50); padding: var(--spacing-md); border-radius: var(--radius-md); margin-top: var(--spacing-md);">
                    <strong>Example:</strong> Book paid in 2 installments:<br>
                    - Payment 1: Jan 10 (₹300) - Partial, no commission yet<br>
                    - Payment 2: Jan 12 (₹200) - Completes full payment<br>
                    <strong>✅ Commission calculated using Jan 12 (when fully paid)</strong>
                </div>
            </div>
        </div>

        <!-- Missing Commissions Preview -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <div class="card-header">
                <h3 class="card-title">📋 Books Missing Commission Records (<?php echo count($missingRecords); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (count($missingRecords) === 0): ?>
                    <div style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                        <div style="font-size: 64px; margin-bottom: var(--spacing-md);">✅</div>
                        <h3>All Commissions Up to Date!</h3>
                        <p>No missing commission records found. All fully paid books have their commissions calculated.</p>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: var(--spacing-md);">
                        <p><strong><?php echo count($missingRecords); ?> book(s)</strong> are fully paid but missing commission records.</p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to sync <?php echo count($missingRecords); ?> commission record(s)?');">
                            <input type="hidden" name="action" value="sync">
                            <button type="submit" class="btn btn-primary">🔄 Sync <?php echo count($missingRecords); ?> Commission(s)</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book #</th>
                                    <th>Location</th>
                                    <th>Extra Book</th>
                                    <th>Expected</th>
                                    <th>Paid</th>
                                    <th>Full Payment Date</th>
                                    <th>Payment Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($missingRecords as $record): ?>
                                    <tr>
                                        <td><strong><?php echo $record['book_number']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($record['distribution_path']); ?></td>
                                        <td>
                                            <?php if ($record['is_extra_book'] == 1): ?>
                                                <span class="badge badge-info">✓ Extra</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Regular</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>₹<?php echo number_format($record['expected_amount']); ?></td>
                                        <td>₹<?php echo number_format($record['total_paid']); ?></td>
                                        <td><strong><?php echo date('M d, Y', strtotime($record['full_payment_date'])); ?></strong></td>
                                        <td>
                                            <?php
                                            if ($record['first_payment_date'] !== $record['full_payment_date']):
                                                echo '<span class="badge badge-warning">Multiple Payments</span>';
                                            else:
                                                echo '<span class="badge badge-success">Single Payment</span>';
                                            endif;
                                            ?>
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
            <a href="/public/group-admin/lottery-commission-report.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">← Back to Commission Report</a>
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">All Events</a>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
