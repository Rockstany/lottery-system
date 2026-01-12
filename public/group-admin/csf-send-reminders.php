<?php
/**
 * CSF Send Reminders - WhatsApp reminders interface for unpaid members
 * Optimized for 50+ age group users
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Check authentication and feature access
requireLogin();
requireRole(['super_admin', 'group_admin']);

// Check if CSF feature is enabled for this group
$group_id = $_SESSION['group_id'] ?? null;
if (!$group_id) {
    header('Location: ../dashboard.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Verify CSF feature access
$stmt = $conn->prepare("SELECT csf_enabled, group_name FROM groups WHERE group_id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group || !$group['csf_enabled']) {
    header('Location: ../dashboard.php');
    exit();
}

$group_name = $group['group_name'];

// Get CSF settings
$stmt = $conn->prepare("SELECT monthly_contribution FROM csf_settings WHERE group_id = ?");
$stmt->execute([$group_id]);
$csf_settings = $stmt->fetch(PDO::FETCH_ASSOC);
$monthly_contribution = $csf_settings['monthly_contribution'] ?? 100;

// Get current month and year
$current_month = date('m');
$current_year = date('Y');
$month_name = date('F Y');

// Get all members in the group
$stmt = $conn->prepare("SELECT u.user_id, u.full_name, u.phone
                       FROM users u
                       INNER JOIN user_groups ug ON u.user_id = ug.user_id
                       WHERE ug.group_id = ?
                       ORDER BY u.full_name");
$stmt->execute([$group_id]);
$all_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payments for current month
$stmt = $conn->prepare("SELECT
                           cp.user_id,
                           SUM(cp.amount) as total_paid
                       FROM csf_payments cp
                       WHERE cp.group_id = ?
                       AND MONTH(cp.payment_date) = ?
                       AND YEAR(cp.payment_date) = ?
                       GROUP BY cp.user_id");
$stmt->execute([$group_id, $current_month, $current_year]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create payment lookup array
$payment_lookup = [];
foreach ($payments as $payment) {
    $payment_lookup[$payment['user_id']] = $payment['total_paid'];
}

// Identify unpaid and partial payment members
$unpaid_members = [];
$partial_members = [];

foreach ($all_members as $member) {
    $user_id = $member['user_id'];
    $phone = $member['phone'];

    // Skip members without phone numbers
    if (!$phone) {
        continue;
    }

    $paid_amount = $payment_lookup[$user_id] ?? 0;

    if ($paid_amount == 0) {
        $member['balance_due'] = $monthly_contribution;
        $unpaid_members[] = $member;
    } elseif ($paid_amount < $monthly_contribution) {
        $member['paid_amount'] = $paid_amount;
        $member['balance_due'] = $monthly_contribution - $paid_amount;
        $partial_members[] = $member;
    }
}

// Default reminder messages
$unpaid_message_template = "Dear {name},\n\nThis is a reminder that your CSF contribution of ₹{amount} for {month} is pending.\n\nPlease make the payment at your earliest convenience.\n\nThank you!\n{group_name}";

$partial_message_template = "Dear {name},\n\nYou have paid ₹{paid} for CSF this month. The remaining balance is ₹{balance}.\n\nPlease complete the payment soon.\n\nThank you!\n{group_name}";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Reminders - CSF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-size: 18px;
            line-height: 1.8;
            background-color: #f8f9fa;
        }

        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .header-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-section h1 {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .info-box {
            background: #e7f3ff;
            border: 2px solid #007bff;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
        }

        .info-box p {
            font-size: 18px;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #dc3545;
        }

        .stat-card.warning {
            border-left-color: #ffc107;
        }

        .stat-label {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }

        .members-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .members-section h2 {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .member-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid #dc3545;
        }

        .member-card.partial {
            border-left-color: #ffc107;
        }

        .member-name {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .member-phone {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .member-amount {
            font-size: 22px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 10px;
        }

        .member-amount.warning {
            color: #ffc107;
        }

        .btn-whatsapp {
            background: #25d366;
            color: white;
            border: none;
            font-size: 20px;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
        }

        .btn-whatsapp:hover {
            background: #1da851;
            color: white;
        }

        .btn-custom {
            font-size: 20px;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-send-all {
            background: #007bff;
            color: white;
            border: none;
        }

        .btn-send-all:hover {
            background: #0056b3;
        }

        .no-members {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-members i {
            font-size: 80px;
            margin-bottom: 20px;
            color: #28a745;
        }

        .no-members h3 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #28a745;
        }

        .back-link {
            font-size: 20px;
            color: #007bff;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .back-link:hover {
            color: #0056b3;
        }

        .message-preview {
            background: #fff;
            border: 2px solid #dee2e6;
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 16px;
            white-space: pre-wrap;
            color: #2c3e50;
        }

        .template-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .template-section h3 {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .form-control {
            font-size: 18px;
            padding: 12px 16px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
        }

        textarea.form-control {
            min-height: 150px;
            font-family: monospace;
        }

        .variable-tags {
            margin-top: 10px;
        }

        .variable-tag {
            display: inline-block;
            background: #e7f3ff;
            color: #007bff;
            padding: 5px 12px;
            border-radius: 5px;
            margin-right: 10px;
            margin-bottom: 10px;
            font-size: 16px;
            font-family: monospace;
        }

        .section-divider {
            margin: 40px 0;
            border-top: 3px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="csf-dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to CSF Dashboard
        </a>

        <div class="header-section">
            <h1><i class="fab fa-whatsapp"></i> Send Payment Reminders</h1>
            <p class="mb-0">Send WhatsApp reminders to members with pending payments</p>
        </div>

        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> How It Works</h3>
            <p><strong>1.</strong> Click the WhatsApp button next to each member's name</p>
            <p><strong>2.</strong> WhatsApp will open with a pre-filled message</p>
            <p><strong>3.</strong> Review the message and click Send in WhatsApp</p>
            <p><strong>Note:</strong> You can customize the message templates below</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Unpaid Members</div>
                <div class="stat-value"><?php echo count($unpaid_members); ?></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">Partial Payment</div>
                <div class="stat-value"><?php echo count($partial_members); ?></div>
            </div>
        </div>

        <?php if (empty($unpaid_members) && empty($partial_members)): ?>
            <div class="members-section">
                <div class="no-members">
                    <i class="fas fa-check-circle"></i>
                    <h3>All Caught Up!</h3>
                    <p>All members have paid their contributions for <?php echo $month_name; ?></p>
                </div>
            </div>
        <?php else: ?>
            <!-- Unpaid Members -->
            <?php if (!empty($unpaid_members)): ?>
                <div class="members-section">
                    <h2><i class="fas fa-exclamation-circle"></i> Unpaid Members (<?php echo count($unpaid_members); ?>)</h2>

                    <?php foreach ($unpaid_members as $member):
                        $message = str_replace(
                            ['{name}', '{amount}', '{month}', '{group_name}'],
                            [$member['full_name'], number_format($member['balance_due'], 2), $month_name, $group_name],
                            $unpaid_message_template
                        );
                        $whatsapp_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $member['phone']) . "?text=" . urlencode($message);
                    ?>
                        <div class="member-card">
                            <div class="member-name">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($member['full_name']); ?>
                            </div>
                            <div class="member-phone">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($member['phone']); ?>
                            </div>
                            <div class="member-amount">
                                Amount Due: ₹<?php echo number_format($member['balance_due'], 2); ?>
                            </div>
                            <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="btn btn-whatsapp">
                                <i class="fab fa-whatsapp"></i> Send WhatsApp Reminder
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Partial Payment Members -->
            <?php if (!empty($partial_members)): ?>
                <div class="members-section">
                    <h2><i class="fas fa-exclamation-triangle"></i> Partial Payment (<?php echo count($partial_members); ?>)</h2>

                    <?php foreach ($partial_members as $member):
                        $message = str_replace(
                            ['{name}', '{paid}', '{balance}', '{month}', '{group_name}'],
                            [$member['full_name'], number_format($member['paid_amount'], 2), number_format($member['balance_due'], 2), $month_name, $group_name],
                            $partial_message_template
                        );
                        $whatsapp_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $member['phone']) . "?text=" . urlencode($message);
                    ?>
                        <div class="member-card partial">
                            <div class="member-name">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($member['full_name']); ?>
                            </div>
                            <div class="member-phone">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($member['phone']); ?>
                            </div>
                            <div class="member-amount warning">
                                Paid: ₹<?php echo number_format($member['paid_amount'], 2); ?> |
                                Balance: ₹<?php echo number_format($member['balance_due'], 2); ?>
                            </div>
                            <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="btn btn-whatsapp">
                                <i class="fab fa-whatsapp"></i> Send WhatsApp Reminder
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="section-divider"></div>

            <!-- Message Templates -->
            <div class="template-section">
                <h3><i class="fas fa-edit"></i> Customize Message Templates</h3>

                <div class="mb-4">
                    <label class="form-label">Template for Unpaid Members</label>
                    <textarea class="form-control" id="unpaid-template"><?php echo htmlspecialchars($unpaid_message_template); ?></textarea>
                    <div class="variable-tags">
                        <span class="variable-tag">{name}</span>
                        <span class="variable-tag">{amount}</span>
                        <span class="variable-tag">{month}</span>
                        <span class="variable-tag">{group_name}</span>
                    </div>
                    <div class="message-preview" id="unpaid-preview">
                        <?php echo htmlspecialchars(str_replace(
                            ['{name}', '{amount}', '{month}', '{group_name}'],
                            ['John Doe', number_format($monthly_contribution, 2), $month_name, $group_name],
                            $unpaid_message_template
                        )); ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Template for Partial Payment Members</label>
                    <textarea class="form-control" id="partial-template"><?php echo htmlspecialchars($partial_message_template); ?></textarea>
                    <div class="variable-tags">
                        <span class="variable-tag">{name}</span>
                        <span class="variable-tag">{paid}</span>
                        <span class="variable-tag">{balance}</span>
                        <span class="variable-tag">{month}</span>
                        <span class="variable-tag">{group_name}</span>
                    </div>
                    <div class="message-preview" id="partial-preview">
                        <?php echo htmlspecialchars(str_replace(
                            ['{name}', '{paid}', '{balance}', '{month}', '{group_name}'],
                            ['Jane Smith', '50.00', '50.00', $month_name, $group_name],
                            $partial_message_template
                        )); ?>
                    </div>
                </div>

                <p class="text-muted" style="font-size: 16px;">
                    <i class="fas fa-info-circle"></i> Note: Template changes are for preview only and will take effect after page refresh.
                    To permanently save templates, contact your system administrator.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update preview when template changes
        document.getElementById('unpaid-template').addEventListener('input', function() {
            const template = this.value;
            const preview = template
                .replace('{name}', 'John Doe')
                .replace('{amount}', '<?php echo number_format($monthly_contribution, 2); ?>')
                .replace('{month}', '<?php echo $month_name; ?>')
                .replace('{group_name}', '<?php echo htmlspecialchars($group_name, ENT_QUOTES); ?>');
            document.getElementById('unpaid-preview').textContent = preview;
        });

        document.getElementById('partial-template').addEventListener('input', function() {
            const template = this.value;
            const preview = template
                .replace('{name}', 'Jane Smith')
                .replace('{paid}', '50.00')
                .replace('{balance}', '50.00')
                .replace('{month}', '<?php echo $month_name; ?>')
                .replace('{group_name}', '<?php echo htmlspecialchars($group_name, ENT_QUOTES); ?>');
            document.getElementById('partial-preview').textContent = preview;
        });
    </script>
</body>
</html>
