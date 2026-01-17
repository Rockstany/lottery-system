<?php
/**
 * Custom Fields Management
 * Define custom fields for members and sub-communities
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/feature-access.php';

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

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $fieldId = intval($_GET['id']);

    $deleteQuery = "DELETE FROM custom_field_definitions
                    WHERE field_id = :id AND community_id = :community_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $fieldId);
    $deleteStmt->bindParam(':community_id', $communityId);

    if ($deleteStmt->execute()) {
        $_SESSION['success_message'] = "Custom field deleted successfully";
    } else {
        $_SESSION['error_message'] = "Failed to delete custom field";
    }
    header('Location: /public/group-admin/community/custom-fields.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fieldLabel = trim($_POST['field_label'] ?? '');
    $fieldName = trim($_POST['field_name'] ?? '');
    $fieldType = $_POST['field_type'] ?? 'text';
    $appliesTo = $_POST['applies_to'] ?? 'member';
    $isRequired = isset($_POST['is_required']) ? 1 : 0;
    $fieldOptions = trim($_POST['field_options'] ?? '');
    $sourceFieldId = !empty($_POST['source_field_id']) ? intval($_POST['source_field_id']) : null;
    $autoPopulateFrom = $_POST['auto_populate_from'] ?? null;

    // Generate field_name from label if empty
    if (empty($fieldName) && !empty($fieldLabel)) {
        $fieldName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $fieldLabel));
    }

    // Validation
    if (empty($fieldLabel) || empty($fieldName)) {
        $_SESSION['error_message'] = "Field label and name are required";
    } elseif ($fieldType === 'sub_community_selector' && $appliesTo !== 'member') {
        $_SESSION['error_message'] = "Sub-Community Selector can only be used for members";
    } elseif ($fieldType === 'auto_populate' && empty($sourceFieldId)) {
        $_SESSION['error_message'] = "Auto-populate fields require a source field";
    } else {
        try {
            // For dropdown, convert options to JSON
            $optionsJson = null;
            if ($fieldType === 'dropdown' && !empty($fieldOptions)) {
                $optionsArray = array_map('trim', explode(',', $fieldOptions));
                $optionsJson = json_encode($optionsArray);
            }

            $insertQuery = "INSERT INTO custom_field_definitions
                            (community_id, field_name, field_label, field_type, field_options, source_field_id, auto_populate_from, is_required, applies_to, created_by)
                            VALUES (:community_id, :field_name, :field_label, :field_type, :field_options, :source_field_id, :auto_populate_from, :is_required, :applies_to, :created_by)";
            $stmt = $db->prepare($insertQuery);
            $stmt->bindParam(':community_id', $communityId);
            $stmt->bindParam(':field_name', $fieldName);
            $stmt->bindParam(':field_label', $fieldLabel);
            $stmt->bindParam(':field_type', $fieldType);
            $stmt->bindParam(':field_options', $optionsJson);
            $stmt->bindParam(':source_field_id', $sourceFieldId);
            $stmt->bindParam(':auto_populate_from', $autoPopulateFrom);
            $stmt->bindParam(':is_required', $isRequired);
            $stmt->bindParam(':applies_to', $appliesTo);
            $stmt->bindParam(':created_by', $userId);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Custom field created successfully";
                header('Location: /public/group-admin/community/custom-fields.php');
                exit();
            } else {
                $_SESSION['error_message'] = "Failed to create custom field";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    }
}

// Get all custom fields
$query = "SELECT * FROM custom_field_definitions
          WHERE community_id = :community_id
          ORDER BY applies_to, display_order, created_at";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$customFields = $stmt->fetchAll();

// Separate by applies_to
$memberFields = array_filter($customFields, fn($f) => $f['applies_to'] === 'member');
$subCommunityFields = array_filter($customFields, fn($f) => $f['applies_to'] === 'sub_community');

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => '/public/group-admin/community/community-building.php'],
    ['label' => 'Custom Fields', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Fields - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .split-view {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        @media (max-width: 968px) {
            .split-view {
                grid-template-columns: 1fr;
            }
        }
        .panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .panel-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .field-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .field-item {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: start;
        }
        .field-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .field-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-type {
            background: #e3f2fd;
            color: #1976d2;
        }
        .badge-required {
            background: #ffebee;
            color: #c62828;
        }
        .badge-optional {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .btn-delete-sm {
            padding: 6px 12px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .form-builder {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .checkbox-wrapper input[type="checkbox"] {
            width: auto;
        }
        .btn-primary {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="content-wrapper">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <h1 style="margin: 0 0 10px 0;">📝 Custom Fields</h1>
        <p style="color: #666; margin: 0 0 30px 0;">Define custom fields for members and sub-communities</p>

        <!-- Form Builder -->
        <div class="form-builder">
            <h2 style="margin: 0 0 20px 0;">Create New Field</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="field_label">Field Label *</label>
                        <input type="text" id="field_label" name="field_label" placeholder="e.g., Mobile Number" required>
                    </div>
                    <div class="form-group">
                        <label for="field_name">Field Name</label>
                        <input type="text" id="field_name" name="field_name" placeholder="e.g., mobile_number">
                        <div class="help-text">Leave empty to auto-generate from label</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="field_type">Field Type *</label>
                        <select id="field_type" name="field_type" onchange="toggleFieldOptions()" required>
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="phone">Phone</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="date">Date</option>
                            <option value="sub_community_selector">Sub-Community Selector</option>
                            <option value="auto_populate">Auto-Populate from Linked Field</option>
                        </select>
                        <div class="help-text" id="field-type-help"></div>
                    </div>
                    <div class="form-group">
                        <label for="applies_to">Applies To *</label>
                        <select id="applies_to" name="applies_to" onchange="toggleFieldOptions()" required>
                            <option value="member">Member</option>
                            <option value="sub_community">Sub-Community</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="options-group" style="display: none;">
                    <label for="field_options">Dropdown Options</label>
                    <textarea id="field_options" name="field_options" rows="3" placeholder="Enter options separated by commas. e.g., IT, HR, Finance, Marketing"></textarea>
                    <div class="help-text">Separate options with commas</div>
                </div>

                <div class="form-group" id="auto-populate-group" style="display: none;">
                    <label for="source_field_id">Source Field *</label>
                    <select id="source_field_id" name="source_field_id">
                        <option value="">-- Select Field to Copy From --</option>
                        <?php foreach ($subCommunityFields as $field): ?>
                            <option value="<?php echo $field['field_id']; ?>" data-applies-to="sub_community">
                                <?php echo htmlspecialchars($field['field_label']); ?> (Sub-Community)
                            </option>
                        <?php endforeach; ?>
                        <?php foreach ($memberFields as $field): ?>
                            <option value="<?php echo $field['field_id']; ?>" data-applies-to="member">
                                <?php echo htmlspecialchars($field['field_label']); ?> (Member)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="auto_populate_from" name="auto_populate_from">
                    <div class="help-text">This field will automatically copy value from the selected field</div>
                </div>

                <div class="form-group">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="is_required" name="is_required" value="1">
                        <label for="is_required" style="margin: 0;">Required Field</label>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Create Field</button>
            </form>
        </div>

        <!-- Field Lists -->
        <div class="split-view" style="margin-top: 30px;">
            <!-- Member Fields -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">👤 Member Fields</h3>
                    <span style="background: #e3f2fd; color: #1976d2; padding: 5px 12px; border-radius: 12px; font-size: 0.85rem;">
                        <?php echo count($memberFields); ?> fields
                    </span>
                </div>
                <div class="field-list">
                    <?php if (count($memberFields) > 0): ?>
                        <?php foreach ($memberFields as $field): ?>
                            <div class="field-item">
                                <div class="field-info">
                                    <h4><?php echo htmlspecialchars($field['field_label']); ?></h4>
                                    <div class="field-meta">
                                        <span class="badge badge-type"><?php echo ucfirst(str_replace('_', ' ', $field['field_type'])); ?></span>
                                        <span class="badge <?php echo $field['is_required'] ? 'badge-required' : 'badge-optional'; ?>">
                                            <?php echo $field['is_required'] ? 'Required' : 'Optional'; ?>
                                        </span>
                                    </div>
                                    <?php if ($field['field_type'] === 'dropdown' && $field['field_options']): ?>
                                        <div class="help-text" style="margin-top: 8px;">
                                            Options: <?php echo htmlspecialchars(implode(', ', json_decode($field['field_options']))); ?>
                                        </div>
                                    <?php elseif ($field['field_type'] === 'auto_populate' && $field['source_field_id']): ?>
                                        <?php
                                            $sourceQuery = "SELECT field_label FROM custom_field_definitions WHERE field_id = :fid";
                                            $sourceStmt = $db->prepare($sourceQuery);
                                            $sourceStmt->bindParam(':fid', $field['source_field_id']);
                                            $sourceStmt->execute();
                                            $sourceLabel = $sourceStmt->fetchColumn();
                                        ?>
                                        <div class="help-text" style="margin-top: 8px;">
                                            Auto-populated from: <strong><?php echo htmlspecialchars($sourceLabel); ?></strong> (<?php echo ucfirst($field['auto_populate_from']); ?>)
                                        </div>
                                    <?php elseif ($field['field_type'] === 'sub_community_selector'): ?>
                                        <div class="help-text" style="margin-top: 8px;">
                                            Allows member to select their sub-community
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button onclick="confirmDelete(<?php echo $field['field_id']; ?>)" class="btn-delete-sm">Delete</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No member fields defined yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sub-Community Fields -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">🏘️ Sub-Community Fields</h3>
                    <span style="background: #e3f2fd; color: #1976d2; padding: 5px 12px; border-radius: 12px; font-size: 0.85rem;">
                        <?php echo count($subCommunityFields); ?> fields
                    </span>
                </div>
                <div class="field-list">
                    <?php if (count($subCommunityFields) > 0): ?>
                        <?php foreach ($subCommunityFields as $field): ?>
                            <div class="field-item">
                                <div class="field-info">
                                    <h4><?php echo htmlspecialchars($field['field_label']); ?></h4>
                                    <div class="field-meta">
                                        <span class="badge badge-type"><?php echo ucfirst($field['field_type']); ?></span>
                                        <span class="badge <?php echo $field['is_required'] ? 'badge-required' : 'badge-optional'; ?>">
                                            <?php echo $field['is_required'] ? 'Required' : 'Optional'; ?>
                                        </span>
                                    </div>
                                    <?php if ($field['field_type'] === 'dropdown' && $field['field_options']): ?>
                                        <div class="help-text" style="margin-top: 8px;">
                                            Options: <?php echo htmlspecialchars(implode(', ', json_decode($field['field_options']))); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button onclick="confirmDelete(<?php echo $field['field_id']; ?>)" class="btn-delete-sm">Delete</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No sub-community fields defined yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleFieldOptions() {
            const fieldType = document.getElementById('field_type').value;
            const appliesTo = document.getElementById('applies_to').value;
            const optionsGroup = document.getElementById('options-group');
            const autoPopulateGroup = document.getElementById('auto-populate-group');
            const fieldTypeHelp = document.getElementById('field-type-help');

            // Hide all groups first
            optionsGroup.style.display = 'none';
            autoPopulateGroup.style.display = 'none';
            fieldTypeHelp.textContent = '';

            // Show appropriate group based on field type
            if (fieldType === 'dropdown') {
                optionsGroup.style.display = 'block';
            } else if (fieldType === 'auto_populate') {
                autoPopulateGroup.style.display = 'block';
                fieldTypeHelp.textContent = 'This field will automatically copy data from another field';
            } else if (fieldType === 'sub_community_selector') {
                fieldTypeHelp.textContent = 'Allows members to select which sub-community they belong to';
                if (appliesTo !== 'member') {
                    alert('Sub-Community Selector can only be used for members');
                    document.getElementById('applies_to').value = 'member';
                }
            }
        }

        // Update auto_populate_from when source field changes
        document.addEventListener('DOMContentLoaded', function() {
            const sourceFieldSelect = document.getElementById('source_field_id');
            const autoPopulateFromInput = document.getElementById('auto_populate_from');

            sourceFieldSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    autoPopulateFromInput.value = selectedOption.getAttribute('data-applies-to');
                } else {
                    autoPopulateFromInput.value = '';
                }
            });
        });

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this field? All associated data will be lost.')) {
                window.location.href = '/public/group-admin/community/custom-fields.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>
