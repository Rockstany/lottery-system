<?php
/**
 * Application Configuration
 * GetToKnow Community App
 */

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Application Settings
define('APP_NAME', 'GetToKnow');
define('APP_VERSION', '1.0');
define('APP_URL', 'http://localhost/Church%20Project');  // Update for production

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 6);
define('BCRYPT_COST', 10);

// File Upload Settings
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['csv', 'xlsx', 'jpg', 'jpeg', 'png', 'pdf']);

// Pagination
define('ITEMS_PER_PAGE', 20);

// System Monitoring Settings
define('ADMIN_EMAIL', 'info@careerplanning.fun'); // Change to your actual admin email
define('MONITORING_ENABLED', true); // Set to false to disable monitoring
define('WEEKLY_DIGEST_DAY', 'Monday'); // Day of week for weekly digest email

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token Generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $directories = [
        __DIR__ . '/../src/controllers/',
        __DIR__ . '/../src/models/',
        __DIR__ . '/../src/middleware/',
        __DIR__ . '/../src/utils/'
    ];

    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Include database configuration
require_once __DIR__ . '/database.php';
?>
