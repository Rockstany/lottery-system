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

// Get selected month and year
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

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

// Get payments for selected month
$stmt = $db->prepare("SELECT
                           cp.user_id,
                           SUM(cp.amount) as total_paid,
                           COUNT(*) as payment_count,
                           MAX(cp.payment_date) as last_payment_date,
                           MAX(cp.payment_method) as last_payment_method
                       FROM csf_payments cp
                       WHERE cp.community_id = ?
                       AND MONTH(cp.payment_date) = ?
                       AND YEAR(cp.payment_date) = ?
                       GROUP BY cp.user_id");
$stmt->execute([$communityId, $selected_month, $selected_year]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create payment lookup array
$payment_lookup = [];
foreach ($payments as $payment) {
    $payment_lookup[$payment['user_id']] = $payment;
}

// Classify members
$paid_members = [];
$unpaid_members = [];
$partial_members = [];

foreach ($all_members as $member) {
    $user_id = $member['user_id'];

    if (isset($payment_lookup[$user_id])) {
        $paid_amount = $payment_lookup[$user_id]['total_paid'];

        if ($paid_amount >= $monthly_contribution) {
            $member['payment_info'] = $payment_lookup[$user_id];
            $paid_members[] = $member;
        } else {
            $member['payment_info'] = $payment_lookup[$user_id];
            $member['balance_due'] = $monthly_contribution - $paid_amount;
            $partial_members[] = $member;
        }
    } else {
        $member['balance_due'] = $monthly_contribution;
        $unpaid_members[] = $member;
    }
}

// Calculate statistics
$total_members = count($all_members);
$paid_count = count($paid_members);
$unpaid_count = count($unpaid_members);
$partial_count = count($partial_members);
$collection_rate = $total_members > 0 ? ($paid_count / $total_members) * 100 : 0;

$total_expected = $total_members * $monthly_contribution;
$total_collected = array_sum(array_column($payments, 'total_paid'));
$total_pending = $total_expected - $total_collected;

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
                <button type="button" class="btn btn-custom print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </h1>
            <p class="mb-0">View contribution status and analytics</p>
        </div>

        <div class="filter-section">
            <form method="GET" action="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select class="form-select" name="month">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo sprintf('%02d', $m); ?>"
                                        <?php echo $selected_month == sprintf('%02d', $m) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select class="form-select" name="year">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-custom btn-filter">
                            <i class="fas fa-search"></i> View Report
                        </button>
                    </div>
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

            <div class="stat-card warning">
                <div class="stat-label">Partial Payment</div>
                <div class="stat-value"><?php echo $partial_count; ?></div>
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
                <div class="stat-label">Expected Amount</div>
                <div class="stat-value">₹<?php echo number_format($total_expected, 0); ?></div>
            </div>

            <div class="stat-card danger">
                <div class="stat-label">Pending Amount</div>
                <div class="stat-value">₹<?php echo number_format($total_pending, 0); ?></div>
            </div>
        </div>

        <div class="chart-container">
            <h3><i class="fas fa-calendar-alt"></i> Monthly Collection Trend - <?php echo $selected_year; ?></h3>
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
                                <th>Amount Paid</th>
                                <th>Payment Date</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paid_members as $member): ?>
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
                                        <span class="amount-display amount-success">
                                            ₹<?php echo number_format($member['payment_info']['total_paid'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $date = new DateTime($member['payment_info']['last_payment_date']);
                                        echo $date->format('d M Y');
                                        ?>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $member['payment_info']['last_payment_method'])); ?></td>
                                    <td><span class="status-badge status-paid">Paid</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Partial Payment Members -->
        <?php if (!empty($partial_members)): ?>
            <div class="section-header warning">
                <h2><i class="fas fa-exclamation-circle"></i> Partial Payment (<?php echo count($partial_members); ?>)</h2>
            </div>
            <div class="members-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Member Name</th>
                                <th>Contact</th>
                                <th>Amount Paid</th>
                                <th>Balance Due</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partial_members as $member): ?>
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
                                        <span class="amount-display amount-warning">
                                            ₹<?php echo number_format($member['payment_info']['total_paid'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="amount-display amount-danger">
                                            ₹<?php echo number_format($member['balance_due'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $date = new DateTime($member['payment_info']['last_payment_date']);
                                        echo $date->format('d M Y');
                                        ?>
                                    </td>
                                    <td><span class="status-badge status-partial">Partial</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

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
                                <th>Amount Due</th>
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
                                    <td>
                                        <span class="amount-display amount-danger">
                                            ₹<?php echo number_format($member['balance_due'], 2); ?>
                                        </span>
                                    </td>
                                    <td><span class="status-badge status-unpaid">Unpaid</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($paid_members) && empty($partial_members) && empty($unpaid_members)): ?>
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
</body>
</html>
