<?php
/**
 * Commission Setup - Individual Control for Each Commission Type
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

// Get existing commission settings
$settingsQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id";
$stmt = $db->prepare($settingsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$settings = $stmt->fetch();

$error = '';
$success = '';

// Handle form submission for individual commission types
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Handle master commission toggle
    if ($action === 'master_commission') {
        $masterEnabled = isset($_POST['commission_enabled']) ? 1 : 0;

        if ($settings) {
            $updateQuery = "UPDATE commission_settings SET
                           commission_enabled = :enabled
                           WHERE event_id = :event_id";
        } else {
            $updateQuery = "INSERT INTO commission_settings
                           (event_id, commission_enabled)
                           VALUES (:event_id, :enabled)";
        }
        $stmt = $db->prepare($updateQuery);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':enabled', $masterEnabled);

        if ($stmt->execute()) {
            header("Location: ?id=$eventId&success=master_saved");
            exit;
        } else {
            $error = 'Failed to save master commission settings';
        }
    }

    if ($action === 'early_commission') {
        $enabled = isset($_POST['early_commission_enabled']) ? 1 : 0;
        $date = $_POST['early_payment_date'] ?? null;
        $percent = Validator::sanitizeFloat($_POST['early_commission_percent'] ?? 10);

        if ($enabled && !$date) {
            $error = 'Please provide deadline date for Early Payment Commission';
        } else {
            if ($settings) {
                $updateQuery = "UPDATE commission_settings SET
                               early_commission_enabled = :enabled,
                               early_payment_date = :date,
                               early_commission_percent = :percent
                               WHERE event_id = :event_id";
            } else {
                $updateQuery = "INSERT INTO commission_settings
                               (event_id, commission_enabled, early_commission_enabled, early_payment_date, early_commission_percent)
                               VALUES (:event_id, 1, :enabled, :date, :percent)";
            }
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':enabled', $enabled);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':percent', $percent);

            if ($stmt->execute()) {
                $success = 'Early Payment Commission settings saved successfully!';
                header("Location: ?id=$eventId&success=early_saved");
                exit;
            } else {
                $error = 'Failed to save settings';
            }
        }
    }
    elseif ($action === 'standard_commission') {
        $enabled = isset($_POST['standard_commission_enabled']) ? 1 : 0;
        $date = $_POST['standard_payment_date'] ?? null;
        $percent = Validator::sanitizeFloat($_POST['standard_commission_percent'] ?? 5);

        if ($enabled && !$date) {
            $error = 'Please provide deadline date for Standard Payment Commission';
        } else {
            if ($settings) {
                $updateQuery = "UPDATE commission_settings SET
                               standard_commission_enabled = :enabled,
                               standard_payment_date = :date,
                               standard_commission_percent = :percent
                               WHERE event_id = :event_id";
            } else {
                $updateQuery = "INSERT INTO commission_settings
                               (event_id, commission_enabled, standard_commission_enabled, standard_payment_date, standard_commission_percent)
                               VALUES (:event_id, 1, :enabled, :date, :percent)";
            }
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':enabled', $enabled);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':percent', $percent);

            if ($stmt->execute()) {
                header("Location: ?id=$eventId&success=standard_saved");
                exit;
            } else {
                $error = 'Failed to save settings';
            }
        }
    }
    elseif ($action === 'extra_books_commission') {
        $enabled = isset($_POST['extra_books_commission_enabled']) ? 1 : 0;
        $date = $_POST['extra_books_date'] ?? null;
        $percent = Validator::sanitizeFloat($_POST['extra_books_commission_percent'] ?? 15);

        if ($enabled && !$date) {
            $error = 'Please provide reference date for Extra Books Commission';
        } else {
            if ($settings) {
                $updateQuery = "UPDATE commission_settings SET
                               extra_books_commission_enabled = :enabled,
                               extra_books_date = :date,
                               extra_books_commission_percent = :percent
                               WHERE event_id = :event_id";
            } else {
                $updateQuery = "INSERT INTO commission_settings
                               (event_id, commission_enabled, extra_books_commission_enabled, extra_books_date, extra_books_commission_percent)
                               VALUES (:event_id, 1, :enabled, :date, :percent)";
            }
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':enabled', $enabled);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':percent', $percent);

            if ($stmt->execute()) {
                header("Location: ?id=$eventId&success=extra_saved");
                exit;
            } else {
                $error = 'Failed to save settings';
            }
        }
    }

    // Refresh settings
    $stmt = $db->prepare($settingsQuery);
    $stmt->bindParam(':event_id', $eventId);
    $stmt->execute();
    $settings = $stmt->fetch();
}

// Handle success messages
if (isset($_GET['success'])) {
    $success = match($_GET['success']) {
        'master_saved' => 'Master Commission settings saved successfully!',
        'early_saved' => 'Early Payment Commission settings saved successfully!',
        'standard_saved' => 'Standard Payment Commission settings saved successfully!',
        'extra_saved' => 'Extra Books Commission settings saved successfully!',
        default => ''
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Setup - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
    <style>
        .commission-card {
            background: white;
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 2px solid var(--gray-200);
            transition: all var(--transition-base);
        }

        .commission-card.enabled {
            border-color: var(--success-color);
        }

        .commission-header {
            padding: var(--spacing-lg);
            background: var(--gray-50);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .commission-header:hover {
            background: var(--gray-100);
        }

        .commission-title {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin: 0;
        }

        .commission-toggle {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-300);
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--success-color);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }

        .commission-body {
            display: none;
            padding: var(--spacing-lg);
            border-top: 1px solid var(--gray-200);
        }

        .commission-body.active {
            display: block;
        }

        .expand-icon {
            transition: transform 0.3s ease;
            font-size: 20px;
        }

        .expand-icon.active {
            transform: rotate(180deg);
        }

        .info-box {
            background: var(--info-light);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-top: var(--spacing-md);
            border-left: 3px solid var(--info-color);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <div class="header" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; padding: var(--spacing-xl) 0; margin-bottom: var(--spacing-xl);">
        <div class="container">
            <h1>💰 Commission Setup</h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($event['event_name']); ?> - Configure Individual Commission Types</p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Back Button -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <a href="/public/group-admin/lottery/lottery-reports.php?id=<?php echo $eventId; ?>#commission" class="btn btn-success" onclick="setTimeout(() => document.querySelector('.tab[onclick*=commission]')?.click(), 100)">View Commission Report →</a>
        </div>

        <!-- Master Commission Toggle -->
        <div class="card" style="margin-bottom: var(--spacing-xl); border: 3px solid <?php echo ($settings && $settings['commission_enabled']) ? 'var(--success-color)' : 'var(--gray-300)'; ?>; background: <?php echo ($settings && $settings['commission_enabled']) ? '#f0fdf4' : 'white'; ?>;">
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spacing-md);">
                    <div>
                        <h3 style="margin: 0; display: flex; align-items: center; gap: var(--spacing-md);">
                            <span style="font-size: 28px;">⚙️</span>
                            Master Commission Control
                            <span class="badge <?php echo ($settings && $settings['commission_enabled']) ? 'badge-success' : 'badge-secondary'; ?>" style="font-size: 14px;">
                                <?php echo ($settings && $settings['commission_enabled']) ? 'ENABLED' : 'DISABLED'; ?>
                            </span>
                        </h3>
                        <p style="margin: var(--spacing-sm) 0 0 0; color: var(--gray-600);">
                            <?php if ($settings && $settings['commission_enabled']): ?>
                                <strong style="color: var(--success-color);">✓ Commission system is active</strong> - Individual commission types can be configured below
                            <?php else: ?>
                                <strong style="color: var(--error-color);">✗ Commission system is disabled</strong> - Enable to activate commission calculations
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('<?php echo ($settings && $settings['commission_enabled']) ? 'Disable commission system? This will stop all commission calculations.' : 'Enable commission system? You can then configure individual commission types below.'; ?>')">
                            <input type="hidden" name="action" value="master_commission">
                            <label class="toggle-switch" style="margin: 0;">
                                <input type="checkbox" name="commission_enabled" value="1"
                                       <?php echo ($settings && $settings['commission_enabled']) ? 'checked' : ''; ?>
                                       onchange="this.form.submit()">
                                <span class="toggle-slider"></span>
                            </label>
                        </form>
                    </div>
                </div>

                <div style="background: <?php echo ($settings && $settings['commission_enabled']) ? '#dcfce7' : '#fef2f2'; ?>; padding: var(--spacing-md); border-radius: var(--radius-md); margin-top: var(--spacing-md); border: 1px solid <?php echo ($settings && $settings['commission_enabled']) ? '#86efac' : '#fecaca'; ?>;">
                    <small>
                        <strong><?php echo ($settings && $settings['commission_enabled']) ? 'ℹ️ Note:' : '⚠️ Important:'; ?></strong>
                        <?php if ($settings && $settings['commission_enabled']): ?>
                            This is the master switch for the entire commission system. When enabled, you can configure individual commission types below. Commission calculations will apply based on your individual settings.
                        <?php else: ?>
                            The master commission system is currently disabled. No commissions will be calculated for this event. Enable it here to start configuring and earning commissions.
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Commission Maintenance Tools -->
        <?php if ($settings && $settings['commission_enabled']): ?>
        <div class="card" style="margin-bottom: var(--spacing-lg); border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <h3 style="margin-top: 0;">🛠️ Commission Maintenance Tools</h3>
                <p style="color: var(--gray-600);">Administrative tools for managing commission data. Use these tools carefully.</p>

                <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap; margin-top: var(--spacing-md);">
                    <a href="/public/group-admin/lottery/lottery-commission-sync.php?id=<?php echo $eventId; ?>" class="btn" style="background: var(--warning-color); color: white;">
                        🔄 Recalculate Commissions
                    </a>
                    <a href="/public/group-admin/lottery/lottery-commission-cleanup-duplicates.php?id=<?php echo $eventId; ?>" class="btn" style="background: var(--error-color); color: white;">
                        🧹 Cleanup Duplicates
                    </a>
                </div>

                <div style="background: #fef2f2; padding: var(--spacing-md); border-radius: var(--radius-md); margin-top: var(--spacing-md); border: 1px solid #fecaca;">
                    <small>
                        <strong>⚠️ Use with caution:</strong>
                        <ul style="margin: var(--spacing-xs) 0 0 var(--spacing-lg);">
                            <li><strong>Recalculate:</strong> Deletes all commission records and recalculates them from payment data</li>
                            <li><strong>Cleanup:</strong> Removes duplicate commission records (one-time fix for data issues)</li>
                        </ul>
                    </small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="help-box mb-3" style="background: var(--info-light); border-left: 4px solid var(--info-color); padding: var(--spacing-lg); border-radius: var(--radius-md); margin-bottom: var(--spacing-xl);">
            <h4>💡 How Individual Commission Controls Work</h4>
            <p>Each commission type can be enabled or disabled independently:</p>
            <ul style="margin-bottom: 0;">
                <li><strong>Early Payment Commission:</strong> Enable to reward early payers with higher commission</li>
                <li><strong>Standard Payment Commission:</strong> Enable for regular payment timeline commission</li>
                <li><strong>Extra Books Commission:</strong> Enable for books marked as "extra" during assignment</li>
            </ul>
            <p style="margin-top: var(--spacing-md); margin-bottom: 0;"><strong>Note:</strong> You can enable any combination of commission types. Toggle and expand each section to configure.</p>
        </div>

        <!-- Commission Type 1: Early Payment Commission -->
        <div class="commission-card <?php echo ($settings && $settings['early_commission_enabled']) ? 'enabled' : ''; ?>">
            <div class="commission-header" onclick="toggleSection('early')">
                <div class="commission-title">
                    <span style="font-size: 24px;">🏃</span>
                    <div>
                        <h3 style="margin: 0;">Early Payment Commission</h3>
                        <small style="color: var(--gray-600);">Reward early payers with bonus commission</small>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <span class="badge <?php echo ($settings && $settings['early_commission_enabled']) ? 'badge-success' : 'badge-secondary'; ?>">
                        <?php echo ($settings && $settings['early_commission_enabled']) ? 'ENABLED' : 'DISABLED'; ?>
                    </span>
                    <span class="expand-icon" id="early-icon">▼</span>
                </div>
            </div>
            <div class="commission-body" id="early-body">
                <form method="POST">
                    <input type="hidden" name="action" value="early_commission">

                    <!-- Enable/Disable Toggle -->
                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" name="early_commission_enabled" value="1"
                                   <?php echo ($settings && $settings['early_commission_enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <label style="margin-left: var(--spacing-md); font-weight: 600;">Enable Early Payment Commission</label>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Deadline Date *</label>
                            <input type="date" name="early_payment_date" class="form-control"
                                   value="<?php echo $settings['early_payment_date'] ?? ''; ?>" required>
                            <small class="form-text">Payments collected before this date qualify for early commission</small>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Commission Percentage *</label>
                            <input type="number" name="early_commission_percent" class="form-control"
                                   step="0.01" min="0" max="100"
                                   value="<?php echo $settings['early_commission_percent'] ?? 10; ?>" required>
                            <small class="form-text">Example: 10 means 10% commission</small>
                        </div>
                    </div>

                    <div class="info-box">
                        <strong>ℹ️ How it works:</strong> When a payment is collected before the deadline date, the system automatically calculates and records commission based on the percentage you set.
                    </div>

                    <div style="margin-top: var(--spacing-lg);">
                        <button type="submit" class="btn btn-success">💾 Save Early Commission Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Commission Type 2: Standard Payment Commission -->
        <div class="commission-card <?php echo ($settings && $settings['standard_commission_enabled']) ? 'enabled' : ''; ?>">
            <div class="commission-header" onclick="toggleSection('standard')">
                <div class="commission-title">
                    <span style="font-size: 24px;">📅</span>
                    <div>
                        <h3 style="margin: 0;">Standard Payment Commission</h3>
                        <small style="color: var(--gray-600);">Regular payment timeline commission</small>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <span class="badge <?php echo ($settings && $settings['standard_commission_enabled']) ? 'badge-success' : 'badge-secondary'; ?>">
                        <?php echo ($settings && $settings['standard_commission_enabled']) ? 'ENABLED' : 'DISABLED'; ?>
                    </span>
                    <span class="expand-icon" id="standard-icon">▼</span>
                </div>
            </div>
            <div class="commission-body" id="standard-body">
                <form method="POST">
                    <input type="hidden" name="action" value="standard_commission">

                    <!-- Enable/Disable Toggle -->
                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" name="standard_commission_enabled" value="1"
                                   <?php echo ($settings && $settings['standard_commission_enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <label style="margin-left: var(--spacing-md); font-weight: 600;">Enable Standard Payment Commission</label>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Deadline Date *</label>
                            <input type="date" name="standard_payment_date" class="form-control"
                                   value="<?php echo $settings['standard_payment_date'] ?? ''; ?>" required>
                            <small class="form-text">Payments collected before this date qualify for standard commission</small>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Commission Percentage *</label>
                            <input type="number" name="standard_commission_percent" class="form-control"
                                   step="0.01" min="0" max="100"
                                   value="<?php echo $settings['standard_commission_percent'] ?? 5; ?>" required>
                            <small class="form-text">Example: 5 means 5% commission</small>
                        </div>
                    </div>

                    <div class="info-box">
                        <strong>ℹ️ How it works:</strong> Payments that don't qualify for early commission but are collected before this deadline get standard commission rate.
                    </div>

                    <div style="margin-top: var(--spacing-lg);">
                        <button type="submit" class="btn btn-success">💾 Save Standard Commission Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Commission Type 3: Extra Books Commission -->
        <div class="commission-card <?php echo ($settings && $settings['extra_books_commission_enabled']) ? 'enabled' : ''; ?>">
            <div class="commission-header" onclick="toggleSection('extra')">
                <div class="commission-title">
                    <span style="font-size: 24px;">📚</span>
                    <div>
                        <h3 style="margin: 0;">Extra Books Commission</h3>
                        <small style="color: var(--gray-600);">Special commission for books marked as "extra"</small>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <span class="badge <?php echo ($settings && $settings['extra_books_commission_enabled']) ? 'badge-success' : 'badge-secondary'; ?>">
                        <?php echo ($settings && $settings['extra_books_commission_enabled']) ? 'ENABLED' : 'DISABLED'; ?>
                    </span>
                    <span class="expand-icon" id="extra-icon">▼</span>
                </div>
            </div>
            <div class="commission-body" id="extra-body">
                <form method="POST">
                    <input type="hidden" name="action" value="extra_books_commission">

                    <!-- Enable/Disable Toggle -->
                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" name="extra_books_commission_enabled" value="1"
                                   <?php echo ($settings && $settings['extra_books_commission_enabled']) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <label style="margin-left: var(--spacing-md); font-weight: 600;">Enable Extra Books Commission</label>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Reference Date *</label>
                            <input type="date" name="extra_books_date" class="form-control"
                                   value="<?php echo $settings['extra_books_date'] ?? ''; ?>" required>
                            <small class="form-text">This date is shown for reference when assigning books</small>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Commission Percentage *</label>
                            <input type="number" name="extra_books_commission_percent" class="form-control"
                                   step="0.01" min="0" max="100"
                                   value="<?php echo $settings['extra_books_commission_percent'] ?? 15; ?>" required>
                            <small class="form-text">Example: 15 means 15% commission</small>
                        </div>
                    </div>

                    <div class="info-box">
                        <strong>ℹ️ How it works:</strong> During book assignment, you'll see a checkbox to mark a book as "Extra Book". Any payment collected for books marked as extra will receive this commission rate, regardless of payment date.
                        <br><br>
                        <strong>Note:</strong> The checkbox only appears during assignment if Extra Books Commission is enabled.
                    </div>

                    <div style="margin-top: var(--spacing-lg);">
                        <button type="submit" class="btn btn-success">💾 Save Extra Books Commission Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSection(section) {
            const body = document.getElementById(section + '-body');
            const icon = document.getElementById(section + '-icon');

            body.classList.toggle('active');
            icon.classList.toggle('active');
        }
    </script>
</body>
</html>
