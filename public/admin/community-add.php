<?php
/**
 * Community Management - Add New Community
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

require_once __DIR__ . '/../../src/models/Community.php';
require_once __DIR__ . '/../../src/models/User.php';

$communityModel = new Community();
$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $errors = [];

    if (empty($_POST['community_name'])) {
        $errors[] = "Community name is required";
    }

    if (empty($errors)) {
        $data = [
            'community_name' => trim($_POST['community_name']),
            'address' => trim($_POST['address'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'state' => trim($_POST['state'] ?? ''),
            'pincode' => trim($_POST['pincode'] ?? ''),
            'created_by' => $_SESSION['user_id'],
            'status' => $_POST['status'] ?? 'active'
        ];

        $communityId = $communityModel->create($data);

        if ($communityId) {
            // If group admin is selected, assign them
            if (!empty($_POST['group_admin_id'])) {
                $communityModel->assignGroupAdmin($communityId, $_POST['group_admin_id'], $_SESSION['user_id']);
            }

            header('Location: /public/admin/communities.php?success=created');
            exit;
        } else {
            $errors[] = "Failed to create community";
        }
    }
}

// Get available group admins (not assigned to any community)
$availableAdmins = $userModel->getAll(1, 1000, 'group_admin');
$unassignedAdmins = [];

foreach ($availableAdmins as $admin) {
    $communityId = $userModel->getCommunityId($admin['user_id']);
    if (!$communityId) {
        $unassignedAdmins[] = $admin;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Community - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .nav-menu {
            background: var(--white);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        .nav-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }
        .nav-menu a {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
        }
        .nav-menu a:hover {
            background: var(--gray-100);
            text-decoration: none;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo APP_NAME; ?> - Add Community</h1>
        </div>
    </div>

    <div class="container main-content">
        <nav class="nav-menu">
            <ul>
                <li><a href="/public/admin/dashboard.php">Dashboard</a></li>
                <li><a href="/public/admin/users.php">Manage Users</a></li>
                <li><a href="/public/admin/communities.php" style="font-weight: 600;">Manage Communities</a></li>
                <li><a href="/public/logout.php">Logout</a></li>
            </ul>
        </nav>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create New Community</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="community_name">Community Name *</label>
                        <input type="text"
                               id="community_name"
                               name="community_name"
                               class="form-control"
                               placeholder="e.g., St. Mary's Parish"
                               value="<?php echo htmlspecialchars($_POST['community_name'] ?? ''); ?>"
                               required>
                        <small class="form-text">Enter the name of the community or parish</small>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address"
                                  name="address"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Enter full address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text"
                                   id="city"
                                   name="city"
                                   class="form-control"
                                   placeholder="e.g., Mumbai"
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text"
                                   id="state"
                                   name="state"
                                   class="form-control"
                                   placeholder="e.g., Maharashtra"
                                   value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="pincode">Pincode</label>
                            <input type="text"
                                   id="pincode"
                                   name="pincode"
                                   class="form-control"
                                   placeholder="e.g., 400001"
                                   maxlength="10"
                                   value="<?php echo htmlspecialchars($_POST['pincode'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" <?php echo (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="group_admin_id">Assign Group Admin (Optional)</label>
                        <select id="group_admin_id" name="group_admin_id" class="form-control">
                            <option value="">-- No Assignment (Assign Later) --</option>
                            <?php foreach ($unassignedAdmins as $admin): ?>
                                <option value="<?php echo $admin['user_id']; ?>"
                                        <?php echo (($_POST['group_admin_id'] ?? '') == $admin['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin['full_name']); ?>
                                    (<?php echo htmlspecialchars($admin['mobile_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">
                            Only showing Group Admins not assigned to any community.
                            <?php if (empty($unassignedAdmins)): ?>
                                <strong>All Group Admins are currently assigned.</strong>
                            <?php endif; ?>
                        </small>
                    </div>

                    <div class="form-actions" style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-md);">
                        <button type="submit" class="btn btn-primary">Create Community</button>
                        <a href="/public/admin/communities.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
