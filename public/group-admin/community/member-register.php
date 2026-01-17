<?php
/**
 * Register New Member
 * Register member with dynamic custom fields and assign to sub-community
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

// Get active sub-communities
$subCommQuery = "SELECT sub_community_id, sub_community_name FROM sub_communities
                 WHERE community_id = :community_id AND status = 'active'
                 ORDER BY sub_community_name";
$subCommStmt = $db->prepare($subCommQuery);
$subCommStmt->bindParam(':community_id', $communityId);
$subCommStmt->execute();
$subCommunities = $subCommStmt->fetchAll();

// Get custom fields for members
$fieldsQuery = "SELECT * FROM custom_field_definitions
                WHERE community_id = :community_id AND applies_to = 'member' AND status = 'active'
                ORDER BY display_order, created_at";
$fieldsStmt = $db->prepare($fieldsQuery);
$fieldsStmt->bindParam(':community_id', $communityId);
$fieldsStmt->execute();
$customFields = $fieldsStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subCommunityId = 0;
    $fullName = '';
    $email = '';
    $mobileNumber = '';

    // Check if sub-community selector field exists and get value from it
    $subCommSelectorField = array_filter($customFields, fn($f) => $f['field_type'] === 'sub_community_selector');
    if (count($subCommSelectorField) > 0) {
        $selectorField = reset($subCommSelectorField);
        $subCommunityId = intval($_POST['custom_field_' . $selectorField['field_id']] ?? 0);
    }

    // Extract basic user data from custom fields (needed for user account creation)
    foreach ($customFields as $field) {
        $fieldValue = $_POST['custom_field_' . $field['field_id']] ?? '';

        // Try to identify name field
        if (empty($fullName) && in_array(strtolower($field['field_name']), ['full_name', 'name', 'member_name', 'student_name', 'user_name'])) {
            $fullName = trim($fieldValue);
        }

        // Try to identify email field
        if (empty($email) && in_array(strtolower($field['field_name']), ['email', 'email_address', 'e_mail'])) {
            $email = trim($fieldValue);
        }

        // Try to identify mobile field
        if (empty($mobileNumber) && in_array(strtolower($field['field_name']), ['mobile', 'mobile_number', 'phone', 'phone_number', 'contact', 'contact_number'])) {
            $mobileNumber = trim($fieldValue);
        }
    }

    // Validation
    $errors = [];
    if ($subCommunityId <= 0) {
        $errors[] = "Please select a sub-community (use Sub-Community Selector field)";
    }

    // Validate basic user data needed for account creation
    if (empty($fullName)) {
        $errors[] = "Member name is required (create a text field named 'name', 'full_name', or 'member_name')";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required (create a text field named 'email')";
    }

    // Validate custom fields (skip auto_populate fields as they're auto-filled)
    foreach ($customFields as $field) {
        if ($field['field_type'] === 'auto_populate') {
            continue; // Skip validation for auto-populate fields
        }
        $fieldValue = $_POST['custom_field_' . $field['field_id']] ?? '';
        if ($field['is_required'] && empty($fieldValue)) {
            $errors[] = $field['field_label'] . " is required";
        }
    }

    if (count($errors) === 0) {
        try {
            $db->beginTransaction();

            // Create new user
            // Generate password
            $defaultPassword = bin2hex(random_bytes(8));
            $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

            $insertUserQuery = "INSERT INTO users
                               (community_id, full_name, email, mobile_number, password_hash, role, status)
                               VALUES (:community_id, :full_name, :email, :mobile_number, :password_hash, 'member', 'active')";
            $insertUserStmt = $db->prepare($insertUserQuery);
            $insertUserStmt->bindParam(':community_id', $communityId);
            $insertUserStmt->bindParam(':full_name', $fullName);
            $insertUserStmt->bindParam(':email', $email);
            $insertUserStmt->bindParam(':mobile_number', $mobileNumber);
            $insertUserStmt->bindParam(':password_hash', $passwordHash);
            $insertUserStmt->execute();

            $newUserId = $db->lastInsertId();

            // Assign to sub-community
            $assignQuery = "INSERT INTO sub_community_members
                           (sub_community_id, user_id, assigned_by, status)
                           VALUES (:sub_community_id, :user_id, :assigned_by, 'active')";
            $assignStmt = $db->prepare($assignQuery);
            $assignStmt->bindParam(':sub_community_id', $subCommunityId);
            $assignStmt->bindParam(':user_id', $newUserId);
            $assignStmt->bindParam(':assigned_by', $userId);
            $assignStmt->execute();

            // Save custom field values
            foreach ($customFields as $field) {
                $fieldValue = '';

                // Handle different field types
                if ($field['field_type'] === 'sub_community_selector') {
                    // Store the sub-community ID
                    $fieldValue = $subCommunityId;
                } elseif ($field['field_type'] === 'auto_populate') {
                    // Auto-populate value from source field
                    if ($field['auto_populate_from'] === 'sub_community' && $field['source_field_id']) {
                        // Get value from sub-community's custom field
                        $sourceQuery = "SELECT field_value FROM sub_community_custom_data
                                       WHERE sub_community_id = :sub_community_id AND field_id = :field_id";
                        $sourceStmt = $db->prepare($sourceQuery);
                        $sourceStmt->bindParam(':sub_community_id', $subCommunityId);
                        $sourceStmt->bindParam(':field_id', $field['source_field_id']);
                        $sourceStmt->execute();
                        $fieldValue = $sourceStmt->fetchColumn() ?: '';
                    }
                } else {
                    // Regular field - get from POST
                    $fieldValue = $_POST['custom_field_' . $field['field_id']] ?? '';
                }

                if (!empty($fieldValue)) {
                    $customDataQuery = "INSERT INTO member_custom_data
                                       (user_id, field_id, field_value)
                                       VALUES (:user_id, :field_id, :field_value)
                                       ON DUPLICATE KEY UPDATE field_value = :field_value";
                    $customDataStmt = $db->prepare($customDataQuery);
                    $customDataStmt->bindParam(':user_id', $newUserId);
                    $customDataStmt->bindParam(':field_id', $field['field_id']);
                    $customDataStmt->bindParam(':field_value', $fieldValue);
                    $customDataStmt->execute();
                }
            }

            $db->commit();
            $_SESSION['success_message'] = "Member registered successfully";
            header('Location: /public/group-admin/community/community-members.php');
            exit();

        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = implode('<br>', $errors);
    }
}

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => '/public/group-admin/community/community-building.php'],
    ['label' => 'Members', 'url' => '/public/group-admin/community/community-members.php'],
    ['label' => 'Register', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Member - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .source-selector {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        .source-selector h3 {
            margin: 0 0 15px 0;
        }
        .source-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .source-btn {
            padding: 12px 24px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .source-btn.active {
            background: #667eea;
            color: white;
        }
        .form-group {
            margin-bottom: 25px;
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
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .required {
            color: #dc3545;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/breadcrumb.php'; ?>

    <div class="container" style="padding: 20px;">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php
                echo $_SESSION['error_message']; // Can contain HTML for multiple errors
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h1 style="margin: 0 0 10px 0;">Register New Member</h1>
            <p style="color: #666; margin: 0 0 30px 0;">Add a member to a sub-community</p>

            <!-- Data Source Selector -->
            <div class="source-selector">
                <h3>Choose Data Source</h3>
                <div class="source-buttons">
                    <a href="/public/group-admin/community/member-register.php"
                       class="source-btn <?php echo !$useExisting ? 'active' : ''; ?>">
                        📝 Create New Member
                    </a>
                    <a href="/public/group-admin/community/member-register.php?use_existing=1"
                       class="source-btn <?php echo $useExisting ? 'active' : ''; ?>">
                        👥 Use Existing Database
                    </a>
                </div>
                <p class="help-text" style="margin-top: 10px;">
                    Select members from lottery system or create new ones
                </p>
            </div>

            <form method="POST" action="">
                <!-- Note: All fields are custom fields defined by Group Admin -->

                <!-- Custom Fields -->
                <?php if (count($customFields) > 0): ?>
                    <div class="section-title">Custom Fields</div>
                    <?php foreach ($customFields as $field): ?>
                        <div class="form-group">
                            <label for="custom_field_<?php echo $field['field_id']; ?>">
                                <?php echo htmlspecialchars($field['field_label']); ?>
                                <?php if ($field['is_required']): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>

                            <?php if ($field['field_type'] === 'text'): ?>
                                <input type="text"
                                       id="custom_field_<?php echo $field['field_id']; ?>"
                                       name="custom_field_<?php echo $field['field_id']; ?>"
                                       <?php echo $field['is_required'] ? 'required' : ''; ?>
                                       value="<?php echo htmlspecialchars($_POST['custom_field_' . $field['field_id']] ?? ''); ?>">

                            <?php elseif ($field['field_type'] === 'number'): ?>
                                <input type="number"
                                       id="custom_field_<?php echo $field['field_id']; ?>"
                                       name="custom_field_<?php echo $field['field_id']; ?>"
                                       <?php echo $field['is_required'] ? 'required' : ''; ?>
                                       value="<?php echo htmlspecialchars($_POST['custom_field_' . $field['field_id']] ?? ''); ?>">

                            <?php elseif ($field['field_type'] === 'phone'): ?>
                                <input type="tel"
                                       id="custom_field_<?php echo $field['field_id']; ?>"
                                       name="custom_field_<?php echo $field['field_id']; ?>"
                                       <?php echo $field['is_required'] ? 'required' : ''; ?>
                                       value="<?php echo htmlspecialchars($_POST['custom_field_' . $field['field_id']] ?? ''); ?>">

                            <?php elseif ($field['field_type'] === 'date'): ?>
                                <input type="date"
                                       id="custom_field_<?php echo $field['field_id']; ?>"
                                       name="custom_field_<?php echo $field['field_id']; ?>"
                                       <?php echo $field['is_required'] ? 'required' : ''; ?>
                                       value="<?php echo htmlspecialchars($_POST['custom_field_' . $field['field_id']] ?? ''); ?>">

                            <?php elseif ($field['field_type'] === 'dropdown' && $field['field_options']): ?>
                                <select id="custom_field_<?php echo $field['field_id']; ?>"
                                        name="custom_field_<?php echo $field['field_id']; ?>"
                                        <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                    <option value="">-- Select --</option>
                                    <?php
                                    $options = json_decode($field['field_options'], true);
                                    foreach ($options as $option):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>">
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($field['field_type'] === 'sub_community_selector'): ?>
                                <select id="custom_field_<?php echo $field['field_id']; ?>"
                                        name="custom_field_<?php echo $field['field_id']; ?>"
                                        class="sub-community-selector"
                                        data-field-id="<?php echo $field['field_id']; ?>"
                                        <?php echo $field['is_required'] ? 'required' : ''; ?>
                                        onchange="handleSubCommunityChange(this)">
                                    <option value="">-- Select Sub-Community --</option>
                                    <?php foreach ($subCommunities as $subComm): ?>
                                        <option value="<?php echo $subComm['sub_community_id']; ?>">
                                            <?php echo htmlspecialchars($subComm['sub_community_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color: #666; display: block; margin-top: 5px;">
                                    Select which sub-community this member belongs to
                                </small>

                            <?php elseif ($field['field_type'] === 'auto_populate'): ?>
                                <input type="text"
                                       id="custom_field_<?php echo $field['field_id']; ?>"
                                       name="custom_field_<?php echo $field['field_id']; ?>"
                                       class="auto-populate-field"
                                       data-source-field="<?php echo $field['source_field_id']; ?>"
                                       data-source-type="<?php echo $field['auto_populate_from']; ?>"
                                       readonly
                                       style="background-color: #f5f5f5;"
                                       placeholder="Will auto-populate when sub-community is selected">
                                <small style="color: #666; display: block; margin-top: 5px;">
                                    This field automatically copies data from the selected sub-community
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Register Member</button>
                    <a href="/public/group-admin/community/community-members.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Prepare sub-community data for auto-population
        const subCommunityData = {};

        // Function to fetch and populate auto-populate fields
        async function handleSubCommunityChange(selectElement) {
            const subCommunityId = selectElement.value;

            if (!subCommunityId) {
                // Clear all auto-populate fields
                document.querySelectorAll('.auto-populate-field').forEach(field => {
                    field.value = '';
                });
                return;
            }

            // Find all auto-populate fields
            const autoPopulateFields = document.querySelectorAll('.auto-populate-field');

            for (const field of autoPopulateFields) {
                const sourceFieldId = field.getAttribute('data-source-field');
                const sourceType = field.getAttribute('data-source-type');

                if (sourceType === 'sub_community') {
                    // Fetch value from sub_community_custom_data
                    try {
                        const response = await fetch(`/public/group-admin/get-field-value.php?sub_community_id=${subCommunityId}&field_id=${sourceFieldId}`);
                        const data = await response.json();

                        if (data.success) {
                            field.value = data.value || '';
                        }
                    } catch (error) {
                        console.error('Error fetching auto-populate value:', error);
                    }
                }
            }
        }
    </script>
</body>
</html>
