<?php
/**
 * Export Commission to CSV
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

// Get detailed commission transactions
$detailsQuery = "SELECT ce.*, lb.book_number
                 FROM commission_earned ce
                 LEFT JOIN lottery_books lb ON ce.book_id = lb.book_id
                 WHERE ce.event_id = :event_id
                 ORDER BY ce.level_1_value, ce.payment_date";
$stmt = $db->prepare($detailsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$details = $stmt->fetchAll();

// Set headers for CSV download
$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $event['event_name']) . '_Commission_' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Write BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
$headers = ['Level 1', 'Book Number', 'Commission Type', 'Commission %', 'Payment Amount', 'Commission Amount', 'Payment Date'];
fputcsv($output, $headers);

// Write data rows
foreach ($details as $detail) {
    $typeMap = [
        'early' => 'Early Payment',
        'standard' => 'Standard Payment',
        'extra_books' => 'Extra Books'
    ];

    $row = [
        $detail['level_1_value'],
        $detail['book_number'],
        $typeMap[$detail['commission_type']] ?? $detail['commission_type'],
        $detail['commission_percent'],
        $detail['payment_amount'],
        $detail['commission_amount'],
        date('Y-m-d', strtotime($detail['payment_date']))
    ];

    fputcsv($output, $row);
}

// Add summary section
fputcsv($output, []); // Empty row
fputcsv($output, ['SUMMARY']);
fputcsv($output, []);

// Get summary by Level 1
$summaryQuery = "SELECT
                 level_1_value,
                 SUM(CASE WHEN commission_type = 'early' THEN commission_amount ELSE 0 END) as early_commission,
                 SUM(CASE WHEN commission_type = 'standard' THEN commission_amount ELSE 0 END) as standard_commission,
                 SUM(CASE WHEN commission_type = 'extra_books' THEN commission_amount ELSE 0 END) as extra_books_commission,
                 SUM(commission_amount) as total_commission
                 FROM commission_earned
                 WHERE event_id = :event_id
                 GROUP BY level_1_value
                 ORDER BY level_1_value";
$stmt = $db->prepare($summaryQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$summary = $stmt->fetchAll();

// Write summary headers
fputcsv($output, ['Level 1', 'Early Payment', 'Standard Payment', 'Extra Books', 'Total Commission']);

// Write summary data
foreach ($summary as $row) {
    fputcsv($output, [
        $row['level_1_value'],
        $row['early_commission'],
        $row['standard_commission'],
        $row['extra_books_commission'],
        $row['total_commission']
    ]);
}

// Grand total
$grandTotal = array_sum(array_column($summary, 'total_commission'));
$totalEarly = array_sum(array_column($summary, 'early_commission'));
$totalStandard = array_sum(array_column($summary, 'standard_commission'));
$totalExtra = array_sum(array_column($summary, 'extra_books_commission'));

fputcsv($output, []);
fputcsv($output, ['GRAND TOTAL', $totalEarly, $totalStandard, $totalExtra, $grandTotal]);

fclose($output);
exit;
