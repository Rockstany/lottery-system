<?php
include("includes/db.php");
$table = $_POST['table_name'] ?? '';
if(!$table) die("No table selected");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$table.'_export_'.date("Ymd_His").'.csv"');

$out = fopen('php://output', 'w');
$res = $conn->query("SHOW COLUMNS FROM `$table`");
$cols = [];
while($col = $res->fetch_assoc()) $cols[] = $col['Field'];
fputcsv($out, $cols);

$data = $conn->query("SELECT * FROM `$table`");
while($row = $data->fetch_assoc()){
  fputcsv($out, $row);
}
fclose($out);
exit;
?>
