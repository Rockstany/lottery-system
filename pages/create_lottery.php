<?php
session_start();
include("../includes/db.php");

if($_SERVER['REQUEST_METHOD']=='POST'){
  $name = $_POST['lottery_name'];
  $cost = $_POST['cost'];
  $first = $_POST['first_no'];
  $books = $_POST['no_books'];
  $perbook = $_POST['tickets_per_book'];

  // unique ID
  $lottery_uid = "LTRY-".date("Ymd")."-".str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
  $user = $_SESSION['user_id'];

  // insert main record
  $conn->query("INSERT INTO lotteries (lottery_id,user_id,name) VALUES ('$lottery_uid',$user,'$name')");
  $lottery_pk = $conn->insert_id;

  $conn->query("INSERT INTO lottery_params (lottery_id,cost_per_ticket,first_ticket_no,tickets_per_book,no_of_books)
   VALUES ($lottery_pk,$cost,$first,$perbook,$books)");

  // generate booklets
  for($i=0;$i<$books;$i++){
     $start=$first+($i*$perbook);
     $end=$start+$perbook-1;
     $conn->query("INSERT INTO booklets (lottery_id,booklet_no,start_ticket,end_ticket)
       VALUES ($lottery_pk,".($i+1).",$start,$end)");
  }

  header("Location: dashboard.php");
  header("Location: stage2_distribution.php?id=".$lottery_pk);
  exit;
}

include("../includes/log_action.php");
log_action($conn, $_SESSION['user_id'], "Created Lottery", "Lottery ID: $new_lottery_uid");

add_notification($conn,$user_id,"Lottery Created","Lottery $new_lottery_uid created successfully.");

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Create Lottery</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<h2>Create New Lottery</h2>
<form method="POST">
  <input type="text" name="lottery_name" placeholder="Lottery Name" required>
  <input type="number" name="cost" placeholder="Cost per Ticket" required>
  <input type="number" name="first_no" placeholder="First Ticket No" required>
  <input type="number" name="no_books" placeholder="No. of Books" required>
  <input type="number" name="tickets_per_book" placeholder="Tickets per Book" required>
  <button type="submit">Generate</button>
</form>
<script src="../assets/js/toast.js"></script>
</body>
</html>
