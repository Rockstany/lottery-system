<?php
/**
 * Logout Handler
 * GetToKnow Community App
 */

require_once __DIR__ . '/../config/config.php';

// Log activity before destroying session
if (AuthMiddleware::isAuthenticated()) {
    $userId = AuthMiddleware::getUserId();

    try {
        $database = new Database();
        $db = $database->getConnection();

        $query = "INSERT INTO activity_logs (user_id, action_type, action_description, ip_address)
                  VALUES (:user_id, 'logout', 'User logged out', :ip)";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? null);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Logout Log Error: " . $e->getMessage());
    }
}

// Logout
AuthMiddleware::logout();

// Redirect to login
header("Location: /login.php");
exit;
?>
