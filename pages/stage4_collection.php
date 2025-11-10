<?php
session_start();
include("../includes/db.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$user_id = $_SESSION['user_id'];
$lottery_id = $_GET['id'] ?? $_POST['id'] ?? 0;

// Handle new payment
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['booklet_id'])){
  $booklet_id = $_POST['booklet_id'];
  $amount = $_POST['amount'];
  $mode = $_POST['payment_mode'];
  $date = $_POST['payment_date'];

  // Expected amount = cost * tickets_per_book
  $q = $conn->query("SELECT lp.cost_per_ticket, lp.tickets_per_book 
                     FROM lottery_params lp JOIN booklets b ON b.lottery_id=lp.lottery_id
                     WHERE b.id=$booklet_id");
  $d = $q->fetch_assoc();
  $expected = $d['cost_per_ticket'] * $d['tickets_per_book'];

  // Insert payment
  $stmt = $conn->prepare("INSERT INTO payments (lottery_id, booklet_id, amount, payment_mode, payment_date, recorded_by)
                          VALUES (?,?,?,?,?,?)");
  $stmt->bind_param("iisssi",$lottery_id,$booklet_id,$amount,$mode,$date,$user_id);
  $stmt->execute();

  // Update booklet status
  $status = ($amount >= $expected) ? 'paid' : 'partial';
  $conn->query("UPDATE booklets SET status='$status' WHERE id=$booklet_id");

  $msg = "✅ Payment recorded: ₹$amount ($status)";
}

include_once("../includes/log_action.php");
log_action($conn, $user_id, "Payment Recorded", "Lottery $lottery_id, Booklet $booklet_id, Amount ₹$amount");

add_notification($conn,$user_id,"Payment Recorded","Received ₹$amount for booklet $booklet_id.");

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Stage 4 – Collection / Payment</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
  <h2>Stage 4: Collection / Payment</h2>
</header>

<div class="container">
<?php if(!empty($msg)) echo "<script>showToast('$msg');</script>"; ?>

<h3>Record Payment</h3>
<form method="POST">
  <input type="hidden" name="id" value="<?=$lottery_id?>">
  <label>Select Booklet:</label>
  <select name="booklet_id" required>
    <option value="">-- Select --</option>
    <?php
      $bks = $conn->query("SELECT * FROM booklets WHERE lottery_id=$lottery_id ORDER BY booklet_no ASC");
      while($b=$bks->fetch_assoc()){
         echo "<option value='{$b['id']}'>Booklet {$b['booklet_no']} ({$b['start_ticket']}-{$b['end_ticket']}) – {$b['status']}</option>";
      }
    ?>
  </select>

  <input type="number" name="amount" placeholder="Amount Received ₹" step="0.01" required>
  <label>Payment Mode:</label>
  <select name="payment_mode">
    <option value="cash">Cash</option>
    <option value="bank_transfer">Bank Transfer</option>
    <option value="upi">UPI</option>
    <option value="card">Card</option>
    <option value="cheque">Cheque</option>
  </select>

  <label>Date Received:</label>
  <input type="date" name="payment_date" required>

  <button type="submit">Record Payment</button>
</form>

<hr>
<h3>💰 Payments History</h3>
<table>
<tr><th>Booklet No</th><th>Range</th><th>Amount</th><th>Mode</th><th>Date</th><th>Status</th></tr>
<?php
$res = $conn->query("SELECT b.booklet_no,b.start_ticket,b.end_ticket,b.status,p.amount,p.payment_mode,p.payment_date
                     FROM booklets b 
                     LEFT JOIN payments p ON b.id=p.booklet_id
                     WHERE b.lottery_id=$lottery_id ORDER BY b.booklet_no ASC");
while($r=$res->fetch_assoc()){
  echo "<tr>
          <td>{$r['booklet_no']}</td>
          <td>{$r['start_ticket']} – {$r['end_ticket']}</td>
          <td>₹{$r['amount']}</td>
          <td>{$r['payment_mode']}</td>
          <td>{$r['payment_date']}</td>
          <td>{$r['status']}</td>
        </tr>";
}
?>
</table>

<form method="POST" action="stage5_summary.php">
  <input type="hidden" name="id" value="<?=$lottery_id?>">
  <button style="margin-top:20px" onclick="return confirm('Once submitted, Stage 4 will lock and proceed to Summary. Continue?')">
    Proceed to Stage 5 →
  </button>
</form>
</div>
<script src="../assets/js/toast.js"></script>
</body>
</html>
