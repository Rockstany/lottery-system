<?php
require_once __DIR__ . '/config/config.php';

$database = new Database();
$db = $database->getConnection();

// Get your event ID (replace with actual event ID)
$eventId = 1; // CHANGE THIS TO YOUR EVENT ID

echo "=== COMMISSION DIAGNOSTICS ===\n\n";

// Check commission settings
$query = "SELECT * FROM commission_settings WHERE event_id = :event_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$settings = $stmt->fetch();

if ($settings) {
    echo "✅ Commission Settings Found:\n";
    echo "   - commission_enabled: {$settings['commission_enabled']}\n";
    echo "   - early_commission_enabled: {$settings['early_commission_enabled']}\n";
    echo "   - early_commission_percent: {$settings['early_commission_percent']}%\n";
    echo "   - early_payment_date: {$settings['early_payment_date']}\n";
    echo "   - standard_commission_enabled: {$settings['standard_commission_enabled']}\n";
    echo "   - standard_commission_percent: {$settings['standard_commission_percent']}%\n";
    echo "   - standard_payment_date: {$settings['standard_payment_date']}\n";
    echo "   - extra_books_commission_enabled: {$settings['extra_books_commission_enabled']}\n";
    echo "   - extra_books_commission_percent: {$settings['extra_books_commission_percent']}%\n\n";
} else {
    echo "❌ NO COMMISSION SETTINGS FOUND!\n";
    echo "   This is why commission is not being calculated.\n\n";
}

// Check a sample book distribution
$distQuery = "SELECT bd.distribution_id, bd.distribution_path, bd.is_extra_book, lb.book_number
              FROM book_distribution bd
              JOIN lottery_books lb ON bd.book_id = lb.book_id
              WHERE lb.event_id = :event_id
              LIMIT 5";
$distStmt = $db->prepare($distQuery);
$distStmt->bindParam(':event_id', $eventId);
$distStmt->execute();
$distributions = $distStmt->fetchAll();

echo "📋 Sample Book Distributions:\n";
foreach ($distributions as $dist) {
    echo "   Book {$dist['book_number']}: Path = '{$dist['distribution_path']}', Extra = {$dist['is_extra_book']}\n";

    // Extract Level 1
    if (!empty($dist['distribution_path'])) {
        $pathParts = explode(' > ', $dist['distribution_path']);
        $level1 = $pathParts[0] ?? '';
        echo "      Level 1 value: '$level1'\n";

        if (empty($level1)) {
            echo "      ⚠️ WARNING: Level 1 value is EMPTY - commission will NOT be calculated!\n";
        }
    } else {
        echo "      ❌ ERROR: distribution_path is EMPTY - commission will NOT be calculated!\n";
    }
}

echo "\n";

// Check payments
$paymentQuery = "SELECT pc.*, bd.distribution_path
                 FROM payment_collections pc
                 JOIN book_distribution bd ON pc.distribution_id = bd.distribution_id
                 JOIN lottery_books lb ON bd.book_id = lb.book_id
                 WHERE lb.event_id = :event_id
                 LIMIT 5";
$paymentStmt = $db->prepare($paymentQuery);
$paymentStmt->bindParam(':event_id', $eventId);
$paymentStmt->execute();
$payments = $paymentStmt->fetchAll();

echo "💰 Sample Payments:\n";
foreach ($payments as $payment) {
    echo "   Payment ID {$payment['payment_id']}: ₹{$payment['amount_paid']} on {$payment['payment_date']}\n";
    echo "      Distribution path: '{$payment['distribution_path']}'\n";
}

echo "\n";

// Check commission records
$commQuery = "SELECT * FROM commission_earned WHERE event_id = :event_id LIMIT 10";
$commStmt = $db->prepare($commQuery);
$commStmt->bindParam(':event_id', $eventId);
$commStmt->execute();
$commissions = $commStmt->fetchAll();

echo "💵 Commission Records Found: " . count($commissions) . "\n";
if (count($commissions) > 0) {
    foreach ($commissions as $comm) {
        echo "   - Type: {$comm['commission_type']}, Amount: ₹{$comm['commission_amount']}, Level 1: '{$comm['level_1_value']}'\n";
    }
} else {
    echo "   ❌ NO COMMISSION RECORDS FOUND\n";
}
