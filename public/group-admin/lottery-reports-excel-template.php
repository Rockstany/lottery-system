<?php
/**
 * Excel Template Generator for Level-Wise Report (XLSX Format)
 * Downloads a standardized Excel template with current data or blank template using PHPSpreadsheet
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

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

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();

// ===== SHEET 1: Instructions =====
$instructionSheet = $spreadsheet->getActiveSheet();
$instructionSheet->setTitle('Instructions');

$row = 1;
$instructionSheet->setCellValue('A' . $row, '📋 INSTRUCTIONS - READ BEFORE EDITING');
$instructionSheet->mergeCells('A' . $row . ':B' . $row);
$instructionSheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16);
$instructionSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;

$instructions = [
    ['IMPORTANT:', 'Follow this format exactly for upload to work correctly'],
    ['Step 1:', 'Do NOT modify column headers or their order'],
    ['Step 2:', 'Fill distribution levels exactly as shown in the Reference Data sheet'],
    ['Step 3:', 'Book Number must match existing books in the system'],
    ['Step 4:', 'Payment Amount should be the TOTAL paid (system will calculate outstanding)'],
    ['Step 5:', 'Payment Date format: DD-MM-YYYY (e.g., 25-12-2025)'],
    ['Step 6:', 'Payment Status: Use exactly "Fully Paid", "Partially Paid", or "Unpaid"'],
    ['Step 7:', 'Payment Method: Use exactly "Cash", "UPI", "Bank Transfer", or "Cheque"'],
    ['Step 8:', 'Book Returned Status: Use exactly "Returned" or "Not Returned"'],
    ['Step 9:', 'Mobile numbers should be 10 digits (system will auto-format)'],
    ['Step 10:', 'Save as .xlsx and upload through the Reports page']
];

foreach ($instructions as $instruction) {
    $instructionSheet->setCellValue('A' . $row, $instruction[0]);
    $instructionSheet->setCellValue('B' . $row, $instruction[1]);
    $instructionSheet->getStyle('A' . $row)->getFont()->setBold(true);
    $instructionSheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF4CE']]
    ]);
    $row++;
}

$instructionSheet->getColumnDimension('A')->setWidth(20);
$instructionSheet->getColumnDimension('B')->setWidth(70);

// ===== SHEET 2: Reference Data =====
$refSheet = $spreadsheet->createSheet();
$refSheet->setTitle('Reference Data');

$row = 1;
$refSheet->setCellValue('A' . $row, '📚 REFERENCE DATA - Valid Values');
$refSheet->mergeCells('A' . $row . ':B' . $row);
$refSheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16);
$refSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row += 2;

// Level values
foreach ($allLevelValues as $levelName => $values) {
    $refSheet->setCellValue('A' . $row, $levelName . ' Values');
    $refSheet->mergeCells('A' . $row . ':B' . $row);
    $refSheet->getStyle('A' . $row)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $row++;

    foreach ($values as $value) {
        $refSheet->setCellValue('A' . $row, $value['value_name']);
        $refSheet->setCellValue('B' . $row, $value['parent_value_id'] ? 'Parent Required' : 'Root Level');
        $refSheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $row++;
    }
    $row++;
}

// Payment Status Values
$statusData = [
    ['Payment Status Values', ''],
    ['Fully Paid', 'Book amount completely paid'],
    ['Partially Paid', 'Some amount paid, balance remaining'],
    ['Unpaid', 'No payment received'],
    ['', ''],
    ['Payment Method Values', ''],
    ['Cash', 'Cash payment'],
    ['UPI', 'UPI/Digital payment'],
    ['Bank Transfer', 'Bank transfer/NEFT/IMPS'],
    ['Cheque', 'Cheque payment'],
    ['', ''],
    ['Book Return Status Values', ''],
    ['Returned', 'Book has been returned'],
    ['Not Returned', 'Book not yet returned']
];

foreach ($statusData as $data) {
    if (strpos($data[0], 'Values') !== false && !empty($data[0])) {
        $refSheet->setCellValue('A' . $row, $data[0]);
        $refSheet->mergeCells('A' . $row . ':B' . $row);
        $refSheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    } elseif (!empty($data[0])) {
        $refSheet->setCellValue('A' . $row, $data[0]);
        $refSheet->setCellValue('B' . $row, $data[1]);
        $refSheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }
    $row++;
}

$refSheet->getColumnDimension('A')->setWidth(30);
$refSheet->getColumnDimension('B')->setWidth(40);

// ===== SHEET 3: Data Sheet =====
$dataSheet = $spreadsheet->createSheet();
$dataSheet->setTitle('Data');

// Title
$row = 1;
$dataSheet->setCellValue('A' . $row, '📊 LEVEL-WISE REPORT DATA - ' . $event['event_name']);
$dataSheet->mergeCells('A' . $row . ':' . chr(65 + count($levels) + 7) . $row);
$dataSheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$dataSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;

// Info
$dataSheet->setCellValue('A' . $row, 'Event: ' . $event['event_name'] . ' | Type: ' . ($templateType === 'blank' ? 'Blank Template' : 'With Current Data') . ' | Generated: ' . date('d-M-Y h:i A'));
$dataSheet->mergeCells('A' . $row . ':' . chr(65 + count($levels) + 7) . $row);
$dataSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row += 2;

// Headers
$col = 0;
$headers = ['Sr No'];
foreach ($levels as $level) {
    $headers[] = $level['level_name'];
}
$headers = array_merge($headers, [
    'Member Name',
    'Mobile Number',
    'Book Number',
    'Payment Amount (₹)',
    'Payment Date',
    'Payment Status',
    'Payment Method',
    'Book Returned Status'
]);

foreach ($headers as $header) {
    $cell = chr(65 + $col) . $row;
    $dataSheet->setCellValue($cell, $header);
    $dataSheet->getStyle($cell)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $dataSheet->getColumnDimension(chr(65 + $col))->setWidth(15);
    $col++;
}
$row++;

// Data rows
if ($templateType === 'with_data' && count($members) > 0) {
    foreach ($members as $index => $member) {
        $col = 0;
        $levelValues = [];
        if (!empty($member['distribution_path'])) {
            $levelValues = explode(' > ', $member['distribution_path']);
        }

        // Sr No
        $dataSheet->setCellValue(chr(65 + $col) . $row, $index + 1);
        $dataSheet->getStyle(chr(65 + $col) . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $col++;

        // Level values
        for ($i = 0; $i < count($levels); $i++) {
            $dataSheet->setCellValue(chr(65 + $col) . $row, $levelValues[$i] ?? '');
            $col++;
        }

        // Member details
        $dataSheet->setCellValue(chr(65 + $col) . $row, $member['notes'] ?? ''); $col++;
        $dataSheet->setCellValue(chr(65 + $col) . $row, $member['mobile_number'] ?? ''); $col++;
        $dataSheet->setCellValue(chr(65 + $col) . $row, $member['book_number']); $col++;
        $dataSheet->setCellValue(chr(65 + $col) . $row, $member['total_paid']);
        $dataSheet->getStyle(chr(65 + $col) . $row)->getNumberFormat()->setFormatCode('#,##0');
        $col++;
        $dataSheet->setCellValue(chr(65 + $col) . $row, $member['payment_dates']); $col++;
        $dataSheet->setCellValue(chr(65 + $col) . $row, $member['payment_status']); $col++;
        $dataSheet->setCellValue(chr(65 + $col) . $row, $member['payment_methods']); $col++;
        $dataSheet->setCellValue(chr(65 + $col) . $row, $member['is_returned'] == 1 ? 'Returned' : 'Not Returned');

        // Borders
        $dataSheet->getStyle('A' . $row . ':' . chr(65 + $col) . $row)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $row++;
    }
} else {
    // Blank template with sample row
    $col = 0;

    // Sample row (highlighted)
    $dataSheet->setCellValue(chr(65 + $col) . $row, 1); $col++;
    foreach ($levels as $level) {
        $sampleValues = $allLevelValues[$level['level_name']] ?? [];
        $sampleValue = !empty($sampleValues) ? $sampleValues[0]['value_name'] : 'Sample';
        $dataSheet->setCellValue(chr(65 + $col) . $row, $sampleValue);
        $col++;
    }
    $dataSheet->setCellValue(chr(65 + $col) . $row, 'John Doe'); $col++;
    $dataSheet->setCellValue(chr(65 + $col) . $row, '9876543210'); $col++;
    $dataSheet->setCellValue(chr(65 + $col) . $row, 'BK0001'); $col++;
    $dataSheet->setCellValue(chr(65 + $col) . $row, 1000); $col++;
    $dataSheet->setCellValue(chr(65 + $col) . $row, '25-12-2025'); $col++;
    $dataSheet->setCellValue(chr(65 + $col) . $row, 'Fully Paid'); $col++;
    $dataSheet->setCellValue(chr(65 + $col) . $row, 'Cash'); $col++;
    $dataSheet->setCellValue(chr(65 + $col) . $row, 'Not Returned');

    $dataSheet->getStyle('A' . $row . ':' . chr(65 + $col) . $row)->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7F3FF']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $row++;

    // 5 empty rows
    for ($i = 2; $i <= 6; $i++) {
        $col = 0;
        $dataSheet->setCellValue(chr(65 + $col) . $row, $i);
        $totalCols = count($headers);
        $dataSheet->getStyle('A' . $row . ':' . chr(65 + $totalCols - 1) . $row)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $row++;
    }
}

// Set active sheet to Data sheet
$spreadsheet->setActiveSheetIndex(2);

// Generate filename
$filename = $templateType === 'blank'
    ? 'Level_Wise_Template_Blank_' . date('Y-m-d') . '.xlsx'
    : 'Level_Wise_Template_' . $event['event_name'] . '_' . date('Y-m-d') . '.xlsx';
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
