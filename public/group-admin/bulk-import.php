<?php
/**
 * Bulk Import Processor
 * Process Excel uploads with preview, validation, and undo functionality
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

AuthMiddleware::requireRole('group_admin');

$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'community_building')) {
    $_SESSION['error_message'] = "Community Building is not enabled for your community";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$type = $_GET['type'] ?? 'sub_communities'; // sub_communities or members
$step = $_POST['step'] ?? 'upload'; // upload, preview, confirm

$previewData = [];
$errors = [];
$stats = ['new' => 0, 'update' => 0, 'delete' => 0, 'errors' => 0];

// Handle File Upload and Preview
if ($step === 'upload' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            if (count($data) < 2) {
                $errors[] = "File is empty or has no data rows";
            } else {
                $headers = array_shift($data); // Remove header row

                if ($type === 'sub_communities') {
                    // Process Sub-Communities
                    foreach ($data as $rowIndex => $row) {
                        $rowNum = $rowIndex + 2; // +2 because of header and 0-index

                        $name = trim($row[0] ?? '');
                        $description = trim($row[1] ?? '');
                        $status = trim($row[2] ?? 'active');

                        if (empty($name)) {
                            $errors[] = "Row $rowNum: Sub-Community Name is required";
                            $stats['errors']++;
                            continue;
                        }

                        if (!in_array($status, ['active', 'inactive'])) {
                            $errors[] = "Row $rowNum: Status must be 'active' or 'inactive'";
                            $stats['errors']++;
                            continue;
                        }

                        // Check if exists
                        $checkQuery = "SELECT sub_community_id FROM sub_communities
                                      WHERE sub_community_name = :name AND community_id = :community_id";
                        $checkStmt = $db->prepare($checkQuery);
                        $checkStmt->bindParam(':name', $name);
                        $checkStmt->bindParam(':community_id', $communityId);
                        $checkStmt->execute();
                        $existingId = $checkStmt->fetchColumn();

                        $action = $existingId ? 'update' : 'new';
                        $stats[$action]++;

                        $previewData[] = [
                            'action' => $action,
                            'id' => $existingId,
                            'name' => $name,
                            'description' => $description,
                            'status' => $status
                        ];
                    }

                } else {
                    // Process Members
                    // Get custom fields
                    $fieldsQuery = "SELECT field_id, field_name, field_label, field_type, is_required, field_options
                                   FROM custom_field_definitions
                                   WHERE community_id = :community_id AND applies_to = 'member' AND status = 'active'
                                   ORDER BY display_order";
                    $fieldsStmt = $db->prepare($fieldsQuery);
                    $fieldsStmt->bindParam(':community_id', $communityId);
                    $fieldsStmt->execute();
                    $customFields = $fieldsStmt->fetchAll();

                    foreach ($data as $rowIndex => $row) {
                        $rowNum = $rowIndex + 2;

                        $fullName = trim($row[0] ?? '');
                        $email = trim($row[1] ?? '');
                        $phoneNumber = trim($row[2] ?? '');
                        $subCommunityName = trim($row[3] ?? '');

                        if (empty($fullName)) {
                            $errors[] = "Row $rowNum: Full Name is required";
                            $stats['errors']++;
                            continue;
                        }

                        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "Row $rowNum: Valid email is required";
                            $stats['errors']++;
                            continue;
                        }

                        if (empty($subCommunityName)) {
                            $errors[] = "Row $rowNum: Sub-Community is required";
                            $stats['errors']++;
                            continue;
                        }

                        // Check sub-community exists
                        $scQuery = "SELECT sub_community_id FROM sub_communities
                                   WHERE sub_community_name = :name AND community_id = :community_id";
                        $scStmt = $db->prepare($scQuery);
                        $scStmt->bindParam(':name', $subCommunityName);
                        $scStmt->bindParam(':community_id', $communityId);
                        $scStmt->execute();
                        $subCommunityId = $scStmt->fetchColumn();

                        if (!$subCommunityId) {
                            $errors[] = "Row $rowNum: Sub-Community '$subCommunityName' does not exist";
                            $stats['errors']++;
                            continue;
                        }

                        // Check if user exists
                        $userQuery = "SELECT user_id FROM users WHERE email = :email";
                        $userStmt = $db->prepare($userQuery);
                        $userStmt->bindParam(':email', $email);
                        $userStmt->execute();
                        $existingUserId = $userStmt->fetchColumn();

                        $action = $existingUserId ? 'update' : 'new';
                        $stats[$action]++;

                        // Get custom field values
                        $customFieldValues = [];
                        $colIndex = 4; // Start after standard columns
                        foreach ($customFields as $field) {
                            $value = trim($row[$colIndex] ?? '');
                            if ($field['is_required'] && empty($value)) {
                                $errors[] = "Row $rowNum: {$field['field_label']} is required";
                                $stats['errors']++;
                            }
                            $customFieldValues[$field['field_id']] = $value;
                            $colIndex++;
                        }

                        $previewData[] = [
                            'action' => $action,
                            'user_id' => $existingUserId,
                            'full_name' => $fullName,
                            'email' => $email,
                            'phone_number' => $phoneNumber,
                            'sub_community_name' => $subCommunityName,
                            'sub_community_id' => $subCommunityId,
                            'custom_fields' => $customFieldValues
                        ];
                    }
                }

                // Store in session for confirmation
                $_SESSION['bulk_import_data'] = [
                    'type' => $type,
                    'data' => $previewData,
                    'stats' => $stats,
                    'errors' => $errors
                ];

                $step = 'preview';
            }

        } catch (Exception $e) {
            $errors[] = "Error reading Excel file: " . $e->getMessage();
        }
    } else {
        $errors[] = "File upload failed. Please try again.";
    }
}

// Handle Confirmation and Import
if ($step === 'confirm' && isset($_SESSION['bulk_import_data'])) {
    $importData = $_SESSION['bulk_import_data'];
    $successCount = 0;
    $errorCount = 0;

    try {
        $db->beginTransaction();

        if ($importData['type'] === 'sub_communities') {
            foreach ($importData['data'] as $item) {
                try {
                    if ($item['action'] === 'new') {
                        $insertQuery = "INSERT INTO sub_communities
                                       (community_id, sub_community_name, description, status, created_by)
                                       VALUES (:community_id, :name, :description, :status, :created_by)";
                        $stmt = $db->prepare($insertQuery);
                        $stmt->bindParam(':community_id', $communityId);
                        $stmt->bindParam(':name', $item['name']);
                        $stmt->bindParam(':description', $item['description']);
                        $stmt->bindParam(':status', $item['status']);
                        $stmt->bindParam(':created_by', $userId);
                        $stmt->execute();
                    } else {
                        $updateQuery = "UPDATE sub_communities
                                       SET sub_community_name = :name,
                                           description = :description,
                                           status = :status
                                       WHERE sub_community_id = :id AND community_id = :community_id";
                        $stmt = $db->prepare($updateQuery);
                        $stmt->bindParam(':name', $item['name']);
                        $stmt->bindParam(':description', $item['description']);
                        $stmt->bindParam(':status', $item['status']);
                        $stmt->bindParam(':id', $item['id']);
                        $stmt->bindParam(':community_id', $communityId);
                        $stmt->execute();
                    }
                    $successCount++;
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Error processing '{$item['name']}': " . $e->getMessage();
                }
            }

        } else {
            // Import Members
            foreach ($importData['data'] as $item) {
                try {
                    if ($item['action'] === 'new') {
                        // Create new user
                        $defaultPassword = bin2hex(random_bytes(8));
                        $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

                        $insertUserQuery = "INSERT INTO users
                                           (community_id, full_name, email, phone_number, password_hash, role, status)
                                           VALUES (:community_id, :full_name, :email, :phone_number, :password_hash, 'member', 'active')";
                        $userStmt = $db->prepare($insertUserQuery);
                        $userStmt->bindParam(':community_id', $communityId);
                        $userStmt->bindParam(':full_name', $item['full_name']);
                        $userStmt->bindParam(':email', $item['email']);
                        $userStmt->bindParam(':phone_number', $item['phone_number']);
                        $userStmt->bindParam(':password_hash', $passwordHash);
                        $userStmt->execute();

                        $newUserId = $db->lastInsertId();
                    } else {
                        $newUserId = $item['user_id'];

                        // Update user
                        $updateUserQuery = "UPDATE users
                                           SET full_name = :full_name,
                                               phone_number = :phone_number
                                           WHERE user_id = :user_id";
                        $userStmt = $db->prepare($updateUserQuery);
                        $userStmt->bindParam(':full_name', $item['full_name']);
                        $userStmt->bindParam(':phone_number', $item['phone_number']);
                        $userStmt->bindParam(':user_id', $newUserId);
                        $userStmt->execute();
                    }

                    // Assign to sub-community
                    $assignQuery = "INSERT INTO sub_community_members
                                   (sub_community_id, user_id, assigned_by, status)
                                   VALUES (:sub_community_id, :user_id, :assigned_by, 'active')
                                   ON DUPLICATE KEY UPDATE
                                   sub_community_id = :sub_community_id2,
                                   assigned_by = :assigned_by2";
                    $assignStmt = $db->prepare($assignQuery);
                    $assignStmt->bindParam(':sub_community_id', $item['sub_community_id']);
                    $assignStmt->bindParam(':sub_community_id2', $item['sub_community_id']);
                    $assignStmt->bindParam(':user_id', $newUserId);
                    $assignStmt->bindParam(':assigned_by', $userId);
                    $assignStmt->bindParam(':assigned_by2', $userId);
                    $assignStmt->execute();

                    // Save custom field values
                    foreach ($item['custom_fields'] as $fieldId => $value) {
                        if (!empty($value)) {
                            $customDataQuery = "INSERT INTO member_custom_data
                                               (user_id, field_id, field_value)
                                               VALUES (:user_id, :field_id, :field_value)
                                               ON DUPLICATE KEY UPDATE field_value = :field_value2";
                            $customStmt = $db->prepare($customDataQuery);
                            $customStmt->bindParam(':user_id', $newUserId);
                            $customStmt->bindParam(':field_id', $fieldId);
                            $customStmt->bindParam(':field_value', $value);
                            $customStmt->bindParam(':field_value2', $value);
                            $customStmt->execute();
                        }
                    }

                    $successCount++;
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Error processing '{$item['full_name']}': " . $e->getMessage();
                }
            }
        }

        $db->commit();

        $_SESSION['success_message'] = "Bulk import completed! $successCount items processed successfully.";
        if ($errorCount > 0) {
            $_SESSION['success_message'] .= " $errorCount items had errors.";
        }

        unset($_SESSION['bulk_import_data']);

        $redirectUrl = $type === 'sub_communities' ? '/public/group-admin/sub-communities.php' : '/public/group-admin/community-members.php';
        header('Location: ' . $redirectUrl);
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "Bulk import failed: " . $e->getMessage();
    }
}

// Retrieve preview data from session
if ($step === 'preview' && isset($_SESSION['bulk_import_data'])) {
    $importData = $_SESSION['bulk_import_data'];
    $previewData = $importData['data'];
    $stats = $importData['stats'];
    $errors = $importData['errors'];
}

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => '/public/group-admin/community-building.php'],
    ['label' => 'Bulk Operations', 'url' => '/public/group-admin/bulk-operations.php'],
    ['label' => 'Import', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Import - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .upload-area {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .file-input-wrapper {
            margin: 20px 0;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .stats-bar {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
        }
        .stat-item {
            padding: 15px 25px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-new {
            background: #d4edda;
            color: #155724;
        }
        .stat-update {
            background: #cce5ff;
            color: #004085;
        }
        .stat-error {
            background: #f8d7da;
            color: #721c24;
        }
        .preview-table {
            background: white;
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .badge-new {
            background: #d4edda;
            color: #155724;
        }
        .badge-update {
            background: #cce5ff;
            color: #004085;
        }
        .errors-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .errors-list ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        <h1 style="margin: 0 0 10px 0;">
            📤 Bulk Import <?php echo $type === 'sub_communities' ? 'Sub-Communities' : 'Members'; ?>
        </h1>

        <?php if ($step === 'upload'): ?>
            <div class="upload-area">
                <div style="font-size: 4rem; margin-bottom: 20px;">📁</div>
                <h2>Upload Excel File</h2>
                <p style="color: #666; margin: 10px 0 20px 0;">
                    Select the Excel file you prepared with <?php echo $type === 'sub_communities' ? 'sub-communities' : 'member'; ?> data
                </p>

                <form method="POST" enctype="multipart/form-data" action="">
                    <input type="hidden" name="step" value="upload">
                    <div class="file-input-wrapper">
                        <input type="file" name="excel_file" accept=".xlsx,.xls" required
                               style="padding: 10px;">
                    </div>
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">Upload and Preview</button>
                        <a href="/public/group-admin/bulk-operations.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

        <?php elseif ($step === 'preview'): ?>
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item stat-new">
                    <div style="font-size: 2rem; font-weight: bold;"><?php echo $stats['new']; ?></div>
                    <div>New Items</div>
                </div>
                <div class="stat-item stat-update">
                    <div style="font-size: 2rem; font-weight: bold;"><?php echo $stats['update']; ?></div>
                    <div>Updates</div>
                </div>
                <?php if ($stats['errors'] > 0): ?>
                    <div class="stat-item stat-error">
                        <div style="font-size: 2rem; font-weight: bold;"><?php echo $stats['errors']; ?></div>
                        <div>Errors</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Errors -->
            <?php if (count($errors) > 0): ?>
                <div class="errors-list">
                    <strong style="color: #721c24;">⚠️ Validation Errors:</strong>
                    <ul>
                        <?php foreach (array_slice($errors, 0, 10) as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                        <?php if (count($errors) > 10): ?>
                            <li><em>... and <?php echo count($errors) - 10; ?> more errors</em></li>
                        <?php endif; ?>
                    </ul>
                    <p style="margin-top: 15px;">
                        <strong>Please fix these errors in your Excel file and upload again.</strong>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Preview Table -->
            <?php if (count($previewData) > 0): ?>
                <div class="preview-table">
                    <h3>Preview - <?php echo count($previewData); ?> Items</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Action</th>
                                <?php if ($type === 'sub_communities'): ?>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                <?php else: ?>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Sub-Community</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($previewData, 0, 50) as $item): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-<?php echo $item['action']; ?>">
                                            <?php echo strtoupper($item['action']); ?>
                                        </span>
                                    </td>
                                    <?php if ($type === 'sub_communities'): ?>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td><?php echo htmlspecialchars($item['status']); ?></td>
                                    <?php else: ?>
                                        <td><?php echo htmlspecialchars($item['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['email']); ?></td>
                                        <td><?php echo htmlspecialchars($item['sub_community_name']); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($previewData) > 50): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #666;">
                                        <em>... and <?php echo count($previewData) - 50; ?> more items</em>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="step" value="confirm">
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            ✓ Confirm and Import
                        </button>
                        <a href="/public/group-admin/bulk-operations.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <p style="text-align: center; padding: 40px;">No valid data to import.</p>
                <div class="action-buttons">
                    <a href="?type=<?php echo $type; ?>" class="btn btn-primary">Try Again</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
