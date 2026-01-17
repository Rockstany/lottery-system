<?php
/**
 * CSF API - Check Duplicate Payment
 * AJAX endpoint to check if member already paid for selected months
 */

require_once __DIR__ . '/../../../config/config.php';

// Authentication
AuthMiddleware::requireRole('group_admin');

header('Content-Type: application/json');

$communityId = AuthMiddleware::getCommunityId();
$userId = intval($_POST['user_id'] ?? 0);
$monthsJson = $_POST['months'] ?? '[]';
$months = json_decode($monthsJson, true);

// Validate inputs
if ($userId <= 0 || empty($months) || !is_array($months)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$duplicates = [];

foreach ($months as $month) {
    // Check if payment exists for this month (using payment_date)
    $checkQuery = "SELECT COUNT(*) as count FROM csf_payments
                   WHERE community_id = ?
                   AND user_id = ?
                   AND DATE_FORMAT(payment_date, '%Y-%m') = ?";

    $stmt = $db->prepare($checkQuery);
    $stmt->execute([$communityId, $userId, $month]);

    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        $duplicates[] = $month;
    }
}

if (count($duplicates) > 0) {
    // Format months for display
    $formattedMonths = array_map(function($m) {
        $date = DateTime::createFromFormat('Y-m', $m);
        return $date ? $date->format('F Y') : $m;
    }, $duplicates);

    echo json_encode([
        'success' => false,
        'duplicates' => $duplicates,
        'message' => 'Member already paid for: ' . implode(', ', $formattedMonths)
    ]);
} else {
    echo json_encode(['success' => true]);
}
