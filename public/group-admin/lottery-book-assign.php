<?php
/**
 * Assign Book to Member
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$bookId = Validator::sanitizeInt($_GET['book_id'] ?? 0);
$eventId = Validator::sanitizeInt($_GET['event_id'] ?? 0);

if (!$bookId || !$eventId) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get book details
$query = "SELECT lb.*, le.event_name, le.tickets_per_book, le.price_per_ticket
          FROM lottery_books lb
          JOIN lottery_events le ON lb.event_id = le.event_id
          WHERE lb.book_id = :book_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':book_id', $bookId);
$stmt->execute();
$book = $stmt->fetch();

if (!$book) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Get distribution levels for this event
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($levelsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$levels = $stmt->fetchAll();

// Get all level values with parent relationships
$levelValues = [];
$allValues = []; // For JavaScript
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = Validator::sanitizeString($_POST['notes'] ?? '');
    $mobile = Validator::sanitizeString($_POST['mobile'] ?? '');

    // Get distribution level selections
    $distributionData = [];
    $lastValueId = null; // Track parent value ID for hierarchical relationship

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

    // Validate distribution levels - all configured levels must be selected
    $missingLevels = [];
    foreach ($levels as $level) {
        if (empty($distributionData[$level['level_name']])) {
            $missingLevels[] = $level['level_name'];
        }
    }

    if (count($levels) > 0 && count($missingLevels) > 0) {
        $error = 'Please select: ' . implode(', ', $missingLevels);
    } else {
        // Build distribution path (e.g., "Wing A > Floor 2 > Flat 101")
        $distributionPath = !empty($distributionData) ? implode(' > ', $distributionData) : '';

        // Assign book
        $query = "INSERT INTO book_distribution (book_id, notes, mobile_number, distribution_path, distributed_by)
                  VALUES (:book_id, :notes, :mobile, :distribution_path, :distributed_by)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':book_id', $bookId);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':distribution_path', $distributionPath);
        $distributedBy = AuthMiddleware::getUserId();
        $stmt->bindParam(':distributed_by', $distributedBy);

        if ($stmt->execute()) {
            header("Location: /public/group-admin/lottery-books.php?id={$eventId}");
            exit;
        } else {
            $error = 'Failed to assign book';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Book - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
    <style>
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .info-box {
            background: var(--gray-50);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .add-new-field {
            display: none;
            margin-top: var(--spacing-sm);
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
            <h1>Assign Book <?php echo $book['book_number']; ?></h1>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="responsive-grid-2">
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Book Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Book Number:</strong> <?php echo $book['book_number']; ?></div>
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Ticket Range:</strong> <?php echo $book['start_ticket_number']; ?> - <?php echo $book['end_ticket_number']; ?></div>
                            <div style="margin-bottom: var(--spacing-sm);"><strong>Total Tickets:</strong> <?php echo $book['tickets_per_book']; ?></div>
                            <div><strong>Book Value:</strong> ₹<?php echo number_format($book['tickets_per_book'] * $book['price_per_ticket']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Assign To</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="assignForm">
                            <?php if (count($levels) > 0): ?>
                                <div style="background: var(--info-light); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                                    <strong>📍 Distribution Levels</strong>
                                    <p style="margin: var(--spacing-xs) 0 0 0; font-size: var(--font-size-sm);">
                                        Select location or add new values
                                    </p>
                                </div>

                                <?php foreach ($levels as $index => $level): ?>
                                    <div class="form-group">
                                        <label class="form-label"><?php echo htmlspecialchars($level['level_name']); ?></label>
                                        <input type="hidden" name="level_<?php echo $level['level_id']; ?>_id" id="level_<?php echo $level['level_id']; ?>_id">
                                        <select
                                            name="level_<?php echo $level['level_id']; ?>"
                                            id="level_<?php echo $level['level_id']; ?>"
                                            class="form-control level-select"
                                            data-level-id="<?php echo $level['level_id']; ?>"
                                            data-level-number="<?php echo $level['level_number']; ?>"
                                            onchange="handleLevelChange(<?php echo $level['level_id']; ?>, <?php echo $level['level_number']; ?>)"
                                        >
                                            <option value="">- Select <?php echo htmlspecialchars($level['level_name']); ?> -</option>
                                            <?php foreach ($levelValues[$level['level_id']] as $value): ?>
                                                <option
                                                    value="<?php echo htmlspecialchars($value['value_name']); ?>"
                                                    data-value-id="<?php echo $value['value_id']; ?>"
                                                    data-parent-id="<?php echo $value['parent_value_id'] ?? ''; ?>"
                                                >
                                                    <?php echo htmlspecialchars($value['value_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="__new__">➕ Add New <?php echo htmlspecialchars($level['level_name']); ?></option>
                                        </select>

                                        <div class="add-new-field" id="add_new_<?php echo $level['level_id']; ?>">
                                            <input
                                                type="text"
                                                name="new_level_<?php echo $level['level_id']; ?>"
                                                class="form-control"
                                                placeholder="Enter new <?php echo strtolower($level['level_name']); ?>"
                                            >
                                            <small class="form-text">Enter new value and it will be saved for future use</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <hr style="margin: var(--spacing-xl) 0;">
                            <?php endif; ?>

                            <div class="form-group">
                                <label class="form-label">Notes (Optional)</label>
                                <input
                                    type="text"
                                    name="notes"
                                    class="form-control"
                                    placeholder="e.g., Member name, location, or any other notes"
                                >
                                <small class="form-text">Optional: Add any notes about this assignment</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Mobile Number (Optional)</label>
                                <input
                                    type="tel"
                                    name="mobile"
                                    class="form-control"
                                    placeholder="10-digit mobile number"
                                    maxlength="10"
                                    pattern="[6-9][0-9]{9}"
                                >
                            </div>

                            <div class="button-group-mobile">
                                <button type="submit" class="btn btn-primary btn-lg">Assign Book</button>
                                <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Instructions</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($levels) > 0): ?>
                            <p><strong>Step 1: Select Distribution Levels (Required)</strong></p>
                            <ul>
                                <?php foreach ($levels as $level): ?>
                                    <li><strong><?php echo htmlspecialchars($level['level_name']); ?>:</strong> Required - Select or add new</li>
                                <?php endforeach; ?>
                            </ul>
                            <p><strong>Step 2: Enter Additional Details (Optional)</strong></p>
                            <ul>
                                <li>Notes (optional) - Member name or any notes</li>
                                <li>Mobile number (optional)</li>
                            </ul>
                        <?php else: ?>
                            <p>Add optional notes and mobile number for this book assignment.</p>
                            <p><strong>Note:</strong> No distribution levels configured. You can set these up in Distribution Setup.</p>
                        <?php endif; ?>

                        <p><strong>After Assignment:</strong></p>
                        <ul>
                            <li>Book status changes to "Distributed"</li>
                            <li>You can collect payment from this member</li>
                            <li>Book appears in payment tracking</li>
                        </ul>
                    </div>
                </div>

                <?php if (count($levels) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">💡 Adding New Values</h4>
                        </div>
                        <div class="card-body">
                            <p>Select "➕ Add New" from any dropdown to add a new value. The value will be saved and available for future assignments.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Store all level data for cascading
        const allLevels = <?php echo json_encode($levels); ?>;
        const allLevelValues = <?php echo json_encode($allValues); ?>;

        function handleLevelChange(levelId, levelNumber) {
            const select = document.getElementById(`level_${levelId}`);
            const selectedOption = select.options[select.selectedIndex];
            const selectedValueId = selectedOption.getAttribute('data-value-id');
            const hiddenInput = document.getElementById(`level_${levelId}_id`);

            // Store the value_id in hidden field
            if (selectedValueId) {
                hiddenInput.value = selectedValueId;
            } else {
                hiddenInput.value = '';
            }

            // Handle "Add New" toggle
            toggleAddNew(levelId);

            // Filter next level dropdown (if exists)
            filterNextLevel(levelId, levelNumber, selectedValueId);
        }

        function toggleAddNew(levelId) {
            const select = document.getElementById(`level_${levelId}`);
            const addNewField = document.getElementById(`add_new_${levelId}`);
            const input = addNewField.querySelector('input');

            if (select.value === '__new__') {
                addNewField.classList.add('show');
                input.required = true;
                input.focus();
            } else {
                addNewField.classList.remove('show');
                input.required = false;
                input.value = '';
            }
        }

        function filterNextLevel(currentLevelId, currentLevelNumber, parentValueId) {
            // Find next level
            const nextLevel = allLevels.find(level => level.level_number === currentLevelNumber + 1);

            if (!nextLevel) {
                return; // No next level exists
            }

            const nextSelect = document.getElementById(`level_${nextLevel.level_id}`);
            if (!nextSelect) {
                return;
            }

            // Get all options for next level
            const allOptions = nextSelect.querySelectorAll('option');

            // Reset next level
            nextSelect.value = '';
            document.getElementById(`level_${nextLevel.level_id}_id`).value = '';

            // Hide/show options based on parent
            allOptions.forEach(option => {
                const optionParentId = option.getAttribute('data-parent-id');
                const optionValueId = option.getAttribute('data-value-id');

                // Always show default option and "Add New" option
                if (option.value === '' || option.value === '__new__') {
                    option.style.display = '';
                    return;
                }

                // Show option if:
                // 1. No parent selected (show all)
                // 2. Option's parent_id matches selected parent's value_id
                // 3. Option has no parent (parent_value_id is null) for level 1 values
                if (!parentValueId) {
                    // No parent selected, hide all child options
                    option.style.display = 'none';
                } else if (optionParentId === parentValueId) {
                    // Parent matches, show this option
                    option.style.display = '';
                } else {
                    // No match, hide option
                    option.style.display = 'none';
                }
            });

            // Also reset all subsequent levels
            resetSubsequentLevels(currentLevelNumber + 1);
        }

        function resetSubsequentLevels(fromLevelNumber) {
            // Reset all levels after the changed level
            allLevels.forEach(level => {
                if (level.level_number > fromLevelNumber) {
                    const select = document.getElementById(`level_${level.level_id}`);
                    const hiddenInput = document.getElementById(`level_${level.level_id}_id`);
                    if (select) {
                        select.value = '';
                        const allOptions = select.querySelectorAll('option');
                        allOptions.forEach(option => {
                            if (option.value !== '' && option.value !== '__new__') {
                                option.style.display = 'none';
                            }
                        });
                    }
                    if (hiddenInput) {
                        hiddenInput.value = '';
                    }
                }
            });
        }

        // Initialize on page load - show only level 1 values initially
        document.addEventListener('DOMContentLoaded', function() {
            // For all levels except the first, hide all value options initially
            allLevels.forEach((level, index) => {
                if (index > 0) { // Not the first level
                    const select = document.getElementById(`level_${level.level_id}`);
                    if (select) {
                        const allOptions = select.querySelectorAll('option');
                        allOptions.forEach(option => {
                            if (option.value !== '' && option.value !== '__new__') {
                                option.style.display = 'none';
                            }
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>
