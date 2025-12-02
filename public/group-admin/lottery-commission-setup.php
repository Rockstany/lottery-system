<?php
/**
 * Commission Setup - Configure commission rates and dates
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

// Get existing commission settings
$settingsQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id";
$stmt = $db->prepare($settingsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$settings = $stmt->fetch();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = isset($_POST['commission_enabled']) ? 1 : 0;
    $earlyDate = $_POST['early_payment_date'] ?? null;
    $earlyPercent = Validator::sanitizeFloat($_POST['early_commission_percent'] ?? 10);
    $standardDate = $_POST['standard_payment_date'] ?? null;
    $standardPercent = Validator::sanitizeFloat($_POST['standard_commission_percent'] ?? 5);
    $extraDate = $_POST['extra_books_date'] ?? null;
    $extraPercent = Validator::sanitizeFloat($_POST['extra_books_commission_percent'] ?? 15);

    if ($enabled && (!$earlyDate || !$standardDate || !$extraDate)) {
        $error = 'Please fill in all dates when commission is enabled';
    } else {
        if ($settings) {
            // Update existing
            $updateQuery = "UPDATE commission_settings SET
                           commission_enabled = :enabled,
                           early_payment_date = :early_date,
                           early_commission_percent = :early_percent,
                           standard_payment_date = :standard_date,
                           standard_commission_percent = :standard_percent,
                           extra_books_date = :extra_date,
                           extra_books_commission_percent = :extra_percent
                           WHERE event_id = :event_id";
            $stmt = $db->prepare($updateQuery);
        } else {
            // Insert new
            $updateQuery = "INSERT INTO commission_settings
                           (event_id, commission_enabled, early_payment_date, early_commission_percent,
                            standard_payment_date, standard_commission_percent, extra_books_date, extra_books_commission_percent)
                           VALUES (:event_id, :enabled, :early_date, :early_percent, :standard_date, :standard_percent, :extra_date, :extra_percent)";
            $stmt = $db->prepare($updateQuery);
        }

        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':enabled', $enabled);
        $stmt->bindParam(':early_date', $earlyDate);
        $stmt->bindParam(':early_percent', $earlyPercent);
        $stmt->bindParam(':standard_date', $standardDate);
        $stmt->bindParam(':standard_percent', $standardPercent);
        $stmt->bindParam(':extra_date', $extraDate);
        $stmt->bindParam(':extra_percent', $extraPercent);

        if ($stmt->execute()) {
            $success = 'Commission settings saved successfully!';
            // Refresh settings
            $settingsQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id";
            $stmt = $db->prepare($settingsQuery);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->execute();
            $settings = $stmt->fetch();
        } else {
            $error = 'Failed to save commission settings';
        }
    }
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
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; padding: var(--spacing-xl) 0; margin-bottom: var(--spacing-xl);">
        <div class="container">
            <h1>💰 <?php echo htmlspecialchars($event['event_name']); ?> - Commission Setup</h1>
            <p style="margin: 0; opacity: 0.9;">Configure commission rates for Level 1 distributors</p>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Help Box -->
        <div class="help-box mb-3" style="background: var(--info-light); border-left: 4px solid var(--info-color); padding: var(--spacing-lg); border-radius: var(--radius-md);">
            <h4>💡 How Commission Works</h4>
            <p>Commission is calculated based on payment collection dates and paid to Level 1 distributors (e.g., Wing A, Building B):</p>
            <ul>
                <li><strong>Early Payment (10%):</strong> Payments received before Date 1</li>
                <li><strong>Standard Payment (5%):</strong> Payments received before Date 2</li>
                <li><strong>Extra Books (15%):</strong> Books assigned after Date 2 get extra commission</li>
            </ul>
            <p style="margin: 0;"><strong>Note:</strong> Commission is calculated automatically when payments are collected</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Commission Configuration</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <!-- Enable/Disable Toggle -->
                    <div class="form-group" style="background: var(--gray-50); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                        <label style="display: flex; align-items: center; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" name="commission_enabled" value="1"
                                   <?php echo ($settings && $settings['commission_enabled']) ? 'checked' : ''; ?>
                                   style="width: 20px; height: 20px; margin-right: var(--spacing-sm);">
                            <span>Enable Commission for this Event</span>
                        </label>
                        <small class="form-text">Toggle commission calculation for this lottery event</small>
                    </div>

                    <!-- Early Payment Commission -->
                    <div style="background: white; border: 2px solid #10b981; padding: var(--spacing-lg); border-radius: var(--radius-md); margin-bottom: var(--spacing-md);">
                        <h4 style="margin-top: 0; color: #10b981;">🏃 Early Payment Commission (10%)</h4>
                        <div class="form-row">
                            <div class="form-col">
                                <label class="form-label">Early Payment Date (Date 1)</label>
                                <input type="date" name="early_payment_date" class="form-control"
                                       value="<?php echo $settings['early_payment_date'] ?? ''; ?>">
                                <small class="form-text">Payments before this date get 10% commission</small>
                            </div>
                            <div class="form-col">
                                <label class="form-label">Commission Percentage</label>
                                <input type="number" name="early_commission_percent" class="form-control" step="0.01"
                                       value="<?php echo $settings['early_commission_percent'] ?? 10; ?>">
                                <small class="form-text">Default: 10%</small>
                            </div>
                        </div>
                    </div>

                    <!-- Standard Payment Commission -->
                    <div style="background: white; border: 2px solid #f59e0b; padding: var(--spacing-lg); border-radius: var(--radius-md); margin-bottom: var(--spacing-md);">
                        <h4 style="margin-top: 0; color: #f59e0b;">🚶 Standard Payment Commission (5%)</h4>
                        <div class="form-row">
                            <div class="form-col">
                                <label class="form-label">Standard Payment Date (Date 2)</label>
                                <input type="date" name="standard_payment_date" class="form-control"
                                       value="<?php echo $settings['standard_payment_date'] ?? ''; ?>">
                                <small class="form-text">Payments before this date get 5% commission</small>
                            </div>
                            <div class="form-col">
                                <label class="form-label">Commission Percentage</label>
                                <input type="number" name="standard_commission_percent" class="form-control" step="0.01"
                                       value="<?php echo $settings['standard_commission_percent'] ?? 5; ?>">
                                <small class="form-text">Default: 5%</small>
                            </div>
                        </div>
                    </div>

                    <!-- Extra Books Commission -->
                    <div style="background: white; border: 2px solid #8b5cf6; padding: var(--spacing-lg); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                        <h4 style="margin-top: 0; color: #8b5cf6;">📚 Extra Books Commission (15%)</h4>
                        <div class="form-row">
                            <div class="form-col">
                                <label class="form-label">Extra Books Date</label>
                                <input type="date" name="extra_books_date" class="form-control"
                                       value="<?php echo $settings['extra_books_date'] ?? ''; ?>">
                                <small class="form-text">Books assigned after this date get 15% commission</small>
                            </div>
                            <div class="form-col">
                                <label class="form-label">Commission Percentage</label>
                                <input type="number" name="extra_books_commission_percent" class="form-control" step="0.01"
                                       value="<?php echo $settings['extra_books_commission_percent'] ?? 15; ?>">
                                <small class="form-text">Default: 15%</small>
                            </div>
                        </div>
                    </div>

                    <div class="button-group-mobile">
                        <button type="submit" class="btn btn-primary btn-lg">Save Commission Settings</button>
                        <a href="/public/group-admin/lottery.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="button-group-mobile mt-3">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <?php if ($settings && $settings['commission_enabled']): ?>
                <a href="/public/group-admin/lottery-commission-report.php?id=<?php echo $eventId; ?>" class="btn btn-success">View Commission Report →</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
