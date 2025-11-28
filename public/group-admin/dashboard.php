<?php
/**
 * Group Admin Dashboard
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';

// Require authentication and Group Admin role
AuthMiddleware::requireRole('group_admin');

$userId = AuthMiddleware::getUserId();
$userName = $_SESSION['full_name'] ?? 'User';
$communityId = AuthMiddleware::getCommunityId();

// Get community details
$database = new Database();
$db = $database->getConnection();

$communityName = 'Not Assigned';
if ($communityId) {
    $query = "SELECT community_name FROM communities WHERE community_id = :community_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':community_id', $communityId);
    $stmt->execute();
    $community = $stmt->fetch();
    if ($community) {
        $communityName = $community['community_name'];
    }
}

// Get statistics (placeholders for now)
$totalLotteryEvents = 0;
$totalTransactionCampaigns = 0;
$totalCollections = 0;
$activeMembers = 0;

if ($communityId) {
    // Lottery Events Count
    $query = "SELECT COUNT(*) as count FROM lottery_events WHERE community_id = :community_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':community_id', $communityId);
    $stmt->execute();
    $result = $stmt->fetch();
    $totalLotteryEvents = $result['count'];

    // Transaction Campaigns Count
    $query = "SELECT COUNT(*) as count FROM transaction_campaigns WHERE community_id = :community_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':community_id', $communityId);
    $stmt->execute();
    $result = $stmt->fetch();
    $totalTransactionCampaigns = $result['count'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .header h1 {
            color: white;
            margin: 0;
        }

        .header-info {
            text-align: right;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            border-left: 4px solid;
        }

        .stat-card.primary { border-color: var(--primary-color); }
        .stat-card.success { border-color: var(--success-color); }
        .stat-card.warning { border-color: var(--warning-color); }
        .stat-card.info { border-color: var(--info-color); }

        .stat-value {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
        }

        .stat-label {
            color: var(--gray-600);
            font-size: var(--font-size-base);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }

        .action-btn {
            background: var(--white);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
            color: var(--gray-900);
        }

        .action-btn:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: var(--font-size-3xl);
            margin-bottom: var(--spacing-sm);
        }

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
            transition: background var(--transition-fast);
        }

        .nav-menu a:hover {
            background: var(--gray-100);
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .header-info {
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .nav-menu ul {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1><?php echo APP_NAME; ?></h1>
                    <p style="margin: 0; opacity: 0.9;">Group Admin Dashboard</p>
                </div>
                <div class="header-info">
                    <p style="margin: 0; font-size: var(--font-size-lg); font-weight: 500;">
                        <?php echo htmlspecialchars($userName); ?>
                    </p>
                    <p style="margin: 0; opacity: 0.9;">
                        <?php echo htmlspecialchars($communityName); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Navigation Menu -->
        <nav class="nav-menu no-print">
            <ul>
                <li><a href="/public/group-admin/dashboard.php" style="font-weight: 600;">Dashboard</a></li>
                <li><a href="/public/group-admin/transactions.php">Transaction Collection</a></li>
                <li><a href="/public/group-admin/lottery.php">Lottery System</a></li>
                <li><a href="/public/group-admin/change-password.php">Change Password</a></li>
                <li><a href="/public/logout.php">Logout</a></li>
            </ul>
        </nav>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value"><?php echo $totalLotteryEvents; ?></div>
                <div class="stat-label">Lottery Events</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo $totalTransactionCampaigns; ?></div>
                <div class="stat-label">Transaction Campaigns</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value">₹<?php echo number_format($totalCollections, 2); ?></div>
                <div class="stat-label">Total Collections</div>
            </div>
            <div class="stat-card info">
                <div class="stat-value"><?php echo $activeMembers; ?></div>
                <div class="stat-label">Active Members</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="/public/group-admin/transaction-create.php" class="action-btn">
                        <div class="action-icon">💰</div>
                        <div>Create Transaction Campaign</div>
                    </a>
                    <a href="/public/group-admin/transactions.php" class="action-btn">
                        <div class="action-icon">📋</div>
                        <div>View Campaigns</div>
                    </a>
                    <a href="/public/group-admin/lottery-create.php" class="action-btn">
                        <div class="action-icon">🎫</div>
                        <div>Create Lottery Event</div>
                    </a>
                    <a href="/public/group-admin/lottery.php" class="action-btn">
                        <div class="action-icon">🎰</div>
                        <div>Manage Lottery</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Welcome to <?php echo APP_NAME; ?>!</strong><br>
                    Your dashboard is ready. Start by creating your first lottery event or transaction campaign using the quick actions above.
                </div>
            </div>
        </div>

        <!-- Feature Overview -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Available Features</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4>🎫 Lottery System</h4>
                        <ul>
                            <li>✅ Event Creation</li>
                            <li>✅ Auto Book Generation</li>
                            <li>✅ Distribution Management</li>
                            <li>✅ Payment Collection</li>
                            <li>✅ Reports & Analytics</li>
                        </ul>
                        <a href="/public/group-admin/lottery.php" class="btn btn-primary">Manage Lottery →</a>
                    </div>
                    <div class="col-6">
                        <h4>💰 Transaction Collection</h4>
                        <ul>
                            <li>✅ CSV Upload</li>
                            <li>✅ WhatsApp Reminders</li>
                            <li>✅ Payment Tracking</li>
                            <li>✅ Collection Reports</li>
                            <li>✅ Member Management</li>
                        </ul>
                        <a href="/public/group-admin/transactions.php" class="btn btn-success">Manage Transactions →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show alert if not assigned to community
        <?php if (!$communityId): ?>
        alert('You are not assigned to any community yet. Please contact your administrator.');
        <?php endif; ?>
    </script>
</body>
</html>
