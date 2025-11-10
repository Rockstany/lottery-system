<?php
session_start();
include("../includes/db.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$user_id = $_SESSION['user_id'];
$lottery_id = $_GET['id'] ?? $_POST['id'] ?? 0;

// ---- Fetch basic info
$lot = $conn->query("SELECT * FROM lotteries WHERE id=$lottery_id")->fetch_assoc();
$name = $lot['name'];

// ---- Totals
$sum = $conn->query("SELECT 
    COUNT(*) as total_books,
    SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as full_paid,
    SUM(CASE WHEN status='partial' THEN 1 ELSE 0 END) as partial_paid
    FROM booklets WHERE lottery_id=$lottery_id")->fetch_assoc();

$totalBooks = $sum['total_books'] ?? 0;
$full = $sum['full_paid'] ?? 0;
$partial = $sum['partial_paid'] ?? 0;
$outstanding = $totalBooks - ($full + $partial);

// ---- Payment totals by mode
$modeRes = $conn->query("SELECT payment_mode, SUM(amount) as amt 
                         FROM payments WHERE lottery_id=$lottery_id GROUP BY payment_mode");
$modes = [];
while($r=$modeRes->fetch_assoc()){ $modes[$r['payment_mode']] = $r['amt']; }

// ---- Total amount received
$totalAmount = array_sum($modes);

// ---- Handle Delete
if(isset($_POST['delete'])){
    // delete all child records safely
    $conn->query("DELETE FROM payments WHERE lottery_id=$lottery_id");
    $conn->query("DELETE FROM assignments WHERE lottery_id=$lottery_id");
    $conn->query("DELETE FROM booklets WHERE lottery_id=$lottery_id");
    $conn->query("DELETE FROM lottery_params WHERE lottery_id=$lottery_id");
    $conn->query("DELETE FROM lotteries WHERE id=$lottery_id");
    header("Location: dashboard.php?msg=deleted");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Stage 5 – Summary / Reports</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
  <h2>Stage 5: Summary / Reports</h2>
</header>

<div class="container">
<h3><?=$name?></h3>
<p>Total Books: <?=$totalBooks?></p>
<p>Full Paid: <?=$full?></p>
<p>Partial: <?=$partial?></p>
<p>Outstanding: <?=$outstanding?></p>
<p><strong>Total Amount Received: ₹<?=$totalAmount?></strong></p>

<hr>
<h3>Charts</h3>
<canvas id="barChart" height="120"></canvas>
<canvas id="pieChart" height="120"></canvas>

<script>
const barData = {
  labels: <?=json_encode(array_keys($modes))?>,
  datasets: [{label: 'Amount by Mode', data: <?=json_encode(array_values($modes))?>}]
};
new Chart(document.getElementById('barChart'), {type:'bar', data:barData});
new Chart(document.getElementById('pieChart'), {type:'pie', data:barData});
</script>

<hr>
<h3>Export / Manage</h3>
<form method="POST" action="../export_csv.php">
  <input type="hidden" name="lottery_id" value="<?=$lottery_id?>">
  <button type="submit">Export CSV</button>
</form>

<form method="POST" onsubmit="return confirm('Are you sure you want to delete this lottery? This cannot be undone.')">
  <button type="submit" name="delete" style="background:#f44336;margin-top:10px;">Delete Lottery</button>
</form>
</div>
</body>
</html>
