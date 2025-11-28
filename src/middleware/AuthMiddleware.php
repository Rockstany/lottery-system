<?php
/**
 * Authentication Middleware
 * Verify user authentication and authorization
 */

class AuthMiddleware {

    /**
     * Check if user is authenticated
     * @return bool
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Require authentication (redirect if not authenticated)
     * @param string $redirect
     */
    public static function requireAuth($redirect = '/login.php') {
        if (!self::isAuthenticated()) {
            header("Location: {$redirect}");
            exit;
        }

        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];

            if ($elapsed > SESSION_TIMEOUT) {
                self::logout();
                header("Location: {$redirect}?timeout=1");
                exit;
            }
        }

        // Update last activity time
        $_SESSION['last_activity'] = time();
    }

    /**
     * Require API authentication (JSON response)
     */
    public static function requireApiAuth() {
        if (!self::isAuthenticated()) {
            Response::unauthorized('Please login to continue');
        }

        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];

            if ($elapsed > SESSION_TIMEOUT) {
                self::logout();
                Response::unauthorized('Session expired');
            }
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Check if user has specific role
     * @param string|array $roles
     * @return bool
     */
    public static function hasRole($roles) {
        if (!self::isAuthenticated()) {
            return false;
        }

        $userRole = $_SESSION['role'] ?? '';
        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($userRole, $roles);
    }

    /**
     * Require specific role (redirect if unauthorized)
     * @param string|array $roles
     * @param string $redirect
     */
    public static function requireRole($roles, $redirect = '/unauthorized.php') {
        self::requireAuth();

        if (!self::hasRole($roles)) {
            header("Location: {$redirect}");
            exit;
        }
    }

    /**
     * Require specific role for API (JSON response)
     * @param string|array $roles
     */
    public static function requireApiRole($roles) {
        self::requireApiAuth();

        if (!self::hasRole($roles)) {
            Response::forbidden('You do not have permission to access this resource');
        }
    }

    /**
     * Get current user ID
     * @return int|null
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user role
     * @return string|null
     */
    public static function getUserRole() {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Get current user's community ID (for group admins)
     * @return int|null
     */
    public static function getCommunityId() {
        return $_SESSION['community_id'] ?? null;
    }

    /**
     * Logout user
     */
    public static function logout() {
        // Clear all session variables
        $_SESSION = [];

        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy the session
        session_destroy();
    }

    /**
     * Verify CSRF token
     * @param string $token
     * @return bool
     */
    public static function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Require valid CSRF token
     */
    public static function requireCsrfToken() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!self::verifyCsrfToken($token)) {
            Response::forbidden('Invalid CSRF token');
        }
    }
}
?>
