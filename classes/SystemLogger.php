<?php
/**
 * System Logger Class
 * Handles error logging, system health monitoring, and alerts
 */

class SystemLogger {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Log system error/warning/info
     *
     * @param string $type error|warning|info|security
     * @param string $severity critical|high|medium|low
     * @param string $message Error message
     * @param array $details Additional details (file, line, user, etc.)
     */
    public function log($type, $severity, $message, $details = []) {
        try {
            $query = "INSERT INTO system_logs
                     (log_type, severity, message, details, file_path, line_number, user_id, ip_address, user_agent, url)
                     VALUES
                     (:log_type, :severity, :message, :details, :file_path, :line_number, :user_id, :ip_address, :user_agent, :url)";

            $stmt = $this->db->prepare($query);

            $detailsJson = !empty($details) ? json_encode($details) : null;
            $filePath = $details['file'] ?? null;
            $lineNumber = $details['line'] ?? null;
            $userId = $details['user_id'] ?? (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $url = $_SERVER['REQUEST_URI'] ?? null;

            $stmt->bindParam(':log_type', $type);
            $stmt->bindParam(':severity', $severity);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':details', $detailsJson);
            $stmt->bindParam(':file_path', $filePath);
            $stmt->bindParam(':line_number', $lineNumber);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':user_agent', $userAgent);
            $stmt->bindParam(':url', $url);

            $stmt->execute();

            // If critical error, create alert
            if ($severity === 'critical') {
                $this->createAlert($type, $message, $severity);
            }

            return true;
        } catch (Exception $e) {
            // Fallback to file logging if database fails
            error_log("SystemLogger Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Quick log methods
     */
    public function logError($message, $details = []) {
        return $this->log('error', 'high', $message, $details);
    }

    public function logCritical($message, $details = []) {
        return $this->log('error', 'critical', $message, $details);
    }

    public function logWarning($message, $details = []) {
        return $this->log('warning', 'medium', $message, $details);
    }

    public function logInfo($message, $details = []) {
        return $this->log('info', 'low', $message, $details);
    }

    public function logSecurity($message, $details = []) {
        return $this->log('security', 'high', $message, $details);
    }

    /**
     * Create alert notification
     */
    public function createAlert($type, $message, $severity = 'medium') {
        try {
            // Map log types to alert types
            $alertTypeMap = [
                'error' => 'critical_error',
                'security' => 'security_threat',
                'warning' => 'performance'
            ];

            $alertType = $alertTypeMap[$type] ?? 'critical_error';
            $alertTitle = ucfirst($type) . ': ' . substr($message, 0, 100);

            $query = "INSERT INTO alert_notifications (alert_type, alert_title, alert_message, severity)
                     VALUES (:alert_type, :alert_title, :alert_message, :severity)";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':alert_type', $alertType);
            $stmt->bindParam(':alert_title', $alertTitle);
            $stmt->bindParam(':alert_message', $message);
            $stmt->bindParam(':severity', $severity);

            $stmt->execute();

            // Send email for critical alerts
            if ($severity === 'critical') {
                $this->sendCriticalAlertEmail($alertTitle, $message);
            }

            return true;
        } catch (Exception $e) {
            error_log("Alert Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log failed login attempt
     */
    public function logFailedLogin($email) {
        try {
            $query = "INSERT INTO failed_login_attempts (email, ip_address, user_agent)
                     VALUES (:email, :ip_address, :user_agent)";

            $stmt = $this->db->prepare($query);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':user_agent', $userAgent);

            $stmt->execute();

            // Check if too many failed attempts (5 in last 15 minutes)
            $this->checkFailedLoginThreshold($email, $ipAddress);

            return true;
        } catch (Exception $e) {
            error_log("Failed Login Logging Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if failed login threshold exceeded
     */
    private function checkFailedLoginThreshold($email, $ipAddress) {
        try {
            $query = "SELECT COUNT(*) as attempt_count
                     FROM failed_login_attempts
                     WHERE (email = :email OR ip_address = :ip_address)
                     AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['attempt_count'] >= 5) {
                $message = "Multiple failed login attempts detected for email: {$email} from IP: {$ipAddress}. Total attempts: {$result['attempt_count']}";
                $this->createAlert('security_threat', $message, 'high');
            }
        } catch (Exception $e) {
            error_log("Failed Login Threshold Check Error: " . $e->getMessage());
        }
    }

    /**
     * Record system health metric
     */
    public function recordHealthMetric($metricName, $metricValue, $metricUnit = null) {
        try {
            // Determine status based on metric
            $status = $this->determineMetricStatus($metricName, $metricValue);

            $query = "INSERT INTO system_health_metrics (metric_name, metric_value, metric_unit, status)
                     VALUES (:metric_name, :metric_value, :metric_unit, :status)";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':metric_name', $metricName);
            $stmt->bindParam(':metric_value', $metricValue);
            $stmt->bindParam(':metric_unit', $metricUnit);
            $stmt->bindParam(':status', $status);

            $stmt->execute();

            // Alert if critical
            if ($status === 'critical') {
                $this->createAlert('performance', "Critical metric: {$metricName} = {$metricValue}{$metricUnit}", 'critical');
            }

            return true;
        } catch (Exception $e) {
            error_log("Health Metric Recording Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Determine metric status
     */
    private function determineMetricStatus($metricName, $value) {
        switch ($metricName) {
            case 'disk_space_used':
                if ($value >= 90) return 'critical';
                if ($value >= 80) return 'warning';
                return 'healthy';

            case 'memory_usage':
                if ($value >= 90) return 'critical';
                if ($value >= 75) return 'warning';
                return 'healthy';

            case 'avg_response_time':
                if ($value >= 5) return 'critical';
                if ($value >= 3) return 'warning';
                return 'healthy';

            default:
                return 'healthy';
        }
    }

    /**
     * Test database connection and log result
     */
    public function testDatabaseConnection() {
        $startTime = microtime(true);

        try {
            $query = "SELECT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            $logQuery = "INSERT INTO database_connection_logs (connection_status, response_time)
                        VALUES ('success', :response_time)";
            $logStmt = $this->db->prepare($logQuery);
            $logStmt->bindParam(':response_time', $responseTime);
            $logStmt->execute();

            return [
                'status' => 'success',
                'response_time' => round($responseTime, 2)
            ];
        } catch (Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $errorMessage = $e->getMessage();

            try {
                $logQuery = "INSERT INTO database_connection_logs (connection_status, response_time, error_message)
                            VALUES ('failed', :response_time, :error_message)";
                $logStmt = $this->db->prepare($logQuery);
                $logStmt->bindParam(':response_time', $responseTime);
                $logStmt->bindParam(':error_message', $errorMessage);
                $logStmt->execute();
            } catch (Exception $logError) {
                error_log("Database Connection Log Error: " . $logError->getMessage());
            }

            // Create critical alert
            $this->createAlert('system_down', 'Database connection failed: ' . $errorMessage, 'critical');

            return [
                'status' => 'failed',
                'error' => $errorMessage,
                'response_time' => round($responseTime, 2)
            ];
        }
    }

    /**
     * Send critical alert email
     */
    private function sendCriticalAlertEmail($title, $message) {
        // Get admin email from config or database
        $adminEmail = ADMIN_EMAIL ?? 'admin@example.com';

        $subject = "[CRITICAL ALERT] " . $title;
        $body = "Critical Alert Notification\n\n";
        $body .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $body .= "Alert: " . $title . "\n\n";
        $body .= "Details:\n" . $message . "\n\n";
        $body .= "Please check the admin dashboard immediately.\n";
        $body .= "URL: " . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] . '/public/admin/system-health.php' : 'N/A');

        $headers = "From: " . APP_NAME . " System Monitor <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n";
        $headers .= "X-Priority: 1\r\n"; // Highest priority

        @mail($adminEmail, $subject, $body, $headers);
    }

    /**
     * Get recent alerts (for dashboard)
     */
    public function getRecentAlerts($limit = 10, $onlyUnread = false) {
        try {
            $query = "SELECT * FROM alert_notifications ";
            if ($onlyUnread) {
                $query .= "WHERE is_read = 0 ";
            }
            $query .= "ORDER BY created_at DESC LIMIT :limit";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get Recent Alerts Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread alert count
     */
    public function getUnreadAlertCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM alert_notifications WHERE is_read = 0 AND is_resolved = 0";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Mark alert as read
     */
    public function markAlertAsRead($alertId) {
        try {
            $query = "UPDATE alert_notifications SET is_read = 1 WHERE alert_id = :alert_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':alert_id', $alertId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clean old logs (keep last 30 days only)
     */
    public function cleanOldLogs() {
        try {
            // Delete logs older than 30 days
            $query = "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $this->db->exec($query);

            // Delete failed login attempts older than 30 days
            $query = "DELETE FROM failed_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $this->db->exec($query);

            // Delete database connection logs older than 7 days
            $query = "DELETE FROM database_connection_logs WHERE checked_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $this->db->exec($query);

            return true;
        } catch (Exception $e) {
            error_log("Clean Old Logs Error: " . $e->getMessage());
            return false;
        }
    }
}
