<?php
/**
 * Admin Dashboard
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';

// Require authentication and Admin role
AuthMiddleware::requireRole('admin');

$userId = AuthMiddleware::getUserId();
$userName = $_SESSION['full_name'] ?? 'Admin';

// Get system statistics
$database = new Database();
$db = $database->getConnection();

// Total Users
$query = "SELECT COUNT(*) as count FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$totalUsers = $stmt->fetch()['count'];

// Total Communities
$query = "SELECT COUNT(*) as count FROM communities";
$stmt = $db->prepare($query);
$stmt->execute();
$totalCommunities = $stmt->fetch()['count'];

// Total Group Admins
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'group_admin'";
$stmt = $db->prepare($query);
$stmt->execute();
$totalGroupAdmins = $stmt->fetch()['count'];

// Total Lottery Events
$query = "SELECT COUNT(*) as count FROM lottery_events";
$stmt = $db->prepare($query);
$stmt->execute();
$totalLotteryEvents = $stmt->fetch()['count'];

// Total Transaction Campaigns
$query = "SELECT COUNT(*) as count FROM transaction_campaigns";
$stmt = $db->prepare($query);
$stmt->execute();
$totalTransactionCampaigns = $stmt->fetch()['count'];

// Recent Activity
$query = "SELECT al.*, u.full_name
          FROM activity_logs al
          LEFT JOIN users u ON al.user_id = u.user_id
          ORDER BY al.created_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recentActivity = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .stat-card.danger { border-color: var(--danger-color); }

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

        .activity-item {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--gray-200);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-time {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
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
                    <p style="margin: 0; opacity: 0.9;">Administrator Dashboard</p>
                </div>
                <div class="header-info">
                    <p style="margin: 0; font-size: var(--font-size-lg); font-weight: 500;">
                        <?php echo htmlspecialchars($userName); ?>
                    </p>
                    <p style="margin: 0; opacity: 0.9;">System Administrator</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Navigation Menu -->
        <nav class="nav-menu no-print">
            <ul>
                <li><a href="/public/admin/dashboard.php" style="font-weight: 600;">Dashboard</a></li>
                <li><a href="/public/admin/users.php">Manage Users</a></li>
                <li><a href="/public/admin/communities.php">Manage Communities</a></li>
                <li><a href="/public/admin/change-password.php">Change Password</a></li>
                <li><a href="/public/logout.php">Logout</a></li>
            </ul>
        </nav>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo $totalCommunities; ?></div>
                <div class="stat-label">Communities</div>
            </div>
            <div class="stat-card info">
                <div class="stat-value"><?php echo $totalGroupAdmins; ?></div>
                <div class="stat-label">Group Admins</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?php echo $totalLotteryEvents; ?></div>
                <div class="stat-label">Lottery Events</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-value"><?php echo $totalTransactionCampaigns; ?></div>
                <div class="stat-label">Transaction Campaigns</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="/public/admin/user-add.php" class="action-btn">
                        <div class="action-icon">👤</div>
                        <div>Create Group Admin</div>
                    </a>
                    <a href="/public/admin/communities.php" class="action-btn">
                        <div class="action-icon">🏘️</div>
                        <div>Manage Communities</div>
                    </a>
                    <a href="/public/admin/users.php" class="action-btn">
                        <div class="action-icon">👥</div>
                        <div>View All Users</div>
                    </a>
                    <a href="/public/admin/dashboard.php" class="action-btn">
                        <div class="action-icon">📊</div>
                        <div>Dashboard Statistics</div>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activity -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activity</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (count($recentActivity) > 0): ?>
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="activity-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></strong>
                                        - <?php echo htmlspecialchars($activity['action_type']); ?>
                                    </div>
                                    <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                        <?php echo htmlspecialchars($activity['action_description']); ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: var(--spacing-lg); text-align: center; color: var(--gray-500);">
                                No recent activity
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">System Information</h3>
                    </div>
                    <div class="card-body">
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);"><strong>Application Name</strong></td>
                                <td style="padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);"><?php echo APP_NAME; ?></td>
                            </tr>
                            <tr>
                                <td style="padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);"><strong>Version</strong></td>
                                <td style="padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);"><?php echo APP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td style="padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);"><strong>PHP Version</strong></td>
                                <td style="padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);"><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);"><strong>Database</strong></td>
                                <td style="padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);">MySQL</td>
                            </tr>
                            <tr>
                                <td style="padding: var(--spacing-sm);"><strong>Server Time</strong></td>
                                <td style="padding: var(--spacing-sm);"><?php echo date('Y-m-d H:i:s'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Coming Soon</h3>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>User Management (Create, Edit, Deactivate)</li>
                            <li>Community Management (CRUD)</li>
                            <li>Assign Group Admins to Communities</li>
                            <li>System Reports & Analytics</li>
                            <li>Activity Log Viewer</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
