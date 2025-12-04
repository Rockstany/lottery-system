<?php
/**
 * Reassign Book to Different Unit
 * GetToKnow Community App
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$distributionId = Validator::sanitizeInt($_GET['dist_id'] ?? 0);
$communityId = AuthMiddleware::getCommunityId();

if (!$distributionId || !$communityId) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get distribution and book details
$query = "SELECT bd.*, lb.*, le.event_name, le.event_id
          FROM book_distribution bd
          JOIN lottery_books lb ON bd.book_id = lb.book_id
          JOIN lottery_events le ON lb.event_id = le.event_id
          WHERE bd.distribution_id = :dist_id AND le.community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':dist_id', $distributionId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$distribution = $stmt->fetch();

if (!$distribution) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$eventId = $distribution['event_id'];

// Check if there are any payments collected for this distribution
$paymentCheck = "SELECT COUNT(*) as payment_count, SUM(amount_paid) as total_paid
                 FROM payment_collections
                 WHERE distribution_id = :dist_id";
$stmt = $db->prepare($paymentCheck);
$stmt->bindParam(':dist_id', $distributionId);
$stmt->execute();
$paymentInfo = $stmt->fetch();

// Get distribution levels for this event
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($levelsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$levels = $stmt->fetchAll();

// Get all level values with parent relationships
$levelValues = [];
$allValues = [];
foreach ($levels as $level) {
    $valuesQuery = "SELECT * FROM distribution_level_values WHERE level_id = :level_id ORDER BY value_name";
    $stmt = $db->prepare($valuesQuery);
    $stmt->bindParam(':level_id', $level['level_id']);
    $stmt->execute();
    $values = $stmt->fetchAll();
    $levelValues[$level['level_id']] = $values;

    foreach ($values as $val) {
        $allValues[] = [
            'value_id' => $val['value_id'],
            'level_id' => $level['level_id'],
            'parent_value_id' => $val['parent_value_id'],
            'value_name' => $val['value_name']
        ];
    }
}

$error = '';
$success = '';

// Handle reassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // BLOCK reassignment if any payment has been made
    if ($paymentInfo['payment_count'] > 0) {
        $error = 'Cannot reassign book - ₹' . number_format($paymentInfo['total_paid']) . ' has already been collected. You cannot reassign a book after payment.';
    } else {
        $notes = Validator::sanitizeString($_POST['notes'] ?? '');
        $mobile = Validator::sanitizeString($_POST['mobile'] ?? '');

        // Get distribution level selections
        $distributionData = [];
        $lastValueId = null;

        foreach ($levels as $level) {
            $selectedValueId = $_POST["level_{$level['level_id']}_id"] ?? '';
            $selectedValue = $_POST["level_{$level['level_id']}"] ?? '';
            $newValue = trim($_POST["new_level_{$level['level_id']}"] ?? '');

            // If "Add New" is selected and new value is provided
            if ($selectedValue === '__new__' && !empty($newValue)) {
                // Insert new value with parent relationship
                $insertQuery = "INSERT INTO distribution_level_values (level_id, value_name, parent_value_id) VALUES (:level_id, :value_name, :parent_value_id)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->bindParam(':level_id', $level['level_id']);
                $insertStmt->bindParam(':value_name', $newValue);
                $insertStmt->bindValue(':parent_value_id', $lastValueId, PDO::PARAM_INT);
                $insertStmt->execute();
                $lastValueId = $db->lastInsertId();
                $selectedValue = $newValue;
            } elseif (!empty($selectedValueId)) {
                // Use existing value ID as parent for next level
                $lastValueId = $selectedValueId;
            }

            if (!empty($selectedValue) && $selectedValue !== '__new__') {
                $distributionData[$level['level_name']] = $selectedValue;
            }
        }

        // Validate distribution levels
        $missingLevels = [];
        foreach ($levels as $level) {
            if (empty($distributionData[$level['level_name']])) {
                $missingLevels[] = $level['level_name'];
            }
        }

        if (count($levels) > 0 && count($missingLevels) > 0) {
            $error = 'Please select: ' . implode(', ', $missingLevels);
        } else {
            // Build distribution path
            $distributionPath = !empty($distributionData) ? implode(' > ', $distributionData) : '';

            // Update book distribution
            $updateQuery = "UPDATE book_distribution
                           SET notes = :notes,
                               mobile_number = :mobile,
                               distribution_path = :distribution_path,
                               distributed_by = :distributed_by,
                               distributed_at = NOW()
                           WHERE distribution_id = :dist_id";
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':mobile', $mobile);
            $stmt->bindParam(':distribution_path', $distributionPath);
            $distributedBy = AuthMiddleware::getUserId();
            $stmt->bindParam(':distributed_by', $distributedBy);
            $stmt->bindParam(':dist_id', $distributionId);

            if ($stmt->execute()) {
                // Log the action
                $logQuery = "INSERT INTO activity_logs (user_id, action_type, action_description) VALUES (:user_id, 'book_reassigned', :description)";
                $logStmt = $db->prepare($logQuery);
                $userId = AuthMiddleware::getUserId();
                $description = "Reassigned Book #{$distribution['book_number']} from '{$distribution['distribution_path']}' to '{$distributionPath}'";
                $logStmt->bindParam(':user_id', $userId);
                $logStmt->bindParam(':description', $description);
                $logStmt->execute();

                header("Location: /public/group-admin/lottery-books.php?id={$eventId}&success=reassigned");
                exit;
            } else {
                $error = 'Failed to reassign book';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reassign Book - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <script src="/public/js/toast.js"></script>
    <style>
        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .info-box {
            background: var(--warning-light);
            border-left: 4px solid var(--warning-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .current-assignment {
            background: var(--gray-50);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .add-new-field {
            display: none;
            margin-top: var(--spacing-xs);
        }
        .add-new-field.show {
            display: block;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1>⚠️ Reassign Book #<?php echo $distribution['book_number']; ?></h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($distribution['event_name']); ?></p>
        </div>
    </div>

    <div class="container main-content">
        <?php include __DIR__ . '/includes/toast-handler.php'; ?>

        <!-- Back Button -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">← Back to Books</a>
        </div>

        <!-- Block if payments exist -->
        <?php if ($paymentInfo['payment_count'] > 0): ?>
            <div style="background: #fee2e2; border: 2px solid #dc2626; padding: var(--spacing-xl); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); text-align: center;">
                <div style="font-size: 64px; margin-bottom: var(--spacing-md);">🚫</div>
                <h2 style="margin-top: 0; color: #dc2626;">Reassignment Not Allowed</h2>
                <p style="font-size: 1.1em; margin: var(--spacing-md) 0;">This book has <strong><?php echo $paymentInfo['payment_count']; ?> payment transaction(s)</strong> totaling <strong style="color: #dc2626;">₹<?php echo number_format($paymentInfo['total_paid']); ?></strong>.</p>
                <p style="font-weight: 600; margin: var(--spacing-md) 0;">You cannot reassign a book after payment has been collected.</p>
                <p style="color: var(--gray-600); margin: var(--spacing-sm) 0;">This protects financial integrity and commission calculations.</p>
                <div style="margin-top: var(--spacing-lg);">
                    <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-primary">← Back to Books</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Warning Box -->
            <div class="info-box">
                <h3 style="margin-top: 0;">⚠️ Important</h3>
                <p>You are about to reassign this book to a different location. Make sure this is correct before proceeding.</p>
            </div>
        <?php endif; ?>

        <!-- Current Assignment -->
        <div class="current-assignment">
            <h3 style="margin-top: 0;">📋 Current Assignment Details</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                <div>
                    <strong>Book Number:</strong> #<?php echo $distribution['book_number']; ?>
                </div>
                <div>
                    <strong>Ticket Range:</strong> <?php echo $distribution['start_ticket_number']; ?> - <?php echo $distribution['end_ticket_number']; ?>
                </div>
                <div>
                    <strong>Current Location:</strong> <?php echo htmlspecialchars($distribution['distribution_path'] ?? 'N/A'); ?>
                </div>
                <div>
                    <strong>Mobile Number:</strong> <?php echo htmlspecialchars($distribution['mobile_number'] ?? 'N/A'); ?>
                </div>
                <div>
                    <strong>Current Notes:</strong> <?php echo htmlspecialchars($distribution['notes'] ?? 'N/A'); ?>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="info-box">
            <h3 style="margin-top: 0;">📝 Reassignment Instructions</h3>
            <ul style="margin: 0; padding-left: var(--spacing-lg);">
                <li><strong>Wrong Assignment Correction:</strong> Use this form to fix incorrect book assignments</li>
                <li><strong>Select New Location:</strong> Choose the correct distribution unit from the dropdowns below</li>
                <li><strong>Update Details:</strong> You can also update mobile number and notes</li>
                <li><strong>Add New Values:</strong> If the correct location isn't listed, select "➕ Add New" and type it in</li>
                <li><strong>Payment Records Safe:</strong> Reassigning does NOT delete or affect existing payment records</li>
            </ul>
        </div>

        <!-- Reassignment Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔄 Reassign to New Location</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if (count($levels) > 0): ?>
                        <h4>Select New Distribution Location:</h4>
                        <div class="form-row">
                            <?php foreach ($levels as $index => $level): ?>
                                <div class="form-col">
                                    <label class="form-label form-label-required">
                                        <?php echo htmlspecialchars($level['level_name']); ?>
                                    </label>
                                    <select
                                        name="level_<?php echo $level['level_id']; ?>"
                                        id="level_<?php echo $level['level_id']; ?>"
                                        class="form-control level-select"
                                        data-level-id="<?php echo $level['level_id']; ?>"
                                        data-level-number="<?php echo $level['level_number']; ?>"
                                        onchange="handleLevelChange(this, <?php echo $level['level_id']; ?>)"
                                        required>
                                        <option value="">Select <?php echo htmlspecialchars($level['level_name']); ?></option>
                                        <?php foreach ($levelValues[$level['level_id']] ?? [] as $value): ?>
                                            <option
                                                value="<?php echo htmlspecialchars($value['value_name']); ?>"
                                                data-value-id="<?php echo $value['value_id']; ?>"
                                                data-parent-id="<?php echo $value['parent_value_id'] ?? ''; ?>"
                                                <?php if ($index > 0): ?>class="level-option" style="display:none;"<?php endif; ?>>
                                                <?php echo htmlspecialchars($value['value_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="__new__">➕ Add New <?php echo htmlspecialchars($level['level_name']); ?></option>
                                    </select>
                                    <input type="hidden" name="level_<?php echo $level['level_id']; ?>_id" id="level_<?php echo $level['level_id']; ?>_id">

                                    <!-- Add New Field -->
                                    <div class="add-new-field" id="new_field_<?php echo $level['level_id']; ?>">
                                        <input type="text"
                                               name="new_level_<?php echo $level['level_id']; ?>"
                                               class="form-control"
                                               placeholder="Enter new <?php echo htmlspecialchars($level['level_name']); ?> name">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-row" style="margin-top: var(--spacing-lg);">
                        <div class="form-col">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control"
                                   value="<?php echo htmlspecialchars($distribution['mobile_number'] ?? ''); ?>"
                                   placeholder="Contact number">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control"
                                   value="<?php echo htmlspecialchars($distribution['notes'] ?? ''); ?>"
                                   placeholder="Additional notes">
                        </div>
                    </div>

                    <div style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-md);">
                        <button type="submit" class="btn btn-danger btn-lg">
                            🔄 Reassign Book
                        </button>
                        <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-secondary btn-lg">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; // End payment check for form display ?>
    </div>

    <script>
        // Store all level data for cascading
        const allLevels = <?php echo json_encode($levels); ?>;
        const allLevelValues = <?php echo json_encode($allValues); ?>;

        function handleLevelChange(selectElement, levelId) {
            const selectedValue = selectElement.value;
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const valueId = selectedOption.getAttribute('data-value-id');
            const currentLevelNumber = parseInt(selectElement.getAttribute('data-level-number'));

            // Store the selected value ID
            document.getElementById('level_' + levelId + '_id').value = valueId || '';

            // Show/hide "Add New" field
            const newField = document.getElementById('new_field_' + levelId);
            if (selectedValue === '__new__') {
                newField.classList.add('show');
            } else {
                newField.classList.remove('show');
            }

            // Reset all subsequent levels
            allLevels.forEach(level => {
                if (level.level_number > currentLevelNumber) {
                    const nextSelect = document.getElementById('level_' + level.level_id);
                    if (nextSelect) {
                        nextSelect.value = '';
                        document.getElementById('level_' + level.level_id + '_id').value = '';
                        const nextNewField = document.getElementById('new_field_' + level.level_id);
                        if (nextNewField) {
                            nextNewField.classList.remove('show');
                        }

                        // Hide all options in next level
                        const options = nextSelect.querySelectorAll('.level-option');
                        options.forEach(opt => opt.style.display = 'none');
                    }
                }
            });

            // Show relevant options in next level if exists
            const nextLevel = allLevels.find(l => l.level_number === currentLevelNumber + 1);
            if (nextLevel && valueId) {
                const nextSelect = document.getElementById('level_' + nextLevel.level_id);
                if (nextSelect) {
                    // Show options that have this value as parent
                    const options = nextSelect.querySelectorAll('.level-option');
                    let hasVisibleOptions = false;
                    options.forEach(opt => {
                        const parentId = opt.getAttribute('data-parent-id');
                        if (parentId === valueId || parentId === '') {
                            opt.style.display = '';
                            hasVisibleOptions = true;
                        }
                    });
                }
            }
        }

        // Initialize on page load - show all options for first level
        window.addEventListener('DOMContentLoaded', function() {
            if (allLevels.length > 0) {
                const firstLevel = allLevels[0];
                const firstSelect = document.getElementById('level_' + firstLevel.level_id);
                if (firstSelect) {
                    const options = firstSelect.querySelectorAll('.level-option');
                    options.forEach(opt => opt.style.display = '');
                }
            }
        });
    </script>
</body>
</html>
