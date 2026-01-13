<?php
/**
 * CSF Record Payment - 5-Step Progressive Payment Recording Form
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

// Handle form submission
$success_message = '';
$error_message = '';
$current_step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'record_payment') {
        try {
            $user_id = $_POST['user_id'];
            $amount = $_POST['amount'];
            $payment_date = $_POST['payment_date'];
            $payment_method = $_POST['payment_method'];
            $transaction_id = $_POST['transaction_id'] ?? null;
            $notes = $_POST['notes'] ?? null;

            // Validate user belongs to community and get sub_community_id
            $stmt = $db->prepare("SELECT scm.user_id, scm.sub_community_id, u.full_name
                                   FROM sub_community_members scm
                                   JOIN users u ON scm.user_id = u.user_id
                                   JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                                   WHERE scm.user_id = ? AND sc.community_id = ? AND scm.status = 'active'");
            $stmt->execute([$user_id, $communityId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Invalid member selected");
            }

            $sub_community_id = $user['sub_community_id'];

            // Calculate payment_for_months (JSON array format with single month)
            // The constraint likely expects: JSON_VALID(payment_for_months) AND JSON_LENGTH(payment_for_months) > 0
            $payment_month = date('Y-m', strtotime($payment_date));
            $payment_for_months = json_encode([$payment_month], JSON_UNESCAPED_SLASHES);

            // Insert payment record with all required fields
            $stmt = $db->prepare("INSERT INTO csf_payments
                                   (community_id, sub_community_id, user_id, amount, payment_date, payment_method, transaction_id, notes, collected_by, payment_for_months, created_at)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $communityId,
                $sub_community_id,
                $user_id,
                $amount,
                $payment_date,
                $payment_method,
                $transaction_id,
                $notes,
                $userId,
                $payment_for_months
            ]);

            $success_message = "Payment of ₹" . number_format($amount, 2) . " recorded successfully for " . htmlspecialchars($user['full_name']);

            // Reset form
            $_POST = [];
            $current_step = 1;

        } catch (Exception $e) {
            $error_message = "Error recording payment: " . $e->getMessage();
        }
    }
}

// Get all members in the community
$stmt = $db->prepare("SELECT scm.user_id, u.full_name, u.mobile_number as phone
                       FROM sub_community_members scm
                       JOIN users u ON scm.user_id = u.user_id
                       JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                       WHERE sc.community_id = ? AND scm.status = 'active'
                       ORDER BY u.full_name");
$stmt->execute([$communityId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Default amount (can be customized later)
$default_amount = 100;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Payment - CSF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-size: 18px;
            line-height: 1.8;
            background-color: #f8f9fa;
        }

        .main-container {
            max-width: 900px;
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

        .progress-bar-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
        }

        .step-indicator::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e9ecef;
            z-index: 0;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin: 0 auto 10px;
            border: 4px solid white;
        }

        .step.active .step-circle {
            background: #007bff;
            color: white;
        }

        .step.completed .step-circle {
            background: #28a745;
            color: white;
        }

        .step-label {
            font-size: 16px;
            color: #6c757d;
            font-weight: 500;
        }

        .step.active .step-label {
            color: #007bff;
            font-weight: bold;
        }

        .form-section {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 400px;
        }

        .form-label {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .form-control, .form-select {
            font-size: 20px;
            padding: 15px 20px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            min-height: 60px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .btn-custom {
            font-size: 22px;
            padding: 18px 40px;
            border-radius: 10px;
            font-weight: 600;
            min-width: 180px;
            margin: 10px;
        }

        .btn-next {
            background: #007bff;
            color: white;
            border: none;
        }

        .btn-next:hover {
            background: #0056b3;
        }

        .btn-prev {
            background: #6c757d;
            color: white;
            border: none;
        }

        .btn-prev:hover {
            background: #545b62;
        }

        .btn-submit {
            background: #28a745;
            color: white;
            border: none;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        .summary-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }

        .summary-label {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .alert-custom {
            font-size: 20px;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .payment-method-card {
            border: 3px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method-card:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .payment-method-card.selected {
            border-color: #007bff;
            background: #e7f3ff;
        }

        .payment-method-card input[type="radio"] {
            width: 24px;
            height: 24px;
            margin-right: 15px;
        }

        .payment-method-label {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
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
    </style>
</head>
<body>
    <div class="main-container">
        <a href="csf-funds.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to CSF Funds
        </a>

        <div class="header-section">
            <h1><i class="fas fa-receipt"></i> Record Payment</h1>
            <p class="mb-0">Follow the steps below to record a member's CSF payment</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-custom">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-custom">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="progress-bar-container">
            <div class="step-indicator">
                <div class="step active" id="step-indicator-1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Member</div>
                </div>
                <div class="step" id="step-indicator-2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Amount</div>
                </div>
                <div class="step" id="step-indicator-3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Date</div>
                </div>
                <div class="step" id="step-indicator-4">
                    <div class="step-circle">4</div>
                    <div class="step-label">Method</div>
                </div>
                <div class="step" id="step-indicator-5">
                    <div class="step-circle">5</div>
                    <div class="step-label">Confirm</div>
                </div>
            </div>
        </div>

        <form method="POST" id="paymentForm">
            <input type="hidden" name="action" value="record_payment">

            <div class="form-section">
                <!-- Step 1: Select Member -->
                <div class="step-content active" id="step-1">
                    <h3 class="mb-4">Step 1: Select Member</h3>
                    <div class="mb-4">
                        <label class="form-label">Search Member</label>
                        <input type="text" class="form-control" id="member_search" placeholder="Type name, mobile, or use @Area1 @Akshit" autocomplete="off">
                        <input type="hidden" name="user_id" id="user_id" required>
                        <div id="search_results" style="position: relative; margin-top: 10px;"></div>
                        <small class="text-muted" style="font-size: 16px;">
                            <i class="fas fa-lightbulb"></i> <strong>Smart Search:</strong> Use @Area @Name to filter by area<br>
                            Example: <code>@Area1 @Akshit</code> finds Akshit in Area 1
                        </small>
                    </div>
                    <div id="selected_member" style="display: none; background: #e7f3ff; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <h5 style="margin-bottom: 10px;">Selected Member:</h5>
                        <div id="selected_member_info"></div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn btn-custom btn-next" onclick="nextStep(1)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Enter Amount -->
                <div class="step-content" id="step-2">
                    <h3 class="mb-4">Step 2: Enter Amount</h3>
                    <div class="mb-4">
                        <label class="form-label">Payment Amount (₹)</label>
                        <input type="number" class="form-control" name="amount" id="amount"
                               value="<?php echo $default_amount; ?>" min="1" step="0.01" required>
                        <small class="text-muted" style="font-size: 16px;">
                            Default monthly contribution: ₹<?php echo number_format($default_amount, 2); ?>
                        </small>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn btn-custom btn-prev" onclick="prevStep(2)">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-custom btn-next" onclick="nextStep(2)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Select Date -->
                <div class="step-content" id="step-3">
                    <h3 class="mb-4">Step 3: Select Payment Date</h3>
                    <div class="mb-4">
                        <label class="form-label">Payment Date</label>
                        <input type="date" class="form-control" name="payment_date" id="payment_date"
                               value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn btn-custom btn-prev" onclick="prevStep(3)">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-custom btn-next" onclick="nextStep(3)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Payment Method -->
                <div class="step-content" id="step-4">
                    <h3 class="mb-4">Step 4: Payment Method</h3>
                    <div class="mb-4">
                        <label class="form-label">How was the payment made?</label>

                        <div class="payment-method-card" onclick="selectPaymentMethod('cash')">
                            <input type="radio" name="payment_method" value="cash" id="method_cash" required>
                            <label for="method_cash" class="payment-method-label">
                                <i class="fas fa-money-bill-wave"></i> Cash
                            </label>
                        </div>

                        <div class="payment-method-card" onclick="selectPaymentMethod('upi')">
                            <input type="radio" name="payment_method" value="upi" id="method_upi">
                            <label for="method_upi" class="payment-method-label">
                                <i class="fas fa-mobile-alt"></i> UPI / PhonePe / Google Pay
                            </label>
                        </div>

                        <div class="payment-method-card" onclick="selectPaymentMethod('bank_transfer')">
                            <input type="radio" name="payment_method" value="bank_transfer" id="method_bank">
                            <label for="method_bank" class="payment-method-label">
                                <i class="fas fa-university"></i> Bank Transfer
                            </label>
                        </div>

                        <div class="payment-method-card" onclick="selectPaymentMethod('cheque')">
                            <input type="radio" name="payment_method" value="cheque" id="method_cheque">
                            <label for="method_cheque" class="payment-method-label">
                                <i class="fas fa-money-check"></i> Cheque
                            </label>
                        </div>
                    </div>

                    <div class="mb-4" id="transaction_id_field" style="display: none;">
                        <label class="form-label">Transaction Reference Number (Optional)</label>
                        <input type="text" class="form-control" name="transaction_id" id="transaction_id">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn btn-custom btn-prev" onclick="prevStep(4)">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-custom btn-next" onclick="nextStep(4)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 5: Confirmation -->
                <div class="step-content" id="step-5">
                    <h3 class="mb-4">Step 5: Confirm Payment Details</h3>

                    <div class="summary-item">
                        <div class="summary-label">Member</div>
                        <div class="summary-value" id="summary_member">-</div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Amount</div>
                        <div class="summary-value" id="summary_amount">-</div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Payment Date</div>
                        <div class="summary-value" id="summary_date">-</div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">Payment Method</div>
                        <div class="summary-value" id="summary_method">-</div>
                    </div>

                    <div class="summary-item" id="summary_reference_container" style="display: none;">
                        <div class="summary-label">Reference Number</div>
                        <div class="summary-value" id="summary_reference">-</div>
                    </div>

                    <div class="summary-item" id="summary_notes_container" style="display: none;">
                        <div class="summary-label">Notes</div>
                        <div class="summary-value" id="summary_notes">-</div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn btn-custom btn-prev" onclick="prevStep(5)">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="submit" class="btn btn-custom btn-submit">
                            <i class="fas fa-check"></i> Record Payment
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 5;
        let selectedMemberName = '';
        let selectedMemberMobile = '';
        let selectedMemberArea = '';

        function updateStepIndicators() {
            for (let i = 1; i <= totalSteps; i++) {
                const indicator = document.getElementById('step-indicator-' + i);
                indicator.classList.remove('active', 'completed');

                if (i < currentStep) {
                    indicator.classList.add('completed');
                } else if (i === currentStep) {
                    indicator.classList.add('active');
                }
            }
        }

        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById('step-' + step).classList.add('active');
            currentStep = step;
            updateStepIndicators();
            window.scrollTo(0, 0);
        }

        function validateStep(step) {
            switch(step) {
                case 1:
                    const userId = document.getElementById('user_id').value;
                    if (!userId) {
                        alert('Please select a member');
                        return false;
                    }
                    return true;
                case 2:
                    const amount = document.getElementById('amount').value;
                    if (!amount || amount <= 0) {
                        alert('Please enter a valid amount');
                        return false;
                    }
                    return true;
                case 3:
                    const date = document.getElementById('payment_date').value;
                    if (!date) {
                        alert('Please select a payment date');
                        return false;
                    }
                    return true;
                case 4:
                    const method = document.querySelector('input[name="payment_method"]:checked');
                    if (!method) {
                        alert('Please select a payment method');
                        return false;
                    }
                    return true;
                default:
                    return true;
            }
        }

        function nextStep(step) {
            if (validateStep(step)) {
                if (step === 4) {
                    updateSummary();
                }
                showStep(step + 1);
            }
        }

        function prevStep(step) {
            showStep(step - 1);
        }

        function selectPaymentMethod(method) {
            document.querySelectorAll('.payment-method-card').forEach(card => {
                card.classList.remove('selected');
            });

            const radio = document.getElementById('method_' + method);
            radio.checked = true;
            radio.closest('.payment-method-card').classList.add('selected');

            // Show/hide reference number field
            const refField = document.getElementById('transaction_id_field');
            if (method !== 'cash') {
                refField.style.display = 'block';
            } else {
                refField.style.display = 'none';
            }
        }

        function updateSummary() {
            // Member
            document.getElementById('summary_member').textContent = selectedMemberName + ' (' + selectedMemberMobile + ')';

            // Amount
            const amount = document.getElementById('amount').value;
            document.getElementById('summary_amount').textContent = '₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Date
            const date = document.getElementById('payment_date').value;
            const dateObj = new Date(date);
            document.getElementById('summary_date').textContent = dateObj.toLocaleDateString('en-IN', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });

            // Method
            const method = document.querySelector('input[name="payment_method"]:checked').value;
            const methodLabels = {
                'cash': 'Cash',
                'upi': 'UPI / PhonePe / Google Pay',
                'bank_transfer': 'Bank Transfer',
                'cheque': 'Cheque'
            };
            document.getElementById('summary_method').textContent = methodLabels[method];

            // Reference Number
            const reference = document.getElementById('transaction_id').value;
            if (reference) {
                document.getElementById('summary_reference').textContent = reference;
                document.getElementById('summary_reference_container').style.display = 'block';
            } else {
                document.getElementById('summary_reference_container').style.display = 'none';
            }

            // Notes
            const notes = document.getElementById('notes').value;
            if (notes) {
                document.getElementById('summary_notes').textContent = notes;
                document.getElementById('summary_notes_container').style.display = 'block';
            } else {
                document.getElementById('summary_notes_container').style.display = 'none';
            }
        }

        // Add click listeners to payment method cards
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                selectPaymentMethod(radio.value);
            });
        });

        // Smart Search Autocomplete
        let searchTimeout;
        const memberSearchInput = document.getElementById('member_search');
        const searchResults = document.getElementById('search_results');
        const userIdInput = document.getElementById('user_id');
        const selectedMemberDiv = document.getElementById('selected_member');
        const selectedMemberInfo = document.getElementById('selected_member_info');

        memberSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`/public/group-admin/csf-api-search-member.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.members.length > 0) {
                            let html = '<div style="background: white; border: 2px solid #007bff; border-radius: 10px; max-height: 400px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">';

                            data.members.forEach(member => {
                                html += `<div class="search-result-item" onclick="selectMember(${member.user_id}, '${escapeHtml(member.full_name)}', '${escapeHtml(member.mobile_number)}', '${escapeHtml(member.sub_community_name)}')"
                                    style="padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; font-size: 18px;">
                                    <strong>${escapeHtml(member.full_name)}</strong><br>
                                    <span style="color: #666; font-size: 16px;">
                                        <i class="fas fa-phone"></i> ${escapeHtml(member.mobile_number)} |
                                        <i class="fas fa-map-marker-alt"></i> ${escapeHtml(member.sub_community_name)}
                                    </span>
                                </div>`;
                            });

                            html += '</div>';
                            searchResults.innerHTML = html;
                        } else {
                            searchResults.innerHTML = '<div style="padding: 15px; background: #fff3cd; border-radius: 8px; color: #856404;">No members found</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        searchResults.innerHTML = '<div style="padding: 15px; background: #f8d7da; border-radius: 8px; color: #721c24;">Search failed</div>';
                    });
            }, 300);
        });

        function selectMember(userId, fullName, mobile, area) {
            userIdInput.value = userId;
            memberSearchInput.value = fullName;
            searchResults.innerHTML = '';

            // Store member details globally for summary
            selectedMemberName = fullName;
            selectedMemberMobile = mobile;
            selectedMemberArea = area;

            selectedMemberInfo.innerHTML = `
                <div style="font-size: 20px;">
                    <strong>${fullName}</strong><br>
                    <span style="color: #666;">
                        <i class="fas fa-phone"></i> ${mobile} |
                        <i class="fas fa-map-marker-alt"></i> ${area}
                    </span>
                </div>
            `;
            selectedMemberDiv.style.display = 'block';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Clear search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!memberSearchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.innerHTML = '';
            }
        });
    </script>
</body>
</html>
