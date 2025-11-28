<?php
/**
 * Create Lottery Event - Part 1
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$communityId = AuthMiddleware::getCommunityId();

if (!$communityId) {
    die('<div class="alert alert-danger">You are not assigned to any community.</div>');
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $eventName = Validator::sanitizeString($_POST['event_name'] ?? '');
        $description = Validator::sanitizeString($_POST['description'] ?? '');

        $validator = new Validator();
        $validator->required('event_name', $eventName, 'Event Name');

        if ($validator->fails()) {
            $errors = $validator->getErrors();
        } else {
            $database = new Database();
            $db = $database->getConnection();

            $query = "INSERT INTO lottery_events (community_id, event_name, event_description, status, created_by,
                      total_books, tickets_per_book, price_per_ticket, first_ticket_number)
                      VALUES (:community_id, :name, :description, 'draft', :created_by, 0, 0, 0, 0)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':community_id', $communityId);
            $stmt->bindParam(':name', $eventName);
            $stmt->bindParam(':description', $description);
            $createdBy = AuthMiddleware::getUserId();
            $stmt->bindParam(':created_by', $createdBy);

            if ($stmt->execute()) {
                $eventId = $db->lastInsertId();
                header("Location: /public/group-admin/lottery-books-generate.php?id={$eventId}");
                exit;
            } else {
                $error = 'Failed to create event';
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
    <title>Create Lottery Event - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
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
        .example-box {
            background: var(--gray-50);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-top: var(--spacing-md);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Create Lottery Event</h1>
            <p style="margin: 0; opacity: 0.9;">Part 1 of 6</p>
        </div>
    </div>

    <div class="container main-content">
        <div class="instructions">
            <h3 style="margin-top: 0;">🎯 Part 1: Name Your Event</h3>
            <p>Give your lottery event a clear name that everyone will recognize.</p>
            <div class="example-box">
                <strong>Examples:</strong>
                <ul style="margin: var(--spacing-sm) 0;">
                    <li>"Diwali 2025 Lottery"</li>
                    <li>"New Year Celebration 2025"</li>
                    <li>"Christmas Lottery 2024"</li>
                </ul>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Event Details</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label form-label-required">Event Name</label>
                                <input
                                    type="text"
                                    name="event_name"
                                    class="form-control <?php echo isset($errors['event_name']) ? 'is-invalid' : ''; ?>"
                                    placeholder="e.g., Diwali 2025 Lottery"
                                    value="<?php echo htmlspecialchars($_POST['event_name'] ?? ''); ?>"
                                    required
                                    autofocus
                                >
                                <?php if (isset($errors['event_name'])): ?>
                                    <span class="form-error"><?php echo $errors['event_name']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description (Optional)</label>
                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Add any notes about this event (optional)"
                                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div style="display: flex; gap: var(--spacing-md);">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Create & Continue to Generate Books →
                                </button>
                                <a href="/public/group-admin/lottery.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">What Happens Next?</h4>
                    </div>
                    <div class="card-body">
                        <p>After creating the event, you'll:</p>
                        <ol style="padding-left: var(--spacing-lg);">
                            <li style="margin: var(--spacing-sm) 0;">
                                <strong>Generate Books</strong><br>
                                <span style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                    Set number of books & tickets
                                </span>
                            </li>
                            <li style="margin: var(--spacing-sm) 0;">
                                <strong>Set Distribution</strong><br>
                                <span style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                    Configure Wing/Floor/Flat
                                </span>
                            </li>
                            <li style="margin: var(--spacing-sm) 0;">
                                <strong>Distribute Books</strong><br>
                                <span style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                    Assign to members
                                </span>
                            </li>
                            <li style="margin: var(--spacing-sm) 0;">
                                <strong>Track Payments</strong><br>
                                <span style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                    Collect & monitor
                                </span>
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">💡 Quick Tip</h4>
                    </div>
                    <div class="card-body">
                        <p>Include the year in the event name to easily identify it later!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
