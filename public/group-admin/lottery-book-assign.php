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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberName = Validator::sanitizeString($_POST['member_name'] ?? '');
    $mobile = Validator::sanitizeString($_POST['mobile'] ?? '');

    if (empty($memberName)) {
        $error = 'Member name is required';
    } else {
        // Assign book
        $query = "INSERT INTO book_distribution (book_id, member_name, mobile_number, distributed_by)
                  VALUES (:book_id, :member_name, :mobile, :distributed_by)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':book_id', $bookId);
        $stmt->bindParam(':member_name', $memberName);
        $stmt->bindParam(':mobile', $mobile);
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
    </style>
</head>
<body>
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
                        <h3 class="card-title">Assign To Member</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
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
                        <p>Enter the name of the member to whom you want to assign this lottery book.</p>
                        <p><strong>After Assignment:</strong></p>
                        <ul>
                            <li>Book status changes to "Distributed"</li>
                            <li>You can collect payment from this member</li>
                            <li>Book appears in payment tracking</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
