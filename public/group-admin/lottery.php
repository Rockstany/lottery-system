<?php
/**
 * Lottery System - Events List
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$communityId = AuthMiddleware::getCommunityId();

if (!$communityId) {
    die('<div class="alert alert-danger">You are not assigned to any community.</div>');
}

$database = new Database();
$db = $database->getConnection();

// Get all lottery events
$query = "SELECT * FROM lottery_events WHERE community_id = :community_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$events = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lottery System - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
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
        .step-list {
            margin: var(--spacing-md) 0;
            padding-left: var(--spacing-xl);
        }
        .step-list li {
            margin: var(--spacing-sm) 0;
            font-size: var(--font-size-base);
        }
        .event-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
        }
        .event-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: var(--spacing-md);
        }
        .event-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: var(--spacing-md);
            margin: var(--spacing-md) 0;
        }
        .stat-item {
            text-align: center;
            padding: var(--spacing-sm);
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }
        .stat-value {
            font-size: var(--font-size-xl);
            font-weight: 700;
        }
        .stat-label {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin-top: var(--spacing-sm);
        }
        .progress-fill {
            height: 100%;
            background: var(--success-color);
            transition: width 0.3s ease;
        }
        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
        }
        .empty-icon {
            font-size: 64px;
            margin-bottom: var(--spacing-md);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🎫 Lottery System</h1>
            <p style="margin: 0; opacity: 0.9;">Manage Lottery Events & Books</p>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($success === 'created'): ?>
            <div class="alert alert-success">Event created successfully! Now generate books.</div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="instructions">
            <h3 style="margin-top: 0;">🎯 How Lottery System Works</h3>
            <p>Complete lottery management in 6 simple parts:</p>
            <ol class="step-list">
                <li><strong>Part 1: Create Event</strong> - Give it a name (e.g., "Diwali 2025")</li>
                <li><strong>Part 2: Generate Books</strong> - Auto-create lottery books with ticket numbers</li>
                <li><strong>Part 3: Distribution Settings</strong> - Set up levels (Wing → Floor → Flat)</li>
                <li><strong>Part 4: Distribute Books</strong> - Assign books to members/locations</li>
                <li><strong>Part 5: Collect Payments</strong> - Track full/partial payments</li>
                <li><strong>Part 6: View Reports</strong> - Complete analytics and exports</li>
            </ol>
            <p style="margin: 0;"><strong>💡 Tip:</strong> Follow each part in sequence for best results!</p>
        </div>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title" style="margin: 0;">Your Lottery Events (<?php echo count($events); ?>)</h3>
                <a href="/public/group-admin/lottery-create.php" class="btn btn-primary">
                    + Create New Event
                </a>
            </div>
        </div>

        <?php if (count($events) === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">🎫</div>
                <h3>No Lottery Events Yet</h3>
                <p style="color: var(--gray-600); max-width: 500px; margin: var(--spacing-md) auto;">
                    Get started by creating your first lottery event. Perfect for Diwali, New Year, or any community celebrations!
                </p>
                <a href="/public/group-admin/lottery-create.php" class="btn btn-primary btn-lg" style="margin-top: var(--spacing-lg);">
                    Create Your First Event
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <?php
                // Get event statistics
                $statsQuery = "SELECT
                    COUNT(*) as total_books,
                    SUM(CASE WHEN book_status = 'distributed' THEN 1 ELSE 0 END) as distributed_books,
                    SUM(CASE WHEN book_status = 'collected' THEN 1 ELSE 0 END) as collected_books
                    FROM lottery_books WHERE event_id = :event_id";
                $statsStmt = $db->prepare($statsQuery);
                $statsStmt->bindParam(':event_id', $event['event_id']);
                $statsStmt->execute();
                $stats = $statsStmt->fetch();

                // Get payment stats
                $paymentQuery = "SELECT COALESCE(SUM(pc.amount_paid), 0) as total_collected
                    FROM payment_collections pc
                    JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                    JOIN lottery_books lb ON bd.book_id = lb.book_id
                    WHERE lb.event_id = :event_id";
                $paymentStmt = $db->prepare($paymentQuery);
                $paymentStmt->bindParam(':event_id', $event['event_id']);
                $paymentStmt->execute();
                $paymentStats = $paymentStmt->fetch();

                $totalCollected = $paymentStats['total_collected'] ?? 0;
                $collectionPercent = $event['total_predicted_amount'] > 0
                    ? ($totalCollected / $event['total_predicted_amount']) * 100
                    : 0;
                ?>

                <div class="event-card">
                    <div class="event-header">
                        <div>
                            <h3 style="margin: 0;"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                            <p style="margin: var(--spacing-xs) 0; color: var(--gray-600);">
                                <?php echo htmlspecialchars($event['event_description'] ?? 'No description'); ?>
                            </p>
                            <p style="margin: 0; font-size: var(--font-size-sm); color: var(--gray-500);">
                                Created: <?php echo date('M d, Y', strtotime($event['created_at'])); ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($event['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php elseif ($event['status'] === 'draft'): ?>
                                <span class="badge badge-warning">Draft</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?php echo ucfirst($event['status']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="event-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $event['total_books']; ?></div>
                            <div class="stat-label">Total Books</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $event['total_tickets']; ?></div>
                            <div class="stat-label">Total Tickets</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['distributed_books'] ?? 0; ?></div>
                            <div class="stat-label">Distributed</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">₹<?php echo $event['price_per_ticket']; ?></div>
                            <div class="stat-label">Per Ticket</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">₹<?php echo number_format($totalCollected, 0); ?></div>
                            <div class="stat-label">Collected</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">₹<?php echo number_format($event['total_predicted_amount'], 0); ?></div>
                            <div class="stat-label">Expected</div>
                        </div>
                    </div>

                    <div style="margin-top: var(--spacing-md);">
                        <div style="display: flex; justify-content: space-between; font-size: var(--font-size-sm); color: var(--gray-600);">
                            <span>Collection Progress</span>
                            <span><?php echo number_format($collectionPercent, 1); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($collectionPercent, 100); ?>%;"></div>
                        </div>
                    </div>

                    <div style="display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-lg); flex-wrap: wrap;">
                        <a href="/public/group-admin/lottery-books.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary">
                            Manage Books
                        </a>
                        <a href="/public/group-admin/lottery-distribution.php?id=<?php echo $event['event_id']; ?>" class="btn btn-success">
                            Distribution
                        </a>
                        <a href="/public/group-admin/lottery-payments.php?id=<?php echo $event['event_id']; ?>" class="btn btn-info">
                            Payments
                        </a>
                        <a href="/public/group-admin/lottery-reports.php?id=<?php echo $event['event_id']; ?>" class="btn btn-secondary">
                            Reports
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
