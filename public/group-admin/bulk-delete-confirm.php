<?php
/**
 * Bulk Delete Confirmation Page
 * Review and confirm bulk deletion with ability to cancel
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

AuthMiddleware::requireRole('group_admin');

$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

if (!isset($_SESSION['pending_delete_ids']) || !isset($_SESSION['delete_type'])) {
    header('Location: /public/group-admin/community-building.php');
    exit();
}

$deleteType = $_SESSION['delete_type'];
$pendingIds = $_SESSION['pending_delete_ids'];
$deletedItems = $_SESSION['deleted_sub_communities'] ?? $_SESSION['deleted_members'] ?? [];

// Handle Confirmation
if (isset($_POST['confirm_delete'])) {
    $database = new Database();
    $db = $database->getConnection();

    try {
        $db->beginTransaction();

        if ($deleteType === 'sub_communities') {
            $placeholders = str_repeat('?,', count($pendingIds) - 1) . '?';
            $query = "DELETE FROM sub_communities
                     WHERE sub_community_id IN ($placeholders) AND community_id = ?";
            $stmt = $db->prepare($query);
            $params = array_merge($pendingIds, [$communityId]);
            $stmt->execute($params);

            $_SESSION['last_deleted'] = [
                'type' => 'sub_communities',
                'items' => $deletedItems,
                'timestamp' => time()
            ];

            $db->commit();
            $_SESSION['success_message'] = count($pendingIds) . " sub-communities deleted successfully. You can undo this action.";

            unset($_SESSION['deleted_sub_communities']);
            unset($_SESSION['pending_delete_ids']);
            unset($_SESSION['delete_type']);

            header('Location: /public/group-admin/sub-communities.php');
            exit();

        } else {
            // Handle members deletion
            $placeholders = str_repeat('?,', count($pendingIds) - 1) . '?';
            $query = "DELETE FROM sub_community_members
                     WHERE user_id IN ($placeholders)
                     AND sub_community_id IN (SELECT sub_community_id FROM sub_communities WHERE community_id = ?)";
            $stmt = $db->prepare($query);
            $params = array_merge($pendingIds, [$communityId]);
            $stmt->execute($params);

            $_SESSION['last_deleted'] = [
                'type' => 'members',
                'items' => $deletedItems,
                'timestamp' => time()
            ];

            $db->commit();
            $_SESSION['success_message'] = count($pendingIds) . " members removed from sub-communities successfully. You can undo this action.";

            unset($_SESSION['deleted_members']);
            unset($_SESSION['pending_delete_ids']);
            unset($_SESSION['delete_type']);

            header('Location: /public/group-admin/community-members.php');
            exit();
        }

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "Failed to delete: " . $e->getMessage();
        header('Location: /public/group-admin/community-building.php');
        exit();
    }
}

// Handle Cancel
if (isset($_POST['cancel_delete'])) {
    unset($_SESSION['deleted_sub_communities']);
    unset($_SESSION['deleted_members']);
    unset($_SESSION['pending_delete_ids']);
    unset($_SESSION['delete_type']);

    $redirectUrl = $deleteType === 'sub_communities' ? '/public/group-admin/sub-communities.php' : '/public/group-admin/community-members.php';
    header('Location: ' . $redirectUrl);
    exit();
}

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => '/public/group-admin/community-building.php'],
    ['label' => 'Confirm Deletion', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Deletion - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .confirmation-card {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .warning-icon {
            text-align: center;
            font-size: 5rem;
            margin-bottom: 20px;
        }
        .items-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .item-row {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .item-row:last-child {
            border-bottom: none;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="container" style="padding: 20px;">
        <div class="confirmation-card">
            <div class="warning-icon">⚠️</div>

            <h1 style="text-align: center; margin: 0 0 10px 0;">Confirm Deletion</h1>
            <p style="text-align: center; color: #666; margin: 0 0 30px 0;">
                You are about to delete <strong><?php echo count($pendingIds); ?></strong>
                <?php echo $deleteType === 'sub_communities' ? 'sub-communities' : 'members'; ?>.
                This action can be undone immediately after deletion.
            </p>

            <div class="items-list">
                <h3 style="margin: 0 0 15px 0;">Items to be deleted:</h3>
                <?php foreach ($deletedItems as $item): ?>
                    <div class="item-row">
                        <?php if ($deleteType === 'sub_communities'): ?>
                            <strong><?php echo htmlspecialchars($item['sub_community_name']); ?></strong>
                            <?php if (!empty($item['description'])): ?>
                                <br><small style="color: #666;"><?php echo htmlspecialchars($item['description']); ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <strong><?php echo htmlspecialchars($item['full_name']); ?></strong>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($item['email']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <strong style="color: #856404;">Note:</strong>
                <p style="margin: 5px 0 0 0; color: #856404;">
                    <?php if ($deleteType === 'sub_communities'): ?>
                        Deleting sub-communities will also remove all associated members and their data.
                    <?php else: ?>
                        Members will be removed from their sub-communities but user accounts will remain.
                    <?php endif; ?>
                    You can undo this deletion immediately after confirming.
                </p>
            </div>

            <form method="POST" action="">
                <div class="action-buttons">
                    <button type="submit" name="confirm_delete" class="btn btn-danger">
                        ✓ Confirm Delete
                    </button>
                    <button type="submit" name="cancel_delete" class="btn btn-secondary">
                        ✕ Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
