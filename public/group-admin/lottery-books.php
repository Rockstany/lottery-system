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

$error = '';
$success = '';

// Handle bulk assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_assign'])) {
    $selectedBooks = $_POST['selected_books'] ?? [];
    $notes = Validator::sanitizeString($_POST['bulk_notes'] ?? '');
    $mobile = Validator::sanitizeString($_POST['bulk_mobile'] ?? '');

    if (count($selectedBooks) === 0) {
        $error = 'Please select at least one book to assign';
    } else {
        $assigned = 0;
        $alreadyAssigned = 0;

        foreach ($selectedBooks as $bookId) {
            // Check if book is available
            $checkQuery = "SELECT book_status FROM lottery_books WHERE book_id = :book_id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':book_id', $bookId);
            $checkStmt->execute();
            $bookStatus = $checkStmt->fetch();

            if ($bookStatus && $bookStatus['book_status'] === 'available') {
                // Assign book
                $query = "INSERT INTO book_distribution (book_id, notes, mobile_number, distributed_by)
                          VALUES (:book_id, :notes, :mobile, :distributed_by)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':book_id', $bookId);
                $stmt->bindParam(':notes', $notes);
                $stmt->bindParam(':mobile', $mobile);
                $distributedBy = AuthMiddleware::getUserId();
                $stmt->bindParam(':distributed_by', $distributedBy);

                if ($stmt->execute()) {
                    $assigned++;
                }
            } else {
                $alreadyAssigned++;
            }
        }

        $success = "Successfully assigned {$assigned} book(s)";
        if (!empty($notes)) {
            $success .= " - " . htmlspecialchars($notes);
        }
        if ($alreadyAssigned > 0) {
            $success .= " (Skipped {$alreadyAssigned} already assigned books)";
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get all books with filter
$whereClause = "lb.event_id = :event_id";
if ($filter === 'available') {
    $whereClause .= " AND lb.book_status = 'available'";
} elseif ($filter === 'assigned') {
    $whereClause .= " AND lb.book_status IN ('distributed', 'collected')";
}

$query = "SELECT lb.*, bd.notes, bd.mobile_number, bd.distribution_path
          FROM lottery_books lb
          LEFT JOIN book_distribution bd ON lb.book_id = bd.book_id
          WHERE {$whereClause}
          ORDER BY lb.book_number";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$books = $stmt->fetchAll();

// Stats (all books)
$statsQuery = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN book_status = 'available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN book_status = 'distributed' THEN 1 ELSE 0 END) as distributed,
    SUM(CASE WHEN book_status = 'collected' THEN 1 ELSE 0 END) as collected
    FROM lottery_books WHERE event_id = :event_id";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->bindParam(':event_id', $eventId);
$statsStmt->execute();
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lottery Books - <?php echo APP_NAME; ?></title>
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
        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: var(--spacing-lg);
        }
        .tab {
            padding: var(--spacing-md) var(--spacing-xl);
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray-600);
            border-bottom: 3px solid transparent;
            transition: all var(--transition-base);
            text-decoration: none;
        }
        .tab:hover {
            color: var(--primary-color);
            background: var(--gray-50);
        }
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .bulk-actions {
            background: var(--gray-50);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            border: 2px solid var(--primary-color);
        }
        .bulk-actions.hidden {
            display: none;
        }
        .assigned-row {
            background-color: #f3f4f6;
            opacity: 0.7;
        }
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1><?php echo htmlspecialchars($event['event_name']); ?> - Books</h1>
            <p style="margin: 0; opacity: 0.9;">Part 4 of 6</p>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--success-color);"><?php echo $stats['available']; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['distributed']; ?></div>
                <div class="stat-label">Distributed</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">₹<?php echo number_format($event['price_per_ticket'] * $event['tickets_per_book']); ?></div>
                <div class="stat-label">Per Book</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="tabs">
            <a href="?id=<?php echo $eventId; ?>&filter=all" class="tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                📚 All Books (<?php echo $stats['total']; ?>)
            </a>
            <a href="?id=<?php echo $eventId; ?>&filter=available" class="tab <?php echo $filter === 'available' ? 'active' : ''; ?>">
                ✅ Available (<?php echo $stats['available']; ?>)
            </a>
            <a href="?id=<?php echo $eventId; ?>&filter=assigned" class="tab <?php echo $filter === 'assigned' ? 'active' : ''; ?>">
                📝 Assigned (<?php echo $stats['distributed'] + $stats['collected']; ?>)
            </a>
        </div>

        <!-- Bulk Assignment Form -->
        <div id="bulkActions" class="bulk-actions hidden">
            <form method="POST" id="bulkForm">
                <input type="hidden" name="bulk_assign" value="1">
                <h4 style="margin-top: 0;">Bulk Assign Selected Books (<span id="selectedCount">0</span> selected)</h4>
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Notes (Optional)</label>
                            <input
                                type="text"
                                name="bulk_notes"
                                class="form-control"
                                placeholder="e.g., Member name or location"
                            >
                            <small class="form-text">Optional: Add any notes for these books</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Mobile Number (Optional)</label>
                            <input
                                type="tel"
                                name="bulk_mobile"
                                class="form-control"
                                placeholder="10-digit mobile number"
                                maxlength="10"
                                pattern="[6-9][0-9]{9}"
                            >
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label" style="visibility: hidden;">Actions</label>
                            <div style="display: flex; gap: var(--spacing-sm);">
                                <button type="submit" class="btn btn-success">
                                    Assign Selected Books
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title" style="margin: 0;">
                    <?php
                    if ($filter === 'available') {
                        echo "Available Books (" . count($books) . ")";
                    } elseif ($filter === 'assigned') {
                        echo "Assigned Books (" . count($books) . ")";
                    } else {
                        echo "All Books (" . count($books) . ")";
                    }
                    ?>
                </h3>
                <div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="selectAll()">Select All Available</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAll()">Deselect All</button>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                </th>
                                <th>Book #</th>
                                <th>Tickets</th>
                                <th>Ticket Range</th>
                                <th>Location</th>
                                <th>Notes</th>
                                <th>Mobile</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($books) === 0): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                                        No books found in this category
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($books as $book): ?>
                                    <tr class="<?php echo $book['book_status'] !== 'available' ? 'assigned-row' : ''; ?>">
                                        <td class="checkbox-cell">
                                            <?php if ($book['book_status'] === 'available'): ?>
                                                <input
                                                    type="checkbox"
                                                    name="selected_books[]"
                                                    value="<?php echo $book['book_id']; ?>"
                                                    form="bulkForm"
                                                    class="book-checkbox"
                                                    onchange="updateBulkActions()"
                                                >
                                            <?php else: ?>
                                                <input type="checkbox" disabled>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong>Book <?php echo $book['book_number']; ?></strong></td>
                                        <td><?php echo $event['tickets_per_book']; ?></td>
                                        <td><?php echo $book['start_ticket_number']; ?> - <?php echo $book['end_ticket_number']; ?></td>
                                        <td><?php echo htmlspecialchars($book['distribution_path'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($book['notes'] ?? '-'); ?></td>
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
                            <?php endif; ?>
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

    <script>
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.book-checkbox:checked');
            const count = checkboxes.length;
            const bulkActions = document.getElementById('bulkActions');
            const countSpan = document.getElementById('selectedCount');

            if (count > 0) {
                bulkActions.classList.remove('hidden');
                countSpan.textContent = count;
            } else {
                bulkActions.classList.add('hidden');
            }

            // Update select all checkbox
            const allCheckboxes = document.querySelectorAll('.book-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            selectAllCheckbox.checked = allCheckboxes.length > 0 && count === allCheckboxes.length;
        }

        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.book-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });

            updateBulkActions();
        }

        function selectAll() {
            const checkboxes = document.querySelectorAll('.book-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateBulkActions();
        }

        function deselectAll() {
            const checkboxes = document.querySelectorAll('.book-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAllCheckbox').checked = false;
            updateBulkActions();
        }

        function clearSelection() {
            deselectAll();
        }

        // Initialize on page load
        updateBulkActions();
    </script>
</body>
</html>
