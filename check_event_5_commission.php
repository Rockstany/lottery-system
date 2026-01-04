<?php
/**
 * Check Commission for Event ID 5 (Christmas 25)
 */

require_once __DIR__ . '/config/config.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("❌ Database connection failed!\n");
}

$eventId = 5; // Christmas 25 event

echo "=== COMMISSION CHECK FOR EVENT ID 5 (Christmas 25) ===\n\n";

// Step 1: Verify event exists
$eventQuery = "SELECT * FROM lottery_events WHERE event_id = :event_id";
$eventStmt = $db->prepare($eventQuery);
$eventStmt->bindParam(':event_id', $eventId);
$eventStmt->execute();
$event = $eventStmt->fetch();

if (!$event) {
    die("❌ Event ID 5 not found!\n");
}

echo "✅ Event Found: {$event['event_name']}\n";
echo "   Created: {$event['created_at']}\n";
echo "   Tickets per book: {$event['tickets_per_book']}\n";
echo "   Price per ticket: ₹{$event['price_per_ticket']}\n";
echo "   Expected per book: ₹" . ($event['tickets_per_book'] * $event['price_per_ticket']) . "\n\n";

// Step 2: Check commission settings for Event 5
echo "📋 COMMISSION SETTINGS FOR EVENT 5:\n";
echo str_repeat("-", 70) . "\n";

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
    echo "   early_payment_date: " . ($settings['early_payment_date'] ?? '❌ NOT SET') . "\n";
    echo "   early_commission_percent: " . ($settings['early_commission_percent'] ?? '0') . "%\n\n";

    echo "Standard Payment Commission:\n";
    echo "   standard_commission_enabled: " . ($settings['standard_commission_enabled'] ? '✅ ENABLED' : '❌ DISABLED') . "\n";
    echo "   standard_payment_date: " . ($settings['standard_payment_date'] ?? '❌ NOT SET') . "\n";
    echo "   standard_commission_percent: " . ($settings['standard_commission_percent'] ?? '0') . "%\n\n";

    echo "Extra Books Commission:\n";
    echo "   extra_books_commission_enabled: " . ($settings['extra_books_commission_enabled'] ? '✅ ENABLED' : '❌ DISABLED') . "\n";
    echo "   extra_books_date: " . ($settings['extra_books_date'] ?? '❌ NOT SET') . "\n";
    echo "   extra_books_commission_percent: " . ($settings['extra_books_commission_percent'] ?? '0') . "%\n\n";

    // CRITICAL CHECKS
    echo "🔍 CRITICAL ANALYSIS:\n";
    $issues = [];

    if (!$settings['commission_enabled']) {
        $issues[] = "❌ MASTER SWITCH IS DISABLED - No commission will be calculated!";
    }

    if (!$settings['early_commission_enabled'] && !$settings['standard_commission_enabled'] && !$settings['extra_books_commission_enabled']) {
        $issues[] = "❌ ALL COMMISSION TYPES ARE DISABLED!";
    }

    if ($settings['early_commission_enabled'] && empty($settings['early_payment_date'])) {
        $issues[] = "❌ Early commission is ENABLED but DEADLINE DATE is NOT SET!";
    }

    if ($settings['standard_commission_enabled'] && empty($settings['standard_payment_date'])) {
        $issues[] = "❌ Standard commission is ENABLED but DEADLINE DATE is NOT SET!";
    }

    if (count($issues) > 0) {
        foreach ($issues as $issue) {
            echo "   $issue\n";
        }
        echo "\n";
    } else {
        echo "   ✅ All settings look correct!\n\n";
    }

} else {
    echo "❌ NO COMMISSION SETTINGS FOUND FOR EVENT 5!\n";
    echo "   This is why commission is not being calculated.\n\n";

    echo "🔧 FIX REQUIRED:\n";
    echo "   Run this SQL to create settings:\n\n";
    echo "   INSERT INTO commission_settings (\n";
    echo "       event_id, commission_enabled, early_commission_enabled,\n";
    echo "       early_payment_date, early_commission_percent\n";
    echo "   ) VALUES (\n";
    echo "       5, 1, 1, '2025-12-14', 10.00\n";
    echo "   );\n\n";
}

// Step 3: Check payments for Event 5
echo "💰 PAYMENT STATUS FOR EVENT 5:\n";
echo str_repeat("-", 70) . "\n";

$paymentsQuery = "SELECT
                    lb.book_number,
                    bd.distribution_path,
                    bd.is_extra_book,
                    le.tickets_per_book * le.price_per_ticket as expected,
                    COALESCE(SUM(pc.amount_paid), 0) as total_paid,
                    CASE
                        WHEN COALESCE(SUM(pc.amount_paid), 0) >= (le.tickets_per_book * le.price_per_ticket)
                        THEN 'FULLY_PAID'
                        WHEN COALESCE(SUM(pc.amount_paid), 0) > 0
                        THEN 'PARTIAL'
                        ELSE 'UNPAID'
                    END as status,
                    MAX(pc.payment_date) as last_payment_date
                  FROM lottery_books lb
                  JOIN book_distribution bd ON lb.book_id = bd.book_id
                  JOIN lottery_events le ON lb.event_id = le.event_id
                  LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
                  WHERE le.event_id = :event_id
                  GROUP BY lb.book_id
                  ORDER BY lb.book_number";
$paymentsStmt = $db->prepare($paymentsQuery);
$paymentsStmt->bindParam(':event_id', $eventId);
$paymentsStmt->execute();
$payments = $paymentsStmt->fetchAll();

$fullyPaidCount = 0;
$partialCount = 0;
$unpaidCount = 0;
$fullyPaidBooks = [];

foreach ($payments as $payment) {
    $icon = match($payment['status']) {
        'FULLY_PAID' => '✅',
        'PARTIAL' => '🟡',
        'UNPAID' => '⚪',
        default => '❓'
    };

    if ($payment['status'] === 'FULLY_PAID') {
        $fullyPaidCount++;
        $fullyPaidBooks[] = $payment;
    } elseif ($payment['status'] === 'PARTIAL') {
        $partialCount++;
    } else {
        $unpaidCount++;
    }

    echo "$icon Book #{$payment['book_number']}: {$payment['status']}\n";
    echo "   Location: {$payment['distribution_path']}\n";
    echo "   Expected: ₹{$payment['expected']} | Paid: ₹{$payment['total_paid']}\n";

    if ($payment['status'] === 'FULLY_PAID') {
        echo "   Payment Date: {$payment['last_payment_date']}\n";

        // Extract Level 1
        $level1 = '';
        if (!empty($payment['distribution_path'])) {
            $parts = explode(' > ', $payment['distribution_path']);
            $level1 = $parts[0] ?? '';
        }

        if (empty($level1)) {
            echo "   ❌ WARNING: Level 1 is EMPTY - commission cannot be calculated!\n";
        } else {
            echo "   Level 1: '$level1'\n";

            // Check eligibility if settings exist
            if ($settings) {
                $eligible = [];

                if ($payment['is_extra_book'] == 1 && $settings['extra_books_commission_enabled'] == 1) {
                    $eligible[] = "Extra Books ({$settings['extra_books_commission_percent']}%)";
                }

                if ($settings['early_commission_enabled'] == 1 &&
                    !empty($settings['early_payment_date']) &&
                    $payment['last_payment_date'] <= $settings['early_payment_date']) {
                    $eligible[] = "Early Payment ({$settings['early_commission_percent']}%)";
                } elseif ($settings['standard_commission_enabled'] == 1 &&
                          !empty($settings['standard_payment_date']) &&
                          $payment['last_payment_date'] <= $settings['standard_payment_date']) {
                    $eligible[] = "Standard Payment ({$settings['standard_commission_percent']}%)";
                }

                if (count($eligible) > 0) {
                    echo "   ✅ ELIGIBLE: " . implode(', ', $eligible) . "\n";
                } else {
                    echo "   ❌ NOT ELIGIBLE (payment date: {$payment['last_payment_date']}";
                    if (!empty($settings['early_payment_date'])) {
                        echo ", early deadline: {$settings['early_payment_date']}";
                    }
                    echo ")\n";
                }
            }
        }
    }
    echo "\n";
}

echo "SUMMARY:\n";
echo "   ✅ Fully Paid: $fullyPaidCount books\n";
echo "   🟡 Partial: $partialCount books\n";
echo "   ⚪ Unpaid: $unpaidCount books\n\n";

// Step 4: Check commission records
echo "💵 COMMISSION RECORDS FOR EVENT 5:\n";
echo str_repeat("-", 70) . "\n";

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
              ORDER BY ce.created_at DESC";
$commStmt = $db->prepare($commQuery);
$commStmt->bindParam(':event_id', $eventId);
$commStmt->execute();
$commissions = $commStmt->fetchAll();

if (count($commissions) > 0) {
    echo "✅ Found " . count($commissions) . " commission record(s):\n\n";

    $totalComm = 0;
    foreach ($commissions as $comm) {
        echo "   Commission ID {$comm['commission_id']}:\n";
        echo "      Book: #{$comm['book_number']}\n";
        echo "      Level 1: {$comm['level_1_value']}\n";
        echo "      Type: {$comm['commission_type']} ({$comm['commission_percent']}%)\n";
        echo "      Payment: ₹{$comm['payment_amount']} → Commission: ₹{$comm['commission_amount']}\n";
        echo "      Date: {$comm['payment_date']}\n\n";
        $totalComm += $comm['commission_amount'];
    }

    echo "   TOTAL COMMISSION: ₹" . number_format($totalComm, 2) . "\n\n";
} else {
    echo "❌ NO COMMISSION RECORDS FOUND!\n\n";

    if ($fullyPaidCount > 0) {
        echo "⚠️  PROBLEM: You have $fullyPaidCount fully paid books but NO commission records!\n\n";

        if (!$settings) {
            echo "ROOT CAUSE: Commission settings do NOT exist for Event 5.\n\n";
            echo "🔧 SOLUTION:\n";
            echo "1. Create commission settings (SQL above), OR\n";
            echo "2. Go to Commission Setup page for Event 5 and configure settings\n\n";
        } elseif (!$settings['commission_enabled']) {
            echo "ROOT CAUSE: Commission is DISABLED (commission_enabled = 0).\n\n";
            echo "🔧 SOLUTION:\n";
            echo "   UPDATE commission_settings SET commission_enabled = 1 WHERE event_id = 5;\n\n";
        } else {
            echo "ROOT CAUSE: Payments were collected BEFORE commission was configured.\n\n";
            echo "🔧 SOLUTION:\n";
            echo "1. Go to: Commission Setup page for Event 5\n";
            echo "2. Click: '🔄 Recalculate Commissions' button\n";
            echo "3. This will regenerate all commission records from existing payments\n\n";
        }
    } else {
        echo "ℹ️  This is expected - no fully paid books yet.\n\n";
    }
}

// Final Summary
echo str_repeat("=", 70) . "\n";
echo "FINAL VERDICT FOR EVENT 5 (Christmas 25):\n";
echo str_repeat("=", 70) . "\n\n";

if (!$settings) {
    echo "❌ CRITICAL: No commission settings found\n";
    echo "   → Create settings via Commission Setup page or SQL INSERT\n\n";
} elseif (!$settings['commission_enabled']) {
    echo "❌ CRITICAL: Commission system is disabled\n";
    echo "   → Enable via Commission Setup page or SQL UPDATE\n\n";
} elseif ($fullyPaidCount > 0 && count($commissions) === 0) {
    echo "❌ CRITICAL: $fullyPaidCount fully paid books but NO commission records\n";
    echo "   → Run the 'Recalculate Commissions' tool\n\n";
} elseif ($fullyPaidCount > 0 && count($commissions) > 0) {
    echo "✅ Commission system is working!\n";
    echo "   → $fullyPaidCount books paid, " . count($commissions) . " commission records created\n\n";
} else {
    echo "ℹ️  No issues detected (no fully paid books yet)\n";
    echo "   → Commission will be calculated when books are fully paid\n\n";
}

echo "Diagnostic complete!\n";
