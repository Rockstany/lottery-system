<?php
/**
 * Get Field Value API
 * Returns custom field value for sub-community (used for auto-populate)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/feature-access.php';

AuthMiddleware::requireRole('group_admin');

header('Content-Type: application/json');

$communityId = AuthMiddleware::getCommunityId();
$subCommunityId = intval($_GET['sub_community_id'] ?? 0);
$fieldId = intval($_GET['field_id'] ?? 0);

if ($subCommunityId <= 0 || $fieldId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verify sub-community belongs to this community
    $verifyQuery = "SELECT 1 FROM sub_communities
                   WHERE sub_community_id = :sub_community_id AND community_id = :community_id";
    $verifyStmt = $db->prepare($verifyQuery);
    $verifyStmt->bindParam(':sub_community_id', $subCommunityId);
    $verifyStmt->bindParam(':community_id', $communityId);
    $verifyStmt->execute();

    if (!$verifyStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }

    // Get field value
    $query = "SELECT field_value FROM sub_community_custom_data
             WHERE sub_community_id = :sub_community_id AND field_id = :field_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':sub_community_id', $subCommunityId);
    $stmt->bindParam(':field_id', $fieldId);
    $stmt->execute();

    $value = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'value' => $value ?: ''
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
