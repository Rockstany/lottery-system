<?php
/**
 * CSF Excel Export - Generate Excel file for accounting
 * Exports payment data in Excel format for easy accounting
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

// Get selected month and year
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$month_name = date('F Y', strtotime("$selected_year-$selected_month-01"));

// Get community name
$stmt = $db->prepare("SELECT community_name FROM communities WHERE community_id = ?");
$stmt->execute([$communityId]);
$community = $stmt->fetch(PDO::FETCH_ASSOC);
$community_name = $community['community_name'] ?? 'Community';

// Get all members in the community
$stmt = $db->prepare("SELECT scm.user_id, u.full_name, u.mobile_number as phone, u.email,
                             sc.sub_community_name as area
                       FROM sub_community_members scm
                       JOIN users u ON scm.user_id = u.user_id
                       JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                       WHERE sc.community_id = ? AND scm.status = 'active'
                       ORDER BY u.full_name");
$stmt->execute([$communityId]);
$all_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payments for selected month
$stmt = $db->prepare("SELECT
                           cp.payment_id,
                           cp.user_id,
                           cp.amount,
                           cp.payment_date,
                           cp.payment_method,
                           cp.transaction_id,
                           cp.notes,
                           u_collected.full_name as collected_by_name,
                           cp.created_at
                       FROM csf_payments cp
                       LEFT JOIN users u_collected ON cp.collected_by = u_collected.user_id
                       WHERE cp.community_id = ?
                       AND MONTH(cp.payment_date) = ?
                       AND YEAR(cp.payment_date) = ?
                       ORDER BY cp.payment_date DESC, cp.created_at DESC");
$stmt->execute([$communityId, $selected_month, $selected_year]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create payment lookup array
$payment_lookup = [];
foreach ($payments as $payment) {
    $payment_lookup[$payment['user_id']] = $payment;
}

// Classify members
$paid_members = [];
$unpaid_members = [];

foreach ($all_members as $member) {
    $user_id = $member['user_id'];

    if (isset($payment_lookup[$user_id])) {
        $member['payment_info'] = $payment_lookup[$user_id];
        $paid_members[] = $member;
    } else {
        $unpaid_members[] = $member;
    }
}

// Calculate statistics
$total_members = count($all_members);
$paid_count = count($paid_members);
$unpaid_count = count($unpaid_members);
$total_collected = array_sum(array_column($payments, 'amount'));
$collection_rate = $total_members > 0 ? ($paid_count / $total_members) * 100 : 0;

// ====================== GENERATE EXCEL FILE ======================

// Set headers for Excel download
$filename = "CSF_Report_" . $community_name . "_" . $selected_year . "_" . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . ".csv";
$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename); // Sanitize filename

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// ===== SUMMARY SECTION =====
fputcsv($output, ["CSF PAYMENT REPORT"]);
fputcsv($output, ["Community:", $community_name]);
fputcsv($output, ["Month:", $month_name]);
fputcsv($output, ["Generated On:", date('d F Y, h:i A')]);
fputcsv($output, [""]);

fputcsv($output, ["SUMMARY"]);
fputcsv($output, ["Total Members:", $total_members]);
fputcsv($output, ["Paid Members:", $paid_count]);
fputcsv($output, ["Unpaid Members:", $unpaid_count]);
fputcsv($output, ["Collection Rate:", number_format($collection_rate, 2) . "%"]);
fputcsv($output, ["Total Amount Collected:", "₹" . number_format($total_collected, 2)]);
fputcsv($output, [""]);
fputcsv($output, [""]);

// ===== PAID MEMBERS SECTION =====
if (!empty($paid_members)) {
    fputcsv($output, ["PAID MEMBERS (" . count($paid_members) . ")"]);
    fputcsv($output, [
        "Sr. No.",
        "Member Name",
        "Mobile Number",
        "Email",
        "Area",
        "Amount Paid",
        "Payment Date",
        "Payment Method",
        "Transaction ID",
        "Collected By",
        "Notes"
    ]);

    $sr_no = 1;
    foreach ($paid_members as $member) {
        $payment_info = $member['payment_info'];
        fputcsv($output, [
            $sr_no++,
            $member['full_name'],
            $member['phone'],
            $member['email'] ?: '-',
            $member['area'],
            number_format($payment_info['amount'], 2),
            date('d-M-Y', strtotime($payment_info['payment_date'])),
            ucfirst(str_replace('_', ' ', $payment_info['payment_method'])),
            $payment_info['transaction_id'] ?: '-',
            $payment_info['collected_by_name'] ?: '-',
            $payment_info['notes'] ?: '-'
        ]);
    }

    fputcsv($output, [""]);
    fputcsv($output, [""]);
}

// ===== UNPAID MEMBERS SECTION =====
if (!empty($unpaid_members)) {
    fputcsv($output, ["UNPAID MEMBERS (" . count($unpaid_members) . ")"]);
    fputcsv($output, [
        "Sr. No.",
        "Member Name",
        "Mobile Number",
        "Email",
        "Area",
        "Status"
    ]);

    $sr_no = 1;
    foreach ($unpaid_members as $member) {
        fputcsv($output, [
            $sr_no++,
            $member['full_name'],
            $member['phone'],
            $member['email'] ?: '-',
            $member['area'],
            "UNPAID"
        ]);
    }

    fputcsv($output, [""]);
    fputcsv($output, [""]);
}

// ===== FOOTER =====
fputcsv($output, [""]);
fputcsv($output, ["Report generated by: GetToKnow CSF System"]);
fputcsv($output, ["Website: zatana.in"]);

fclose($output);
exit();
?>
