<?php
/**
 * Export Winners to CSV
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);
$communityId = AuthMiddleware::getCommunityId();

if (!$eventId || !$communityId) {
    header("Location: /public/group-admin/lottery/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get event
$query = "SELECT * FROM lottery_events WHERE event_id = :id AND community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $eventId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery/lottery.php");
    exit;
}

// Get distribution levels for parsing
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($levelsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$levels = $stmt->fetchAll();

// Get all winners
$winnersQuery = "SELECT * FROM lottery_winners WHERE event_id = :event_id ORDER BY
                 CASE prize_position
                     WHEN '1st' THEN 1
                     WHEN '2nd' THEN 2
                     WHEN '3rd' THEN 3
                     WHEN 'consolation' THEN 4
                 END, added_at";
$stmt = $db->prepare($winnersQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$winners = $stmt->fetchAll();

// Set headers for CSV download
$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $event['event_name']) . '_Winners_' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Write BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Build header row
$headers = ['Prize Position', 'Ticket Number', 'Book Number'];

// Add dynamic level headers
foreach ($levels as $level) {
    $headers[] = $level['level_name'];
}

// Add remaining headers
$headers[] = 'Winner Name';
$headers[] = 'Contact Number';
$headers[] = 'Date Added';

fputcsv($output, $headers);

// Write data rows
foreach ($winners as $winner) {
    $row = [
        ucfirst($winner['prize_position']),
        $winner['ticket_number'],
        $winner['book_number']
    ];

    // Parse distribution path into levels
    $levelValues = [];
    if (!empty($winner['distribution_path'])) {
        $levelValues = explode(' > ', $winner['distribution_path']);
    }

    // Add level values
    for ($i = 0; $i < count($levels); $i++) {
        $row[] = $levelValues[$i] ?? '';
    }

    // Add remaining fields
    $row[] = $winner['winner_name'] ?? '';
    $row[] = $winner['winner_contact'] ?? '';
    $row[] = date('Y-m-d H:i', strtotime($winner['added_at']));

    fputcsv($output, $row);
}

fclose($output);
exit;
