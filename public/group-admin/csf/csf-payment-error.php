<?php
/**
 * CSF Payment Error Status Page
 * Shows detailed error information after payment recording failures
 * Provides clear feedback for bulk imports and single payment errors
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/feature-access.php';

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

// Get import results from session
$importResults = $_SESSION['import_results'] ?? null;

// Clear session data after reading
unset($_SESSION['import_results']);

// If no results, redirect to CSF dashboard
if (!$importResults) {
    header('Location: /public/group-admin/csf/csf-funds.php');
    exit();
}

$successCount = $importResults['success_count'] ?? 0;
$errorCount = $importResults['error_count'] ?? 0;
$errors = $importResults['errors'] ?? [];
$successRecords = $importResults['success_records'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Results - CSF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-size: 18px;
            line-height: 1.8;
            background-color: #f8f9fa;
        }

        .main-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .header-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .header-section h1 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .header-section.partial-success h1 {
            color: #856404;
        }

        .header-section.all-errors h1 {
            color: #dc3545;
        }

        .result-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }

        .summary-card.success {
            background: #d4edda;
            border: 2px solid #28a745;
        }

        .summary-card.error {
            background: #f8d7da;
            border: 2px solid #dc3545;
        }

        .summary-card .count {
            font-size: 48px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 10px;
        }

        .summary-card.success .count {
            color: #28a745;
        }

        .summary-card.error .count {
            color: #dc3545;
        }

        .summary-card .label {
            font-size: 18px;
            color: #2c3e50;
        }

        .error-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .error-item {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 4px solid #dc3545;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .error-item:last-child {
            margin-bottom: 0;
        }

        .error-item .error-row {
            font-weight: 600;
            color: #dc3545;
            font-size: 16px;
        }

        .error-item .error-message {
            font-size: 18px;
            color: #2c3e50;
            margin: 8px 0;
        }

        .error-item .error-data {
            font-size: 15px;
            color: #6c757d;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .success-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
        }

        .success-item {
            background: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .success-item:last-child {
            margin-bottom: 0;
        }

        .btn-custom {
            font-size: 20px;
            padding: 15px 35px;
            border-radius: 10px;
            font-weight: 600;
            min-width: 180px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .icon-large {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .icon-large.warning {
            color: #ffc107;
        }

        .icon-large.error {
            color: #dc3545;
        }

        h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .help-text {
            background: #e7f3ff;
            border: 2px solid #007bff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .help-text h4 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .help-text ul {
            margin-bottom: 0;
            font-size: 16px;
        }

        .help-text li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header-section <?php echo $successCount > 0 ? 'partial-success' : 'all-errors'; ?>">
            <?php if ($successCount > 0 && $errorCount > 0): ?>
            <div class="icon-large warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>Partial Import Complete</h1>
            <p class="mb-0" style="font-size: 20px; color: #666;">
                Some payments were imported successfully, but <?php echo $errorCount; ?> record(s) had errors
            </p>
            <?php elseif ($errorCount > 0): ?>
            <div class="icon-large error">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1>Import Failed</h1>
            <p class="mb-0" style="font-size: 20px; color: #666;">
                <?php echo $errorCount; ?> record(s) could not be imported
            </p>
            <?php endif; ?>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card success">
                <div class="count"><?php echo $successCount; ?></div>
                <div class="label">
                    <i class="fas fa-check-circle"></i> Successfully Imported
                </div>
            </div>
            <div class="summary-card error">
                <div class="count"><?php echo $errorCount; ?></div>
                <div class="label">
                    <i class="fas fa-times-circle"></i> Failed to Import
                </div>
            </div>
        </div>

        <!-- Error Details -->
        <?php if (!empty($errors)): ?>
        <div class="result-section">
            <h3><i class="fas fa-exclamation-triangle text-danger"></i> Error Details</h3>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                <div class="error-item">
                    <?php if (isset($error['row']) && $error['row'] > 0): ?>
                    <div class="error-row">
                        <i class="fas fa-file-alt"></i> Row <?php echo $error['row']; ?>
                    </div>
                    <?php endif; ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle text-danger"></i>
                        <?php echo htmlspecialchars($error['message']); ?>
                    </div>
                    <?php if (isset($error['data']) && !empty($error['data'])): ?>
                    <div class="error-data">
                        <?php echo htmlspecialchars($error['data']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Common Error Fixes -->
            <div class="help-text">
                <h4><i class="fas fa-lightbulb"></i> Common Fixes</h4>
                <ul>
                    <li><strong>Member not found:</strong> Check that the mobile number is 10 digits or the name matches exactly</li>
                    <li><strong>Duplicate payment:</strong> Payment already exists for this member and month. Delete the existing one first or skip duplicates.</li>
                    <li><strong>Invalid month format:</strong> Use YYYY-MM format (e.g., 2025-06 for June 2025)</li>
                    <li><strong>Invalid date format:</strong> Use YYYY-MM-DD (e.g., 2025-06-15) or DD-MM-YYYY (e.g., 15-06-2025)</li>
                    <li><strong>Invalid amount:</strong> Amount must be a positive number (e.g., 100 or 150.50)</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Successful Records -->
        <?php if (!empty($successRecords)): ?>
        <div class="result-section">
            <h3><i class="fas fa-check-circle text-success"></i> Successfully Imported (<?php echo count($successRecords); ?>)</h3>
            <div class="success-list">
                <?php foreach ($successRecords as $record): ?>
                <div class="success-item">
                    <div>
                        <strong><?php echo htmlspecialchars($record['member']['full_name']); ?></strong>
                        <span style="color: #666; font-size: 16px;">
                            (<?php echo htmlspecialchars($record['member']['mobile_number']); ?>)
                        </span>
                    </div>
                    <div style="text-align: right;">
                        <span style="color: #28a745; font-weight: 600;">
                            ₹<?php echo number_format($record['amount'], 2); ?>
                        </span>
                        <br>
                        <small style="color: #666;">
                            <?php echo date('F Y', strtotime($record['payment_month'] . '-01')); ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="csf-bulk-import-payments.php" class="btn btn-custom btn-primary">
                <i class="fas fa-redo"></i> Import More
            </a>
            <a href="csf-payment-history.php" class="btn btn-custom btn-success">
                <i class="fas fa-list"></i> View Payment History
            </a>
            <a href="csf-funds.php" class="btn btn-custom btn-secondary">
                <i class="fas fa-home"></i> Back to CSF
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
