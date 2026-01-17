<?php
/**
 * Commission Reset - Reset All Commission Records for an Event
 * GetToKnow Community App
 *
 * This tool allows resetting all commission records for an event
 * Useful when re-uploading Excel data or correcting commission calculations
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

$success = '';
$error = '';
$stats = null;

// Get current commission statistics
$statsQuery = "SELECT
                COUNT(*) as total_records,
                COUNT(DISTINCT distribution_id) as total_books_with_commission,
                SUM(commission_amount) as total_commission,
                commission_type,
                COUNT(*) as type_count
               FROM commission_earned
               WHERE event_id = :event_id
               GROUP BY commission_type";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->bindParam(':event_id', $eventId);
$statsStmt->execute();
$commissionStats = $statsStmt->fetchAll();

$totalRecords = 0;
$totalAmount = 0;
foreach ($commissionStats as $stat) {
    $totalRecords += $stat['type_count'];
    $totalAmount += $stat['total_commission'] ?? 0;
}

// Handle reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    // Verify confirmation
    if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'RESET') {
        $error = 'Please type "RESET" to confirm deletion of all commission records.';
    } else {
        try {
            $db->beginTransaction();

            // Count records before deletion
            $countQuery = "SELECT COUNT(*) as count FROM commission_earned WHERE event_id = :event_id";
            $countStmt = $db->prepare($countQuery);
            $countStmt->bindParam(':event_id', $eventId);
            $countStmt->execute();
            $beforeCount = $countStmt->fetch()['count'];

            // Delete all commission records for this event
            $deleteQuery = "DELETE FROM commission_earned WHERE event_id = :event_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':event_id', $eventId);
            $deleteStmt->execute();

            $db->commit();

            $success = "Successfully reset all commission records! Deleted $beforeCount commission records for event: {$event['event_name']}";

            // Refresh stats
            $totalRecords = 0;
            $totalAmount = 0;
            $commissionStats = [];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = "Error resetting commission records: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Commission Records - <?php echo htmlspecialchars($event['event_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        :root {
            --primary: #4A90E2;
            --danger: #E74C3C;
            --success: #2ECC71;
            --warning: #F39C12;
            --text: #2C3E50;
            --bg-light: #F8F9FA;
            --border: #DEE2E6;
            --radius-sm: 6px;
            --spacing-sm: 8px;
            --spacing-md: 16px;
            --spacing-lg: 24px;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-light);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 var(--spacing-md);
        }

        .card {
            background: white;
            border-radius: var(--radius-sm);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        h1 {
            margin: 0 0 var(--spacing-sm) 0;
            color: var(--text);
            font-size: 28px;
        }

        .breadcrumb {
            color: #6C757D;
            margin-bottom: var(--spacing-lg);
            font-size: 14px;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-sm);
            margin-bottom: var(--spacing-lg);
        }

        .alert-success {
            background: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .alert-danger {
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }

        .alert-warning {
            background: #FFF3CD;
            color: #856404;
            border: 1px solid #FFEAA7;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin: var(--spacing-lg) 0;
        }

        .stat-card {
            background: var(--bg-light);
            padding: var(--spacing-md);
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
        }

        .stat-card.danger {
            border-left-color: var(--danger);
        }

        .stat-label {
            font-size: 12px;
            color: #6C757D;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--text);
            margin-top: var(--spacing-sm);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: var(--spacing-lg) 0;
        }

        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .table th {
            background: var(--bg-light);
            font-weight: 600;
            font-size: 14px;
            color: #6C757D;
        }

        .warning-box {
            background: #FFF3CD;
            border: 2px solid #FFC107;
            border-radius: var(--radius-sm);
            padding: var(--spacing-lg);
            margin: var(--spacing-lg) 0;
        }

        .warning-box h3 {
            color: #856404;
            margin: 0 0 var(--spacing-md) 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .warning-box ul {
            margin: var(--spacing-md) 0;
            padding-left: var(--spacing-lg);
        }

        .warning-box li {
            margin: var(--spacing-sm) 0;
            color: #856404;
        }

        .form-group {
            margin: var(--spacing-lg) 0;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-family: monospace;
            text-align: center;
            font-weight: bold;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #C0392B;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .btn-secondary {
            background: #6C757D;
            color: white;
        }

        .btn-secondary:hover {
            background: #5A6268;
        }

        .button-group {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-lg);
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-primary {
            background: #E3F2FD;
            color: #1976D2;
        }

        .badge-success {
            background: #E8F5E9;
            color: #388E3C;
        }

        .badge-warning {
            background: #FFF3E0;
            color: #F57C00;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="/public/group-admin/dashboard.php">Dashboard</a> /
            <a href="/public/group-admin/lottery/lottery.php">Lottery Events</a> /
            <a href="/public/group-admin/lottery/lottery-reports.php?id=<?php echo $eventId; ?>">Reports</a> /
            Reset Commission
        </div>

        <h1>🔄 Reset Commission Records</h1>
        <p style="color: #6C757D; margin-top: 0;">Event: <strong><?php echo htmlspecialchars($event['event_name']); ?></strong></p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Current Statistics -->
        <div class="card">
            <h2 style="margin-top: 0;">📊 Current Commission Statistics</h2>

            <div class="stats-grid">
                <div class="stat-card danger">
                    <div class="stat-label">Total Records</div>
                    <div class="stat-value"><?php echo number_format($totalRecords); ?></div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-label">Total Commission Amount</div>
                    <div class="stat-value">₹<?php echo number_format($totalAmount, 2); ?></div>
                </div>
            </div>

            <?php if (!empty($commissionStats)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Commission Type</th>
                            <th>Record Count</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commissionStats as $stat): ?>
                            <tr>
                                <td>
                                    <?php
                                    $typeLabels = [
                                        'early' => '⚡ Early Payment',
                                        'standard' => '📅 Standard Payment',
                                        'extra_books' => '📚 Extra Books'
                                    ];
                                    echo $typeLabels[$stat['commission_type']] ?? $stat['commission_type'];
                                    ?>
                                </td>
                                <td><?php echo number_format($stat['type_count']); ?></td>
                                <td>₹<?php echo number_format($stat['total_commission'] ?? 0, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-warning">
                    ℹ️ No commission records found for this event.
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalRecords > 0): ?>
            <!-- Reset Form -->
            <div class="card">
                <h2 style="margin-top: 0;">⚠️ Reset All Commission Records</h2>

                <div class="warning-box">
                    <h3>⚠️ WARNING: This action is PERMANENT</h3>
                    <p>Resetting commission records will:</p>
                    <ul>
                        <li><strong>Delete ALL <?php echo number_format($totalRecords); ?> commission records</strong> for this event</li>
                        <li>Remove total ₹<?php echo number_format($totalAmount, 2); ?> in commission data</li>
                        <li>This action CANNOT be undone</li>
                        <li>Commission will be recalculated when you re-upload Excel data</li>
                    </ul>
                    <p><strong>Use this when:</strong></p>
                    <ul>
                        <li>You need to re-upload Excel data with corrected payments</li>
                        <li>Commission settings have changed and you want to recalculate</li>
                        <li>You need to fix incorrect commission calculations</li>
                    </ul>
                </div>

                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to DELETE ALL commission records? This CANNOT be undone!');">
                    <input type="hidden" name="action" value="reset">

                    <div class="form-group">
                        <label for="confirm">Type "RESET" to confirm:</label>
                        <input
                            type="text"
                            id="confirm"
                            name="confirm"
                            class="form-control"
                            placeholder="Type RESET here"
                            autocomplete="off"
                            required>
                        <small style="color: #6C757D; display: block; margin-top: var(--spacing-sm);">
                            Type the word "RESET" exactly (all caps) to enable the reset button
                        </small>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-danger">
                            🗑️ Reset All Commission Records (<?php echo number_format($totalRecords); ?>)
                        </button>
                        <a href="/public/group-admin/lottery/lottery-reports.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="card">
                <p style="text-align: center; color: #6C757D;">
                    No commission records to reset. Commission will be calculated automatically when you upload payment data.
                </p>
                <div style="text-align: center; margin-top: var(--spacing-lg);">
                    <a href="/public/group-admin/lottery/lottery-reports.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">
                        ← Back to Reports
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Enable submit button only when "RESET" is typed exactly
        document.getElementById('confirm').addEventListener('input', function(e) {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (e.target.value === 'RESET') {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
            }
        });

        // Disable submit button initially
        document.querySelector('button[type="submit"]').disabled = true;
        document.querySelector('button[type="submit"]').style.opacity = '0.5';
    </script>
</body>
</html>
