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
$eventStmt = $db->prepare($query);
$eventStmt->bindValue(':id', $eventId);
$eventStmt->execute();
$event = $eventStmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Get distribution levels for this event
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$levelsStmt = $db->prepare($levelsQuery);
$levelsStmt->bindValue(':event_id', $eventId);
$levelsStmt->execute();
$levels = $levelsStmt->fetchAll();

// Get all level values
$levelValues = [];
$allValues = []; // For JavaScript cascading
foreach ($levels as $level) {
    $valuesQuery = "SELECT * FROM distribution_level_values WHERE level_id = :level_id ORDER BY value_name";
    $valuesStmt = $db->prepare($valuesQuery);
    $valuesStmt->bindValue(':level_id', $level['level_id']);
    $valuesStmt->execute();
    $values = $valuesStmt->fetchAll();
    $levelValues[$level['level_id']] = $values;

    foreach ($values as $val) {
        $allValues[] = [
            'value_id' => $val['value_id'],
            'level_id' => $level['level_id'],
            'parent_value_id' => $val['parent_value_id'],
            'value_name' => $val['value_name']
        ];
    }
}

// Get search, filter, and pagination parameters
// For search, use trim and strip_tags only (no htmlspecialchars) since PDO handles SQL injection
$search = trim(strip_tags($_GET['search'] ?? ''));
$statusFilter = $_GET['status_filter'] ?? 'all';
// Get level filters
$level1Filter = isset($_GET['level1']) ? (int)$_GET['level1'] : 0;
$level2Filter = isset($_GET['level2']) ? (int)$_GET['level2'] : 0;
$level3Filter = isset($_GET['level3']) ? (int)$_GET['level3'] : 0;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20; // Default 20 per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Build where clause for search
$whereClause = "lb.event_id = :event_id";
$searchParams = [];

// Add level filters (filter by level value names in distribution_path)
if ($level1Filter > 0) {
    // Get level 1 value name
    $level1ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level1ValueStmt = $db->prepare($level1ValueQuery);
    $level1ValueStmt->bindValue(':value_id', $level1Filter, PDO::PARAM_INT);
    $level1ValueStmt->execute();
    $level1ValueName = $level1ValueStmt->fetchColumn();
    if ($level1ValueName) {
        $whereClause .= " AND bd.distribution_path LIKE :level1_filter";
        $searchParams['level1_filter'] = '%' . $level1ValueName . '%';
    }
}
if ($level2Filter > 0) {
    // Get level 2 value name
    $level2ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level2ValueStmt = $db->prepare($level2ValueQuery);
    $level2ValueStmt->bindValue(':value_id', $level2Filter, PDO::PARAM_INT);
    $level2ValueStmt->execute();
    $level2ValueName = $level2ValueStmt->fetchColumn();
    if ($level2ValueName) {
        $whereClause .= " AND bd.distribution_path LIKE :level2_filter";
        $searchParams['level2_filter'] = '%' . $level2ValueName . '%';
    }
}
if ($level3Filter > 0) {
    // Get level 3 value name
    $level3ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level3ValueStmt = $db->prepare($level3ValueQuery);
    $level3ValueStmt->bindValue(':value_id', $level3Filter, PDO::PARAM_INT);
    $level3ValueStmt->execute();
    $level3ValueName = $level3ValueStmt->fetchColumn();
    if ($level3ValueName) {
        $whereClause .= " AND bd.distribution_path LIKE :level3_filter";
        $searchParams['level3_filter'] = '%' . $level3ValueName . '%';
    }
}

if (!empty($search)) {
    // Check if search is a range (e.g., 1000-1040)
    if (preg_match('/^(\d+)-(\d+)$/', $search, $matches)) {
        $rangeStart = (int)$matches[1];
        $rangeEnd = (int)$matches[2];
        $whereClause .= " AND (lb.start_ticket_number >= :range_start AND lb.start_ticket_number <= :range_end)";
        $searchParams['range_start'] = $rangeStart;
        $searchParams['range_end'] = $rangeEnd;
    }
    // Check if search is a single ticket number
    elseif (is_numeric($search)) {
        $ticketNum = (int)$search;
        // Find book where the ticket falls in the range (start to end)
        $whereClause .= " AND (:ticket_num_start BETWEEN lb.start_ticket_number AND lb.end_ticket_number)";
        $searchParams['ticket_num_start'] = $ticketNum;
    }
    // Otherwise search in distribution path, notes
    else {
        $searchTerm = '%' . $search . '%';
        $whereClause .= " AND (bd.distribution_path LIKE :search_path OR bd.notes LIKE :search_notes OR bd.mobile_number LIKE :search_mobile)";
        $searchParams['search_path'] = $searchTerm;
        $searchParams['search_notes'] = $searchTerm;
        $searchParams['search_mobile'] = $searchTerm;
    }
}

// First, get total count for pagination (using subquery to account for HAVING clause)
$countQuery = "SELECT COUNT(*) as total FROM (
          SELECT lb.book_id,
          COALESCE(SUM(pc.amount_paid), 0) as total_paid,
          (lb.end_ticket_number - lb.start_ticket_number + 1) * :price_per_ticket as expected_amount
          FROM lottery_books lb
          JOIN book_distribution bd ON lb.book_id = bd.book_id
          LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
          WHERE {$whereClause}
          GROUP BY lb.book_id
          HAVING 1=1";

// Add status filter to count query
if ($statusFilter === 'paid') {
    $countQuery .= " AND total_paid >= expected_amount";
} elseif ($statusFilter === 'partial') {
    $countQuery .= " AND total_paid > 0 AND total_paid < expected_amount";
} elseif ($statusFilter === 'unpaid') {
    $countQuery .= " AND total_paid = 0";
}

$countQuery .= ") as filtered_books";

$countStmt = $db->prepare($countQuery);
$countStmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
$countStmt->bindValue(':price_per_ticket', $event['price_per_ticket']);
foreach ($searchParams as $key => $value) {
    if (is_int($value)) {
        $countStmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
    } else {
        $countStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
    }
}
$countStmt->execute();
$totalDistributions = $countStmt->fetch()['total'];
$totalPages = ceil($totalDistributions / $perPage);

// Get distributed books with payment info (paginated)
$query = "SELECT lb.*, bd.notes, bd.mobile_number, bd.distribution_path, bd.distribution_id,
          bd.is_returned, bd.returned_by, le.book_return_deadline,
          COALESCE(SUM(pc.amount_paid), 0) as total_paid,
          (lb.end_ticket_number - lb.start_ticket_number + 1) * :price_per_ticket as expected_amount
          FROM lottery_books lb
          JOIN book_distribution bd ON lb.book_id = bd.book_id
          LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
          LEFT JOIN lottery_events le ON lb.event_id = le.event_id
          WHERE {$whereClause}
          GROUP BY lb.book_id
          HAVING 1=1";

// Add status filter
if ($statusFilter === 'paid') {
    $query .= " AND total_paid >= expected_amount";
} elseif ($statusFilter === 'partial') {
    $query .= " AND total_paid > 0 AND total_paid < expected_amount";
} elseif ($statusFilter === 'unpaid') {
    $query .= " AND total_paid = 0";
}

$query .= " ORDER BY lb.start_ticket_number, bd.distribution_path ASC, bd.notes ASC
          LIMIT :limit OFFSET :offset";

$distributionsStmt = $db->prepare($query);
$distributionsStmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
$distributionsStmt->bindValue(':price_per_ticket', $event['price_per_ticket']);
$distributionsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$distributionsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Bind search parameters
foreach ($searchParams as $key => $value) {
    if (is_int($value)) {
        $distributionsStmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
    } else {
        $distributionsStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
    }
}

$distributionsStmt->execute();
$distributions = $distributionsStmt->fetchAll();

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
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
    <script src="/public/js/toast.js"></script>
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

        .btn-xs {
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            line-height: 1.2;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }

            .help-box-toggle {
                font-size: 0.9rem;
                padding: var(--spacing-sm) !important;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .button-group-mobile {
                display: flex;
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .button-group-mobile .btn {
                width: 100%;
            }

            .card-body > form > div {
                flex-direction: column !important;
            }

            .card-body > form > div > div {
                width: 100% !important;
                min-width: 100% !important;
            }
        }

        @media (max-width: 480px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }

            .btn-sm {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
                min-width: 35px !important;
            }

            .btn-sm span {
                display: none;
            }
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
        <!-- Back Button at Top -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-primary">View Books</a>
        </div>

        <?php
        $error = '';
        $success = '';
        include __DIR__ . '/includes/toast-handler.php';
        ?>

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

        <!-- Search & Filter Box -->
        <div class="card" style="margin-bottom: var(--spacing-md);">
            <div class="card-body">
                <form method="GET" action="" style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap; align-items: end;">
                    <input type="hidden" name="id" value="<?php echo $eventId; ?>">
                    <div style="flex: 1; min-width: 250px;">
                        <label class="form-label">🔍 Search Payments</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Ticket number, range, location, or member name"
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    <div style="min-width: 150px;">
                        <label class="form-label">Status Filter</label>
                        <select name="status_filter" class="form-control">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid Only</option>
                            <option value="partial" <?php echo $statusFilter === 'partial' ? 'selected' : ''; ?>>Partial Only</option>
                            <option value="unpaid" <?php echo $statusFilter === 'unpaid' ? 'selected' : ''; ?>>Unpaid Only</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search) || $statusFilter !== 'all'): ?>
                        <a href="?id=<?php echo $eventId; ?>" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Level Filters -->
        <?php if (count($levels) > 0): ?>
        <div class="card" style="margin-bottom: var(--spacing-md);">
            <div class="card-body">
                <form method="GET" action="" id="levelFilterForm">
                    <input type="hidden" name="id" value="<?php echo $eventId; ?>">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($statusFilter); ?>">
                    <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md); align-items: end;">
                        <?php foreach ($levels as $index => $level): ?>
                        <div>
                            <label class="form-label"><?php echo htmlspecialchars($level['level_name']); ?></label>
                            <select
                                name="level<?php echo $level['level_number']; ?>"
                                class="form-control level-filter-select"
                                data-level="<?php echo $level['level_number']; ?>"
                                onchange="handleLevelFilterChange(<?php echo $level['level_number']; ?>)"
                            >
                                <option value="">All <?php echo htmlspecialchars($level['level_name']); ?></option>
                                <?php
                                $currentLevelFilter = ${'level' . $level['level_number'] . 'Filter'};
                                foreach ($levelValues[$level['level_id']] as $value):
                                    // For dependent levels, filter by parent
                                    if ($level['level_number'] == 2 && $level1Filter > 0) {
                                        // Only show Level 2 values that belong to selected Level 1
                                        if ($value['parent_value_id'] != $level1Filter) continue;
                                    } elseif ($level['level_number'] == 3 && $level2Filter > 0) {
                                        // Only show Level 3 values that belong to selected Level 2
                                        if ($value['parent_value_id'] != $level2Filter) continue;
                                    }
                                ?>
                                <option value="<?php echo $value['value_id']; ?>" <?php echo $currentLevelFilter == $value['value_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($value['value_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endforeach; ?>

                        <div style="display: flex; gap: var(--spacing-xs);">
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                            <?php if ($level1Filter > 0 || $level2Filter > 0 || $level3Filter > 0): ?>
                                <a href="?id=<?php echo $eventId; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo $statusFilter; ?>&per_page=<?php echo $perPage; ?>" class="btn btn-secondary">Clear</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Collapsible Help Box -->
        <div class="help-box mb-3" style="border: 2px solid #16a34a; border-radius: var(--radius-md); overflow: hidden;">
            <button type="button" class="help-box-toggle" onclick="toggleHelpBox()" style="width: 100%; text-align: left; background: #f0fdf4; border: none; padding: var(--spacing-md); cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: 600; color: #15803d;">
                <span>💰 Instructions: How to Track Payments</span>
                <span id="helpBoxIcon" style="font-size: 1.25rem;">▼</span>
            </button>
            <div id="helpBoxContent" style="display: none; padding: var(--spacing-md); background: white;">
                <p style="margin: 0 0 var(--spacing-sm) 0;">Track all payments for distributed lottery books. Use search and filters above to find specific payments. Click "Collect Payment" to record partial or full payments.</p>
                <ul style="margin: var(--spacing-sm) 0;">
                    <li><strong>Paid:</strong> Full payment received (₹<?php echo number_format($event['price_per_ticket'] * $event['tickets_per_book']); ?> per book)</li>
                    <li><strong>Partial:</strong> Some payment received, but not complete</li>
                    <li><strong>Unpaid:</strong> No payment received yet</li>
                </ul>
            </div>
        </div>

        <!-- Per Page Selector -->
        <div class="card" style="margin-bottom: var(--spacing-sm); background: #f8fafc;">
            <div class="card-body" style="padding: var(--spacing-md);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spacing-sm);">
                    <div style="display: flex; align-items: center; gap: var(--spacing-sm); flex-wrap: wrap;">
                        <span style="font-weight: 600;">Show:</span>
                        <?php
                        $perPageOptions = [10, 20, 50, 100];
                        foreach ($perPageOptions as $option):
                            $isActive = $perPage == $option;
                            $linkParams = http_build_query(array_filter([
                                'id' => $eventId,
                                'status_filter' => $statusFilter != 'all' ? $statusFilter : null,
                                'search' => $search,
                                'level1' => $level1Filter,
                                'level2' => $level2Filter,
                                'level3' => $level3Filter,
                                'per_page' => $option,
                                'page' => 1
                            ]));
                        ?>
                            <a href="?<?php echo $linkParams; ?>"
                               class="btn btn-sm <?php echo $isActive ? 'btn-primary' : 'btn-secondary'; ?>"
                               style="min-width: 50px;">
                                <?php echo $option; ?>
                            </a>
                        <?php endforeach; ?>
                        <span style="color: var(--gray-600); margin-left: var(--spacing-sm);">
                            Showing <?php echo min($offset + 1, $totalDistributions); ?>-<?php echo min($offset + $perPage, $totalDistributions); ?> of <?php echo $totalDistributions; ?> distributions
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payment Tracking (<?php echo $totalDistributions; ?> total)</h3>
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
                                    <th>First Ticket No</th>
                                    <?php foreach ($levels as $level): ?>
                                        <th><?php echo htmlspecialchars($level['level_name']); ?></th>
                                    <?php endforeach; ?>
                                    <?php if (count($levels) === 0): ?>
                                        <th>Location</th>
                                    <?php endif; ?>
                                    <th>Status & Action</th>
                                    <th>Book Return</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($distributions as $dist):
                                    // Parse distribution_path into individual levels
                                    $levelValues = [];
                                    if (!empty($dist['distribution_path'])) {
                                        $levelValues = explode(' > ', $dist['distribution_path']);
                                    }

                                    $outstanding = $dist['expected_amount'] - $dist['total_paid'];
                                    $status = 'unpaid';
                                    if ($dist['total_paid'] >= $dist['expected_amount']) $status = 'paid';
                                    elseif ($dist['total_paid'] > 0) $status = 'partial';
                                ?>
                                    <tr>
                                        <td><strong><?php echo $dist['start_ticket_number']; ?></strong></td>
                                        <?php
                                        // Display dynamic level columns
                                        if (count($levels) > 0) {
                                            for ($i = 0; $i < count($levels); $i++) {
                                                echo '<td>' . htmlspecialchars($levelValues[$i] ?? '-') . '</td>';
                                            }
                                        } else {
                                            // Fallback if no levels configured
                                            echo '<td>' . htmlspecialchars($dist['distribution_path'] ?? '-') . '</td>';
                                        }
                                        ?>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: var(--spacing-xs);">
                                                <div>
                                                    <?php if ($status === 'paid'): ?>
                                                        <span class="badge badge-success">✓ Paid - ₹<?php echo number_format($dist['total_paid']); ?></span>
                                                    <?php elseif ($status === 'partial'): ?>
                                                        <span class="badge badge-warning">Partial - ₹<?php echo number_format($dist['total_paid']); ?> / ₹<?php echo number_format($dist['expected_amount']); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Unpaid - ₹<?php echo number_format($dist['expected_amount']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="display: flex; gap: var(--spacing-xs); flex-wrap: wrap;">
                                                    <?php if ($status !== 'paid'): ?>
                                                        <a href="/public/group-admin/lottery-payment-collect.php?book_id=<?php echo $dist['book_id']; ?>" class="btn btn-sm btn-success">
                                                            💰 Collect
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($dist['total_paid'] > 0): ?>
                                                        <a href="/public/group-admin/lottery-payment-transactions.php?dist_id=<?php echo $dist['distribution_id']; ?>" class="btn btn-sm btn-info">
                                                            📋 Transactions
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            // Check if past deadline
                                            $isPastDeadline = false;
                                            if (!empty($dist['book_return_deadline'])) {
                                                $deadline = strtotime($dist['book_return_deadline']);
                                                $today = strtotime(date('Y-m-d'));
                                                $isPastDeadline = $today > $deadline;
                                            }

                                            if ($dist['is_returned'] == 1): ?>
                                                <span class="badge badge-success" title="Book has been returned">✓ Returned</span>
                                                <form method="POST" action="/public/group-admin/book-return-toggle.php" style="display: inline;">
                                                    <input type="hidden" name="distribution_id" value="<?php echo $dist['distribution_id']; ?>">
                                                    <input type="hidden" name="action" value="mark_not_returned">
                                                    <button type="submit" class="btn btn-xs btn-secondary">Undo</button>
                                                </form>
                                            <?php elseif ($isPastDeadline): ?>
                                                <span class="badge badge-danger" title="Book not returned after deadline">⚠️ Not Returned</span>
                                                <form method="POST" action="/public/group-admin/book-return-toggle.php" style="display: inline;">
                                                    <input type="hidden" name="distribution_id" value="<?php echo $dist['distribution_id']; ?>">
                                                    <input type="hidden" name="action" value="mark_returned">
                                                    <button type="submit" class="btn btn-xs btn-success">Mark Returned</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge badge-warning" title="Awaiting return">📦 Pending Return</span>
                                                <form method="POST" action="/public/group-admin/book-return-toggle.php" style="display: inline;">
                                                    <input type="hidden" name="distribution_id" value="<?php echo $dist['distribution_id']; ?>">
                                                    <input type="hidden" name="action" value="mark_returned">
                                                    <button type="submit" class="btn btn-xs btn-success">Mark Returned</button>
                                                </form>
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

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
        <div class="card" style="margin-top: var(--spacing-sm); background: #f8fafc;">
            <div class="card-body" style="padding: var(--spacing-md);">
                <div style="display: flex; justify-content: center; align-items: center; gap: var(--spacing-xs); flex-wrap: wrap;">
                    <?php
                    // Previous button
                    if ($page > 1):
                        $prevParams = http_build_query(array_filter([
                            'id' => $eventId,
                            'status_filter' => $statusFilter != 'all' ? $statusFilter : null,
                            'search' => $search,
                            'level1' => $level1Filter,
                            'level2' => $level2Filter,
                            'level3' => $level3Filter,
                            'per_page' => $perPage,
                            'page' => $page - 1
                        ]));
                    ?>
                        <a href="?<?php echo $prevParams; ?>" class="btn btn-sm btn-secondary">« Previous</a>
                    <?php endif; ?>

                    <?php
                    // Page numbers
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    if ($startPage > 1):
                        $firstParams = http_build_query(array_filter([
                            'id' => $eventId,
                            'status_filter' => $statusFilter != 'all' ? $statusFilter : null,
                            'search' => $search,
                            'level1' => $level1Filter,
                            'level2' => $level2Filter,
                            'level3' => $level3Filter,
                            'per_page' => $perPage,
                            'page' => 1
                        ]));
                    ?>
                        <a href="?<?php echo $firstParams; ?>" class="btn btn-sm btn-secondary">1</a>
                        <?php if ($startPage > 2): ?>
                            <span style="padding: 0 var(--spacing-xs);">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++):
                        $pageParams = http_build_query(array_filter([
                            'id' => $eventId,
                            'status_filter' => $statusFilter != 'all' ? $statusFilter : null,
                            'search' => $search,
                            'level1' => $level1Filter,
                            'level2' => $level2Filter,
                            'level3' => $level3Filter,
                            'per_page' => $perPage,
                            'page' => $i
                        ]));
                    ?>
                        <a href="?<?php echo $pageParams; ?>"
                           class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"
                           style="min-width: 40px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages):
                        if ($endPage < $totalPages - 1):
                        ?>
                            <span style="padding: 0 var(--spacing-xs);">...</span>
                        <?php endif;
                        $lastParams = http_build_query(array_filter([
                            'id' => $eventId,
                            'status_filter' => $statusFilter != 'all' ? $statusFilter : null,
                            'search' => $search,
                            'level1' => $level1Filter,
                            'level2' => $level2Filter,
                            'level3' => $level3Filter,
                            'per_page' => $perPage,
                            'page' => $totalPages
                        ]));
                    ?>
                        <a href="?<?php echo $lastParams; ?>" class="btn btn-sm btn-secondary"><?php echo $totalPages; ?></a>
                    <?php endif; ?>

                    <?php
                    // Next button
                    if ($page < $totalPages):
                        $nextParams = http_build_query(array_filter([
                            'id' => $eventId,
                            'status_filter' => $statusFilter != 'all' ? $statusFilter : null,
                            'search' => $search,
                            'level1' => $level1Filter,
                            'level2' => $level2Filter,
                            'level3' => $level3Filter,
                            'per_page' => $perPage,
                            'page' => $page + 1
                        ]));
                    ?>
                        <a href="?<?php echo $nextParams; ?>" class="btn btn-sm btn-secondary">Next »</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="button-group-mobile mt-3">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-primary">View Books</a>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        // Toggle help box
        function toggleHelpBox() {
            const content = document.getElementById('helpBoxContent');
            const icon = document.getElementById('helpBoxIcon');
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.textContent = '▲';
            } else {
                content.style.display = 'none';
                icon.textContent = '▼';
            }
        }

        // Store all level data for cascading
        const allLevels = <?php echo json_encode($levels); ?>;
        const allLevelValues = <?php echo json_encode($allValues); ?>;

        // Handle level filter changes (for dependent dropdowns)
        function handleLevelFilterChange(levelNumber) {
            // Update dependent dropdowns based on parent selection
            if (levelNumber === 1) {
                const level1Select = document.querySelector('select[data-level="1"]');
                const level2Select = document.querySelector('select[data-level="2"]');
                const level3Select = document.querySelector('select[data-level="3"]');

                const selectedLevel1 = level1Select ? parseInt(level1Select.value) : 0;

                // Clear level 2 and 3 selections
                if (level2Select) level2Select.value = '';
                if (level3Select) level3Select.value = '';

                // Update Level 2 options
                if (level2Select && allLevels.length >= 2) {
                    updateLevelOptions(level2Select, allLevels[1].level_id, selectedLevel1);
                }

                // Clear Level 3 options (no parent selected)
                if (level3Select && allLevels.length >= 3) {
                    updateLevelOptions(level3Select, allLevels[2].level_id, 0);
                }
            }
            else if (levelNumber === 2) {
                const level2Select = document.querySelector('select[data-level="2"]');
                const level3Select = document.querySelector('select[data-level="3"]');

                const selectedLevel2 = level2Select ? parseInt(level2Select.value) : 0;

                // Clear level 3 selection
                if (level3Select) level3Select.value = '';

                // Update Level 3 options
                if (level3Select && allLevels.length >= 3) {
                    updateLevelOptions(level3Select, allLevels[2].level_id, selectedLevel2);
                }
            }

            // Auto-submit the form
            document.getElementById('levelFilterForm').submit();
        }

        // Update dropdown options based on parent selection
        function updateLevelOptions(selectElement, levelId, parentValueId) {
            // Keep the first option (All...)
            const firstOption = selectElement.options[0];
            selectElement.innerHTML = '';
            selectElement.appendChild(firstOption);

            // Filter values by parent
            const filteredValues = allLevelValues.filter(val => {
                if (val.level_id != levelId) return false;
                if (parentValueId === 0) return true; // Show all if no parent selected
                return val.parent_value_id == parentValueId;
            });

            // Add filtered options
            filteredValues.forEach(val => {
                const option = document.createElement('option');
                option.value = val.value_id;
                option.textContent = val.value_name;
                selectElement.appendChild(option);
            });
        }
    </script>
</body>
</html>
