<?php
/**
 * Register New Member
 * Register member with dynamic custom fields and assign to sub-community
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

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

// Check if user wants to use existing database (lottery system users)
$useExisting = isset($_GET['use_existing']) && $_GET['use_existing'] === '1';

// Get existing users if needed
$existingUsers = [];
if ($useExisting) {
    $usersQuery = "SELECT user_id, full_name, email, phone_number FROM users
                   WHERE community_id = :community_id AND role = 'member'
                   AND user_id NOT IN (SELECT user_id FROM sub_community_members)
                   ORDER BY full_name";
    $usersStmt = $db->prepare($usersQuery);
    $usersStmt->bindParam(':community_id', $communityId);
    $usersStmt->execute();
    $existingUsers = $usersStmt->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subCommunityId = intval($_POST['sub_community_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $existingUserId = isset($_POST['existing_user_id']) ? intval($_POST['existing_user_id']) : null;

    // Validation
    $errors = [];
    if ($subCommunityId <= 0) {
        $errors[] = "Please select a sub-community";
    }

    if ($existingUserId) {
        // Using existing user
        $newUserId = $existingUserId;
    } else {
        // Creating new user
        if (empty($fullName)) {
            $errors[] = "Full name is required";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }
    }

    // Validate custom fields
    foreach ($customFields as $field) {
        $fieldValue = $_POST['custom_field_' . $field['field_id']] ?? '';
        if ($field['is_required'] && empty($fieldValue)) {
            $errors[] = $field['field_label'] . " is required";
        }
    }

    if (count($errors) === 0) {
        try {
            $db->beginTransaction();

            // Create new user if not using existing
            if (!$existingUserId) {
                // Generate password
                $defaultPassword = bin2hex(random_bytes(8));
                $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

                $insertUserQuery = "INSERT INTO users
                                   (community_id, full_name, email, phone_number, password_hash, role, status)
                                   VALUES (:community_id, :full_name, :email, :phone_number, :password_hash, 'member', 'active')";
                $insertUserStmt = $db->prepare($insertUserQuery);
                $insertUserStmt->bindParam(':community_id', $communityId);
                $insertUserStmt->bindParam(':full_name', $fullName);
                $insertUserStmt->bindParam(':email', $email);
                $insertUserStmt->bindParam(':phone_number', $phoneNumber);
                $insertUserStmt->bindParam(':password_hash', $passwordHash);
                $insertUserStmt->execute();

                $newUserId = $db->lastInsertId();
            }

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
                $fieldValue = $_POST['custom_field_' . $field['field_id']] ?? '';
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
            header('Location: /public/group-admin/community-members.php');
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
    ['label' => 'Community Building', 'url' => '/public/group-admin/community-building.php'],
    ['label' => 'Members', 'url' => '/public/group-admin/community-members.php'],
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
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

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
                    <a href="/public/group-admin/member-register.php"
                       class="source-btn <?php echo !$useExisting ? 'active' : ''; ?>">
                        📝 Create New Member
                    </a>
                    <a href="/public/group-admin/member-register.php?use_existing=1"
                       class="source-btn <?php echo $useExisting ? 'active' : ''; ?>">
                        👥 Use Existing Database
                    </a>
                </div>
                <p class="help-text" style="margin-top: 10px;">
                    Select members from lottery system or create new ones
                </p>
            </div>

            <form method="POST" action="">
                <!-- Sub-Community Selection -->
                <div class="form-group">
                    <label for="sub_community_id">
                        Select Sub-Community <span class="required">*</span>
                    </label>
                    <select id="sub_community_id" name="sub_community_id" required>
                        <option value="">-- Select Sub-Community --</option>
                        <?php foreach ($subCommunities as $sc): ?>
                            <option value="<?php echo $sc['sub_community_id']; ?>">
                                <?php echo htmlspecialchars($sc['sub_community_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($useExisting && count($existingUsers) > 0): ?>
                    <!-- Existing User Selection -->
                    <div class="section-title">Select Existing User</div>
                    <div class="form-group">
                        <label for="existing_user_id">
                            Existing User <span class="required">*</span>
                        </label>
                        <select id="existing_user_id" name="existing_user_id" required>
                            <option value="">-- Select User --</option>
                            <?php foreach ($existingUsers as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($useExisting): ?>
                    <div class="alert alert-error">
                        No existing users available. All users are already assigned to sub-communities or you don't have any users yet.
                        <a href="/public/group-admin/member-register.php">Create a new member instead</a>.
                    </div>
                <?php else: ?>
                    <!-- New User Creation -->
                    <div class="section-title">Basic Information</div>

                    <div class="form-group">
                        <label for="full_name">
                            Full Name <span class="required">*</span>
                        </label>
                        <input type="text" id="full_name" name="full_name"
                               placeholder="Enter full name" required
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            Email <span class="required">*</span>
                        </label>
                        <input type="email" id="email" name="email"
                               placeholder="Enter email address" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number"
                               placeholder="Enter phone number"
                               value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                    </div>
                <?php endif; ?>

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
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Register Member</button>
                    <a href="/public/group-admin/community-members.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
