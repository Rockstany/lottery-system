<?php
/**
 * CSF Funds - Main Dashboard
 * Entry point for CSF (Community Social Funds) management feature
 * Shows quick stats and navigation to all CSF features
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/feature-access.php';

// Authentication & Authorization
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

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get current month stats
$currentMonth = date('Y-m');
$statsQuery = "SELECT
    COUNT(DISTINCT p.user_id) as paid_count,
    SUM(p.amount) as total_amount,
    (SELECT COUNT(*) FROM sub_community_members scm
     JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
     WHERE sc.community_id = ? AND scm.status = 'active') as total_members
    FROM csf_payments p
    WHERE p.community_id = ?
    AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute([$communityId, $communityId, $currentMonth]);
$stats = $statsStmt->fetch();

// Breadcrumb navigation
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'CSF Funds', 'url' => null]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSF Funds - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .action-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .action-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .action-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/breadcrumb.php'; ?>

    <div class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        <div class="header" style="margin-bottom: 30px;">
            <h1 style="margin: 0;">💰 CSF Funds Management</h1>
            <p style="color: #666; margin: 5px 0 0 0;">Track community social fund contributions</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">This Month Collected</div>
                <div class="stat-value">₹<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></div>
                <div class="stat-label"><?php echo $stats['paid_count']; ?>/<?php echo $stats['total_members']; ?> members</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="stat-label">Unpaid Members</div>
                <div class="stat-value"><?php echo ($stats['total_members'] - $stats['paid_count']); ?></div>
                <div class="stat-label">Current month</div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="action-grid">
            <div class="action-card">
                <div class="action-icon">👥</div>
                <h3>Manage Members</h3>
                <p style="color: #666;">Add members single or bulk import</p>
                <a href="/public/group-admin/csf/csf-manage-members.php" class="action-btn">Manage Members</a>
            </div>

            <div class="action-card">
                <div class="action-icon">💵</div>
                <h3>Record Payment</h3>
                <p style="color: #666;">Collect and record CSF contributions from members</p>
                <a href="/public/group-admin/csf/csf-record-payment.php" class="action-btn">Record Payment</a>
            </div>

            <div class="action-card">
                <div class="action-icon">📊</div>
                <h3>View Reports</h3>
                <p style="color: #666;">Detailed payment history and analytics</p>
                <a href="/public/group-admin/csf/csf-reports.php" class="action-btn">View Reports</a>
            </div>

            <div class="action-card">
                <div class="action-icon">📱</div>
                <h3>Send Reminders</h3>
                <p style="color: #666;">WhatsApp reminders to unpaid members</p>
                <a href="/public/group-admin/csf/csf-send-reminders.php" class="action-btn">Send Reminders</a>
            </div>

            <div class="action-card">
                <div class="action-icon">📋</div>
                <h3>Payment History</h3>
                <p style="color: #666;">View all recorded payments</p>
                <a href="/public/group-admin/csf/csf-payment-history.php" class="action-btn">View History</a>
            </div>

            <div class="action-card">
                <div class="action-icon">📥</div>
                <h3>Bulk Import Payments</h3>
                <p style="color: #666;">Import past/historical payments in bulk</p>
                <a href="/public/group-admin/csf/csf-bulk-import-payments.php" class="action-btn">Import Payments</a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
