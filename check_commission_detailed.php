<?php
/**
 * Detailed Commission Diagnostics
 * This script provides comprehensive commission troubleshooting information
 */

require_once __DIR__ . '/config/config.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("❌ Database connection failed! Please check your database configuration.\n");
}

echo "=== DETAILED COMMISSION DIAGNOSTICS ===\n\n";

// Step 1: Find all events
echo "📊 STEP 1: Available Events\n";
echo str_repeat("-", 60) . "\n";
$eventsQuery = "SELECT event_id, event_name, created_at FROM lottery_events ORDER BY created_at DESC";
$eventsStmt = $db->prepare($eventsQuery);
$eventsStmt->execute();
$events = $eventsStmt->fetchAll();

if (count($events) === 0) {
    die("❌ No lottery events found in database!\n");
}

echo "Found " . count($events) . " event(s):\n";
foreach ($events as $event) {
    echo "   Event ID {$event['event_id']}: {$event['event_name']} (created: {$event['created_at']})\n";
}

// Ask user to select event or use the first one
$eventId = $events[0]['event_id'];
echo "\nℹ️  Using Event ID: $eventId ({$events[0]['event_name']})\n";
echo "   To check a different event, modify the \$eventId variable in this script.\n\n";

// Step 2: Check commission settings
echo "📋 STEP 2: Commission Settings Check\n";
echo str_repeat("-", 60) . "\n";
$settingsQuery = "SELECT * FROM commission_settings WHERE event_id = :event_id";
$settingsStmt = $db->prepare($settingsQuery);
$settingsStmt->bindParam(':event_id', $eventId);
$settingsStmt->execute();
$settings = $settingsStmt->fetch();

if ($settings) {
    echo "✅ Commission Settings FOUND\n\n";

    echo "Master Switch:\n";
    echo "   commission_enabled: " . ($settings['commission_enabled'] ? '✅ ENABLED' : '❌ DISABLED') . "\n\n";

    echo "Early Payment Commission:\n";
    echo "   early_commission_enabled: " . ($settings['early_commission_enabled'] ? '✅ ENABLED' : '❌ DISABLED') . "\n";
    echo "   early_payment_date: " . ($settings['early_payment_date'] ?? 'NOT SET') . "\n";
    echo "   early_commission_percent: " . ($settings['early_commission_percent'] ?? '0') . "%\n\n";

    echo "Standard Payment Commission:\n";
    echo "   standard_commission_enabled: " . ($settings['standard_commission_enabled'] ? '✅ ENABLED' : '❌ DISABLED') . "\n";
    echo "   standard_payment_date: " . ($settings['standard_payment_date'] ?? 'NOT SET') . "\n";
    echo "   standard_commission_percent: " . ($settings['standard_commission_percent'] ?? '0') . "%\n\n";

    echo "Extra Books Commission:\n";
    echo "   extra_books_commission_enabled: " . ($settings['extra_books_commission_enabled'] ? '✅ ENABLED' : '❌ DISABLED') . "\n";
    echo "   extra_books_date: " . ($settings['extra_books_date'] ?? 'NOT SET') . "\n";
    echo "   extra_books_commission_percent: " . ($settings['extra_books_commission_percent'] ?? '0') . "%\n\n";

    // Analysis
    echo "⚠️  ANALYSIS:\n";
    if (!$settings['commission_enabled']) {
        echo "   ❌ PROBLEM: Master commission switch is DISABLED!\n";
        echo "      → Go to Commission Setup and enable the commission system.\n\n";
    }
    if (!$settings['early_commission_enabled'] && !$settings['standard_commission_enabled'] && !$settings['extra_books_commission_enabled']) {
        echo "   ❌ PROBLEM: All commission types are DISABLED!\n";
        echo "      → Enable at least one commission type in Commission Setup.\n\n";
    }
    if ($settings['early_commission_enabled'] && empty($settings['early_payment_date'])) {
        echo "   ❌ PROBLEM: Early commission is enabled but deadline date is NOT SET!\n";
        echo "      → Set the early payment deadline date in Commission Setup.\n\n";
    }
} else {
    echo "❌ Commission Settings NOT FOUND!\n";
    echo "   This is the PRIMARY REASON commission is not being calculated.\n\n";
    echo "⚠️  ACTION REQUIRED:\n";
    echo "   1. Go to: Commission Setup page for this event\n";
    echo "   2. Enable 'Early Payment Commission'\n";
    echo "   3. Set deadline date (e.g., 2025-12-14)\n";
    echo "   4. Set commission percentage (e.g., 10)\n";
    echo "   5. Click 'Save Early Commission Settings'\n\n";
}

// Step 3: Check book distribution and payments
echo "📚 STEP 3: Book Distribution & Payment Analysis\n";
echo str_repeat("-", 60) . "\n";
$booksQuery = "SELECT
                lb.book_id,
                lb.book_number,
                bd.distribution_id,
                bd.distribution_path,
                bd.is_extra_book,
                le.tickets_per_book,
                le.price_per_ticket,
                le.tickets_per_book * le.price_per_ticket as expected_amount,
                COALESCE(SUM(pc.amount_paid), 0) as total_paid,
                CASE
                    WHEN COALESCE(SUM(pc.amount_paid), 0) >= (le.tickets_per_book * le.price_per_ticket)
                    THEN 'FULLY_PAID'
                    WHEN COALESCE(SUM(pc.amount_paid), 0) > 0
                    THEN 'PARTIAL'
                    ELSE 'UNPAID'
                END as payment_status,
                MAX(pc.payment_date) as last_payment_date
              FROM lottery_books lb
              JOIN book_distribution bd ON lb.book_id = bd.book_id
              JOIN lottery_events le ON lb.event_id = le.event_id
              LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
              WHERE le.event_id = :event_id
              GROUP BY lb.book_id
              ORDER BY lb.book_number
              LIMIT 20";
$booksStmt = $db->prepare($booksQuery);
$booksStmt->bindParam(':event_id', $eventId);
$booksStmt->execute();
$books = $booksStmt->fetchAll();

$fullyPaidCount = 0;
$partialPaidCount = 0;
$unpaidCount = 0;

echo "Sample Books (showing first 20):\n\n";
foreach ($books as $book) {
    $statusIcon = match($book['payment_status']) {
        'FULLY_PAID' => '✅',
        'PARTIAL' => '🟡',
        'UNPAID' => '⚪',
        default => '❓'
    };

    if ($book['payment_status'] === 'FULLY_PAID') $fullyPaidCount++;
    if ($book['payment_status'] === 'PARTIAL') $partialPaidCount++;
    if ($book['payment_status'] === 'UNPAID') $unpaidCount++;

    echo "   $statusIcon Book #{$book['book_number']}: {$book['payment_status']}\n";
    echo "      Location: {$book['distribution_path']}\n";
    echo "      Expected: ₹{$book['expected_amount']} | Paid: ₹{$book['total_paid']}\n";

    if ($book['payment_status'] === 'FULLY_PAID') {
        echo "      Last payment: {$book['last_payment_date']}\n";

        // Check if this qualifies for commission
        if ($settings) {
            $level1 = '';
            if (!empty($book['distribution_path'])) {
                $pathParts = explode(' > ', $book['distribution_path']);
                $level1 = $pathParts[0] ?? '';
            }

            if (empty($level1)) {
                echo "      ❌ WARNING: Level 1 value is EMPTY - commission CANNOT be calculated!\n";
            } else {
                echo "      Level 1: '$level1'\n";

                // Check commission eligibility
                $eligible = [];

                if ($book['is_extra_book'] == 1 && $settings['extra_books_commission_enabled'] == 1) {
                    $eligible[] = "Extra Books ({$settings['extra_books_commission_percent']}%)";
                }

                if ($settings['early_commission_enabled'] == 1 &&
                    !empty($settings['early_payment_date']) &&
                    $book['last_payment_date'] <= $settings['early_payment_date']) {
                    $eligible[] = "Early Payment ({$settings['early_commission_percent']}%)";
                } elseif ($settings['standard_commission_enabled'] == 1 &&
                          !empty($settings['standard_payment_date']) &&
                          $book['last_payment_date'] <= $settings['standard_payment_date']) {
                    $eligible[] = "Standard Payment ({$settings['standard_commission_percent']}%)";
                }

                if (count($eligible) > 0) {
                    echo "      ✅ ELIGIBLE for: " . implode(', ', $eligible) . "\n";
                } else {
                    echo "      ❌ NOT ELIGIBLE for any commission (payment date too late or commission disabled)\n";
                }
            }
        }
    }

    echo "\n";
}

echo "Summary:\n";
echo "   ✅ Fully Paid: $fullyPaidCount books\n";
echo "   🟡 Partially Paid: $partialPaidCount books\n";
echo "   ⚪ Unpaid: $unpaidCount books\n\n";

if ($fullyPaidCount === 0) {
    echo "⚠️  WARNING: No fully paid books found!\n";
    echo "   Commission is ONLY calculated when a book is FULLY PAID.\n";
    echo "   Partial payments do NOT trigger commission calculation.\n\n";
}

// Step 4: Check commission records
echo "💰 STEP 4: Commission Records\n";
echo str_repeat("-", 60) . "\n";
$commQuery = "SELECT
                ce.commission_id,
                ce.level_1_value,
                ce.commission_type,
                ce.commission_percent,
                ce.payment_amount,
                ce.commission_amount,
                ce.payment_date,
                lb.book_number
              FROM commission_earned ce
              LEFT JOIN lottery_books lb ON ce.book_id = lb.book_id
              WHERE ce.event_id = :event_id
              ORDER BY ce.created_at DESC
              LIMIT 10";
$commStmt = $db->prepare($commQuery);
$commStmt->bindParam(':event_id', $eventId);
$commStmt->execute();
$commissions = $commStmt->fetchAll();

$totalCommQuery = "SELECT COUNT(*) as total FROM commission_earned WHERE event_id = :event_id";
$totalCommStmt = $db->prepare($totalCommQuery);
$totalCommStmt->bindParam(':event_id', $eventId);
$totalCommStmt->execute();
$totalComm = $totalCommStmt->fetch();

echo "Total Commission Records: {$totalComm['total']}\n\n";

if (count($commissions) > 0) {
    echo "Sample Commission Records (latest 10):\n\n";
    foreach ($commissions as $comm) {
        echo "   Commission ID {$comm['commission_id']}:\n";
        echo "      Level 1: {$comm['level_1_value']}\n";
        echo "      Type: {$comm['commission_type']} ({$comm['commission_percent']}%)\n";
        echo "      Payment: ₹{$comm['payment_amount']} → Commission: ₹{$comm['commission_amount']}\n";
        echo "      Date: {$comm['payment_date']}\n";
        echo "      Book: #{$comm['book_number']}\n\n";
    }
} else {
    echo "❌ NO COMMISSION RECORDS FOUND!\n\n";

    if ($fullyPaidCount > 0) {
        echo "⚠️  PROBLEM IDENTIFIED:\n";
        echo "   You have $fullyPaidCount fully paid books, but NO commission records.\n";
        echo "   This indicates commission calculation is NOT working.\n\n";

        if (!$settings) {
            echo "   ROOT CAUSE: Commission settings are NOT configured.\n";
            echo "   → Go to Commission Setup and configure commission settings.\n\n";
        } elseif (!$settings['commission_enabled']) {
            echo "   ROOT CAUSE: Master commission switch is DISABLED.\n";
            echo "   → Enable the commission system in Commission Setup.\n\n";
        } else {
            echo "   POSSIBLE CAUSES:\n";
            echo "   1. Payments were made BEFORE commission settings were configured\n";
            echo "      → Use 'Recalculate Commissions' tool to regenerate commission records\n";
            echo "   2. Payment dates are AFTER the commission deadline dates\n";
            echo "      → Check date eligibility in the analysis above\n";
            echo "   3. Distribution paths have empty Level 1 values\n";
            echo "      → Check the Level 1 values in the analysis above\n\n";
        }
    } else {
        echo "ℹ️  This is expected since there are no fully paid books yet.\n";
        echo "   Commission will be calculated automatically when books are fully paid.\n\n";
    }
}

// Step 5: Summary and recommendations
echo "📝 STEP 5: Summary & Recommendations\n";
echo str_repeat("-", 60) . "\n";

$issues = [];
$recommendations = [];

if (!$settings) {
    $issues[] = "Commission settings are NOT configured in database";
    $recommendations[] = "Configure commission settings via Commission Setup page";
}

if ($settings && !$settings['commission_enabled']) {
    $issues[] = "Master commission switch is DISABLED";
    $recommendations[] = "Enable the commission system in settings";
}

if ($settings && !$settings['early_commission_enabled'] && !$settings['standard_commission_enabled'] && !$settings['extra_books_commission_enabled']) {
    $issues[] = "All commission types are DISABLED";
    $recommendations[] = "Enable at least one commission type (Early/Standard/Extra Books)";
}

if ($fullyPaidCount > 0 && $totalComm['total'] == 0 && $settings && $settings['commission_enabled']) {
    $issues[] = "Fully paid books exist but NO commission records found";
    $recommendations[] = "Run the 'Recalculate Commissions' tool to regenerate commission records";
}

if (count($issues) === 0) {
    echo "✅ No critical issues detected!\n\n";

    if ($totalComm['total'] > 0) {
        echo "Your commission system appears to be working correctly.\n";
    } else {
        echo "Commission system is configured. Waiting for fully paid books to calculate commission.\n";
    }
} else {
    echo "❌ Issues Found:\n";
    foreach ($issues as $i => $issue) {
        echo "   " . ($i + 1) . ". $issue\n";
    }
    echo "\n";

    echo "✅ Recommended Actions:\n";
    foreach ($recommendations as $i => $rec) {
        echo "   " . ($i + 1) . ". $rec\n";
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "Diagnostics Complete!\n";
echo str_repeat("=", 60) . "\n";
