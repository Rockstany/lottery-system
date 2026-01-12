<?php
/**
 * Generate Lottery Books - Part 2
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
$previewData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $totalBooks = Validator::sanitizeInt($_POST['total_books'] ?? 0);
    $ticketsPerBook = Validator::sanitizeInt($_POST['tickets_per_book'] ?? 0);
    $pricePerTicket = Validator::sanitizeFloat($_POST['price_per_ticket'] ?? 0);
    $firstTicket = Validator::sanitizeInt($_POST['first_ticket_number'] ?? 1);

    if ($totalBooks < 1 || $ticketsPerBook < 1 || $pricePerTicket <= 0) {
        $error = 'All fields must have valid values';
    } else {
        // Generate preview
        $previewData = [
            'total_books' => $totalBooks,
            'tickets_per_book' => $ticketsPerBook,
            'price_per_ticket' => $pricePerTicket,
            'first_ticket' => $firstTicket,
            'total_tickets' => $totalBooks * $ticketsPerBook,
            'total_amount' => $totalBooks * $ticketsPerBook * $pricePerTicket,
            'books' => []
        ];

        // Generate first 5 books for preview
        for ($i = 1; $i <= min(5, $totalBooks); $i++) {
            $startTicket = $firstTicket + (($i - 1) * $ticketsPerBook);
            $endTicket = $startTicket + $ticketsPerBook - 1;
            $previewData['books'][] = [
                'book_number' => $i,
                'start_ticket' => $startTicket,
                'end_ticket' => $endTicket
            ];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $totalBooks = Validator::sanitizeInt($_POST['total_books'] ?? 0);
    $ticketsPerBook = Validator::sanitizeInt($_POST['tickets_per_book'] ?? 0);
    $pricePerTicket = Validator::sanitizeFloat($_POST['price_per_ticket'] ?? 0);
    $firstTicket = Validator::sanitizeInt($_POST['first_ticket_number'] ?? 1);

    // Update event
    $query = "UPDATE lottery_events
              SET total_books = :total_books,
                  tickets_per_book = :tickets_per_book,
                  price_per_ticket = :price_per_ticket,
                  first_ticket_number = :first_ticket,
                  status = 'active'
              WHERE event_id = :event_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':total_books', $totalBooks);
    $stmt->bindParam(':tickets_per_book', $ticketsPerBook);
    $stmt->bindParam(':price_per_ticket', $pricePerTicket);
    $stmt->bindParam(':first_ticket', $firstTicket);
    $stmt->bindParam(':event_id', $eventId);
    $stmt->execute();

    // Generate books
    for ($i = 1; $i <= $totalBooks; $i++) {
        $startTicket = $firstTicket + (($i - 1) * $ticketsPerBook);
        $endTicket = $startTicket + $ticketsPerBook - 1;

        $bookQuery = "INSERT INTO lottery_books (event_id, book_number, start_ticket_number, end_ticket_number, book_status)
                      VALUES (:event_id, :book_number, :start_ticket, :end_ticket, 'available')";
        $bookStmt = $db->prepare($bookQuery);
        $bookStmt->bindParam(':event_id', $eventId);
        $bookStmt->bindParam(':book_number', $i);
        $bookStmt->bindParam(':start_ticket', $startTicket);
        $bookStmt->bindParam(':end_ticket', $endTicket);
        $bookStmt->execute();
    }

    header("Location: /public/group-admin/lottery-distribution-setup.php?id={$eventId}");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Books - <?php echo APP_NAME; ?></title>
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
        .formula-box {
            background: var(--gray-900);
            color: #fff;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-family: monospace;
            margin: var(--spacing-md) 0;
        }
        .calc-display {
            background: var(--success-light);
            border: 2px solid var(--success-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin: var(--spacing-lg) 0;
        }
        .calc-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-sm) 0;
            font-size: var(--font-size-lg);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo htmlspecialchars($event['event_name']); ?> - Generate Books</h1>
            <p style="margin: 0; opacity: 0.9;">Part 2 of 6</p>
        </div>
    </div>

    <div class="container main-content">
        <div class="instructions">
            <h3 style="margin-top: 0;">📚 Part 2: Generate Lottery Books</h3>
            <p>Set how many lottery books you want and how many tickets per book. The system will automatically calculate ticket numbers!</p>
            <div class="formula-box">
Start Ticket = First Ticket + (Book Number - 1) × Tickets Per Book<br>
End Ticket = Start Ticket + Tickets Per Book - 1
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($previewData)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Book Configuration</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label form-label-required">Total Number of Books</label>
                                    <input
                                        type="number"
                                        name="total_books"
                                        class="form-control"
                                        min="1"
                                        value="50"
                                        required
                                    >
                                    <span class="form-help">How many lottery books do you need?</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label form-label-required">Tickets Per Book</label>
                                    <input
                                        type="number"
                                        name="tickets_per_book"
                                        class="form-control"
                                        min="1"
                                        value="10"
                                        required
                                    >
                                    <span class="form-help">How many tickets in each book?</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label form-label-required">Price Per Ticket</label>
                                    <input
                                        type="number"
                                        name="price_per_ticket"
                                        class="form-control"
                                        min="1"
                                        step="1"
                                        value="100"
                                        required
                                    >
                                    <span class="form-help">Price in ₹ for each ticket</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label form-label-required">First Ticket Number</label>
                                    <input
                                        type="number"
                                        name="first_ticket_number"
                                        class="form-control"
                                        min="1"
                                        value="1"
                                        required
                                    >
                                    <span class="form-help">Starting ticket number</span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="generate" class="btn btn-primary btn-lg">
                            Preview Books →
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="calc-display">
                <h3 style="margin-top: 0; color: var(--success-color);">✓ Calculation Summary</h3>
                <div class="calc-row">
                    <span>Total Books:</span>
                    <strong><?php echo $previewData['total_books']; ?></strong>
                </div>
                <div class="calc-row">
                    <span>Tickets Per Book:</span>
                    <strong><?php echo $previewData['tickets_per_book']; ?></strong>
                </div>
                <div class="calc-row">
                    <span>Total Tickets:</span>
                    <strong><?php echo number_format($previewData['total_tickets']); ?></strong>
                </div>
                <div class="calc-row">
                    <span>Price Per Ticket:</span>
                    <strong>₹<?php echo number_format($previewData['price_per_ticket']); ?></strong>
                </div>
                <div class="calc-row" style="border-top: 2px solid var(--success-color); margin-top: var(--spacing-md); padding-top: var(--spacing-md);">
                    <span style="font-size: var(--font-size-xl);">Total Expected Amount:</span>
                    <strong style="font-size: var(--font-size-2xl); color: var(--success-color);">
                        ₹<?php echo number_format($previewData['total_amount']); ?>
                    </strong>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Preview: First 5 Books</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Book #</th>
                                <th>Start Ticket</th>
                                <th>End Ticket</th>
                                <th>Total Tickets</th>
                                <th>Book Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($previewData['books'] as $book): ?>
                                <tr>
                                    <td><strong>Book <?php echo $book['book_number']; ?></strong></td>
                                    <td><?php echo $book['start_ticket']; ?></td>
                                    <td><?php echo $book['end_ticket']; ?></td>
                                    <td><?php echo $previewData['tickets_per_book']; ?></td>
                                    <td>₹<?php echo number_format($previewData['tickets_per_book'] * $previewData['price_per_ticket']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($previewData['total_books'] > 5): ?>
                        <p style="padding: var(--spacing-md); text-align: center; color: var(--gray-600);">
                            ... and <?php echo $previewData['total_books'] - 5; ?> more books
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="total_books" value="<?php echo $previewData['total_books']; ?>">
                <input type="hidden" name="tickets_per_book" value="<?php echo $previewData['tickets_per_book']; ?>">
                <input type="hidden" name="price_per_ticket" value="<?php echo $previewData['price_per_ticket']; ?>">
                <input type="hidden" name="first_ticket_number" value="<?php echo $previewData['first_ticket']; ?>">

                <div style="display: flex; gap: var(--spacing-md);">
                    <button type="submit" name="confirm" class="btn btn-success btn-lg">
                        ✓ Confirm & Generate <?php echo $previewData['total_books']; ?> Books →
                    </button>
                    <a href="/public/group-admin/lottery-books-generate.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">
                        ← Change Settings
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
