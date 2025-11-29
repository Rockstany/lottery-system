<?php
/**
 * Record Payment for Member
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$memberId = Validator::sanitizeInt($_GET['member_id'] ?? 0);
$campaignId = Validator::sanitizeInt($_GET['campaign_id'] ?? 0);

if (!$memberId || !$campaignId) {
    header("Location: /public/group-admin/transactions.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get member details
$query = "SELECT cm.*, tc.campaign_name
          FROM campaign_members cm
          JOIN transaction_campaigns tc ON cm.campaign_id = tc.campaign_id
          WHERE cm.member_id = :member_id AND cm.campaign_id = :campaign_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':member_id', $memberId);
$stmt->bindParam(':campaign_id', $campaignId);
$stmt->execute();
$member = $stmt->fetch();

if (!$member) {
    header("Location: /public/group-admin/transactions.php");
    exit;
}

$error = '';
$success = '';

$outstanding = $member['expected_amount'] - $member['total_paid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentType = $_POST['payment_type'] ?? 'partial';
    $amount = Validator::sanitizeFloat($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $confirmMethod = $_POST['confirmation_method'] ?? 'in_person';
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $notes = Validator::sanitizeString($_POST['notes'] ?? '');

    // Validate based on payment type
    if ($paymentType === 'full') {
        if ($amount != $outstanding) {
            $error = 'Full payment amount must equal outstanding amount of ₹' . number_format($outstanding);
        }
    } else {
        if ($amount <= 0 || $amount > $outstanding) {
            $error = 'Partial payment must be between ₹1 and ₹' . number_format($outstanding);
        }
    }

    if (!$error) {
        // Insert payment history
        $query = "INSERT INTO payment_history (member_id, amount_paid, payment_method, confirmation_method, payment_date, payment_notes, recorded_by)
                  VALUES (:member_id, :amount, :payment_method, :confirmation_method, :payment_date, :notes, :recorded_by)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':member_id', $memberId);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':payment_method', $paymentMethod);
        $stmt->bindParam(':confirmation_method', $confirmMethod);
        $stmt->bindParam(':payment_date', $paymentDate);
        $stmt->bindParam(':notes', $notes);
        $recordedBy = AuthMiddleware::getUserId();
        $stmt->bindParam(':recorded_by', $recordedBy);

        if ($stmt->execute()) {
            $success = 'Payment recorded successfully!';
            // Refresh member data
            $stmt = $db->prepare("SELECT * FROM campaign_members WHERE member_id = :member_id");
            $stmt->bindParam(':member_id', $memberId);
            $stmt->execute();
            $member = $stmt->fetch();
        } else {
            $error = 'Failed to record payment';
        }
    }
}

// Get payment history
$query = "SELECT * FROM payment_history WHERE member_id = :member_id ORDER BY payment_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':member_id', $memberId);
$stmt->execute();
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Payment - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .info-card {
            background: var(--gray-50);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--gray-200);
        }
        .info-row:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Record Payment</h1>
            <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($member['campaign_name']); ?></p>
        </div>
    </div>

    <div class="container main-content">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <a href="/public/group-admin/transaction-members.php?id=<?php echo $campaignId; ?>" style="margin-left: var(--spacing-md);">
                    Back to Members →
                </a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-6">
                <!-- Member Info -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Member Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-card">
                            <div class="info-row">
                                <strong>Name:</strong>
                                <span><?php echo htmlspecialchars($member['member_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <strong>Mobile:</strong>
                                <span><?php echo htmlspecialchars($member['mobile_number']); ?></span>
                            </div>
                            <div class="info-row">
                                <strong>Expected Amount:</strong>
                                <span>₹<?php echo number_format($member['expected_amount'], 2); ?></span>
                            </div>
                            <div class="info-row">
                                <strong>Already Paid:</strong>
                                <span style="color: var(--success-color);">₹<?php echo number_format($member['total_paid'], 2); ?></span>
                            </div>
                            <div class="info-row">
                                <strong>Outstanding:</strong>
                                <span style="color: var(--danger-color); font-weight: 700; font-size: var(--font-size-lg);">
                                    ₹<?php echo number_format($member['outstanding_amount'], 2); ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <strong>Status:</strong>
                                <span>
                                    <?php if ($member['payment_status'] === 'paid'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php elseif ($member['payment_status'] === 'partial'): ?>
                                        <span class="badge badge-warning">Partial</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Unpaid</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Record New Payment</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="paymentForm">
                            <div class="form-group">
                                <label class="form-label form-label-required">Payment Type</label>
                                <div style="display: flex; gap: var(--spacing-lg); margin-top: var(--spacing-sm);">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="radio" name="payment_type" value="full" <?php echo ($outstanding > 0) ? '' : 'checked'; ?> onchange="updateAmount()" style="margin-right: var(--spacing-sm);">
                                        <span>Full Payment (₹<?php echo number_format($outstanding, 2); ?>)</span>
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="radio" name="payment_type" value="partial" checked onchange="updateAmount()" style="margin-right: var(--spacing-sm);">
                                        <span>Partial Payment</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Amount Received</label>
                                <input
                                    type="number"
                                    name="amount"
                                    id="amountInput"
                                    class="form-control"
                                    step="0.01"
                                    min="0.01"
                                    max="<?php echo $outstanding; ?>"
                                    placeholder="Enter amount"
                                    value=""
                                    required
                                    autofocus
                                >
                                <span class="form-help">Outstanding: ₹<?php echo number_format($outstanding, 2); ?></span>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Payment Method</label>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-sm); margin-top: var(--spacing-sm);">
                                    <label style="display: flex; align-items: center; cursor: pointer; padding: var(--spacing-sm); border: 2px solid var(--gray-200); border-radius: var(--radius-md);">
                                        <input type="radio" name="payment_method" value="cash" style="margin-right: var(--spacing-sm);">
                                        <span>💵 Cash</span>
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer; padding: var(--spacing-sm); border: 2px solid var(--gray-200); border-radius: var(--radius-md);">
                                        <input type="radio" name="payment_method" value="upi" checked style="margin-right: var(--spacing-sm);">
                                        <span>📱 UPI</span>
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer; padding: var(--spacing-sm); border: 2px solid var(--gray-200); border-radius: var(--radius-md);">
                                        <input type="radio" name="payment_method" value="bank" style="margin-right: var(--spacing-sm);">
                                        <span>🏦 Bank Transfer</span>
                                    </label>
                                    <label style="display: flex; align-items: center; cursor: pointer; padding: var(--spacing-sm); border: 2px solid var(--gray-200); border-radius: var(--radius-md);">
                                        <input type="radio" name="payment_method" value="other" style="margin-right: var(--spacing-sm);">
                                        <span>💳 Other</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Confirmed Via</label>
                                <select name="confirmation_method" class="form-control" required>
                                    <option value="whatsapp" selected>WhatsApp</option>
                                    <option value="call">Phone Call</option>
                                    <option value="in_person">In Person</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Payment Date</label>
                                <input
                                    type="date"
                                    name="payment_date"
                                    class="form-control"
                                    value="<?php echo date('Y-m-d'); ?>"
                                    max="<?php echo date('Y-m-d'); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea
                                    name="notes"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Any additional notes..."
                                ></textarea>
                            </div>

                            <div style="display: flex; gap: var(--spacing-md);">
                                <button type="submit" class="btn btn-success btn-lg">Record Payment</button>
                                <a href="/public/group-admin/transaction-members.php?id=<?php echo $campaignId; ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <!-- Payment History -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payment History (<?php echo count($payments); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($payments) === 0): ?>
                            <p style="color: var(--gray-500); text-align: center;">No payments recorded yet</p>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <div style="padding: var(--spacing-md); border-bottom: 1px solid var(--gray-200);">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div>
                                            <div style="font-size: var(--font-size-xl); font-weight: 700; color: var(--success-color);">
                                                ₹<?php echo number_format($payment['amount_paid'], 2); ?>
                                            </div>
                                            <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                                <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div><span class="badge badge-info"><?php echo strtoupper($payment['payment_method']); ?></span></div>
                                            <div style="font-size: var(--font-size-sm); color: var(--gray-600); margin-top: 4px;">
                                                via <?php echo ucfirst($payment['confirmation_method']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($payment['payment_notes']): ?>
                                        <div style="margin-top: var(--spacing-sm); font-size: var(--font-size-sm); color: var(--gray-600);">
                                            📝 <?php echo htmlspecialchars($payment['payment_notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const outstanding = <?php echo $outstanding; ?>;
        const amountInput = document.getElementById('amountInput');

        function updateAmount() {
            const paymentType = document.querySelector('input[name="payment_type"]:checked').value;

            if (paymentType === 'full') {
                amountInput.value = outstanding.toFixed(2);
                amountInput.readOnly = true;
                amountInput.style.backgroundColor = '#f3f4f6';
            } else {
                amountInput.value = '';
                amountInput.readOnly = false;
                amountInput.style.backgroundColor = '';
                amountInput.focus();
            }
        }

        // Initialize on page load
        updateAmount();

        // Add visual feedback for selected payment method
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('label:has(input[name="payment_method"])').forEach(label => {
                    label.style.borderColor = '#e5e7eb';
                    label.style.backgroundColor = '';
                });
                this.parentElement.style.borderColor = '#3b82f6';
                this.parentElement.style.backgroundColor = '#eff6ff';
            });
        });

        // Trigger initial selection
        document.querySelector('input[name="payment_method"]:checked').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
