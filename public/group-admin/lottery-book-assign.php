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

// Get all level values
$levelValues = [];
foreach ($levels as $level) {
    $valuesQuery = "SELECT * FROM distribution_level_values WHERE level_id = :level_id ORDER BY value_name";
    $stmt = $db->prepare($valuesQuery);
    $stmt->bindParam(':level_id', $level['level_id']);
    $stmt->execute();
    $levelValues[$level['level_id']] = $stmt->fetchAll();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberName = Validator::sanitizeString($_POST['member_name'] ?? '');
    $mobile = Validator::sanitizeString($_POST['mobile'] ?? '');

    // Get distribution level selections
    $distributionData = [];
    foreach ($levels as $level) {
        $selectedValue = $_POST["level_{$level['level_id']}"] ?? '';
        $newValue = trim($_POST["new_level_{$level['level_id']}"] ?? '');

        // If "Add New" is selected and new value is provided
        if ($selectedValue === '__new__' && !empty($newValue)) {
            // Insert new value
            $insertQuery = "INSERT INTO distribution_level_values (level_id, value_name) VALUES (:level_id, :value_name)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':level_id', $level['level_id']);
            $insertStmt->bindParam(':value_name', $newValue);
            $insertStmt->execute();
            $selectedValue = $newValue;
        }

        if (!empty($selectedValue) && $selectedValue !== '__new__') {
            $distributionData[$level['level_name']] = $selectedValue;
        }
    }

    if (empty($memberName)) {
        $error = 'Member name is required';
    } else {
        // Build distribution path (e.g., "Wing A > Floor 2 > Flat 101")
        $distributionPath = !empty($distributionData) ? implode(' > ', $distributionData) : '';

        // Assign book
        $query = "INSERT INTO book_distribution (book_id, member_name, mobile_number, distribution_path, distributed_by)
                  VALUES (:book_id, :member_name, :mobile, :distribution_path, :distributed_by)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':book_id', $bookId);
        $stmt->bindParam(':member_name', $memberName);
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

        <div class="row">
            <div class="col-6">
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
                                        <select
                                            name="level_<?php echo $level['level_id']; ?>"
                                            class="form-control level-select"
                                            data-level-id="<?php echo $level['level_id']; ?>"
                                            onchange="toggleAddNew(<?php echo $level['level_id']; ?>)"
                                        >
                                            <option value="">- Select <?php echo htmlspecialchars($level['level_name']); ?> -</option>
                                            <?php foreach ($levelValues[$level['level_id']] as $value): ?>
                                                <option value="<?php echo htmlspecialchars($value['value_name']); ?>">
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
                                <label class="form-label form-label-required">Member Name</label>
                                <input
                                    type="text"
                                    name="member_name"
                                    class="form-control"
                                    placeholder="Enter member name"
                                    required
                                    autofocus
                                >
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

                            <div style="display: flex; gap: var(--spacing-md);">
                                <button type="submit" class="btn btn-primary btn-lg">Assign Book</button>
                                <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Instructions</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($levels) > 0): ?>
                            <p><strong>Step 1: Select Distribution Levels</strong></p>
                            <ul>
                                <?php foreach ($levels as $level): ?>
                                    <li>Choose <?php echo htmlspecialchars($level['level_name']); ?> or add new</li>
                                <?php endforeach; ?>
                            </ul>
                            <p><strong>Step 2: Enter Member Details</strong></p>
                            <ul>
                                <li>Enter member name (required)</li>
                                <li>Add mobile number (optional)</li>
                            </ul>
                        <?php else: ?>
                            <p>Enter the name of the member to whom you want to assign this lottery book.</p>
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
        function toggleAddNew(levelId) {
            const select = document.querySelector(`select[data-level-id="${levelId}"]`);
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
    </script>
</body>
</html>
