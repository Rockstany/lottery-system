<?php
/**
 * Level-Wise Report Excel Export (XLSX Format)
 * Exports detailed book distribution with payment information using PHPSpreadsheet
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$eventId) {
    header("Location: /public/group-admin/lottery/lottery.php");
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
    header("Location: /public/group-admin/lottery/lottery.php");
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

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Level Wise Report');

// Set page orientation to landscape
$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

// Title row
$sheet->setCellValue('A1', 'Level-Wise Lottery Report: ' . $event['event_name']);
$sheet->mergeCells('A1:' . chr(65 + count($levels) + 11) . '1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Date row
$sheet->setCellValue('A2', 'Generated on: ' . date('d-M-Y h:i A'));
$sheet->mergeCells('A2:' . chr(65 + count($levels) + 11) . '2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header row (row 4)
$row = 4;
$col = 0;

// Define headers
$headers = ['Sr. No.'];
foreach ($levels as $level) {
    $headers[] = $level['level_name'];
}
$headers = array_merge($headers, [
    'Member Name',
    'Mobile Number',
    'Book Number',
    'Ticket Range',
    'Expected Amount (₹)',
    'Total Paid (₹)',
    'Outstanding (₹)',
    'Payment Status',
    'Payment Date(s)',
    'Payment Method(s)',
    'Book Returned Status',
    'Distribution Date'
]);

// Write headers
foreach ($headers as $header) {
    $cell = chr(65 + $col) . $row;
    $sheet->setCellValue($cell, $header);

    // Style header
    $sheet->getStyle($cell)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    // Set column width
    $sheet->getColumnDimension(chr(65 + $col))->setWidth(15);
    $col++;
}

// Data rows
$totalExpected = 0;
$totalPaid = 0;
$totalOutstanding = 0;
$dataRow = 5;

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

    // Write data
    $col = 0;

    // Sr No
    $sheet->setCellValue(chr(65 + $col) . $dataRow, $index + 1);
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $col++;

    // Level values
    for ($i = 0; $i < count($levels); $i++) {
        $sheet->setCellValue(chr(65 + $col) . $dataRow, $levelValues[$i] ?? '-');
        $col++;
    }

    // Member details
    $sheet->setCellValue(chr(65 + $col) . $dataRow, $member['notes'] ?? '-'); $col++;
    $sheet->setCellValue(chr(65 + $col) . $dataRow, $member['mobile_number'] ?? '-'); $col++;
    $sheet->setCellValue(chr(65 + $col) . $dataRow, $member['book_number']);
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $col++;

    $sheet->setCellValue(chr(65 + $col) . $dataRow, $member['start_ticket_number'] . ' - ' . $member['end_ticket_number']);
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $col++;

    // Amounts
    $sheet->setCellValue(chr(65 + $col) . $dataRow, $member['expected_amount']);
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $col++;

    $sheet->setCellValue(chr(65 + $col) . $dataRow, $member['total_paid']);
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $col++;

    $sheet->setCellValue(chr(65 + $col) . $dataRow, $member['outstanding']);
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $col++;

    // Payment status with color coding
    $statusCell = chr(65 + $col) . $dataRow;
    $sheet->setCellValue($statusCell, $member['payment_status']);
    $sheet->getStyle($statusCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    if ($member['payment_status'] === 'Fully Paid') {
        $sheet->getStyle($statusCell)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6EFCE']],
            'font' => ['color' => ['rgb' => '006100']]
        ]);
    } elseif ($member['payment_status'] === 'Partially Paid') {
        $sheet->getStyle($statusCell)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEB9C']],
            'font' => ['color' => ['rgb' => '9C6500']]
        ]);
    } else {
        $sheet->getStyle($statusCell)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC7CE']],
            'font' => ['color' => ['rgb' => '9C0006']]
        ]);
    }
    $col++;

    // Payment details
    $sheet->setCellValue(chr(65 + $col) . $dataRow, $paymentDateStr); $col++;
    $sheet->setCellValue(chr(65 + $col) . $dataRow, $paymentMethodStr); $col++;

    // Return status with color coding
    $returnCell = chr(65 + $col) . $dataRow;
    $returnStatus = $member['is_returned'] == 1 ? 'Returned' : 'Not Returned';
    $sheet->setCellValue($returnCell, $returnStatus);
    $sheet->getStyle($returnCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    if ($member['is_returned'] == 1) {
        $sheet->getStyle($returnCell)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6EFCE']],
            'font' => ['color' => ['rgb' => '006100']]
        ]);
    } else {
        $sheet->getStyle($returnCell)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEB9C']],
            'font' => ['color' => ['rgb' => '9C6500']]
        ]);
    }
    $col++;

    // Distribution date
    $sheet->setCellValue(chr(65 + $col) . $dataRow, date('d-M-Y', strtotime($member['distributed_at'])));
    $sheet->getStyle(chr(65 + $col) . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Apply borders to entire row
    $sheet->getStyle('A' . $dataRow . ':' . chr(65 + $col) . $dataRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    $dataRow++;
}

// Total row
$totalRow = $dataRow;
$totalLabelCol = count($levels) + 4; // After Sr No + Levels + Member Name + Mobile + Book Number
$sheet->setCellValue(chr(65 + $totalLabelCol) . $totalRow, 'TOTAL:');
$sheet->getStyle(chr(65 + $totalLabelCol) . $totalRow)->getFont()->setBold(true);
$sheet->getStyle(chr(65 + $totalLabelCol) . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$totalAmountCol = $totalLabelCol + 1;
$sheet->setCellValue(chr(65 + $totalAmountCol) . $totalRow, $totalExpected);
$sheet->getStyle(chr(65 + $totalAmountCol) . $totalRow)->getFont()->setBold(true);
$sheet->getStyle(chr(65 + $totalAmountCol) . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle(chr(65 + $totalAmountCol) . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$sheet->setCellValue(chr(65 + $totalAmountCol + 1) . $totalRow, $totalPaid);
$sheet->getStyle(chr(65 + $totalAmountCol + 1) . $totalRow)->getFont()->setBold(true);
$sheet->getStyle(chr(65 + $totalAmountCol + 1) . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle(chr(65 + $totalAmountCol + 1) . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$sheet->setCellValue(chr(65 + $totalAmountCol + 2) . $totalRow, $totalOutstanding);
$sheet->getStyle(chr(65 + $totalAmountCol + 2) . $totalRow)->getFont()->setBold(true);
$sheet->getStyle(chr(65 + $totalAmountCol + 2) . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle(chr(65 + $totalAmountCol + 2) . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$sheet->getStyle('A' . $totalRow . ':' . chr(65 + count($headers) - 1) . $totalRow)->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

// Summary section
$summaryRow = $totalRow + 3;
$sheet->setCellValue('A' . $summaryRow, 'Summary');
$sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(14);

$summaryRow++;
$summaryData = [
    ['Event Name:', $event['event_name']],
    ['Total Books Distributed:', count($members)],
    ['Total Expected Amount:', '₹' . number_format($totalExpected, 0)],
    ['Total Collected:', '₹' . number_format($totalPaid, 0)],
    ['Total Outstanding:', '₹' . number_format($totalOutstanding, 0)],
    ['Collection %:', number_format($totalExpected > 0 ? ($totalPaid / $totalExpected) * 100 : 0, 2) . '%']
];

// Count statuses
$paidCount = $partialCount = $unpaidCount = $returnedCount = $notReturnedCount = 0;
foreach ($members as $member) {
    if ($member['payment_status'] === 'Fully Paid') $paidCount++;
    elseif ($member['payment_status'] === 'Partially Paid') $partialCount++;
    else $unpaidCount++;

    if ($member['is_returned'] == 1) $returnedCount++;
    else $notReturnedCount++;
}

$summaryData[] = ['Fully Paid:', $paidCount];
$summaryData[] = ['Partially Paid:', $partialCount];
$summaryData[] = ['Unpaid:', $unpaidCount];
$summaryData[] = ['Books Returned:', $returnedCount];
$summaryData[] = ['Books Not Returned:', $notReturnedCount];

foreach ($summaryData as $data) {
    $sheet->setCellValue('A' . $summaryRow, $data[0]);
    $sheet->setCellValue('B' . $summaryRow, $data[1]);
    $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);
    $sheet->getStyle('A' . $summaryRow . ':B' . $summaryRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $summaryRow++;
}

// Auto-size columns
foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Generate filename
$filename = 'Level_Wise_Report_' . $event['event_name'] . '_' . date('Y-m-d') . '.xlsx';
$filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Write file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
