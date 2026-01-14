<?php
/**
 * CSF Reports - Dashboard showing paid/unpaid members and contribution analytics
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

// Get date range (start month/year to end month/year)
$start_month = $_GET['start_month'] ?? date('m');
$start_year = $_GET['start_year'] ?? date('Y');
$end_month = $_GET['end_month'] ?? date('m');
$end_year = $_GET['end_year'] ?? date('Y');

// Create date strings for comparison
$start_date = $start_year . '-' . str_pad($start_month, 2, '0', STR_PAD_LEFT);
$end_date = $end_year . '-' . str_pad($end_month, 2, '0', STR_PAD_LEFT);

// Default monthly contribution (can be customized later)
$monthly_contribution = 100;

// Get all members in the community
$stmt = $db->prepare("SELECT scm.user_id, u.full_name, u.mobile_number as phone, u.email
                       FROM sub_community_members scm
                       JOIN users u ON scm.user_id = u.user_id
                       JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                       WHERE sc.community_id = ? AND scm.status = 'active'
                       ORDER BY u.full_name");
$stmt->execute([$communityId]);
$all_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate list of months in the date range
$months_in_range = [];
$current = new DateTime($start_date . '-01');
$end = new DateTime($end_date . '-01');

while ($current <= $end) {
    $months_in_range[] = $current->format('Y-m');
    $current->modify('+1 month');
}

// Get ALL payments within the date range
$stmt = $db->prepare("SELECT
                           cp.user_id,
                           cp.amount,
                           cp.payment_date,
                           cp.payment_method,
                           cp.transaction_id,
                           cp.payment_for_months,
                           cp.created_at
                       FROM csf_payments cp
                       WHERE cp.community_id = ?
                       AND cp.payment_for_months REGEXP ?
                       ORDER BY cp.payment_date DESC");

// Create regex pattern to match any month in range
$month_pattern = implode('|', array_map(function($m) {
    return preg_quote($m, '/');
}, $months_in_range));

$stmt->execute([$communityId, $month_pattern]);
$all_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create payment lookup array grouped by user and month
$payment_lookup = [];
$month_wise_data = []; // Track per-month statistics

// Initialize month-wise data
foreach ($months_in_range as $month) {
    $month_wise_data[$month] = [
        'paid_users' => [],
        'total_amount' => 0,
        'paid_count' => 0
    ];
}

foreach ($all_payments as $payment) {
    $user_id = $payment['user_id'];

    // Extract month from payment_for_months JSON
    $months_json = json_decode($payment['payment_for_months'], true);
    if (!empty($months_json) && is_array($months_json)) {
        $payment_month = $months_json[0]; // e.g., "2026-01"

        // Only include if month is in our range
        if (in_array($payment_month, $months_in_range)) {
            if (!isset($payment_lookup[$user_id])) {
                $payment_lookup[$user_id] = [
                    'months_paid' => [],
                    'month_details' => [], // Store per-month details
                    'total_amount' => 0,
                    'payment_count' => 0
                ];
            }

            $payment_lookup[$user_id]['months_paid'][] = $payment_month;
            $payment_lookup[$user_id]['month_details'][$payment_month] = [
                'amount' => $payment['amount'],
                'method' => $payment['payment_method'],
                'date' => $payment['payment_date']
            ];
            $payment_lookup[$user_id]['total_amount'] += $payment['amount'];
            $payment_lookup[$user_id]['payment_count']++;

            // Update month-wise stats
            $month_wise_data[$payment_month]['paid_users'][] = $user_id;
            $month_wise_data[$payment_month]['total_amount'] += $payment['amount'];
            $month_wise_data[$payment_month]['paid_count']++;
        }
    }
}

// Classify members: paid at least once in range vs never paid in range
$paid_members = [];
$unpaid_members = [];

foreach ($all_members as $member) {
    $user_id = $member['user_id'];

    if (isset($payment_lookup[$user_id]) && !empty($payment_lookup[$user_id]['months_paid'])) {
        // Member paid for at least one month in the range
        $member['payment_info'] = $payment_lookup[$user_id];
        $paid_members[] = $member;
    } else {
        // Member has not paid for any month in the range
        $member['payment_info'] = null;
        $unpaid_members[] = $member;
    }
}

// Calculate statistics
$total_members = count($all_members);
$paid_count = count($paid_members);
$unpaid_count = count($unpaid_members);
$partial_count = 0; // No longer used, but kept for backward compatibility with charts
$collection_rate = $total_members > 0 ? ($paid_count / $total_members) * 100 : 0;

// Total collected for selected month
$total_collected = array_sum($monthly_payment_lookup);

// Average payment for selected month
$average_payment = $paid_count > 0 ? $total_collected / $paid_count : 0;

// Get yearly statistics
$stmt = $db->prepare("SELECT
                           MONTH(payment_date) as month,
                           SUM(amount) as monthly_total,
                           COUNT(DISTINCT user_id) as unique_payers
                       FROM csf_payments
                       WHERE community_id = ? AND YEAR(payment_date) = ?
                       GROUP BY MONTH(payment_date)
                       ORDER BY month");
$stmt->execute([$communityId, $selected_year]);
$yearly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create monthly data array
$monthly_stats = array_fill(1, 12, ['total' => 0, 'payers' => 0]);
foreach ($yearly_data as $data) {
    $monthly_stats[$data['month']] = [
        'total' => $data['monthly_total'],
        'payers' => $data['unique_payers']
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - CSF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
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

        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-label {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .form-select {
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
        }

        .btn-filter {
            background: #007bff;
            color: white;
            border: none;
        }

        .btn-filter:hover {
            background: #0056b3;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #007bff;
        }

        .stat-card.success {
            border-left-color: #28a745;
        }

        .stat-card.warning {
            border-left-color: #ffc107;
        }

        .stat-card.danger {
            border-left-color: #dc3545;
        }

        .stat-label {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }

        .section-header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            border-left: 5px solid #007bff;
            margin-top: 30px;
        }

        .section-header h2 {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }

        .section-header.success {
            border-left-color: #28a745;
        }

        .section-header.warning {
            border-left-color: #ffc107;
        }

        .section-header.danger {
            border-left-color: #dc3545;
        }

        .members-table {
            background: white;
            padding: 30px;
            border-radius: 0 0 15px 15px;
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

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 600;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-partial {
            background: #fff3cd;
            color: #856404;
        }

        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .amount-display {
            font-size: 20px;
            font-weight: bold;
        }

        .amount-success {
            color: #28a745;
        }

        .amount-warning {
            color: #ffc107;
        }

        .amount-danger {
            color: #dc3545;
        }

        .no-members {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-members i {
            font-size: 80px;
            margin-bottom: 20px;
            color: #dee2e6;
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

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .chart-container h3 {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-wrapper {
            position: relative;
            height: 350px;
        }

        @media (max-width: 768px) {
            .charts-row {
                grid-template-columns: 1fr;
            }

            .chart-wrapper {
                height: 300px;
            }
        }

        .month-bar {
            background: #f8f9fa;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 5px solid #007bff;
        }

        .month-bar.active {
            background: #e7f3ff;
            border-left-color: #007bff;
        }

        .month-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        .month-stats {
            font-size: 16px;
            color: #6c757d;
            margin-top: 5px;
        }

        .month-amount {
            font-size: 22px;
            font-weight: bold;
            color: #28a745;
            float: right;
        }

        .print-btn {
            background: #6c757d;
            color: white;
            border: none;
            float: right;
        }

        .print-btn:hover {
            background: #545b62;
        }

        @media print {
            .back-link, .filter-section, .print-btn {
                display: none;
            }

            .main-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="csf-funds.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to CSF Funds
        </a>

        <div class="header-section">
            <h1>
                <i class="fas fa-chart-bar"></i> CSF Reports
                <a href="csf-export-excel.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>"
                   class="btn btn-custom"
                   style="background: #28a745; color: white; border: none; float: right; margin-left: 10px;"
                   download>
                    <i class="fas fa-file-excel"></i> Export to Excel
                </a>
                <button type="button" class="btn btn-custom print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </h1>
            <p class="mb-0">View contribution status and analytics</p>
        </div>

        <div class="filter-section">
            <form method="GET" action="">
                <div style="margin-bottom: 20px;">
                    <h5 style="color: #2c3e50; font-size: 20px; margin-bottom: 15px;">
                        <i class="fas fa-calendar-alt"></i> Select Date Range
                    </h5>
                </div>
                <div class="row g-3 align-items-end">
                    <!-- Start Date -->
                    <div class="col-md-2">
                        <label class="form-label" style="font-weight: 600;">Start Month</label>
                        <select class="form-select" name="start_month" style="font-size: 18px;">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo sprintf('%02d', $m); ?>"
                                        <?php echo $start_month == sprintf('%02d', $m) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" style="font-weight: 600;">Start Year</label>
                        <select class="form-select" name="start_year" style="font-size: 18px;">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $start_year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-1" style="text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #007bff; padding-top: 8px;">to</div>
                    </div>

                    <!-- End Date -->
                    <div class="col-md-2">
                        <label class="form-label" style="font-weight: 600;">End Month</label>
                        <select class="form-select" name="end_month" style="font-size: 18px;">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo sprintf('%02d', $m); ?>"
                                        <?php echo $end_month == sprintf('%02d', $m) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" style="font-weight: 600;">End Year</label>
                        <select class="form-select" name="end_year" style="font-size: 18px;">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $end_year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-custom btn-filter" style="font-size: 20px; padding: 12px 30px;">
                            <i class="fas fa-search"></i> View Report
                        </button>
                    </div>
                </div>

                <div style="margin-top: 15px; font-size: 16px; color: #666;">
                    <i class="fas fa-info-circle"></i> Showing data from <strong><?php echo date('F Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('F Y', strtotime($end_date)); ?></strong>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Members</div>
                <div class="stat-value"><?php echo $total_members; ?></div>
            </div>

            <div class="stat-card success">
                <div class="stat-label">Paid Members</div>
                <div class="stat-value"><?php echo $paid_count; ?></div>
            </div>

            <div class="stat-card danger">
                <div class="stat-label">Unpaid Members</div>
                <div class="stat-value"><?php echo $unpaid_count; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Collection Rate</div>
                <div class="stat-value"><?php echo number_format($collection_rate, 1); ?>%</div>
            </div>

            <div class="stat-card success">
                <div class="stat-label">Total Collected</div>
                <div class="stat-value">₹<?php echo number_format($total_collected, 0); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Average Payment</div>
                <div class="stat-value">₹<?php echo number_format($average_payment, 0); ?></div>
            </div>
        </div>

        <!-- Month-Wise Breakdown Section -->
        <div class="table-container" style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">
                <i class="fas fa-calendar-check"></i> Month-Wise Breakdown
            </h3>
            <div style="overflow-x: auto;">
                <table class="table table-striped table-hover">
                    <thead style="background: #007bff; color: white;">
                        <tr>
                            <th>Month</th>
                            <th>Paid Members</th>
                            <th>Unpaid Members</th>
                            <th>Total Amount Collected</th>
                            <th>Collection Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($months_in_range as $month):
                            $month_data = $month_wise_data[$month];
                            $month_paid = count($month_data['paid_users']);
                            $month_unpaid = $total_members - $month_paid;
                            $month_rate = $total_members > 0 ? ($month_paid / $total_members) * 100 : 0;
                            $month_label = date('F Y', strtotime($month . '-01'));
                        ?>
                            <tr>
                                <td><strong><?php echo $month_label; ?></strong></td>
                                <td>
                                    <span class="badge bg-success" style="font-size: 16px; padding: 8px 15px;">
                                        <?php echo $month_paid; ?> members
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-danger" style="font-size: 16px; padding: 8px 15px;">
                                        <?php echo $month_unpaid; ?> members
                                    </span>
                                </td>
                                <td>
                                    <span class="amount-display amount-success">
                                        ₹<?php echo number_format($month_data['total_amount'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 30px; font-size: 16px;">
                                        <div class="progress-bar <?php echo $month_rate >= 80 ? 'bg-success' : ($month_rate >= 50 ? 'bg-warning' : 'bg-danger'); ?>"
                                             role="progressbar"
                                             style="width: <?php echo $month_rate; ?>%;"
                                             aria-valuenow="<?php echo $month_rate; ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            <?php echo number_format($month_rate, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot style="background: #f8f9fa; font-weight: bold;">
                        <tr>
                            <td>TOTAL</td>
                            <td colspan="2"><?php echo count($months_in_range); ?> months</td>
                            <td>
                                <span class="amount-display amount-success">
                                    ₹<?php echo number_format($total_collected, 2); ?>
                                </span>
                            </td>
                            <td>
                                Avg: <?php echo number_format($collection_rate, 1); ?>%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Interactive Charts Section -->
        <div class="charts-row">
            <!-- Pie Chart: Payment Status Distribution -->
            <div class="chart-container">
                <h3><i class="fas fa-chart-pie"></i> Payment Status Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="statusPieChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart: Collection Rate -->
            <div class="chart-container">
                <h3><i class="fas fa-percentage"></i> Collection Rate</h3>
                <div class="chart-wrapper">
                    <canvas id="collectionDoughnutChart"></canvas>
                </div>
            </div>
        </div>

        <div class="charts-row">
            <!-- Bar Chart: Monthly Trend -->
            <div class="chart-container" style="grid-column: 1 / -1;">
                <h3><i class="fas fa-chart-bar"></i> Monthly Collection Trend - <?php echo $selected_year; ?></h3>
                <div class="chart-wrapper" style="height: 400px;">
                    <canvas id="monthlyBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Line Chart: Amount vs Members -->
        <div class="chart-container">
            <h3><i class="fas fa-chart-line"></i> Collection Amount vs Number of Members</h3>
            <div class="chart-wrapper">
                <canvas id="amountMembersLineChart"></canvas>
            </div>
        </div>

        <!-- Original Monthly Bars (Keep for comparison) -->
        <div class="chart-container">
            <h3><i class="fas fa-calendar-alt"></i> Detailed Monthly Breakdown - <?php echo $selected_year; ?></h3>
            <?php
            $month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            for ($m = 1; $m <= 12; $m++):
                $is_current = ($m == $selected_month);
                $month_data = $monthly_stats[$m];
            ?>
                <div class="month-bar <?php echo $is_current ? 'active' : ''; ?>">
                    <span class="month-amount">₹<?php echo number_format($month_data['total'], 0); ?></span>
                    <div class="month-name"><?php echo $month_names[$m - 1]; ?> <?php echo $selected_year; ?></div>
                    <div class="month-stats">
                        <?php echo $month_data['payers']; ?> member(s) paid
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Paid Members -->
        <?php if (!empty($paid_members)): ?>
            <div class="section-header success">
                <h2><i class="fas fa-check-circle"></i> Paid Members (<?php echo count($paid_members); ?>)</h2>
            </div>
            <div class="members-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Member Name</th>
                                <th>Contact</th>
                                <th>Month-Wise Payment Details</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paid_members as $member):
                                $payment_info = $member['payment_info'];
                                $months_paid = isset($payment_info['months_paid']) ? $payment_info['months_paid'] : [];
                                $month_details = isset($payment_info['month_details']) ? $payment_info['month_details'] : [];

                                // Sort months chronologically
                                sort($months_paid);
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($member['full_name']); ?></strong></td>
                                    <td>
                                        <?php if ($member['phone']): ?>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($member['phone']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($member['email']): ?>
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($member['email']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <?php foreach ($months_paid as $month):
                                                $date = new DateTime($month . '-01');
                                                $month_label = $date->format('F Y');
                                                $details = $month_details[$month] ?? null;
                                                if ($details):
                                            ?>
                                                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #28a745;">
                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                                        <strong style="color: #28a745; font-size: 16px;"><?php echo $month_label; ?></strong>
                                                        <span class="badge bg-success" style="font-size: 14px;">
                                                            ₹<?php echo number_format($details['amount'], 2); ?>
                                                        </span>
                                                    </div>
                                                    <div style="font-size: 14px; color: #666;">
                                                        <i class="fas fa-credit-card"></i> <?php echo ucfirst(str_replace('_', ' ', $details['method'])); ?>
                                                        &nbsp;&nbsp;
                                                        <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($details['date'])); ?>
                                                    </div>
                                                </div>
                                            <?php
                                                endif;
                                            endforeach; ?>
                                        </div>
                                        <small style="color: #666; font-size: 14px; margin-top: 8px; display: block;">
                                            <strong><?php echo count($months_paid); ?> month(s) paid</strong>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="amount-display amount-success">
                                            ₹<?php echo number_format(isset($payment_info['total_amount']) ? $payment_info['total_amount'] : 0, 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo count($months_paid); ?> payment(s)
                                        </span>
                                    </td>
                                    <td><span class="status-badge status-paid">Paid</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Partial Payment section removed - CSF only supports PAID or UNPAID -->

        <!-- Unpaid Members -->
        <?php if (!empty($unpaid_members)): ?>
            <div class="section-header danger">
                <h2><i class="fas fa-times-circle"></i> Unpaid Members (<?php echo count($unpaid_members); ?>)</h2>
            </div>
            <div class="members-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Member Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unpaid_members as $member): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($member['full_name']); ?></strong></td>
                                    <td>
                                        <?php if ($member['phone']): ?>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($member['phone']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($member['email']): ?>
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($member['email']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="status-badge status-unpaid">Unpaid</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($paid_members) && empty($unpaid_members)): ?>
            <div class="members-table">
                <div class="no-members">
                    <i class="fas fa-users"></i>
                    <h3>No Members Found</h3>
                    <p>There are no members in this group</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart.js Configuration
        Chart.defaults.font.size = 16;
        Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';

        // 1. Payment Status Pie Chart (PAID vs UNPAID only)
        const statusPieCtx = document.getElementById('statusPieChart').getContext('2d');
        new Chart(statusPieCtx, {
            type: 'pie',
            data: {
                labels: ['Paid', 'Unpaid'],
                datasets: [{
                    data: [<?php echo $paid_count; ?>, <?php echo $unpaid_count; ?>],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',   // Green for Paid
                        'rgba(220, 53, 69, 0.8)'    // Red for Unpaid
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 16
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = <?php echo $total_members; ?>;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // 2. Member Status Doughnut Chart (Paid vs Unpaid Members)
        const collectionDoughnutCtx = document.getElementById('collectionDoughnutChart').getContext('2d');
        new Chart(collectionDoughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Paid Members', 'Unpaid Members'],
                datasets: [{
                    data: [<?php echo $paid_count; ?>, <?php echo $unpaid_count; ?>],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 16
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = <?php echo $total_members; ?>;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // 3. Monthly Bar Chart
        const monthlyBarCtx = document.getElementById('monthlyBarChart').getContext('2d');
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthlyAmounts = [
            <?php
            for ($m = 1; $m <= 12; $m++) {
                echo $monthly_stats[$m]['total'];
                if ($m < 12) echo ', ';
            }
            ?>
        ];
        const monthlyPayers = [
            <?php
            for ($m = 1; $m <= 12; $m++) {
                echo $monthly_stats[$m]['payers'];
                if ($m < 12) echo ', ';
            }
            ?>
        ];

        new Chart(monthlyBarCtx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Amount Collected (₹)',
                    data: monthlyAmounts,
                    backgroundColor: 'rgba(0, 123, 255, 0.8)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 2,
                    yAxisID: 'y'
                }, {
                    label: 'Number of Payers',
                    data: monthlyPayers,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Amount (₹)',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Number of Payers',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            font: {
                                size: 16
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.datasetIndex === 0) {
                                    label += '₹' + context.parsed.y.toLocaleString('en-IN');
                                } else {
                                    label += context.parsed.y + ' members';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // 4. Line Chart: Amount vs Members
        const amountMembersLineCtx = document.getElementById('amountMembersLineChart').getContext('2d');
        new Chart(amountMembersLineCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Total Amount (₹)',
                    data: monthlyAmounts,
                    borderColor: 'rgba(0, 123, 255, 1)',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount Collected (₹)',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Amount: ₹' + context.parsed.y.toLocaleString('en-IN');
                            },
                            afterLabel: function(context) {
                                const memberCount = monthlyPayers[context.dataIndex];
                                return 'Members: ' + memberCount;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
