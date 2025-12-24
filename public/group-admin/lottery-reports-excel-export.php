<?php
/**
 * Level-Wise Report Excel Export
 * Exports detailed book distribution with payment information
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$eventId) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get event details
$query = "SELECT * FROM lottery_events WHERE event_id = :event_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Get distribution levels
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($levelsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$levels = $stmt->fetchAll();

// Get comprehensive member report with payment details
$memberQuery = "SELECT
    bd.notes,
    bd.distribution_path,
    bd.mobile_number,
    bd.is_returned,
    bd.distribution_id,
    bd.distributed_at,
    lb.book_number,
    lb.start_ticket_number,
    lb.end_ticket_number,
    (le.tickets_per_book * le.price_per_ticket) as expected_amount,
    COALESCE(SUM(pc.amount_paid), 0) as total_paid,
    ((le.tickets_per_book * le.price_per_ticket) - COALESCE(SUM(pc.amount_paid), 0)) as outstanding,
    CASE
        WHEN COALESCE(SUM(pc.amount_paid), 0) >= (le.tickets_per_book * le.price_per_ticket) THEN 'Fully Paid'
        WHEN COALESCE(SUM(pc.amount_paid), 0) > 0 THEN 'Partially Paid'
        ELSE 'Unpaid'
    END as payment_status
    FROM book_distribution bd
    JOIN lottery_books lb ON bd.book_id = lb.book_id
    JOIN lottery_events le ON lb.event_id = le.event_id
    LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
    WHERE le.event_id = :event_id
    GROUP BY bd.distribution_id
    ORDER BY bd.distribution_path ASC, bd.notes ASC";

$stmt = $db->prepare($memberQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$members = $stmt->fetchAll();

// For each member, get detailed payment information
$memberPayments = [];
foreach ($members as $member) {
    $paymentQuery = "SELECT payment_date, payment_method, amount_paid
                     FROM payment_collections
                     WHERE distribution_id = :distribution_id
                     ORDER BY payment_date DESC";
    $paymentStmt = $db->prepare($paymentQuery);
    $paymentStmt->bindValue(':distribution_id', $member['distribution_id'], PDO::PARAM_INT);
    $paymentStmt->execute();
    $payments = $paymentStmt->fetchAll();
    $memberPayments[$member['distribution_id']] = $payments;
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Level_Wise_Report_' . $event['event_name'] . '_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Start HTML table for Excel
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4472C4; color: white; font-weight: bold; padding: 10px; border: 1px solid #000; }
        td { padding: 8px; border: 1px solid #000; }
        .header-row { background-color: #4472C4; color: white; font-weight: bold; }
        .paid { background-color: #C6EFCE; color: #006100; }
        .partial { background-color: #FFEB9C; color: #9C6500; }
        .unpaid { background-color: #FFC7CE; color: #9C0006; }
        .returned { background-color: #C6EFCE; color: #006100; }
        .not-returned { background-color: #FFEB9C; color: #9C6500; }
        .center { text-align: center; }
        .right { text-align: right; }
    </style>
</head>
<body>';

echo '<h2>Level-Wise Lottery Report: ' . htmlspecialchars($event['event_name']) . '</h2>';
echo '<p>Generated on: ' . date('d-M-Y h:i A') . '</p>';
echo '<br>';

// Main table
echo '<table border="1">';
echo '<thead>';
echo '<tr class="header-row">';
echo '<th>Sr. No.</th>';

// Dynamic level headers
foreach ($levels as $level) {
    echo '<th>' . htmlspecialchars($level['level_name']) . '</th>';
}

echo '<th>Member Name</th>';
echo '<th>Mobile Number</th>';
echo '<th>Book Number</th>';
echo '<th>Ticket Range</th>';
echo '<th>Expected Amount (₹)</th>';
echo '<th>Total Paid (₹)</th>';
echo '<th>Outstanding (₹)</th>';
echo '<th>Payment Status</th>';
echo '<th>Payment Date(s)</th>';
echo '<th>Payment Method(s)</th>';
echo '<th>Book Returned Status</th>';
echo '<th>Distribution Date</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$totalExpected = 0;
$totalPaid = 0;
$totalOutstanding = 0;

foreach ($members as $index => $member) {
    $totalExpected += $member['expected_amount'];
    $totalPaid += $member['total_paid'];
    $totalOutstanding += $member['outstanding'];

    // Parse distribution path
    $levelValues = [];
    if (!empty($member['distribution_path'])) {
        $levelValues = explode(' > ', $member['distribution_path']);
    }

    // Get payment details
    $payments = $memberPayments[$member['distribution_id']] ?? [];
    $paymentDates = [];
    $paymentMethods = [];

    foreach ($payments as $payment) {
        $paymentDates[] = date('d-M-Y', strtotime($payment['payment_date']));
        $paymentMethods[] = ucfirst($payment['payment_method']) . ' (₹' . number_format($payment['amount_paid'], 0) . ')';
    }

    $paymentDateStr = !empty($paymentDates) ? implode(', ', $paymentDates) : 'N/A';
    $paymentMethodStr = !empty($paymentMethods) ? implode(', ', $paymentMethods) : 'N/A';

    // Determine cell classes
    $statusClass = '';
    if ($member['payment_status'] === 'Fully Paid') {
        $statusClass = 'paid';
    } elseif ($member['payment_status'] === 'Partially Paid') {
        $statusClass = 'partial';
    } else {
        $statusClass = 'unpaid';
    }

    $returnClass = $member['is_returned'] == 1 ? 'returned' : 'not-returned';

    echo '<tr>';
    echo '<td class="center">' . ($index + 1) . '</td>';

    // Display level values
    for ($i = 0; $i < count($levels); $i++) {
        echo '<td>' . htmlspecialchars($levelValues[$i] ?? '-') . '</td>';
    }

    echo '<td>' . htmlspecialchars($member['notes'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($member['mobile_number'] ?? '-') . '</td>';
    echo '<td class="center">' . htmlspecialchars($member['book_number']) . '</td>';
    echo '<td class="center">' . $member['start_ticket_number'] . ' - ' . $member['end_ticket_number'] . '</td>';
    echo '<td class="right">' . number_format($member['expected_amount'], 0) . '</td>';
    echo '<td class="right">' . number_format($member['total_paid'], 0) . '</td>';
    echo '<td class="right">' . number_format($member['outstanding'], 0) . '</td>';
    echo '<td class="center ' . $statusClass . '">' . $member['payment_status'] . '</td>';
    echo '<td>' . htmlspecialchars($paymentDateStr) . '</td>';
    echo '<td>' . htmlspecialchars($paymentMethodStr) . '</td>';
    echo '<td class="center ' . $returnClass . '">' . ($member['is_returned'] == 1 ? 'Returned' : 'Not Returned') . '</td>';
    echo '<td class="center">' . date('d-M-Y', strtotime($member['distributed_at'])) . '</td>';
    echo '</tr>';
}

// Total row
echo '<tr style="background-color: #f0f0f0; font-weight: bold;">';
echo '<td colspan="' . (count($levels) + 4) . '" class="right">TOTAL:</td>';
echo '<td class="right">' . number_format($totalExpected, 0) . '</td>';
echo '<td class="right">' . number_format($totalPaid, 0) . '</td>';
echo '<td class="right">' . number_format($totalOutstanding, 0) . '</td>';
echo '<td colspan="5"></td>';
echo '</tr>';

echo '</tbody>';
echo '</table>';

// Summary section
echo '<br><br>';
echo '<h3>Summary</h3>';
echo '<table border="1" style="width: 50%;">';
echo '<tr><td><strong>Event Name:</strong></td><td>' . htmlspecialchars($event['event_name']) . '</td></tr>';
echo '<tr><td><strong>Total Books Distributed:</strong></td><td>' . count($members) . '</td></tr>';
echo '<tr><td><strong>Total Expected Amount:</strong></td><td>₹' . number_format($totalExpected, 0) . '</td></tr>';
echo '<tr><td><strong>Total Collected:</strong></td><td>₹' . number_format($totalPaid, 0) . '</td></tr>';
echo '<tr><td><strong>Total Outstanding:</strong></td><td>₹' . number_format($totalOutstanding, 0) . '</td></tr>';

$collectionPercent = $totalExpected > 0 ? ($totalPaid / $totalExpected) * 100 : 0;
echo '<tr><td><strong>Collection %:</strong></td><td>' . number_format($collectionPercent, 2) . '%</td></tr>';

// Count payment statuses
$paidCount = 0;
$partialCount = 0;
$unpaidCount = 0;
$returnedCount = 0;
$notReturnedCount = 0;

foreach ($members as $member) {
    if ($member['payment_status'] === 'Fully Paid') $paidCount++;
    elseif ($member['payment_status'] === 'Partially Paid') $partialCount++;
    else $unpaidCount++;

    if ($member['is_returned'] == 1) $returnedCount++;
    else $notReturnedCount++;
}

echo '<tr><td><strong>Fully Paid:</strong></td><td>' . $paidCount . '</td></tr>';
echo '<tr><td><strong>Partially Paid:</strong></td><td>' . $partialCount . '</td></tr>';
echo '<tr><td><strong>Unpaid:</strong></td><td>' . $unpaidCount . '</td></tr>';
echo '<tr><td><strong>Books Returned:</strong></td><td>' . $returnedCount . '</td></tr>';
echo '<tr><td><strong>Books Not Returned:</strong></td><td>' . $notReturnedCount . '</td></tr>';
echo '</table>';

echo '</body></html>';
exit;
?>
