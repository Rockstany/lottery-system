<?php
/**
 * Excel Upload Processor for Level-Wise Report
 * Processes uploaded Excel files and updates the database
 */

require_once __DIR__ . '/../../config/config.php';
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

// Install PhpSpreadsheet if not available (check for composer autoload)
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

try {
    // Load the uploaded file
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();

    // Get distribution levels for this event
    $levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
    $stmt = $db->prepare($levelsQuery);
    $stmt->bindParam(':event_id', $eventId);
    $stmt->execute();
    $levels = $stmt->fetchAll();
    $levelCount = count($levels);

    // Expected column positions (after level columns)
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

    // Start from row 2 (skip header)
    $rowNumber = 2;
    $maxRows = $sheet->getHighestRow();

    // Begin transaction
    $db->beginTransaction();

    while ($rowNumber <= $maxRows) {
        $row = $sheet->rangeToArray('A' . $rowNumber . ':Z' . $rowNumber, NULL, TRUE, FALSE)[0];

        // Skip empty rows
        if (empty(array_filter($row))) {
            $rowNumber++;
            continue;
        }

        // Get book number
        $bookNumber = trim($row[$bookNumberCol] ?? '');

        if (empty($bookNumber)) {
            $rowNumber++;
            continue;
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
            $errors[] = "Row $rowNumber: Book number '$bookNumber' not found";
            $errorCount++;
            $rowNumber++;
            continue;
        }

        $bookId = $bookData['book_id'];
        $distributionId = $bookData['distribution_id'];

        // Extract data from row
        $levelPath = [];
        for ($i = 0; $i < $levelCount; $i++) {
            $levelValue = trim($row[$i] ?? '');
            if (!empty($levelValue)) {
                $levelPath[] = $levelValue;
            }
        }
        $distributionPath = implode(' > ', $levelPath);

        $memberName = trim($row[$memberNameCol] ?? '');
        $mobile = trim($row[$mobileCol] ?? '');

        // Clean mobile number (remove spaces, dashes, +91, etc.)
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10); // Take last 10 digits
        }

        $paymentAmount = floatval($row[$paymentAmountCol] ?? 0);
        $paymentDateStr = trim($row[$paymentDateCol] ?? '');
        $paymentStatus = trim($row[$paymentStatusCol] ?? '');
        $paymentMethod = strtolower(trim($row[$paymentMethodCol] ?? ''));
        $returnStatus = trim($row[$returnStatusCol] ?? '');

        // Parse payment date
        $paymentDate = null;
        if (!empty($paymentDateStr)) {
            // Try to parse date in DD-MM-YYYY format
            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $paymentDateStr, $matches)) {
                $paymentDate = $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            } else {
                // Try Excel date format
                if (is_numeric($paymentDateStr)) {
                    $paymentDate = Date::excelToDateTimeObject($paymentDateStr)->format('Y-m-d');
                }
            }
        }

        // Validate payment method
        $validMethods = ['cash', 'upi', 'bank transfer', 'cheque'];
        if (!empty($paymentMethod) && !in_array($paymentMethod, $validMethods)) {
            $paymentMethod = 'cash'; // Default to cash if invalid
        }

        // Determine if book is returned
        $isReturned = (strtolower($returnStatus) === 'returned') ? 1 : 0;

        // Update or insert distribution
        if ($distributionId) {
            // Update existing distribution
            $updateDistQuery = "UPDATE book_distribution
                               SET notes = :notes,
                                   mobile_number = :mobile,
                                   distribution_path = :dist_path,
                                   is_returned = :is_returned
                               WHERE distribution_id = :dist_id";
            $updateStmt = $db->prepare($updateDistQuery);
            $updateStmt->bindValue(':notes', $memberName, PDO::PARAM_STR);
            $updateStmt->bindValue(':mobile', $mobile, PDO::PARAM_STR);
            $updateStmt->bindValue(':dist_path', $distributionPath, PDO::PARAM_STR);
            $updateStmt->bindValue(':is_returned', $isReturned, PDO::PARAM_INT);
            $updateStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
            $updateStmt->execute();

        } else {
            // Create new distribution
            $insertDistQuery = "INSERT INTO book_distribution
                               (book_id, notes, mobile_number, distribution_path, is_returned, distributed_at)
                               VALUES (:book_id, :notes, :mobile, :dist_path, :is_returned, NOW())";
            $insertStmt = $db->prepare($insertDistQuery);
            $insertStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
            $insertStmt->bindValue(':notes', $memberName, PDO::PARAM_STR);
            $insertStmt->bindValue(':mobile', $mobile, PDO::PARAM_STR);
            $insertStmt->bindValue(':dist_path', $distributionPath, PDO::PARAM_STR);
            $insertStmt->bindValue(':is_returned', $isReturned, PDO::PARAM_INT);
            $insertStmt->execute();

            $distributionId = $db->lastInsertId();

            // Update book status
            $updateBookQuery = "UPDATE lottery_books SET book_status = 'distributed' WHERE book_id = :book_id";
            $updateBookStmt = $db->prepare($updateBookQuery);
            $updateBookStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
            $updateBookStmt->execute();
        }

        // Handle payment if amount > 0
        if ($paymentAmount > 0 && $paymentDate && !empty($paymentMethod)) {
            // Check if payment already exists for this distribution and date (ignore amount to allow updates)
            $checkPaymentQuery = "SELECT payment_id, amount_paid FROM payment_collections
                                 WHERE distribution_id = :dist_id
                                 AND DATE(payment_date) = :payment_date
                                 LIMIT 1";
            $checkPaymentStmt = $db->prepare($checkPaymentQuery);
            $checkPaymentStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
            $checkPaymentStmt->bindValue(':payment_date', $paymentDate, PDO::PARAM_STR);
            $checkPaymentStmt->execute();
            $existingPayment = $checkPaymentStmt->fetch();

            if ($existingPayment) {
                // UPDATE existing payment if amount has changed
                if ($existingPayment['amount_paid'] != $paymentAmount) {
                    $updatePaymentQuery = "UPDATE payment_collections
                                          SET amount_paid = :amount,
                                              payment_method = :method,
                                              notes = 'Updated from Excel'
                                          WHERE payment_id = :payment_id";
                    $updatePaymentStmt = $db->prepare($updatePaymentQuery);
                    $updatePaymentStmt->bindValue(':amount', $paymentAmount, PDO::PARAM_STR);
                    $updatePaymentStmt->bindValue(':method', $paymentMethod, PDO::PARAM_STR);
                    $updatePaymentStmt->bindValue(':payment_id', $existingPayment['payment_id'], PDO::PARAM_INT);
                    $updatePaymentStmt->execute();
                }
                // If amount is same, no need to update (prevents unnecessary writes)
            } else {
                // Insert new payment (first time or different date)
                $insertPaymentQuery = "INSERT INTO payment_collections
                                      (distribution_id, amount_paid, payment_date, payment_method, notes)
                                      VALUES (:dist_id, :amount, :payment_date, :method, 'Imported from Excel')";
                $insertPaymentStmt = $db->prepare($insertPaymentQuery);
                $insertPaymentStmt->bindValue(':dist_id', $distributionId, PDO::PARAM_INT);
                $insertPaymentStmt->bindValue(':amount', $paymentAmount, PDO::PARAM_STR);
                $insertPaymentStmt->bindValue(':payment_date', $paymentDate, PDO::PARAM_STR);
                $insertPaymentStmt->bindValue(':method', $paymentMethod, PDO::PARAM_STR);
                $insertPaymentStmt->execute();
            }
        }

        $updates[] = "Row $rowNumber: Book '$bookNumber' - $memberName - Updated successfully";
        $successCount++;
        $rowNumber++;
    }

    // Commit transaction
    $db->commit();

    // Log the activity
    SystemLogger::log(
        'lottery_upload',
        'Uploaded Excel report',
        "Event: {$event['event_name']}, Success: $successCount, Errors: $errorCount"
    );

    $_SESSION['success'] = "Excel upload completed! Successfully updated $successCount records." .
                          ($errorCount > 0 ? " $errorCount errors occurred." : "");

    if (!empty($errors)) {
        $_SESSION['upload_errors'] = $errors;
    }
    if (!empty($updates)) {
        $_SESSION['upload_updates'] = $updates;
    }

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = "Error processing Excel file: " . $e->getMessage();

    SystemLogger::log(
        'lottery_upload_error',
        'Excel upload failed',
        "Event: {$event['event_name']}, Error: " . $e->getMessage()
    );
}

header("Location: /public/group-admin/lottery-reports.php?id=" . $eventId);
exit;
?>
