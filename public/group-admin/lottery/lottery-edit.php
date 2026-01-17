<?php
/**
 * Edit Lottery Event
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$communityId = AuthMiddleware::getCommunityId();

if (!$communityId) {
    die('<div class="alert alert-danger">You are not assigned to any community.</div>');
}

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);

$database = new Database();
$db = $database->getConnection();

// Get event details
$query = "SELECT * FROM lottery_events WHERE event_id = :event_id AND community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery/lottery.php");
    exit;
}

// Check if books have been generated (determines what can be edited)
$booksGenerated = $event['total_books'] > 0;

// Get distribution levels for this event
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($levelsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$distributionLevels = $stmt->fetchAll();

// Get count of values for each level
$levelValueCounts = [];
foreach ($distributionLevels as $level) {
    $countQuery = "SELECT COUNT(*) as value_count FROM distribution_level_values WHERE level_id = :level_id";
    $stmt = $db->prepare($countQuery);
    $stmt->bindParam(':level_id', $level['level_id']);
    $stmt->execute();
    $result = $stmt->fetch();
    $levelValueCounts[$level['level_id']] = $result['value_count'];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventName = Validator::sanitizeString($_POST['event_name'] ?? '');
    $description = Validator::sanitizeString($_POST['description'] ?? '');

    if (empty($eventName)) {
        $error = 'Event name is required';
    } else {
        // Always allow editing name and description
        $updateQuery = "UPDATE lottery_events SET event_name = :name, event_description = :description";
        $params = [
            ':name' => $eventName,
            ':description' => $description,
            ':event_id' => $eventId,
            ':community_id' => $communityId
        ];

        // If books haven't been generated yet, allow editing all fields
        if (!$booksGenerated) {
            $totalBooks = Validator::sanitizeInt($_POST['total_books'] ?? 0);
            $ticketsPerBook = Validator::sanitizeInt($_POST['tickets_per_book'] ?? 0);
            $pricePerTicket = Validator::sanitizeFloat($_POST['price_per_ticket'] ?? 0);
            $firstTicketNumber = Validator::sanitizeInt($_POST['first_ticket_number'] ?? 0);

            // Validate book configuration
            if ($totalBooks <= 0 || $ticketsPerBook <= 0 || $pricePerTicket <= 0 || $firstTicketNumber < 0) {
                $error = 'All book configuration fields must be valid numbers';
            } else {
                $updateQuery .= ", total_books = :total_books, tickets_per_book = :tickets_per_book,
                                  price_per_ticket = :price_per_ticket, first_ticket_number = :first_ticket_number";
                $params[':total_books'] = $totalBooks;
                $params[':tickets_per_book'] = $ticketsPerBook;
                $params[':price_per_ticket'] = $pricePerTicket;
                $params[':first_ticket_number'] = $firstTicketNumber;
            }
        }

        if (!$error) {
            $updateQuery .= " WHERE event_id = :event_id AND community_id = :community_id";
            $stmt = $db->prepare($updateQuery);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($stmt->execute()) {
                $success = 'Event updated successfully!';
                // Refresh event data
                $refreshStmt = $db->prepare("SELECT * FROM lottery_events WHERE event_id = :event_id");
                $refreshStmt->bindParam(':event_id', $eventId);
                $refreshStmt->execute();
                $event = $refreshStmt->fetch();
            } else {
                $error = 'Failed to update event';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lottery Event - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
    <style>
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .info-box {
            background: var(--info-light);
            border-left: 4px solid var(--info-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .locked-field {
            background-color: #f3f4f6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1>Edit Lottery Event</h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($event['event_name']); ?></p>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <a href="/public/group-admin/lottery/lottery.php" style="margin-left: var(--spacing-md);">
                    Back to Events →
                </a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($booksGenerated): ?>
            <div class="warning-box">
                <h4 style="margin-top: 0;">⚠️ Limited Editing - Books Already Generated</h4>
                <p style="margin: 0;">
                    Since lottery books have been created for this event, you can only edit the <strong>Event Name</strong>
                    and <strong>Description</strong>. Book configuration (total books, tickets per book, price, etc.) cannot be changed.
                </p>
            </div>
        <?php else: ?>
            <div class="info-box">
                <h4 style="margin-top: 0;">ℹ️ Draft Event - Full Editing Available</h4>
                <p style="margin: 0;">
                    This event is still in draft mode (no books generated). You can edit all fields including book configuration.
                </p>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Event Details</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label form-label-required">Event Name</label>
                                <input
                                    type="text"
                                    name="event_name"
                                    class="form-control"
                                    placeholder="e.g., Diwali 2025 Lottery"
                                    value="<?php echo htmlspecialchars($event['event_name']); ?>"
                                    required
                                    autofocus
                                >
                                <small class="form-text">✅ Can always be edited</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Add any notes about this event (optional)"
                                ><?php echo htmlspecialchars($event['event_description'] ?? ''); ?></textarea>
                                <small class="form-text">✅ Can always be edited</small>
                            </div>

                            <?php if (!$booksGenerated): ?>
                                <hr style="margin: var(--spacing-xl) 0;">
                                <h4 style="margin-bottom: var(--spacing-lg);">Book Configuration</h4>

                                <div class="form-group">
                                    <label class="form-label form-label-required">Total Number of Books</label>
                                    <input
                                        type="number"
                                        name="total_books"
                                        class="form-control"
                                        placeholder="e.g., 100"
                                        min="1"
                                        value="<?php echo $event['total_books']; ?>"
                                        required
                                    >
                                    <small class="form-text">✅ Can be edited (books not generated yet)</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label form-label-required">Tickets Per Book</label>
                                    <input
                                        type="number"
                                        name="tickets_per_book"
                                        class="form-control"
                                        placeholder="e.g., 10"
                                        min="1"
                                        value="<?php echo $event['tickets_per_book']; ?>"
                                        required
                                    >
                                    <small class="form-text">✅ Can be edited (books not generated yet)</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label form-label-required">Price Per Ticket (₹)</label>
                                    <input
                                        type="number"
                                        name="price_per_ticket"
                                        class="form-control"
                                        placeholder="e.g., 50"
                                        min="0"
                                        step="0.01"
                                        value="<?php echo $event['price_per_ticket']; ?>"
                                        required
                                    >
                                    <small class="form-text">✅ Can be edited (books not generated yet)</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label form-label-required">First Ticket Number</label>
                                    <input
                                        type="number"
                                        name="first_ticket_number"
                                        class="form-control"
                                        placeholder="e.g., 1"
                                        min="0"
                                        value="<?php echo $event['first_ticket_number']; ?>"
                                        required
                                    >
                                    <small class="form-text">✅ Can be edited (books not generated yet)</small>
                                </div>
                            <?php else: ?>
                                <hr style="margin: var(--spacing-xl) 0;">
                                <h4 style="margin-bottom: var(--spacing-lg);">Book Configuration (Read-Only)</h4>

                                <div class="form-group">
                                    <label class="form-label">Total Number of Books</label>
                                    <input
                                        type="number"
                                        class="form-control locked-field"
                                        value="<?php echo $event['total_books']; ?>"
                                        readonly
                                    >
                                    <small class="form-text" style="color: #dc2626;">🔒 Cannot be changed (books already generated)</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Tickets Per Book</label>
                                    <input
                                        type="number"
                                        class="form-control locked-field"
                                        value="<?php echo $event['tickets_per_book']; ?>"
                                        readonly
                                    >
                                    <small class="form-text" style="color: #dc2626;">🔒 Cannot be changed (books already generated)</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Price Per Ticket (₹)</label>
                                    <input
                                        type="number"
                                        class="form-control locked-field"
                                        value="<?php echo $event['price_per_ticket']; ?>"
                                        readonly
                                    >
                                    <small class="form-text" style="color: #dc2626;">🔒 Cannot be changed (books already generated)</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">First Ticket Number</label>
                                    <input
                                        type="number"
                                        class="form-control locked-field"
                                        value="<?php echo $event['first_ticket_number']; ?>"
                                        readonly
                                    >
                                    <small class="form-text" style="color: #dc2626;">🔒 Cannot be changed (books already generated)</small>
                                </div>
                            <?php endif; ?>

                            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Update Event
                                </button>
                                <a href="/public/group-admin/lottery/lottery.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Event Status</h4>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong>Status:</strong>
                            <?php if ($event['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php elseif ($event['status'] === 'draft'): ?>
                                <span class="badge badge-warning">Draft</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?php echo ucfirst($event['status']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong>Books Generated:</strong>
                            <?php if ($booksGenerated): ?>
                                <span style="color: var(--success-color);">✅ Yes (<?php echo $event['total_books']; ?> books)</span>
                            <?php else: ?>
                                <span style="color: var(--warning-color);">⚠️ No</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-bottom: var(--spacing-md);">
                            <strong>Total Tickets:</strong> <?php echo number_format($event['total_tickets']); ?>
                        </div>
                        <div>
                            <strong>Expected Amount:</strong> ₹<?php echo number_format($event['total_predicted_amount'], 2); ?>
                        </div>
                    </div>
                </div>

                <?php if (count($distributionLevels) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">📍 Distribution Levels (Step 3)</h4>
                        </div>
                        <div class="card-body">
                            <p style="margin-bottom: var(--spacing-md);"><strong>Configured Levels:</strong></p>
                            <ul style="margin: 0; padding-left: var(--spacing-lg);">
                                <?php foreach ($distributionLevels as $level): ?>
                                    <li style="margin-bottom: var(--spacing-sm);">
                                        <strong>Level <?php echo $level['level_number']; ?>:</strong>
                                        <?php echo htmlspecialchars($level['level_name']); ?>
                                        <span style="color: var(--gray-600); font-size: var(--font-size-sm);">
                                            (<?php echo $levelValueCounts[$level['level_id']]; ?> values)
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p style="margin-top: var(--spacing-md); color: var(--info-color); font-size: var(--font-size-sm);">
                                💡 New values can be added during book assignment
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">💡 Editing Rules</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Always Editable:</strong></p>
                        <ul style="margin: var(--spacing-sm) 0; padding-left: var(--spacing-lg);">
                            <li>Event Name</li>
                            <li>Description</li>
                        </ul>

                        <p style="margin-top: var(--spacing-md);"><strong>Locked After Books Generated:</strong></p>
                        <ul style="margin: var(--spacing-sm) 0; padding-left: var(--spacing-lg);">
                            <li>Total Books</li>
                            <li>Tickets Per Book</li>
                            <li>Price Per Ticket</li>
                            <li>First Ticket Number</li>
                        </ul>

                        <p style="margin-top: var(--spacing-md); color: var(--gray-600); font-size: var(--font-size-sm);">
                            This prevents accidental changes to book configuration after distribution has started.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
