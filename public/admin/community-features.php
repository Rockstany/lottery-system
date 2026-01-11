<?php
/**
 * Admin - Manage Community Features
 * GetToKnow SAAS Platform v4.0
 * Enable/Disable features for communities
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

// Require authentication and Admin role
AuthMiddleware::requireRole('admin');

$userId = AuthMiddleware::getUserId();
$database = new Database();
$db = $database->getConnection();
$featureAccess = new FeatureAccess();

// Get community ID from URL
$communityId = isset($_GET['community_id']) ? intval($_GET['community_id']) : 0;

if (!$communityId) {
    header('Location: /public/admin/communities.php');
    exit;
}

// Get community details
$query = "SELECT * FROM communities WHERE community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$community = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$community) {
    $_SESSION['error_message'] = "Community not found";
    header('Location: /public/admin/communities.php');
    exit;
}

// Handle feature toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $featureId = isset($_POST['feature_id']) ? intval($_POST['feature_id']) : 0;

    if ($_POST['action'] === 'enable') {
        if ($featureAccess->enableFeature($communityId, $featureId, $userId)) {
            $_SESSION['success_message'] = "Feature enabled successfully";
        } else {
            $_SESSION['error_message'] = "Failed to enable feature";
        }
    } elseif ($_POST['action'] === 'disable') {
        if ($featureAccess->disableFeature($communityId, $featureId, $userId)) {
            $_SESSION['success_message'] = "Feature disabled successfully";
        } else {
            $_SESSION['error_message'] = "Failed to disable feature";
        }
    }

    header('Location: /public/admin/community-features.php?community_id=' . $communityId);
    exit;
}

// Get all available features
$allFeatures = $featureAccess->getAllFeatures();

// Get enabled features for this community
$enabledFeatureIds = [];
$query = "SELECT feature_id FROM community_features WHERE community_id = :community_id AND is_enabled = TRUE";
$stmt = $db->prepare($query);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $enabledFeatureIds[] = $row['feature_id'];
}

// Breadcrumb
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '/public/admin/dashboard.php'],
    ['label' => 'Communities', 'url' => '/public/admin/communities.php'],
    ['label' => $community['community_name'], 'url' => '/public/admin/community-edit.php?id=' . $communityId],
    ['label' => 'Features', 'url' => null]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Features - <?php echo htmlspecialchars($community['community_name']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .page-header {
            background: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
            border-bottom: 1px solid #e9ecef;
        }

        .page-header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            color: #2C3E50;
        }

        .page-header p {
            margin: 0;
            color: #6c757d;
        }

        .features-list {
            display: grid;
            gap: var(--spacing-lg);
        }

        .feature-item {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            transition: all 0.2s;
        }

        .feature-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .feature-item.enabled {
            border-left: 4px solid #2ECC71;
        }

        .feature-item.disabled {
            border-left: 4px solid #e9ecef;
            opacity: 0.7;
        }

        .feature-info {
            flex: 1;
        }

        .feature-name {
            font-size: 20px;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 8px;
        }

        .feature-description {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
        }

        .feature-status {
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .status-badge.enabled {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.disabled {
            background: #f8f9fa;
            color: #6c757d;
        }

        .toggle-form {
            margin: 0;
        }

        .btn-toggle {
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-enable {
            background: #2ECC71;
            color: white;
        }

        .btn-enable:hover {
            background: #27ae60;
        }

        .btn-disable {
            background: #e74c3c;
            color: white;
        }

        .btn-disable:hover {
            background: #c0392b;
        }

        .back-link {
            display: inline-block;
            margin-bottom: var(--spacing-lg);
            color: var(--primary-color);
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .feature-item {
                flex-direction: column;
                text-align: center;
            }

            .feature-status {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Include breadcrumb -->
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <div class="container main-content">
        <a href="/public/admin/community-edit.php?id=<?php echo $communityId; ?>" class="back-link">
            ← Back to Community
        </a>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Manage Features</h1>
            <p>Community: <?php echo htmlspecialchars($community['community_name']); ?></p>
        </div>

        <!-- Show success message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Show error message -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Features List -->
        <div class="features-list">
            <?php if (empty($allFeatures)): ?>
                <div class="alert alert-info">
                    <strong>No features available</strong><br>
                    There are no features available in the platform yet.
                </div>
            <?php else: ?>
                <?php foreach ($allFeatures as $feature): ?>
                    <?php
                    $isEnabled = in_array($feature['feature_id'], $enabledFeatureIds);
                    ?>
                    <div class="feature-item <?php echo $isEnabled ? 'enabled' : 'disabled'; ?>">
                        <div class="feature-info">
                            <div class="feature-name">
                                <?php echo htmlspecialchars($feature['feature_name']); ?>
                            </div>
                            <div class="feature-description">
                                <?php echo htmlspecialchars($feature['feature_description']); ?>
                            </div>
                        </div>
                        <div class="feature-status">
                            <div class="status-badge <?php echo $isEnabled ? 'enabled' : 'disabled'; ?>">
                                <?php echo $isEnabled ? '✓ Enabled' : '✗ Disabled'; ?>
                            </div>
                            <form method="POST" class="toggle-form" onsubmit="return confirm('Are you sure you want to <?php echo $isEnabled ? 'disable' : 'enable'; ?> this feature?');">
                                <input type="hidden" name="feature_id" value="<?php echo $feature['feature_id']; ?>">
                                <input type="hidden" name="action" value="<?php echo $isEnabled ? 'disable' : 'enable'; ?>">
                                <button type="submit" class="btn-toggle <?php echo $isEnabled ? 'btn-disable' : 'btn-enable'; ?>">
                                    <?php echo $isEnabled ? 'Disable Feature' : 'Enable Feature'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Information Box -->
        <div class="card" style="margin-top: var(--spacing-xl);">
            <div class="card-body">
                <h3>About Feature Management</h3>
                <p>When you enable a feature for a community:</p>
                <ul>
                    <li>The feature will appear on the Group Admin's dashboard as a feature card</li>
                    <li>The Group Admin can access and use the feature</li>
                    <li>Feature data is preserved when toggling on/off</li>
                </ul>
                <p>When you disable a feature:</p>
                <ul>
                    <li>The feature card is hidden from the Group Admin's dashboard</li>
                    <li>The Group Admin cannot access the feature</li>
                    <li>Existing data is NOT deleted (can be re-enabled later)</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Add visual feedback on form submit
        document.querySelectorAll('.toggle-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('.btn-toggle');
                button.disabled = true;
                button.textContent = 'Processing...';
            });
        });
    </script>

    <?php include __DIR__ . '/../admin/includes/footer.php'; ?>
</body>
</html>
