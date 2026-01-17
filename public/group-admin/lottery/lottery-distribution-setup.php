<?php
/**
 * Distribution Setup - Part 3
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);
$communityId = AuthMiddleware::getCommunityId();

if (!$eventId || !$communityId) {
    header("Location: /public/group-admin/lottery/lottery.php");
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
    header("Location: /public/group-admin/lottery/lottery.php");
    exit;
}

$error = '';
$success = '';

// Handle level creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_level'])) {
    $levelName = Validator::sanitizeString($_POST['level_name'] ?? '');
    $levelNumber = Validator::sanitizeInt($_POST['level_number'] ?? 0);

    if (empty($levelName)) {
        $error = 'Please enter a level name';
    } elseif ($levelNumber < 1 || $levelNumber > 10) {
        $error = 'Level number must be between 1 and 10';
    } else {
        // Check if level already exists
        $checkQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id AND level_number = :level_number";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':event_id', $eventId);
        $checkStmt->bindParam(':level_number', $levelNumber);
        $checkStmt->execute();

        if ($checkStmt->fetch()) {
            $error = "Level $levelNumber already exists";
        } else {
            $insertQuery = "INSERT INTO distribution_levels (event_id, level_name, level_number) VALUES (:event_id, :level_name, :level_number)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':event_id', $eventId);
            $insertStmt->bindParam(':level_name', $levelName);
            $insertStmt->bindParam(':level_number', $levelNumber);

            if ($insertStmt->execute()) {
                $success = "Level added successfully!";
            } else {
                $error = 'Failed to add level';
            }
        }
    }
}

// Handle value creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_value'])) {
    $levelId = Validator::sanitizeInt($_POST['level_id'] ?? 0);
    $valueName = Validator::sanitizeString($_POST['value_name'] ?? '');
    $parentValueId = !empty($_POST['parent_value_id']) ? Validator::sanitizeInt($_POST['parent_value_id']) : null;

    if (empty($valueName)) {
        $error = 'Please enter a value name';
    } elseif (!$levelId) {
        $error = 'Invalid level selected';
    } else {
        $insertQuery = "INSERT INTO distribution_level_values (level_id, value_name, parent_value_id) VALUES (:level_id, :value_name, :parent_value_id)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':level_id', $levelId);
        $insertStmt->bindParam(':value_name', $valueName);
        $insertStmt->bindValue(':parent_value_id', $parentValueId, PDO::PARAM_INT);

        if ($insertStmt->execute()) {
            $success = "Value added successfully!";
        } else {
            $error = 'Failed to add value';
        }
    }
}

// Handle level deletion
if (isset($_GET['delete_level'])) {
    $levelId = Validator::sanitizeInt($_GET['delete_level']);
    // Delete values first
    $deleteValuesQuery = "DELETE FROM distribution_level_values WHERE level_id = :level_id";
    $stmt = $db->prepare($deleteValuesQuery);
    $stmt->bindParam(':level_id', $levelId);
    $stmt->execute();

    // Delete level
    $deleteLevelQuery = "DELETE FROM distribution_levels WHERE level_id = :level_id AND event_id = :event_id";
    $stmt = $db->prepare($deleteLevelQuery);
    $stmt->bindParam(':level_id', $levelId);
    $stmt->bindParam(':event_id', $eventId);
    if ($stmt->execute()) {
        header("Location: ?id=$eventId&success=level_deleted");
        exit;
    }
}

// Handle value deletion
if (isset($_GET['delete_value'])) {
    $valueId = Validator::sanitizeInt($_GET['delete_value']);
    $deleteQuery = "DELETE FROM distribution_level_values WHERE value_id = :value_id";
    $stmt = $db->prepare($deleteQuery);
    $stmt->bindParam(':value_id', $valueId);
    if ($stmt->execute()) {
        header("Location: ?id=$eventId&success=value_deleted");
        exit;
    }
}

if (isset($_GET['success'])) {
    $success = match($_GET['success']) {
        'level_deleted' => 'Level deleted successfully',
        'value_deleted' => 'Value deleted successfully',
        default => ''
    };
}

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
    <script src="/public/js/toast.js"></script>
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
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1><?php echo htmlspecialchars($event['event_name']); ?> - Distribution Setup</h1>
            <p style="margin: 0; opacity: 0.9;">Part 3 of 6 - Configure Hierarchical Levels</p>
        </div>
    </div>

    <div class="container main-content">
        <?php include __DIR__ . '/../includes/toast-handler.php'; ?>

        <div class="instructions">
            <h3 style="margin-top: 0;">🏢 Part 3: Distribution Levels (Optional)</h3>
            <p><strong>Hierarchical Structure:</strong> Values depend on parent level selection</p>
            <ul>
                <li><strong>Level 1:</strong> Add 1 value (e.g., Wing "A")</li>
                <li><strong>Level 2:</strong> Add 2 values for each Level 1 (e.g., for Wing "A" → Floor "1", "2")</li>
                <li><strong>Level 3:</strong> Add 3 values for each Level 2 (e.g., for Floor "1" → Flat "101", "102", "103")</li>
            </ul>
            <p style="margin: 0; color: var(--info-color);"><strong>💡 Tip:</strong> Values can also be added during book assignment with "Add New" option.</p>
        </div>

        <!-- Add Level Form -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <div class="card-header">
                <h3 class="card-title">➕ Add New Level</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_level" value="1">
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label form-label-required">Level Number</label>
                            <select name="level_number" class="form-control" required>
                                <option value="">Select Level Number</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <?php
                                    // Check if this level already exists
                                    $exists = false;
                                    foreach ($existingLevels as $level) {
                                        if ($level['level_number'] == $i) {
                                            $exists = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <option value="<?php echo $i; ?>" <?php echo $exists ? 'disabled' : ''; ?>>
                                        Level <?php echo $i; ?> <?php echo $exists ? '(Already Added)' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="form-label form-label-required">Level Name</label>
                            <input type="text" name="level_name" class="form-control" placeholder="e.g., Wing, Floor, Flat" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Level</button>
                </form>
            </div>
        </div>

        <!-- Existing Levels and Values -->
        <?php if (count($existingLevels) > 0): ?>
            <div class="card" style="margin-bottom: var(--spacing-lg);">
                <div class="card-header">
                    <h3 class="card-title">📋 Configured Levels & Values</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($existingLevels as $index => $level): ?>
                        <div class="level-box">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
                                <h4 style="margin: 0;">
                                    Level <?php echo $level['level_number']; ?>: <?php echo htmlspecialchars($level['level_name']); ?>
                                    <span class="info-badge"><?php echo count($existingValues[$level['level_id']] ?? []); ?> values</span>
                                </h4>
                                <a href="?id=<?php echo $eventId; ?>&delete_level=<?php echo $level['level_id']; ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this level and all its values?')">Delete Level</a>
                            </div>

                            <!-- Add Value Form -->
                            <form method="POST" style="background: white; padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-md);">
                                <input type="hidden" name="add_value" value="1">
                                <input type="hidden" name="level_id" value="<?php echo $level['level_id']; ?>">
                                <div class="form-row">
                                    <?php if ($level['level_number'] > 1): ?>
                                        <!-- Show parent level dropdown -->
                                        <?php
                                        // Get previous level
                                        $prevLevel = null;
                                        foreach ($existingLevels as $l) {
                                            if ($l['level_number'] == $level['level_number'] - 1) {
                                                $prevLevel = $l;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if ($prevLevel): ?>
                                            <div class="form-col">
                                                <label class="form-label">Parent (<?php echo htmlspecialchars($prevLevel['level_name']); ?>)</label>
                                                <select name="parent_value_id" class="form-control">
                                                    <option value="">No Parent (Optional)</option>
                                                    <?php foreach ($existingValues[$prevLevel['level_id']] ?? [] as $parentValue): ?>
                                                        <option value="<?php echo $parentValue['value_id']; ?>">
                                                            <?php echo htmlspecialchars($parentValue['value_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="form-text">Select parent to create hierarchy</small>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="form-col">
                                        <label class="form-label form-label-required">Value Name</label>
                                        <input type="text" name="value_name" class="form-control"
                                               placeholder="e.g., Wing A, Floor 1, Flat 101" required>
                                    </div>
                                    <div class="form-col" style="display: flex; align-items: end;">
                                        <button type="submit" class="btn btn-success" style="width: 100%;">Add Value</button>
                                    </div>
                                </div>
                            </form>

                            <!-- Existing Values -->
                            <?php if (count($existingValues[$level['level_id']] ?? []) > 0): ?>
                                <div>
                                    <strong>Existing Values:</strong>
                                    <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-sm); margin-top: var(--spacing-sm);">
                                        <?php foreach ($existingValues[$level['level_id']] as $value): ?>
                                            <div style="background: var(--primary-light); padding: 8px 12px; border-radius: var(--radius-md); display: flex; align-items: center; gap: var(--spacing-xs);">
                                                <span><?php echo htmlspecialchars($value['value_name']); ?></span>
                                                <?php if ($value['parent_value_id']): ?>
                                                    <?php
                                                    // Find parent value name
                                                    $parentName = '';
                                                    foreach ($existingLevels as $l) {
                                                        if ($l['level_number'] == $level['level_number'] - 1) {
                                                            foreach ($existingValues[$l['level_id']] ?? [] as $pv) {
                                                                if ($pv['value_id'] == $value['parent_value_id']) {
                                                                    $parentName = $pv['value_name'];
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <small style="color: var(--gray-600);">(under <?php echo htmlspecialchars($parentName); ?>)</small>
                                                <?php endif; ?>
                                                <a href="?id=<?php echo $eventId; ?>&delete_value=<?php echo $value['value_id']; ?>"
                                                   style="color: var(--danger-color); font-weight: bold; text-decoration: none;"
                                                   onclick="return confirm('Delete this value?')">×</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--gray-500); margin: 0;">No values added yet. Add values using the form above.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card" style="margin-bottom: var(--spacing-lg);">
                <div class="card-header" style="background: var(--warning-light);">
                    <h3 class="card-title" style="color: var(--warning-color); margin: 0;">
                        ⚠️ No Distribution Levels Added Yet
                    </h3>
                </div>
                <div class="card-body">
                    <p>Use the form above to add your first level (e.g., Level 1: Wing)</p>
                    <p style="margin: 0; color: var(--info-color);">
                        💡 <strong>Tip:</strong> You can also skip this step and add levels during book assignment using the "Add New" feature.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-md);">
            <a href="/public/group-admin/lottery/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-primary btn-lg">
                Continue to Manage Books →
            </a>
            <a href="/public/group-admin/lottery/lottery.php" class="btn btn-secondary">
                ← Back to Events
            </a>
        </div>
    </div>
</body>
</html>
