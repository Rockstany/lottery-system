<?php
/**
 * Lottery Books List & Distribution - Part 4
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

// Get all books
$query = "SELECT lb.*, bd.member_name, bd.mobile_number
          FROM lottery_books lb
          LEFT JOIN book_distribution bd ON lb.book_id = bd.book_id
          WHERE lb.event_id = :event_id
          ORDER BY lb.book_number";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$books = $stmt->fetchAll();

// Stats
$availableCount = 0;
$distributedCount = 0;
foreach ($books as $book) {
    if ($book['book_status'] === 'available') $availableCount++;
    elseif ($book['book_status'] === 'distributed') $distributedCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lottery Books - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
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
            <h1><?php echo htmlspecialchars($event['event_name']); ?> - Books</h1>
            <p style="margin: 0; opacity: 0.9;">Part 4 of 6</p>
        </div>
    </div>

    <div class="container main-content">
        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-value"><?php echo count($books); ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--success-color);"><?php echo $availableCount; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--info-color);"><?php echo $distributedCount; ?></div>
                <div class="stat-label">Distributed</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">₹<?php echo number_format($event['price_per_ticket'] * $event['tickets_per_book']); ?></div>
                <div class="stat-label">Per Book</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Books (<?php echo count($books); ?>)</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Book #</th>
                                <th>Tickets</th>
                                <th>Ticket Range</th>
                                <th>Assigned To</th>
                                <th>Mobile</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><strong>Book <?php echo $book['book_number']; ?></strong></td>
                                    <td><?php echo $event['tickets_per_book']; ?></td>
                                    <td><?php echo $book['start_ticket_number']; ?> - <?php echo $book['end_ticket_number']; ?></td>
                                    <td><?php echo htmlspecialchars($book['member_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($book['mobile_number'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($book['book_status'] === 'available'): ?>
                                            <span class="badge badge-success">Available</span>
                                        <?php elseif ($book['book_status'] === 'distributed'): ?>
                                            <span class="badge badge-info">Distributed</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Collected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($book['book_status'] === 'available'): ?>
                                            <a href="/public/group-admin/lottery-book-assign.php?book_id=<?php echo $book['book_id']; ?>&event_id=<?php echo $eventId; ?>"
                                               class="btn btn-sm btn-primary">Assign</a>
                                        <?php else: ?>
                                            <a href="/public/group-admin/lottery-payment-collect.php?book_id=<?php echo $book['book_id']; ?>"
                                               class="btn btn-sm btn-success">Collect Payment</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="margin-top: var(--spacing-lg);">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <a href="/public/group-admin/lottery-payments.php?id=<?php echo $eventId; ?>" class="btn btn-success">Track Payments →</a>
        </div>
    </div>
</body>
</html>
