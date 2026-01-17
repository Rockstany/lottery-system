<?php
/**
 * CSF Bulk Import Payments - Import historical/past payments in bulk
 * Allows importing multiple payment records at once via CSV paste
 * Optimized for 50+ age group users
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

// Authentication
AuthMiddleware::requireRole('group_admin');
$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

// Feature access check
$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'csf_funds')) {
    $_SESSION['error_message'] = "CSF Funds is not enabled for your community";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get all members for validation
$membersQuery = "SELECT u.user_id, u.full_name, u.mobile_number, sc.name as sub_community_name
                 FROM sub_community_members scm
                 JOIN users u ON scm.user_id = u.user_id
                 JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                 WHERE sc.community_id = ? AND scm.status = 'active'
                 ORDER BY u.full_name";
$membersStmt = $db->prepare($membersQuery);
$membersStmt->execute([$communityId]);
$allMembers = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

// Create lookup arrays for quick validation
$membersByMobile = [];
$membersByName = [];
foreach ($allMembers as $member) {
    $membersByMobile[$member['mobile_number']] = $member;
    $membersByName[strtolower(trim($member['full_name']))] = $member;
}

// Handle form submission
$importResults = null;
$successCount = 0;
$errorCount = 0;
$errors = [];
$successRecords = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'preview_import') {
        // Preview mode - validate and show what will be imported
        $csvData = trim($_POST['csv_data'] ?? '');
        $defaultPaymentMethod = $_POST['default_payment_method'] ?? 'cash';

        if (empty($csvData)) {
            $errors[] = ['row' => 0, 'message' => 'No data provided. Please paste CSV data.'];
        } else {
            $lines = preg_split('/\r\n|\r|\n/', $csvData);
            $previewRecords = [];
            $rowNum = 0;

            foreach ($lines as $line) {
                $rowNum++;
                $line = trim($line);
                if (empty($line)) continue;

                // Parse CSV line (supports comma, semicolon, tab)
                $parts = preg_split('/[,;\t]/', $line);
                $parts = array_map('trim', $parts);

                // Expected format: Mobile/Name, Amount, Month (YYYY-MM), Payment Date (optional), Payment Method (optional)
                if (count($parts) < 3) {
                    $errors[] = ['row' => $rowNum, 'data' => $line, 'message' => 'Invalid format. Expected: Mobile/Name, Amount, Month (YYYY-MM)'];
                    continue;
                }

                $identifier = $parts[0]; // Mobile or Name
                $amount = floatval($parts[1]);
                $paymentMonth = $parts[2]; // YYYY-MM format
                $paymentDate = isset($parts[3]) && !empty($parts[3]) ? $parts[3] : date('Y-m-d');
                $paymentMethod = isset($parts[4]) && !empty($parts[4]) ? strtolower($parts[4]) : $defaultPaymentMethod;

                // Validate member
                $member = null;
                if (preg_match('/^[0-9]{10}$/', $identifier)) {
                    // It's a mobile number
                    $member = $membersByMobile[$identifier] ?? null;
                } else {
                    // It's a name
                    $member = $membersByName[strtolower($identifier)] ?? null;
                }

                if (!$member) {
                    $errors[] = ['row' => $rowNum, 'data' => $line, 'message' => "Member not found: $identifier"];
                    continue;
                }

                // Validate amount
                if ($amount <= 0) {
                    $errors[] = ['row' => $rowNum, 'data' => $line, 'message' => "Invalid amount: {$parts[1]}"];
                    continue;
                }

                // Validate month format (YYYY-MM)
                if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/', $paymentMonth)) {
                    $errors[] = ['row' => $rowNum, 'data' => $line, 'message' => "Invalid month format: $paymentMonth. Use YYYY-MM (e.g., 2025-06)"];
                    continue;
                }

                // Validate payment date
                $dateObj = DateTime::createFromFormat('Y-m-d', $paymentDate);
                if (!$dateObj) {
                    $dateObj = DateTime::createFromFormat('d-m-Y', $paymentDate);
                    if ($dateObj) {
                        $paymentDate = $dateObj->format('Y-m-d');
                    }
                }
                if (!$dateObj) {
                    $errors[] = ['row' => $rowNum, 'data' => $line, 'message' => "Invalid date format: {$parts[3]}. Use YYYY-MM-DD or DD-MM-YYYY"];
                    continue;
                }

                // Validate payment method
                $validMethods = ['cash', 'upi', 'bank_transfer', 'cheque'];
                if (!in_array($paymentMethod, $validMethods)) {
                    $paymentMethod = $defaultPaymentMethod;
                }

                // Check for existing payment
                $checkStmt = $db->prepare("SELECT payment_id FROM csf_payments
                                          WHERE community_id = ? AND user_id = ?
                                          AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
                $checkStmt->execute([$communityId, $member['user_id'], $paymentMonth]);
                $existingPayment = $checkStmt->fetch();

                $record = [
                    'row' => $rowNum,
                    'member' => $member,
                    'amount' => $amount,
                    'payment_month' => $paymentMonth,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'is_duplicate' => $existingPayment ? true : false,
                    'existing_payment_id' => $existingPayment ? $existingPayment['payment_id'] : null
                ];

                $previewRecords[] = $record;
            }

            // Store in session for actual import
            $_SESSION['bulk_import_preview'] = $previewRecords;
            $_SESSION['bulk_import_errors'] = $errors;
            $importResults = ['preview' => true, 'records' => $previewRecords, 'errors' => $errors];
        }

    } elseif ($_POST['action'] === 'confirm_import') {
        // Actual import from session data
        $previewRecords = $_SESSION['bulk_import_preview'] ?? [];
        $skipDuplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] === '1';

        if (empty($previewRecords)) {
            $errors[] = ['row' => 0, 'message' => 'No preview data found. Please start over.'];
        } else {
            $db->beginTransaction();

            try {
                $insertStmt = $db->prepare("INSERT INTO csf_payments
                    (community_id, sub_community_id, user_id, amount, payment_date, payment_method, collected_by, payment_for_months, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                // Get sub_community_id for each member
                $subCommunityStmt = $db->prepare("SELECT sub_community_id FROM sub_community_members WHERE user_id = ? AND status = 'active' LIMIT 1");

                foreach ($previewRecords as $record) {
                    // Skip duplicates if option selected
                    if ($record['is_duplicate'] && $skipDuplicates) {
                        continue;
                    }

                    // Skip duplicates (they will cause DB error anyway)
                    if ($record['is_duplicate']) {
                        $errors[] = [
                            'row' => $record['row'],
                            'data' => $record['member']['full_name'] . ' - ' . $record['payment_month'],
                            'message' => 'Duplicate payment exists for this month'
                        ];
                        $errorCount++;
                        continue;
                    }

                    // Get sub_community_id
                    $subCommunityStmt->execute([$record['member']['user_id']]);
                    $subCommunity = $subCommunityStmt->fetch();
                    $subCommunityId = $subCommunity ? $subCommunity['sub_community_id'] : null;

                    $paymentForMonths = json_encode([$record['payment_month']]);
                    $notes = 'Bulk imported on ' . date('Y-m-d H:i:s');

                    try {
                        $insertStmt->execute([
                            $communityId,
                            $subCommunityId,
                            $record['member']['user_id'],
                            $record['amount'],
                            $record['payment_date'],
                            $record['payment_method'],
                            $userId,
                            $paymentForMonths,
                            $notes
                        ]);

                        $successRecords[] = $record;
                        $successCount++;
                    } catch (PDOException $e) {
                        $errors[] = [
                            'row' => $record['row'],
                            'data' => $record['member']['full_name'] . ' - ' . $record['payment_month'],
                            'message' => 'Database error: ' . $e->getMessage()
                        ];
                        $errorCount++;
                    }
                }

                $db->commit();

                // Clear session data
                unset($_SESSION['bulk_import_preview']);
                unset($_SESSION['bulk_import_errors']);

                // If there were errors, redirect to error page
                if ($errorCount > 0) {
                    $_SESSION['import_results'] = [
                        'success_count' => $successCount,
                        'error_count' => $errorCount,
                        'errors' => $errors,
                        'success_records' => $successRecords
                    ];
                    header('Location: /public/group-admin/csf-payment-error.php');
                    exit();
                }

                // All successful
                $_SESSION['success_message'] = "Successfully imported $successCount payment records!";
                header('Location: /public/group-admin/csf-payment-history.php');
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = ['row' => 0, 'message' => 'Import failed: ' . $e->getMessage()];
            }
        }

        $importResults = ['preview' => false, 'success_count' => $successCount, 'error_count' => $errorCount, 'errors' => $errors];
    }
}

// Payment methods for dropdown
$paymentMethods = [
    'cash' => 'Cash',
    'upi' => 'UPI / PhonePe / Google Pay',
    'bank_transfer' => 'Bank Transfer',
    'cheque' => 'Cheque'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Import Payments - CSF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-size: 18px;
            line-height: 1.8;
            background-color: #f8f9fa;
        }

        .main-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .header-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-section h1 {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .form-section {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-label {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .form-control, .form-select {
            font-size: 18px;
            padding: 15px 20px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        textarea.form-control {
            min-height: 200px;
            font-family: 'Courier New', monospace;
            font-size: 16px;
        }

        .btn-custom {
            font-size: 20px;
            padding: 15px 35px;
            border-radius: 10px;
            font-weight: 600;
            min-width: 160px;
        }

        .btn-primary {
            background: #007bff;
            border: none;
        }

        .btn-success {
            background: #28a745;
            border: none;
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
        }

        .back-link {
            font-size: 20px;
            color: #007bff;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .back-link:hover {
            color: #0056b3;
        }

        .format-guide {
            background: #e7f3ff;
            border: 2px solid #007bff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .format-guide h4 {
            color: #007bff;
            margin-bottom: 15px;
        }

        .format-guide code {
            background: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 16px;
        }

        .preview-table {
            font-size: 16px;
        }

        .preview-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .preview-table .duplicate-row {
            background: #fff3cd;
        }

        .preview-table .error-row {
            background: #f8d7da;
        }

        .error-list {
            background: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            max-height: 300px;
            overflow-y: auto;
        }

        .error-item {
            padding: 10px;
            border-bottom: 1px solid #f5c6cb;
            font-size: 16px;
        }

        .error-item:last-child {
            border-bottom: none;
        }

        .summary-box {
            background: #e7f3ff;
            border: 2px solid #007bff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: center;
        }

        .summary-box .count {
            font-size: 48px;
            font-weight: bold;
            color: #007bff;
        }

        .summary-box .label {
            font-size: 18px;
            color: #2c3e50;
        }

        .alert-custom {
            font-size: 18px;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="csf-funds.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to CSF Funds
        </a>

        <div class="header-section">
            <h1><i class="fas fa-file-import"></i> Bulk Import Payments</h1>
            <p class="mb-0">Import multiple past payment records at once using CSV format</p>
        </div>

        <?php if (!$importResults || (isset($importResults['preview']) && !$importResults['preview'])): ?>
        <!-- STEP 1: Paste CSV Data -->
        <div class="form-section">
            <div class="format-guide">
                <h4><i class="fas fa-info-circle"></i> CSV Format Guide</h4>
                <p style="font-size: 16px; margin-bottom: 15px;">
                    Paste your payment data below. Each line should contain:
                </p>
                <p style="font-size: 16px;">
                    <code>Mobile/Name, Amount, Month (YYYY-MM), Date (optional), Method (optional)</code>
                </p>
                <hr>
                <p style="font-size: 16px; margin-bottom: 10px;"><strong>Examples:</strong></p>
                <pre style="background: #fff; padding: 15px; border-radius: 8px; font-size: 15px; margin: 0;">9876543210, 100, 2025-06
John Doe, 150, 2025-05, 2025-05-15
9876543211, 200, 2025-04, 2025-04-10, upi
Jane Smith, 100, 2025-03, 2025-03-20, bank_transfer</pre>
                <p style="font-size: 14px; color: #666; margin-top: 15px; margin-bottom: 0;">
                    <i class="fas fa-lightbulb"></i> <strong>Tips:</strong>
                    Mobile must be 10 digits. Month format: YYYY-MM. Date format: YYYY-MM-DD or DD-MM-YYYY.
                    Payment methods: cash, upi, bank_transfer, cheque
                </p>
            </div>

            <form method="POST" id="importForm">
                <input type="hidden" name="action" value="preview_import">

                <div class="mb-4">
                    <label class="form-label">Default Payment Method</label>
                    <select name="default_payment_method" class="form-select" style="max-width: 400px;">
                        <?php foreach ($paymentMethods as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted" style="font-size: 16px;">
                        Used when payment method is not specified in CSV
                    </small>
                </div>

                <div class="mb-4">
                    <label class="form-label">Paste CSV Data</label>
                    <textarea name="csv_data" class="form-control" required placeholder="9876543210, 100, 2025-06
John Doe, 150, 2025-05, 2025-05-15
..."><?php echo htmlspecialchars($_POST['csv_data'] ?? ''); ?></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-custom btn-primary">
                        <i class="fas fa-search"></i> Preview Import
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($importResults && isset($importResults['preview']) && $importResults['preview']): ?>
        <!-- STEP 2: Preview Results -->
        <div class="form-section">
            <h3 class="mb-4"><i class="fas fa-eye"></i> Preview Import</h3>

            <!-- Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="summary-box" style="background: #d4edda; border-color: #28a745;">
                        <div class="count" style="color: #28a745;"><?php echo count(array_filter($importResults['records'], function($r) { return !$r['is_duplicate']; })); ?></div>
                        <div class="label">Ready to Import</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-box" style="background: #fff3cd; border-color: #ffc107;">
                        <div class="count" style="color: #856404;"><?php echo count(array_filter($importResults['records'], function($r) { return $r['is_duplicate']; })); ?></div>
                        <div class="label">Duplicates</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-box" style="background: #f8d7da; border-color: #dc3545;">
                        <div class="count" style="color: #dc3545;"><?php echo count($importResults['errors']); ?></div>
                        <div class="label">Errors</div>
                    </div>
                </div>
            </div>

            <!-- Errors List -->
            <?php if (!empty($importResults['errors'])): ?>
            <div class="error-list">
                <h5 style="color: #721c24; margin-bottom: 15px;"><i class="fas fa-exclamation-triangle"></i> Errors Found</h5>
                <?php foreach ($importResults['errors'] as $error): ?>
                <div class="error-item">
                    <strong>Row <?php echo $error['row']; ?>:</strong> <?php echo htmlspecialchars($error['message']); ?>
                    <?php if (isset($error['data'])): ?>
                    <br><small class="text-muted"><?php echo htmlspecialchars($error['data']); ?></small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Valid Records Preview -->
            <?php if (!empty($importResults['records'])): ?>
            <div class="table-responsive mb-4">
                <table class="table table-bordered preview-table">
                    <thead>
                        <tr>
                            <th>Row</th>
                            <th>Member</th>
                            <th>Mobile</th>
                            <th>Amount</th>
                            <th>Month</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($importResults['records'] as $record): ?>
                        <tr class="<?php echo $record['is_duplicate'] ? 'duplicate-row' : ''; ?>">
                            <td><?php echo $record['row']; ?></td>
                            <td><?php echo htmlspecialchars($record['member']['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['member']['mobile_number']); ?></td>
                            <td>₹<?php echo number_format($record['amount'], 2); ?></td>
                            <td><?php echo date('F Y', strtotime($record['payment_month'] . '-01')); ?></td>
                            <td><?php echo date('d M Y', strtotime($record['payment_date'])); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $record['payment_method'])); ?></td>
                            <td>
                                <?php if ($record['is_duplicate']): ?>
                                <span class="badge bg-warning text-dark">Duplicate</span>
                                <?php else: ?>
                                <span class="badge bg-success">Ready</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Confirm Import Form -->
            <form method="POST" id="confirmForm">
                <input type="hidden" name="action" value="confirm_import">

                <?php
                $hasDuplicates = count(array_filter($importResults['records'], function($r) { return $r['is_duplicate']; })) > 0;
                if ($hasDuplicates):
                ?>
                <div class="alert alert-warning alert-custom">
                    <div class="form-check" style="font-size: 18px;">
                        <input class="form-check-input" type="checkbox" name="skip_duplicates" value="1" id="skipDuplicates" checked style="width: 20px; height: 20px;">
                        <label class="form-check-label" for="skipDuplicates">
                            Skip duplicate payments (recommended)
                        </label>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Duplicates are payments that already exist for the same member and month.
                    </small>
                </div>
                <?php endif; ?>

                <div class="text-center">
                    <a href="csf-bulk-import-payments.php" class="btn btn-custom btn-secondary me-3">
                        <i class="fas fa-arrow-left"></i> Start Over
                    </a>
                    <?php
                    $validRecords = count(array_filter($importResults['records'], function($r) { return !$r['is_duplicate']; }));
                    if ($validRecords > 0):
                    ?>
                    <button type="submit" class="btn btn-custom btn-success">
                        <i class="fas fa-check"></i> Confirm Import (<?php echo $validRecords; ?> records)
                    </button>
                    <?php endif; ?>
                </div>
            </form>
            <?php else: ?>
            <div class="alert alert-warning alert-custom">
                <i class="fas fa-exclamation-circle"></i> No valid records to import. Please check your data and try again.
            </div>
            <div class="text-center">
                <a href="csf-bulk-import-payments.php" class="btn btn-custom btn-secondary">
                    <i class="fas fa-arrow-left"></i> Start Over
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Help -->
        <div class="form-section" style="background: #f8f9fa;">
            <h4><i class="fas fa-question-circle"></i> Need Help?</h4>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h5>Supported Formats</h5>
                    <ul style="font-size: 16px;">
                        <li>Mobile: 10-digit number (e.g., 9876543210)</li>
                        <li>Name: Exact match (e.g., John Doe)</li>
                        <li>Amount: Number (e.g., 100, 150.50)</li>
                        <li>Month: YYYY-MM (e.g., 2025-06)</li>
                        <li>Date: YYYY-MM-DD or DD-MM-YYYY</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Payment Methods</h5>
                    <ul style="font-size: 16px;">
                        <li><code>cash</code> - Cash payment</li>
                        <li><code>upi</code> - UPI / PhonePe / GPay</li>
                        <li><code>bank_transfer</code> - Bank Transfer</li>
                        <li><code>cheque</code> - Cheque</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
