<?php
/**
 * Final Excel Report Generator
 * Generates comprehensive 5-sheet Excel report for lottery event
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);
$communityId = AuthMiddleware::getCommunityId();

if (!$eventId || !$communityId) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Verify event belongs to community
$eventQuery = "SELECT * FROM lottery_events WHERE event_id = :id AND community_id = :community_id";
$stmt = $db->prepare($eventQuery);
$stmt->bindParam(':id', $eventId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();

// ==========================================
// SHEET 1: BOOK ASSIGNMENTS
// ==========================================
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Book Assignments');

// Header styling
$headerStyle = [
    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// Title
$sheet1->setCellValue('A1', 'Book Assignment Report');
$sheet1->mergeCells('A1:E1');
$sheet1->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$sheet1->setCellValue('A2', 'Event: ' . $event['event_name']);
$sheet1->mergeCells('A2:E2');
$sheet1->getStyle('A2')->applyFromArray([
    'font' => ['size' => 12],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// Headers
$sheet1->setCellValue('A4', 'Serial No');
$sheet1->setCellValue('B4', 'Unit/Location');
$sheet1->setCellValue('C4', 'Assigned To');
$sheet1->setCellValue('D4', 'Mobile Number');
$sheet1->setCellValue('E4', 'Assignment Date');
$sheet1->getStyle('A4:E4')->applyFromArray($headerStyle);

// Fetch book assignments
$booksQuery = "SELECT
                lb.book_number,
                lb.start_ticket_number,
                lb.end_ticket_number,
                bd.distribution_path,
                bd.notes as assigned_to,
                bd.mobile_number,
                bd.distributed_at
              FROM lottery_books lb
              LEFT JOIN book_distribution bd ON lb.book_id = bd.book_id
              WHERE lb.event_id = :event_id
              ORDER BY lb.start_ticket_number ASC";
$stmt = $db->prepare($booksQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$books = $stmt->fetchAll();

$row = 5;
foreach ($books as $book) {
    // Use start ticket number as Serial No
    $sheet1->setCellValue('A' . $row, $book['start_ticket_number']);
    $sheet1->setCellValue('B' . $row, $book['distribution_path'] ?? 'Not Assigned');
    $sheet1->setCellValue('C' . $row, $book['assigned_to'] ?? '-');
    $sheet1->setCellValue('D' . $row, $book['mobile_number'] ?? '-');
    $sheet1->setCellValue('E' . $row, $book['distributed_at'] ? date('d-M-Y', strtotime($book['distributed_at'])) : '-');
    $row++;
}

// Apply borders and formatting
$lastRow = $row - 1;
$sheet1->getStyle('A4:E' . $lastRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

// Auto-size columns
foreach (range('A', 'E') as $col) {
    $sheet1->getColumnDimension($col)->setAutoSize(true);
}

// ==========================================
// SHEET 2: PAYMENT DETAILS
// ==========================================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Payment Details');

// Title
$sheet2->setCellValue('A1', 'Payment Collection Report');
$sheet2->mergeCells('A1:F1');
$sheet2->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// Headers
$sheet2->setCellValue('A3', 'Serial No');
$sheet2->setCellValue('B3', 'Unit/Location');
$sheet2->setCellValue('C3', 'Amount Paid');
$sheet2->setCellValue('D3', 'Payment Date');
$sheet2->setCellValue('E3', 'Payment Method');
$sheet2->setCellValue('F3', 'Payment Status');
$sheet2->getStyle('A3:F3')->applyFromArray($headerStyle);

// Fetch payments
$paymentsQuery = "SELECT
                    lb.start_ticket_number,
                    bd.distribution_path,
                    pc.amount_paid,
                    pc.payment_date,
                    pc.payment_method,
                    (le.tickets_per_book * le.price_per_ticket) as expected_amount,
                    COALESCE(SUM(pc2.amount_paid), 0) as total_paid
                  FROM payment_collections pc
                  JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                  JOIN lottery_books lb ON bd.book_id = lb.book_id
                  JOIN lottery_events le ON lb.event_id = le.event_id
                  LEFT JOIN payment_collections pc2 ON bd.distribution_id = pc2.distribution_id
                    AND pc2.payment_id <= pc.payment_id
                  WHERE le.event_id = :event_id
                  GROUP BY pc.payment_id
                  ORDER BY pc.payment_date ASC, lb.start_ticket_number ASC";
$stmt = $db->prepare($paymentsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$payments = $stmt->fetchAll();

$row = 4;
foreach ($payments as $payment) {
    $paymentStatus = ($payment['total_paid'] >= $payment['expected_amount']) ? 'Fully Paid' : 'Partial';

    $sheet2->setCellValue('A' . $row, $payment['start_ticket_number']);
    $sheet2->setCellValue('B' . $row, $payment['distribution_path'] ?? '-');
    $sheet2->setCellValue('C' . $row, '₹' . number_format($payment['amount_paid'], 2));
    $sheet2->setCellValue('D' . $row, date('d-M-Y', strtotime($payment['payment_date'])));
    $sheet2->setCellValue('E' . $row, ucfirst($payment['payment_method']));
    $sheet2->setCellValue('F' . $row, $paymentStatus);
    $row++;
}

// Formatting
$lastRow = $row - 1;
$sheet2->getStyle('A3:F' . $lastRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

foreach (range('A', 'F') as $col) {
    $sheet2->getColumnDimension($col)->setAutoSize(true);
}

// ==========================================
// SHEET 3: COMMISSION REPORT
// ==========================================
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Commission Report');

// Title
$sheet3->setCellValue('A1', 'Commission Earned Report');
$sheet3->mergeCells('A1:G1');
$sheet3->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// Headers
$sheet3->setCellValue('A3', 'Serial No');
$sheet3->setCellValue('B3', 'Unit/Location');
$sheet3->setCellValue('C3', 'Commission Type');
$sheet3->setCellValue('D3', 'Payment Amount');
$sheet3->setCellValue('E3', 'Commission %');
$sheet3->setCellValue('F3', 'Commission Amount');
$sheet3->setCellValue('G3', 'Payment Date');
$sheet3->getStyle('A3:G3')->applyFromArray($headerStyle);

// Fetch commissions
$commissionsQuery = "SELECT
                      lb.start_ticket_number,
                      ce.level_1_value as unit,
                      ce.commission_type,
                      ce.payment_amount,
                      ce.commission_percent,
                      ce.commission_amount,
                      ce.payment_date
                    FROM commission_earned ce
                    JOIN lottery_books lb ON ce.book_id = lb.book_id
                    WHERE ce.event_id = :event_id
                    ORDER BY ce.payment_date ASC, lb.start_ticket_number ASC";
$stmt = $db->prepare($commissionsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$commissions = $stmt->fetchAll();

$row = 4;
foreach ($commissions as $comm) {
    $commType = match($comm['commission_type']) {
        'early' => 'Early Payment',
        'standard' => 'Standard',
        'extra_books' => 'Extra Book',
        default => ucfirst($comm['commission_type'])
    };

    $sheet3->setCellValue('A' . $row, $comm['start_ticket_number']);
    $sheet3->setCellValue('B' . $row, $comm['unit']);
    $sheet3->setCellValue('C' . $row, $commType);
    $sheet3->setCellValue('D' . $row, '₹' . number_format($comm['payment_amount'], 2));
    $sheet3->setCellValue('E' . $row, $comm['commission_percent'] . '%');
    $sheet3->setCellValue('F' . $row, '₹' . number_format($comm['commission_amount'], 2));
    $sheet3->setCellValue('G' . $row, date('d-M-Y', strtotime($comm['payment_date'])));
    $row++;
}

// Formatting
$lastRow = $row - 1;
$sheet3->getStyle('A3:G' . $lastRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

foreach (range('A', 'G') as $col) {
    $sheet3->getColumnDimension($col)->setAutoSize(true);
}

// ==========================================
// SHEET 4: EARNINGS & COST ANALYSIS
// ==========================================
$sheet4 = $spreadsheet->createSheet();
$sheet4->setTitle('Earnings Analysis');

// Title
$sheet4->setCellValue('A1', 'Earnings & Cost Analysis');
$sheet4->mergeCells('A1:D1');
$sheet4->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$currentRow = 3;

// Section 1: Date-Wise Money Earning
$sheet4->setCellValue('A' . $currentRow, 'Date-Wise Money Earning');
$sheet4->mergeCells('A' . $currentRow . ':D' . $currentRow);
$sheet4->getStyle('A' . $currentRow)->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']]
]);
$currentRow++;

$sheet4->setCellValue('A' . $currentRow, 'Date');
$sheet4->setCellValue('B' . $currentRow, 'Total Collected');
$sheet4->setCellValue('C' . $currentRow, 'Total Commission');
$sheet4->setCellValue('D' . $currentRow, 'Net Earning');
$sheet4->getStyle('A' . $currentRow . ':D' . $currentRow)->applyFromArray($headerStyle);
$currentRow++;

$dataStartRow = $currentRow;

// Fetch date-wise data
$dateWiseQuery = "SELECT
                    pc.payment_date,
                    SUM(pc.amount_paid) as total_collected,
                    COALESCE(SUM(ce.commission_amount), 0) as total_commission
                  FROM payment_collections pc
                  JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                  JOIN lottery_books lb ON bd.book_id = lb.book_id
                  LEFT JOIN commission_earned ce ON bd.distribution_id = ce.distribution_id
                    AND DATE(ce.payment_date) = DATE(pc.payment_date)
                  WHERE lb.event_id = :event_id
                  GROUP BY DATE(pc.payment_date)
                  ORDER BY pc.payment_date ASC";
$stmt = $db->prepare($dateWiseQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$dateWise = $stmt->fetchAll();

foreach ($dateWise as $data) {
    $sheet4->setCellValue('A' . $currentRow, date('d-M-Y', strtotime($data['payment_date'])));
    $sheet4->setCellValue('B' . $currentRow, $data['total_collected']);
    $sheet4->setCellValue('C' . $currentRow, $data['total_commission']);
    $sheet4->setCellValue('D' . $currentRow, '=B' . $currentRow . '-C' . $currentRow);
    $currentRow++;
}

// Add 8 extra blank rows with formulas
for ($i = 0; $i < 8; $i++) {
    $sheet4->setCellValue('A' . $currentRow, '');
    $sheet4->setCellValue('B' . $currentRow, 0);
    $sheet4->setCellValue('C' . $currentRow, 0);
    $sheet4->setCellValue('D' . $currentRow, '=B' . $currentRow . '-C' . $currentRow);
    $currentRow++;
}

$dataEndRow = $currentRow - 1;
$sheet4->getStyle('A' . $dataStartRow . ':D' . $dataEndRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);
$sheet4->getStyle('B' . $dataStartRow . ':D' . $dataEndRow)->getNumberFormat()
    ->setFormatCode('₹#,##0.00');

$currentRow += 2;

// Section 2: Date-Wise Commission
$sheet4->setCellValue('A' . $currentRow, 'Date-Wise Commission Breakdown');
$sheet4->mergeCells('A' . $currentRow . ':E' . $currentRow);
$sheet4->getStyle('A' . $currentRow)->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']]
]);
$currentRow++;

$sheet4->setCellValue('A' . $currentRow, 'Date');
$sheet4->setCellValue('B' . $currentRow, 'Early Commission');
$sheet4->setCellValue('C' . $currentRow, 'Standard Commission');
$sheet4->setCellValue('D' . $currentRow, 'Extra Books');
$sheet4->setCellValue('E' . $currentRow, 'Total Commission');
$sheet4->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray($headerStyle);
$currentRow++;

$dataStartRow = $currentRow;

// Fetch commission breakdown
$commBreakdownQuery = "SELECT
                         DATE(payment_date) as payment_date,
                         SUM(CASE WHEN commission_type = 'early' THEN commission_amount ELSE 0 END) as early_comm,
                         SUM(CASE WHEN commission_type = 'standard' THEN commission_amount ELSE 0 END) as standard_comm,
                         SUM(CASE WHEN commission_type = 'extra_books' THEN commission_amount ELSE 0 END) as extra_comm,
                         SUM(commission_amount) as total_comm
                       FROM commission_earned
                       WHERE event_id = :event_id
                       GROUP BY DATE(payment_date)
                       ORDER BY payment_date ASC";
$stmt = $db->prepare($commBreakdownQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$commBreakdown = $stmt->fetchAll();

foreach ($commBreakdown as $data) {
    $sheet4->setCellValue('A' . $currentRow, date('d-M-Y', strtotime($data['payment_date'])));
    $sheet4->setCellValue('B' . $currentRow, $data['early_comm']);
    $sheet4->setCellValue('C' . $currentRow, $data['standard_comm']);
    $sheet4->setCellValue('D' . $currentRow, $data['extra_comm']);
    $sheet4->setCellValue('E' . $currentRow, '=B' . $currentRow . '+C' . $currentRow . '+D' . $currentRow);
    $currentRow++;
}

// Add 8 extra blank rows
for ($i = 0; $i < 8; $i++) {
    $sheet4->setCellValue('A' . $currentRow, '');
    $sheet4->setCellValue('B' . $currentRow, 0);
    $sheet4->setCellValue('C' . $currentRow, 0);
    $sheet4->setCellValue('D' . $currentRow, 0);
    $sheet4->setCellValue('E' . $currentRow, '=B' . $currentRow . '+C' . $currentRow . '+D' . $currentRow);
    $currentRow++;
}

$dataEndRow = $currentRow - 1;
$sheet4->getStyle('A' . $dataStartRow . ':E' . $dataEndRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);
$sheet4->getStyle('B' . $dataStartRow . ':E' . $dataEndRow)->getNumberFormat()
    ->setFormatCode('₹#,##0.00');

$currentRow += 2;

// Section 3: Payment Method-Wise Report
$sheet4->setCellValue('A' . $currentRow, 'Payment Method-Wise Report');
$sheet4->mergeCells('A' . $currentRow . ':D' . $currentRow);
$sheet4->getStyle('A' . $currentRow)->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']]
]);
$currentRow++;

$sheet4->setCellValue('A' . $currentRow, 'Payment Method');
$sheet4->setCellValue('B' . $currentRow, 'Count');
$sheet4->setCellValue('C' . $currentRow, 'Total Amount');
$sheet4->setCellValue('D' . $currentRow, 'Percentage');
$sheet4->getStyle('A' . $currentRow . ':D' . $currentRow)->applyFromArray($headerStyle);
$currentRow++;

$dataStartRow = $currentRow;

// Fetch payment method data
$methodQuery = "SELECT
                  pc.payment_method,
                  COUNT(*) as count,
                  SUM(pc.amount_paid) as total_amount
                FROM payment_collections pc
                JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                JOIN lottery_books lb ON bd.book_id = lb.book_id
                WHERE lb.event_id = :event_id
                GROUP BY pc.payment_method
                ORDER BY total_amount DESC";
$stmt = $db->prepare($methodQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$methods = $stmt->fetchAll();

// Get total for percentage
$totalAmount = array_sum(array_column($methods, 'total_amount'));

foreach ($methods as $method) {
    $percentage = $totalAmount > 0 ? ($method['total_amount'] / $totalAmount) * 100 : 0;

    $sheet4->setCellValue('A' . $currentRow, ucfirst($method['payment_method']));
    $sheet4->setCellValue('B' . $currentRow, $method['count']);
    $sheet4->setCellValue('C' . $currentRow, $method['total_amount']);
    $sheet4->setCellValue('D' . $currentRow, number_format($percentage, 1) . '%');
    $currentRow++;
}

// Add 8 extra blank rows
for ($i = 0; $i < 8; $i++) {
    $sheet4->setCellValue('A' . $currentRow, '');
    $sheet4->setCellValue('B' . $currentRow, 0);
    $sheet4->setCellValue('C' . $currentRow, 0);
    $sheet4->setCellValue('D' . $currentRow, '0%');
    $currentRow++;
}

$dataEndRow = $currentRow - 1;
$sheet4->getStyle('A' . $dataStartRow . ':D' . $dataEndRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);
$sheet4->getStyle('C' . $dataStartRow . ':C' . $dataEndRow)->getNumberFormat()
    ->setFormatCode('₹#,##0.00');

foreach (range('A', 'E') as $col) {
    $sheet4->getColumnDimension($col)->setAutoSize(true);
}

// ==========================================
// SHEET 5: OVERALL EARNING SUMMARY
// ==========================================
$sheet5 = $spreadsheet->createSheet();
$sheet5->setTitle('Overall Earning');

// Title
$sheet5->setCellValue('A1', 'Overall Earning Summary');
$sheet5->mergeCells('A1:B1');
$sheet5->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$sheet5->setCellValue('A2', 'Event: ' . $event['event_name']);
$sheet5->mergeCells('A2:B2');
$sheet5->getStyle('A2')->applyFromArray([
    'font' => ['size' => 12],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// Calculate totals
$totalCollectedQuery = "SELECT COALESCE(SUM(pc.amount_paid), 0) as total
                        FROM payment_collections pc
                        JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                        JOIN lottery_books lb ON bd.book_id = lb.book_id
                        WHERE lb.event_id = :event_id";
$stmt = $db->prepare($totalCollectedQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$totalCollected = $stmt->fetch()['total'];

$totalCommissionQuery = "SELECT COALESCE(SUM(commission_amount), 0) as total
                         FROM commission_earned
                         WHERE event_id = :event_id";
$stmt = $db->prepare($totalCommissionQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$totalCommission = $stmt->fetch()['total'];

$currentRow = 4;

// Headers
$sheet5->setCellValue('A' . $currentRow, 'Description');
$sheet5->setCellValue('B' . $currentRow, 'Amount');
$sheet5->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($headerStyle);
$currentRow++;

$dataStartRow = $currentRow;

// Total Collected
$sheet5->setCellValue('A' . $currentRow, 'Total Money Collected');
$sheet5->setCellValue('B' . $currentRow, $totalCollected);
$sheet5->getStyle('A' . $currentRow)->getFont()->setBold(true);
$currentRow++;

// Total Commission
$sheet5->setCellValue('A' . $currentRow, '(-) Total Commission Paid');
$sheet5->setCellValue('B' . $currentRow, $totalCommission);
$currentRow++;

// Extra cost rows (5 rows for user to fill)
$costRows = ['Printing Cost', 'Prize Money', 'Administrative Cost', 'Other Expense 1', 'Other Expense 2'];
foreach ($costRows as $costLabel) {
    $sheet5->setCellValue('A' . $currentRow, '(-) ' . $costLabel);
    $sheet5->setCellValue('B' . $currentRow, 0);
    $sheet5->getStyle('B' . $currentRow)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFCC'); // Light yellow for user input
    $currentRow++;
}

// Net Profit (with formula)
$sheet5->setCellValue('A' . $currentRow, 'Net Profit');
$formulaRow = $currentRow;
$totalRow = $dataStartRow;
$lastCostRow = $currentRow - 1;
$sheet5->setCellValue('B' . $currentRow, '=B' . $totalRow . '-SUM(B' . ($totalRow + 1) . ':B' . $lastCostRow . ')');
$sheet5->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6E0B4']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
]);

// Formatting
$sheet5->getStyle('A' . $dataStartRow . ':B' . $currentRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);
$sheet5->getStyle('B' . $dataStartRow . ':B' . $currentRow)->getNumberFormat()
    ->setFormatCode('₹#,##0.00');

$sheet5->getColumnDimension('A')->setWidth(30);
$sheet5->getColumnDimension('B')->setWidth(20);

// Set active sheet to first sheet
$spreadsheet->setActiveSheetIndex(0);

// Save file
$filename = 'Final_Report_' . str_replace(' ', '_', $event['event_name']) . '_' . date('Y-m-d') . '.xlsx';
$filepath = __DIR__ . '/../../temp/' . $filename;

// Create temp directory if doesn't exist
if (!file_exists(__DIR__ . '/../../temp/')) {
    mkdir(__DIR__ . '/../../temp/', 0777, true);
}

$writer = new Xlsx($spreadsheet);
$writer->save($filepath);

// Download file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

readfile($filepath);

// Delete temp file
unlink($filepath);

exit;
