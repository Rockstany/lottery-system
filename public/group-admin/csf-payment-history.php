<?php
/**
 * CSF Payment History - View payment records with filters
 * Optimized for 50+ age group users
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

// Authentication
AuthMiddleware::requireRole('group_admin');
$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

// Feature access check
$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'csf_funds')) {
    $_SESSION['error_message'] = "CSF Funds is not enabled for your community";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle delete payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_payment') {
    $payment_id = $_POST['payment_id'];

    try {
        $stmt = $db->prepare("DELETE FROM csf_payments WHERE payment_id = ? AND community_id = ?");
        $stmt->execute([$payment_id, $communityId]);
        $success_message = "Payment record deleted successfully";
    } catch (Exception $e) {
        $error_message = "Error deleting payment: " . $e->getMessage();
    }
}

// Handle edit payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_payment') {
    $payment_id = $_POST['payment_id'];
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    try {
        $stmt = $db->prepare("UPDATE csf_payments
                              SET amount = ?, payment_method = ?, transaction_id = ?, notes = ?
                              WHERE payment_id = ? AND community_id = ?");
        $stmt->execute([$amount, $payment_method, $transaction_id, $notes, $payment_id, $communityId]);
        $success_message = "Payment record updated successfully";
    } catch (Exception $e) {
        $error_message = "Error updating payment: " . $e->getMessage();
    }
}

// Get filter parameters
$filter_user = $_GET['user_id'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_year = $_GET['year'] ?? date('Y');
$filter_method = $_GET['payment_method'] ?? '';
$search_query = $_GET['search'] ?? '';

// Sorting parameters
$sort_column = $_GET['sort'] ?? 'date';
$sort_direction = $_GET['dir'] ?? 'desc';
$allowed_sorts = ['date', 'month', 'member', 'amount'];
$allowed_dirs = ['asc', 'desc'];
if (!in_array($sort_column, $allowed_sorts)) $sort_column = 'date';
if (!in_array($sort_direction, $allowed_dirs)) $sort_direction = 'desc';

// Pagination parameters
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build base query for counting and fetching
$base_sql = "FROM csf_payments cp
        INNER JOIN users u ON cp.user_id = u.user_id
        LEFT JOIN users recorder ON cp.collected_by = recorder.user_id
        WHERE cp.community_id = ?";

$params = [$communityId];

if ($filter_user) {
    $base_sql .= " AND cp.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_month) {
    $base_sql .= " AND MONTH(cp.payment_date) = ?";
    $params[] = $filter_month;
}

if ($filter_year) {
    $base_sql .= " AND YEAR(cp.payment_date) = ?";
    $params[] = $filter_year;
}

if ($filter_method) {
    $base_sql .= " AND cp.payment_method = ?";
    $params[] = $filter_method;
}

if ($search_query) {
    $base_sql .= " AND (u.full_name LIKE ? OR cp.transaction_id LIKE ? OR cp.notes LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total " . $base_sql;
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $per_page);

// Get sum of amounts for filtered results
$sum_sql = "SELECT SUM(cp.amount) as total_amount " . $base_sql;
$stmt = $db->prepare($sum_sql);
$stmt->execute($params);
$total_amount = $stmt->fetch(PDO::FETCH_ASSOC)['total_amount'] ?? 0;

// Build ORDER BY clause based on sort column
$order_by = match($sort_column) {
    'date' => "cp.payment_date $sort_direction, cp.created_at $sort_direction",
    'month' => "cp.payment_for_months $sort_direction, cp.payment_date $sort_direction",
    'member' => "u.full_name $sort_direction",
    'amount' => "cp.amount $sort_direction",
    default => "cp.payment_date DESC, cp.created_at DESC"
};

// Build query with sorting and pagination
$sql = "SELECT
            cp.payment_id,
            cp.amount,
            cp.payment_date,
            cp.payment_method,
            cp.transaction_id,
            cp.notes,
            cp.payment_for_months,
            cp.created_at as recorded_at,
            u.full_name,
            u.mobile_number as phone,
            recorder.full_name as recorded_by_name
        " . $base_sql . " ORDER BY $order_by LIMIT $per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Quick Stats for specific month filter
$quick_stats = null;
if ($filter_month && $filter_year) {
    // Get payment method breakdown
    $stats_sql = "SELECT
                    cp.payment_method,
                    COUNT(*) as count,
                    SUM(cp.amount) as total
                  FROM csf_payments cp
                  WHERE cp.community_id = ?
                  AND MONTH(cp.payment_date) = ?
                  AND YEAR(cp.payment_date) = ?
                  GROUP BY cp.payment_method";
    $stmt = $db->prepare($stats_sql);
    $stmt->execute([$communityId, $filter_month, $filter_year]);
    $method_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique members who paid
    $unique_sql = "SELECT COUNT(DISTINCT cp.user_id) as unique_members
                   FROM csf_payments cp
                   WHERE cp.community_id = ?
                   AND MONTH(cp.payment_date) = ?
                   AND YEAR(cp.payment_date) = ?";
    $stmt = $db->prepare($unique_sql);
    $stmt->execute([$communityId, $filter_month, $filter_year]);
    $unique_members = $stmt->fetch(PDO::FETCH_ASSOC)['unique_members'];

    $quick_stats = [
        'month_name' => date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)),
        'method_breakdown' => $method_breakdown,
        'unique_members' => $unique_members
    ];
}

// Get all members for filter dropdown
$stmt = $db->prepare("SELECT scm.user_id, u.full_name
                       FROM sub_community_members scm
                       JOIN users u ON scm.user_id = u.user_id
                       JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                       WHERE sc.community_id = ? AND scm.status = 'active'
                       ORDER BY u.full_name");
$stmt->execute([$communityId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to build sort URL
function buildSortUrl($column, $currentSort, $currentDir, $params) {
    $newDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $params['sort'] = $column;
    $params['dir'] = $newDir;
    unset($params['page']); // Reset to page 1 on sort change
    return '?' . http_build_query($params);
}

// Helper function to build pagination URL
function buildPageUrl($page, $params) {
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// Current filter params for URL building
$current_params = array_filter([
    'user_id' => $filter_user,
    'month' => $filter_month,
    'year' => $filter_year,
    'payment_method' => $filter_method,
    'search' => $search_query,
    'sort' => $sort_column,
    'dir' => $sort_direction
]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - CSF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-size: 18px;
            line-height: 1.8;
            background-color: #f8f9fa;
        }

        .main-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .header-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-section h1 {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #007bff;
        }

        .summary-card.success {
            border-left-color: #28a745;
        }

        .summary-label {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .summary-value {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }

        .filter-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filter-section h3 {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .form-control, .form-select {
            font-size: 18px;
            padding: 12px 16px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            min-height: 50px;
        }

        .btn-custom {
            font-size: 20px;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            min-width: 150px;
        }

        .btn-filter {
            background: #007bff;
            color: white;
            border: none;
        }

        .btn-filter:hover {
            background: #0056b3;
        }

        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
        }

        .btn-reset:hover {
            background: #545b62;
        }

        .payments-table {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .table {
            font-size: 18px;
            margin-bottom: 0;
        }

        .table thead th {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            background: #f8f9fa;
            border-bottom: 3px solid #dee2e6;
            padding: 20px 15px;
        }

        .table tbody td {
            padding: 20px 15px;
            vertical-align: middle;
        }

        .payment-method-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 600;
        }

        .method-cash {
            background: #d4edda;
            color: #155724;
        }

        .method-upi {
            background: #cce5ff;
            color: #004085;
        }

        .method-bank {
            background: #fff3cd;
            color: #856404;
        }

        .method-cheque {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 16px;
            font-size: 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: #007bff;
            color: white;
        }

        .btn-edit:hover {
            background: #0056b3;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .no-payments {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-payments i {
            font-size: 80px;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        .no-payments h3 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .back-link {
            font-size: 20px;
            color: #007bff;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .back-link:hover {
            color: #0056b3;
        }

        .alert-custom {
            font-size: 20px;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .amount-display {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
        }

        .modal-content {
            border-radius: 15px;
        }

        .modal-header {
            background: #dc3545;
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-header.edit-header {
            background: #007bff;
        }

        .modal-title {
            font-size: 24px;
            font-weight: bold;
        }

        .modal-body {
            font-size: 20px;
            padding: 30px;
        }

        .modal-footer .btn {
            font-size: 20px;
            padding: 12px 30px;
        }

        /* Sortable headers */
        .sortable {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }

        .sortable:hover {
            background: #e9ecef;
        }

        .sortable i {
            margin-left: 8px;
            font-size: 14px;
            color: #6c757d;
        }

        .sortable.active i {
            color: #007bff;
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagination-info {
            font-size: 18px;
            color: #6c757d;
        }

        .pagination {
            margin: 0;
        }

        .pagination .page-link {
            font-size: 18px;
            padding: 12px 18px;
            color: #007bff;
            border: 2px solid #dee2e6;
            margin: 0 3px;
            border-radius: 8px;
        }

        .pagination .page-link:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination .page-item.active .page-link {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background: #f8f9fa;
        }

        /* Quick Stats */
        .quick-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .quick-stats h4 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .quick-stats h4 i {
            margin-right: 10px;
        }

        .quick-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .quick-stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
        }

        .quick-stat-item .stat-value {
            font-size: 28px;
            font-weight: bold;
            display: block;
        }

        .quick-stat-item .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="csf-funds.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to CSF Funds
        </a>

        <div class="header-section">
            <h1><i class="fas fa-history"></i> Payment History</h1>
            <p class="mb-0">View and manage all CSF payment records</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-custom">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-custom">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-label">Total Payments</div>
                <div class="summary-value"><?php echo $total_records; ?></div>
            </div>
            <div class="summary-card success">
                <div class="summary-label">Total Amount</div>
                <div class="summary-value">₹<?php echo number_format($total_amount, 2); ?></div>
            </div>
        </div>

        <?php if ($quick_stats): ?>
        <div class="quick-stats">
            <h4><i class="fas fa-chart-pie"></i> Quick Stats for <?php echo $quick_stats['month_name']; ?></h4>
            <div class="quick-stats-grid">
                <div class="quick-stat-item">
                    <span class="stat-value"><?php echo $quick_stats['unique_members']; ?></span>
                    <span class="stat-label">Members Paid</span>
                </div>
                <div class="quick-stat-item">
                    <span class="stat-value"><?php echo $total_records; ?></span>
                    <span class="stat-label">Total Transactions</span>
                </div>
                <?php
                $method_labels = [
                    'cash' => 'Cash',
                    'upi' => 'UPI',
                    'bank_transfer' => 'Bank',
                    'cheque' => 'Cheque'
                ];
                foreach ($quick_stats['method_breakdown'] as $method):
                    $label = $method_labels[$method['payment_method']] ?? $method['payment_method'];
                ?>
                <div class="quick-stat-item">
                    <span class="stat-value"><?php echo $method['count']; ?></span>
                    <span class="stat-label"><?php echo $label; ?> (₹<?php echo number_format($method['total']); ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Filter Payments</h3>
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Member</label>
                        <select class="form-select" name="user_id">
                            <option value="">All Members</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo $member['user_id']; ?>"
                                        <?php echo $filter_user == $member['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($member['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Month</label>
                        <select class="form-select" name="month">
                            <option value="">All Months</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $filter_month == $m ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Year</label>
                        <select class="form-select" name="year">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method">
                            <option value="">All Methods</option>
                            <option value="cash" <?php echo $filter_method == 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="upi" <?php echo $filter_method == 'upi' ? 'selected' : ''; ?>>UPI</option>
                            <option value="bank_transfer" <?php echo $filter_method == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="cheque" <?php echo $filter_method == 'cheque' ? 'selected' : ''; ?>>Cheque</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search"
                               placeholder="Name, reference, notes..."
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-custom btn-filter">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="csf-payment-history.php" class="btn btn-custom btn-reset">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="payments-table">
            <?php if (empty($payments)): ?>
                <div class="no-payments">
                    <i class="fas fa-receipt"></i>
                    <h3>No Payment Records Found</h3>
                    <p>No payments match your current filters</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="sortable <?php echo $sort_column === 'month' ? 'active' : ''; ?>">
                                    <a href="<?php echo buildSortUrl('month', $sort_column, $sort_direction, $current_params); ?>" style="color: inherit; text-decoration: none;">
                                        Month
                                        <i class="fas fa-sort<?php echo $sort_column === 'month' ? ($sort_direction === 'asc' ? '-up' : '-down') : ''; ?>"></i>
                                    </a>
                                </th>
                                <th class="sortable <?php echo $sort_column === 'date' ? 'active' : ''; ?>">
                                    <a href="<?php echo buildSortUrl('date', $sort_column, $sort_direction, $current_params); ?>" style="color: inherit; text-decoration: none;">
                                        Date
                                        <i class="fas fa-sort<?php echo $sort_column === 'date' ? ($sort_direction === 'asc' ? '-up' : '-down') : ''; ?>"></i>
                                    </a>
                                </th>
                                <th class="sortable <?php echo $sort_column === 'member' ? 'active' : ''; ?>">
                                    <a href="<?php echo buildSortUrl('member', $sort_column, $sort_direction, $current_params); ?>" style="color: inherit; text-decoration: none;">
                                        Member
                                        <i class="fas fa-sort<?php echo $sort_column === 'member' ? ($sort_direction === 'asc' ? '-up' : '-down') : ''; ?>"></i>
                                    </a>
                                </th>
                                <th class="sortable <?php echo $sort_column === 'amount' ? 'active' : ''; ?>">
                                    <a href="<?php echo buildSortUrl('amount', $sort_column, $sort_direction, $current_params); ?>" style="color: inherit; text-decoration: none;">
                                        Amount
                                        <i class="fas fa-sort<?php echo $sort_column === 'amount' ? ($sort_direction === 'asc' ? '-up' : '-down') : ''; ?>"></i>
                                    </a>
                                </th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <?php
                                        // Display the month(s) the payment is FOR
                                        $paymentForMonths = json_decode($payment['payment_for_months'], true);
                                        if (!empty($paymentForMonths) && is_array($paymentForMonths)) {
                                            $formattedMonths = array_map(function($m) {
                                                $monthDate = DateTime::createFromFormat('Y-m', $m);
                                                return $monthDate ? $monthDate->format('M Y') : $m;
                                            }, $paymentForMonths);
                                            echo htmlspecialchars(implode(', ', $formattedMonths));
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $date = new DateTime($payment['payment_date']);
                                        echo $date->format('d M Y');
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payment['full_name']); ?></strong>
                                        <?php if ($payment['phone']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($payment['phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="amount-display">
                                            ₹<?php echo number_format($payment['amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $method_class = 'method-' . str_replace('_', '-', $payment['payment_method']);
                                        $method_label = [
                                            'cash' => 'Cash',
                                            'upi' => 'UPI',
                                            'bank_transfer' => 'Bank Transfer',
                                            'cheque' => 'Cheque'
                                        ][$payment['payment_method']] ?? $payment['payment_method'];
                                        ?>
                                        <span class="payment-method-badge <?php echo $method_class; ?>">
                                            <?php echo $method_label; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($payment['transaction_id']): ?>
                                            <code><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                        <?php if ($payment['notes']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($payment['notes']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($payment['recorded_by_name']); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php
                                            $recorded = new DateTime($payment['recorded_at']);
                                            echo $recorded->format('d M Y, g:i A');
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-action btn-edit"
                                                    onclick="openEditModal(<?php echo $payment['payment_id']; ?>, '<?php echo htmlspecialchars($payment['full_name'], ENT_QUOTES); ?>', <?php echo $payment['amount']; ?>, '<?php echo $payment['payment_method']; ?>', '<?php echo htmlspecialchars($payment['transaction_id'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($payment['notes'] ?? '', ENT_QUOTES); ?>')">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn-action btn-delete"
                                                    onclick="confirmDelete(<?php echo $payment['payment_id']; ?>, '<?php echo htmlspecialchars($payment['full_name'], ENT_QUOTES); ?>', <?php echo $payment['amount']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> payments
                    </div>
                    <nav>
                        <ul class="pagination">
                            <!-- Previous button -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $page > 1 ? buildPageUrl($page - 1, $current_params) : '#'; ?>">
                                    <i class="fas fa-chevron-left"></i> Prev
                                </a>
                            </li>

                            <?php
                            // Calculate page range to show
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            // Always show first page
                            if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPageUrl(1, $current_params); ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildPageUrl($i, $current_params); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPageUrl($total_pages, $current_params); ?>"><?php echo $total_pages; ?></a>
                                </li>
                            <?php endif; ?>

                            <!-- Next button -->
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $page < $total_pages ? buildPageUrl($page + 1, $current_params) : '#'; ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this payment record?</p>
                    <p><strong>Member:</strong> <span id="delete-member-name"></span></p>
                    <p><strong>Amount:</strong> ₹<span id="delete-amount"></span></p>
                    <p class="text-danger mt-3"><strong>This action cannot be undone!</strong></p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete_payment">
                        <input type="hidden" name="payment_id" id="delete-payment-id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header edit-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_payment">
                        <input type="hidden" name="payment_id" id="edit-payment-id">

                        <p class="mb-4"><strong>Member:</strong> <span id="edit-member-name"></span></p>

                        <div class="mb-4">
                            <label class="form-label">Amount (₹)</label>
                            <input type="number" class="form-control" name="amount" id="edit-amount"
                                   step="0.01" min="0" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" id="edit-payment-method" required>
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Transaction ID / Reference</label>
                            <input type="text" class="form-control" name="transaction_id" id="edit-transaction-id"
                                   placeholder="Optional">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit-notes" rows="3"
                                      placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(paymentId, memberName, amount) {
            document.getElementById('delete-payment-id').value = paymentId;
            document.getElementById('delete-member-name').textContent = memberName;
            document.getElementById('delete-amount').textContent = parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function openEditModal(paymentId, memberName, amount, paymentMethod, transactionId, notes) {
            document.getElementById('edit-payment-id').value = paymentId;
            document.getElementById('edit-member-name').textContent = memberName;
            document.getElementById('edit-amount').value = amount;
            document.getElementById('edit-payment-method').value = paymentMethod;
            document.getElementById('edit-transaction-id').value = transactionId;
            document.getElementById('edit-notes').value = notes;

            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
