<?php
/**
 * Distribution Setup - Part 3
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);
$communityId = AuthMiddleware::getCommunityId();

if (!$eventId || !$communityId) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get event
$query = "SELECT * FROM lottery_events WHERE event_id = :id AND community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $eventId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$error = '';
$success = '';

// Get existing levels
$query = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$existingLevels = $stmt->fetchAll();

// Get existing values for each level
$existingValues = [];
foreach ($existingLevels as $level) {
    $query = "SELECT * FROM distribution_level_values WHERE level_id = :level_id ORDER BY value_name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':level_id', $level['level_id']);
    $stmt->execute();
    $existingValues[$level['level_id']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribution Setup - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .instructions {
            background: var(--info-light);
            border-left: 4px solid var(--info-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .level-box {
            background: var(--gray-50);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
        }
        .info-badge {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1><?php echo htmlspecialchars($event['event_name']); ?> - Distribution Setup</h1>
            <p style="margin: 0; opacity: 0.9;">Part 3 of 6 - Configure Hierarchical Levels</p>
        </div>
    </div>

    <div class="container main-content">
        <div class="instructions">
            <h3 style="margin-top: 0;">🏢 Part 3: Distribution Levels (Optional)</h3>
            <p><strong>Hierarchical Structure:</strong> Values depend on parent level selection</p>
            <ul>
                <li><strong>Level 1:</strong> Add 1 value (e.g., Wing "A")</li>
                <li><strong>Level 2:</strong> Add 2 values for each Level 1 (e.g., for Wing "A" → Floor "1", "2")</li>
                <li><strong>Level 3:</strong> Add 3 values for each Level 2 (e.g., for Floor "1" → Flat "101", "102", "103")</li>
            </ul>
            <p style="margin: 0; color: var(--warning-color);"><strong>Note:</strong> Values can be added during assignment with "Add New" option. This page just sets up the level names.</p>
        </div>

        <?php if (count($existingLevels) > 0): ?>
            <div class="card">
                <div class="card-header" style="background: var(--success-light);">
                    <h3 class="card-title" style="color: var(--success-color); margin: 0;">
                        ✅ Distribution Levels Configured
                    </h3>
                </div>
                <div class="card-body">
                    <p><strong>Your distribution structure:</strong></p>
                    <ul>
                        <?php foreach ($existingLevels as $level): ?>
                            <li>
                                <strong>Level <?php echo $level['level_number']; ?>:</strong>
                                <?php echo htmlspecialchars($level['level_name']); ?>
                                <span class="info-badge">
                                    <?php echo count($existingValues[$level['level_id']] ?? []); ?> values
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="color: var(--gray-600); margin-top: var(--spacing-md);">
                        You can add new values dynamically during book assignment using the "Add New" option in dropdowns.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header" style="background: #fef3c7;">
                    <h3 class="card-title" style="color: #f59e0b; margin: 0;">
                        ⚠️ No Distribution Levels Configured
                    </h3>
                </div>
                <div class="card-body">
                    <p>You haven't set up any distribution levels yet. You have two options:</p>
                    <ol>
                        <li><strong>Use the Manual Method (Recommended for First Time):</strong> Go to "Manage Books" and use the assignment page. When assigning books, you'll be prompted to create levels and add values on-the-fly with the "Add New" feature.</li>
                        <li><strong>Pre-configure Levels Here:</strong> Contact your administrator to set up the distribution levels structure in the database directly.</li>
                    </ol>
                    <p style="margin-top: var(--spacing-lg); color: var(--info-color);">
                        💡 <strong>Tip:</strong> The "Add New" feature during assignment is easier and more flexible. You can skip this page and configure everything during assignment.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-md);">
            <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-primary btn-lg">
                Continue to Manage Books →
            </a>
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">
                ← Back to Events
            </a>
        </div>
    </div>
</body>
</html>
