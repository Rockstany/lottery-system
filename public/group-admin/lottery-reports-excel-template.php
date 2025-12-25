<?php
/**
 * Excel Template Generator for Level-Wise Report
 * Downloads a standardized Excel template with current data or blank template
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);
$templateType = $_GET['type'] ?? 'with_data'; // 'with_data' or 'blank'

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

// Get level values for reference sheet
$allLevelValues = [];
foreach ($levels as $level) {
    $valuesQuery = "SELECT * FROM distribution_level_values WHERE level_id = :level_id ORDER BY value_name";
    $valuesStmt = $db->prepare($valuesQuery);
    $valuesStmt->bindValue(':level_id', $level['level_id']);
    $valuesStmt->execute();
    $values = $valuesStmt->fetchAll();
    $allLevelValues[$level['level_name']] = $values;
}

$members = [];
if ($templateType === 'with_data') {
    // Get existing data
    $memberQuery = "SELECT
        bd.distribution_id,
        bd.notes,
        bd.distribution_path,
        bd.mobile_number,
        bd.is_returned,
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

    // Get payment details for each member
    $memberPayments = [];
    foreach ($members as &$member) {
        $paymentQuery = "SELECT payment_date, payment_method, amount_paid
                         FROM payment_collections
                         WHERE distribution_id = :distribution_id
                         ORDER BY payment_date DESC";
        $paymentStmt = $db->prepare($paymentQuery);
        $paymentStmt->bindValue(':distribution_id', $member['distribution_id'], PDO::PARAM_INT);
        $paymentStmt->execute();
        $payments = $paymentStmt->fetchAll();

        $paymentDates = [];
        $paymentMethods = [];
        foreach ($payments as $payment) {
            $paymentDates[] = date('d-m-Y', strtotime($payment['payment_date']));
            $paymentMethods[] = ucfirst($payment['payment_method']);
        }

        $member['payment_dates'] = !empty($paymentDates) ? implode(', ', $paymentDates) : '';
        $member['payment_methods'] = !empty($paymentMethods) ? implode(', ', $paymentMethods) : '';
    }
}

// Set headers for Excel download
$filename = $templateType === 'blank'
    ? 'Level_Wise_Template_Blank_' . date('Y-m-d') . '.xls'
    : 'Level_Wise_Template_' . $event['event_name'] . '_' . date('Y-m-d') . '.xls';

// Clean filename - remove special characters that might cause issues
$filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Start HTML (simplified, without XML declaration that causes issues)
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="ProgId" content="Excel.Sheet">
    <!--[if gte mso 9]><xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Level Wise Report</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml><![endif]-->
    <style>
        table { border-collapse: collapse; }
        th { background-color: #4472C4; color: white; font-weight: bold; padding: 10px; border: 1px solid #000; text-align: center; }
        td { padding: 8px; border: 1px solid #000; }
        .header-row { background-color: #4472C4; color: white; font-weight: bold; }
        .instruction { background-color: #FFF4CE; color: #856404; font-weight: bold; }
        .center { text-align: center; }
        .sample { background-color: #E7F3FF; }
    </style>
</head>
<body>
<?php

// Instructions Sheet
echo '<h2>📋 INSTRUCTIONS - READ BEFORE EDITING</h2>';
echo '<table border="1" style="width: 100%; margin-bottom: 20px;">';
echo '<tr class="instruction"><td colspan="2">IMPORTANT: Follow this format exactly for upload to work correctly</td></tr>';
echo '<tr><td width="30%"><strong>Step 1:</strong></td><td>Do NOT modify column headers or their order</td></tr>';
echo '<tr><td><strong>Step 2:</strong></td><td>Fill distribution levels exactly as shown in the Reference Data sheet</td></tr>';
echo '<tr><td><strong>Step 3:</strong></td><td>Book Number must match existing books in the system</td></tr>';
echo '<tr><td><strong>Step 4:</strong></td><td>Payment Amount should be the TOTAL paid (system will calculate outstanding)</td></tr>';
echo '<tr><td><strong>Step 5:</strong></td><td>Payment Date format: DD-MM-YYYY (e.g., 25-12-2025)</td></tr>';
echo '<tr><td><strong>Step 6:</strong></td><td>Payment Status: Use exactly "Fully Paid", "Partially Paid", or "Unpaid"</td></tr>';
echo '<tr><td><strong>Step 7:</strong></td><td>Payment Method: Use exactly "Cash", "UPI", "Bank Transfer", or "Cheque"</td></tr>';
echo '<tr><td><strong>Step 8:</strong></td><td>Book Returned Status: Use exactly "Returned" or "Not Returned"</td></tr>';
echo '<tr><td><strong>Step 9:</strong></td><td>Mobile numbers should be 10 digits (system will auto-format)</td></tr>';
echo '<tr><td><strong>Step 10:</strong></td><td>Save as .xls or .xlsx and upload through the Reports page</td></tr>';
echo '</table>';

// Reference Data Sheet
echo '<h2>📚 REFERENCE DATA - Valid Values</h2>';
echo '<table border="1" style="margin-bottom: 30px;">';

foreach ($allLevelValues as $levelName => $values) {
    echo '<tr class="header-row"><td colspan="2">' . htmlspecialchars($levelName) . ' Values</td></tr>';
    foreach ($values as $value) {
        echo '<tr><td width="50%">' . htmlspecialchars($value['value_name']) . '</td>';
        echo '<td>' . ($value['parent_value_id'] ? 'Parent Required' : 'Root Level') . '</td></tr>';
    }
}

echo '<tr class="header-row"><td colspan="2">Payment Status Values</td></tr>';
echo '<tr><td>Fully Paid</td><td>Book amount completely paid</td></tr>';
echo '<tr><td>Partially Paid</td><td>Some amount paid, balance remaining</td></tr>';
echo '<tr><td>Unpaid</td><td>No payment received</td></tr>';

echo '<tr class="header-row"><td colspan="2">Payment Method Values</td></tr>';
echo '<tr><td>Cash</td><td>Cash payment</td></tr>';
echo '<tr><td>UPI</td><td>UPI/Digital payment</td></tr>';
echo '<tr><td>Bank Transfer</td><td>Bank transfer/NEFT/IMPS</td></tr>';
echo '<tr><td>Cheque</td><td>Cheque payment</td></tr>';

echo '<tr class="header-row"><td colspan="2">Book Return Status Values</td></tr>';
echo '<tr><td>Returned</td><td>Book has been returned</td></tr>';
echo '<tr><td>Not Returned</td><td>Book not yet returned</td></tr>';

echo '</table>';

// Main Data Sheet
echo '<h2>📊 LEVEL-WISE REPORT DATA - ' . htmlspecialchars($event['event_name']) . '</h2>';
echo '<p><strong>Event:</strong> ' . htmlspecialchars($event['event_name']) . ' | ';
echo '<strong>Template Type:</strong> ' . ($templateType === 'blank' ? 'Blank Template' : 'With Current Data') . ' | ';
echo '<strong>Generated:</strong> ' . date('d-M-Y h:i A') . '</p>';

echo '<table border="1">';
echo '<thead>';
echo '<tr class="header-row">';
echo '<th>Sr No</th>';

// Dynamic level headers
foreach ($levels as $level) {
    echo '<th>' . htmlspecialchars($level['level_name']) . '</th>';
}

echo '<th>Member Name</th>';
echo '<th>Mobile Number</th>';
echo '<th>Book Number</th>';
echo '<th>Payment Amount (₹)</th>';
echo '<th>Payment Date</th>';
echo '<th>Payment Status</th>';
echo '<th>Payment Method</th>';
echo '<th>Book Returned Status</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if ($templateType === 'with_data' && count($members) > 0) {
    // Export existing data
    foreach ($members as $index => $member) {
        $levelValues = [];
        if (!empty($member['distribution_path'])) {
            $levelValues = explode(' > ', $member['distribution_path']);
        }

        echo '<tr>';
        echo '<td class="center">' . ($index + 1) . '</td>';

        // Level values
        for ($i = 0; $i < count($levels); $i++) {
            echo '<td>' . htmlspecialchars($levelValues[$i] ?? '') . '</td>';
        }

        echo '<td>' . htmlspecialchars($member['notes'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($member['mobile_number'] ?? '') . '</td>';
        echo '<td class="center">' . htmlspecialchars($member['book_number']) . '</td>';
        echo '<td class="center">' . number_format($member['total_paid'], 0) . '</td>';
        echo '<td class="center">' . htmlspecialchars($member['payment_dates']) . '</td>';
        echo '<td class="center">' . htmlspecialchars($member['payment_status']) . '</td>';
        echo '<td class="center">' . htmlspecialchars($member['payment_methods']) . '</td>';
        echo '<td class="center">' . ($member['is_returned'] == 1 ? 'Returned' : 'Not Returned') . '</td>';
        echo '</tr>';
    }
} else {
    // Blank template with sample row
    echo '<tr class="sample">';
    echo '<td class="center">1</td>';

    // Sample level values
    foreach ($levels as $index => $level) {
        $sampleValues = $allLevelValues[$level['level_name']] ?? [];
        $sampleValue = !empty($sampleValues) ? $sampleValues[0]['value_name'] : 'Sample';
        echo '<td>' . htmlspecialchars($sampleValue) . '</td>';
    }

    echo '<td>John Doe</td>';
    echo '<td>9876543210</td>';
    echo '<td>BK0001</td>';
    echo '<td>1000</td>';
    echo '<td>25-12-2025</td>';
    echo '<td>Fully Paid</td>';
    echo '<td>Cash</td>';
    echo '<td>Not Returned</td>';
    echo '</tr>';

    // Add 5 empty rows
    for ($i = 2; $i <= 6; $i++) {
        echo '<tr>';
        echo '<td class="center">' . $i . '</td>';
        foreach ($levels as $level) {
            echo '<td></td>';
        }
        echo '<td></td>'; // Member name
        echo '<td></td>'; // Mobile
        echo '<td></td>'; // Book number
        echo '<td></td>'; // Payment amount
        echo '<td></td>'; // Payment date
        echo '<td></td>'; // Payment status
        echo '<td></td>'; // Payment method
        echo '<td></td>'; // Return status
        echo '</tr>';
    }
}

echo '</tbody>';
echo '</table>';

echo '</body></html>';
exit;
?>
