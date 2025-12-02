<?php
/**
 * Commission Report
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

// Check if commission is enabled
$settingsQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id AND commission_enabled = 1";
$stmt = $db->prepare($settingsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$settings = $stmt->fetch();

if (!$settings) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Get commission summary by Level 1
$summaryQuery = "SELECT
                 level_1_value,
                 COUNT(*) as transaction_count,
                 SUM(CASE WHEN commission_type = 'early' THEN commission_amount ELSE 0 END) as early_commission,
                 SUM(CASE WHEN commission_type = 'standard' THEN commission_amount ELSE 0 END) as standard_commission,
                 SUM(CASE WHEN commission_type = 'extra_books' THEN commission_amount ELSE 0 END) as extra_books_commission,
                 SUM(commission_amount) as total_commission
                 FROM commission_earned
                 WHERE event_id = :event_id
                 GROUP BY level_1_value
                 ORDER BY level_1_value";
$stmt = $db->prepare($summaryQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$summary = $stmt->fetchAll();

// Get detailed transactions
$detailsQuery = "SELECT ce.*, lb.book_number
                 FROM commission_earned ce
                 LEFT JOIN lottery_books lb ON ce.book_id = lb.book_id
                 WHERE ce.event_id = :event_id
                 ORDER BY ce.payment_date DESC, ce.level_1_value";
$stmt = $db->prepare($detailsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$details = $stmt->fetchAll();

// Calculate totals
$grandTotal = array_sum(array_column($summary, 'total_commission'));
$totalEarly = array_sum(array_column($summary, 'early_commission'));
$totalStandard = array_sum(array_column($summary, 'standard_commission'));
$totalExtra = array_sum(array_column($summary, 'extra_books_commission'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Report - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: var(--spacing-xl) 0; margin-bottom: var(--spacing-xl);">
        <div class="container">
            <h1>💰 <?php echo htmlspecialchars($event['event_name']); ?> - Commission Report</h1>
            <p style="margin: 0; opacity: 0.9;">Level 1 Distributor Commissions</p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Commission Summary Cards -->
        <div class="stats-bar" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
            <div class="stat-box" style="background: white; padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center; border: 2px solid #10b981;">
                <div style="font-size: var(--font-size-sm); color: var(--gray-600); margin-bottom: var(--spacing-xs);">Early Payment (<?php echo $settings['early_commission_percent']; ?>%)</div>
                <div style="font-size: var(--font-size-2xl); font-weight: 700; color: #10b981;">₹<?php echo number_format($totalEarly); ?></div>
            </div>
            <div class="stat-box" style="background: white; padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center; border: 2px solid #f59e0b;">
                <div style="font-size: var(--font-size-sm); color: var(--gray-600); margin-bottom: var(--spacing-xs);">Standard (<?php echo $settings['standard_commission_percent']; ?>%)</div>
                <div style="font-size: var(--font-size-2xl); font-weight: 700; color: #f59e0b;">₹<?php echo number_format($totalStandard); ?></div>
            </div>
            <div class="stat-box" style="background: white; padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center; border: 2px solid #8b5cf6;">
                <div style="font-size: var(--font-size-sm); color: var(--gray-600); margin-bottom: var(--spacing-xs);">Extra Books (<?php echo $settings['extra_books_commission_percent']; ?>%)</div>
                <div style="font-size: var(--font-size-2xl); font-weight: 700; color: #8b5cf6;">₹<?php echo number_format($totalExtra); ?></div>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: var(--font-size-sm); color: white; opacity: 0.9; margin-bottom: var(--spacing-xs);">Total Commission</div>
                <div style="font-size: var(--font-size-3xl); font-weight: 700; color: white;">₹<?php echo number_format($grandTotal); ?></div>
            </div>
        </div>

        <!-- Level 1 Summary -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Commission by Level 1 Distributor</h3>
                <a href="/public/group-admin/lottery-commission-export.php?id=<?php echo $eventId; ?>" class="btn btn-primary btn-sm">
                    📥 Export to CSV
                </a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($summary) === 0): ?>
                    <div style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                        <div style="font-size: 64px; margin-bottom: var(--spacing-md);">💰</div>
                        <h3>No Commission Earned Yet</h3>
                        <p>Commissions will appear here when payments are collected.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Level 1</th>
                                    <th>Transactions</th>
                                    <th>Early (<?php echo $settings['early_commission_percent']; ?>%)</th>
                                    <th>Standard (<?php echo $settings['standard_commission_percent']; ?>%)</th>
                                    <th>Extra (<?php echo $settings['extra_books_commission_percent']; ?>%)</th>
                                    <th>Total Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary as $row): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['level_1_value']); ?></strong></td>
                                        <td><?php echo $row['transaction_count']; ?></td>
                                        <td style="color: #10b981;">₹<?php echo number_format($row['early_commission']); ?></td>
                                        <td style="color: #f59e0b;">₹<?php echo number_format($row['standard_commission']); ?></td>
                                        <td style="color: #8b5cf6;">₹<?php echo number_format($row['extra_books_commission']); ?></td>
                                        <td><strong style="color: var(--success-color);">₹<?php echo number_format($row['total_commission']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background: var(--gray-50); font-weight: 700;">
                                    <td>GRAND TOTAL</td>
                                    <td><?php echo count($details); ?></td>
                                    <td style="color: #10b981;">₹<?php echo number_format($totalEarly); ?></td>
                                    <td style="color: #f59e0b;">₹<?php echo number_format($totalStandard); ?></td>
                                    <td style="color: #8b5cf6;">₹<?php echo number_format($totalExtra); ?></td>
                                    <td style="color: var(--success-color);">₹<?php echo number_format($grandTotal); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detailed Transactions -->
        <?php if (count($details) > 0): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detailed Commission Transactions (<?php echo count($details); ?>)</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Level 1</th>
                                <th>Book #</th>
                                <th>Type</th>
                                <th>Payment Amount</th>
                                <th>Rate</th>
                                <th>Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $detail): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($detail['payment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($detail['level_1_value']); ?></td>
                                    <td><?php echo $detail['book_number']; ?></td>
                                    <td>
                                        <?php
                                        $typeColors = [
                                            'early' => ['color' => '#10b981', 'icon' => '🏃', 'text' => 'Early'],
                                            'standard' => ['color' => '#f59e0b', 'icon' => '🚶', 'text' => 'Standard'],
                                            'extra_books' => ['color' => '#8b5cf6', 'icon' => '📚', 'text' => 'Extra Books']
                                        ];
                                        $type = $typeColors[$detail['commission_type']];
                                        echo '<span style="color: ' . $type['color'] . ';">' . $type['icon'] . ' ' . $type['text'] . '</span>';
                                        ?>
                                    </td>
                                    <td>₹<?php echo number_format($detail['payment_amount']); ?></td>
                                    <td><?php echo $detail['commission_percent']; ?>%</td>
                                    <td><strong style="color: var(--success-color);">₹<?php echo number_format($detail['commission_amount']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="button-group-mobile mt-3">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <a href="/public/group-admin/lottery-commission-setup.php?id=<?php echo $eventId; ?>" class="btn btn-primary">Commission Settings</a>
        </div>
    </div>
</body>
</html>
