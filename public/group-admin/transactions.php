<?php
/**
 * Transaction Collection - Campaigns List
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$communityId = AuthMiddleware::getCommunityId();
$userName = $_SESSION['full_name'] ?? 'User';

if (!$communityId) {
    die('<div class="alert alert-danger">You are not assigned to any community. Please contact administrator.</div>');
}

$database = new Database();
$db = $database->getConnection();

// Get all campaigns for this community
$query = "SELECT * FROM transaction_campaigns WHERE community_id = :community_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$campaigns = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Collection - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .header {
            background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .nav-menu {
            background: var(--white);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        .nav-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }
        .nav-menu a {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
        }
        .nav-menu a:hover {
            background: var(--gray-100);
            text-decoration: none;
        }
        .campaign-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
        }
        .campaign-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        .campaign-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: var(--spacing-md);
        }
        .campaign-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
        }
        .stat-item {
            text-align: center;
            padding: var(--spacing-sm);
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }
        .stat-value {
            font-size: var(--font-size-xl);
            font-weight: 700;
        }
        .stat-label {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }
        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
        }
        .empty-icon {
            font-size: 64px;
            margin-bottom: var(--spacing-md);
        }
        .instructions {
            background: var(--info-light);
            border-left: 4px solid var(--info-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .step-list {
            margin: var(--spacing-md) 0;
            padding-left: var(--spacing-xl);
        }
        .step-list li {
            margin: var(--spacing-sm) 0;
            font-size: var(--font-size-base);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>💰 Transaction Collection</h1>
            <p style="margin: 0; opacity: 0.9;">Manage Payment Collections</p>
        </div>
    </div>

    <div class="container main-content">
        <nav class="nav-menu">
            <ul>
                <li><a href="/public/group-admin/dashboard.php">Dashboard</a></li>
                <li><a href="/public/group-admin/transactions.php" style="font-weight: 600;">Transaction Collection</a></li>
                <li><a href="#lottery">Lottery System</a></li>
                <li><a href="/public/logout.php">Logout</a></li>
            </ul>
        </nav>

        <?php if ($success === 'created'): ?>
            <div class="alert alert-success">Campaign created successfully! Now upload members.</div>
        <?php endif; ?>

        <!-- Instructions Card -->
        <div class="instructions">
            <h3 style="margin-top: 0;">📋 How Transaction Collection Works</h3>
            <p>Follow these simple 4 steps to collect payments from your community members:</p>
            <ol class="step-list">
                <li><strong>Create Campaign</strong> - Give it a name (e.g., "November Maintenance")</li>
                <li><strong>Upload Members</strong> - Add members via CSV file with their payment details</li>
                <li><strong>Send Reminders</strong> - Use WhatsApp to remind members about payment</li>
                <li><strong>Track Payments</strong> - Mark payments as received and view reports</li>
            </ol>
            <p style="margin: 0;"><strong>💡 Tip:</strong> You can create unlimited campaigns for different purposes!</p>
        </div>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title" style="margin: 0;">Your Campaigns (<?php echo count($campaigns); ?>)</h3>
                <a href="/public/group-admin/transaction-create.php" class="btn btn-primary">
                    + Create New Campaign
                </a>
            </div>
        </div>

        <?php if (count($campaigns) === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <h3>No Campaigns Yet</h3>
                <p style="color: var(--gray-600); max-width: 500px; margin: var(--spacing-md) auto;">
                    Get started by creating your first transaction collection campaign.
                    Perfect for collecting maintenance, event fees, or any community payments.
                </p>
                <a href="/public/group-admin/transaction-create.php" class="btn btn-primary btn-lg" style="margin-top: var(--spacing-lg);">
                    Create Your First Campaign
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($campaigns as $campaign): ?>
                <?php
                // Get campaign statistics
                $statsQuery = "SELECT
                    COUNT(*) as total_members,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) as partial_count,
                    SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count,
                    COALESCE(SUM(total_paid), 0) as total_collected
                    FROM campaign_members WHERE campaign_id = :campaign_id";
                $statsStmt = $db->prepare($statsQuery);
                $statsStmt->bindParam(':campaign_id', $campaign['campaign_id']);
                $statsStmt->execute();
                $stats = $statsStmt->fetch();
                ?>

                <div class="campaign-card">
                    <div class="campaign-header">
                        <div>
                            <h3 style="margin: 0;"><?php echo htmlspecialchars($campaign['campaign_name']); ?></h3>
                            <p style="margin: var(--spacing-xs) 0; color: var(--gray-600);">
                                <?php echo htmlspecialchars($campaign['campaign_description'] ?? 'No description'); ?>
                            </p>
                            <p style="margin: 0; font-size: var(--font-size-sm); color: var(--gray-500);">
                                Created: <?php echo date('M d, Y', strtotime($campaign['created_at'])); ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($campaign['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?php echo ucfirst($campaign['status']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="campaign-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['total_members'] ?? 0; ?></div>
                            <div class="stat-label">Total Members</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" style="color: var(--success-color);"><?php echo $stats['paid_count'] ?? 0; ?></div>
                            <div class="stat-label">Paid</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" style="color: var(--warning-color);"><?php echo $stats['partial_count'] ?? 0; ?></div>
                            <div class="stat-label">Partial</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" style="color: var(--danger-color);"><?php echo $stats['unpaid_count'] ?? 0; ?></div>
                            <div class="stat-label">Unpaid</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">₹<?php echo number_format($stats['total_collected'] ?? 0, 2); ?></div>
                            <div class="stat-label">Collected</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-lg); flex-wrap: wrap;">
                        <a href="/public/group-admin/transaction-members.php?id=<?php echo $campaign['campaign_id']; ?>" class="btn btn-primary">
                            View Members
                        </a>
                        <a href="/public/group-admin/transaction-upload.php?id=<?php echo $campaign['campaign_id']; ?>" class="btn btn-success">
                            Upload Members
                        </a>
                        <a href="/public/group-admin/transaction-payments.php?id=<?php echo $campaign['campaign_id']; ?>" class="btn btn-info">
                            Track Payments
                        </a>
                        <a href="/public/group-admin/transaction-reports.php?id=<?php echo $campaign['campaign_id']; ?>" class="btn btn-secondary">
                            Reports
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
