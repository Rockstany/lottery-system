<?php
/**
 * Application Entry Point
 * GetToKnow Community App
 */

require_once __DIR__ . '/../config/config.php';

// Redirect to login if not authenticated
if (!AuthMiddleware::isAuthenticated()) {
    header("Location: /login.php");
    exit;
}

// Redirect to appropriate dashboard based on role
$role = AuthMiddleware::getUserRole();

if ($role === 'admin') {
    header("Location: /admin/dashboard.php");
} else if ($role === 'group_admin') {
    header("Location: /group-admin/dashboard.php");
} else {
    // Invalid role - logout
    AuthMiddleware::logout();
    header("Location: /login.php");
}

exit;
?>
