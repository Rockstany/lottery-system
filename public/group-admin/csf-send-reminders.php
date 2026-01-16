<?php
/**
 * CSF Send Reminders - WhatsApp reminders interface for unpaid members
 * Optimized for 50+ age group users
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

// Authentication
AuthMiddleware::requireRole('group_admin');
$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

// Feature access check
$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'csf_funds')) {
    $_SESSION['error_message'] = "CSF Funds is not enabled for your community";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get community name
$stmt = $db->prepare("SELECT community_name FROM communities WHERE community_id = ?");
$stmt->execute([$communityId]);
$community = $stmt->fetch(PDO::FETCH_ASSOC);
$community_name = $community['community_name'] ?? 'Community';

// Default monthly contribution (can be customized later)
$monthly_contribution = 100;

// Get current month and year
$current_month = date('m');
$current_year = date('Y');
$month_name = date('F Y');

// Get all members in the community
$stmt = $db->prepare("SELECT scm.user_id, u.full_name, u.mobile_number as phone
                       FROM sub_community_members scm
                       JOIN users u ON scm.user_id = u.user_id
                       JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                       WHERE sc.community_id = ? AND scm.status = 'active'
                       ORDER BY u.full_name");
$stmt->execute([$communityId]);
$all_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ALL payments for current year (to track which months each member paid)
$stmt = $db->prepare("SELECT
                           cp.user_id,
                           cp.payment_for_months
                       FROM csf_payments cp
                       WHERE cp.community_id = ?
                       AND YEAR(cp.payment_date) = ?");
$stmt->execute([$communityId, $current_year]);
$all_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build lookup: which months has each user paid for?
$months_paid_lookup = [];
foreach ($all_payments as $payment) {
    $user_id = $payment['user_id'];
    $months_json = json_decode($payment['payment_for_months'], true);

    if (!empty($months_json) && is_array($months_json)) {
        $payment_month = $months_json[0];

        if (!isset($months_paid_lookup[$user_id])) {
            $months_paid_lookup[$user_id] = [];
        }

        $months_paid_lookup[$user_id][] = $payment_month;
    }
}

// Generate list of months to check (last 3 months including current)
$months_to_check = [];
for ($i = 0; $i < 3; $i++) {
    $date = date('Y-m', strtotime("-$i months"));
    $label = date('F Y', strtotime("-$i months"));
    $months_to_check[] = ['value' => $date, 'label' => $label];
}

// Identify members with unpaid months
$members_with_unpaid_months = [];

foreach ($all_members as $member) {
    $user_id = $member['user_id'];
    $phone = $member['phone'];

    // Skip members without phone numbers
    if (!$phone) {
        continue;
    }

    $paid_months = $months_paid_lookup[$user_id] ?? [];
    $unpaid_months = [];

    // Check which months are unpaid
    foreach ($months_to_check as $month_info) {
        if (!in_array($month_info['value'], $paid_months)) {
            $unpaid_months[] = $month_info;
        }
    }

    // If member has any unpaid months, add to list
    if (!empty($unpaid_months)) {
        $member['unpaid_months'] = $unpaid_months;
        $members_with_unpaid_months[] = $member;
    }
}

// Default reminder message (with unpaid months placeholder)
$unpaid_message_template = "Dear {name},\n\nThis is a reminder that your CSF contribution for the following months is pending:\n{unpaid_months}\n\nPlease make the payment at your earliest convenience.\n\nThank you!\n{community_name}";

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
        <a href="csf-funds.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to CSF Funds
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
                <div class="stat-label">Members with Unpaid Months</div>
                <div class="stat-value"><?php echo count($members_with_unpaid_months); ?></div>
            </div>
        </div>

        <?php if (empty($members_with_unpaid_months)): ?>
            <div class="members-section">
                <div class="no-members">
                    <i class="fas fa-check-circle"></i>
                    <h3>All Caught Up!</h3>
                    <p>All members have paid their contributions for <?php echo $month_name; ?></p>
                </div>
            </div>
        <?php else: ?>
            <!-- Unpaid Members -->
            <?php if (!empty($members_with_unpaid_months)): ?>
                <div class="members-section">
                    <h2><i class="fas fa-exclamation-circle"></i> Members with Unpaid Months (<?php echo count($members_with_unpaid_months); ?>)</h2>

                    <?php foreach ($members_with_unpaid_months as $member):
                        $unpaid_month_labels = array_column($member['unpaid_months'], 'label');
                        $unpaid_months_text = implode(', ', $unpaid_month_labels);

                        $message = str_replace(
                            ['{name}', '{unpaid_months}', '{community_name}'],
                            [$member['full_name'], $unpaid_months_text, $community_name],
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
                            <div class="member-amount" style="background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;">
                                <strong style="color: #856404;">Unpaid Months:</strong><br>
                                <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;">
                                    <?php foreach ($member['unpaid_months'] as $month_info): ?>
                                        <span class="badge bg-warning text-dark" style="font-size: 16px;">
                                            <?php echo $month_info['label']; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="btn btn-whatsapp">
                                <i class="fab fa-whatsapp"></i> Send WhatsApp Reminder
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Partial Payment section removed - CSF only supports PAID or UNPAID -->

            <div class="section-divider"></div>

            <!-- Message Templates -->
            <div class="template-section">
                <h3><i class="fas fa-edit"></i> Customize Message Template</h3>

                <div class="mb-4">
                    <label class="form-label">Template for Unpaid Members</label>
                    <textarea class="form-control" id="unpaid-template"><?php echo htmlspecialchars($unpaid_message_template); ?></textarea>
                    <div class="variable-tags">
                        <span class="variable-tag">{name}</span>
                        <span class="variable-tag">{unpaid_months}</span>
                        <span class="variable-tag">{community_name}</span>
                    </div>
                    <div class="message-preview" id="unpaid-preview">
                        <?php echo htmlspecialchars(str_replace(
                            ['{name}', '{unpaid_months}', '{community_name}'],
                            ['John Doe', 'January 2026, December 2025', $community_name],
                            $unpaid_message_template
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
                .replace('{unpaid_months}', 'January 2026, December 2025')
                .replace('{community_name}', '<?php echo htmlspecialchars($community_name, ENT_QUOTES); ?>');
            document.getElementById('unpaid-preview').textContent = preview;
        });
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
