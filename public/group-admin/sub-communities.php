<?php
/**
 * Sub-Communities Management
 * View, create, edit, and delete sub-communities
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

// Handle Bulk Delete
if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete' && isset($_POST['selected_items'])) {
    $selectedIds = array_map('intval', $_POST['selected_items']);

    if (count($selectedIds) > 0) {
        // Store for undo
        $deletedItems = [];
        foreach ($selectedIds as $id) {
            $query = "SELECT * FROM sub_communities WHERE sub_community_id = :id AND community_id = :community_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':community_id', $communityId);
            $stmt->execute();
            $item = $stmt->fetch();
            if ($item) {
                $deletedItems[] = $item;
            }
        }

        $_SESSION['deleted_sub_communities'] = $deletedItems;
        $_SESSION['pending_delete_ids'] = $selectedIds;
        $_SESSION['delete_type'] = 'sub_communities';
        header('Location: /public/group-admin/bulk-delete-confirm.php');
        exit();
    }
}

// Handle Undo
if (isset($_GET['undo']) && $_GET['undo'] === 'delete' && isset($_SESSION['last_deleted'])) {
    $lastDeleted = $_SESSION['last_deleted'];
    if ($lastDeleted['type'] === 'sub_communities') {
        try {
            $db->beginTransaction();
            foreach ($lastDeleted['items'] as $item) {
                $insertQuery = "INSERT INTO sub_communities
                               (sub_community_id, community_id, sub_community_name, description, status, created_by, created_at)
                               VALUES (:id, :community_id, :name, :description, :status, :created_by, :created_at)";
                $stmt = $db->prepare($insertQuery);
                $stmt->bindParam(':id', $item['sub_community_id']);
                $stmt->bindParam(':community_id', $item['community_id']);
                $stmt->bindParam(':name', $item['sub_community_name']);
                $stmt->bindParam(':description', $item['description']);
                $stmt->bindParam(':status', $item['status']);
                $stmt->bindParam(':created_by', $item['created_by']);
                $stmt->bindParam(':created_at', $item['created_at']);
                $stmt->execute();
            }
            $db->commit();
            $_SESSION['success_message'] = "Deletion undone! " . count($lastDeleted['items']) . " sub-communities restored.";
            unset($_SESSION['last_deleted']);
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "Failed to undo deletion: " . $e->getMessage();
        }
    }
    header('Location: /public/group-admin/sub-communities.php');
    exit();
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $subCommId = intval($_GET['id']);

    $deleteQuery = "DELETE FROM sub_communities
                    WHERE sub_community_id = :id AND community_id = :community_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $subCommId);
    $deleteStmt->bindParam(':community_id', $communityId);

    if ($deleteStmt->execute()) {
        $_SESSION['success_message'] = "Sub-community deleted successfully";
    } else {
        $_SESSION['error_message'] = "Failed to delete sub-community";
    }
    header('Location: /public/group-admin/sub-communities.php');
    exit();
}

// Get all sub-communities
$query = "SELECT sc.*,
          (SELECT COUNT(*) FROM sub_community_members scm
           WHERE scm.sub_community_id = sc.sub_community_id AND scm.status = 'active') as member_count
          FROM sub_communities sc
          WHERE sc.community_id = :community_id
          ORDER BY sc.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$subCommunities = $stmt->fetchAll();

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => '/public/group-admin/community-building.php'],
    ['label' => 'Sub-Communities', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sub-Communities - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .btn-primary {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .card-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .card-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .member-count {
            color: #667eea;
            font-weight: 600;
        }
        .card-actions {
            display: flex;
            gap: 10px;
        }
        .btn-sm {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
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
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }
        .empty-state .icon {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .bulk-toolbar {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 15px;
        }
        .bulk-toolbar.active {
            display: flex;
        }
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .btn-bulk {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn-bulk-delete {
            background: #dc3545;
            color: white;
        }
        .undo-banner {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-undo {
            padding: 8px 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
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

        <div class="header-actions">
            <div>
                <h1 style="margin: 0 0 5px 0;">🏘️ Sub-Communities</h1>
                <p style="color: #666; margin: 0;">Manage sub-communities within your community</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="/public/group-admin/bulk-operations.php" class="btn-primary" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    📊 Bulk Operations
                </a>
                <a href="/public/group-admin/sub-community-create.php" class="btn-primary">
                    + Create Sub-Community
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['last_deleted']) && $_SESSION['last_deleted']['type'] === 'sub_communities'): ?>
            <div class="undo-banner">
                <div>
                    <strong>✓ <?php echo count($_SESSION['last_deleted']['items']); ?> sub-communities deleted.</strong>
                    <span style="color: #856404; margin-left: 10px;">You can undo this action.</span>
                </div>
                <button onclick="window.location.href='?undo=delete'" class="btn-undo">
                    ↶ Undo Delete
                </button>
            </div>
        <?php endif; ?>

        <?php if (count($subCommunities) > 0): ?>
            <!-- Bulk Actions Toolbar -->
            <div class="bulk-toolbar" id="bulkToolbar">
                <span id="selectedCount">0 selected</span>
                <form method="POST" id="bulkForm" style="margin: 0; display: flex; gap: 10px;">
                    <input type="hidden" name="bulk_action" value="delete">
                    <button type="submit" class="btn-bulk btn-bulk-delete" onclick="return confirm('Are you sure you want to delete the selected sub-communities?');">
                        🗑️ Delete Selected
                    </button>
                </form>
                <button onclick="clearSelection()" class="btn-bulk" style="background: #6c757d; color: white;">
                    Clear Selection
                </button>
            </div>

            <div class="grid">
                <?php foreach ($subCommunities as $subComm): ?>
                    <div class="card">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" class="item-checkbox" name="selected_items[]"
                                   value="<?php echo $subComm['sub_community_id']; ?>"
                                   onchange="updateBulkToolbar()">
                        </div>
                        <div class="card-header">
                            <h3 class="card-title"><?php echo htmlspecialchars($subComm['sub_community_name']); ?></h3>
                            <span class="card-status status-<?php echo $subComm['status']; ?>">
                                <?php echo ucfirst($subComm['status']); ?>
                            </span>
                        </div>
                        <p class="card-description">
                            <?php echo htmlspecialchars($subComm['description'] ?? 'No description provided'); ?>
                        </p>
                        <div class="card-footer">
                            <span class="member-count">
                                👥 <?php echo $subComm['member_count']; ?> Members
                            </span>
                            <div class="card-actions">
                                <a href="/public/group-admin/sub-community-view.php?id=<?php echo $subComm['sub_community_id']; ?>"
                                   class="btn-sm btn-view">View</a>
                                <a href="/public/group-admin/sub-community-edit.php?id=<?php echo $subComm['sub_community_id']; ?>"
                                   class="btn-sm btn-edit">Edit</a>
                                <button onclick="confirmDelete(<?php echo $subComm['sub_community_id']; ?>)"
                                        class="btn-sm btn-delete">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">🏘️</div>
                <h2>No Sub-Communities Yet</h2>
                <p style="color: #666; margin-bottom: 20px;">Create your first sub-community to get started</p>
                <a href="/public/group-admin/sub-community-create.php" class="btn-primary">
                    Create Your First Sub-Community
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this sub-community? This will also remove all associated members and data.')) {
                window.location.href = '/public/group-admin/sub-communities.php?action=delete&id=' + id;
            }
        }

        function updateBulkToolbar() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const toolbar = document.getElementById('bulkToolbar');
            const countSpan = document.getElementById('selectedCount');
            const form = document.getElementById('bulkForm');

            if (checkboxes.length > 0) {
                toolbar.classList.add('active');
                countSpan.textContent = checkboxes.length + ' selected';

                // Remove existing hidden inputs
                form.querySelectorAll('input[name="selected_items[]"]').forEach(input => input.remove());

                // Add selected IDs to form
                checkboxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_items[]';
                    input.value = cb.value;
                    form.appendChild(input);
                });
            } else {
                toolbar.classList.remove('active');
            }
        }

        function clearSelection() {
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
            updateBulkToolbar();
        }
    </script>
</body>
</html>
