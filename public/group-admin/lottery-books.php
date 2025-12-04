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

// Get distribution levels for this event
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($levelsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$levels = $stmt->fetchAll();

// Get all level values with parent relationships
$levelValues = [];
$allValues = []; // For JavaScript
foreach ($levels as $level) {
    $valuesQuery = "SELECT * FROM distribution_level_values WHERE level_id = :level_id ORDER BY value_name";
    $stmt = $db->prepare($valuesQuery);
    $stmt->bindParam(':level_id', $level['level_id']);
    $stmt->execute();
    $values = $stmt->fetchAll();
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
        // Get distribution level selections
        $distributionData = [];
        $lastValueId = null;

        foreach ($levels as $level) {
            $selectedValueId = $_POST["level_{$level['level_id']}_id"] ?? '';
            $selectedValue = $_POST["level_{$level['level_id']}"] ?? '';
            $newValue = trim($_POST["new_level_{$level['level_id']}"] ?? '');

            // If "Add New" is selected and new value is provided
            if ($selectedValue === '__new__' && !empty($newValue)) {
                // Insert new value with parent relationship
                $insertQuery = "INSERT INTO distribution_level_values (level_id, value_name, parent_value_id) VALUES (:level_id, :value_name, :parent_value_id)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->bindParam(':level_id', $level['level_id']);
                $insertStmt->bindParam(':value_name', $newValue);
                $insertStmt->bindValue(':parent_value_id', $lastValueId, PDO::PARAM_INT);
                $insertStmt->execute();
                $lastValueId = $db->lastInsertId();
                $selectedValue = $newValue;
            } elseif (!empty($selectedValueId)) {
                // Use existing value ID as parent for next level
                $lastValueId = $selectedValueId;
            }

            if (!empty($selectedValue) && $selectedValue !== '__new__') {
                $distributionData[$level['level_name']] = $selectedValue;
            }
        }

        // Validate distribution levels - all configured levels must be selected
        $missingLevels = [];
        foreach ($levels as $level) {
            if (empty($distributionData[$level['level_name']])) {
                $missingLevels[] = $level['level_name'];
            }
        }

        if (count($levels) > 0 && count($missingLevels) > 0) {
            $error = 'Please select: ' . implode(', ', $missingLevels);
        } else {
            // Build distribution path
            $distributionPath = !empty($distributionData) ? implode(' > ', $distributionData) : '';

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
                    $query = "INSERT INTO book_distribution (book_id, notes, mobile_number, distribution_path, distributed_by)
                              VALUES (:book_id, :notes, :mobile, :distribution_path, :distributed_by)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':book_id', $bookId);
                    $stmt->bindParam(':notes', $notes);
                    $stmt->bindParam(':mobile', $mobile);
                    $stmt->bindParam(':distribution_path', $distributionPath);
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
            if (!empty($distributionPath)) {
                $success .= " to " . htmlspecialchars($distributionPath);
            }
            if (!empty($notes)) {
                $success .= " - " . htmlspecialchars($notes);
            }
            if ($alreadyAssigned > 0) {
                $success .= " (Skipped {$alreadyAssigned} already assigned books)";
            }
        }
    }
}

// Handle success messages
if (isset($_GET['success'])) {
    $success = match($_GET['success']) {
        'reassigned' => 'Book reassigned successfully to new location',
        default => ''
    };
}

// Get filter, search, and pagination
$filter = $_GET['filter'] ?? 'all';
$search = Validator::sanitizeString($_GET['search'] ?? '');
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20; // Default 20 per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Build where clause
$whereClause = "lb.event_id = :event_id";
$searchParams = [];

if ($filter === 'available') {
    $whereClause .= " AND lb.book_status = 'available'";
} elseif ($filter === 'assigned') {
    $whereClause .= " AND lb.book_status IN ('distributed', 'collected')";
}

// Add search conditions
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
        $whereClause .= " AND (bd.distribution_path LIKE :search_term OR bd.notes LIKE :search_term OR bd.mobile_number LIKE :search_term)";
        $searchParams['search_term'] = '%' . $search . '%';
    }
}

// Count total books for pagination
$countQuery = "SELECT COUNT(*) as total
          FROM lottery_books lb
          LEFT JOIN book_distribution bd ON lb.book_id = bd.book_id
          WHERE {$whereClause}";
$countStmt = $db->prepare($countQuery);
$countStmt->bindValue(':event_id', $eventId);
foreach ($searchParams as $key => $value) {
    $countStmt->bindValue(':' . $key, $value);
}
$countStmt->execute();
$totalBooks = $countStmt->fetch()['total'];
$totalPages = ceil($totalBooks / $perPage);

// Get paginated books
$query = "SELECT lb.*, bd.notes, bd.mobile_number, bd.distribution_path, bd.distribution_id
          FROM lottery_books lb
          LEFT JOIN book_distribution bd ON lb.book_id = bd.book_id
          WHERE {$whereClause}
          ORDER BY lb.start_ticket_number, lb.book_number
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':event_id', $eventId);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Bind search parameters
foreach ($searchParams as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}

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
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
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
        .add-new-field {
            display: none;
            margin-top: var(--spacing-xs);
        }
        .add-new-field.show {
            display: block;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }

            .tabs {
                flex-direction: column;
            }

            .tab {
                text-align: center;
                border-bottom: 1px solid var(--gray-200);
            }

            .help-box-toggle {
                font-size: 0.9rem;
                padding: var(--spacing-sm) !important;
            }

            .card-header {
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            .card-header h3 {
                margin-bottom: var(--spacing-sm) !important;
            }

            .card-header > div {
                width: 100%;
                display: flex;
                gap: var(--spacing-xs);
                flex-wrap: wrap;
            }

            .card-header .btn {
                flex: 1;
                min-width: 120px;
            }

            .button-group-mobile {
                display: flex;
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .button-group-mobile .btn {
                width: 100%;
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

            .card-header .btn {
                font-size: 0.8rem;
                padding: 0.375rem 0.5rem;
            }
        }
    </style>
    <script src="/public/js/toast.js"></script>
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
        <!-- Back Button at Top -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <a href="/public/group-admin/lottery-payments.php?id=<?php echo $eventId; ?>" class="btn btn-success">View Payments</a>
        </div>

        <?php include __DIR__ . '/includes/toast-handler.php'; ?>

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

        <!-- Search Box -->
        <div class="card" style="margin-bottom: var(--spacing-md);">
            <div class="card-body">
                <form method="GET" action="" style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap; align-items: end;">
                    <input type="hidden" name="id" value="<?php echo $eventId; ?>">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <div style="flex: 1; min-width: 250px;">
                        <label class="form-label">🔍 Search Books</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Enter ticket number (1000) or range (1000-1040) or location"
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                        <small class="form-text">Examples: 1000, 1000-1040, Wing A, Floor 1, or member name</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="?id=<?php echo $eventId; ?>&filter=<?php echo $filter; ?>" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Collapsible Help Box -->
        <div class="help-box mb-3" style="border: 2px solid #3b82f6; border-radius: var(--radius-md); overflow: hidden;">
            <button type="button" class="help-box-toggle" onclick="toggleHelpBox()" style="width: 100%; text-align: left; background: #eff6ff; border: none; padding: var(--spacing-md); cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: 600; color: #1e40af;">
                <span>💡 Instructions: How to Assign & Reassign Books</span>
                <span id="helpBoxIcon" style="font-size: 1.25rem;">▼</span>
            </button>
            <div id="helpBoxContent" style="display: none; padding: var(--spacing-md); background: white;">
                <ul style="margin: 0;">
                    <li><strong>Assign Books (First Time):</strong>
                        <ol style="margin: var(--spacing-xs) 0;">
                            <li>Use search above to find specific books by ticket number or range</li>
                            <li>Select available books using checkboxes</li>
                            <?php if (count($levels) > 0): ?>
                                <li>Fill in required distribution levels: <?php echo implode(', ', array_column($levels, 'level_name')); ?></li>
                            <?php endif; ?>
                            <li>Optionally add notes and mobile number</li>
                            <li>Click "Assign Selected Books" to complete</li>
                        </ol>
                    </li>
                    <li><strong>Reassign Books (Fix Wrong Assignment):</strong>
                        <ol style="margin: var(--spacing-xs) 0;">
                            <li>Find the incorrectly assigned book in the table</li>
                            <li>Click the <span class="badge badge-warning" style="display: inline-block; padding: 4px 8px;">🔄 Reassign</span> button in the Actions column</li>
                            <li>Select the correct distribution location</li>
                            <li>Update mobile number and notes if needed</li>
                            <li>Click "Reassign Book" to save changes</li>
                        </ol>
                    </li>
                </ul>
                <p style="margin: var(--spacing-sm) 0 0 0;"><strong>Tip:</strong> Use filters below to view Available or Assigned books separately. Reassigning does NOT affect existing payment records.</p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="tabs">
            <a href="?id=<?php echo $eventId; ?>&filter=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                📚 All Books (<?php echo $stats['total']; ?>)
            </a>
            <a href="?id=<?php echo $eventId; ?>&filter=available<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab <?php echo $filter === 'available' ? 'active' : ''; ?>">
                ✅ Available (<?php echo $stats['available']; ?>)
            </a>
            <a href="?id=<?php echo $eventId; ?>&filter=assigned<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab <?php echo $filter === 'assigned' ? 'active' : ''; ?>">
                📝 Assigned (<?php echo $stats['distributed'] + $stats['collected']; ?>)
            </a>
        </div>

        <!-- Bulk Assignment Form -->
        <div id="bulkActions" class="bulk-actions hidden">
            <form method="POST" id="bulkForm">
                <input type="hidden" name="bulk_assign" value="1">
                <h4 style="margin-top: 0;">Bulk Assign Selected Books (<span id="selectedCount">0</span> selected)</h4>

                <?php if (count($levels) > 0): ?>
                    <div style="background: white; padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-md);">
                        <p style="margin: 0 0 var(--spacing-md) 0; font-weight: 600; color: var(--primary-color);">
                            📍 Distribution Levels (Required)
                        </p>
                        <div class="form-row">
                            <?php foreach ($levels as $level): ?>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label"><?php echo htmlspecialchars($level['level_name']); ?> <span style="color: red;">*</span></label>
                                        <input type="hidden" name="level_<?php echo $level['level_id']; ?>_id" id="bulk_level_<?php echo $level['level_id']; ?>_id">
                                        <select
                                            name="level_<?php echo $level['level_id']; ?>"
                                            id="bulk_level_<?php echo $level['level_id']; ?>"
                                            class="form-control bulk-level-select"
                                            data-level-id="<?php echo $level['level_id']; ?>"
                                            data-level-number="<?php echo $level['level_number']; ?>"
                                            onchange="handleBulkLevelChange(<?php echo $level['level_id']; ?>, <?php echo $level['level_number']; ?>)"
                                            required
                                        >
                                            <option value="">- Select <?php echo htmlspecialchars($level['level_name']); ?> -</option>
                                            <?php foreach ($levelValues[$level['level_id']] as $value): ?>
                                                <option
                                                    value="<?php echo htmlspecialchars($value['value_name']); ?>"
                                                    data-value-id="<?php echo $value['value_id']; ?>"
                                                    data-parent-id="<?php echo $value['parent_value_id'] ?? ''; ?>"
                                                >
                                                    <?php echo htmlspecialchars($value['value_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="__new__">➕ Add New</option>
                                        </select>

                                        <div class="add-new-field" id="bulk_add_new_<?php echo $level['level_id']; ?>">
                                            <input
                                                type="text"
                                                name="new_level_<?php echo $level['level_id']; ?>"
                                                class="form-control"
                                                placeholder="Enter new <?php echo htmlspecialchars($level['level_name']); ?>"
                                                style="margin-top: var(--spacing-xs);"
                                            >
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Notes (Optional)</label>
                            <input
                                type="text"
                                name="bulk_notes"
                                class="form-control"
                                placeholder="e.g., Member name or any notes"
                            >
                            <small class="form-text">Optional: Add any notes for these books</small>
                        </div>
                    </div>
                    <div class="form-col">
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
                </div>

                <div class="button-group-mobile">
                    <button type="submit" class="btn btn-success">
                        Assign Selected Books
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                        Clear
                    </button>
                </div>
            </form>
        </div>

        <!-- Per Page Selector & Pagination Info -->
        <div class="card" style="margin-bottom: var(--spacing-sm); background: #f8fafc;">
            <div class="card-body" style="padding: var(--spacing-md);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spacing-sm);">
                    <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                        <span style="font-weight: 600;">Show:</span>
                        <?php
                        $perPageOptions = [10, 20, 50, 100];
                        foreach ($perPageOptions as $option):
                            $isActive = $perPage == $option;
                            $linkParams = http_build_query(array_filter([
                                'id' => $eventId,
                                'filter' => $filter,
                                'search' => $search,
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
                            Showing <?php echo min($offset + 1, $totalBooks); ?>-<?php echo min($offset + $perPage, $totalBooks); ?> of <?php echo $totalBooks; ?> books
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spacing-sm);">
                <h3 class="card-title" style="margin: 0;">
                    <?php
                    if ($filter === 'available') {
                        echo "Available Books (" . $totalBooks . " total)";
                    } elseif ($filter === 'assigned') {
                        echo "Assigned Books (" . $totalBooks . " total)";
                    } else {
                        echo "All Books (" . $totalBooks . " total)";
                    }
                    ?>
                </h3>
                <div style="display: flex; gap: var(--spacing-xs); flex-wrap: wrap;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="selectAll()">Select All on Page</button>
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
                                <th>First Ticket No</th>
                                <?php foreach ($levels as $level): ?>
                                    <th><?php echo htmlspecialchars($level['level_name']); ?></th>
                                <?php endforeach; ?>
                                <?php if (count($levels) === 0): ?>
                                    <th>Location</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($books) === 0): ?>
                                <tr>
                                    <td colspan="<?php echo 3 + count($levels); ?>" style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                                        No books found in this category
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($books as $book):
                                    // Parse distribution_path into individual levels
                                    $levelValues = [];
                                    if (!empty($book['distribution_path'])) {
                                        $levelValues = explode(' > ', $book['distribution_path']);
                                    }
                                ?>
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
                                        <td><strong><?php echo $book['start_ticket_number']; ?></strong></td>
                                        <?php
                                        // Display dynamic level columns
                                        if (count($levels) > 0) {
                                            for ($i = 0; $i < count($levels); $i++) {
                                                echo '<td>' . htmlspecialchars($levelValues[$i] ?? '-') . '</td>';
                                            }
                                        } else {
                                            // Fallback if no levels configured
                                            echo '<td>' . htmlspecialchars($book['distribution_path'] ?? '-') . '</td>';
                                        }
                                        ?>
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
                                                <div style="display: flex; gap: var(--spacing-xs); flex-wrap: wrap;">
                                                    <a href="/public/group-admin/lottery-payments.php?id=<?php echo $eventId; ?>"
                                                       class="btn btn-sm btn-success">View Payments</a>
                                                    <a href="/public/group-admin/lottery-book-reassign.php?dist_id=<?php echo $book['distribution_id']; ?>"
                                                       class="btn btn-sm btn-warning"
                                                       title="Change assignment to different unit">🔄 Reassign</a>
                                                </div>
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
                            'filter' => $filter,
                            'search' => $search,
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
                            'filter' => $filter,
                            'search' => $search,
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
                            'filter' => $filter,
                            'search' => $search,
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
                            'filter' => $filter,
                            'search' => $search,
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
                            'filter' => $filter,
                            'search' => $search,
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

        // Bulk level change handling
        function handleBulkLevelChange(levelId, levelNumber) {
            const select = document.getElementById(`bulk_level_${levelId}`);
            const selectedOption = select.options[select.selectedIndex];
            const selectedValueId = selectedOption.getAttribute('data-value-id');
            const hiddenInput = document.getElementById(`bulk_level_${levelId}_id`);

            // Store the value_id in hidden field
            if (selectedValueId) {
                hiddenInput.value = selectedValueId;
            } else {
                hiddenInput.value = '';
            }

            // Handle "Add New" toggle
            toggleBulkAddNew(levelId);

            // Filter next level dropdown (if exists)
            filterBulkNextLevel(levelId, levelNumber, selectedValueId);
        }

        function toggleBulkAddNew(levelId) {
            const select = document.getElementById(`bulk_level_${levelId}`);
            const addNewField = document.getElementById(`bulk_add_new_${levelId}`);
            const input = addNewField.querySelector('input');

            if (select.value === '__new__') {
                addNewField.classList.add('show');
                input.required = true;
                input.focus();
            } else {
                addNewField.classList.remove('show');
                input.required = false;
                input.value = '';
            }
        }

        function filterBulkNextLevel(currentLevelId, currentLevelNumber, parentValueId) {
            // Find next level
            const nextLevel = allLevels.find(level => level.level_number === currentLevelNumber + 1);

            if (!nextLevel) {
                return; // No next level exists
            }

            const nextSelect = document.getElementById(`bulk_level_${nextLevel.level_id}`);
            if (!nextSelect) {
                return;
            }

            // Get all options for next level
            const allOptions = nextSelect.querySelectorAll('option');

            // Reset next level
            nextSelect.value = '';
            document.getElementById(`bulk_level_${nextLevel.level_id}_id`).value = '';

            // Hide/show options based on parent
            allOptions.forEach(option => {
                const optionParentId = option.getAttribute('data-parent-id');

                // Always show default option and "Add New" option
                if (option.value === '' || option.value === '__new__') {
                    option.style.display = '';
                    return;
                }

                // Show option if parent matches
                if (!parentValueId) {
                    option.style.display = 'none';
                } else if (optionParentId === parentValueId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });

            // Also reset all subsequent levels
            resetBulkSubsequentLevels(currentLevelNumber + 1);
        }

        function resetBulkSubsequentLevels(fromLevelNumber) {
            // Reset all levels after the changed level
            allLevels.forEach(level => {
                if (level.level_number > fromLevelNumber) {
                    const select = document.getElementById(`bulk_level_${level.level_id}`);
                    const hiddenInput = document.getElementById(`bulk_level_${level.level_id}_id`);
                    if (select) {
                        select.value = '';
                        const allOptions = select.querySelectorAll('option');
                        allOptions.forEach(option => {
                            if (option.value !== '' && option.value !== '__new__') {
                                option.style.display = 'none';
                            }
                        });
                    }
                    if (hiddenInput) {
                        hiddenInput.value = '';
                    }
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateBulkActions();

            // For all levels except the first, hide all value options initially
            allLevels.forEach((level, index) => {
                if (index > 0) { // Not the first level
                    const select = document.getElementById(`bulk_level_${level.level_id}`);
                    if (select) {
                        const allOptions = select.querySelectorAll('option');
                        allOptions.forEach(option => {
                            if (option.value !== '' && option.value !== '__new__') {
                                option.style.display = 'none';
                            }
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>
