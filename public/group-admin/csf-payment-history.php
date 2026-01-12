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

// Get filter parameters
$filter_user = $_GET['user_id'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_year = $_GET['year'] ?? date('Y');
$filter_method = $_GET['payment_method'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query
$sql = "SELECT
            cp.payment_id,
            cp.amount,
            cp.payment_date,
            cp.payment_method,
            cp.reference_number,
            cp.notes,
            cp.created_at as recorded_at,
            u.full_name,
            u.mobile_number as phone,
            recorder.full_name as recorded_by_name
        FROM csf_payments cp
        INNER JOIN users u ON cp.user_id = u.user_id
        LEFT JOIN users recorder ON cp.collected_by = recorder.user_id
        WHERE cp.community_id = ?";

$params = [$communityId];

if ($filter_user) {
    $sql .= " AND cp.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_month) {
    $sql .= " AND MONTH(cp.payment_date) = ?";
    $params[] = $filter_month;
}

if ($filter_year) {
    $sql .= " AND YEAR(cp.payment_date) = ?";
    $params[] = $filter_year;
}

if ($filter_method) {
    $sql .= " AND cp.payment_method = ?";
    $params[] = $filter_method;
}

if ($search_query) {
    $sql .= " AND (u.full_name LIKE ? OR cp.reference_number LIKE ? OR cp.notes LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY cp.payment_date DESC, cp.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all members for filter dropdown
$stmt = $db->prepare("SELECT scm.user_id, u.full_name
                       FROM sub_community_members scm
                       JOIN users u ON scm.user_id = u.user_id
                       JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                       WHERE sc.community_id = ? AND scm.status = 'active'
                       ORDER BY u.full_name");
$stmt->execute([$communityId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$total_amount = array_sum(array_column($payments, 'amount'));
$total_payments = count($payments);

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
                <div class="summary-value"><?php echo $total_payments; ?></div>
            </div>
            <div class="summary-card success">
                <div class="summary-label">Total Amount</div>
                <div class="summary-value">₹<?php echo number_format($total_amount, 2); ?></div>
            </div>
        </div>

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
                                <th>Date</th>
                                <th>Member</th>
                                <th>Amount</th>
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
                                        <?php if ($payment['reference_number']): ?>
                                            <code><?php echo htmlspecialchars($payment['reference_number']); ?></code>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(paymentId, memberName, amount) {
            document.getElementById('delete-payment-id').value = paymentId;
            document.getElementById('delete-member-name').textContent = memberName;
            document.getElementById('delete-amount').textContent = parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>
</html>
