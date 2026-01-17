<?php
/**
 * Bulk Assign Multiple Books to One Person
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$eventId) {
    header("Location: /public/group-admin/lottery/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get event details
$query = "SELECT * FROM lottery_events WHERE event_id = :event_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery/lottery.php");
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

// Check if Extra Books Commission is enabled
$commissionQuery = "SELECT extra_books_commission_enabled, extra_books_date, extra_books_commission_percent
                    FROM commission_settings
                    WHERE event_id = :event_id";
$stmt = $db->prepare($commissionQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$commissionSettings = $stmt->fetch();
$extraBooksEnabled = $commissionSettings && $commissionSettings['extra_books_commission_enabled'];

// Get available books for this event
$booksQuery = "SELECT * FROM lottery_books
               WHERE event_id = :event_id
               AND book_status = 'available'
               ORDER BY book_number";
$stmt = $db->prepare($booksQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$availableBooks = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = Validator::sanitizeString($_POST['notes'] ?? '');
    $mobile = Validator::sanitizeString($_POST['mobile'] ?? '');
    $selectedBooks = $_POST['book_ids'] ?? [];

    // Validate at least one book is selected
    if (empty($selectedBooks)) {
        $error = 'Please select at least one book to assign';
    } else {
        // Get distribution level selections
        $distributionData = [];
        $lastValueId = null;

        foreach ($levels as $level) {
            $selectedValueId = $_POST["level_{$level['level_id']}_id"] ?? '';
            $selectedValue = $_POST["level_{$level['level_id']}"] ?? '';
            $newValue = trim($_POST["new_level_{$level['level_id']}"] ?? '');

            // If "Add New" is selected and new value is provided
            if ($selectedValue === '__new__' && !empty($newValue)) {
                $insertQuery = "INSERT INTO distribution_level_values (level_id, value_name, parent_value_id) VALUES (:level_id, :value_name, :parent_value_id)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->bindParam(':level_id', $level['level_id']);
                $insertStmt->bindParam(':value_name', $newValue);
                $insertStmt->bindValue(':parent_value_id', $lastValueId, PDO::PARAM_INT);
                $insertStmt->execute();
                $lastValueId = $db->lastInsertId();
                $selectedValue = $newValue;
            } elseif (!empty($selectedValueId)) {
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

            // Check if marked as extra books
            $isExtraBook = isset($_POST['is_extra_book']) ? 1 : 0;

            // Begin transaction
            try {
                $db->beginTransaction();

                $assignedCount = 0;
                $distributedBy = AuthMiddleware::getUserId();

                // Assign all selected books
                foreach ($selectedBooks as $bookId) {
                    $bookId = (int)$bookId;

                    // Verify book is still available
                    $checkQuery = "SELECT book_status FROM lottery_books WHERE book_id = :book_id AND event_id = :event_id";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':book_id', $bookId);
                    $checkStmt->bindParam(':event_id', $eventId);
                    $checkStmt->execute();
                    $bookStatus = $checkStmt->fetch();

                    if ($bookStatus && $bookStatus['book_status'] === 'available') {
                        $query = "INSERT INTO book_distribution (book_id, notes, mobile_number, distribution_path, distributed_by, is_extra_book)
                                  VALUES (:book_id, :notes, :mobile, :distribution_path, :distributed_by, :is_extra_book)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':book_id', $bookId);
                        $stmt->bindParam(':notes', $notes);
                        $stmt->bindParam(':mobile', $mobile);
                        $stmt->bindParam(':distribution_path', $distributionPath);
                        $stmt->bindParam(':distributed_by', $distributedBy);
                        $stmt->bindParam(':is_extra_book', $isExtraBook);

                        if ($stmt->execute()) {
                            $assignedCount++;
                        }
                    }
                }

                $db->commit();

                if ($assignedCount > 0) {
                    $success = "Successfully assigned {$assignedCount} book(s) to {$notes}";
                    // Redirect after 2 seconds
                    header("refresh:2;url=/public/group-admin/lottery/lottery-books.php?id={$eventId}");
                } else {
                    $error = 'No books were assigned. They may have been assigned to someone else already.';
                }

            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to assign books: ' . $e->getMessage();
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
    <title>Bulk Assign Books - <?php echo APP_NAME; ?></title>
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

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .book-card {
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .book-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-md);
        }

        .book-card.selected {
            border-color: var(--success-color);
            background: #f0fdf4;
        }

        .book-card input[type="checkbox"] {
            margin-right: var(--spacing-xs);
        }

        .book-number {
            font-size: var(--font-size-lg);
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: var(--spacing-xs);
        }

        .ticket-range {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }

        .add-new-field {
            display: none;
            margin-top: var(--spacing-sm);
        }

        .add-new-field.show {
            display: block;
        }

        .selected-count {
            position: sticky;
            top: 20px;
            background: var(--info-color);
            color: white;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            text-align: center;
            font-weight: 600;
            margin-bottom: var(--spacing-lg);
            display: none;
        }

        .selected-count.show {
            display: block;
        }

        @media (max-width: 768px) {
            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1>📚 Bulk Assign Books</h1>
            <p style="margin: var(--spacing-sm) 0 0 0; opacity: 0.9;">
                Assign multiple books to one person at once
            </p>
        </div>
    </div>

    <div class="container main-content">
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">← Back to Books</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <br>Redirecting back to Books page...</div>
        <?php endif; ?>

        <div class="info-box">
            <h3>Event: <?php echo htmlspecialchars($event['event_name']); ?></h3>
            <p>Available Books: <strong><?php echo count($availableBooks); ?></strong></p>
        </div>

        <?php if (count($availableBooks) === 0): ?>
            <div class="alert alert-warning">
                No available books to assign. All books have been distributed.
            </div>
            <a href="/public/group-admin/lottery/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-primary">Back to Books</a>
        <?php else: ?>
            <form method="POST" action="" id="bulkAssignForm">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">1️⃣ Select Books to Assign</h3>
                    </div>
                    <div class="card-body">
                        <div id="selectedCount" class="selected-count">
                            <span id="countText">0 books selected</span>
                        </div>

                        <div class="books-grid">
                            <?php foreach ($availableBooks as $book): ?>
                                <label class="book-card" data-book-id="<?php echo $book['book_id']; ?>">
                                    <input type="checkbox" name="book_ids[]" value="<?php echo $book['book_id']; ?>" onchange="updateSelectedCount()">
                                    <div class="book-number">Book #<?php echo htmlspecialchars($book['book_number']); ?></div>
                                    <div class="ticket-range">
                                        Tickets: <?php echo $book['start_ticket_number']; ?> - <?php echo $book['end_ticket_number']; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">2️⃣ Member Details</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($levels) > 0): ?>
                            <?php foreach ($levels as $level): ?>
                                <div class="form-group">
                                    <label class="form-label"><?php echo htmlspecialchars($level['level_name']); ?> *</label>
                                    <select
                                        name="level_<?php echo $level['level_id']; ?>"
                                        class="form-control level-select"
                                        data-level-id="<?php echo $level['level_id']; ?>"
                                        data-level-number="<?php echo $level['level_number']; ?>"
                                        onchange="handleLevelChange(this)"
                                        required
                                    >
                                        <option value="">Select <?php echo htmlspecialchars($level['level_name']); ?></option>
                                        <?php foreach ($levelValues[$level['level_id']] as $value): ?>
                                            <option value="<?php echo htmlspecialchars($value['value_name']); ?>" data-value-id="<?php echo $value['value_id']; ?>">
                                                <?php echo htmlspecialchars($value['value_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="__new__">➕ Add New...</option>
                                    </select>
                                    <input type="hidden" name="level_<?php echo $level['level_id']; ?>_id" class="level-value-id">
                                    <div class="add-new-field" id="new_level_<?php echo $level['level_id']; ?>">
                                        <input type="text" name="new_level_<?php echo $level['level_id']; ?>" class="form-control" placeholder="Enter new <?php echo strtolower($level['level_name']); ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Member Name / Notes (Optional)</label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g., John Doe, or any notes about this assignment">
                            <small class="form-text">Optional: Add member name or any notes about this bulk assignment</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mobile Number (Optional)</label>
                            <input type="tel" name="mobile" class="form-control" placeholder="e.g., 9876543210" pattern="[0-9]{10}">
                            <small class="form-text">Optional: 10-digit mobile number</small>
                        </div>

                        <?php if ($extraBooksEnabled): ?>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_extra_book">
                                    Mark as Extra Book (<?php echo $commissionSettings['extra_books_commission_percent']; ?>% commission)
                                </label>
                                <small class="form-text">Check this if assigning additional books beyond the standard allocation</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-success btn-lg">✓ Assign Selected Books</button>
                    <a href="/public/group-admin/lottery/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Store all level data for cascading
        const allLevels = <?php echo json_encode($levels); ?>;
        const allLevelValues = <?php echo json_encode($allValues); ?>;

        // Update selected books count
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('input[name="book_ids[]"]:checked');
            const count = checkboxes.length;
            const countDisplay = document.getElementById('selectedCount');
            const countText = document.getElementById('countText');

            if (count > 0) {
                countText.textContent = count + (count === 1 ? ' book selected' : ' books selected');
                countDisplay.classList.add('show');
            } else {
                countDisplay.classList.remove('show');
            }

            // Update card visual state
            document.querySelectorAll('.book-card').forEach(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                if (checkbox && checkbox.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        }

        // Handle level dropdown changes
        function handleLevelChange(selectElement) {
            const levelId = selectElement.getAttribute('data-level-id');
            const levelNumber = parseInt(selectElement.getAttribute('data-level-number'));
            const selectedValue = selectElement.value;
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const valueId = selectedOption.getAttribute('data-value-id');

            // Store the value_id in hidden field
            const hiddenInput = document.querySelector(`input[name="level_${levelId}_id"]`);
            if (hiddenInput) {
                hiddenInput.value = valueId || '';
            }

            // Show/hide "Add New" field
            const newField = document.getElementById(`new_level_${levelId}`);
            if (selectedValue === '__new__') {
                newField.classList.add('show');
            } else {
                newField.classList.remove('show');
            }

            // Update dependent dropdowns
            updateDependentDropdowns(levelNumber, valueId);
        }

        // Update dependent level dropdowns based on parent selection
        function updateDependentDropdowns(changedLevel, selectedValueId) {
            allLevels.forEach((level, index) => {
                if (level.level_number > changedLevel) {
                    const selectElement = document.querySelector(`select[data-level-id="${level.level_id}"]`);
                    if (selectElement) {
                        // Clear selection
                        selectElement.value = '';
                        const hiddenInput = document.querySelector(`input[name="level_${level.level_id}_id"]`);
                        if (hiddenInput) hiddenInput.value = '';

                        // Filter options based on parent
                        const firstOption = selectElement.options[0];
                        const addNewOption = selectElement.options[selectElement.options.length - 1];
                        selectElement.innerHTML = '';
                        selectElement.appendChild(firstOption);

                        // Get parent value ID for this level
                        let parentValueId = null;
                        if (level.level_number === changedLevel + 1) {
                            parentValueId = selectedValueId;
                        } else {
                            // Get parent from previous level
                            const prevLevel = allLevels[index - 1];
                            const prevSelect = document.querySelector(`select[data-level-id="${prevLevel.level_id}"]`);
                            if (prevSelect) {
                                const prevOption = prevSelect.options[prevSelect.selectedIndex];
                                parentValueId = prevOption ? prevOption.getAttribute('data-value-id') : null;
                            }
                        }

                        // Add filtered values
                        allLevelValues.forEach(val => {
                            if (val.level_id == level.level_id) {
                                if (!parentValueId || val.parent_value_id == parentValueId) {
                                    const option = document.createElement('option');
                                    option.value = val.value_name;
                                    option.setAttribute('data-value-id', val.value_id);
                                    option.textContent = val.value_name;
                                    selectElement.appendChild(option);
                                }
                            }
                        });

                        selectElement.appendChild(addNewOption);
                    }
                }
            });
        }

        // Toggle book selection when clicking card
        document.querySelectorAll('.book-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.type !== 'checkbox') {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    updateSelectedCount();
                }
            });
        });
    </script>

    <?php include __DIR__ . '/../includes/toast-handler.php'; ?>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
