<?php
/**
 * Feature Management - Admin Panel
 * Manage which features are enabled for each community
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

AuthMiddleware::requireRole('admin');

$userId = AuthMiddleware::getUserId();
$userName = $_SESSION['full_name'] ?? 'Admin';
$database = new Database();
$db = $database->getConnection();

// Get community ID from URL
$communityId = isset($_GET['community_id']) ? intval($_GET['community_id']) : 1;

// Get community details
$query = "SELECT community_name, status FROM communities WHERE community_id = :cid";
$stmt = $db->prepare($query);
$stmt->bindParam(':cid', $communityId);
$stmt->execute();
$community = $stmt->fetch(PDO::FETCH_ASSOC);

$communityName = $community ? $community['community_name'] : 'Unknown Community';
$communityStatus = $community ? $community['status'] : 'unknown';

// Success/Error messages
$successMessage = '';
$errorMessage = '';

// Handle toggle
if (isset($_GET['toggle'])) {
    $featureId = intval($_GET['feature_id']);
    $action = $_GET['toggle'];

    if ($action === 'enable') {
        $query = "INSERT INTO community_features (community_id, feature_id, is_enabled, enabled_by, enabled_date)
                  VALUES (:cid, :fid, 1, :uid, NOW())
                  ON DUPLICATE KEY UPDATE is_enabled = 1, enabled_by = :uid, enabled_date = NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cid', $communityId);
        $stmt->bindParam(':fid', $featureId);
        $stmt->bindParam(':uid', $userId);

        if ($stmt->execute()) {
            $successMessage = "Feature enabled successfully! Group Admin can now access this feature.";
        } else {
            $errorMessage = "Failed to enable feature. Please try again.";
        }
    } elseif ($action === 'disable') {
        $query = "UPDATE community_features
                  SET is_enabled = 0, disabled_by = :uid, disabled_date = NOW()
                  WHERE community_id = :cid AND feature_id = :fid";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cid', $communityId);
        $stmt->bindParam(':fid', $featureId);
        $stmt->bindParam(':uid', $userId);

        if ($stmt->execute()) {
            $successMessage = "Feature disabled successfully! Group Admin can no longer access this feature.";
        } else {
            $errorMessage = "Failed to disable feature. Please try again.";
        }
    }
}

// Get all features
$query = "SELECT * FROM features WHERE is_active = 1 ORDER BY display_order ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$features = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all communities for dropdown
$query = "SELECT community_id, community_name, status FROM communities ORDER BY community_name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$allCommunities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature Management - <?php echo htmlspecialchars($communityName); ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .header h1 {
            color: white;
            margin: 0 0 var(--spacing-sm) 0;
            font-size: 32px;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .community-selector {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-md);
        }

        .community-selector label {
            display: block;
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
            color: var(--gray-700);
        }

        .community-selector select {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: 16px;
            background: white;
            cursor: pointer;
        }

        .alert {
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .feature-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .feature-card.enabled {
            border-color: var(--success-color);
            background: linear-gradient(to bottom, rgba(46, 204, 113, 0.05), white);
        }

        .feature-card.disabled {
            border-color: var(--gray-300);
        }

        .feature-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .feature-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .feature-icon {
            font-size: 48px;
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: var(--radius-lg);
            color: white;
        }

        .feature-info {
            flex: 1;
        }

        .feature-name {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 var(--spacing-xs) 0;
        }

        .feature-key {
            font-size: 13px;
            color: var(--gray-500);
            font-family: monospace;
            background: var(--gray-100);
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .feature-description {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: var(--spacing-lg);
        }

        .feature-status {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-weight: 600;
        }

        .feature-status.enabled {
            background: #d4edda;
            color: #155724;
        }

        .feature-status.disabled {
            background: #f8f9fa;
            color: #6c757d;
        }

        .status-icon {
            font-size: 24px;
        }

        .feature-actions {
            display: flex;
            gap: var(--spacing-md);
        }

        .btn {
            padding: var(--spacing-md) var(--spacing-xl);
            border-radius: var(--radius-md);
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .btn-enable {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            flex: 1;
        }

        .btn-enable:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }

        .btn-disable {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            flex: 1;
        }

        .btn-disable:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .btn-back {
            background: var(--gray-200);
            color: var(--gray-700);
            padding: var(--spacing-md) var(--spacing-lg);
        }

        .btn-back:hover {
            background: var(--gray-300);
        }

        .nav-menu {
            background: white;
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }

        .nav-menu a {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            transition: background var(--transition-fast);
            text-decoration: none;
            color: var(--gray-700);
            display: inline-block;
        }

        .nav-menu a:hover {
            background: var(--gray-100);
        }

        .empty-state {
            text-align: center;
            padding: var(--spacing-3xl);
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .empty-state-icon {
            font-size: 72px;
            margin-bottom: var(--spacing-lg);
        }

        .empty-state h3 {
            font-size: 24px;
            color: var(--gray-700);
            margin-bottom: var(--spacing-md);
        }

        .empty-state p {
            color: var(--gray-600);
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1>⚙️ Feature Management</h1>
                    <p>Enable or disable features for your communities</p>
                </div>
                <div style="text-align: right;">
                    <p style="margin: 0; font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($userName); ?></p>
                    <p style="margin: 0;">System Administrator</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Navigation -->
        <nav class="nav-menu">
            <a href="/public/admin/dashboard.php">← Back to Dashboard</a>
        </nav>

        <!-- Community Selector -->
        <div class="community-selector">
            <label for="community-select">Select Community to Manage:</label>
            <select id="community-select" onchange="window.location.href='?community_id=' + this.value">
                <?php foreach ($allCommunities as $comm): ?>
                    <option value="<?php echo $comm['community_id']; ?>"
                            <?php echo $comm['community_id'] == $communityId ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($comm['community_name']); ?>
                        <?php echo $comm['status'] !== 'active' ? ' (Inactive)' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <strong>✅ Success!</strong> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <strong>❌ Error!</strong> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Current Community Info -->
        <div class="card" style="margin-bottom: var(--spacing-xl);">
            <div class="card-header">
                <h3 class="card-title">Managing Features for: <?php echo htmlspecialchars($communityName); ?></h3>
            </div>
            <div class="card-body">
                <p style="color: var(--gray-600); margin: 0;">
                    Toggle features on or off for this community. Enabled features will appear as cards on the Group Admin's dashboard.
                    Disabled features will be hidden and inaccessible to the Group Admin.
                </p>
            </div>
        </div>

        <!-- Features Grid -->
        <?php if (empty($features)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3>No Features Available</h3>
                <p>No features found in the system. Please add features to the database first.</p>
            </div>
        <?php else: ?>
            <div class="features-grid">
                <?php foreach ($features as $feature): ?>
                    <?php
                    // Check if enabled for this community
                    $query = "SELECT is_enabled, enabled_date FROM community_features
                              WHERE community_id = :cid AND feature_id = :fid";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':cid', $communityId);
                    $stmt->bindParam(':fid', $feature['feature_id']);
                    $stmt->execute();
                    $status = $stmt->fetch(PDO::FETCH_ASSOC);

                    $isEnabled = $status && $status['is_enabled'] == 1;
                    $enabledDate = $status ? $status['enabled_date'] : null;
                    ?>

                    <div class="feature-card <?php echo $isEnabled ? 'enabled' : 'disabled'; ?>">
                        <div class="feature-header">
                            <div class="feature-icon">
                                <?php echo $feature['feature_icon'] ?? '🎯'; ?>
                            </div>
                            <div class="feature-info">
                                <h3 class="feature-name"><?php echo htmlspecialchars($feature['feature_name']); ?></h3>
                                <span class="feature-key"><?php echo htmlspecialchars($feature['feature_key']); ?></span>
                            </div>
                        </div>

                        <p class="feature-description">
                            <?php echo htmlspecialchars($feature['feature_description'] ?? 'No description available'); ?>
                        </p>

                        <div class="feature-status <?php echo $isEnabled ? 'enabled' : 'disabled'; ?>">
                            <span class="status-icon"><?php echo $isEnabled ? '✓' : '○'; ?></span>
                            <span>
                                <?php if ($isEnabled): ?>
                                    Enabled
                                    <?php if ($enabledDate): ?>
                                        <span style="font-weight: normal; font-size: 13px;">
                                            (<?php echo date('M d, Y', strtotime($enabledDate)); ?>)
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Disabled
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="feature-actions">
                            <?php if ($isEnabled): ?>
                                <a href="?community_id=<?php echo $communityId; ?>&toggle=disable&feature_id=<?php echo $feature['feature_id']; ?>"
                                   class="btn btn-disable"
                                   onclick="return confirm('Disable <?php echo htmlspecialchars($feature['feature_name']); ?>?\n\nThis will remove the feature from the Group Admin\'s dashboard.')">
                                    <span>🚫</span> Disable Feature
                                </a>
                            <?php else: ?>
                                <a href="?community_id=<?php echo $communityId; ?>&toggle=enable&feature_id=<?php echo $feature['feature_id']; ?>"
                                   class="btn btn-enable"
                                   onclick="return confirm('Enable <?php echo htmlspecialchars($feature['feature_name']); ?>?\n\nThis will make the feature available to the Group Admin.')">
                                    <span>✓</span> Enable Feature
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
