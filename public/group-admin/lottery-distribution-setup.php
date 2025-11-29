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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $levels = $_POST['levels'] ?? [];

    if (count($levels) > 0) {
        // Save distribution levels
        foreach ($levels as $levelNum => $levelData) {
            if (!empty($levelData['name'])) {
                $query = "INSERT INTO distribution_levels (event_id, level_number, level_name)
                          VALUES (:event_id, :level_number, :level_name)
                          ON DUPLICATE KEY UPDATE level_name = :level_name2";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':event_id', $eventId);
                $stmt->bindParam(':level_number', $levelNum);
                $stmt->bindParam(':level_name', $levelData['name']);
                $stmt->bindParam(':level_name2', $levelData['name']);
                $stmt->execute();

                $levelId = $db->lastInsertId() ?: $db->query("SELECT level_id FROM distribution_levels WHERE event_id = {$eventId} AND level_number = {$levelNum}")->fetchColumn();

                // Save level values
                if (!empty($levelData['values'])) {
                    $values = array_filter(array_map('trim', explode(',', $levelData['values'])));
                    foreach ($values as $value) {
                        $query = "INSERT IGNORE INTO distribution_level_values (level_id, value_name)
                                  VALUES (:level_id, :value_name)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':level_id', $levelId);
                        $stmt->bindParam(':value_name', $value);
                        $stmt->execute();
                    }
                }
            }
        }

        header("Location: /public/group-admin/lottery-books.php?id={$eventId}");
        exit;
    }
}

// Get existing levels
$query = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$existingLevels = $stmt->fetchAll();
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
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo htmlspecialchars($event['event_name']); ?> - Distribution Setup</h1>
            <p style="margin: 0; opacity: 0.9;">Part 3 of 6</p>
        </div>
    </div>

    <div class="container main-content">
        <div class="instructions">
            <h3 style="margin-top: 0;">🏢 Part 3: Distribution Levels (Optional)</h3>
            <p>Set up hierarchy for book distribution. Common examples:</p>
            <ul>
                <li><strong>Level 1:</strong> Wing (A, B, C)</li>
                <li><strong>Level 2:</strong> Floor (1, 2, 3, 4)</li>
                <li><strong>Level 3:</strong> Flat (101, 102, 103)</li>
            </ul>
            <p style="margin: 0;"><strong>Note:</strong> You can skip this and distribute books directly by member name.</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Configure Distribution Levels</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="level-box">
                        <h4>Level 1 (e.g., Wing)</h4>
                        <div class="form-group">
                            <label class="form-label">Level Name</label>
                            <input type="text" name="levels[1][name]" class="form-control" placeholder="Wing" value="Wing">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Values (comma separated)</label>
                            <input type="text" name="levels[1][values]" class="form-control" placeholder="A, B, C" value="A, B, C">
                            <span class="form-help">Example: A, B, C</span>
                        </div>
                    </div>

                    <div class="level-box">
                        <h4>Level 2 (e.g., Floor) - Optional</h4>
                        <div class="form-group">
                            <label class="form-label">Level Name</label>
                            <input type="text" name="levels[2][name]" class="form-control" placeholder="Floor">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Values (comma separated)</label>
                            <input type="text" name="levels[2][values]" class="form-control" placeholder="1, 2, 3, 4">
                        </div>
                    </div>

                    <div class="level-box">
                        <h4>Level 3 (e.g., Flat) - Optional</h4>
                        <div class="form-group">
                            <label class="form-label">Level Name</label>
                            <input type="text" name="levels[3][name]" class="form-control" placeholder="Flat">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Values (comma separated)</label>
                            <input type="text" name="levels[3][values]" class="form-control" placeholder="01, 02, 03">
                        </div>
                    </div>

                    <div style="display: flex; gap: var(--spacing-md);">
                        <button type="submit" class="btn btn-primary btn-lg">Save & Continue →</button>
                        <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">Skip This Step</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
