<?php
/**
 * Bulk Operations - Import/Export Sub-Communities and Members
 * Download sample Excel, upload bulk data, edit/delete in bulk
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

// Handle Sample Download
if (isset($_GET['download_sample'])) {
    $type = $_GET['download_sample']; // 'sub_communities' or 'members'

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    if ($type === 'sub_communities') {
        // Sub-Communities Sample
        $sheet->setTitle('Sub-Communities');
        $sheet->setCellValue('A1', 'Sub-Community Name');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Status');

        // Sample data
        $sheet->setCellValue('A2', 'IT Department');
        $sheet->setCellValue('B2', 'Information Technology team members');
        $sheet->setCellValue('C2', 'active');

        $sheet->setCellValue('A3', 'HR Department');
        $sheet->setCellValue('B3', 'Human Resources team');
        $sheet->setCellValue('C3', 'active');

        $sheet->setCellValue('A4', 'Finance Department');
        $sheet->setCellValue('B4', 'Finance and accounting team');
        $sheet->setCellValue('C4', 'active');

        // Instructions sheet
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');
        $instructionSheet->setCellValue('A1', 'INSTRUCTIONS FOR BULK IMPORT');
        $instructionSheet->setCellValue('A3', 'Column A: Sub-Community Name (Required)');
        $instructionSheet->setCellValue('A4', 'Column B: Description (Optional)');
        $instructionSheet->setCellValue('A5', 'Column C: Status (Required) - Values: active or inactive');
        $instructionSheet->setCellValue('A7', 'Notes:');
        $instructionSheet->setCellValue('A8', '- Do not modify the header row');
        $instructionSheet->setCellValue('A9', '- Sub-Community Name must be unique');
        $instructionSheet->setCellValue('A10', '- Status can only be "active" or "inactive"');
        $instructionSheet->setCellValue('A11', '- Delete the sample rows before uploading');

        $filename = 'sub_communities_sample.xlsx';

    } else {
        // Members Sample with dynamic custom fields
        $sheet->setTitle('Members');

        // Get custom fields
        $fieldsQuery = "SELECT field_name, field_label, field_type, is_required, field_options
                        FROM custom_field_definitions
                        WHERE community_id = :community_id AND applies_to = 'member' AND status = 'active'
                        ORDER BY display_order";
        $fieldsStmt = $db->prepare($fieldsQuery);
        $fieldsStmt->bindParam(':community_id', $communityId);
        $fieldsStmt->execute();
        $customFields = $fieldsStmt->fetchAll();

        // Get sub-communities for dropdown
        $subCommQuery = "SELECT sub_community_name FROM sub_communities
                        WHERE community_id = :community_id AND status = 'active'";
        $subCommStmt = $db->prepare($subCommQuery);
        $subCommStmt->bindParam(':community_id', $communityId);
        $subCommStmt->execute();
        $subCommunities = $subCommStmt->fetchAll(PDO::FETCH_COLUMN);

        // Headers
        $col = 'A';
        $sheet->setCellValue($col . '1', 'Full Name');
        $col++;
        $sheet->setCellValue($col . '1', 'Email');
        $col++;
        $sheet->setCellValue($col . '1', 'Phone Number');
        $col++;
        $sheet->setCellValue($col . '1', 'Sub-Community');
        $col++;

        // Custom field headers
        foreach ($customFields as $field) {
            $header = $field['field_label'];
            if ($field['is_required']) {
                $header .= ' *';
            }
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Sample data
        $sheet->setCellValue('A2', 'John Doe');
        $sheet->setCellValue('B2', 'john.doe@example.com');
        $sheet->setCellValue('C2', '1234567890');
        $sheet->setCellValue('D2', count($subCommunities) > 0 ? $subCommunities[0] : 'IT Department');

        // Sample custom field data
        $col = 'E';
        foreach ($customFields as $field) {
            if ($field['field_type'] === 'dropdown' && $field['field_options']) {
                $options = json_decode($field['field_options'], true);
                $sheet->setCellValue($col . '2', $options[0] ?? '');
            } else {
                $sheet->setCellValue($col . '2', 'Sample Value');
            }
            $col++;
        }

        // Instructions sheet
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');
        $instructionSheet->setCellValue('A1', 'INSTRUCTIONS FOR BULK MEMBER IMPORT');
        $instructionSheet->setCellValue('A3', 'Required Columns:');
        $instructionSheet->setCellValue('A4', '- Full Name (Required)');
        $instructionSheet->setCellValue('A5', '- Email (Required, must be valid email)');
        $instructionSheet->setCellValue('A6', '- Sub-Community (Required, must exist)');
        $instructionSheet->setCellValue('A8', 'Optional Columns:');
        $instructionSheet->setCellValue('A9', '- Phone Number');

        $row = 11;
        $instructionSheet->setCellValue('A' . $row, 'Custom Fields:');
        $row++;
        foreach ($customFields as $field) {
            $req = $field['is_required'] ? '(Required)' : '(Optional)';
            $text = "- {$field['field_label']} $req - Type: {$field['field_type']}";
            if ($field['field_type'] === 'dropdown' && $field['field_options']) {
                $options = json_decode($field['field_options'], true);
                $text .= ' - Values: ' . implode(', ', $options);
            }
            $instructionSheet->setCellValue('A' . $row, $text);
            $row++;
        }

        $row += 2;
        $instructionSheet->setCellValue('A' . $row, 'Available Sub-Communities:');
        $row++;
        foreach ($subCommunities as $sc) {
            $instructionSheet->setCellValue('A' . $row, '- ' . $sc);
            $row++;
        }

        $row += 2;
        $instructionSheet->setCellValue('A' . $row, 'Notes:');
        $row++;
        $instructionSheet->setCellValue('A' . $row, '- Do not modify the header row');
        $row++;
        $instructionSheet->setCellValue('A' . $row, '- Email must be unique');
        $row++;
        $instructionSheet->setCellValue('A' . $row, '- One member can only be in one sub-community');
        $row++;
        $instructionSheet->setCellValue('A' . $row, '- Delete the sample row before uploading');

        $filename = 'members_sample.xlsx';
    }

    // Send file to browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Handle Export Current Data
if (isset($_GET['export'])) {
    $type = $_GET['export'];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    if ($type === 'sub_communities') {
        $sheet->setTitle('Sub-Communities');
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Sub-Community Name');
        $sheet->setCellValue('C1', 'Description');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Member Count');

        $query = "SELECT sc.sub_community_id, sc.sub_community_name, sc.description, sc.status,
                  COUNT(scm.user_id) as member_count
                  FROM sub_communities sc
                  LEFT JOIN sub_community_members scm ON sc.sub_community_id = scm.sub_community_id
                  WHERE sc.community_id = :community_id
                  GROUP BY sc.sub_community_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':community_id', $communityId);
        $stmt->execute();
        $data = $stmt->fetchAll();

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['sub_community_id']);
            $sheet->setCellValue('B' . $row, $item['sub_community_name']);
            $sheet->setCellValue('C' . $row, $item['description']);
            $sheet->setCellValue('D' . $row, $item['status']);
            $sheet->setCellValue('E' . $row, $item['member_count']);
            $row++;
        }

        $filename = 'sub_communities_export_' . date('Y-m-d') . '.xlsx';

    } else {
        $sheet->setTitle('Members');

        // Get custom fields
        $fieldsQuery = "SELECT field_id, field_name, field_label
                        FROM custom_field_definitions
                        WHERE community_id = :community_id AND applies_to = 'member' AND status = 'active'
                        ORDER BY display_order";
        $fieldsStmt = $db->prepare($fieldsQuery);
        $fieldsStmt->bindParam(':community_id', $communityId);
        $fieldsStmt->execute();
        $customFields = $fieldsStmt->fetchAll();

        // Headers
        $col = 'A';
        $sheet->setCellValue($col . '1', 'User ID');
        $col++;
        $sheet->setCellValue($col . '1', 'Full Name');
        $col++;
        $sheet->setCellValue($col . '1', 'Email');
        $col++;
        $sheet->setCellValue($col . '1', 'Phone Number');
        $col++;
        $sheet->setCellValue($col . '1', 'Sub-Community');
        $col++;

        foreach ($customFields as $field) {
            $sheet->setCellValue($col . '1', $field['field_label']);
            $col++;
        }

        // Get members with custom data
        $query = "SELECT u.user_id, u.full_name, u.email, u.phone_number, sc.sub_community_name
                  FROM users u
                  JOIN sub_community_members scm ON u.user_id = scm.user_id
                  JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                  WHERE sc.community_id = :community_id
                  ORDER BY u.full_name";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':community_id', $communityId);
        $stmt->execute();
        $members = $stmt->fetchAll();

        $row = 2;
        foreach ($members as $member) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $member['user_id']);
            $col++;
            $sheet->setCellValue($col . $row, $member['full_name']);
            $col++;
            $sheet->setCellValue($col . $row, $member['email']);
            $col++;
            $sheet->setCellValue($col . $row, $member['phone_number']);
            $col++;
            $sheet->setCellValue($col . $row, $member['sub_community_name']);
            $col++;

            // Get custom field values
            foreach ($customFields as $field) {
                $valueQuery = "SELECT field_value FROM member_custom_data
                              WHERE user_id = :user_id AND field_id = :field_id";
                $valueStmt = $db->prepare($valueQuery);
                $valueStmt->bindParam(':user_id', $member['user_id']);
                $valueStmt->bindParam(':field_id', $field['field_id']);
                $valueStmt->execute();
                $value = $valueStmt->fetchColumn();

                $sheet->setCellValue($col . $row, $value ?: '');
                $col++;
            }
            $row++;
        }

        $filename = 'members_export_' . date('Y-m-d') . '.xlsx';
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => '/public/group-admin/community-building.php'],
    ['label' => 'Bulk Operations', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Operations - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .operations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        .operation-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .operation-card h2 {
            margin: 0 0 15px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .operation-card .icon {
            font-size: 2rem;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-download {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-export {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .btn-import {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        .description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .note strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <h1 style="margin: 0 0 10px 0;">📊 Bulk Operations</h1>
        <p style="color: #666; margin: 0 0 30px 0;">Import/Export sub-communities and members using Excel</p>

        <div class="operations-grid">
            <!-- Sub-Communities Operations -->
            <div class="operation-card">
                <h2>
                    <span class="icon">🏘️</span>
                    Sub-Communities
                </h2>
                <p class="description">
                    Download sample Excel format, add multiple sub-communities at once, or export existing data for editing.
                </p>

                <div class="action-buttons">
                    <a href="?download_sample=sub_communities" class="btn btn-download">
                        📥 Download Sample Format
                    </a>
                    <a href="?export=sub_communities" class="btn btn-export">
                        📤 Export Current Data
                    </a>
                    <a href="/public/group-admin/bulk-import.php?type=sub_communities" class="btn btn-import">
                        📤 Upload Excel File
                    </a>
                </div>

                <div class="note">
                    <strong>Note:</strong> You can add, edit, or delete multiple sub-communities at once using Excel.
                </div>
            </div>

            <!-- Members Operations -->
            <div class="operation-card">
                <h2>
                    <span class="icon">👥</span>
                    Members
                </h2>
                <p class="description">
                    Download sample with your custom fields, add multiple members at once, or export existing members for bulk editing.
                </p>

                <div class="action-buttons">
                    <a href="?download_sample=members" class="btn btn-download">
                        📥 Download Sample Format
                    </a>
                    <a href="?export=members" class="btn btn-export">
                        📤 Export Current Data
                    </a>
                    <a href="/public/group-admin/bulk-import.php?type=members" class="btn btn-import">
                        📤 Upload Excel File
                    </a>
                </div>

                <div class="note">
                    <strong>Note:</strong> Sample includes all your custom fields. You can add, edit, or delete members in bulk.
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="operation-card" style="margin-top: 30px;">
            <h2>📖 How to Use Bulk Operations</h2>

            <h3 style="margin-top: 20px;">1. Adding New Data</h3>
            <ol style="line-height: 1.8;">
                <li>Click <strong>"Download Sample Format"</strong></li>
                <li>Open the Excel file</li>
                <li>Delete the sample rows</li>
                <li>Add your data in the provided format</li>
                <li>Save the file</li>
                <li>Click <strong>"Upload Excel File"</strong> and select your file</li>
            </ol>

            <h3 style="margin-top: 20px;">2. Editing Existing Data</h3>
            <ol style="line-height: 1.8;">
                <li>Click <strong>"Export Current Data"</strong></li>
                <li>Open the Excel file</li>
                <li>Edit the data you want to change</li>
                <li>Save the file</li>
                <li>Click <strong>"Upload Excel File"</strong> and select your file</li>
                <li>System will update existing records based on ID/Email</li>
            </ol>

            <h3 style="margin-top: 20px;">3. Deleting Data</h3>
            <ol style="line-height: 1.8;">
                <li>Export current data</li>
                <li>Delete rows you want to remove</li>
                <li>Upload the file</li>
                <li>System will show deleted items for confirmation</li>
                <li>Confirm or undo the deletions</li>
            </ol>
        </div>
    </div>
</body>
</html>
