<?php
/**
 * Lottery Reports & Analytics
 * GetToKnow Community App - Part 6
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$eventId) {
    header("Location: /public/group-admin/lottery.php");
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
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Get comprehensive statistics
$statsQuery = "SELECT
    COUNT(*) as total_books,
    SUM(CASE WHEN book_status = 'available' THEN 1 ELSE 0 END) as available_books,
    SUM(CASE WHEN book_status = 'distributed' THEN 1 ELSE 0 END) as distributed_books,
    SUM(CASE WHEN book_status = 'collected' THEN 1 ELSE 0 END) as collected_books
    FROM lottery_books WHERE event_id = :event_id";
$stmt = $db->prepare($statsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$stats = $stmt->fetch();

// Get payment statistics
$paymentQuery = "SELECT
    COUNT(DISTINCT bd.distribution_id) as total_distributed,
    SUM(CASE WHEN bd.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN bd.payment_status = 'partial' THEN 1 ELSE 0 END) as partial_count,
    SUM(CASE WHEN bd.payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count,
    COALESCE(SUM(pc.amount_paid), 0) as total_collected,
    COUNT(pc.collection_id) as total_transactions
    FROM book_distribution bd
    JOIN lottery_books lb ON bd.book_id = lb.book_id
    LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
    WHERE lb.event_id = :event_id";
$stmt = $db->prepare($paymentQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$paymentStats = $stmt->fetch();

// Get member-wise report
$memberQuery = "SELECT
    bd.member_name,
    bd.mobile_number,
    lb.book_number,
    lb.start_ticket_number,
    lb.end_ticket_number,
    bd.payment_status,
    (le.tickets_per_book * le.price_per_ticket) as expected_amount,
    COALESCE(SUM(pc.amount_paid), 0) as total_paid,
    ((le.tickets_per_book * le.price_per_ticket) - COALESCE(SUM(pc.amount_paid), 0)) as outstanding,
    bd.distributed_date
    FROM book_distribution bd
    JOIN lottery_books lb ON bd.book_id = lb.book_id
    JOIN lottery_events le ON lb.event_id = le.event_id
    LEFT JOIN payment_collections pc ON bd.distribution_id = pc.distribution_id
    WHERE le.event_id = :event_id
    GROUP BY bd.distribution_id
    ORDER BY bd.member_name ASC";
$stmt = $db->prepare($memberQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$members = $stmt->fetchAll();

// Calculate percentages
$collectionPercent = $event['total_predicted_amount'] > 0
    ? ($paymentStats['total_collected'] / $event['total_predicted_amount']) * 100
    : 0;

$distributionPercent = $event['total_books'] > 0
    ? ($stats['distributed_books'] / $event['total_books']) * 100
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        .header {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }
        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            text-align: center;
        }
        .stat-value {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            margin-bottom: var(--spacing-xs);
        }
        .stat-label {
            color: var(--gray-600);
            font-size: var(--font-size-sm);
        }
        .progress-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(var(--success-color) 0%, var(--success-color) var(--progress), var(--gray-200) var(--progress), var(--gray-200) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-md);
            position: relative;
        }
        .progress-circle::after {
            content: '';
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            position: absolute;
        }
        .progress-text {
            position: relative;
            z-index: 1;
            font-size: var(--font-size-xl);
            font-weight: 700;
        }
        .export-buttons {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
        }
        .tab-container {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid var(--gray-200);
            overflow-x: auto;
        }
        .tab {
            padding: var(--spacing-md) var(--spacing-lg);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: var(--font-size-base);
            white-space: nowrap;
            border-bottom: 3px solid transparent;
            transition: all var(--transition-base);
        }
        .tab.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }
        .tab-content {
            display: none;
            padding: var(--spacing-lg);
        }
        .tab-content.active {
            display: block;
        }
        @media print {
            .header, .export-buttons, .tabs, .btn { display: none; }
            .tab-content { display: block !important; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>📊 Reports & Analytics</h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($event['event_name']); ?></p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Summary Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $event['total_books']; ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: var(--success-color);"><?php echo $stats['distributed_books']; ?></div>
                <div class="stat-label">Distributed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: var(--warning-color);"><?php echo $stats['available_books']; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($paymentStats['total_collected'], 0); ?></div>
                <div class="stat-label">Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($event['total_predicted_amount'], 0); ?></div>
                <div class="stat-label">Expected</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($event['total_predicted_amount'] - $paymentStats['total_collected'], 0); ?></div>
                <div class="stat-label">Outstanding</div>
            </div>
        </div>

        <!-- Progress Indicators -->
        <div class="row" style="margin-bottom: var(--spacing-xl);">
            <div class="col-6">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <h4>Distribution Progress</h4>
                        <div class="progress-circle" style="--progress: <?php echo $distributionPercent; ?>%;">
                            <div class="progress-text"><?php echo number_format($distributionPercent, 1); ?>%</div>
                        </div>
                        <p style="color: var(--gray-600);"><?php echo $stats['distributed_books']; ?> of <?php echo $event['total_books']; ?> books distributed</p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <h4>Collection Progress</h4>
                        <div class="progress-circle" style="--progress: <?php echo $collectionPercent; ?>%;">
                            <div class="progress-text"><?php echo number_format($collectionPercent, 1); ?>%</div>
                        </div>
                        <p style="color: var(--gray-600);">₹<?php echo number_format($paymentStats['total_collected'], 0); ?> of ₹<?php echo number_format($event['total_predicted_amount'], 0); ?> collected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <button onclick="exportToCSV()" class="btn btn-success">📥 Export to CSV</button>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
            <button onclick="copyToClipboard()" class="btn btn-info">📋 Copy Data</button>
        </div>

        <!-- Tabbed Reports -->
        <div class="tab-container">
            <div class="tabs">
                <button class="tab active" onclick="switchTab(event, 'member-report')">Member-Wise Report</button>
                <button class="tab" onclick="switchTab(event, 'payment-status')">Payment Status</button>
                <button class="tab" onclick="switchTab(event, 'book-status')">Book Status</button>
                <button class="tab" onclick="switchTab(event, 'summary')">Summary</button>
            </div>

            <!-- Tab 1: Member-Wise Report -->
            <div id="member-report" class="tab-content active">
                <h3>Member-Wise Detailed Report</h3>
                <?php if (count($members) === 0): ?>
                    <p style="text-align: center; color: var(--gray-500); padding: var(--spacing-xl);">No books distributed yet</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="memberTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Member Name</th>
                                    <th>Mobile</th>
                                    <th>Book No.</th>
                                    <th>Ticket Range</th>
                                    <th>Expected</th>
                                    <th>Paid</th>
                                    <th>Outstanding</th>
                                    <th>Status</th>
                                    <th>Distributed On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $index => $member): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo htmlspecialchars($member['member_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($member['mobile_number'] ?? '-'); ?></td>
                                        <td>#<?php echo $member['book_number']; ?></td>
                                        <td><?php echo $member['start_ticket_number']; ?> - <?php echo $member['end_ticket_number']; ?></td>
                                        <td>₹<?php echo number_format($member['expected_amount'], 0); ?></td>
                                        <td>₹<?php echo number_format($member['total_paid'], 0); ?></td>
                                        <td>₹<?php echo number_format($member['outstanding'], 0); ?></td>
                                        <td>
                                            <?php if ($member['payment_status'] === 'paid'): ?>
                                                <span class="badge badge-success">Paid</span>
                                            <?php elseif ($member['payment_status'] === 'partial'): ?>
                                                <span class="badge badge-warning">Partial</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($member['distributed_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: 700; background: var(--gray-50);">
                                    <td colspan="5" style="text-align: right;">TOTAL:</td>
                                    <td>₹<?php echo number_format(array_sum(array_column($members, 'expected_amount')), 0); ?></td>
                                    <td>₹<?php echo number_format(array_sum(array_column($members, 'total_paid')), 0); ?></td>
                                    <td>₹<?php echo number_format(array_sum(array_column($members, 'outstanding')), 0); ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab 2: Payment Status -->
            <div id="payment-status" class="tab-content">
                <h3>Payment Status Breakdown</h3>
                <div class="stats-grid" style="margin-top: var(--spacing-lg);">
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--success-color);"><?php echo $paymentStats['paid_count']; ?></div>
                        <div class="stat-label">Fully Paid</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--warning-color);"><?php echo $paymentStats['partial_count']; ?></div>
                        <div class="stat-label">Partially Paid</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--danger-color);"><?php echo $paymentStats['unpaid_count']; ?></div>
                        <div class="stat-label">Unpaid</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $paymentStats['total_transactions']; ?></div>
                        <div class="stat-label">Total Transactions</div>
                    </div>
                </div>

                <div style="margin-top: var(--spacing-xl);">
                    <h4>Collection Rate</h4>
                    <div style="background: var(--gray-50); padding: var(--spacing-lg); border-radius: var(--radius-md);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-sm);">
                            <span>Collected: ₹<?php echo number_format($paymentStats['total_collected'], 0); ?></span>
                            <span><?php echo number_format($collectionPercent, 2); ?>%</span>
                        </div>
                        <div style="height: 20px; background: var(--gray-200); border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo min($collectionPercent, 100); ?>%; height: 100%; background: var(--success-color);"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Book Status -->
            <div id="book-status" class="tab-content">
                <h3>Book Status Overview</h3>
                <div class="stats-grid" style="margin-top: var(--spacing-lg);">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_books']; ?></div>
                        <div class="stat-label">Total Books Generated</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--success-color);"><?php echo $stats['distributed_books']; ?></div>
                        <div class="stat-label">Distributed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--warning-color);"><?php echo $stats['available_books']; ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--info-color);"><?php echo $stats['collected_books']; ?></div>
                        <div class="stat-label">Collected (Paid)</div>
                    </div>
                </div>

                <div style="margin-top: var(--spacing-xl);">
                    <h4>Distribution Rate</h4>
                    <div style="background: var(--gray-50); padding: var(--spacing-lg); border-radius: var(--radius-md);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-sm);">
                            <span>Distributed: <?php echo $stats['distributed_books']; ?> of <?php echo $stats['total_books']; ?></span>
                            <span><?php echo number_format($distributionPercent, 2); ?>%</span>
                        </div>
                        <div style="height: 20px; background: var(--gray-200); border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo min($distributionPercent, 100); ?>%; height: 100%; background: var(--primary-color);"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Summary -->
            <div id="summary" class="tab-content">
                <h3>Event Summary</h3>
                <div style="background: var(--gray-50); padding: var(--spacing-lg); border-radius: var(--radius-md); margin-top: var(--spacing-lg);">
                    <div style="margin-bottom: var(--spacing-md);"><strong>Event Name:</strong> <?php echo htmlspecialchars($event['event_name']); ?></div>
                    <div style="margin-bottom: var(--spacing-md);"><strong>Description:</strong> <?php echo htmlspecialchars($event['event_description'] ?? 'N/A'); ?></div>
                    <div style="margin-bottom: var(--spacing-md);"><strong>Created:</strong> <?php echo date('M d, Y', strtotime($event['created_at'])); ?></div>
                    <div style="margin-bottom: var(--spacing-md);"><strong>Status:</strong>
                        <?php if ($event['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo ucfirst($event['status']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <h4 style="margin-top: var(--spacing-xl);">Financial Summary</h4>
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <td><strong>Total Books:</strong></td>
                            <td><?php echo $event['total_books']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Tickets:</strong></td>
                            <td><?php echo $event['total_tickets']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Price per Ticket:</strong></td>
                            <td>₹<?php echo $event['price_per_ticket']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Expected Total Amount:</strong></td>
                            <td><strong>₹<?php echo number_format($event['total_predicted_amount'], 0); ?></strong></td>
                        </tr>
                        <tr style="background: var(--success-light);">
                            <td><strong>Amount Collected:</strong></td>
                            <td><strong style="color: var(--success-color);">₹<?php echo number_format($paymentStats['total_collected'], 0); ?></strong></td>
                        </tr>
                        <tr style="background: var(--danger-light);">
                            <td><strong>Outstanding Amount:</strong></td>
                            <td><strong style="color: var(--danger-color);">₹<?php echo number_format($event['total_predicted_amount'] - $paymentStats['total_collected'], 0); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div style="margin-top: var(--spacing-xl); display: flex; gap: var(--spacing-md);">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <a href="/public/group-admin/lottery-books.php?id=<?php echo $eventId; ?>" class="btn btn-primary">Manage Books</a>
            <a href="/public/group-admin/lottery-payments.php?id=<?php echo $eventId; ?>" class="btn btn-success">Track Payments</a>
        </div>
    </div>

    <script>
        function switchTab(event, tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }

        function exportToCSV() {
            const table = document.getElementById('memberTable');
            let csv = [];

            // Headers
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            csv.push(headers.join(','));

            // Rows
            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach(td => {
                    let text = td.textContent.trim().replace(/,/g, '');
                    row.push(text);
                });
                csv.push(row.join(','));
            });

            // Download
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'lottery_report_<?php echo date('Y-m-d'); ?>.csv';
            a.click();
        }

        function copyToClipboard() {
            const table = document.getElementById('memberTable');
            const range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand('copy');
            window.getSelection().removeAllRanges();
            alert('Report copied to clipboard!');
        }
    </script>
</body>
</html>
