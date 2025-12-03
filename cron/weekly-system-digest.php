<?php
/**
 * Weekly System Health Digest
 * Run this script once per week via cron (every Monday)
 *
 * Cron Command (Runs every Monday at 9 AM):
 * 0 9 * * 1 /usr/bin/php /path/to/cron/weekly-system-digest.php
 */

// Load dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/SystemLogger.php';

// Only run from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

$logger = new SystemLogger();
$database = new Database();
$db = $database->getConnection();

echo "[" . date('Y-m-d H:i:s') . "] Generating weekly system digest...\n";

// Prepare email content
$emailBody = "=== WEEKLY SYSTEM HEALTH DIGEST ===\n";
$emailBody .= "Period: " . date('M d, Y', strtotime('-7 days')) . " to " . date('M d, Y') . "\n\n";

// 1. Get disk space
$diskTotal = disk_total_space("/");
$diskFree = disk_free_space("/");
$diskUsed = $diskTotal - $diskFree;
$diskUsagePercent = round(($diskUsed / $diskTotal) * 100, 2);

$emailBody .= "--- DISK SPACE ---\n";
$emailBody .= "Usage: {$diskUsagePercent}%\n";
$emailBody .= "Free: " . round($diskFree / 1024 / 1024 / 1024, 2) . " GB\n";
$emailBody .= "Status: " . ($diskUsagePercent >= 90 ? "CRITICAL ⚠️" : ($diskUsagePercent >= 80 ? "WARNING ⚠️" : "OK ✓")) . "\n\n";

// 2. Database connection test
$dbTest = $logger->testDatabaseConnection();
$emailBody .= "--- DATABASE ---\n";
$emailBody .= "Status: " . ($dbTest['status'] === 'success' ? "CONNECTED ✓" : "FAILED ❌") . "\n";
$emailBody .= "Response Time: {$dbTest['response_time']}ms\n\n";

// 3. Error statistics (last 7 days)
$errorQuery = "SELECT
    log_type,
    severity,
    COUNT(*) as count
    FROM system_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY log_type, severity
    ORDER BY severity DESC, log_type";
$errorStmt = $db->prepare($errorQuery);
$errorStmt->execute();
$errors = $errorStmt->fetchAll(PDO::FETCH_ASSOC);

$emailBody .= "--- SYSTEM LOGS (Last 7 Days) ---\n";
if (count($errors) > 0) {
    foreach ($errors as $error) {
        $emailBody .= ucfirst($error['log_type']) . " ({$error['severity']}): {$error['count']}\n";
    }
} else {
    $emailBody .= "No errors logged ✓\n";
}
$emailBody .= "\n";

// 4. Failed login attempts (last 7 days)
$loginQuery = "SELECT COUNT(*) as count, COUNT(DISTINCT ip_address) as unique_ips
              FROM failed_login_attempts
              WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$loginStmt = $db->prepare($loginQuery);
$loginStmt->execute();
$loginData = $loginStmt->fetch(PDO::FETCH_ASSOC);

$emailBody .= "--- SECURITY ---\n";
$emailBody .= "Failed Login Attempts: {$loginData['count']}\n";
$emailBody .= "Unique IPs: {$loginData['unique_ips']}\n";
$emailBody .= "Status: " . ($loginData['count'] > 20 ? "REVIEW NEEDED ⚠️" : "OK ✓") . "\n\n";

// 5. Database connection health (last 7 days)
$dbHealthQuery = "SELECT
    connection_status,
    COUNT(*) as count,
    AVG(response_time) as avg_time
    FROM database_connection_logs
    WHERE checked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY connection_status";
$dbHealthStmt = $db->prepare($dbHealthQuery);
$dbHealthStmt->execute();
$dbHealth = $dbHealthStmt->fetchAll(PDO::FETCH_ASSOC);

$emailBody .= "--- DATABASE HEALTH (Last 7 Days) ---\n";
if (count($dbHealth) > 0) {
    foreach ($dbHealth as $health) {
        $avgTime = round($health['avg_time'], 2);
        $emailBody .= ucfirst($health['connection_status']) . ": {$health['count']} checks (Avg: {$avgTime}ms)\n";
    }
} else {
    $emailBody .= "No database checks logged\n";
}
$emailBody .= "\n";

// 6. Unresolved alerts
$alertQuery = "SELECT COUNT(*) as count FROM alert_notifications WHERE is_resolved = 0";
$alertStmt = $db->prepare($alertQuery);
$alertStmt->execute();
$unresolvedAlerts = $alertStmt->fetch(PDO::FETCH_ASSOC)['count'];

$emailBody .= "--- ALERTS ---\n";
$emailBody .= "Unresolved Alerts: {$unresolvedAlerts}\n";
if ($unresolvedAlerts > 0) {
    $emailBody .= "⚠️ Please check the admin dashboard\n";
}
$emailBody .= "\n";

// 7. System recommendations
$emailBody .= "--- RECOMMENDATIONS ---\n";
$recommendations = [];

if ($diskUsagePercent >= 80) {
    $recommendations[] = "Clean up old files or expand disk space";
}
if ($loginData['count'] > 20) {
    $recommendations[] = "Review failed login attempts for security threats";
}
if ($unresolvedAlerts > 0) {
    $recommendations[] = "Review and resolve pending alerts";
}

if (count($recommendations) > 0) {
    foreach ($recommendations as $rec) {
        $emailBody .= "• {$rec}\n";
    }
} else {
    $emailBody .= "✓ All systems running smoothly\n";
}

$emailBody .= "\n";
$emailBody .= "Dashboard: " . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/public/admin/system-health.php' : 'https://zatana.in/public/admin/system-health.php') . "\n";
$emailBody .= "\n--- End of Weekly Digest ---\n";

// Display to console
echo $emailBody;

// Send email to admin
$adminEmail = ADMIN_EMAIL;
$subject = "[Weekly Digest] System Health Report - " . date('M d, Y');
$headers = "From: " . APP_NAME . " Monitor <noreply@zatana.in>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

if (@mail($adminEmail, $subject, $emailBody, $headers)) {
    echo "\n✅ Weekly digest email sent to: {$adminEmail}\n";
} else {
    echo "\n❌ Failed to send email to: {$adminEmail}\n";
}

// Clean old logs (older than 30 days)
echo "Cleaning old logs...\n";
$logger->cleanOldLogs();
echo "✅ Old logs cleaned\n";

echo "[" . date('Y-m-d H:i:s') . "] Weekly digest completed\n";
echo str_repeat("=", 60) . "\n";
