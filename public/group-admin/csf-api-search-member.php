<?php
/**
 * CSF API - Search Member
 * AJAX endpoint to search members by name or mobile number
 */

require_once __DIR__ . '/../../config/config.php';

// Authentication
AuthMiddleware::requireRole('group_admin');

header('Content-Type: application/json');

$communityId = AuthMiddleware::getCommunityId();
$subCommunityId = intval($_GET['sub_community_id'] ?? 0);
$searchTerm = trim($_GET['q'] ?? '');

// Validate inputs
if (empty($searchTerm) || strlen($searchTerm) < 2) {
    echo json_encode(['success' => false, 'error' => 'Search term too short']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Build query
$query = "SELECT scm.user_id, u.full_name, u.mobile_number, sc.sub_community_name
          FROM sub_community_members scm
          JOIN users u ON scm.user_id = u.user_id
          JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
          WHERE sc.community_id = :community_id
          AND scm.status = 'active'";

// Filter by sub-community if specified
if ($subCommunityId > 0) {
    $query .= " AND scm.sub_community_id = :sub_community_id";
}

// Search by name or mobile
$query .= " AND (u.full_name LIKE :search OR u.mobile_number LIKE :search)";

$query .= " ORDER BY u.full_name LIMIT 10";

$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);

if ($subCommunityId > 0) {
    $stmt->bindParam(':sub_community_id', $subCommunityId);
}

$searchPattern = "%{$searchTerm}%";
$stmt->bindParam(':search', $searchPattern);

$stmt->execute();
$results = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'members' => $results
]);
