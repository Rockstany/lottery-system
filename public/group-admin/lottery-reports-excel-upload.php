<?php
/**
 * Excel Upload Processor for Level-Wise Report (XLSX Format)
 * Processes uploaded Excel files and updates the database using PHPSpreadsheet
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_POST['event_id'] ?? 0);

if (!$eventId) {
    $_SESSION['error'] = "Invalid event ID";
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
    $_SESSION['error'] = "Event not found";
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = "No file uploaded or upload error occurred";
    header("Location: /public/group-admin/lottery-reports.php?id=" . $eventId);
    exit;
}

$file = $_FILES['excel_file'];
$allowedExtensions = ['xls', 'xlsx'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    $_SESSION['error'] = "Invalid file format. Please upload .xls or .xlsx file";
    header("Location: /public/group-admin/lottery-reports.php?id=" . $eventId);
    exit;
}

try {
    // Load the Excel file
    $spreadsheet = IOFactory::load($file['tmp_name']);

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
    // Then: Member Name, Mobile, Book Number, Payment Amount, Payment Date, Payment Status, Payment Method, Return Status
    $srNoCol = 0;
    $firstLevelCol = 1;
    $memberNameCol = $levelCount + 1;
    $mobileCol = $levelCount + 2;
    $bookNumberCol = $levelCount + 3;
    $paymentAmountCol = $levelCount + 4;
    $paymentDateCol = $levelCount + 5;
    $paymentStatusCol = $levelCount + 6;
    $paymentMethodCol = $levelCount + 7;
    $returnStatusCol = $levelCount + 8;

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
                                      (distribution_id, amount_paid, payment_date, payment_method)
                                      VALUES (:dist_id, :amount, :payment_date, :method)";
                $insertStmt = $db->prepare($insertPaymentQuery);
                $insertStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
                $insertStmt->bindValue(':amount', $paymentAmount, PDO::PARAM_STR);
                $insertStmt->bindValue(':payment_date', $paymentDate, PDO::PARAM_STR);
                $insertStmt->bindValue(':method', $paymentMethod, PDO::PARAM_STR);
                $insertStmt->execute();

                $updates[] = "✅ Row $row (Book $bookNumber): Added new payment of ₹$paymentAmount on $paymentDate";
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

} catch (Exception $e) {
    // Only rollback if there's an active transaction
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = "Error processing Excel file: " . $e->getMessage();

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

header("Location: /public/group-admin/lottery-reports.php?id=" . $eventId);
exit;
?>
