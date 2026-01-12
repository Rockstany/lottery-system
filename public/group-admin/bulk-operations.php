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
        // Sub-Communities Sample - ONLY CUSTOM FIELDS
        $sheet->setTitle('Sub-Communities');

        // Get custom fields for sub-communities
        $fieldsQuery = "SELECT field_name, field_label, field_type, is_required, field_options
                        FROM custom_field_definitions
                        WHERE community_id = :community_id AND applies_to = 'sub_community' AND status = 'active'
                        ORDER BY display_order";
        $fieldsStmt = $db->prepare($fieldsQuery);
        $fieldsStmt->bindParam(':community_id', $communityId);
        $fieldsStmt->execute();
        $customFields = $fieldsStmt->fetchAll();

        if (count($customFields) === 0) {
            $_SESSION['error_message'] = "No custom fields defined for sub-communities. Please define custom fields first.";
            header('Location: /public/group-admin/custom-fields.php');
            exit();
        }

        // Headers - ONLY custom fields
        $col = 'A';
        foreach ($customFields as $field) {
            $header = $field['field_label'];
            if ($field['is_required']) {
                $header .= ' *';
            }
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Sample data row
        $col = 'A';
        foreach ($customFields as $field) {
            if ($field['field_type'] === 'dropdown' && $field['field_options']) {
                $options = json_decode($field['field_options'], true);
                $sheet->setCellValue($col . '2', $options[0] ?? 'Sample Value');
            } else {
                $sheet->setCellValue($col . '2', 'Sample Value');
            }
            $col++;
        }

        // Instructions sheet
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');
        $instructionSheet->setCellValue('A1', 'INSTRUCTIONS FOR BULK IMPORT - SUB-COMMUNITIES');
        $instructionSheet->setCellValue('A3', 'Custom Fields:');

        $row = 4;
        foreach ($customFields as $idx => $field) {
            $colLetter = chr(65 + $idx); // A, B, C...
            $req = $field['is_required'] ? '(Required)' : '(Optional)';
            $text = "Column $colLetter: {$field['field_label']} $req - Type: {$field['field_type']}";
            if ($field['field_type'] === 'dropdown' && $field['field_options']) {
                $options = json_decode($field['field_options'], true);
                $text .= ' - Values: ' . implode(', ', $options);
            }
            $instructionSheet->setCellValue('A' . $row, $text);
            $row++;
        }

        $row += 2;
        $instructionSheet->setCellValue('A' . $row, 'Notes:');
        $row++;
        $instructionSheet->setCellValue('A' . $row, '- Do not modify the header row');
        $row++;
        $instructionSheet->setCellValue('A' . $row, '- Fill in all required fields (marked with *)');
        $row++;
        $instructionSheet->setCellValue('A' . $row, '- Delete the sample row before uploading');

        $filename = 'sub_communities_sample.xlsx';

    } else {
        // Members Sample with dynamic custom fields ONLY
        $sheet->setTitle('Members');

        // Get custom fields for members
        $fieldsQuery = "SELECT field_name, field_label, field_type, is_required, field_options
                        FROM custom_field_definitions
                        WHERE community_id = :community_id AND applies_to = 'member' AND status = 'active'
                        ORDER BY display_order";
        $fieldsStmt = $db->prepare($fieldsQuery);
        $fieldsStmt->bindParam(':community_id', $communityId);
        $fieldsStmt->execute();
        $customFields = $fieldsStmt->fetchAll();

        // Check if custom fields exist
        if (count($customFields) === 0) {
            $_SESSION['error_message'] = "No custom fields defined for members. Please create custom fields first.";
            header('Location: /public/group-admin/custom-fields.php');
            exit();
        }

        // Headers - ONLY custom fields
        $col = 'A';
        foreach ($customFields as $field) {
            $header = $field['field_label'];
            if ($field['is_required']) {
                $header .= ' *';
            }
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Sample data - ONLY custom fields
        $col = 'A';
        foreach ($customFields as $field) {
            if ($field['field_type'] === 'dropdown' && $field['field_options']) {
                $options = json_decode($field['field_options'], true);
                $sheet->setCellValue($col . '2', $options[0] ?? '');
            } elseif ($field['field_type'] === 'sub_community_selector') {
                // For sub-community selector, show example sub-community name
                $subCommQuery = "SELECT sub_community_name FROM sub_communities
                               WHERE community_id = :community_id AND status = 'active'
                               LIMIT 1";
                $subCommStmt = $db->prepare($subCommQuery);
                $subCommStmt->bindParam(':community_id', $communityId);
                $subCommStmt->execute();
                $sampleSubComm = $subCommStmt->fetchColumn();
                $sheet->setCellValue($col . '2', $sampleSubComm ?: 'Sub-Community Name');
            } elseif ($field['field_type'] === 'auto_populate') {
                // For auto-populate, indicate it can be left empty
                $sheet->setCellValue($col . '2', '(auto-fills)');
            } else {
                $sheet->setCellValue($col . '2', 'Sample Value');
            }
            $col++;
        }

        // Instructions sheet
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');
        $instructionSheet->setCellValue('A1', 'INSTRUCTIONS FOR BULK MEMBER IMPORT');
        $instructionSheet->setCellValue('A3', 'Custom Fields (defined by Group Admin):');

        $row = 4;
        $hasSubCommSelector = false;
        $hasAutoPopulate = false;

        foreach ($customFields as $field) {
            $req = $field['is_required'] ? '(Required)' : '(Optional)';
            $text = "- {$field['field_label']} $req - Type: {$field['field_type']}";

            if ($field['field_type'] === 'dropdown' && $field['field_options']) {
                $options = json_decode($field['field_options'], true);
                $text .= ' - Values: ' . implode(', ', $options);
            } elseif ($field['field_type'] === 'sub_community_selector') {
                $text .= ' - Enter sub-community name exactly as it appears';
                $hasSubCommSelector = true;
            } elseif ($field['field_type'] === 'auto_populate') {
                $text .= ' - Can be left empty (will auto-fill from sub-community)';
                $hasAutoPopulate = true;
            }

            $instructionSheet->setCellValue('A' . $row, $text);
            $row++;
        }

        $row += 2;
        $instructionSheet->setCellValue('A' . $row, 'Notes:');
        $row++;
        $instructionSheet->setCellValue('A' . $row, '- Do not modify the header row');
        $row++;
        $instructionSheet->setCellValue('A' . $row, '- Fill in all required fields (marked with *)');
        $row++;

        if ($hasSubCommSelector) {
            $instructionSheet->setCellValue('A' . $row, '- For Sub-Community Selector: Enter the exact sub-community name');
            $row++;
        }

        if ($hasAutoPopulate) {
            $instructionSheet->setCellValue('A' . $row, '- For Auto-Populate fields: Leave empty or enter custom value');
            $row++;
        }

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

        // Get custom fields for sub-communities
        $fieldsQuery = "SELECT field_id, field_name, field_label
                        FROM custom_field_definitions
                        WHERE community_id = :community_id AND applies_to = 'sub_community' AND status = 'active'
                        ORDER BY display_order";
        $fieldsStmt = $db->prepare($fieldsQuery);
        $fieldsStmt->bindParam(':community_id', $communityId);
        $fieldsStmt->execute();
        $customFields = $fieldsStmt->fetchAll();

        // Headers - ONLY custom fields
        $col = 'A';
        foreach ($customFields as $field) {
            $sheet->setCellValue($col . '1', $field['field_label']);
            $col++;
        }

        // Get sub-communities
        $query = "SELECT sub_community_id FROM sub_communities
                  WHERE community_id = :community_id
                  ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':community_id', $communityId);
        $stmt->execute();
        $subCommunities = $stmt->fetchAll();

        $row = 2;
        foreach ($subCommunities as $subComm) {
            $col = 'A';

            // Get custom field values
            foreach ($customFields as $field) {
                $valueQuery = "SELECT field_value FROM sub_community_custom_data
                              WHERE sub_community_id = :sub_community_id AND field_id = :field_id";
                $valueStmt = $db->prepare($valueQuery);
                $valueStmt->bindParam(':sub_community_id', $subComm['sub_community_id']);
                $valueStmt->bindParam(':field_id', $field['field_id']);
                $valueStmt->execute();
                $value = $valueStmt->fetchColumn();

                $sheet->setCellValue($col . $row, $value ?: '');
                $col++;
            }
            $row++;
        }

        $filename = 'sub_communities_export_' . date('Y-m-d') . '.xlsx';

    } else {
        $sheet->setTitle('Members');

        // Get custom fields for members
        $fieldsQuery = "SELECT field_id, field_name, field_label
                        FROM custom_field_definitions
                        WHERE community_id = :community_id AND applies_to = 'member' AND status = 'active'
                        ORDER BY display_order";
        $fieldsStmt = $db->prepare($fieldsQuery);
        $fieldsStmt->bindParam(':community_id', $communityId);
        $fieldsStmt->execute();
        $customFields = $fieldsStmt->fetchAll();

        // Headers - ONLY custom fields
        $col = 'A';
        foreach ($customFields as $field) {
            $sheet->setCellValue($col . '1', $field['field_label']);
            $col++;
        }

        // Get members from sub_community_members
        $query = "SELECT DISTINCT scm.user_id
                  FROM sub_community_members scm
                  JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                  WHERE sc.community_id = :community_id
                  ORDER BY scm.user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':community_id', $communityId);
        $stmt->execute();
        $members = $stmt->fetchAll();

        $row = 2;
        foreach ($members as $member) {
            $col = 'A';

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
