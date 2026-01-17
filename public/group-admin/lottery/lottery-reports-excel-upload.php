<?php
/**
 * Excel Upload Processor for Level-Wise Report (XLSX Format)
 * Processes uploaded Excel files and updates the database using PHPSpreadsheet
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_POST['event_id'] ?? 0);

if (!$eventId) {
    $_SESSION['error'] = "Invalid event ID";
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
    $_SESSION['error'] = "Event not found";
    header("Location: /public/group-admin/lottery/lottery.php");
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = "No file uploaded or upload error occurred";
    header("Location: /public/group-admin/lottery/lottery-reports.php?id=" . $eventId);
    exit;
}

$file = $_FILES['excel_file'];
$allowedExtensions = ['xls', 'xlsx'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    $_SESSION['error'] = "Invalid file format. Please upload .xls or .xlsx file";
    header("Location: /public/group-admin/lottery/lottery-reports.php?id=" . $eventId);
    exit;
}

try {
    // Load the Excel file
    $spreadsheet = IOFactory::load($file['tmp_name']);

    // Debug: Log file loading
    error_log("Excel Upload: File loaded successfully - " . $file['name']);

    // Find the "Data" worksheet (it's the 3rd sheet in our template)
    $dataSheet = null;
    foreach ($spreadsheet->getAllSheets() as $sheet) {
        if ($sheet->getTitle() === 'Data') {
            $dataSheet = $sheet;
            break;
        }
    }

    // If "Data" sheet not found, use the last sheet (backward compatibility)
    if (!$dataSheet) {
        $dataSheet = $spreadsheet->getSheet($spreadsheet->getSheetCount() - 1);
    }

    error_log("Excel Upload: Using sheet - " . $dataSheet->getTitle());

    // Get distribution levels for this event
    $levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
    $stmt = $db->prepare($levelsQuery);
    $stmt->bindParam(':event_id', $eventId);
    $stmt->execute();
    $levels = $stmt->fetchAll();
    $levelCount = count($levels);

    // Column positions (1-indexed in PHPSpreadsheet, but we'll use 0-indexed for array access)
    // Column A (0) = Sr No
    // Columns B onwards (1+) = Levels
    // Then: Member Name, Mobile, Book Number, Payment Amount, Payment Date, Commission Date, Payment Status, Payment Method, Return Status
    $srNoCol = 0;
    $firstLevelCol = 1;
    $memberNameCol = $levelCount + 1;
    $mobileCol = $levelCount + 2;
    $bookNumberCol = $levelCount + 3;
    $paymentAmountCol = $levelCount + 4;
    $paymentDateCol = $levelCount + 5;
    $commissionDateCol = $levelCount + 6;
    $paymentStatusCol = $levelCount + 7;
    $paymentMethodCol = $levelCount + 8;
    $returnStatusCol = $levelCount + 9;

    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $updates = [];

    // Begin transaction
    $db->beginTransaction();

    // Get the highest row number
    $highestRow = $dataSheet->getHighestRow();

    // Find the header row (looking for "Sr No" or "Book Number")
    $headerRow = 0;
    for ($row = 1; $row <= min($highestRow, 10); $row++) {
        $cellValue = $dataSheet->getCellByColumnAndRow(1, $row)->getValue();
        $cellValue = $cellValue ?? ''; // Handle null values
        if (stripos($cellValue, 'Sr No') !== false || stripos($cellValue, 'Book Number') !== false) {
            $headerRow = $row;
            break;
        }
    }

    if ($headerRow === 0) {
        throw new Exception("Could not find header row in Excel file. Please use the correct template format.");
    }

    $updates[] = "📊 Processing Excel file with $levelCount distribution levels";
    $updates[] = "🔍 Found header at row $headerRow, processing " . ($highestRow - $headerRow) . " data rows";

    // Process data rows (start from row after header)
    for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
        // Get all cell values for this row
        $rowData = [];
        for ($col = 0; $col <= $returnStatusCol; $col++) {
            $cellValue = $dataSheet->getCellByColumnAndRow($col + 1, $row)->getValue(); // +1 because PHPSpreadsheet columns are 1-indexed
            $rowData[$col] = trim((string)$cellValue);
        }

        // Skip empty rows (no book number)
        $bookNumber = $rowData[$bookNumberCol] ?? '';
        if (empty($bookNumber)) {
            continue;
        }

        // Get payment data
        $paymentAmount = $rowData[$paymentAmountCol] ?? 0;
        $paymentDateRaw = $rowData[$paymentDateCol] ?? '';
        $paymentStatus = $rowData[$paymentStatusCol] ?? '';
        $paymentMethod = strtolower($rowData[$paymentMethodCol] ?? 'cash');
        $returnStatus = $rowData[$returnStatusCol] ?? '';

        // Parse payment amount (remove commas and currency symbols)
        $paymentAmount = (float) preg_replace('/[^\d.]/', '', $paymentAmount);

        // Parse payment date
        $paymentDate = null;
        if (!empty($paymentDateRaw)) {
            // Try to parse Excel date serial number
            if (is_numeric($paymentDateRaw) && $paymentDateRaw > 25569) { // Excel epoch starts at 1900-01-01
                try {
                    $dateTime = Date::excelToDateTimeObject($paymentDateRaw);
                    $paymentDate = $dateTime->format('Y-m-d');
                } catch (Exception $e) {
                    // Not an Excel date, try text parsing
                }
            }

            // If not Excel date, try parsing as text (DD-MM-YYYY or DD/MM/YYYY)
            if (!$paymentDate) {
                $paymentDateRaw = trim($paymentDateRaw);
                // Handle DD-MM-YYYY or DD/MM/YYYY format
                if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $paymentDateRaw, $matches)) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                    $year = $matches[3];
                    $paymentDate = "$year-$month-$day";
                } else {
                    // Try strtotime as last resort
                    $timestamp = strtotime($paymentDateRaw);
                    if ($timestamp) {
                        $paymentDate = date('Y-m-d', $timestamp);
                    }
                }
            }
        }

        // Use today's date if payment date is still invalid and there's a payment amount
        if (!$paymentDate && $paymentAmount > 0) {
            $paymentDate = date('Y-m-d');
            $updates[] = "⚠️ Row $row (Book $bookNumber): Using today's date for payment (date field was empty/invalid)";
        }

        // Parse commission date (same logic as payment date)
        $commissionDateRaw = $rowData[$commissionDateCol] ?? '';
        $commissionDate = null;
        if (!empty($commissionDateRaw)) {
            // Try to parse Excel date serial number
            if (is_numeric($commissionDateRaw) && $commissionDateRaw > 25569) {
                try {
                    $dateTime = Date::excelToDateTimeObject($commissionDateRaw);
                    $commissionDate = $dateTime->format('Y-m-d');
                } catch (Exception $e) {
                    // Not an Excel date, try text parsing
                }
            }

            // If not Excel date, try parsing as text
            if (!$commissionDate) {
                $commissionDateRaw = trim($commissionDateRaw);
                if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $commissionDateRaw, $matches)) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                    $year = $matches[3];
                    $commissionDate = "$year-$month-$day";
                } else {
                    $timestamp = strtotime($commissionDateRaw);
                    if ($timestamp) {
                        $commissionDate = date('Y-m-d', $timestamp);
                    }
                }
            }
        }

        // If commission date is empty or invalid, use payment date
        if (!$commissionDate && $paymentDate) {
            $commissionDate = $paymentDate;
        }

        // Find the book in database
        $bookQuery = "SELECT lb.book_id, bd.distribution_id
                      FROM lottery_books lb
                      LEFT JOIN book_distribution bd ON lb.book_id = bd.book_id
                      WHERE lb.event_id = :event_id AND lb.book_number = :book_number";
        $bookStmt = $db->prepare($bookQuery);
        $bookStmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
        $bookStmt->bindValue(':book_number', $bookNumber, PDO::PARAM_STR);
        $bookStmt->execute();
        $bookData = $bookStmt->fetch();

        if (!$bookData) {
            $errors[] = "Row $row: Book number '$bookNumber' not found in system";
            $errorCount++;
            continue;
        }

        if (!$bookData['distribution_id']) {
            $errors[] = "Row $row: Book '$bookNumber' has not been distributed yet";
            $errorCount++;
            continue;
        }

        $distributionId = $bookData['distribution_id'];

        // Update payment information if payment amount > 0 and valid date
        if ($paymentAmount > 0 && $paymentDate) {
            // Check if payment already exists for this distribution and date
            $checkPaymentQuery = "SELECT payment_id, amount_paid FROM payment_collections
                                 WHERE distribution_id = :dist_id
                                 AND DATE(payment_date) = :payment_date
                                 LIMIT 1";
            $checkStmt = $db->prepare($checkPaymentQuery);
            $checkStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
            $checkStmt->bindValue(':payment_date', $paymentDate, PDO::PARAM_STR);
            $checkStmt->execute();
            $existingPayment = $checkStmt->fetch();

            if ($existingPayment) {
                // Update existing payment if amount changed
                if ($existingPayment['amount_paid'] != $paymentAmount) {
                    $updatePaymentQuery = "UPDATE payment_collections
                                          SET amount_paid = :amount,
                                              payment_method = :method
                                          WHERE payment_id = :payment_id";
                    $updateStmt = $db->prepare($updatePaymentQuery);
                    $updateStmt->bindValue(':amount', $paymentAmount, PDO::PARAM_STR);
                    $updateStmt->bindValue(':method', $paymentMethod, PDO::PARAM_STR);
                    $updateStmt->bindValue(':payment_id', $existingPayment['payment_id'], PDO::PARAM_INT);
                    $updateStmt->execute();

                    $updates[] = "✅ Row $row (Book $bookNumber): Updated payment from ₹{$existingPayment['amount_paid']} to ₹$paymentAmount";
                }
            } else {
                // Insert new payment
                $insertPaymentQuery = "INSERT INTO payment_collections
                                      (distribution_id, amount_paid, payment_date, payment_method, collected_by)
                                      VALUES (:dist_id, :amount, :payment_date, :method, :collected_by)";
                $insertStmt = $db->prepare($insertPaymentQuery);
                $insertStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
                $insertStmt->bindValue(':amount', $paymentAmount, PDO::PARAM_STR);
                $insertStmt->bindValue(':payment_date', $paymentDate, PDO::PARAM_STR);
                $insertStmt->bindValue(':method', $paymentMethod, PDO::PARAM_STR);
                $insertStmt->bindValue(':collected_by', $_SESSION['user_id'], PDO::PARAM_INT);
                $insertStmt->execute();

                $updates[] = "✅ Row $row (Book $bookNumber): Added new payment of ₹$paymentAmount on $paymentDate";
            }

            // ===== COMMISSION CALCULATION (after payment INSERT/UPDATE) =====
            // Calculate commission on the ACTUAL payment amount (partial or full)
            $paymentCheckQuery = "SELECT
                                    lb.event_id,
                                    lb.book_id,
                                    le.price_per_ticket,
                                    le.tickets_per_book,
                                    bd.distribution_path,
                                    bd.is_extra_book,
                                    bd.distributed_at
                                  FROM lottery_books lb
                                  JOIN lottery_events le ON lb.event_id = le.event_id
                                  JOIN book_distribution bd ON lb.book_id = bd.book_id
                                  WHERE bd.distribution_id = :dist_id";
            $paymentStmt = $db->prepare($paymentCheckQuery);
            $paymentStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
            $paymentStmt->execute();
            $bookData = $paymentStmt->fetch();

            error_log("Commission Check - Row $row, Book $bookNumber: bookData found = " . ($bookData ? 'YES' : 'NO'));

            if ($bookData) {
                // Calculate commission on the payment amount (not the expected amount)
                if ($paymentAmount > 0) {
                    error_log("Commission Check - Payment amount: ₹$paymentAmount, Event ID: {$bookData['event_id']}");

                    $commissionQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id AND commission_enabled = 1";
                    $commStmt = $db->prepare($commissionQuery);
                    $commStmt->bindParam(':event_id', $bookData['event_id']);
                    $commStmt->execute();
                    $commSettings = $commStmt->fetch();

                    error_log("Commission Check - Settings found = " . ($commSettings ? 'YES' : 'NO') .
                              ($commSettings ? ", enabled = {$commSettings['commission_enabled']}" : ""));

                    if ($commSettings) {
                        // Collect all eligible commission types (can have multiple)
                        $eligibleCommissions = [];

                        error_log("Commission Check - Extra book: " . ($bookData['is_extra_book'] == 1 ? 'YES' : 'NO') .
                                 ", Extra enabled: " . ($commSettings['extra_books_commission_enabled'] == 1 ? 'YES' : 'NO'));

                        // Check if book is marked as extra book
                        if ($bookData['is_extra_book'] == 1 &&
                            $commSettings['extra_books_commission_enabled'] == 1) {
                            $eligibleCommissions[] = [
                                'type' => 'extra_books',
                                'percent' => $commSettings['extra_books_commission_percent']
                            ];
                            error_log("Commission Check - Added extra_books commission: {$commSettings['extra_books_commission_percent']}%");
                        }

                        error_log("Commission Check - Early enabled: " . ($commSettings['early_commission_enabled'] == 1 ? 'YES' : 'NO') .
                                 ", Early date: {$commSettings['early_payment_date']}, Commission date: $commissionDate");

                        // Check date-based commission (can be in addition to extra books)
                        // Uses commission date (not payment date) for commission eligibility
                        if ($commSettings['early_commission_enabled'] == 1 &&
                            !empty($commSettings['early_payment_date']) &&
                            $commissionDate <= $commSettings['early_payment_date']) {
                            $eligibleCommissions[] = [
                                'type' => 'early',
                                'percent' => $commSettings['early_commission_percent']
                            ];
                            error_log("Commission Check - Added early commission: {$commSettings['early_commission_percent']}%");
                        }
                        elseif ($commSettings['standard_commission_enabled'] == 1 &&
                                !empty($commSettings['standard_payment_date']) &&
                                $commissionDate <= $commSettings['standard_payment_date']) {
                            $eligibleCommissions[] = [
                                'type' => 'standard',
                                'percent' => $commSettings['standard_commission_percent']
                            ];
                            error_log("Commission Check - Added standard commission: {$commSettings['standard_commission_percent']}%");
                        }

                        // Extract Level 1 value from distribution_path
                        $level1Value = '';
                        if (!empty($bookData['distribution_path'])) {
                            $pathParts = explode(' > ', $bookData['distribution_path']);
                            $level1Value = $pathParts[0] ?? '';
                        }

                        error_log("Commission Check - Level 1 value: '$level1Value', Eligible commissions: " . count($eligibleCommissions));

                        // Save each eligible commission
                        if ($level1Value && count($eligibleCommissions) > 0) {
                            foreach ($eligibleCommissions as $commission) {
                                // Check if commission already exists for this distribution, type, and payment date
                                $checkCommQuery = "SELECT commission_id, commission_amount FROM commission_earned
                                                  WHERE distribution_id = :dist_id
                                                  AND commission_type = :comm_type
                                                  AND DATE(payment_date) = :payment_date
                                                  LIMIT 1";
                                $checkCommStmt = $db->prepare($checkCommQuery);
                                $checkCommStmt->bindParam(':dist_id', $distributionId);
                                $checkCommStmt->bindParam(':comm_type', $commission['type']);
                                $checkCommStmt->bindParam(':payment_date', $paymentDate);
                                $checkCommStmt->execute();
                                $existingComm = $checkCommStmt->fetch();

                                // Calculate commission on ACTUAL payment amount (partial or full)
                                $commissionAmount = ($paymentAmount * $commission['percent']) / 100;

                                if ($existingComm) {
                                    // Update existing commission if amount changed
                                    if ($existingComm['commission_amount'] != $commissionAmount) {
                                        $updateCommQuery = "UPDATE commission_earned
                                                           SET commission_amount = :comm_amt,
                                                               payment_amount = :payment_amt,
                                                               commission_percent = :comm_percent
                                                           WHERE commission_id = :comm_id";
                                        $updateCommStmt = $db->prepare($updateCommQuery);
                                        $updateCommStmt->bindParam(':comm_amt', $commissionAmount);
                                        $updateCommStmt->bindParam(':payment_amt', $paymentAmount);
                                        $updateCommStmt->bindParam(':comm_percent', $commission['percent']);
                                        $updateCommStmt->bindParam(':comm_id', $existingComm['commission_id']);
                                        $updateCommStmt->execute();

                                        $updates[] = "💰 Row $row (Book $bookNumber): Commission updated - {$commission['type']} ({$commission['percent']}%) = ₹$commissionAmount";
                                    }
                                } else {
                                    // Insert new commission
                                    $insertCommQuery = "INSERT INTO commission_earned
                                                       (event_id, distribution_id, level_1_value, commission_type, commission_percent,
                                                        payment_amount, commission_amount, payment_date, book_id)
                                                       VALUES (:event_id, :dist_id, :level_1, :comm_type, :comm_percent,
                                                               :payment_amt, :comm_amt, :payment_date, :book_id)";
                                    $insertCommStmt = $db->prepare($insertCommQuery);
                                    $insertCommStmt->bindParam(':event_id', $bookData['event_id']);
                                    $insertCommStmt->bindParam(':dist_id', $distributionId);
                                    $insertCommStmt->bindParam(':level_1', $level1Value);
                                    $insertCommStmt->bindParam(':comm_type', $commission['type']);
                                    $insertCommStmt->bindParam(':comm_percent', $commission['percent']);
                                    $insertCommStmt->bindParam(':payment_amt', $paymentAmount);
                                    $insertCommStmt->bindParam(':comm_amt', $commissionAmount);
                                    $insertCommStmt->bindParam(':payment_date', $paymentDate);
                                    $insertCommStmt->bindParam(':book_id', $bookData['book_id']);
                                    $insertCommStmt->execute();

                                    $updates[] = "💰 Row $row (Book $bookNumber): Commission earned - {$commission['type']} ({$commission['percent']}%) = ₹$commissionAmount";
                                }
                            }
                        }
                    }
                }
            }
        }

        // Update return status
        $isReturned = (stripos($returnStatus, 'returned') !== false && stripos($returnStatus, 'not') === false) ? 1 : 0;
        $updateReturnQuery = "UPDATE book_distribution SET is_returned = :is_returned WHERE distribution_id = :dist_id";
        $returnStmt = $db->prepare($updateReturnQuery);
        $returnStmt->bindValue(':is_returned', $isReturned, PDO::PARAM_INT);
        $returnStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
        $returnStmt->execute();

        $successCount++;
    }

    // ===== PROCESS MULTIPLE PAYMENTS SHEET (if exists) =====
    $multiPaymentSheet = null;
    foreach ($spreadsheet->getAllSheets() as $sheet) {
        if ($sheet->getTitle() === 'Multiple Payments') {
            $multiPaymentSheet = $sheet;
            break;
        }
    }

    if ($multiPaymentSheet) {
        $updates[] = "📋 Processing Multiple Payments sheet...";

        $multiHighestRow = $multiPaymentSheet->getHighestRow();

        // Find header row in Multiple Payments sheet
        $multiHeaderRow = 0;
        for ($row = 1; $row <= 10; $row++) {
            $cellValue = $multiPaymentSheet->getCellByColumnAndRow(1, $row)->getValue();
            $cellValue = $cellValue ?? ''; // Handle null values
            if (stripos($cellValue, 'Book Number') !== false) {
                $multiHeaderRow = $row;
                break;
            }
        }

        if ($multiHeaderRow > 0) {
            $multiPaymentCount = 0;

            for ($row = $multiHeaderRow + 1; $row <= $multiHighestRow; $row++) {
                // Columns: Book Number (A/1), Payment Amount (B/2), Payment Date (C/3), Commission Date (D/4), Payment Method (E/5), Notes (F/6)
                $bookNumber = trim($multiPaymentSheet->getCellByColumnAndRow(1, $row)->getValue() ?? '');
                $paymentAmount = trim($multiPaymentSheet->getCellByColumnAndRow(2, $row)->getValue() ?? '');
                $paymentDateRaw = trim($multiPaymentSheet->getCellByColumnAndRow(3, $row)->getValue() ?? '');
                $commissionDateRaw = trim($multiPaymentSheet->getCellByColumnAndRow(4, $row)->getValue() ?? '');
                $paymentMethod = strtolower(trim($multiPaymentSheet->getCellByColumnAndRow(5, $row)->getValue() ?? ''));
                $notes = trim($multiPaymentSheet->getCellByColumnAndRow(6, $row)->getValue() ?? '');

                // Skip empty rows
                if (empty($bookNumber) || empty($paymentAmount)) {
                    continue;
                }

                // Parse payment amount
                $paymentAmount = (float) preg_replace('/[^\d.]/', '', $paymentAmount);
                if ($paymentAmount <= 0) {
                    continue;
                }

                // Parse payment date (same logic as main sheet)
                $paymentDate = null;
                if (!empty($paymentDateRaw)) {
                    if (is_numeric($paymentDateRaw) && $paymentDateRaw > 25569) {
                        try {
                            $dateTime = Date::excelToDateTimeObject($paymentDateRaw);
                            $paymentDate = $dateTime->format('Y-m-d');
                        } catch (Exception $e) {
                            // Try text parsing
                        }
                    }

                    if (!$paymentDate) {
                        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $paymentDateRaw, $matches)) {
                            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                            $year = $matches[3];
                            $paymentDate = "$year-$month-$day";
                        } else {
                            $timestamp = strtotime($paymentDateRaw);
                            if ($timestamp) {
                                $paymentDate = date('Y-m-d', $timestamp);
                            }
                        }
                    }
                }

                if (!$paymentDate) {
                    $errors[] = "Multiple Payments Row $row: Invalid payment date '$paymentDateRaw'";
                    $errorCount++;
                    continue;
                }

                // Parse commission date (same logic as payment date)
                $commissionDate = null;
                if (!empty($commissionDateRaw)) {
                    if (is_numeric($commissionDateRaw) && $commissionDateRaw > 25569) {
                        try {
                            $dateTime = Date::excelToDateTimeObject($commissionDateRaw);
                            $commissionDate = $dateTime->format('Y-m-d');
                        } catch (Exception $e) {
                            // Try text parsing
                        }
                    }

                    if (!$commissionDate) {
                        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $commissionDateRaw, $matches)) {
                            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                            $year = $matches[3];
                            $commissionDate = "$year-$month-$day";
                        } else {
                            $timestamp = strtotime($commissionDateRaw);
                            if ($timestamp) {
                                $commissionDate = date('Y-m-d', $timestamp);
                            }
                        }
                    }
                }

                // If commission date is empty or invalid, use payment date
                if (!$commissionDate) {
                    $commissionDate = $paymentDate;
                }

                // Find book in database
                $bookQuery = "SELECT lb.book_id, bd.distribution_id
                              FROM lottery_books lb
                              LEFT JOIN book_distribution bd ON lb.book_id = bd.book_id
                              WHERE lb.event_id = :event_id AND lb.book_number = :book_number";
                $bookStmt = $db->prepare($bookQuery);
                $bookStmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
                $bookStmt->bindValue(':book_number', $bookNumber, PDO::PARAM_STR);
                $bookStmt->execute();
                $bookData = $bookStmt->fetch();

                if (!$bookData) {
                    $errors[] = "Multiple Payments Row $row: Book '$bookNumber' not found";
                    $errorCount++;
                    continue;
                }

                if (!$bookData['distribution_id']) {
                    $errors[] = "Multiple Payments Row $row: Book '$bookNumber' not distributed";
                    $errorCount++;
                    continue;
                }

                $distributionId = $bookData['distribution_id'];

                // Check if payment exists
                $checkPaymentQuery = "SELECT payment_id, amount_paid FROM payment_collections
                                     WHERE distribution_id = :dist_id
                                     AND DATE(payment_date) = :payment_date
                                     LIMIT 1";
                $checkStmt = $db->prepare($checkPaymentQuery);
                $checkStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
                $checkStmt->bindValue(':payment_date', $paymentDate, PDO::PARAM_STR);
                $checkStmt->execute();
                $existingPayment = $checkStmt->fetch();

                if ($existingPayment) {
                    // Update existing
                    if ($existingPayment['amount_paid'] != $paymentAmount) {
                        $updatePaymentQuery = "UPDATE payment_collections
                                              SET amount_paid = :amount,
                                                  payment_method = :method
                                              WHERE payment_id = :payment_id";
                        $updateStmt = $db->prepare($updatePaymentQuery);
                        $updateStmt->bindValue(':amount', $paymentAmount, PDO::PARAM_STR);
                        $updateStmt->bindValue(':method', $paymentMethod, PDO::PARAM_STR);
                        $updateStmt->bindValue(':payment_id', $existingPayment['payment_id'], PDO::PARAM_INT);
                        $updateStmt->execute();

                        $updates[] = "💰 Row $row (Book $bookNumber): Updated payment from ₹{$existingPayment['amount_paid']} to ₹$paymentAmount" . (!empty($notes) ? " ($notes)" : "");
                    }
                } else {
                    // Insert new payment
                    $insertPaymentQuery = "INSERT INTO payment_collections
                                          (distribution_id, amount_paid, payment_date, payment_method, collected_by)
                                          VALUES (:dist_id, :amount, :payment_date, :method, :collected_by)";
                    $insertStmt = $db->prepare($insertPaymentQuery);
                    $insertStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
                    $insertStmt->bindValue(':amount', $paymentAmount, PDO::PARAM_STR);
                    $insertStmt->bindValue(':payment_date', $paymentDate, PDO::PARAM_STR);
                    $insertStmt->bindValue(':method', $paymentMethod, PDO::PARAM_STR);
                    $insertStmt->bindValue(':collected_by', $_SESSION['user_id'], PDO::PARAM_INT);
                    $insertStmt->execute();

                    $updates[] = "💰 Row $row (Book $bookNumber): Added payment of ₹$paymentAmount on $paymentDate" . (!empty($notes) ? " ($notes)" : "");
                }

                // ===== COMMISSION CALCULATION (for Multiple Payments sheet) =====
                // Calculate commission on the ACTUAL payment amount (partial or full)
                $paymentCheckQuery = "SELECT
                                        lb.event_id,
                                        lb.book_id,
                                        le.price_per_ticket,
                                        le.tickets_per_book,
                                        bd.distribution_path,
                                        bd.is_extra_book,
                                        bd.distributed_at
                                      FROM lottery_books lb
                                      JOIN lottery_events le ON lb.event_id = le.event_id
                                      JOIN book_distribution bd ON lb.book_id = bd.book_id
                                      WHERE bd.distribution_id = :dist_id";
                $paymentStmt = $db->prepare($paymentCheckQuery);
                $paymentStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
                $paymentStmt->execute();
                $bookData = $paymentStmt->fetch();

                if ($bookData) {
                    // Calculate commission on the payment amount (not the expected amount)
                    if ($paymentAmount > 0) {
                        $commissionQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id AND commission_enabled = 1";
                        $commStmt = $db->prepare($commissionQuery);
                        $commStmt->bindParam(':event_id', $bookData['event_id']);
                        $commStmt->execute();
                        $commSettings = $commStmt->fetch();

                        if ($commSettings) {
                            // Collect all eligible commission types (can have multiple)
                            $eligibleCommissions = [];

                            // Check if book is marked as extra book
                            if ($bookData['is_extra_book'] == 1 &&
                                $commSettings['extra_books_commission_enabled'] == 1) {
                                $eligibleCommissions[] = [
                                    'type' => 'extra_books',
                                    'percent' => $commSettings['extra_books_commission_percent']
                                ];
                            }

                            // Check date-based commission (can be in addition to extra books)
                            // Uses commission date (not payment date) for commission eligibility
                            if ($commSettings['early_commission_enabled'] == 1 &&
                                !empty($commSettings['early_payment_date']) &&
                                $commissionDate <= $commSettings['early_payment_date']) {
                                $eligibleCommissions[] = [
                                    'type' => 'early',
                                    'percent' => $commSettings['early_commission_percent']
                                ];
                            }
                            elseif ($commSettings['standard_commission_enabled'] == 1 &&
                                    !empty($commSettings['standard_payment_date']) &&
                                    $commissionDate <= $commSettings['standard_payment_date']) {
                                $eligibleCommissions[] = [
                                    'type' => 'standard',
                                    'percent' => $commSettings['standard_commission_percent']
                                ];
                            }

                            // Extract Level 1 value from distribution_path
                            $level1Value = '';
                            if (!empty($bookData['distribution_path'])) {
                                $pathParts = explode(' > ', $bookData['distribution_path']);
                                $level1Value = $pathParts[0] ?? '';
                            }

                            // Save each eligible commission
                            if ($level1Value && count($eligibleCommissions) > 0) {
                                foreach ($eligibleCommissions as $commission) {
                                    // Check if commission already exists for this distribution, type, and payment date
                                    $checkCommQuery = "SELECT commission_id, commission_amount FROM commission_earned
                                                      WHERE distribution_id = :dist_id
                                                      AND commission_type = :comm_type
                                                      AND DATE(payment_date) = :payment_date
                                                      LIMIT 1";
                                    $checkCommStmt = $db->prepare($checkCommQuery);
                                    $checkCommStmt->bindParam(':dist_id', $distributionId);
                                    $checkCommStmt->bindParam(':comm_type', $commission['type']);
                                    $checkCommStmt->bindParam(':payment_date', $paymentDate);
                                    $checkCommStmt->execute();
                                    $existingComm = $checkCommStmt->fetch();

                                    // Calculate commission on ACTUAL payment amount (partial or full)
                                    $commissionAmount = ($paymentAmount * $commission['percent']) / 100;

                                    if ($existingComm) {
                                        // Update existing commission if amount changed
                                        if ($existingComm['commission_amount'] != $commissionAmount) {
                                            $updateCommQuery = "UPDATE commission_earned
                                                               SET commission_amount = :comm_amt,
                                                                   payment_amount = :payment_amt,
                                                                   commission_percent = :comm_percent
                                                               WHERE commission_id = :comm_id";
                                            $updateCommStmt = $db->prepare($updateCommQuery);
                                            $updateCommStmt->bindParam(':comm_amt', $commissionAmount);
                                            $updateCommStmt->bindParam(':payment_amt', $paymentAmount);
                                            $updateCommStmt->bindParam(':comm_percent', $commission['percent']);
                                            $updateCommStmt->bindParam(':comm_id', $existingComm['commission_id']);
                                            $updateCommStmt->execute();

                                            $updates[] = "💰 Row $row (Book $bookNumber): Commission updated - {$commission['type']} ({$commission['percent']}%) = ₹$commissionAmount";
                                        }
                                    } else {
                                        // Insert new commission
                                        $insertCommQuery = "INSERT INTO commission_earned
                                                           (event_id, distribution_id, level_1_value, commission_type, commission_percent,
                                                            payment_amount, commission_amount, payment_date, book_id)
                                                           VALUES (:event_id, :dist_id, :level_1, :comm_type, :comm_percent,
                                                                   :payment_amt, :comm_amt, :payment_date, :book_id)";
                                        $insertCommStmt = $db->prepare($insertCommQuery);
                                        $insertCommStmt->bindParam(':event_id', $bookData['event_id']);
                                        $insertCommStmt->bindParam(':dist_id', $distributionId);
                                        $insertCommStmt->bindParam(':level_1', $level1Value);
                                        $insertCommStmt->bindParam(':comm_type', $commission['type']);
                                        $insertCommStmt->bindParam(':comm_percent', $commission['percent']);
                                        $insertCommStmt->bindParam(':payment_amt', $paymentAmount);
                                        $insertCommStmt->bindParam(':comm_amt', $commissionAmount);
                                        $insertCommStmt->bindParam(':payment_date', $paymentDate);
                                        $insertCommStmt->bindParam(':book_id', $bookData['book_id']);
                                        $insertCommStmt->execute();

                                        $updates[] = "💰 Row $row (Book $bookNumber): Commission earned - {$commission['type']} ({$commission['percent']}%) = ₹$commissionAmount";
                                    }
                                }
                            }
                        }
                    }
                }

                $multiPaymentCount++;
            }

            if ($multiPaymentCount > 0) {
                $updates[] = "✅ Processed $multiPaymentCount additional payments from Multiple Payments sheet";
            }
        }
    }

    // Commit transaction
    $db->commit();

    // Log the activity (optional - fail silently if logger not available)
    try {
        if (class_exists('SystemLogger')) {
            $logger = new SystemLogger();
            $logger->log(
                'lottery_upload',
                'medium',
                "Uploaded Excel report - Event: {$event['event_name']}, Success: $successCount, Errors: $errorCount"
            );
        }
    } catch (Exception $logError) {
        // Logging failed, but continue with success message
        error_log("SystemLogger error: " . $logError->getMessage());
    }

    $_SESSION['success'] = "Excel upload completed! Successfully processed $successCount records." .
                          ($errorCount > 0 ? " $errorCount errors occurred." : "");

    if (!empty($errors)) {
        $_SESSION['upload_errors'] = $errors;
    }
    if (!empty($updates)) {
        $_SESSION['upload_updates'] = $updates;
    }

    // Debug log
    error_log("Excel Upload: Success! Processed $successCount records, $errorCount errors, " . count($updates) . " updates, " . count($errors) . " error messages");

} catch (Exception $e) {
    // Only rollback if there's an active transaction
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = "Error processing Excel file: " . $e->getMessage();

    // Debug log
    error_log("Excel Upload: ERROR - " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

    // Log the error (optional - fail silently if logger not available)
    try {
        if (class_exists('SystemLogger')) {
            $logger = new SystemLogger();
            $logger->log(
                'lottery_upload_error',
                'high',
                "Excel upload failed - Event: {$event['event_name']}, Error: " . $e->getMessage()
            );
        }
    } catch (Exception $logError) {
        // Logging failed, but error message is already set
        error_log("SystemLogger error: " . $logError->getMessage());
    }
}

header("Location: /public/group-admin/lottery/lottery-reports.php?id=" . $eventId);
exit;
?>
