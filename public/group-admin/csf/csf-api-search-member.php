<?php
/**
 * CSF API - Search Member
 * AJAX endpoint to search members with smart filtering
 * Supports: @Area @Name format for area-specific search
 */

require_once __DIR__ . '/../../../config/config.php';

// Authentication
AuthMiddleware::requireRole('group_admin');

header('Content-Type: application/json');

$communityId = AuthMiddleware::getCommunityId();
$searchTerm = trim($_GET['q'] ?? '');

// Validate inputs
if (empty($searchTerm) || strlen($searchTerm) < 2) {
    echo json_encode(['success' => false, 'error' => 'Search term too short']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Parse smart search with @ symbols
$areaFilter = null;
$nameFilter = null;

// Extract @Area and @Name from search
if (preg_match_all('/@(\w+)/i', $searchTerm, $matches)) {
    $tags = $matches[1];

    // Build query for area and name filtering
    $query = "SELECT scm.user_id, u.full_name, u.mobile_number, sc.sub_community_name
              FROM sub_community_members scm
              JOIN users u ON scm.user_id = u.user_id
              JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
              WHERE sc.community_id = ?
              AND scm.status = 'active'";

    $params = [$communityId];

    // Add filters for each tag
    foreach ($tags as $tag) {
        // Check if it matches an area name
        $areaCheckQuery = "SELECT sub_community_id FROM sub_communities
                           WHERE community_id = ? AND sub_community_name LIKE ? AND status = 'active'";
        $areaStmt = $db->prepare($areaCheckQuery);
        $areaStmt->execute([$communityId, "%{$tag}%"]);
        $areaMatch = $areaStmt->fetch(PDO::FETCH_ASSOC);

        if ($areaMatch) {
            // It's an area filter
            $query .= " AND scm.sub_community_id = ?";
            $params[] = $areaMatch['sub_community_id'];
        } else {
            // Treat as name filter
            $query .= " AND u.full_name LIKE ?";
            $params[] = "%{$tag}%";
        }
    }

    $query .= " ORDER BY u.full_name LIMIT 20";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // Regular search without @ symbols
    $query = "SELECT scm.user_id, u.full_name, u.mobile_number, sc.sub_community_name
              FROM sub_community_members scm
              JOIN users u ON scm.user_id = u.user_id
              JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
              WHERE sc.community_id = ?
              AND scm.status = 'active'
              AND (u.full_name LIKE ? OR u.mobile_number LIKE ?)
              ORDER BY u.full_name LIMIT 20";

    $searchPattern = "%{$searchTerm}%";
    $stmt = $db->prepare($query);
    $stmt->execute([$communityId, $searchPattern, $searchPattern]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    'success' => true,
    'members' => $results
]);
