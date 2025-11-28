<?php
/**
 * Upload Members via CSV
 * GetToKnow Community App - Step 2
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$communityId = AuthMiddleware::getCommunityId();
$campaignId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$communityId || !$campaignId) {
    header("Location: /public/group-admin/transactions.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get campaign details
$query = "SELECT * FROM transaction_campaigns WHERE campaign_id = :id AND community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $campaignId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$campaign = $stmt->fetch();

if (!$campaign) {
    header("Location: /public/group-admin/transactions.php");
    exit;
}

$error = '';
$success = '';
$previewData = [];

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($fileExt !== 'csv') {
            $error = 'Only CSV files are allowed';
        } else {
            $handle = fopen($file['tmp_name'], 'r');

            if ($handle) {
                $row = 0;
                $headers = [];
                $data = [];
                $errors = [];

                while (($line = fgetcsv($handle)) !== FALSE) {
                    $row++;

                    if ($row === 1) {
                        // First row is headers
                        $headers = $line;
                        continue;
                    }

                    // Validate row has 3 columns
                    if (count($line) < 3) {
                        $errors[] = "Row {$row}: Missing columns";
                        continue;
                    }

                    $memberName = trim($line[0]);
                    $mobile = trim($line[1]);
                    $amount = trim($line[2]);

                    // Validate data
                    if (empty($memberName)) {
                        $errors[] = "Row {$row}: Member name is required";
                    }
                    if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
                        $errors[] = "Row {$row}: Invalid mobile number '{$mobile}'";
                    }
                    if (!is_numeric($amount) || $amount <= 0) {
                        $errors[] = "Row {$row}: Invalid amount '{$amount}'";
                    }

                    $data[] = [
                        'name' => $memberName,
                        'mobile' => $mobile,
                        'amount' => $amount
                    ];
                }

                fclose($handle);

                if (count($errors) > 0) {
                    $error = implode('<br>', array_slice($errors, 0, 10)); // Show first 10 errors
                    if (count($errors) > 10) {
                        $error .= '<br>... and ' . (count($errors) - 10) . ' more errors';
                    }
                } else {
                    $previewData = $data;
                }
            }
        }
    } else {
        $error = 'File upload failed';
    }
}

// Handle confirm import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    $importData = json_decode($_POST['import_data'], true);

    if ($importData && count($importData) > 0) {
        $inserted = 0;
        $duplicates = 0;

        foreach ($importData as $member) {
            // Check if mobile already exists in this campaign
            $checkQuery = "SELECT member_id FROM campaign_members
                          WHERE campaign_id = :campaign_id AND mobile_number = :mobile";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':campaign_id', $campaignId);
            $checkStmt->bindParam(':mobile', $member['mobile']);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                $duplicates++;
                continue;
            }

            // Insert member
            $insertQuery = "INSERT INTO campaign_members
                           (campaign_id, member_name, mobile_number, expected_amount, payment_status, total_paid)
                           VALUES (:campaign_id, :name, :mobile, :amount, 'unpaid', 0)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':campaign_id', $campaignId);
            $insertStmt->bindParam(':name', $member['name']);
            $insertStmt->bindParam(':mobile', $member['mobile']);
            $insertStmt->bindParam(':amount', $member['amount']);

            if ($insertStmt->execute()) {
                $inserted++;
            }
        }

        // Update campaign totals
        $updateQuery = "UPDATE transaction_campaigns
                       SET total_members = (SELECT COUNT(*) FROM campaign_members WHERE campaign_id = :campaign_id),
                           total_expected_amount = (SELECT COALESCE(SUM(expected_amount), 0) FROM campaign_members WHERE campaign_id = :campaign_id2)
                       WHERE campaign_id = :campaign_id3";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':campaign_id', $campaignId);
        $updateStmt->bindParam(':campaign_id2', $campaignId);
        $updateStmt->bindParam(':campaign_id3', $campaignId);
        $updateStmt->execute();

        $success = "Successfully imported {$inserted} members!";
        if ($duplicates > 0) {
            $success .= " (Skipped {$duplicates} duplicates)";
        }

        $previewData = []; // Clear preview
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Members - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .header {
            background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .instructions {
            background: var(--info-light);
            border-left: 4px solid var(--info-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .upload-zone {
            border: 3px dashed var(--gray-300);
            border-radius: var(--radius-lg);
            padding: var(--spacing-2xl);
            text-align: center;
            background: var(--gray-50);
            transition: all var(--transition-base);
        }
        .upload-zone:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
            background-opacity: 0.05;
        }
        .upload-icon {
            font-size: 64px;
            margin-bottom: var(--spacing-md);
        }
        .preview-table {
            max-height: 400px;
            overflow-y: auto;
        }
        .code-block {
            background: var(--gray-900);
            color: #fff;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-family: monospace;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Upload Members - <?php echo htmlspecialchars($campaign['campaign_name']); ?></h1>
            <p style="margin: 0; opacity: 0.9;">Step 2 of 4</p>
        </div>
    </div>

    <div class="container main-content">
        <div class="instructions">
            <h3 style="margin-top: 0;">📤 Step 2: Upload Member List</h3>
            <p>Upload a CSV file with your members' details. The file must have 3 columns:</p>
            <ol style="margin: var(--spacing-md) 0; padding-left: var(--spacing-xl);">
                <li><strong>Member Name</strong> - Full name</li>
                <li><strong>Mobile Number</strong> - 10-digit number (without +91)</li>
                <li><strong>Amount</strong> - Expected payment amount</li>
            </ol>
            <div class="code-block">
Member Name,Mobile Number,Amount<br>
Raj Kumar,9876543210,5000<br>
Priya Sharma,9876543211,5000<br>
Amit Patel,9876543212,3000
            </div>
            <p style="margin-top: var(--spacing-md); margin-bottom: 0;">
                <a href="#" onclick="downloadSampleCSV(); return false;" style="color: var(--info-color); font-weight: 600;">
                    📥 Download Sample CSV File
                </a>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <div style="margin-top: var(--spacing-md);">
                    <a href="/public/group-admin/transaction-members.php?id=<?php echo $campaignId; ?>" class="btn btn-primary">
                        View All Members →
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($previewData) === 0): ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="upload-zone">
                            <div class="upload-icon">📁</div>
                            <h3>Choose CSV File to Upload</h3>
                            <p style="color: var(--gray-600); margin-bottom: var(--spacing-lg);">
                                File must be in CSV format with 3 columns
                            </p>
                            <input
                                type="file"
                                name="csv_file"
                                accept=".csv"
                                required
                                style="margin-bottom: var(--spacing-md);"
                            >
                            <br>
                            <button type="submit" class="btn btn-primary btn-lg">
                                Upload & Preview
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header" style="background: var(--success-light);">
                    <h3 class="card-title" style="color: var(--success-color); margin: 0;">
                        ✓ Preview: <?php echo count($previewData); ?> Members Found
                    </h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="preview-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Member Name</th>
                                    <th>Mobile Number</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($previewData as $index => $member): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['mobile']); ?></td>
                                        <td>₹<?php echo number_format($member['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="POST" style="display: flex; gap: var(--spacing-md); align-items: center;">
                        <input type="hidden" name="import_data" value='<?php echo json_encode($previewData); ?>'>
                        <input type="hidden" name="confirm_import" value="1">
                        <button type="submit" class="btn btn-success btn-lg">
                            ✓ Confirm & Import <?php echo count($previewData); ?> Members
                        </button>
                        <a href="/public/group-admin/transaction-upload.php?id=<?php echo $campaignId; ?>" class="btn btn-secondary">
                            Cancel & Upload Again
                        </a>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div style="display: flex; gap: var(--spacing-md);">
                    <a href="/public/group-admin/transactions.php" class="btn btn-secondary">
                        ← Back to Campaigns
                    </a>
                    <a href="/public/group-admin/transaction-members.php?id=<?php echo $campaignId; ?>" class="btn btn-primary">
                        View Uploaded Members →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadSampleCSV() {
            const csvContent = "Member Name,Mobile Number,Amount\nRaj Kumar,9876543210,5000\nPriya Sharma,9876543211,5000\nAmit Patel,9876543212,3000";
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'sample_members.csv';
            a.click();
        }
    </script>
</body>
</html>
