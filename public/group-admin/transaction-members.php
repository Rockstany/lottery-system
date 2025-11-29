<?php
/**
 * View Campaign Members & Send WhatsApp Reminders
 * GetToKnow Community App - Step 3
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$communityId = AuthMiddleware::getCommunityId();
$campaignId = Validator::sanitizeInt($_GET['id'] ?? 0);

if (!$communityId || !$campaignId) {
    header("Location: /public/group-admin/transactions.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get campaign
$query = "SELECT * FROM transaction_campaigns WHERE campaign_id = :id AND community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $campaignId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$campaign = $stmt->fetch();

if (!$campaign) {
    header("Location: /public/group-admin/transactions.php");
    exit;
}

// Get all members
$query = "SELECT * FROM campaign_members WHERE campaign_id = :campaign_id ORDER BY member_name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':campaign_id', $campaignId);
$stmt->execute();
$members = $stmt->fetchAll();

// Get stats
$paidCount = 0;
$partialCount = 0;
$unpaidCount = 0;
$totalCollected = 0;

foreach ($members as $member) {
    if ($member['payment_status'] === 'paid') $paidCount++;
    elseif ($member['payment_status'] === 'partial') $partialCount++;
    else $unpaidCount++;
    $totalCollected += $member['total_paid'];
}

$messageTemplate = "Hello {name},\n\nThis is a reminder for {campaign}.\n\nExpected Amount: ₹{amount}\n\nPlease make the payment at your earliest convenience.\n\nThank you!";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Members - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        .stat-box {
            background: white;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .stat-value {
            font-size: var(--font-size-2xl);
            font-weight: 700;
        }
        .stat-label {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }
        .instructions {
            background: var(--info-light);
            border-left: 4px solid var(--info-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .whatsapp-btn {
            background: #25D366;
            color: white;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: var(--font-size-sm);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .whatsapp-btn:hover {
            background: #20BA5A;
            color: white;
        }
        .filter-buttons {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid var(--gray-300);
            background: white;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: var(--font-size-base);
        }
        .filter-btn.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo htmlspecialchars($campaign['campaign_name']); ?></h1>
            <p style="margin: 0; opacity: 0.9;">Step 3 of 4: Send Reminders & Track Payments</p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-value"><?php echo count($members); ?></div>
                <div class="stat-label">Total Members</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--success-color);"><?php echo $paidCount; ?></div>
                <div class="stat-label">Paid</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--warning-color);"><?php echo $partialCount; ?></div>
                <div class="stat-label">Partial</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--danger-color);"><?php echo $unpaidCount; ?></div>
                <div class="stat-label">Unpaid</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">₹<?php echo number_format($totalCollected, 2); ?></div>
                <div class="stat-label">Collected</div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <h3 style="margin-top: 0;">💬 Step 3: Send WhatsApp Reminders</h3>
            <p>Click the WhatsApp button next to any member to send them a payment reminder. The message is pre-filled and ready to send!</p>
            <p style="margin: 0;"><strong>Tip:</strong> Filter by "Unpaid" to see only members who haven't paid yet.</p>
        </div>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spacing-md);">
                <h3 class="card-title" style="margin: 0;">Members (<?php echo count($members); ?>)</h3>
                <div style="display: flex; gap: var(--spacing-sm);">
                    <a href="/public/group-admin/transaction-upload.php?id=<?php echo $campaignId; ?>" class="btn btn-success">
                        + Add More Members
                    </a>
                    <a href="/public/group-admin/transaction-payments.php?id=<?php echo $campaignId; ?>" class="btn btn-primary">
                        Track Payments →
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="filter-buttons">
                    <button class="filter-btn active" onclick="filterMembers('all')">All (<?php echo count($members); ?>)</button>
                    <button class="filter-btn" onclick="filterMembers('paid')">Paid (<?php echo $paidCount; ?>)</button>
                    <button class="filter-btn" onclick="filterMembers('partial')">Partial (<?php echo $partialCount; ?>)</button>
                    <button class="filter-btn" onclick="filterMembers('unpaid')">Unpaid (<?php echo $unpaidCount; ?>)</button>
                </div>

                <?php if (count($members) === 0): ?>
                    <div style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                        <p>No members uploaded yet.</p>
                        <a href="/public/group-admin/transaction-upload.php?id=<?php echo $campaignId; ?>" class="btn btn-primary">
                            Upload Members via CSV
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Member Name</th>
                                    <th>Mobile</th>
                                    <th>Expected</th>
                                    <th>Paid</th>
                                    <th>Outstanding</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="membersTable">
                                <?php foreach ($members as $index => $member): ?>
                                    <tr class="member-row" data-status="<?php echo $member['payment_status']; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo htmlspecialchars($member['member_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($member['mobile_number']); ?></td>
                                        <td>₹<?php echo number_format($member['expected_amount'], 2); ?></td>
                                        <td>₹<?php echo number_format($member['total_paid'], 2); ?></td>
                                        <td>₹<?php echo number_format($member['outstanding_amount'], 2); ?></td>
                                        <td>
                                            <?php if ($member['payment_status'] === 'paid'): ?>
                                                <span class="badge badge-success">Paid</span>
                                            <?php elseif ($member['payment_status'] === 'partial'): ?>
                                                <span class="badge badge-warning">Partial</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $message = str_replace(
                                                ['{name}', '{campaign}', '{amount}'],
                                                [$member['member_name'], $campaign['campaign_name'], number_format($member['expected_amount'], 2)],
                                                $messageTemplate
                                            );
                                            $whatsappLink = "https://wa.me/91" . $member['mobile_number'] . "?text=" . urlencode($message);
                                            ?>
                                            <div style="display: flex; gap: 6px;">
                                                <a href="<?php echo $whatsappLink; ?>" target="_blank" class="whatsapp-btn">
                                                    📱 WhatsApp
                                                </a>
                                                <a href="/public/group-admin/transaction-payment-record.php?member_id=<?php echo $member['member_id']; ?>&campaign_id=<?php echo $campaignId; ?>"
                                                   class="btn btn-sm btn-primary">
                                                    Record Payment
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-md);">
            <a href="/public/group-admin/transactions.php" class="btn btn-secondary">← Back to Campaigns</a>
            <a href="/public/group-admin/transaction-reports.php?id=<?php echo $campaignId; ?>" class="btn btn-info">View Reports</a>
        </div>
    </div>

    <script>
        function filterMembers(status) {
            const rows = document.querySelectorAll('.member-row');
            const buttons = document.querySelectorAll('.filter-btn');

            // Update button states
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Filter rows
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
