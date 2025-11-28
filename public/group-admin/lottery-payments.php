<?php
/**
 * Lottery Payment Tracking - Part 5
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);

$database = new Database();
$db = $database->getConnection();

// Get event
$query = "SELECT * FROM lottery_events WHERE event_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $eventId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Get distributed books with payment info
$query = "SELECT lb.*, bd.member_name, bd.mobile_number,
          COALESCE(SUM(pc.amount_paid), 0) as total_paid,
          (lb.end_ticket_number - lb.start_ticket_number + 1) * :price_per_ticket as expected_amount
          FROM lottery_books lb
          JOIN book_distribution bd ON lb.book_id = bd.book_id
          LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
          WHERE lb.event_id = :event_id
          GROUP BY lb.book_id
          ORDER BY lb.book_number";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->bindParam(':price_per_ticket', $event['price_per_ticket']);
$stmt->execute();
$distributions = $stmt->fetchAll();

$totalCollected = 0;
$totalExpected = 0;
$paidCount = 0;
$partialCount = 0;
$unpaidCount = 0;

foreach ($distributions as $dist) {
    $totalCollected += $dist['total_paid'];
    $totalExpected += $dist['expected_amount'];
    if ($dist['total_paid'] >= $dist['expected_amount']) $paidCount++;
    elseif ($dist['total_paid'] > 0) $partialCount++;
    else $unpaidCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Tracking - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .header {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        .stat-box {
            background: white;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .stat-value {
            font-size: var(--font-size-2xl);
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo htmlspecialchars($event['event_name']); ?> - Payments</h1>
            <p style="margin: 0; opacity: 0.9;">Part 5 of 6</p>
        </div>
    </div>

    <div class="container main-content">
        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-value"><?php echo count($distributions); ?></div>
                <div class="stat-label">Distributed Books</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--success-color);"><?php echo $paidCount; ?></div>
                <div class="stat-label">Paid</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--warning-color);"><?php echo $partialCount; ?></div>
                <div class="stat-label">Partial</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--danger-color);"><?php echo $unpaidCount; ?></div>
                <div class="stat-label">Unpaid</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">₹<?php echo number_format($totalCollected, 0); ?></div>
                <div class="stat-label">Collected</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">₹<?php echo number_format($totalExpected, 0); ?></div>
                <div class="stat-label">Expected</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payment Tracking (<?php echo count($distributions); ?>)</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($distributions) === 0): ?>
                    <div style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                        <p>No books distributed yet.</p>
                        <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-primary">
                            Distribute Books First
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book #</th>
                                    <th>Member Name</th>
                                    <th>Mobile</th>
                                    <th>Expected</th>
                                    <th>Paid</th>
                                    <th>Outstanding</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($distributions as $dist): ?>
                                    <?php
                                    $outstanding = $dist['expected_amount'] - $dist['total_paid'];
                                    $status = 'unpaid';
                                    if ($dist['total_paid'] >= $dist['expected_amount']) $status = 'paid';
                                    elseif ($dist['total_paid'] > 0) $status = 'partial';
                                    ?>
                                    <tr>
                                        <td><strong>Book <?php echo $dist['book_number']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($dist['member_name']); ?></td>
                                        <td><?php echo htmlspecialchars($dist['mobile_number'] ?? '-'); ?></td>
                                        <td>₹<?php echo number_format($dist['expected_amount']); ?></td>
                                        <td>₹<?php echo number_format($dist['total_paid']); ?></td>
                                        <td>₹<?php echo number_format($outstanding); ?></td>
                                        <td>
                                            <?php if ($status === 'paid'): ?>
                                                <span class="badge badge-success">Paid</span>
                                            <?php elseif ($status === 'partial'): ?>
                                                <span class="badge badge-warning">Partial</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($status !== 'paid'): ?>
                                                <a href="/public/group-admin/lottery-payment-collect.php?book_id=<?php echo $dist['book_id']; ?>" class="btn btn-sm btn-success">
                                                    Collect Payment
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--success-color);">✓ Complete</span>
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

        <div style="margin-top: var(--spacing-lg);">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
        </div>
    </div>
</body>
</html>
