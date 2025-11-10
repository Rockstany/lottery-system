<?php
session_start();
include("../includes/db.php");
if(!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$user_id = $_SESSION['user_id'];
$orig_id = intval($_GET['id'] ?? 0);

// Fetch original lottery + params
$orig = $conn->query("SELECT * FROM lotteries WHERE id=$orig_id AND user_id=$user_id")->fetch_assoc();
if(!$orig){ die("Lottery not found"); }

$param = $conn->query("SELECT * FROM lottery_params WHERE lottery_id=$orig_id")->fetch_assoc();

// --- Create new lottery ---
$new_lottery_uid = "LTRY-".date("Ymd")."-".str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
$name_copy = $orig['name']." (Copy)";
$conn->query("INSERT INTO lotteries (lottery_id,user_id,name,status_stage)
              VALUES ('$new_lottery_uid',$user_id,'$name_copy',1)");
$new_id = $conn->insert_id;

// --- Copy Stage 1 params ---
$conn->query("INSERT INTO lottery_params (lottery_id,cost_per_ticket,first_ticket_no,tickets_per_book,no_of_books)
              VALUES ($new_id,{$param['cost_per_ticket']},{$param['first_ticket_no']},
                      {$param['tickets_per_book']},{$param['no_of_books']})");

// --- Generate new booklets ---
$cost = $param['cost_per_ticket'];
$first = $param['first_ticket_no'];
$books = $param['no_of_books'];
$perbook = $param['tickets_per_book'];

for($i=0;$i<$books;$i++){
    $start=$first+($i*$perbook);
    $end=$start+$perbook-1;
    $conn->query("INSERT INTO booklets (lottery_id,booklet_no,start_ticket,end_ticket)
                  VALUES ($new_id,".($i+1).",$start,$end)");
}

header("Location: dashboard.php?msg=duplicated");
exit;

include_once("../includes/log_action.php");
log_action($conn, $user_id, "Duplicated Lottery", "Original ID: $orig_id → New ID: $new_id");

add_notification($conn,$user_id,"Lottery Duplicated","Copy of Lottery $orig_id created.");

?>

