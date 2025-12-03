<?php
/**
 * System Health Dashboard
 * Admin-only page for monitoring system health, errors, and alerts
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/SystemLogger.php';

AuthMiddleware::requireRole('admin');

$logger = new SystemLogger();
$database = new Database();
$db = $database->getConnection();

// Test database connection
$dbConnectionTest = $logger->testDatabaseConnection();

// Get disk space usage
$diskTotal = disk_total_space("/");
$diskFree = disk_free_space("/");
$diskUsed = $diskTotal - $diskFree;
$diskUsagePercent = round(($diskUsed / $diskTotal) * 100, 2);

// Record disk space metric
$logger->recordHealthMetric('disk_space_used', $diskUsagePercent, 'percentage');

// Get memory usage (if available)
$memoryUsage = 0;
if (function_exists('memory_get_usage')) {
    $memoryLimit = ini_get('memory_limit');
    $memoryLimitBytes = convertToBytes($memoryLimit);
    $memoryUsed = memory_get_usage(true);
    $memoryUsage = round(($memoryUsed / $memoryLimitBytes) * 100, 2);
}

// Get recent alerts
$recentAlerts = $logger->getRecentAlerts(10);
$unreadAlertCount = $logger->getUnreadAlertCount();

// Get recent errors (last 24 hours)
$errorQuery = "SELECT COUNT(*) as count FROM system_logs
              WHERE log_type = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$errorStmt = $db->prepare($errorQuery);
$errorStmt->execute();
$errorCount = $errorStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get critical errors (last 24 hours)
$criticalQuery = "SELECT COUNT(*) as count FROM system_logs
                 WHERE severity = 'critical' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$criticalStmt = $db->prepare($criticalQuery);
$criticalStmt->execute();
$criticalCount = $criticalStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get failed login attempts (last 24 hours)
$loginQuery = "SELECT COUNT(*) as count FROM failed_login_attempts
              WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$loginStmt = $db->prepare($loginQuery);
$loginStmt->execute();
$failedLoginCount = $loginStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent system logs
$logsQuery = "SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 20";
$logsStmt = $db->prepare($logsQuery);
$logsStmt->execute();
$recentLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle mark as read
if (isset($_GET['mark_read']) && isset($_GET['alert_id'])) {
    $alertId = Validator::sanitizeInt($_GET['alert_id']);
    $logger->markAlertAsRead($alertId);
    header("Location: /public/admin/system-health.php");
    exit;
}

function convertToBytes($value) {
    $unit = strtolower(substr($value, -1));
    $number = (int)$value;
    switch($unit) {
        case 'g': $number *= 1024;
        case 'm': $number *= 1024;
        case 'k': $number *= 1024;
    }
    return $number;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }

        .health-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .health-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--gray-300);
        }

        .health-card.healthy { border-left-color: var(--success-color); }
        .health-card.warning { border-left-color: var(--warning-color); }
        .health-card.critical { border-left-color: var(--danger-color); }

        .health-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .health-card-title {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
            text-transform: uppercase;
            font-weight: 600;
        }

        .health-card-value {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            margin: var(--spacing-sm) 0;
        }

        .health-card-subtitle {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-indicator.online { background: var(--success-color); }
        .status-indicator.offline { background: var(--danger-color); }
        .status-indicator.warning { background: var(--warning-color); }

        .alert-item {
            padding: var(--spacing-md);
            border-left: 4px solid;
            margin-bottom: var(--spacing-sm);
            background: white;
            border-radius: var(--radius-sm);
        }

        .alert-item.critical { border-left-color: var(--danger-color); background: var(--danger-light); }
        .alert-item.high { border-left-color: var(--warning-color); background: var(--warning-light); }
        .alert-item.medium { border-left-color: var(--info-color); background: var(--info-light); }
        .alert-item.low { border-left-color: var(--gray-400); background: var(--gray-50); }

        .alert-item.unread {
            font-weight: 600;
        }

        .log-table {
            width: 100%;
            font-size: var(--font-size-sm);
        }

        .log-row.error { background: #fee; }
        .log-row.warning { background: #ffc; }
        .log-row.security { background: #fef; }

        .badge-critical { background: var(--danger-color); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .badge-high { background: var(--warning-color); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .badge-medium { background: var(--info-color); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .badge-low { background: var(--gray-400); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
    </style>
    <script>
        // Auto-refresh page every 60 seconds
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</head>
<body>
    <?php include __DIR__ . '/../group-admin/includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1>🖥️ System Health Dashboard</h1>
            <p style="margin: 0; opacity: 0.9;">Real-time monitoring and alerts</p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Back Button -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/admin/dashboard.php" class="btn btn-secondary">← Back to Admin Dashboard</a>
        </div>

        <!-- Health Status Cards -->
        <div class="health-grid">
            <!-- Website Status -->
            <div class="health-card healthy">
                <div class="health-card-header">
                    <span class="health-card-title">Website Status</span>
                    <span class="status-indicator online"></span>
                </div>
                <div class="health-card-value" style="color: var(--success-color);">ONLINE</div>
                <div class="health-card-subtitle">All systems operational</div>
            </div>

            <!-- Database Connection -->
            <div class="health-card <?php echo $dbConnectionTest['status'] === 'success' ? 'healthy' : 'critical'; ?>">
                <div class="health-card-header">
                    <span class="health-card-title">Database</span>
                    <span class="status-indicator <?php echo $dbConnectionTest['status'] === 'success' ? 'online' : 'offline'; ?>"></span>
                </div>
                <div class="health-card-value" style="color: <?php echo $dbConnectionTest['status'] === 'success' ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">
                    <?php echo $dbConnectionTest['status'] === 'success' ? 'CONNECTED' : 'FAILED'; ?>
                </div>
                <div class="health-card-subtitle">
                    Response: <?php echo $dbConnectionTest['response_time']; ?>ms
                </div>
            </div>

            <!-- Disk Space -->
            <div class="health-card <?php echo $diskUsagePercent >= 90 ? 'critical' : ($diskUsagePercent >= 80 ? 'warning' : 'healthy'); ?>">
                <div class="health-card-header">
                    <span class="health-card-title">Disk Space</span>
                    <span class="status-indicator <?php echo $diskUsagePercent >= 90 ? 'offline' : ($diskUsagePercent >= 80 ? 'warning' : 'online'); ?>"></span>
                </div>
                <div class="health-card-value" style="color: <?php echo $diskUsagePercent >= 90 ? 'var(--danger-color)' : ($diskUsagePercent >= 80 ? 'var(--warning-color)' : 'var(--success-color)'); ?>;">
                    <?php echo $diskUsagePercent; ?>%
                </div>
                <div class="health-card-subtitle">
                    <?php echo round($diskFree / 1024 / 1024 / 1024, 2); ?> GB free
                </div>
            </div>

            <!-- Unread Alerts -->
            <div class="health-card <?php echo $unreadAlertCount > 0 ? 'warning' : 'healthy'; ?>">
                <div class="health-card-header">
                    <span class="health-card-title">Pending Alerts</span>
                </div>
                <div class="health-card-value" style="color: <?php echo $unreadAlertCount > 0 ? 'var(--warning-color)' : 'var(--success-color)'; ?>;">
                    <?php echo $unreadAlertCount; ?>
                </div>
                <div class="health-card-subtitle">Unread notifications</div>
            </div>
        </div>

        <!-- Last 24 Hours Stats -->
        <div class="card" style="margin-bottom: var(--spacing-xl);">
            <div class="card-header">
                <h3 class="card-title">📊 Last 24 Hours</h3>
            </div>
            <div class="card-body">
                <div class="health-grid">
                    <div>
                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">TOTAL ERRORS</div>
                        <div style="font-size: var(--font-size-2xl); font-weight: 700; color: var(--danger-color);">
                            <?php echo $errorCount; ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">CRITICAL ISSUES</div>
                        <div style="font-size: var(--font-size-2xl); font-weight: 700; color: var(--danger-color);">
                            <?php echo $criticalCount; ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">FAILED LOGINS</div>
                        <div style="font-size: var(--font-size-2xl); font-weight: 700; color: var(--warning-color);">
                            <?php echo $failedLoginCount; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Alerts -->
        <div class="card" style="margin-bottom: var(--spacing-xl);">
            <div class="card-header">
                <h3 class="card-title">🔔 Recent Alerts</h3>
            </div>
            <div class="card-body">
                <?php if (count($recentAlerts) === 0): ?>
                    <p style="text-align: center; color: var(--gray-500); padding: var(--spacing-xl);">
                        No alerts. System is running smoothly! ✅
                    </p>
                <?php else: ?>
                    <?php foreach ($recentAlerts as $alert): ?>
                        <div class="alert-item <?php echo $alert['severity']; ?> <?php echo $alert['is_read'] == 0 ? 'unread' : ''; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-xs);">
                                        <span class="badge-<?php echo $alert['severity']; ?>">
                                            <?php echo strtoupper($alert['severity']); ?>
                                        </span>
                                        <span style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                            <?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div style="font-weight: 600; margin-bottom: var(--spacing-xs);">
                                        <?php echo htmlspecialchars($alert['alert_title']); ?>
                                    </div>
                                    <div style="font-size: var(--font-size-sm); color: var(--gray-700);">
                                        <?php echo htmlspecialchars($alert['alert_message']); ?>
                                    </div>
                                </div>
                                <?php if ($alert['is_read'] == 0): ?>
                                    <a href="?mark_read=1&alert_id=<?php echo $alert['alert_id']; ?>" class="btn btn-sm btn-secondary">
                                        Mark Read
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent System Logs -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📝 Recent System Logs</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table log-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Message</th>
                                <th>User</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentLogs) === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: var(--spacing-xl); color: var(--gray-500);">
                                        No logs found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr class="log-row <?php echo $log['log_type']; ?>">
                                        <td style="white-space: nowrap;">
                                            <?php echo date('M d H:i:s', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $log['log_type']; ?>">
                                                <?php echo strtoupper($log['log_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-<?php echo $log['severity']; ?>">
                                                <?php echo strtoupper($log['severity']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($log['message'], 0, 100)); ?></td>
                                        <td><?php echo $log['user_id'] ?? '-'; ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="margin-top: var(--spacing-lg); padding: var(--spacing-md); background: var(--info-light); border-radius: var(--radius-md);">
            <p style="margin: 0; font-size: var(--font-size-sm);">
                ℹ️ <strong>Auto-refresh:</strong> This page automatically refreshes every 60 seconds.
                Last updated: <?php echo date('M d, Y H:i:s'); ?>
            </p>
        </div>
    </div>
</body>
</html>
