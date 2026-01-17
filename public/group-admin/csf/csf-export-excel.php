<?php
/**
 * CSF Excel Export - Generate Excel file for accounting
 * Exports payment data in Excel format for easy accounting
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/feature-access.php';

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

// Get date range (start month/year to end month/year)
$start_month = $_GET['start_month'] ?? date('m');
$start_year = $_GET['start_year'] ?? date('Y');
$end_month = $_GET['end_month'] ?? date('m');
$end_year = $_GET['end_year'] ?? date('Y');

// Create date strings for comparison
$start_date = $start_year . '-' . str_pad($start_month, 2, '0', STR_PAD_LEFT);
$end_date = $end_year . '-' . str_pad($end_month, 2, '0', STR_PAD_LEFT);

// Date range label for display
$date_range_label = date('F Y', strtotime($start_date)) . ' to ' . date('F Y', strtotime($end_date));

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

// Generate list of months in the date range
$months_in_range = [];
$current = new DateTime($start_date . '-01');
$end = new DateTime($end_date . '-01');

while ($current <= $end) {
    $months_in_range[] = $current->format('Y-m');
    $current->modify('+1 month');
}

// Get ALL payments within the date range
$stmt = $db->prepare("SELECT
                           cp.user_id,
                           cp.amount,
                           cp.payment_date,
                           cp.payment_method,
                           cp.transaction_id,
                           cp.payment_for_months,
                           cp.notes,
                           u_collected.full_name as collected_by_name,
                           cp.created_at
                       FROM csf_payments cp
                       LEFT JOIN users u_collected ON cp.collected_by = u_collected.user_id
                       WHERE cp.community_id = ?
                       AND cp.payment_for_months REGEXP ?
                       ORDER BY cp.payment_date DESC");

// Create regex pattern to match any month in range
$month_pattern = implode('|', array_map(function($m) {
    return preg_quote($m, '/');
}, $months_in_range));

$stmt->execute([$communityId, $month_pattern]);
$all_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create payment lookup array grouped by user and month
$payment_lookup = [];
$month_wise_data = [];

// Initialize month-wise data
foreach ($months_in_range as $month) {
    $month_wise_data[$month] = [
        'paid_users' => [],
        'total_amount' => 0,
        'paid_count' => 0
    ];
}

foreach ($all_payments as $payment) {
    $user_id = $payment['user_id'];
    $months_json = json_decode($payment['payment_for_months'], true);

    if (!empty($months_json) && is_array($months_json)) {
        $payment_month = $months_json[0];

        if (in_array($payment_month, $months_in_range)) {
            if (!isset($payment_lookup[$user_id])) {
                $payment_lookup[$user_id] = [
                    'months_paid' => [],
                    'month_details' => [],
                    'total_amount' => 0,
                    'payment_count' => 0
                ];
            }

            $payment_lookup[$user_id]['months_paid'][] = $payment_month;
            $payment_lookup[$user_id]['month_details'][$payment_month] = [
                'amount' => $payment['amount'],
                'method' => $payment['payment_method'],
                'date' => $payment['payment_date']
            ];
            $payment_lookup[$user_id]['total_amount'] += $payment['amount'];
            $payment_lookup[$user_id]['payment_count']++;

            // Update month-wise stats
            $month_wise_data[$payment_month]['paid_users'][] = $user_id;
            $month_wise_data[$payment_month]['total_amount'] += $payment['amount'];
            $month_wise_data[$payment_month]['paid_count']++;
        }
    }
}

// Classify members: paid at least once in range vs never paid in range
$paid_members = [];
$unpaid_members = [];

foreach ($all_members as $member) {
    $user_id = $member['user_id'];

    if (isset($payment_lookup[$user_id]) && !empty($payment_lookup[$user_id]['months_paid'])) {
        $member['payment_info'] = $payment_lookup[$user_id];
        $paid_members[] = $member;
    } else {
        $member['payment_info'] = null;
        $unpaid_members[] = $member;
    }
}

// Calculate statistics
$total_members = count($all_members);
$paid_count = count($paid_members);
$unpaid_count = count($unpaid_members);

// Total collected across all months in the date range
$total_collected = 0;
foreach ($month_wise_data as $month => $data) {
    $total_collected += $data['total_amount'];
}

$collection_rate = $total_members > 0 ? ($paid_count / $total_members) * 100 : 0;

// ====================== GENERATE EXCEL FILE ======================

// Set headers for Excel download
$filename = "CSF_Report_" . $community_name . "_" . $start_date . "_to_" . $end_date . ".csv";
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
fputcsv($output, ["Date Range:", $date_range_label]);
fputcsv($output, ["Generated On:", date('d F Y, h:i A')]);
fputcsv($output, [""]);

fputcsv($output, ["SUMMARY"]);
fputcsv($output, ["Total Members:", $total_members]);
fputcsv($output, ["Paid Members (at least once):", $paid_count]);
fputcsv($output, ["Unpaid Members:", $unpaid_count]);
fputcsv($output, ["Collection Rate:", number_format($collection_rate, 2) . "%"]);
fputcsv($output, ["Total Amount Collected:", "₹" . number_format($total_collected, 2)]);
fputcsv($output, [""]);

// ===== MONTH-WISE BREAKDOWN =====
fputcsv($output, ["MONTH-WISE BREAKDOWN"]);
fputcsv($output, ["Month", "Paid Members", "Unpaid Members", "Amount Collected", "Collection Rate"]);

foreach ($months_in_range as $month) {
    $month_data = $month_wise_data[$month];
    $month_paid = count(array_unique($month_data['paid_users']));
    $month_unpaid = $total_members - $month_paid;
    $month_rate = $total_members > 0 ? ($month_paid / $total_members) * 100 : 0;
    $month_label = date('F Y', strtotime($month . '-01'));

    fputcsv($output, [
        $month_label,
        $month_paid,
        $month_unpaid,
        "₹" . number_format($month_data['total_amount'], 2),
        number_format($month_rate, 2) . "%"
    ]);
}
fputcsv($output, [""]);
fputcsv($output, [""]);

// ===== PAID MEMBERS SECTION =====
if (!empty($paid_members)) {
    fputcsv($output, ["PAID MEMBERS (" . count($paid_members) . ")"]);

    // Build dynamic headers with months
    $headers = ["Sr. No.", "Member Name", "Mobile Number", "Email", "Area"];
    foreach ($months_in_range as $month) {
        $month_label = date('M Y', strtotime($month . '-01'));
        $headers[] = $month_label . " (Amount)";
        $headers[] = $month_label . " (Method)";
    }
    $headers[] = "Total Amount";
    $headers[] = "Months Paid";
    fputcsv($output, $headers);

    $sr_no = 1;
    foreach ($paid_members as $member) {
        $payment_info = $member['payment_info'];
        $months_paid = isset($payment_info['months_paid']) ? $payment_info['months_paid'] : [];
        $month_details = isset($payment_info['month_details']) ? $payment_info['month_details'] : [];

        $row = [
            $sr_no++,
            $member['full_name'],
            $member['phone'],
            $member['email'] ?: '-',
            $member['area']
        ];

        // Add per-month details
        foreach ($months_in_range as $month) {
            if (isset($month_details[$month])) {
                $row[] = "₹" . number_format($month_details[$month]['amount'], 2);
                $row[] = ucfirst(str_replace('_', ' ', $month_details[$month]['method']));
            } else {
                $row[] = "-";
                $row[] = "-";
            }
        }

        $row[] = "₹" . number_format($payment_info['total_amount'], 2);
        $row[] = count($months_paid);

        fputcsv($output, $row);
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
