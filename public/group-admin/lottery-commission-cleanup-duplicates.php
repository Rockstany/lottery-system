<?php
/**
 * Commission Cleanup - Remove Duplicate Commission Records
 * GetToKnow Community App
 *
 * This one-time tool removes duplicate commission records
 * that were created due to multiple payment entries
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

$success = '';
$error = '';
$cleanupResults = null;

// Handle cleanup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup') {
    try {
        $db->beginTransaction();

        // Find duplicate commission records (same distribution_id + commission_type)
        $findDuplicatesQuery = "SELECT distribution_id, commission_type, COUNT(*) as duplicate_count,
                                       GROUP_CONCAT(commission_id ORDER BY commission_id) as commission_ids
                                FROM commission_earned
                                WHERE event_id = ?
                                AND distribution_id IS NOT NULL
                                GROUP BY distribution_id, commission_type
                                HAVING duplicate_count > 1";

        $findStmt = $db->prepare($findDuplicatesQuery);
        $findStmt->execute([$eventId]);
        $duplicates = $findStmt->fetchAll();

        $totalDuplicates = 0;
        $totalRemoved = 0;

        foreach ($duplicates as $dup) {
            $commissionIds = explode(',', $dup['commission_ids']);
            // Keep the first record, delete the rest
            $keepId = array_shift($commissionIds);
            $deleteIds = $commissionIds;

            if (count($deleteIds) > 0) {
                $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
                $deleteQuery = "DELETE FROM commission_earned WHERE commission_id IN ($placeholders)";
                $deleteStmt = $db->prepare($deleteQuery);
                $deleteStmt->execute($deleteIds);

                $totalRemoved += $deleteStmt->rowCount();
                $totalDuplicates += ($dup['duplicate_count'] - 1);
            }
        }

        $db->commit();

        $cleanupResults = [
            'duplicate_groups' => count($duplicates),
            'records_removed' => $totalRemoved
        ];

        $success = "Cleanup completed! Removed {$totalRemoved} duplicate commission records from {$cleanupResults['duplicate_groups']} book(s).";

    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Cleanup failed: ' . $e->getMessage();
        error_log("Commission Cleanup Error: " . $e->getMessage());
    }
}

// Preview duplicate records
$previewQuery = "SELECT distribution_id, commission_type, COUNT(*) as duplicate_count,
                        MIN(commission_id) as keep_id,
                        GROUP_CONCAT(commission_id ORDER BY commission_id) as all_ids,
                        level_1_value,
                        commission_amount,
                        payment_date
                 FROM commission_earned
                 WHERE event_id = ?
                 AND distribution_id IS NOT NULL
                 GROUP BY distribution_id, commission_type
                 HAVING duplicate_count > 1
                 ORDER BY distribution_id, commission_type";

$previewStmt = $db->prepare($previewQuery);
$previewStmt->execute([$eventId]);
$duplicateRecords = $previewStmt->fetchAll();

// Get book numbers for display
$bookNumbers = [];
if (count($duplicateRecords) > 0) {
    $distIds = array_unique(array_column($duplicateRecords, 'distribution_id'));
    $placeholders = implode(',', array_fill(0, count($distIds), '?'));
    $bookQuery = "SELECT bd.distribution_id, lb.book_number
                  FROM book_distribution bd
                  JOIN lottery_books lb ON bd.book_id = lb.book_id
                  WHERE bd.distribution_id IN ($placeholders)";
    $bookStmt = $db->prepare($bookQuery);
    $bookStmt->execute($distIds);
    foreach ($bookStmt->fetchAll() as $row) {
        $bookNumbers[$row['distribution_id']] = $row['book_number'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Duplicate Commissions - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: var(--spacing-xl) 0; margin-bottom: var(--spacing-xl);">
        <div class="container">
            <h1>🧹 Cleanup Duplicate Commissions</h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($event['event_name']); ?></p>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <?php if ($cleanupResults): ?>
                    <ul style="margin: var(--spacing-sm) 0 0 var(--spacing-lg);">
                        <li>Duplicate groups found: <?php echo $cleanupResults['duplicate_groups']; ?></li>
                        <li>Commission records removed: <?php echo $cleanupResults['records_removed']; ?></li>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Warning Card -->
        <div class="card" style="margin-bottom: var(--spacing-lg); border-left: 4px solid var(--error-color);">
            <div class="card-body">
                <h3 style="margin-top: 0;">⚠️ Warning: One-Time Cleanup Tool</h3>
                <p>This tool removes duplicate commission records that were created due to a bug in the payment collection system.</p>

                <h4>What it does:</h4>
                <ul>
                    <li>Finds commission records with same <code>distribution_id</code> + <code>commission_type</code></li>
                    <li>Keeps the FIRST record (lowest earned_id)</li>
                    <li>Deletes all duplicate records</li>
                </ul>

                <div style="background: #fef2f2; padding: var(--spacing-md); border-radius: var(--radius-md); margin-top: var(--spacing-md); border: 1px solid #fecaca;">
                    <strong>Note:</strong> The duplicate prevention fix has already been applied to the payment collection system. This cleanup tool is only needed once to remove existing duplicates.
                </div>
            </div>
        </div>

        <!-- Duplicate Records Preview -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <div class="card-header">
                <h3 class="card-title">📋 Duplicate Commission Records (<?php echo count($duplicateRecords); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (count($duplicateRecords) === 0): ?>
                    <div style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                        <div style="font-size: 64px; margin-bottom: var(--spacing-md);">✅</div>
                        <h3>No Duplicate Commissions Found!</h3>
                        <p>All commission records are unique. No cleanup needed.</p>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: var(--spacing-md);">
                        <p><strong><?php echo count($duplicateRecords); ?> book(s)</strong> have duplicate commission records that need cleanup.</p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete duplicate commission records? This cannot be undone!');">
                            <input type="hidden" name="action" value="cleanup">
                            <button type="submit" class="btn btn-danger">🧹 Remove All Duplicates</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book #</th>
                                    <th>Level 1</th>
                                    <th>Commission Type</th>
                                    <th>Duplicate Count</th>
                                    <th>Commission Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($duplicateRecords as $record): ?>
                                    <tr>
                                        <td><strong><?php echo $bookNumbers[$record['distribution_id']] ?? 'N/A'; ?></strong></td>
                                        <td><?php echo htmlspecialchars($record['level_1_value']); ?></td>
                                        <td>
                                            <?php
                                            $typeColors = [
                                                'early' => ['color' => '#10b981', 'icon' => '🏃', 'text' => 'Early'],
                                                'standard' => ['color' => '#f59e0b', 'icon' => '🚶', 'text' => 'Standard'],
                                                'extra_books' => ['color' => '#8b5cf6', 'icon' => '📚', 'text' => 'Extra Books']
                                            ];
                                            $type = $typeColors[$record['commission_type']] ?? ['color' => '#6b7280', 'icon' => '❓', 'text' => $record['commission_type']];
                                            echo '<span style="color: ' . $type['color'] . ';">' . $type['icon'] . ' ' . $type['text'] . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-error"><?php echo $record['duplicate_count']; ?> records</span>
                                        </td>
                                        <td>₹<?php echo number_format($record['commission_amount']); ?></td>
                                        <td>
                                            <small style="color: var(--gray-600);">
                                                Keep ID: <?php echo $record['keep_id']; ?><br>
                                                Delete: <?php
                                                    $ids = explode(',', $record['all_ids']);
                                                    array_shift($ids);
                                                    echo implode(', ', $ids);
                                                ?>
                                            </small>
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
