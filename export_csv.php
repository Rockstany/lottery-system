<?php
include("includes/db.php");
$lottery_id = $_POST['lottery_id'] ?? 0;
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="lottery_'.$lottery_id.'_report.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['Booklet','Start','End','Status','Amount','Mode','Date']);

$q = $conn->query("SELECT b.booklet_no,b.start_ticket,b.end_ticket,b.status,
                          p.amount,p.payment_mode,p.payment_date
                   FROM booklets b 
                   LEFT JOIN payments p ON b.id=p.booklet_id
                   WHERE b.lottery_id=$lottery_id
                   ORDER BY b.booklet_no ASC");
while($r=$q->fetch_assoc()){
  fputcsv($out, $r);
}
fclose($out);
exit;
?>
