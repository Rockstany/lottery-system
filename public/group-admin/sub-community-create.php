<?php
/**
 * Create Sub-Community
 * Form to create a new sub-community
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subCommName = trim($_POST['sub_community_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // Validation
    if (empty($subCommName)) {
        $_SESSION['error_message'] = "Sub-community name is required";
    } else {
        try {
            $insertQuery = "INSERT INTO sub_communities
                            (community_id, sub_community_name, description, status, created_by)
                            VALUES (:community_id, :name, :description, :status, :created_by)";
            $stmt = $db->prepare($insertQuery);
            $stmt->bindParam(':community_id', $communityId);
            $stmt->bindParam(':name', $subCommName);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':created_by', $userId);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Sub-community created successfully";
                header('Location: /public/group-admin/sub-communities.php');
                exit();
            } else {
                $_SESSION['error_message'] = "Failed to create sub-community";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    }
}

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => '/public/group-admin/community-building.php'],
    ['label' => 'Sub-Communities', 'url' => '/public/group-admin/sub-communities.php'],
    ['label' => 'Create', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Sub-Community - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .form-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
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
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
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
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="container" style="padding: 20px;">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h1 style="margin: 0 0 10px 0;">Create Sub-Community</h1>
            <p style="color: #666; margin: 0 0 30px 0;">Fill in the details to create a new sub-community</p>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="sub_community_name">
                        Sub-Community Name <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="sub_community_name"
                           name="sub_community_name"
                           placeholder="Enter sub-community name"
                           required
                           value="<?php echo htmlspecialchars($_POST['sub_community_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description"
                              name="description"
                              placeholder="Enter a brief description of this sub-community"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active" <?php echo (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>
                            Active
                        </option>
                        <option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Sub-Community</button>
                    <a href="/public/group-admin/sub-communities.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
