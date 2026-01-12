<?php
/**
 * Community Management - Toggle Status
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

require_once __DIR__ . '/../../src/models/Community.php';

$communityId = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';

if (!$communityId || !in_array($action, ['activate', 'deactivate'])) {
    header('Location: /public/admin/communities.php?error=Invalid request');
    exit;
}

$communityModel = new Community();
$community = $communityModel->getById($communityId);

if (!$community) {
    header('Location: /public/admin/communities.php?error=Community not found');
    exit;
}

// Toggle status
if ($communityModel->toggleStatus($communityId)) {
    header('Location: /public/admin/communities.php?success=status_changed');
} else {
    header('Location: /public/admin/communities.php?error=Failed to update status');
}
exit;
?>
