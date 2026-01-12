<?php
/**
 * Group Admin Dashboard - SAAS Version
 * GetToKnow Community App v4.0
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

// Require authentication and Group Admin role
AuthMiddleware::requireRole('group_admin');

$userId = AuthMiddleware::getUserId();
$userName = $_SESSION['full_name'] ?? 'User';
$communityId = AuthMiddleware::getCommunityId();

// Get community details
$database = new Database();
$db = $database->getConnection();

$communityName = 'Not Assigned';
if ($communityId) {
    $query = "SELECT community_name FROM communities WHERE community_id = :community_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':community_id', $communityId);
    $stmt->execute();
    $community = $stmt->fetch();
    if ($community) {
        $communityName = $community['community_name'];
    }
}

// Get enabled features for this community
$featureAccess = new FeatureAccess();
$enabledFeatures = $featureAccess->getEnabledFeatures($communityId);

// Get aggregated statistics from enabled features
$totalStats = [
    'total_events' => 0,
    'active_events' => 0,
    'total_collection' => 0
];

if ($communityId) {
    foreach ($enabledFeatures as $feature) {
        $stats = $featureAccess->getFeatureStats($communityId, $feature['feature_key']);

        if ($feature['feature_key'] === 'lottery_system') {
            $totalStats['total_events'] = $stats['total_events'] ?? 0;
            $totalStats['active_events'] = $stats['active_events'] ?? 0;
        }
    }
}

// Breadcrumb
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => null]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        :root {
            --feature-lottery: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --feature-color-lottery: #667eea;
        }

        .top-navbar {
            background: #2C3E50;
            color: white;
            padding: 16px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top-navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-navbar .logo a {
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
        }

        .top-navbar .logo a:hover {
            color: #667eea;
        }

        .top-navbar .community-name {
            font-size: 16px;
            color: #ecf0f1;
        }

        .top-navbar .profile-menu {
            position: relative;
        }

        .top-navbar .profile-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .top-navbar .profile-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .top-navbar .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 200px;
            z-index: 1000;
        }

        .top-navbar .dropdown-menu.show {
            display: block;
        }

        .top-navbar .dropdown-menu a {
            display: block;
            padding: 12px 16px;
            color: #2C3E50;
            text-decoration: none;
            transition: background 0.2s;
        }

        .top-navbar .dropdown-menu a:hover {
            background: #f8f9fa;
        }

        .welcome-section {
            background: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
            border-bottom: 1px solid #e9ecef;
        }

        .welcome-section h1 {
            margin: 0 0 8px 0;
            font-size: 32px;
            color: #2C3E50;
        }

        .welcome-section p {
            margin: 0;
            color: #6c757d;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-card.primary { border-color: #667eea; }
        .stat-card.success { border-color: #2ECC71; }
        .stat-card.warning { border-color: #F39C12; }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #2C3E50;
        }

        .stat-label {
            color: #6c757d;
            font-size: 16px;
        }

        .features-section {
            margin-bottom: var(--spacing-xl);
        }

        .features-section h2 {
            font-size: 24px;
            margin-bottom: var(--spacing-lg);
            color: #2C3E50;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-lg);
        }

        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--feature-lottery);
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .feature-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #2C3E50;
        }

        .feature-description {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .feature-btn {
            background: var(--feature-lottery);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .feature-btn:hover {
            opacity: 0.9;
        }

        .no-features {
            background: white;
            border-radius: 12px;
            padding: 48px 32px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .no-features h3 {
            font-size: 24px;
            color: #2C3E50;
            margin-bottom: 16px;
        }

        .no-features p {
            font-size: 16px;
            color: #6c757d;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .top-navbar .container {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .welcome-section h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="top-navbar">
        <div class="container">
            <div class="logo">
                <a href="/public/group-admin/dashboard.php"><?php echo APP_NAME; ?></a>
            </div>
            <div class="community-name">
                <?php echo htmlspecialchars($communityName); ?>
            </div>
            <div class="profile-menu">
                <button class="profile-btn" onclick="toggleDropdown()">
                    <?php echo htmlspecialchars($userName); ?> ▾
                </button>
                <div class="dropdown-menu" id="profileDropdown">
                    <a href="/public/group-admin/change-password.php">Change Password</a>
                    <a href="/public/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <?php include __DIR__ . '/../includes/breadcrumb.php'; ?>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="container">
                <h1>Welcome, <?php echo htmlspecialchars($userName); ?></h1>
                <p>Manage your community with ease using the features below</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php if (!empty($enabledFeatures)): ?>
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value"><?php echo $totalStats['total_events']; ?></div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo $totalStats['active_events']; ?></div>
                <div class="stat-label">Active Events</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value">₹<?php echo number_format($totalStats['total_collection'], 2); ?></div>
                <div class="stat-label">Total Collection</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Available Features -->
        <div class="features-section">
            <h2>Available Features</h2>

            <?php if (empty($enabledFeatures)): ?>
                <div class="no-features">
                    <h3>No Features Enabled</h3>
                    <p>Your administrator hasn't enabled any features for your community yet.</p>
                    <p>Please contact your administrator to get started.</p>
                </div>
            <?php else: ?>
                <div class="features-grid">
                    <?php foreach ($enabledFeatures as $feature): ?>
                        <?php
                        $featureUrl = '/public/group-admin/dashboard.php'; // Default
                        $featureIcon = $feature['feature_icon'] ?? '🎯'; // Use icon from database

                        if ($feature['feature_key'] === 'lottery_system') {
                            $featureUrl = '/public/group-admin/lottery.php';
                        } elseif ($feature['feature_key'] === 'community_building') {
                            $featureUrl = '/public/group-admin/community-building.php';
                        }
                        ?>
                        <a href="<?php echo $featureUrl; ?>" class="feature-card">
                            <div class="feature-icon"><?php echo $featureIcon; ?></div>
                            <div class="feature-name"><?php echo htmlspecialchars($feature['feature_name']); ?></div>
                            <div class="feature-description"><?php echo htmlspecialchars($feature['feature_description']); ?></div>
                            <button class="feature-btn">Access Feature</button>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Show success message if any -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Show error message if any -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.matches('.profile-btn')) {
                const dropdown = document.getElementById('profileDropdown');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });

        // Show alert if not assigned to community
        <?php if (!$communityId): ?>
        alert('You are not assigned to any community yet. Please contact your administrator.');
        <?php endif; ?>
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
