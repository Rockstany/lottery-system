<?php
/**
 * Lottery Reports & Analytics
 * GetToKnow Community App - Part 6
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$eventId) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get event details
$query = "SELECT * FROM lottery_events WHERE event_id = :event_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Get distribution levels for dynamic columns
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($levelsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$levels = $stmt->fetchAll();

// Get all level values for filters
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

// Get filter parameters (only for on-screen display, not for exports)
$level1Filter = isset($_GET['level1']) ? (int)$_GET['level1'] : 0;
$level2Filter = isset($_GET['level2']) ? (int)$_GET['level2'] : 0;
$level3Filter = isset($_GET['level3']) ? (int)$_GET['level3'] : 0;
$paymentStatusFilter = $_GET['payment_status'] ?? 'all';
$paymentMethodFilter = $_GET['payment_method'] ?? 'all';
$returnStatusFilter = $_GET['return_status'] ?? 'all';
$dateFromFilter = $_GET['date_from'] ?? '';
$dateToFilter = $_GET['date_to'] ?? '';

// Get comprehensive statistics
$statsQuery = "SELECT
    COUNT(*) as total_books,
    SUM(CASE WHEN book_status = 'available' THEN 1 ELSE 0 END) as available_books,
    SUM(CASE WHEN book_status = 'distributed' THEN 1 ELSE 0 END) as distributed_books,
    SUM(CASE WHEN book_status = 'collected' THEN 1 ELSE 0 END) as collected_books
    FROM lottery_books WHERE event_id = :event_id";
$stmt = $db->prepare($statsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$stats = $stmt->fetch();

// Get payment statistics
$paymentQuery = "SELECT
    COUNT(DISTINCT bd.distribution_id) as total_distributed,
    COALESCE(SUM(pc.amount_paid), 0) as total_collected,
    COUNT(pc.payment_id) as total_transactions
    FROM book_distribution bd
    JOIN lottery_books lb ON bd.book_id = lb.book_id
    LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
    WHERE lb.event_id = :event_id";
$stmt = $db->prepare($paymentQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$paymentStats = $stmt->fetch();

// Calculate payment status counts
$statusQuery = "SELECT
    SUM(CASE
        WHEN COALESCE(paid_total, 0) >= expected
        THEN 1 ELSE 0
    END) as paid_count,
    SUM(CASE
        WHEN COALESCE(paid_total, 0) > 0 AND COALESCE(paid_total, 0) < expected
        THEN 1 ELSE 0
    END) as partial_count,
    SUM(CASE
        WHEN COALESCE(paid_total, 0) = 0
        THEN 1 ELSE 0
    END) as unpaid_count
    FROM (
        SELECT
            bd.distribution_id,
            (le.tickets_per_book * le.price_per_ticket) as expected,
            COALESCE(SUM(pc.amount_paid), 0) as paid_total
        FROM book_distribution bd
        JOIN lottery_books lb ON bd.book_id = lb.book_id
        JOIN lottery_events le ON lb.event_id = le.event_id
        LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
        WHERE le.event_id = :event_id
        GROUP BY bd.distribution_id
    ) as payment_breakdown";
$stmt = $db->prepare($statusQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$statusCounts = $stmt->fetch();

// Merge status counts into paymentStats
$paymentStats['paid_count'] = $statusCounts['paid_count'];
$paymentStats['partial_count'] = $statusCounts['partial_count'];
$paymentStats['unpaid_count'] = $statusCounts['unpaid_count'];

// Get member-wise report WITH FILTERS (for on-screen display)
$memberWhereClause = "le.event_id = :event_id";
$memberSearchParams = [];

// Apply level filters
if ($level1Filter > 0) {
    $level1ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level1ValueStmt = $db->prepare($level1ValueQuery);
    $level1ValueStmt->bindValue(':value_id', $level1Filter, PDO::PARAM_INT);
    $level1ValueStmt->execute();
    $level1ValueName = $level1ValueStmt->fetchColumn();
    if ($level1ValueName) {
        $memberWhereClause .= " AND bd.distribution_path LIKE :level1_filter";
        $memberSearchParams['level1_filter'] = '%' . $level1ValueName . '%';
    }
}
if ($level2Filter > 0) {
    $level2ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level2ValueStmt = $db->prepare($level2ValueQuery);
    $level2ValueStmt->bindValue(':value_id', $level2Filter, PDO::PARAM_INT);
    $level2ValueStmt->execute();
    $level2ValueName = $level2ValueStmt->fetchColumn();
    if ($level2ValueName) {
        $memberWhereClause .= " AND bd.distribution_path LIKE :level2_filter";
        $memberSearchParams['level2_filter'] = '%' . $level2ValueName . '%';
    }
}
if ($level3Filter > 0) {
    $level3ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level3ValueStmt = $db->prepare($level3ValueQuery);
    $level3ValueStmt->bindValue(':value_id', $level3Filter, PDO::PARAM_INT);
    $level3ValueStmt->execute();
    $level3ValueName = $level3ValueStmt->fetchColumn();
    if ($level3ValueName) {
        $memberWhereClause .= " AND bd.distribution_path LIKE :level3_filter";
        $memberSearchParams['level3_filter'] = '%' . $level3ValueName . '%';
    }
}

// Apply date filters
if (!empty($dateFromFilter)) {
    $memberWhereClause .= " AND bd.distributed_at >= :date_from";
    $memberSearchParams['date_from'] = $dateFromFilter . ' 00:00:00';
}
if (!empty($dateToFilter)) {
    $memberWhereClause .= " AND bd.distributed_at <= :date_to";
    $memberSearchParams['date_to'] = $dateToFilter . ' 23:59:59';
}

$memberQuery = "SELECT
    bd.notes,
    bd.distribution_path,
    bd.mobile_number,
    bd.is_returned,
    bd.distribution_id,
    lb.book_number,
    lb.start_ticket_number,
    lb.end_ticket_number,
    (le.tickets_per_book * le.price_per_ticket) as expected_amount,
    COALESCE(SUM(pc.amount_paid), 0) as total_paid,
    ((le.tickets_per_book * le.price_per_ticket) - COALESCE(SUM(pc.amount_paid), 0)) as outstanding,
    CASE
        WHEN COALESCE(SUM(pc.amount_paid), 0) >= (le.tickets_per_book * le.price_per_ticket) THEN 'paid'
        WHEN COALESCE(SUM(pc.amount_paid), 0) > 0 THEN 'partial'
        ELSE 'unpaid'
    END as payment_status,
    bd.distributed_at
    FROM book_distribution bd
    JOIN lottery_books lb ON bd.book_id = lb.book_id
    JOIN lottery_events le ON lb.event_id = le.event_id
    LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
    WHERE {$memberWhereClause}
    GROUP BY bd.distribution_id
    HAVING 1=1";

// Apply payment status filter using HAVING clause
if ($paymentStatusFilter === 'paid') {
    $memberQuery .= " AND total_paid >= expected_amount";
} elseif ($paymentStatusFilter === 'partial') {
    $memberQuery .= " AND total_paid > 0 AND total_paid < expected_amount";
} elseif ($paymentStatusFilter === 'unpaid') {
    $memberQuery .= " AND total_paid = 0";
}

$memberQuery .= " ORDER BY bd.distribution_path ASC, bd.notes ASC";

$stmt = $db->prepare($memberQuery);
$stmt->bindParam(':event_id', $eventId);
foreach ($memberSearchParams as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$members = $stmt->fetchAll();

// Apply payment method filter (post-query - check if distribution has any payment with specified method)
if ($paymentMethodFilter !== 'all') {
    $members = array_filter($members, function($member) use ($db, $paymentMethodFilter) {
        $checkQuery = "SELECT COUNT(*) as count FROM payment_collections
                       WHERE distribution_id = :dist_id AND payment_method = :method";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindValue(':dist_id', $member['distribution_id'], PDO::PARAM_INT);
        $checkStmt->bindValue(':method', $paymentMethodFilter, PDO::PARAM_STR);
        $checkStmt->execute();
        $result = $checkStmt->fetch();
        return $result['count'] > 0;
    });
}

// Apply return status filter (post-query since it's simpler)
if ($returnStatusFilter === 'returned') {
    $members = array_filter($members, function($member) {
        return $member['is_returned'] == 1;
    });
} elseif ($returnStatusFilter === 'not_returned') {
    $members = array_filter($members, function($member) {
        return $member['is_returned'] == 0;
    });
}

// Calculate percentages
$collectionPercent = $event['total_predicted_amount'] > 0
    ? ($paymentStats['total_collected'] / $event['total_predicted_amount']) * 100
    : 0;

$distributionPercent = $event['total_books'] > 0
    ? ($stats['distributed_books'] / $event['total_books']) * 100
    : 0;

// Get payment method-wise collection WITH FILTERS
$paymentMethodWhereClause = "lb.event_id = :event_id";
$paymentMethodParams = [];

// Apply level filters
if ($level1Filter > 0) {
    $level1ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level1ValueStmt = $db->prepare($level1ValueQuery);
    $level1ValueStmt->bindValue(':value_id', $level1Filter, PDO::PARAM_INT);
    $level1ValueStmt->execute();
    $level1ValueName = $level1ValueStmt->fetchColumn();
    if ($level1ValueName) {
        $paymentMethodWhereClause .= " AND bd.distribution_path LIKE :pm_level1_filter";
        $paymentMethodParams['pm_level1_filter'] = '%' . $level1ValueName . '%';
    }
}
if ($level2Filter > 0) {
    $level2ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level2ValueStmt = $db->prepare($level2ValueQuery);
    $level2ValueStmt->bindValue(':value_id', $level2Filter, PDO::PARAM_INT);
    $level2ValueStmt->execute();
    $level2ValueName = $level2ValueStmt->fetchColumn();
    if ($level2ValueName) {
        $paymentMethodWhereClause .= " AND bd.distribution_path LIKE :pm_level2_filter";
        $paymentMethodParams['pm_level2_filter'] = '%' . $level2ValueName . '%';
    }
}
if ($level3Filter > 0) {
    $level3ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level3ValueStmt = $db->prepare($level3ValueQuery);
    $level3ValueStmt->bindValue(':value_id', $level3Filter, PDO::PARAM_INT);
    $level3ValueStmt->execute();
    $level3ValueName = $level3ValueStmt->fetchColumn();
    if ($level3ValueName) {
        $paymentMethodWhereClause .= " AND bd.distribution_path LIKE :pm_level3_filter";
        $paymentMethodParams['pm_level3_filter'] = '%' . $level3ValueName . '%';
    }
}

// Apply payment method filter
if ($paymentMethodFilter !== 'all') {
    $paymentMethodWhereClause .= " AND pc.payment_method = :pm_method_filter";
    $paymentMethodParams['pm_method_filter'] = $paymentMethodFilter;
}

// Apply return status filter
if ($returnStatusFilter === 'returned') {
    $paymentMethodWhereClause .= " AND bd.is_returned = 1";
} elseif ($returnStatusFilter === 'not_returned') {
    $paymentMethodWhereClause .= " AND bd.is_returned = 0";
}

// Apply date filters for payment collection date
if (!empty($dateFromFilter)) {
    $paymentMethodWhereClause .= " AND DATE(pc.payment_date) >= :pm_date_from";
    $paymentMethodParams['pm_date_from'] = $dateFromFilter;
}
if (!empty($dateToFilter)) {
    $paymentMethodWhereClause .= " AND DATE(pc.payment_date) <= :pm_date_to";
    $paymentMethodParams['pm_date_to'] = $dateToFilter;
}

$paymentMethodQuery = "SELECT
    pc.payment_method,
    COUNT(pc.payment_id) as transaction_count,
    SUM(pc.amount_paid) as total_amount
    FROM payment_collections pc
    JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
    JOIN lottery_books lb ON bd.book_id = lb.book_id
    WHERE {$paymentMethodWhereClause}
    GROUP BY pc.payment_method
    ORDER BY total_amount DESC";
$stmt = $db->prepare($paymentMethodQuery);
$stmt->bindParam(':event_id', $eventId);
foreach ($paymentMethodParams as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$paymentMethods = $stmt->fetchAll();

// Get date-wise collection by payment method WITH FILTERS (reuse same filters as payment method)
$dateWiseQuery = "SELECT
    DATE(pc.payment_date) as payment_date,
    pc.payment_method,
    COUNT(pc.payment_id) as transaction_count,
    SUM(pc.amount_paid) as daily_amount
    FROM payment_collections pc
    JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
    JOIN lottery_books lb ON bd.book_id = lb.book_id
    WHERE {$paymentMethodWhereClause}
    GROUP BY DATE(pc.payment_date), pc.payment_method
    ORDER BY payment_date DESC, payment_method";
$stmt = $db->prepare($dateWiseQuery);
$stmt->bindParam(':event_id', $eventId);
foreach ($paymentMethodParams as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$dateWisePayments = $stmt->fetchAll();

// Get commission statistics WITH FILTERS
$commissionWhereClause = "ce.event_id = :event_id";
$commissionParams = [];

// Apply level filters for commission (filter by level_1_value)
if ($level1Filter > 0) {
    $level1ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
    $level1ValueStmt = $db->prepare($level1ValueQuery);
    $level1ValueStmt->bindValue(':value_id', $level1Filter, PDO::PARAM_INT);
    $level1ValueStmt->execute();
    $level1ValueName = $level1ValueStmt->fetchColumn();
    if ($level1ValueName) {
        $commissionWhereClause .= " AND ce.level_1_value = :comm_level1_filter";
        $commissionParams['comm_level1_filter'] = $level1ValueName;
    }
}

// Note: Commission table only has level_1_value, so level2 and level3 filters won't apply directly
// But we can filter by checking the distribution path through JOIN
if ($level2Filter > 0 || $level3Filter > 0) {
    // Join with distribution table to filter by full path
    $commissionJoinClause = " JOIN book_distribution bd ON ce.distribution_id = bd.distribution_id";

    if ($level2Filter > 0) {
        $level2ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
        $level2ValueStmt = $db->prepare($level2ValueQuery);
        $level2ValueStmt->bindValue(':value_id', $level2Filter, PDO::PARAM_INT);
        $level2ValueStmt->execute();
        $level2ValueName = $level2ValueStmt->fetchColumn();
        if ($level2ValueName) {
            $commissionWhereClause .= " AND bd.distribution_path LIKE :comm_level2_filter";
            $commissionParams['comm_level2_filter'] = '%' . $level2ValueName . '%';
        }
    }
    if ($level3Filter > 0) {
        $level3ValueQuery = "SELECT value_name FROM distribution_level_values WHERE value_id = :value_id";
        $level3ValueStmt = $db->prepare($level3ValueQuery);
        $level3ValueStmt->bindValue(':value_id', $level3Filter, PDO::PARAM_INT);
        $level3ValueStmt->execute();
        $level3ValueName = $level3ValueStmt->fetchColumn();
        if ($level3ValueName) {
            $commissionWhereClause .= " AND bd.distribution_path LIKE :comm_level3_filter";
            $commissionParams['comm_level3_filter'] = '%' . $level3ValueName . '%';
        }
    }
} else {
    $commissionJoinClause = "";
}

$commissionQuery = "SELECT
    ce.commission_type,
    COUNT(*) as payment_count,
    SUM(ce.payment_amount) as total_payment_amount,
    SUM(ce.commission_amount) as total_commission_earned,
    AVG(ce.commission_percent) as avg_commission_percent
    FROM commission_earned ce
    {$commissionJoinClause}
    WHERE {$commissionWhereClause}
    GROUP BY ce.commission_type";
$stmt = $db->prepare($commissionQuery);
$stmt->bindParam(':event_id', $eventId);
foreach ($commissionParams as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$commissionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get commission by Level 1 WITH FILTERS
$commissionByLevelQuery = "SELECT
    ce.level_1_value,
    ce.commission_type,
    COUNT(*) as payment_count,
    SUM(ce.payment_amount) as total_payment,
    SUM(ce.commission_amount) as total_commission
    FROM commission_earned ce
    {$commissionJoinClause}
    WHERE {$commissionWhereClause}
    GROUP BY ce.level_1_value, ce.commission_type
    ORDER BY ce.level_1_value, ce.commission_type";
$stmt = $db->prepare($commissionByLevelQuery);
$stmt->bindParam(':event_id', $eventId);
foreach ($commissionParams as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$commissionByLevel = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total commission
$totalCommission = 0;
foreach ($commissionStats as $comm) {
    $totalCommission += $comm['total_commission_earned'];
}

// Check if commission is enabled
$commissionSettingsQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id";
$stmt = $db->prepare($commissionSettingsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$commissionSettings = $stmt->fetch(PDO::FETCH_ASSOC);
$commissionEnabled = $commissionSettings && (
    $commissionSettings['early_commission_enabled'] ||
    $commissionSettings['standard_commission_enabled'] ||
    $commissionSettings['extra_books_commission_enabled']
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
    <style>
        .header {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }
        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            text-align: center;
        }
        .stat-value {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            margin-bottom: var(--spacing-xs);
        }
        .stat-label {
            color: var(--gray-600);
            font-size: var(--font-size-sm);
        }
        .progress-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(var(--success-color) 0%, var(--success-color) var(--progress), var(--gray-200) var(--progress), var(--gray-200) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-md);
            position: relative;
        }
        .progress-circle::after {
            content: '';
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            position: absolute;
        }
        .progress-text {
            position: relative;
            z-index: 1;
            font-size: var(--font-size-xl);
            font-weight: 700;
        }
        .export-buttons {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
        }
        .tab-container {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid var(--gray-200);
            overflow-x: auto;
        }
        .tab {
            padding: var(--spacing-md) var(--spacing-lg);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: var(--font-size-base);
            white-space: nowrap;
            border-bottom: 3px solid transparent;
            transition: all var(--transition-base);
        }
        .tab.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }
        .tab-content {
            display: none;
            padding: var(--spacing-lg);
        }
        .tab-content.active {
            display: block;
        }
        @media print {
            .header, .export-buttons, .tabs, .btn { display: none; }
            .tab-content { display: block !important; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1>📊 Reports & Analytics</h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($event['event_name']); ?></p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Back Button at Top -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-primary">Manage Books</a>
            <a href="/public/group-admin/lottery-payments.php?id=<?php echo $eventId; ?>" class="btn btn-success">Track Payments</a>
        </div>

        <!-- Summary Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $event['total_books']; ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: var(--success-color);"><?php echo $stats['distributed_books']; ?></div>
                <div class="stat-label">Distributed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: var(--warning-color);"><?php echo $stats['available_books']; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($paymentStats['total_collected'], 0); ?></div>
                <div class="stat-label">Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($event['total_predicted_amount'], 0); ?></div>
                <div class="stat-label">Expected</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($event['total_predicted_amount'] - $paymentStats['total_collected'], 0); ?></div>
                <div class="stat-label">Outstanding</div>
            </div>
        </div>

        <!-- Progress Indicators -->
        <div class="row" style="margin-bottom: var(--spacing-xl);">
            <div class="col-6">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <h4>Distribution Progress</h4>
                        <div class="progress-circle" style="--progress: <?php echo $distributionPercent; ?>%;">
                            <div class="progress-text"><?php echo number_format($distributionPercent, 1); ?>%</div>
                        </div>
                        <p style="color: var(--gray-600);"><?php echo $stats['distributed_books']; ?> of <?php echo $event['total_books']; ?> books distributed</p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <h4>Collection Progress</h4>
                        <div class="progress-circle" style="--progress: <?php echo $collectionPercent; ?>%;">
                            <div class="progress-text"><?php echo number_format($collectionPercent, 1); ?>%</div>
                        </div>
                        <p style="color: var(--gray-600);">₹<?php echo number_format($paymentStats['total_collected'], 0); ?> of ₹<?php echo number_format($event['total_predicted_amount'], 0); ?> collected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <button onclick="exportToCSV()" class="btn btn-success">📥 Export to CSV</button>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
        </div>

        <!-- Filter Section (applies to ALL reports except Summary) -->
        <?php if (count($levels) > 0 || true): ?>
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <div class="card-header">
                <h3 class="card-title">🔍 Report Filters</h3>
                <small style="color: var(--gray-600);">These filters apply to all report tabs (Member-Wise, Payment Methods, Date-Wise, Payment Status, Book Status, and Commission). Export functions will include ALL data.</small>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="reportFilterForm">
                    <input type="hidden" name="id" value="<?php echo $eventId; ?>">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
                        <!-- Level Filters -->
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
                                        if ($value['parent_value_id'] != $level1Filter) continue;
                                    } elseif ($level['level_number'] == 3 && $level2Filter > 0) {
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

                        <!-- Payment Status Filter -->
                        <div>
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-control">
                                <option value="all" <?php echo $paymentStatusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="paid" <?php echo $paymentStatusFilter === 'paid' ? 'selected' : ''; ?>>✓ Paid Only</option>
                                <option value="partial" <?php echo $paymentStatusFilter === 'partial' ? 'selected' : ''; ?>>⚠️ Partial Only</option>
                                <option value="unpaid" <?php echo $paymentStatusFilter === 'unpaid' ? 'selected' : ''; ?>>❌ Unpaid Only</option>
                            </select>
                        </div>

                        <!-- Payment Method Filter -->
                        <div>
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-control">
                                <option value="all" <?php echo $paymentMethodFilter === 'all' ? 'selected' : ''; ?>>All Methods</option>
                                <option value="cash" <?php echo $paymentMethodFilter === 'cash' ? 'selected' : ''; ?>>💵 Cash Only</option>
                                <option value="upi" <?php echo $paymentMethodFilter === 'upi' ? 'selected' : ''; ?>>📱 UPI Only</option>
                                <option value="bank" <?php echo $paymentMethodFilter === 'bank' ? 'selected' : ''; ?>>🏦 Bank Transfer Only</option>
                                <option value="other" <?php echo $paymentMethodFilter === 'other' ? 'selected' : ''; ?>>🔄 Other Only</option>
                            </select>
                        </div>

                        <!-- Return Status Filter -->
                        <div>
                            <label class="form-label">Book Return Status</label>
                            <select name="return_status" class="form-control">
                                <option value="all" <?php echo $returnStatusFilter === 'all' ? 'selected' : ''; ?>>All Books</option>
                                <option value="returned" <?php echo $returnStatusFilter === 'returned' ? 'selected' : ''; ?>>✓ Returned Only</option>
                                <option value="not_returned" <?php echo $returnStatusFilter === 'not_returned' ? 'selected' : ''; ?>>⚠️ Not Returned Only</option>
                            </select>
                        </div>

                        <!-- Date From Filter -->
                        <div>
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFromFilter); ?>">
                        </div>

                        <!-- Date To Filter -->
                        <div>
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateToFilter); ?>">
                        </div>
                    </div>

                    <div style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <?php if ($level1Filter > 0 || $level2Filter > 0 || $level3Filter > 0 || $paymentStatusFilter !== 'all' || $paymentMethodFilter !== 'all' || $returnStatusFilter !== 'all' || !empty($dateFromFilter) || !empty($dateToFilter)): ?>
                            <a href="?id=<?php echo $eventId; ?>" class="btn btn-secondary">Clear All Filters</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabbed Reports -->
        <div class="tab-container">
            <div class="tabs">
                <button class="tab active" onclick="switchTab(event, 'member-report')">Member-Wise Report</button>
                <button class="tab" onclick="switchTab(event, 'payment-method')">Payment Methods</button>
                <button class="tab" onclick="switchTab(event, 'date-wise')">Date-Wise Collection</button>
                <button class="tab" onclick="switchTab(event, 'payment-status')">Payment Status</button>
                <button class="tab" onclick="switchTab(event, 'book-status')">Book Status</button>
                <?php if ($commissionEnabled): ?>
                    <button class="tab" onclick="switchTab(event, 'commission')">Commission</button>
                <?php endif; ?>
                <button class="tab" onclick="switchTab(event, 'summary')">Summary</button>
            </div>

            <!-- Tab 1: Member-Wise Report -->
            <div id="member-report" class="tab-content active">
                <h3>Member-Wise Detailed Report</h3>
                <?php if (count($members) === 0): ?>
                    <p style="text-align: center; color: var(--gray-500); padding: var(--spacing-xl);">No books distributed yet</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="memberTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <?php foreach ($levels as $level): ?>
                                        <th><?php echo htmlspecialchars($level['level_name']); ?></th>
                                    <?php endforeach; ?>
                                    <th>Notes</th>
                                    <th>Mobile</th>
                                    <th>Book No.</th>
                                    <th>Ticket Range</th>
                                    <th>Expected</th>
                                    <th>Paid</th>
                                    <th>Outstanding</th>
                                    <th>Status</th>
                                    <th>Book Return</th>
                                    <th>Distributed On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $index => $member): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <?php
                                        // Parse distribution path into level values
                                        $levelValues = [];
                                        if (!empty($member['distribution_path'])) {
                                            $levelValues = explode(' > ', $member['distribution_path']);
                                        }
                                        // Display each level value
                                        for ($i = 0; $i < count($levels); $i++) {
                                            echo '<td>' . htmlspecialchars($levelValues[$i] ?? '-') . '</td>';
                                        }
                                        ?>
                                        <td><?php echo htmlspecialchars($member['notes'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($member['mobile_number'] ?? '-'); ?></td>
                                        <td>#<?php echo $member['book_number']; ?></td>
                                        <td><?php echo $member['start_ticket_number']; ?> - <?php echo $member['end_ticket_number']; ?></td>
                                        <td>₹<?php echo number_format($member['expected_amount'], 0); ?></td>
                                        <td>₹<?php echo number_format($member['total_paid'], 0); ?></td>
                                        <td>₹<?php echo number_format($member['outstanding'], 0); ?></td>
                                        <td>
                                            <?php if ($member['payment_status'] === 'paid'): ?>
                                                <span class="badge badge-success">Paid</span>
                                            <?php elseif ($member['payment_status'] === 'partial'): ?>
                                                <span class="badge badge-warning">Partial</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($member['is_returned'] == 1): ?>
                                                <span class="badge badge-success">✓ Returned</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">⚠️ Not Returned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($member['distributed_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: 700; background: var(--gray-50);">
                                    <td colspan="6" style="text-align: right;">TOTAL:</td>
                                    <td>₹<?php echo number_format(array_sum(array_column($members, 'expected_amount')), 0); ?></td>
                                    <td>₹<?php echo number_format(array_sum(array_column($members, 'total_paid')), 0); ?></td>
                                    <td>₹<?php echo number_format(array_sum(array_column($members, 'outstanding')), 0); ?></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab 2: Payment Method Report -->
            <div id="payment-method" class="tab-content">
                <h3>💳 Payment Method-Wise Collection Report</h3>
                <p style="color: var(--gray-600); margin-bottom: var(--spacing-lg);">Shows total collections and percentages by payment method</p>

                <?php if (count($paymentMethods) === 0): ?>
                    <p style="text-align: center; color: var(--gray-500); padding: var(--spacing-xl);">No payments collected yet</p>
                <?php else: ?>
                    <!-- Summary Cards -->
                    <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
                        <?php foreach ($paymentMethods as $method): ?>
                            <?php
                                $percentage = $paymentStats['total_collected'] > 0
                                    ? ($method['total_amount'] / $paymentStats['total_collected']) * 100
                                    : 0;
                                $icon = match($method['payment_method']) {
                                    'cash' => '💵',
                                    'upi' => '📱',
                                    'bank' => '🏦',
                                    default => '💳'
                                };
                            ?>
                            <div class="stat-card" style="border-left: 4px solid var(--primary-color);">
                                <div style="font-size: var(--font-size-2xl); margin-bottom: var(--spacing-xs);"><?php echo $icon; ?></div>
                                <div class="stat-label" style="text-transform: uppercase; font-weight: 600; margin-bottom: var(--spacing-sm);">
                                    <?php echo htmlspecialchars($method['payment_method']); ?>
                                </div>
                                <div class="stat-value" style="color: var(--success-color);">
                                    ₹<?php echo number_format($method['total_amount'], 0); ?>
                                </div>
                                <div style="margin-top: var(--spacing-xs); color: var(--gray-600); font-size: var(--font-size-sm);">
                                    <?php echo number_format($percentage, 2); ?>% of total
                                </div>
                                <div style="margin-top: var(--spacing-xs); color: var(--gray-500); font-size: var(--font-size-xs);">
                                    <?php echo $method['transaction_count']; ?> transactions
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Detailed Table -->
                    <div class="table-responsive">
                        <table class="table" id="paymentMethodTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Payment Method</th>
                                    <th>Number of Transactions</th>
                                    <th>Total Amount Collected</th>
                                    <th>Percentage of Total</th>
                                    <th>Average Transaction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentMethods as $index => $method):
                                    $percentage = $paymentStats['total_collected'] > 0
                                        ? ($method['total_amount'] / $paymentStats['total_collected']) * 100
                                        : 0;
                                    $avgTransaction = $method['transaction_count'] > 0
                                        ? $method['total_amount'] / $method['transaction_count']
                                        : 0;
                                    $icon = match($method['payment_method']) {
                                        'cash' => '💵',
                                        'upi' => '📱',
                                        'bank' => '🏦',
                                        default => '💳'
                                    };
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo $icon; ?> <?php echo strtoupper(htmlspecialchars($method['payment_method'])); ?></strong></td>
                                        <td><?php echo $method['transaction_count']; ?></td>
                                        <td><strong>₹<?php echo number_format($method['total_amount'], 0); ?></strong></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                                <div style="flex: 1; height: 20px; background: var(--gray-200); border-radius: 10px; overflow: hidden;">
                                                    <div style="width: <?php echo min($percentage, 100); ?>%; height: 100%; background: var(--success-color);"></div>
                                                </div>
                                                <span style="min-width: 60px; font-weight: 600;"><?php echo number_format($percentage, 2); ?>%</span>
                                            </div>
                                        </td>
                                        <td>₹<?php echo number_format($avgTransaction, 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: 700; background: var(--gray-50);">
                                    <td colspan="2" style="text-align: right;">TOTAL:</td>
                                    <td><?php echo array_sum(array_column($paymentMethods, 'transaction_count')); ?></td>
                                    <td>₹<?php echo number_format($paymentStats['total_collected'], 0); ?></td>
                                    <td>100%</td>
                                    <td>-</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Visual Chart -->
                    <div style="margin-top: var(--spacing-xl);">
                        <h4>Visual Breakdown</h4>
                        <div style="background: white; padding: var(--spacing-xl); border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
                            <?php foreach ($paymentMethods as $method):
                                $percentage = $paymentStats['total_collected'] > 0
                                    ? ($method['total_amount'] / $paymentStats['total_collected']) * 100
                                    : 0;
                            ?>
                                <div style="margin-bottom: var(--spacing-lg);">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-xs);">
                                        <span style="font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($method['payment_method']); ?></span>
                                        <span style="font-weight: 600; color: var(--success-color);">₹<?php echo number_format($method['total_amount'], 0); ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                                    </div>
                                    <div style="height: 30px; background: var(--gray-100); border-radius: 15px; overflow: hidden;">
                                        <div style="width: <?php echo min($percentage, 100); ?>%; height: 100%; background: linear-gradient(90deg, var(--primary-color), var(--success-color)); display: flex; align-items: center; justify-content: flex-end; padding-right: 10px; color: white; font-size: var(--font-size-xs); font-weight: 600;">
                                            <?php if ($percentage > 15): ?>
                                                <?php echo number_format($percentage, 1); ?>%
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab 3: Date-Wise Collection -->
            <div id="date-wise" class="tab-content">
                <h3>📅 Date-Wise Payment Collection Report</h3>
                <p style="color: var(--gray-600); margin-bottom: var(--spacing-lg);">Shows daily collection breakdown by payment method</p>

                <?php if (count($dateWisePayments) === 0): ?>
                    <p style="text-align: center; color: var(--gray-500); padding: var(--spacing-xl);">No payments collected yet</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="dateWiseTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Payment Date</th>
                                    <th>Payment Method</th>
                                    <th>Number of Transactions</th>
                                    <th>Amount Collected</th>
                                    <th>Percentage of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $currentDate = null;
                                $dateTotal = 0;
                                $dateRowStart = 0;
                                foreach ($dateWisePayments as $index => $payment):
                                    $percentage = $paymentStats['total_collected'] > 0
                                        ? ($payment['daily_amount'] / $paymentStats['total_collected']) * 100
                                        : 0;
                                    $icon = match($payment['payment_method']) {
                                        'cash' => '💵',
                                        'upi' => '📱',
                                        'bank' => '🏦',
                                        default => '💳'
                                    };

                                    // Check if date changed
                                    if ($currentDate !== null && $currentDate !== $payment['payment_date']) {
                                        // Output date subtotal
                                        ?>
                                        <tr style="background: var(--gray-100); font-weight: 600;">
                                            <td colspan="4" style="text-align: right;">Subtotal for <?php echo date('M d, Y', strtotime($currentDate)); ?>:</td>
                                            <td>₹<?php echo number_format($dateTotal, 0); ?></td>
                                            <td><?php echo number_format(($dateTotal / $paymentStats['total_collected']) * 100, 2); ?>%</td>
                                        </tr>
                                        <?php
                                        $dateTotal = 0;
                                    }

                                    $currentDate = $payment['payment_date'];
                                    $dateTotal += $payment['daily_amount'];
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></strong> <small style="color: var(--gray-500);">(<?php echo date('D', strtotime($payment['payment_date'])); ?>)</small></td>
                                        <td><?php echo $icon; ?> <?php echo strtoupper(htmlspecialchars($payment['payment_method'])); ?></td>
                                        <td><?php echo $payment['transaction_count']; ?></td>
                                        <td><strong>₹<?php echo number_format($payment['daily_amount'], 0); ?></strong></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                                <div style="flex: 1; max-width: 100px; height: 15px; background: var(--gray-200); border-radius: 8px; overflow: hidden;">
                                                    <div style="width: <?php echo min($percentage, 100); ?>%; height: 100%; background: var(--primary-color);"></div>
                                                </div>
                                                <span style="min-width: 50px; font-size: var(--font-size-sm);"><?php echo number_format($percentage, 2); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                endforeach;

                                // Output final date subtotal
                                if ($currentDate !== null):
                                ?>
                                    <tr style="background: var(--gray-100); font-weight: 600;">
                                        <td colspan="4" style="text-align: right;">Subtotal for <?php echo date('M d, Y', strtotime($currentDate)); ?>:</td>
                                        <td>₹<?php echo number_format($dateTotal, 0); ?></td>
                                        <td><?php echo number_format(($dateTotal / $paymentStats['total_collected']) * 100, 2); ?>%</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: 700; background: var(--success-light);">
                                    <td colspan="3" style="text-align: right;">GRAND TOTAL:</td>
                                    <td><?php echo array_sum(array_column($dateWisePayments, 'transaction_count')); ?></td>
                                    <td>₹<?php echo number_format($paymentStats['total_collected'], 0); ?></td>
                                    <td>100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Daily Summary -->
                    <div style="margin-top: var(--spacing-xl);">
                        <h4>Daily Collection Summary</h4>
                        <div class="stats-grid">
                            <?php
                            // Get unique dates and calculate daily totals
                            $dailyTotals = [];
                            foreach ($dateWisePayments as $payment) {
                                $date = $payment['payment_date'];
                                if (!isset($dailyTotals[$date])) {
                                    $dailyTotals[$date] = 0;
                                }
                                $dailyTotals[$date] += $payment['daily_amount'];
                            }

                            // Sort by date descending and show top days
                            arsort($dailyTotals);
                            $topDays = array_slice($dailyTotals, 0, 4, true);

                            foreach ($topDays as $date => $total):
                                $percentage = $paymentStats['total_collected'] > 0
                                    ? ($total / $paymentStats['total_collected']) * 100
                                    : 0;
                            ?>
                                <div class="stat-card">
                                    <div class="stat-label"><?php echo date('M d, Y', strtotime($date)); ?></div>
                                    <div class="stat-value" style="color: var(--primary-color);">₹<?php echo number_format($total, 0); ?></div>
                                    <div style="margin-top: var(--spacing-xs); font-size: var(--font-size-sm); color: var(--gray-600);">
                                        <?php echo number_format($percentage, 2); ?>% of total
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab 4: Payment Status -->
            <div id="payment-status" class="tab-content">
                <h3>Payment Status Breakdown</h3>
                <div class="stats-grid" style="margin-top: var(--spacing-lg);">
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--success-color);"><?php echo $paymentStats['paid_count']; ?></div>
                        <div class="stat-label">Fully Paid</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--warning-color);"><?php echo $paymentStats['partial_count']; ?></div>
                        <div class="stat-label">Partially Paid</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--danger-color);"><?php echo $paymentStats['unpaid_count']; ?></div>
                        <div class="stat-label">Unpaid</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $paymentStats['total_transactions']; ?></div>
                        <div class="stat-label">Total Transactions</div>
                    </div>
                </div>

                <div style="margin-top: var(--spacing-xl);">
                    <h4>Collection Rate</h4>
                    <div style="background: var(--gray-50); padding: var(--spacing-lg); border-radius: var(--radius-md);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-sm);">
                            <span>Collected: ₹<?php echo number_format($paymentStats['total_collected'], 0); ?></span>
                            <span><?php echo number_format($collectionPercent, 2); ?>%</span>
                        </div>
                        <div style="height: 20px; background: var(--gray-200); border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo min($collectionPercent, 100); ?>%; height: 100%; background: var(--success-color);"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Book Status -->
            <div id="book-status" class="tab-content">
                <h3>Book Status Overview</h3>
                <div class="stats-grid" style="margin-top: var(--spacing-lg);">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_books']; ?></div>
                        <div class="stat-label">Total Books Generated</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--success-color);"><?php echo $stats['distributed_books']; ?></div>
                        <div class="stat-label">Distributed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--warning-color);"><?php echo $stats['available_books']; ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['collected_books']; ?></div>
                        <div class="stat-label">Collected (Paid)</div>
                    </div>
                </div>

                <div style="margin-top: var(--spacing-xl);">
                    <h4>Distribution Rate</h4>
                    <div style="background: var(--gray-50); padding: var(--spacing-lg); border-radius: var(--radius-md);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-sm);">
                            <span>Distributed: <?php echo $stats['distributed_books']; ?> of <?php echo $stats['total_books']; ?></span>
                            <span><?php echo number_format($distributionPercent, 2); ?>%</span>
                        </div>
                        <div style="height: 20px; background: var(--gray-200); border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo min($distributionPercent, 100); ?>%; height: 100%; background: var(--primary-color);"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Summary -->
            <div id="summary" class="tab-content">
                <h3>Event Summary</h3>
                <div style="background: var(--gray-50); padding: var(--spacing-lg); border-radius: var(--radius-md); margin-top: var(--spacing-lg);">
                    <div style="margin-bottom: var(--spacing-md);"><strong>Event Name:</strong> <?php echo htmlspecialchars($event['event_name']); ?></div>
                    <div style="margin-bottom: var(--spacing-md);"><strong>Description:</strong> <?php echo htmlspecialchars($event['event_description'] ?? 'N/A'); ?></div>
                    <div style="margin-bottom: var(--spacing-md);"><strong>Created:</strong> <?php echo date('M d, Y', strtotime($event['created_at'])); ?></div>
                    <div style="margin-bottom: var(--spacing-md);"><strong>Status:</strong>
                        <?php if ($event['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo ucfirst($event['status']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <h4 style="margin-top: var(--spacing-xl);">Financial Summary</h4>
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <td><strong>Total Books:</strong></td>
                            <td><?php echo $event['total_books']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Tickets:</strong></td>
                            <td><?php echo $event['total_tickets']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Price per Ticket:</strong></td>
                            <td>₹<?php echo $event['price_per_ticket']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Expected Total Amount:</strong></td>
                            <td><strong>₹<?php echo number_format($event['total_predicted_amount'], 0); ?></strong></td>
                        </tr>
                        <tr style="background: var(--success-light);">
                            <td><strong>Amount Collected:</strong></td>
                            <td><strong style="color: var(--success-color);">₹<?php echo number_format($paymentStats['total_collected'], 0); ?></strong></td>
                        </tr>
                        <tr style="background: var(--danger-light);">
                            <td><strong>Outstanding Amount:</strong></td>
                            <td><strong style="color: var(--danger-color);">₹<?php echo number_format($event['total_predicted_amount'] - $paymentStats['total_collected'], 0); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 5: Commission Summary -->
        <?php if ($commissionEnabled): ?>
            <div id="commission" class="tab-content">
                <h3>💰 Commission Summary Report</h3>
                <p style="color: var(--gray-600); margin-bottom: var(--spacing-lg);">Breakdown of commission earned by type and distribution level</p>

                <?php if (count($commissionStats) === 0): ?>
                    <p style="text-align: center; color: var(--gray-500); padding: var(--spacing-xl);">No commission data available yet</p>
                <?php else: ?>
                    <!-- Total Commission Card -->
                    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: var(--spacing-xl); border-radius: var(--radius-lg); margin-bottom: var(--spacing-xl); box-shadow: var(--shadow-lg);">
                        <div style="text-align: center;">
                            <div style="font-size: var(--font-size-sm); opacity: 0.9; margin-bottom: var(--spacing-xs);">TOTAL COMMISSION EARNED</div>
                            <div style="font-size: 3rem; font-weight: 700;">₹<?php echo number_format($totalCommission, 2); ?></div>
                        </div>
                    </div>

                    <!-- Commission by Type - Summary Cards -->
                    <h4 style="margin-bottom: var(--spacing-md);">Commission by Type</h4>
                    <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
                        <?php foreach ($commissionStats as $comm): ?>
                            <?php
                                $percentage = $totalCommission > 0
                                    ? ($comm['total_commission_earned'] / $totalCommission) * 100
                                    : 0;
                                $icon = match($comm['commission_type']) {
                                    'early' => '🏃',
                                    'standard' => '💼',
                                    'extra_books' => '📚',
                                    default => '💰'
                                };
                                $label = match($comm['commission_type']) {
                                    'early' => 'Early Payment',
                                    'standard' => 'Standard Payment',
                                    'extra_books' => 'Extra Books',
                                    default => ucfirst($comm['commission_type'])
                                };
                                $color = match($comm['commission_type']) {
                                    'early' => '#10b981',
                                    'standard' => '#3b82f6',
                                    'extra_books' => '#f59e0b',
                                    default => '#6366f1'
                                };
                            ?>
                            <div class="stat-card" style="border-left: 4px solid <?php echo $color; ?>;">
                                <div style="font-size: var(--font-size-2xl); margin-bottom: var(--spacing-xs);"><?php echo $icon; ?></div>
                                <div class="stat-label" style="font-weight: 600; margin-bottom: var(--spacing-sm);">
                                    <?php echo htmlspecialchars($label); ?>
                                </div>
                                <div class="stat-value" style="color: <?php echo $color; ?>;">
                                    ₹<?php echo number_format($comm['total_commission_earned'], 2); ?>
                                </div>
                                <div style="margin-top: var(--spacing-xs); color: var(--gray-600); font-size: var(--font-size-sm);">
                                    <?php echo number_format($percentage, 2); ?>% of total
                                </div>
                                <div style="margin-top: var(--spacing-xs); color: var(--gray-500); font-size: var(--font-size-xs);">
                                    <?php echo $comm['payment_count']; ?> payments • Avg: <?php echo number_format($comm['avg_commission_percent'], 2); ?>%
                                </div>
                                <div style="margin-top: var(--spacing-xs); padding-top: var(--spacing-xs); border-top: 1px solid var(--gray-200); color: var(--gray-600); font-size: var(--font-size-xs);">
                                    Payment Total: ₹<?php echo number_format($comm['total_payment_amount'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Detailed Table by Type -->
                    <div class="table-responsive" style="margin-bottom: var(--spacing-xl);">
                        <table class="table" id="commissionTypeTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Commission Type</th>
                                    <th>Number of Payments</th>
                                    <th>Total Payment Amount</th>
                                    <th>Avg Commission %</th>
                                    <th>Total Commission Earned</th>
                                    <th>% of Total Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commissionStats as $index => $comm):
                                    $percentage = $totalCommission > 0
                                        ? ($comm['total_commission_earned'] / $totalCommission) * 100
                                        : 0;
                                    $icon = match($comm['commission_type']) {
                                        'early' => '🏃',
                                        'standard' => '💼',
                                        'extra_books' => '📚',
                                        default => '💰'
                                    };
                                    $label = match($comm['commission_type']) {
                                        'early' => 'Early Payment',
                                        'standard' => 'Standard Payment',
                                        'extra_books' => 'Extra Books',
                                        default => ucfirst($comm['commission_type'])
                                    };
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo $icon; ?> <?php echo htmlspecialchars($label); ?></strong></td>
                                        <td><?php echo $comm['payment_count']; ?></td>
                                        <td>₹<?php echo number_format($comm['total_payment_amount'], 2); ?></td>
                                        <td><?php echo number_format($comm['avg_commission_percent'], 2); ?>%</td>
                                        <td><strong style="color: var(--success-color);">₹<?php echo number_format($comm['total_commission_earned'], 2); ?></strong></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                                <div style="flex: 1; height: 20px; background: var(--gray-200); border-radius: 10px; overflow: hidden;">
                                                    <div style="width: <?php echo min($percentage, 100); ?>%; height: 100%; background: var(--success-color);"></div>
                                                </div>
                                                <span style="min-width: 60px; font-weight: 600;"><?php echo number_format($percentage, 2); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: 700; background: var(--gray-50);">
                                    <td colspan="2" style="text-align: right;">TOTAL:</td>
                                    <td><?php echo array_sum(array_column($commissionStats, 'payment_count')); ?></td>
                                    <td>₹<?php echo number_format(array_sum(array_column($commissionStats, 'total_payment_amount')), 2); ?></td>
                                    <td>-</td>
                                    <td>₹<?php echo number_format($totalCommission, 2); ?></td>
                                    <td>100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Commission by Level 1 -->
                    <h4 style="margin-bottom: var(--spacing-md);">Commission Breakdown by Distribution Level (Level 1)</h4>
                    <div class="table-responsive">
                        <table class="table" id="commissionLevelTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Level 1 Name</th>
                                    <th>Commission Type</th>
                                    <th>Number of Payments</th>
                                    <th>Total Payment Amount</th>
                                    <th>Commission Earned</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rowNum = 1;
                                $currentLevel = null;
                                $levelTotal = 0;
                                $levelPaymentTotal = 0;
                                $levelCount = 0;
                                $levelRowStart = 0;

                                foreach ($commissionByLevel as $index => $comm):
                                    // Calculate level totals when level changes
                                    if ($currentLevel !== null && $currentLevel !== $comm['level_1_value']) {
                                        ?>
                                        <tr style="background: var(--gray-100); font-weight: 600;">
                                            <td colspan="3" style="text-align: right;">Subtotal for <?php echo htmlspecialchars($currentLevel); ?>:</td>
                                            <td><?php echo $levelCount; ?></td>
                                            <td>₹<?php echo number_format($levelPaymentTotal, 2); ?></td>
                                            <td style="color: var(--success-color);">₹<?php echo number_format($levelTotal, 2); ?></td>
                                        </tr>
                                        <?php
                                        $levelTotal = 0;
                                        $levelPaymentTotal = 0;
                                        $levelCount = 0;
                                    }

                                    $currentLevel = $comm['level_1_value'];
                                    $levelTotal += $comm['total_commission'];
                                    $levelPaymentTotal += $comm['total_payment'];
                                    $levelCount += $comm['payment_count'];

                                    $icon = match($comm['commission_type']) {
                                        'early' => '🏃',
                                        'standard' => '💼',
                                        'extra_books' => '📚',
                                        default => '💰'
                                    };
                                    $label = match($comm['commission_type']) {
                                        'early' => 'Early Payment',
                                        'standard' => 'Standard Payment',
                                        'extra_books' => 'Extra Books',
                                        default => ucfirst($comm['commission_type'])
                                    };
                                ?>
                                    <tr>
                                        <td><?php echo $rowNum++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($comm['level_1_value']); ?></strong></td>
                                        <td><?php echo $icon; ?> <?php echo htmlspecialchars($label); ?></td>
                                        <td><?php echo $comm['payment_count']; ?></td>
                                        <td>₹<?php echo number_format($comm['total_payment'], 2); ?></td>
                                        <td style="color: var(--success-color);"><strong>₹<?php echo number_format($comm['total_commission'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if ($currentLevel !== null): ?>
                                    <tr style="background: var(--gray-100); font-weight: 600;">
                                        <td colspan="3" style="text-align: right;">Subtotal for <?php echo htmlspecialchars($currentLevel); ?>:</td>
                                        <td><?php echo $levelCount; ?></td>
                                        <td>₹<?php echo number_format($levelPaymentTotal, 2); ?></td>
                                        <td style="color: var(--success-color);">₹<?php echo number_format($levelTotal, 2); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: 700; background: var(--gray-50);">
                                    <td colspan="3" style="text-align: right;">GRAND TOTAL:</td>
                                    <td><?php echo array_sum(array_column($commissionByLevel, 'payment_count')); ?></td>
                                    <td>₹<?php echo number_format(array_sum(array_column($commissionByLevel, 'total_payment')), 2); ?></td>
                                    <td style="color: var(--success-color);">₹<?php echo number_format(array_sum(array_column($commissionByLevel, 'total_commission')), 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Export Button -->
                    <div style="margin-top: var(--spacing-xl); text-align: center;">
                        <button class="btn btn-primary" onclick="exportTableToExcel('commissionTypeTable', 'Commission_by_Type')">
                            📥 Export Commission by Type
                        </button>
                        <button class="btn btn-primary" onclick="exportTableToExcel('commissionLevelTable', 'Commission_by_Level')">
                            📥 Export Commission by Level
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function switchTab(event, tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
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
            document.getElementById('reportFilterForm').submit();
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

        function exportToCSV() {
            const table = document.getElementById('memberTable');
            let csv = [];

            // Headers
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            csv.push(headers.join(','));

            // Rows
            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach(td => {
                    let text = td.textContent.trim().replace(/,/g, '');
                    row.push(text);
                });
                csv.push(row.join(','));
            });

            // Download
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'lottery_report_<?php echo date('Y-m-d'); ?>.csv';
            a.click();
        }

        function copyToClipboard() {
            const table = document.getElementById('memberTable');
            const range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand('copy');
            window.getSelection().removeAllRanges();
            alert('Report copied to clipboard!');
        }
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
