<?php
/**
 * Community Members Management
 * View and manage members with custom fields
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

// Handle Delete Member from Sub-Community
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['user_id'])) {
    $removeUserId = intval($_GET['user_id']);

    $deleteQuery = "DELETE FROM sub_community_members
                    WHERE user_id = :user_id
                    AND sub_community_id IN (
                        SELECT sub_community_id FROM sub_communities WHERE community_id = :community_id
                    )";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':user_id', $removeUserId);
    $deleteStmt->bindParam(':community_id', $communityId);

    if ($deleteStmt->execute()) {
        $_SESSION['success_message'] = "Member removed from sub-community successfully";
    } else {
        $_SESSION['error_message'] = "Failed to remove member";
    }
    header('Location: /public/group-admin/community/community-members.php');
    exit();
}

// Get all members in this community's sub-communities
$query = "SELECT u.user_id, u.full_name, u.email, u.mobile_number,
          sc.sub_community_name, sc.sub_community_id,
          scm.joined_date, scm.status
          FROM users u
          JOIN sub_community_members scm ON u.user_id = scm.user_id
          JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
          WHERE sc.community_id = :community_id
          ORDER BY u.full_name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$members = $stmt->fetchAll();

// Get all sub-communities for filter
$subCommQuery = "SELECT sub_community_id, sub_community_name FROM sub_communities
                 WHERE community_id = :community_id AND status = 'active'
                 ORDER BY sub_community_name";
$subCommStmt = $db->prepare($subCommQuery);
$subCommStmt->bindParam(':community_id', $communityId);
$subCommStmt->execute();
$subCommunities = $subCommStmt->fetchAll();

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/group-admin/dashboard.php'],
    ['label' => 'Community Building', 'url' => '/public/group-admin/community/community-building.php'],
    ['label' => 'Members', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Members - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
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
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        .filter-group {
            flex: 1;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
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
        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
            margin-right: 5px;
        }
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        .btn-remove {
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
        }
        .empty-state .icon {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/breadcrumb.php'; ?>

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
                <h1 style="margin: 0 0 5px 0;">👤 Community Members</h1>
                <p style="color: #666; margin: 0;">Manage members in sub-communities</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="/public/group-admin/community/member-register.php" class="btn-primary">
                    + Register New Member
                </a>
                <a href="/public/group-admin/member-assign.php" class="btn-primary">
                    📋 Assign Existing Users
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="subComFilter">Filter by Sub-Community</label>
                    <select id="subComFilter" onchange="filterTable()">
                        <option value="">All Sub-Communities</option>
                        <?php foreach ($subCommunities as $sc): ?>
                            <option value="<?php echo $sc['sub_community_id']; ?>">
                                <?php echo htmlspecialchars($sc['sub_community_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="statusFilter">Filter by Status</label>
                    <select id="statusFilter" onchange="filterTable()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <?php if (count($members) > 0): ?>
            <div class="table-container">
                <table id="membersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Sub-Community</th>
                            <th>Joined Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr data-subcomm="<?php echo $member['sub_community_id']; ?>"
                                data-status="<?php echo $member['status']; ?>">
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><?php echo htmlspecialchars($member['mobile_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($member['sub_community_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($member['joined_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $member['status']; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/public/group-admin/member-view.php?id=<?php echo $member['user_id']; ?>"
                                       class="btn-action btn-view">View</a>
                                    <button onclick="confirmRemove(<?php echo $member['user_id']; ?>)"
                                            class="btn-action btn-remove">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">👥</div>
                <h2>No Members Yet</h2>
                <p style="color: #666; margin-bottom: 20px;">Register members or assign existing users to sub-communities</p>
                <a href="/public/group-admin/community/member-register.php" class="btn-primary">
                    Register First Member
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterTable() {
            const subCommFilter = document.getElementById('subComFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#membersTable tbody tr');

            rows.forEach(row => {
                const rowSubComm = row.getAttribute('data-subcomm');
                const rowStatus = row.getAttribute('data-status');

                const subCommMatch = !subCommFilter || rowSubComm === subCommFilter;
                const statusMatch = !statusFilter || rowStatus === statusFilter;

                if (subCommMatch && statusMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function confirmRemove(userId) {
            if (confirm('Are you sure you want to remove this member from their sub-community?')) {
                window.location.href = '/public/group-admin/community/community-members.php?action=remove&user_id=' + userId;
            }
        }
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
