<?php
/**
 * Critical System Monitoring (OPTIONAL)
 * Only monitors CRITICAL issues and sends immediate alerts
 *
 * USAGE: This is optional. Only use if you want real-time critical alerts.
 * For most users, the weekly digest is sufficient.
 *
 * Cron Command (Run every hour - OPTIONAL):
 * 0 * * * * /usr/bin/php /path/to/cron/monitor-system-health.php
 */

// Load dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/SystemLogger.php';

// Only run from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

$logger = new SystemLogger();

echo "[" . date('Y-m-d H:i:s') . "] Starting system health monitoring...\n";

// 1. Check Disk Space
echo "Checking disk space...\n";
$diskTotal = disk_total_space("/");
$diskFree = disk_free_space("/");
$diskUsed = $diskTotal - $diskFree;
$diskUsagePercent = round(($diskUsed / $diskTotal) * 100, 2);

$logger->recordHealthMetric('disk_space_used', $diskUsagePercent, 'percentage');

if ($diskUsagePercent >= 90) {
    echo "  ⚠️  CRITICAL: Disk space usage at {$diskUsagePercent}%\n";
    $logger->createAlert(
        'disk_space',
        "Disk space critically low: {$diskUsagePercent}% used. Only " . round($diskFree / 1024 / 1024 / 1024, 2) . " GB remaining.",
        'critical'
    );
} elseif ($diskUsagePercent >= 80) {
    echo "  ⚠️  WARNING: Disk space usage at {$diskUsagePercent}%\n";
    $logger->createAlert(
        'disk_space',
        "Disk space warning: {$diskUsagePercent}% used. Only " . round($diskFree / 1024 / 1024 / 1024, 2) . " GB remaining.",
        'high'
    );
} else {
    echo "  ✅ Disk space OK: {$diskUsagePercent}% used\n";
}

// 2. Test Database Connection
echo "Testing database connection...\n";
$dbTest = $logger->testDatabaseConnection();
if ($dbTest['status'] === 'success') {
    echo "  ✅ Database connection OK ({$dbTest['response_time']}ms)\n";
} else {
    echo "  ❌ Database connection FAILED: {$dbTest['error']}\n";
}

// 3. Check Memory Usage (if available)
if (function_exists('memory_get_usage')) {
    echo "Checking memory usage...\n";
    $memoryLimit = ini_get('memory_limit');
    $memoryLimitBytes = convertToBytes($memoryLimit);
    $memoryUsed = memory_get_usage(true);
    $memoryUsagePercent = round(($memoryUsed / $memoryLimitBytes) * 100, 2);

    $logger->recordHealthMetric('memory_usage', $memoryUsagePercent, 'percentage');

    if ($memoryUsagePercent >= 90) {
        echo "  ⚠️  WARNING: Memory usage at {$memoryUsagePercent}%\n";
        $logger->createAlert(
            'performance',
            "Memory usage critical: {$memoryUsagePercent}% used",
            'high'
        );
    } else {
        echo "  ✅ Memory usage OK: {$memoryUsagePercent}%\n";
    }
}

// 4. Check Recent Errors (last 5 minutes)
echo "Checking recent errors...\n";
$database = new Database();
$db = $database->getConnection();

$errorQuery = "SELECT COUNT(*) as count FROM system_logs
              WHERE severity = 'critical'
              AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
$errorStmt = $db->prepare($errorQuery);
$errorStmt->execute();
$criticalErrors = $errorStmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($criticalErrors > 0) {
    echo "  ⚠️  {$criticalErrors} critical error(s) in last 5 minutes\n";
} else {
    echo "  ✅ No critical errors\n";
}

// 5. Clean Old Logs (run once daily - check if last clean was >24 hours ago)
$lastCleanFile = __DIR__ . '/../storage/last_log_clean.txt';
$shouldClean = false;

if (!file_exists($lastCleanFile)) {
    $shouldClean = true;
} else {
    $lastClean = file_get_contents($lastCleanFile);
    if (time() - $lastClean > 86400) { // 24 hours
        $shouldClean = true;
    }
}

if ($shouldClean) {
    echo "Cleaning old logs...\n";
    $logger->cleanOldLogs();
    file_put_contents($lastCleanFile, time());
    echo "  ✅ Old logs cleaned\n";
}

echo "[" . date('Y-m-d H:i:s') . "] System health monitoring completed\n";
echo str_repeat("-", 60) . "\n";

/**
 * Helper function to convert PHP memory limit to bytes
 */
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
